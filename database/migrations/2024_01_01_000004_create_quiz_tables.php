<?php

declare(strict_types=1);

use App\Core\Database\Migration\Migration;
use App\Core\Database\Migration\Schema;
use App\Core\Database\Migration\Blueprint;

/**
 * Create quiz engine tables.
 */
class CreateQuizTables extends Migration
{
    public function up(): void
    {
        // Quizzes Table
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->references('id')->on('lessons')->onDelete('CASCADE');
            $table->integer('time_limit')->default(0);  // Minutes, 0 = unlimited
            $table->integer('passing_score')->default(70);  // Percentage
            $table->integer('allowed_attempts')->default(0);  // 0 = unlimited
            $table->boolean('is_randomized')->default(false);
            $table->boolean('show_correct_answers')->default(true);
            $table->string('status', 20)->default('draft');  // draft, published
            $table->timestamps();
            $table->unique(['lesson_id']);
        });

        // Questions Table
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->references('id')->on('quizzes')->onDelete('CASCADE');
            $table->string('type', 20);  // single_choice, multi_choice, true_false, short_text
            $table->text('question_text');
            $table->string('media_url')->nullable()->default(null);
            $table->integer('points')->default(1);
            $table->text('options')->nullable();  // JSON Array
            $table->text('correct_answers')->nullable();  // JSON Array
            $table->text('explanation')->nullable()->default(null);
            $table->string('grading_policy', 20)->default('auto');  // auto, manual, ai_assisted
            $table->text('ai_rubric')->nullable()->default(null);  // AI grading rubric
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->check('type', ['single_choice', 'multi_choice', 'true_false', 'short_text', 'fill_blank', 'matching', 'essay', 'video_submission']);
        });

        // Quiz Attempts Table
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->foreignId('quiz_id')->references('id')->on('quizzes')->onDelete('CASCADE');
            $table->integer('score')->nullable()->default(null);
            $table->boolean('is_passed')->default(false);
            $table->datetime('started_at')->default('CURRENT_TIMESTAMP');
            $table->datetime('completed_at')->nullable()->default(null);
            $table->string('grading_status', 20)->default('final');  // pending, graded, final
            $table->text('answers')->nullable();  // JSON storing user responses
        });

        // Attempt Requests Table (for requesting additional attempts)
        Schema::create('attempt_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->foreignId('quiz_id')->references('id')->on('quizzes')->onDelete('CASCADE');
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->check('status', ['pending', 'approved', 'rejected']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_requests');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('quizzes');
    }

    public function getDescription(): string
    {
        return 'Create quiz engine tables: quizzes, questions, quiz_attempts, attempt_requests';
    }
}
