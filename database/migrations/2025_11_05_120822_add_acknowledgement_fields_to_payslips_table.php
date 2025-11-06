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
        Schema::table('payslips', function (Blueprint $table) {
            $table->timestamp('acknowledged_at')->nullable()->after('sent_at');
            $table->uuid('acknowledged_by')->nullable()->after('acknowledged_at');
            $table->foreign('acknowledged_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropForeign(['acknowledged_by']);
            $table->dropColumn(['acknowledged_at', 'acknowledged_by']);
        });
    }
};
