<?php

namespace Tests\Feature;

use App\Models\BranchOffice;
use App\Services\Fincloud;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AtmrSourceOptimizationTest extends TestCase
{
    private string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databasePath = tempnam(sys_get_temp_dir(), 'salak-atmr-');
        $sqlite = [
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ];

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => $sqlite,
            'database.connections.mysql' => $sqlite,
            'database.connections.edapem' => $sqlite,
        ]);

        foreach (['sqlite', 'mysql', 'edapem'] as $connection) {
            DB::purge($connection);
            DB::reconnect($connection);
        }

        $this->createAtmrSchema();
    }

    protected function tearDown(): void
    {
        foreach (['sqlite', 'mysql', 'edapem'] as $connection) {
            DB::disconnect($connection);
        }

        if (is_file($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function test_fetch_commands_replace_and_deduplicate_daily_snapshots(): void
    {
        $fincloud = Mockery::mock(Fincloud::class);
        $fincloud->shouldReceive('login')->times(6);
        $fincloud
            ->shouldReceive('inquiryCbrCustomerReport')
            ->times(3)
            ->andReturnUsing(fn () => $this->lines([
                'cif_no|owner_group|debtor_group|unused',
                'CIF-1|874|UK|first',
                'CIF-1|875|UM|last',
                'CIF-2|100|UM|other',
            ]));
        $fincloud
            ->shouldReceive('inquiryLoanCollateralListReport')
            ->times(3)
            ->andReturn(implode("\n", [
                'loan_acc_no|collateral_type|unused',
                'LOAN-1|Other|ignored',
                'LOAN-2|Land / Building,Other|kept',
                'LOAN-2|Land / Building|last',
                'LOAN-3|Vehicle|ignored',
                'LOAN-4|Building|kept',
            ]));
        $this->app->instance(Fincloud::class, $fincloud);

        foreach (['2026-07-29', '2026-07-29', '2026-07-28'] as $date) {
            $this->artisan("app:fetch-cbr-customer-report {$date}")->assertSuccessful();
            $this->artisan("app:fetch-loan-collateral-list {$date}")->assertSuccessful();
        }

        $this->assertSame(4, DB::table('cbr_customers')->count());
        $this->assertSame(4, DB::table('loan_collateral_lists')->count());
        $this->assertDatabaseHas('cbr_customers', [
            'fetch_date' => '2026-07-29',
            'cif_no' => 'CIF-1',
            'owner_group' => '875',
            'debtor_group' => 'UM',
        ]);
        $this->assertDatabaseMissing('loan_collateral_lists', [
            'loan_acc_no' => 'LOAN-1',
        ]);
        $this->assertDatabaseHas('loan_collateral_lists', [
            'fetch_date' => '2026-07-29',
            'loan_acc_no' => 'LOAN-2',
            'collateral_type' => 'Land / Building',
        ]);
    }

    public function test_shadow_migration_keeps_latest_cbr_and_only_land_collateral(): void
    {
        Schema::drop('cbr_customers');
        Schema::drop('loan_collateral_lists');

        Schema::create('cbr_customers', function (Blueprint $table) {
            $table->id();
            $table->date('fetch_date')->nullable();
            $table->string('cif_no');
            $table->string('owner_group')->nullable();
            $table->string('debtor_group')->nullable();
        });
        Schema::create('loan_collateral_lists', function (Blueprint $table) {
            $table->id();
            $table->date('fetch_date')->nullable();
            $table->string('loan_acc_no');
            $table->string('collateral_type')->nullable();
        });

        DB::table('cbr_customers')->insert([
            ['fetch_date' => '2026-07-29', 'cif_no' => 'CIF-1', 'owner_group' => '874', 'debtor_group' => 'UK'],
            ['fetch_date' => '2026-07-29', 'cif_no' => 'CIF-1', 'owner_group' => '875', 'debtor_group' => 'UM'],
            ['fetch_date' => '2026-07-28', 'cif_no' => 'CIF-1', 'owner_group' => '100', 'debtor_group' => 'UM'],
        ]);
        DB::table('loan_collateral_lists')->insert([
            ['fetch_date' => '2026-07-29', 'loan_acc_no' => 'LOAN-1', 'collateral_type' => 'Other'],
            ['fetch_date' => '2026-07-29', 'loan_acc_no' => 'LOAN-2', 'collateral_type' => 'Land / Building'],
            ['fetch_date' => '2026-07-29', 'loan_acc_no' => 'LOAN-2', 'collateral_type' => 'Building'],
            ['fetch_date' => '2026-07-28', 'loan_acc_no' => 'LOAN-2', 'collateral_type' => 'Land'],
            ['fetch_date' => '2026-07-28', 'loan_acc_no' => 'LOAN-3', 'collateral_type' => 'Vehicle'],
        ]);

        $migration = require database_path('migrations/2026_07_30_130000_compact_atmr_source_tables.php');
        $migration->up();

        $this->assertSame(2, DB::table('cbr_customers')->count());
        $this->assertSame(2, DB::table('loan_collateral_lists')->count());
        $this->assertSame('875', DB::table('cbr_customers')
            ->where('fetch_date', '2026-07-29')
            ->where('cif_no', 'CIF-1')
            ->value('owner_group'));
        $this->assertSame('Building', DB::table('loan_collateral_lists')
            ->where('fetch_date', '2026-07-29')
            ->value('collateral_type'));
        $this->assertSame('Land', DB::table('loan_collateral_lists')
            ->where('fetch_date', '2026-07-28')
            ->value('collateral_type'));
        $this->assertTrue(Schema::hasTable('cbr_customers_legacy'));
        $this->assertTrue(Schema::hasTable('loan_collateral_lists_legacy'));

        $migration->down();

        $this->assertSame(3, DB::table('cbr_customers')->count());
        $this->assertSame(5, DB::table('loan_collateral_lists')->count());
    }

    public function test_atmr_categories_keep_their_expected_weights(): void
    {
        $date = '2026-07-29';
        $this->insertLoan($date, 'BAD', 'CIF-BAD', null, 5);
        $this->insertLoan($date, 'LAND', 'CIF-LAND');
        $this->insertLoan($date, 'MSO-874', 'CIF-MSO-874', 'ALT-874');
        $this->insertLoan($date, 'CBR-874', 'CIF-CBR-874');
        $this->insertLoan($date, 'CBR-UMK', 'CIF-CBR-UMK');
        $this->insertLoan($date, 'MSO-UMK', 'CIF-MSO-UMK', 'ALT-UMK');
        $this->insertLoan($date, 'REMAINING', 'CIF-REMAINING');

        DB::table('loan_collateral_lists')->insert([
            'fetch_date' => $date,
            'loan_acc_no' => 'LAND',
            'collateral_type' => 'Land / Building',
        ]);
        DB::table('mso_loan_atmrs')->insert([
            [
                'loan_account' => 'ALT-874',
                'loan_golongan_debitur' => '874',
                'loan_jenis_usaha' => '3',
            ],
            [
                'loan_account' => 'ALT-UMK',
                'loan_golongan_debitur' => '100',
                'loan_jenis_usaha' => '1',
            ],
        ]);
        DB::table('cbr_customers')->insert([
            [
                'fetch_date' => $date,
                'cif_no' => 'CIF-CBR-874',
                'owner_group' => '874',
                'debtor_group' => 'XX',
            ],
            [
                'fetch_date' => $date,
                'cif_no' => 'CIF-CBR-UMK',
                'owner_group' => '100',
                'debtor_group' => 'UM',
            ],
            [
                'fetch_date' => '2026-07-28',
                'cif_no' => 'CIF-MSO-UMK',
                'owner_group' => '100',
                'debtor_group' => 'UM',
            ],
        ]);

        $this->assertEqualsWithDelta(470.0, BranchOffice::konsolidasiATMR($date), 0.0001);
    }

    private function createAtmrSchema(): void
    {
        Schema::create('saldo_neracas', function (Blueprint $table) {
            $table->id();
            $table->string('cabang')->nullable();
            $table->date('tanggal');
            $table->string('noakun');
            $table->decimal('saldoakhir', 15, 2)->default(0);
        });
        Schema::create('loan_outstandings', function (Blueprint $table) {
            $table->id();
            $table->date('loan_date_params');
            $table->string('loan_branch_office');
            $table->string('loan_account');
            $table->string('loan_cif');
            $table->string('loan_alt_account')->nullable();
            $table->decimal('loan_principal', 15, 2)->default(0);
            $table->decimal('loan_installment_loans', 15, 2)->default(0);
            $table->integer('loan_bi_collectability')->default(1);
            $table->decimal('loan_outstanding', 15, 2)->default(0);
        });
        Schema::create('cbr_customers', function (Blueprint $table) {
            $table->date('fetch_date');
            $table->string('cif_no');
            $table->string('owner_group')->nullable();
            $table->string('debtor_group')->nullable();
            $table->primary(['fetch_date', 'cif_no']);
        });
        Schema::create('loan_collateral_lists', function (Blueprint $table) {
            $table->date('fetch_date');
            $table->string('loan_acc_no');
            $table->string('collateral_type')->nullable();
            $table->primary(['fetch_date', 'loan_acc_no']);
        });
        Schema::create('mso_loan_atmrs', function (Blueprint $table) {
            $table->id();
            $table->string('loan_account')->unique();
            $table->string('loan_golongan_debitur')->nullable();
            $table->string('loan_jenis_usaha')->nullable();
        });
        Schema::create('payroll_dapem_masters', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id');
            $table->decimal('nominal_dapem', 15, 2)->default(0);
            $table->date('bulan_dapem')->nullable();
        });
    }

    private function insertLoan(
        string $date,
        string $account,
        string $cif,
        ?string $alternateAccount = null,
        int $collectability = 1,
    ): void {
        DB::connection('mysql')->table('loan_outstandings')->insert([
            'loan_date_params' => $date,
            'loan_branch_office' => '001',
            'loan_account' => $account,
            'loan_cif' => $cif,
            'loan_alt_account' => $alternateAccount,
            'loan_principal' => 100,
            'loan_installment_loans' => 0,
            'loan_bi_collectability' => $collectability,
            'loan_outstanding' => 100,
        ]);
    }

    private function lines(array $lines): \Generator
    {
        yield from $lines;
    }
}
