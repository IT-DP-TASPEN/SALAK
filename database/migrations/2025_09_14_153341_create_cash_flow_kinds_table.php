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
        Schema::create('cash_flow_kinds', function (Blueprint $table) {
            $table->id();
            $table->string('kind_name');
            $table->enum('kind_type', ['Cash In', 'Cash Out']);
            $table->string('kind_description')->nullable();
            $table->boolean('kind_pusat_only')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_flow_kinds');
    }
};
