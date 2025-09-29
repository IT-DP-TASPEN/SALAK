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
        Schema::create('perusahaan_asuransis', function (Blueprint $table) {
            $table->id();
            $table->string('asuransi_npwp');
            $table->string('asuransi_nama');
            $table->string('asuransi_alamat');
            $table->string('asuransi_telepon')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perusahaan_asuransis');
    }
};
