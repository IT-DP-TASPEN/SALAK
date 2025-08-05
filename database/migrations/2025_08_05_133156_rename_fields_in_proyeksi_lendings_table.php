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
            $table->renameColumn('lending_booking', 'lending_plafond');
            $table->renameColumn('lending_pot_asuransi', 'lending_pot_premi');
            $table->renameColumn('lending_pot_asuransi_extra', 'lending_pot_premi_extra');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyeksi_lendings', function (Blueprint $table) {
            $table->renameColumn('lending_plafond', 'lending_booking');
            $table->renameColumn('lending_pot_premi', 'lending_pot_asuransi');
            $table->renameColumn('lending_pot_premi_extra', 'lending_pot_asuransi_extra');
        });
    }
};
