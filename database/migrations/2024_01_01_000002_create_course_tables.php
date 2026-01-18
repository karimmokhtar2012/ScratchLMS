<?php

declare(strict_types=1);

use App\Core\Database\Migration\Migration;
use App\Core\Database\Migration\Schema;
use App\Core\Database\Migration\Blueprint;

/**
 * Create courses, modules, and lessons tables.
 */
class CreateCourseTables extends Migration
{
    public function up(): void
    {
        // Courses Table
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('thumbnail')->nullable()->default(null);
            $table->foreignId('instructor_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->string('status', 20)->default('draft');
            $table->datetime('published_at')->nullable()->default(null);
            $table->string('content_flow', 20)->default('free');
            $table->boolean('has_certificate')->default(false);
            $table->boolean('certificate_auto_issue')->default(false);
            $table->timestamps();
        });

        // Modules Table
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->references('id')->on('courses')->onDelete('CASCADE');
            $table->string('title');
            $table->integer('sort_order')->default(0);
        });

        // Lessons Table
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->integer('module_id')->nullable();  // Nullable for library lessons
            $table->foreignId('instructor_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->string('title');
            $table->string('type', 20)->default('video');
            $table->string('content_url')->nullable()->default(null);
            $table->text('content_text')->nullable()->default(null);  // Article/text content
            $table->string('attachment_url')->nullable()->default(null);
            $table->integer('duration')->nullable()->default(null);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_library')->default(false);
            $table->integer('library_owner_id')->nullable()->default(null);
            $table->timestamps();
            $table->check('type', ['video', 'article', 'quiz']);
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('CASCADE');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('courses');
    }

    public function getDescription(): string
    {
        return 'Create course structure tables: courses, modules, lessons';
    }
}
