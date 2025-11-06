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
        Schema::create('payslips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pay_period_id')->constrained()->cascadeOnDelete();

            $table->json('user_snapshot')->nullable(); // snapshot of UserDetail

            $table->decimal('gross_earnings', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);

            // Late/Absence tracking
            $table->decimal('late_hours', 8, 2)->default(0);
            $table->decimal('absence_days', 8, 2)->default(0);
            $table->decimal('late_deduction', 12, 2)->default(0);
            $table->decimal('absence_deduction', 12, 2)->default(0);
            $table->boolean('override_deductions')->default(false);

            $table->string('status')->default('draft'); // draft | sent | acknowledged
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
