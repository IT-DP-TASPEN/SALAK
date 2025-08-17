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
        Schema::create('proyeksi_lending_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('progress_lending')
                ->constrained('proyeksi_lendings')
                ->onDelete('cascade');
            $table->foreignId('progress_status')
                ->constrained('proyeksi_lending_progress_statuses')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyeksi_lending_progress');
    }
};
