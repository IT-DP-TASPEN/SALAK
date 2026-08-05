<?php

namespace Tests\Feature;

use App\Filament\Pages\SimulasiTks;
use App\Models\BranchOffice;
use App\Services\TksRatioSnapshotService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TksRatioCacheTest extends TestCase
{
    private string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-01-08 12:00:00');

        $this->databasePath = tempnam(sys_get_temp_dir(), 'salak-tks-cache-');

        $sharedSqliteConfig = [
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ];

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => $sharedSqliteConfig,
            'database.connections.mysql' => $sharedSqliteConfig,
            'database.connections.edapem' => $sharedSqliteConfig,
            'cache.default' => 'array',
            'cache.stores.rekap_tks' => [
                'driver' => 'database',
                'connection' => 'sqlite',
                'table' => 'rekap_tks_cache',
                'lock_connection' => 'sqlite',
                'lock_table' => 'rekap_tks_cache_locks',
            ],
            'neraca.tks_cache_store' => 'rekap_tks',
        ]);

        Cache::forgetDriver(['array', 'rekap_tks']);

        DB::purge('sqlite');
        DB::purge('mysql');
        DB::purge('edapem');
        DB::reconnect('sqlite');
        DB::reconnect('mysql');
        DB::reconnect('edapem');

        Cache::flush();

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::flush();

        DB::disconnect('sqlite');
        DB::disconnect('mysql');
        DB::disconnect('edapem');

        if (is_file($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function test_historical_snapshot_is_cached_while_today_stays_live(): void
    {
        $historicalDate = '2026-01-07';
        $today = Carbon::today()->toDateString();

        $this->seedBranch('001', 'Cabang 1');
        $this->seedRatioSourceData($historicalDate, '001', savings: 400, loanOutstanding: 600);
        $this->seedRatioSourceData($today, '001', savings: 400, loanOutstanding: 600);

        $service = app(TksRatioSnapshotService::class);

        $historicalFirst = $service->getSnapshot($historicalDate, '001');
        $this->assertTrue($service->hasCachedSnapshot($historicalDate, '001'));

        $this->updateSavingsBalance($historicalDate, '001', 800);
        $historicalSecond = $service->getSnapshot($historicalDate, '001');

        $this->assertSame($historicalFirst, $historicalSecond);

        $todayFirst = $service->getSnapshot($today, '001');
        $this->assertFalse($service->hasCachedSnapshot($today, '001'));

        $this->updateSavingsBalance($today, '001', 800);
        $todaySecond = $service->getSnapshot($today, '001');

        $this->assertNotSame($todayFirst['ldr'], $todaySecond['ldr']);
        $this->assertFalse($service->hasCachedSnapshot($today, '001'));
    }

    public function test_command_warms_default_targets_and_keeps_branch_and_all_keys_separate(): void
    {
        $historicalDate = '2026-01-07';

        $this->seedBranch('001', 'Cabang 1');
        $this->seedBranch('002', 'Cabang 2');

        $this->seedSaldoOnlyDate('2026-01-06', '001');
        $this->seedRatioSourceData($historicalDate, '001', savings: 400, loanOutstanding: 600);
        $this->seedRatioSourceData($historicalDate, '002', savings: 900, loanOutstanding: 300);

        $this->artisan('cache:warm-rekap-tks')
            ->expectsOutputToContain('Selesai warm cache Rekap TKS')
            ->expectsOutputToContain('Warmed baru: 3')
            ->assertSuccessful();

        $service = app(TksRatioSnapshotService::class);

        $this->assertTrue($service->hasCachedSnapshot($historicalDate, null));
        $this->assertTrue($service->hasCachedSnapshot($historicalDate, '001'));
        $this->assertTrue($service->hasCachedSnapshot($historicalDate, '002'));
        $this->assertFalse($service->hasCachedSnapshot('2026-01-06', null));

        $allSnapshot = $this->tksCache()->get($service->cacheKey($historicalDate, null));
        $branchSnapshot = $this->tksCache()->get($service->cacheKey($historicalDate, '001'));

        $this->assertNotSame($service->cacheKey($historicalDate, null), $service->cacheKey($historicalDate, '001'));
        $this->assertNotSame($allSnapshot['ldr'], $branchSnapshot['ldr']);
    }

    public function test_refresh_option_overwrites_existing_historical_cache(): void
    {
        $historicalDate = '2026-01-07';

        $this->seedBranch('001', 'Cabang 1');
        $this->seedRatioSourceData($historicalDate, '001', savings: 400, loanOutstanding: 600);

        $service = app(TksRatioSnapshotService::class);

        $this->artisan('cache:warm-rekap-tks --branch=001')
            ->expectsOutputToContain('Warmed baru: 1')
            ->assertSuccessful();

        $initialSnapshot = $this->tksCache()->get($service->cacheKey($historicalDate, '001'));
        $this->updateSavingsBalance($historicalDate, '001', 800);

        $this->artisan('cache:warm-rekap-tks --branch=001')
            ->expectsOutputToContain('Sudah tercache: 1')
            ->assertSuccessful();

        $cachedWithoutRefresh = $this->tksCache()->get($service->cacheKey($historicalDate, '001'));
        $this->assertSame($initialSnapshot['ldr'], $cachedWithoutRefresh['ldr']);

        $this->artisan('cache:warm-rekap-tks --branch=001 --refresh')
            ->expectsOutputToContain('Refreshed: 1')
            ->assertSuccessful();

        $refreshedSnapshot = $this->tksCache()->get($service->cacheKey($historicalDate, '001'));
        $this->assertNotSame($initialSnapshot['ldr'], $refreshedSnapshot['ldr']);
    }

    public function test_optimize_clear_keeps_tks_cache_while_clearing_default_cache(): void
    {
        $historicalDate = '2026-01-07';

        $this->seedBranch('001', 'Cabang 1');
        $this->seedRatioSourceData($historicalDate, '001', savings: 400, loanOutstanding: 600);

        $service = app(TksRatioSnapshotService::class);
        $cacheKey = $service->cacheKey($historicalDate, '001');

        $this->assertNotNull($service->warmSnapshot($historicalDate, '001'));
        Cache::put('regular-cache-key', 'will-be-cleared', 3600);

        $this->assertTrue(Cache::has('regular-cache-key'));
        $this->assertTrue($service->hasCachedSnapshot($historicalDate, '001'));

        $this->artisan('optimize:clear')
            ->assertSuccessful();

        $this->assertFalse(Cache::has('regular-cache-key'));
        $this->assertTrue($service->hasCachedSnapshot($historicalDate, '001'));
        $this->assertIsArray($this->tksCache()->get($cacheKey));
    }

    public function test_command_exits_successfully_when_no_eligible_historical_dates_exist(): void
    {
        $this->seedBranch('001', 'Cabang 1');

        $this->artisan('cache:warm-rekap-tks')
            ->expectsOutputToContain('Tidak ada tanggal historis yang eligible untuk warm cache Rekap TKS.')
            ->assertSuccessful();
    }

    public function test_simulasi_tks_ldr_defaults_match_rekap_tks_components(): void
    {
        $date = Carbon::today()->toDateString();

        $this->seedBranch('001', 'Cabang 1');
        $this->seedLdrSourceData($date, '001');

        $page = app(SimulasiTks::class);
        $reflection = new \ReflectionClass($page);

        $defaultComponents = $reflection
            ->getMethod('defaultComponentsForRatio')
            ->invoke($page, 'LDR', $date);
        $simulation = $reflection
            ->getMethod('calculateSimulation')
            ->invoke($page, 'LDR', $defaultComponents);
        $rekapComponents = BranchOffice::konsolidasiLDRComponents($date, false, null);
        $rekapLdr = BranchOffice::konsolidasiLDR($date, false, null);

        $this->assertEqualsWithDelta(650.0, $rekapComponents['total_baki_debet'], 0.0001);
        $this->assertEqualsWithDelta(650.0, $rekapComponents['total_simpanan'], 0.0001);
        $this->assertEqualsWithDelta($rekapComponents['total_baki_debet'], $defaultComponents['Total Loans'], 0.0001);
        $this->assertEqualsWithDelta($rekapComponents['total_simpanan'], $defaultComponents['Total Deposits'], 0.0001);
        $this->assertEqualsWithDelta($rekapLdr, $simulation['value'], 0.0001);
    }

    private function createSchema(): void
    {
        Schema::create('branch_offices', function (Blueprint $table): void {
            $table->id();
            $table->string('branch_code')->nullable();
            $table->string('branch_code_fincloud')->unique();
            $table->string('branch_name')->nullable();
            $table->decimal('branch_saldo_aba_blokir', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('saldo_neracas', function (Blueprint $table): void {
            $table->id();
            $table->char('cabang', 3);
            $table->date('tanggal');
            $table->string('noakun');
            $table->decimal('saldoakhir', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('loan_outstandings', function (Blueprint $table): void {
            $table->id();
            $table->date('loan_date_params');
            $table->char('loan_branch_office', 3);
            $table->string('loan_account');
            $table->string('loan_cif')->nullable();
            $table->string('loan_alt_account')->nullable();
            $table->decimal('loan_principal', 15, 2)->default(0);
            $table->decimal('loan_installment_loans', 15, 2)->default(0);
            $table->integer('loan_bi_collectability')->default(1);
            $table->integer('loan_days_past_due')->default(0);
            $table->decimal('loan_outstanding', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('loan_collateral_lists', function (Blueprint $table): void {
            $table->id();
            $table->string('loan_acc_no')->nullable();
            $table->date('fetch_date')->nullable();
            $table->string('collateral_type')->nullable();
            $table->timestamps();
        });

        Schema::create('cbr_customers', function (Blueprint $table): void {
            $table->id();
            $table->string('cif_no')->nullable();
            $table->char('branch', 3)->nullable();
            $table->date('fetch_date')->nullable();
            $table->string('owner_group')->nullable();
            $table->string('debtor_group')->nullable();
            $table->timestamps();
        });

        Schema::create('mso_loan_atmrs', function (Blueprint $table): void {
            $table->id();
            $table->string('loan_account')->nullable();
            $table->string('loan_cif')->nullable();
            $table->string('loan_golongan_debitur')->nullable();
            $table->string('loan_jenis_usaha')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_dapem_masters', function (Blueprint $table): void {
            $table->id();
            $table->string('customer_id')->nullable();
            $table->decimal('nominal_dapem', 15, 2)->default(0);
            $table->date('bulan_dapem')->nullable();
            $table->timestamps();
        });

        Schema::create('rekap_tks_cache', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('rekap_tks_cache_locks', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    private function tksCache()
    {
        return Cache::store((string) config('neraca.tks_cache_store', 'rekap_tks'));
    }

    private function seedBranch(string $code, string $name): void
    {
        DB::table('branch_offices')->insert([
            'branch_code' => substr($code, -2),
            'branch_code_fincloud' => $code,
            'branch_name' => $name,
            'branch_saldo_aba_blokir' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedSaldoOnlyDate(string $date, string $branch): void
    {
        DB::table('saldo_neracas')->insert([
            'cabang' => $branch,
            'tanggal' => $date,
            'noakun' => '221',
            'saldoakhir' => -100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedRatioSourceData(string $date, string $branch, int $savings, int $loanOutstanding): void
    {
        $now = now();

        $saldoRows = [
            '1272005' => -10,
            '5' => 100,
            '558' => 0,
            '4' => -200,
            '401' => -150,
            '402' => -50,
            '403' => -20,
            '410' => -80,
            '501' => 10,
            '502' => 5,
            '511' => 5,
            '211' => -20,
            '212' => -20,
            '213' => -20,
            '219' => -20,
            '2011008' => -20,
            '2011001' => -20,
            '2011004' => -20,
            '2011005' => -20,
            '2011006' => -20,
            '2011007' => -20,
            '208' => -20,
            '221' => -$savings,
            '2312200' => -200,
            '2312201' => -100,
            '300' => -500,
            '3111000' => -100,
            '3111001' => -50,
            '322' => -60,
            '323' => -120,
            '302' => -10,
            '100' => 150,
            '110' => 100,
            '111' => 50,
            '112' => 50,
            '1' => 2_000,
            '121' => $loanOutstanding,
            '150' => 20,
        ];

        DB::table('saldo_neracas')->insert(array_map(
            fn(string $noakun, int|float $saldoakhir): array => [
                'cabang' => $branch,
                'tanggal' => $date,
                'noakun' => $noakun,
                'saldoakhir' => $saldoakhir,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            array_keys($saldoRows),
            $saldoRows,
        ));

        DB::connection('mysql')->table('loan_outstandings')->insert([
            'loan_date_params' => $date,
            'loan_branch_office' => $branch,
            'loan_account' => 'LN-' . $branch . '-' . str_replace('-', '', $date),
            'loan_cif' => 'CIF-' . $branch,
            'loan_alt_account' => 'ALT-' . $branch,
            'loan_principal' => $loanOutstanding,
            'loan_installment_loans' => 25,
            'loan_bi_collectability' => 1,
            'loan_days_past_due' => 0,
            'loan_outstanding' => $loanOutstanding,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function updateSavingsBalance(string $date, string $branch, int $savings): void
    {
        DB::table('saldo_neracas')
            ->where('tanggal', $date)
            ->where('cabang', $branch)
            ->where('noakun', '221')
            ->update([
                'saldoakhir' => -$savings,
                'updated_at' => now(),
            ]);
    }

    private function seedLdrSourceData(string $date, string $branch): void
    {
        $now = now();

        $saldoRows = [
            '121' => 600,
            '122' => 50,
            '221' => -500,
            '2312200' => -200,
            '2312201' => -100,
            '2212111' => -20,
            '2212116' => -10,
            '2212199' => -120,
        ];

        DB::table('saldo_neracas')->insert(array_map(
            fn(string $noakun, int|float $saldoakhir): array => [
                'cabang' => $branch,
                'tanggal' => $date,
                'noakun' => $noakun,
                'saldoakhir' => $saldoakhir,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            array_keys($saldoRows),
            $saldoRows,
        ));

        DB::connection('mysql')->table('loan_outstandings')->insert([
            'loan_date_params' => $date,
            'loan_branch_office' => $branch,
            'loan_account' => 'LDR-' . $branch . '-' . str_replace('-', '', $date),
            'loan_cif' => 'CIF-LDR-' . $branch,
            'loan_alt_account' => 'ALT-LDR-' . $branch,
            'loan_principal' => 650,
            'loan_installment_loans' => 25,
            'loan_bi_collectability' => 1,
            'loan_days_past_due' => 0,
            'loan_outstanding' => 650,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
