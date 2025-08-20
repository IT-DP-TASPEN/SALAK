<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('branch_offices', function (Blueprint $table) {
            $table
                ->decimal('branch_saldo_aba_blokir', 15, 2)
                ->default(0)
                ->after('branch_name')
                ->comment('Saldo yang diblokir untuk keperluan tertentu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_offices', function (Blueprint $table) {
            $table->dropColumn('branch_saldo_aba_blokir');
        });
    }
};
