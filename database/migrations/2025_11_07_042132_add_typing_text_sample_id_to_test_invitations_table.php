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
        Schema::table('test_invitations', function (Blueprint $table) {
            $table->foreignId('typing_text_sample_id')->nullable()->after('candidate_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_invitations', function (Blueprint $table) {
            $table->dropForeign(['typing_text_sample_id']);
            $table->dropColumn('typing_text_sample_id');
        });
    }
};
