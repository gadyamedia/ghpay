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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove role_id foreign key/column from users first to avoid constraint errors
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'role_id')) {
                    $table->dropConstrainedForeignId('role_id');
                }
            });
        }

        Schema::dropIfExists('roles');
    }
};
