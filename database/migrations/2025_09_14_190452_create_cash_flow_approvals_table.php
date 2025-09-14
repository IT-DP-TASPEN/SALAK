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
        Schema::create('cash_flow_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_cash_flow')
                ->constrained('cash_flows', 'id')
                ->onDelete('cascade');
            $table->foreignId('approval_user')
                ->nullable()
                ->constrained('users', 'id')
                ->onDelete('cascade');
            $table
                ->enum('approval_status', ['Pending', 'Approved', 'Rejected'])
                ->default('Pending');
            $table->text('approval_comment')->nullable();
            $table->timestamp('approval_approved_at')->nullable();
            $table->timestamp('approval_rejected_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_flow_approvals');
    }
};
