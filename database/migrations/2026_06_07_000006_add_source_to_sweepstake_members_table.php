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
        Schema::table('sweepstake_members', function (Blueprint $table) {
            $table->string('source')->default('join_link')->after('join_token');
            $table->index(['sweepstake_id', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sweepstake_members', function (Blueprint $table) {
            $table->dropIndex(['sweepstake_id', 'source']);
            $table->dropColumn('source');
        });
    }
};
