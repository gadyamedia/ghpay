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
        Schema::create('position_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->enum('type', ['text', 'textarea', 'yes_no', 'multiple_choice'])->default('textarea');
            $table->json('options')->nullable(); // for multiple_choice
            $table->boolean('is_required')->default(false);
            $table->integer('order')->default(0);
            $table->integer('scoring_weight')->nullable(); // for screening score
            $table->json('correct_answer')->nullable(); // for auto-scoring
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('position_questions');
    }
};
