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
        Schema::table('proyeksi_fundings', function (Blueprint $table) {
            $table->dropForeign(['funding_agent']);

            $table->foreign('funding_agent')
                ->references('id')
                ->on('agents')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyeksi_fundings', function (Blueprint $table) {
            $table->dropForeign(['lending_agent']);

            $table->foreign('lending_agent')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
