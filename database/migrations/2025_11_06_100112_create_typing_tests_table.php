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
        Schema::create('typing_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('typing_text_sample_id')->nullable()->constrained()->nullOnDelete();
            $table->text('original_text');
            $table->text('typed_text');
            $table->integer('wpm')->default(0); // Words per minute
            $table->decimal('accuracy', 5, 2)->default(0); // Percentage 0-100
            $table->integer('duration_seconds'); // How long they took
            $table->integer('total_characters');
            $table->integer('correct_characters');
            $table->integer('incorrect_characters');
            $table->json('keystroke_data')->nullable(); // For replay feature
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('typing_tests');
    }
};
