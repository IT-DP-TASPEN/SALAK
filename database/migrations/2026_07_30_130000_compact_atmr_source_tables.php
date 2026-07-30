<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('cbr_customers_legacy')
            || Schema::hasTable('loan_collateral_lists_legacy')
            || Schema::hasTable('cbr_customers_slim')
            || Schema::hasTable('loan_collateral_lists_slim')
        ) {
            throw new RuntimeException('ATMR source shadow/legacy tables already exist.');
        }

        Schema::create('cbr_customers_slim', function (Blueprint $table) {
            $table->date('fetch_date');
            $table->string('cif_no');
            $table->string('owner_group')->nullable();
            $table->string('debtor_group')->nullable();
            $table->primary(['fetch_date', 'cif_no']);
        });

        Schema::create('loan_collateral_lists_slim', function (Blueprint $table) {
            $table->date('fetch_date');
            $table->string('loan_acc_no');
            $table->string('collateral_type')->nullable();
            $table->primary(['fetch_date', 'loan_acc_no']);
        });

        try {
            $latestCbrRows = DB::table('cbr_customers')
                ->selectRaw('MAX(id) AS id')
                ->whereNotNull('fetch_date')
                ->where('cif_no', '<>', '')
                ->groupBy('fetch_date', 'cif_no');

            DB::table('cbr_customers_slim')->insertUsing(
                ['fetch_date', 'cif_no', 'owner_group', 'debtor_group'],
                DB::table('cbr_customers as source')
                    ->joinSub($latestCbrRows, 'latest', fn ($join) => $join->on('source.id', '=', 'latest.id'))
                    ->select([
                        'source.fetch_date',
                        'source.cif_no',
                        'source.owner_group',
                        'source.debtor_group',
                    ]),
            );

            DB::table('loan_collateral_lists_slim')->insertUsing(
                ['fetch_date', 'loan_acc_no', 'collateral_type'],
                DB::table('loan_collateral_lists')
                    ->select(['fetch_date', 'loan_acc_no'])
                    ->selectRaw('MIN(collateral_type) AS collateral_type')
                    ->whereNotNull('fetch_date')
                    ->where('loan_acc_no', '<>', '')
                    ->where(function ($query) {
                        $query->where('collateral_type', 'like', '%land%')
                            ->orWhere('collateral_type', 'like', '%building%');
                    })
                    ->groupBy('fetch_date', 'loan_acc_no'),
            );

            $this->swapTablesToSlim();
        } catch (Throwable $exception) {
            Schema::dropIfExists('cbr_customers_slim');
            Schema::dropIfExists('loan_collateral_lists_slim');

            throw $exception;
        }
    }

    public function down(): void
    {
        if (
            ! Schema::hasTable('cbr_customers_legacy')
            || ! Schema::hasTable('loan_collateral_lists_legacy')
        ) {
            throw new RuntimeException('ATMR legacy tables are unavailable; rollback is not possible.');
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'RENAME TABLE
                    cbr_customers TO cbr_customers_slim_rollback,
                    cbr_customers_legacy TO cbr_customers,
                    loan_collateral_lists TO loan_collateral_lists_slim_rollback,
                    loan_collateral_lists_legacy TO loan_collateral_lists'
            );
        } else {
            Schema::rename('cbr_customers', 'cbr_customers_slim_rollback');
            Schema::rename('cbr_customers_legacy', 'cbr_customers');
            Schema::rename('loan_collateral_lists', 'loan_collateral_lists_slim_rollback');
            Schema::rename('loan_collateral_lists_legacy', 'loan_collateral_lists');
        }

        Schema::drop('cbr_customers_slim_rollback');
        Schema::drop('loan_collateral_lists_slim_rollback');
    }

    private function swapTablesToSlim(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'RENAME TABLE
                    cbr_customers TO cbr_customers_legacy,
                    cbr_customers_slim TO cbr_customers,
                    loan_collateral_lists TO loan_collateral_lists_legacy,
                    loan_collateral_lists_slim TO loan_collateral_lists'
            );

            return;
        }

        Schema::rename('cbr_customers', 'cbr_customers_legacy');
        Schema::rename('cbr_customers_slim', 'cbr_customers');
        Schema::rename('loan_collateral_lists', 'loan_collateral_lists_legacy');
        Schema::rename('loan_collateral_lists_slim', 'loan_collateral_lists');
    }
};
