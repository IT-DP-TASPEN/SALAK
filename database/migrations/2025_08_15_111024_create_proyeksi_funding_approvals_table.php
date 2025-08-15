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
        Schema::create('proyeksi_funding_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_funding')
                ->constrained('proyeksi_fundings')
                ->onDelete('cascade');
            $table->foreignId('approval_user') // User who approved the funding
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade');
            $table
                ->enum('approval_status', ['Pending', 'Approved', 'Rejected'])
                ->default('Pending');
            $table->text('approval_comment')->nullable(); // Optional comments for the approval
            $table->timestamp('approval_approved_at')->nullable(); // Timestamp when the approval was made
            $table->timestamp('approval_rejected_at')->nullable(); // Timestamp when the rejection was made
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyeksi_funding_approvals');
    }
};
