<?php

declare(strict_types=1);

use App\Core\Database\Migration\Migration;
use App\Core\Database\Migration\Schema;
use App\Core\Database\Migration\Blueprint;

/**
 * Create users, roles, and permissions tables.
 */
class CreateAuthTables extends Migration
{
    public function up(): void
    {
        // Users Table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('bio', 500)->nullable()->default(null);
            $table->string('avatar')->nullable()->default(null);
            $table->boolean('seen_tutorial')->default(false);
            
            // Onboarding fields
            $table->boolean('is_onboarded')->default(false);
            $table->string('preferred_name')->nullable()->default(null);
            $table->string('gender', 20)->nullable()->default(null);
            $table->string('dob', 10)->nullable()->default(null);
            $table->string('country', 100)->nullable()->default(null);
            $table->string('phone', 30)->nullable()->default(null);
            $table->string('field_of_work', 100)->nullable()->default(null);
            $table->string('usage_intent', 100)->nullable()->default(null);
            $table->boolean('can_switch_role')->default(false);
            
            $table->timestamps();
        });

        // Roles Table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
        });

        // Permissions Table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
        });

        // Role User Pivot Table
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->foreignId('role_id')->references('id')->on('roles')->onDelete('CASCADE');
            $table->primaryKey(['user_id', 'role_id']);
        });

        // Insert default roles
        $this->seedRoles();
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
    }

    private function seedRoles(): void
    {
        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin'],
            ['name' => 'Instructor', 'slug' => 'instructor'],
            ['name' => 'Student', 'slug' => 'student'],
        ];

        foreach ($roles as $role) {
            $this->db->insertOrIgnore('roles', $role);
        }
    }

    public function getDescription(): string
    {
        return 'Create authentication tables: users, roles, permissions';
    }
}
