<?php

declare(strict_types=1);

use App\Core\Database\Migration\Migration;
use App\Core\Database\Migration\Schema;
use App\Core\Database\Migration\Blueprint;

/**
 * Create enrollments table.
 */
class CreateEnrollmentsTable extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->foreignId('course_id')->references('id')->on('courses')->onDelete('CASCADE');
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->boolean('is_completed')->default(false);
            $table->string('certificate_id')->nullable()->default(null);
            $table->datetime('certified_at')->nullable()->default(null);
            $table->datetime('enrolled_at')->default('CURRENT_TIMESTAMP');
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }

    public function getDescription(): string
    {
        return 'Create enrollments table with progress tracking and certificates';
    }
}
