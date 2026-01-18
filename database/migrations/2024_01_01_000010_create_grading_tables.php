<?php

declare(strict_types=1);

use App\Core\Database\Migration\Migration;
use App\Core\Database\Migration\Schema;
use App\Core\Database\Migration\Blueprint;

/**
 * Create grading workflow and legacy drip rules tables.
 */
class CreateGradingTables extends Migration
{
    public function up(): void
    {
        // Grading Queue - Items pending instructor review
        Schema::create('grading_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->references('id')->on('quiz_attempts')->onDelete('CASCADE');
            $table->foreignId('question_id')->references('id')->on('questions')->onDelete('CASCADE');
            $table->string('status', 20)->default('pending_review');
            $table->decimal('ai_score', 5, 2)->nullable()->default(null);
            $table->text('ai_feedback')->nullable()->default(null);
            $table->text('ai_rationale')->nullable()->default(null);
            $table->string('ai_rationale_version', 50)->nullable()->default(null);
            $table->decimal('final_score', 5, 2)->nullable()->default(null);
            $table->integer('reviewer_id')->nullable()->default(null);
            $table->timestamps();
            $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('SET NULL');
        });

        // Legacy Lesson Drip Rules - kept for compatibility
        Schema::create('lesson_drip_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->references('id')->on('lessons')->onDelete('CASCADE');
            $table->integer('unlock_after_days')->nullable()->default(null);
            $table->integer('unlock_after_lesson_id')->nullable()->default(null);
            $table->integer('unlock_after_percent')->nullable()->default(null);
            $table->unique(['lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_drip_rules');
        Schema::dropIfExists('grading_queue');
    }

    public function getDescription(): string
    {
        return 'Create grading queue and legacy lesson drip rules tables';
    }
}
