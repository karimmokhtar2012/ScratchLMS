<?php

declare(strict_types=1);

use App\Core\Database\Migration\Migration;
use App\Core\Database\Migration\Schema;
use App\Core\Database\Migration\Blueprint;

/**
 * Create notes and notifications tables.
 */
class CreateUserContentTables extends Migration
{
    public function up(): void
    {
        // Notes Table
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->foreignId('lesson_id')->references('id')->on('lessons')->onDelete('CASCADE');
            $table->text('content');
            $table->integer('timestamp')->nullable()->default(null);  // Seconds for video (optional)
            $table->timestamps();
        });

        // Notifications Table
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->string('type', 50);  // system, enrollment, quiz_request, quiz_result, course_update
            $table->string('title');
            $table->text('message');
            $table->string('link')->nullable()->default(null);
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notes');
    }

    public function getDescription(): string
    {
        return 'Create user content tables: notes, notifications';
    }
}
