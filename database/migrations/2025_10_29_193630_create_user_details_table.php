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
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('hourly_rate', 8, 2);
            $table->string('position');
            $table->string('gender');
            $table->string('civil_status');
            $table->string('nationality');
            $table->date('hire_date');
            $table->date('birthday');
            $table->text('pagibig');
            $table->text('sss');
            $table->text('tin');
            $table->text('philhealth');
            $table->address();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
