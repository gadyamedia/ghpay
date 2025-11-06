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
        Schema::table('pay_period_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignUuid('user_id')->nullable()->change()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pay_period_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignUuid('user_id')->nullable(false)->change()->constrained()->cascadeOnDelete();
        });
    }
};
