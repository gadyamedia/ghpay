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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('department')->nullable();
            $table->enum('employment_type', ['full-time', 'part-time', 'contract', 'internship'])->default('full-time');
            $table->enum('location_type', ['remote', 'hybrid', 'onsite'])->default('onsite');
            $table->string('location')->nullable();
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->text('responsibilities')->nullable();
            $table->text('benefits')->nullable();
            $table->integer('salary_min')->nullable();
            $table->integer('salary_max')->nullable();
            $table->boolean('show_salary')->default(false);
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->date('application_deadline')->nullable();

            // Typing test integration
            $table->boolean('require_typing_test')->default(false);
            $table->boolean('auto_send_typing_test')->default(false);
            $table->integer('minimum_wpm')->nullable();
            $table->foreignId('typing_text_sample_id')->nullable()->constrained()->nullOnDelete();

            // Notifications
            $table->boolean('notify_admin_on_application')->default(true);
            $table->string('notification_email')->nullable();

            // Meta
            $table->integer('views_count')->default(0);
            $table->integer('applications_count')->default(0);
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
