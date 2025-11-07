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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained()->nullOnDelete();

            // Applicant information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('location')->nullable();

            // Application content
            $table->text('cover_letter')->nullable();
            $table->string('resume_path')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();

            // References
            $table->json('references')->nullable();

            // Screening
            $table->json('screening_answers')->nullable();
            $table->integer('screening_score')->nullable();

            // Status tracking
            $table->enum('status', ['new', 'reviewed', 'typing_test_sent', 'typing_test_completed', 'interview', 'offer', 'hired', 'rejected'])->default('new');
            $table->text('admin_notes')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            // Tracking
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('source')->nullable(); // how they found the job

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
