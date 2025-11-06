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
        Schema::create('payslip_deductions', function (Blueprint $table) {
            $table->id();
            $table->uuid('payslip_id');
            $table->foreign('payslip_id')->references('id')->on('payslips')->cascadeOnDelete();

            $table->string('label');
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_deductions');
    }
};
