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
        Schema::table('proyeksi_lendings', function (Blueprint $table) {
            $table->decimal('lending_pot_provisi_percent', 5, 2)
                ->after('lending_pot_provisi')
                ->default(0)
                ->comment('Persentase potongan provisi dari plafond');
            $table->decimal('lending_pot_admin_percent', 5, 2)
                ->after('lending_pot_admin')
                ->default(0)
                ->comment('Persentase potongan administrasi dari plafond');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyeksi_lendings', function (Blueprint $table) {
            $table->dropColumn('lending_pot_admin_percent');
            $table->dropColumn('lending_pot_provisi_percent');
        });
    }
};
