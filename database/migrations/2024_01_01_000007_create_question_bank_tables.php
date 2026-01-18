<?php

declare(strict_types=1);

use App\Core\Database\Migration\Migration;
use App\Core\Database\Migration\Schema;
use App\Core\Database\Migration\Blueprint;

/**
 * Create question bank tables for reusable questions.
 */
class CreateQuestionBankTables extends Migration
{
    public function up(): void
    {
        // Question Banks (Folders)
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->string('title');
            $table->text('description')->nullable()->default(null);
            $table->timestamps();
        });

        // Bank Questions (Templates)
        Schema::create('bank_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->references('id')->on('question_banks')->onDelete('CASCADE');
            $table->string('type', 20);
            $table->text('question_text');
            $table->text('options')->nullable();  // JSON Array
            $table->text('correct_answers')->nullable();  // JSON Array
            $table->text('explanation')->nullable()->default(null);
            $table->timestamps();
            $table->check('type', ['single_choice', 'multi_choice', 'true_false', 'short_text']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_questions');
        Schema::dropIfExists('question_banks');
    }

    public function getDescription(): string
    {
        return 'Create question bank tables: question_banks, bank_questions';
    }
}
