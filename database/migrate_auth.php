<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    // Enable Foreign Keys for SQLite
    $db->exec('PRAGMA foreign_keys = ON;');

    // Users Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Roles Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE
        );
    ");

    // Permissions Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE
        );
    ");

    // Role User Pivot Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS role_user (
            user_id INTEGER NOT NULL,
            role_id INTEGER NOT NULL,
            PRIMARY KEY (user_id, role_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
        );
    ");

    // Insert Default Roles
    $stmt = $db->prepare("SELECT COUNT(*) FROM roles WHERE slug = :slug");
    $insertStmt = $db->prepare("INSERT INTO roles (name, slug) VALUES (:name, :slug)");

    $roles = [
        ['name' => 'Administrator', 'slug' => 'admin'],
        ['name' => 'Instructor', 'slug' => 'instructor'],
        ['name' => 'Student', 'slug' => 'student'],
    ];

    foreach ($roles as $role) {
        $stmt->execute([':slug' => $role['slug']]);
        if ($stmt->fetchColumn() == 0) {
            $insertStmt->execute([':name' => $role['name'], ':slug' => $role['slug']]);
        }
    }

    echo "✅ Auth Tables Migrated Successfully\n";

} catch (Exception $e) {
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}
