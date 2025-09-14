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
        Schema::create('cash_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_kind')->constrained('cash_flow_kinds')->onDelete('cascade');
            $table->foreignId('cash_kantor')->constrained('branch_offices')->onDelete('cascade');
            $table->foreignId('cash_user')->constrained('users')->onDelete('cascade');
            $table->date('cash_tanggal');
            $table->string('cash_keterangan');
            $table->decimal('cash_jumlah', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_flows');
    }
};
