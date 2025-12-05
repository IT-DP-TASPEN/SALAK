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
        Schema::create('mso_loan_atmrs', function (Blueprint $table) {
            $table->id();
            $table->string('loan_account')->unique();
            $table->string('loan_cif');
            $table->string('loan_golongan_debitur')->nullable();
            $table->string('loan_jenis_usaha')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mso_loan_atmrs');
    }
};
