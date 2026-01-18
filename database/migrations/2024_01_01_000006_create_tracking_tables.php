<?php

declare(strict_types=1);

use App\Core\Database\Migration\Migration;
use App\Core\Database\Migration\Schema;
use App\Core\Database\Migration\Blueprint;

/**
 * Create tracking tables for lesson progress and xAPI logs.
 */
class CreateTrackingTables extends Migration
{
    public function up(): void
    {
        // Completed Lessons Table
        Schema::create('completed_lessons', function (Blueprint $table) {
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->foreignId('lesson_id')->references('id')->on('lessons')->onDelete('CASCADE');
            $table->datetime('completed_at')->default('CURRENT_TIMESTAMP');
            $table->primaryKey(['user_id', 'lesson_id']);
        });

        // xAPI Logs Table
        Schema::create('xapi_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->foreignId('lesson_id')->references('id')->on('lessons')->onDelete('CASCADE');
            $table->string('verb', 50);
            $table->text('meta_data')->nullable()->default(null);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xapi_logs');
        Schema::dropIfExists('completed_lessons');
    }

    public function getDescription(): string
    {
        return 'Create tracking tables: completed_lessons, xapi_logs';
    }
}
