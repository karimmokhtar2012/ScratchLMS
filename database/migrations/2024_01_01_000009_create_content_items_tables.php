<?php

declare(strict_types=1);

use App\Core\Database\Migration\Migration;
use App\Core\Database\Migration\Schema;
use App\Core\Database\Migration\Blueprint;

/**
 * Create content structure tables for unified course content management.
 */
class CreateContentItemsTables extends Migration
{
    public function up(): void
    {
        // Content Items - The structural source of truth
        Schema::create('content_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->references('id')->on('courses')->onDelete('CASCADE');
            $table->foreignId('module_id')->references('id')->on('modules')->onDelete('CASCADE');
            $table->string('item_type', 20);  // 'lesson', 'quiz'
            $table->integer('item_id');
            $table->integer('position');
            $table->integer('display_group_id')->nullable()->default(null);
            $table->timestamps();
            $table->check('item_type', ['lesson', 'quiz']);
        });

        // Content Item Rules - Drip/unlock rules per item
        Schema::create('content_item_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->references('id')->on('content_items')->onDelete('CASCADE');
            $table->string('rule_type', 50);
            $table->text('params_json');
            $table->integer('version')->default(1);
        });

        // Content Rule Definitions - Rule schemas for UI
        Schema::create('content_rule_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('rule_type', 50);
            $table->integer('version')->default(1);
            $table->text('schema_json');
            $table->text('ui_schema_json');
            $table->timestamps();
        });

        // Content Prerequisites - Dependencies between items
        Schema::create('content_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->references('id')->on('content_items')->onDelete('CASCADE');
            $table->integer('prerequisite_item_id');
            $table->string('relation_type', 20)->default('completion');
            $table->foreign('prerequisite_item_id')->references('id')->on('content_items')->onDelete('CASCADE');
        });

        // Content Progress - User progress on content items
        Schema::create('content_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->foreignId('item_id')->references('id')->on('content_items')->onDelete('CASCADE');
            $table->string('status', 20)->default('not_started');
            $table->datetime('completed_at')->nullable()->default(null);
            $table->decimal('score', 5, 2)->nullable()->default(null);
            $table->integer('attempts')->default(0);
            $table->unique(['user_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_progress');
        Schema::dropIfExists('content_prerequisites');
        Schema::dropIfExists('content_rule_definitions');
        Schema::dropIfExists('content_item_rules');
        Schema::dropIfExists('content_items');
    }

    public function getDescription(): string
    {
        return 'Create unified content structure tables for course management';
    }
}
