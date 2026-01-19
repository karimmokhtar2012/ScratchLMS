# ScratchLMS Developer Guide

> **Mission-Critical Documentation**  
> This document establishes the mandatory standards for developing, modifying, or extending ScratchLMS.  
> **Non-compliance with security requirements is unacceptable.**

---

## Table of Contents

1. [System Architecture](#1-system-architecture)
2. [Page & Route Structure](#2-page--route-structure)
3. [Required Functions & Dependencies](#3-required-functions--dependencies)
4. [Security Layers (MANDATORY)](#4-security-layers-mandatory)
5. [UI Design Rules](#5-ui-design-rules)
6. [Development Requirements](#6-development-requirements)
7. [Quick Reference Checklists](#7-quick-reference-checklists)

---

## 1. System Architecture

### Directory Structure

```
LMS/
├── app/
│   ├── Core/              # Framework core (Router, Database, Controller, Csrf)
│   ├── Domain/            # Domain models (Users, Courses, Lessons, etc.)
│   ├── Exceptions/        # Custom exceptions
│   ├── Http/Controllers/  # HTTP request handlers
│   ├── Policies/          # Authorization policies
│   ├── Services/          # Business logic services
│   └── helpers.php        # Global helpers (env, config)
├── config/                # Configuration files
├── public/                # Web root (index.php, assets, uploads)
├── resources/views/       # View templates
├── routes/web.php         # Route definitions
└── docs/                  # Documentation
```

### MVC Pattern

```
Request → Router → Controller → Service → Model → Database
                       ↓
                    View ← Data
```

- **Controllers**: Handle HTTP requests, validate input, delegate to services
- **Services**: Encapsulate business logic, interact with models
- **Models**: Data access and manipulation
- **Views**: Display data (NO business logic in views!)

---

## 2. Page & Route Structure

### Controllers & Their Responsibilities

| Controller | Purpose | Auth Required |
|------------|---------|---------------|
| `AuthController` | Login, logout, registration | No |
| `OnboardingController` | New user setup | Yes |
| `DashboardController` | User/instructor dashboard | Yes |
| `CourseController` | Public course browsing | No |
| `LearnController` | Course player, progress tracking | Yes |
| `InstructorController` | Course building, student management | Yes (Instructor) |
| `QuizController` | Quiz management (CRUD) | Yes (Instructor) |
| `QuizRunnerController` | Quiz taking, submission | Yes |
| `ProfileController` | User profile management | Yes |
| `NoteController` | Student notes for lessons | Yes |
| `AdminController` | Platform administration | Yes (Admin) |
| `UploadController` | File upload handling | Yes |
| `NotificationController` | API for notifications | Yes |
| `CartController` | Shopping cart | Yes |
| `CheckoutController` | Payment processing | Yes |
| `CertificateController` | Certificate generation | Yes |
| `QuestionBankController` | Reusable question pools | Yes (Instructor) |
| `HomeController` | Public homepage | No |

### Route Naming Convention

```php
// Pattern: HTTP_METHOD /resource/action/parameter
GET  /courses                    → CourseController::index
GET  /course/{id}                → CourseController::show
POST /course/{id}/enroll         → CourseController::enroll
GET  /learn/{courseId}/lesson/{lessonId} → LearnController::player
POST /instructor/course/store    → InstructorController::store
```

### Adding New Routes

1. Open `routes/web.php`
2. Add route with correct HTTP method:

```php
use App\Http\Controllers\YourController;
$router->get('/your-route', [YourController::class, 'method']);
$router->post('/your-route', [YourController::class, 'method']);
```

---

## 3. Required Functions & Dependencies

### Helper Functions (Always Available)

#### Security Helpers (`app/Core/helpers.php`)

```php
// XSS Protection - USE IN ALL VIEWS!
e($string)  // Escapes HTML entities

// File Upload Validation
validateFileUpload(
    $file,                    // $_FILES array element
    $allowedExtensions,       // ['jpg', 'png', 'pdf']
    $allowedMimeTypes         // ['image/jpeg', 'image/png']
)

// Redirect Sanitization
sanitizeRedirect($path, $default = '/')  // Prevents open redirects

// Database Error Logging
logDatabaseError($query, $params, $error, $file, $line)
```

#### Input Sanitizer (`app/Core/InputSanitizer.php`)

```php
use App\Core\InputSanitizer;

// Sanitize text (strips HTML tags)
$name = InputSanitizer::text($_POST['name'] ?? '');

// Sanitize email (validates + sanitizes)
$email = InputSanitizer::email($_POST['email'] ?? '');

// Sanitize integers/floats
$age = InputSanitizer::int($_POST['age'] ?? 0);

// Sanitize URLs (HTTP/HTTPS only)
$website = InputSanitizer::url($_POST['website'] ?? '');

// Sanitize HTML with whitelist (for rich text)
$bio = InputSanitizer::html($_POST['bio'] ?? '', ['p', 'br', 'strong', 'em']);

// Batch sanitize
$data = InputSanitizer::sanitize($_POST, [
    'name' => 'text',
    'email' => 'email',
    'age' => ['int', 18],  // with default
]);
```

#### Global Helpers (`app/helpers.php`)

```php
env($key, $default)     // Get environment variable
config($key, $default)  // Get config value (dot notation: 'database.default')
loadEnvFile($path)      // Load .env file
```

### Database Operations

```php
// Get database instance
$db = \App\Core\Database::getInstance();

// Query methods
$db->query($sql, $params);          // Execute query
$db->fetchAll($sql, $params);       // Get multiple rows
$db->fetchOne($sql, $params);       // Get single row
$db->insertOrIgnore($table, $data); // Insert without duplicate errors
$db->upsert($table, $data, $uniqueColumn); // Insert or update
```

### Database Compatibility (PostgreSQL)

ScratchLMS supports SQLite, MySQL, and **PostgreSQL**. When writing SQL queries, follow these rules to ensure cross-database compatibility:

#### Boolean Comparisons

PostgreSQL requires boolean literals (`true`/`false`), not integers (`1`/`0`):

```php
// ✅ CORRECT (works on all databases)
$sql = "SELECT * FROM courses WHERE is_published = true";
$sql = "UPDATE enrollments SET is_completed = false WHERE id = :id";

// ❌ WRONG (fails on PostgreSQL)
$sql = "SELECT * FROM courses WHERE is_published = 1";
$sql = "UPDATE enrollments SET is_completed = 0 WHERE id = :id";
```

> **⚠️ Important:** Do NOT bind PHP booleans as parameters. PDO does not correctly convert them for PostgreSQL. Use inline SQL literals instead.

```php
// ❌ WRONG - PDO binding fails for booleans
$db->query("UPDATE users SET active = :active", [':active' => true]);

// ✅ CORRECT - Use inline literal
$db->query("UPDATE users SET active = true WHERE id = :id", [':id' => $userId]);
```

#### Aggregation Functions

PostgreSQL does not support `GROUP_CONCAT`. Use separate queries instead:

```php
// ❌ WRONG (SQLite/MySQL only)
$sql = "SELECT u.*, GROUP_CONCAT(r.slug) as roles FROM users u 
        LEFT JOIN role_user ru ON u.id = ru.user_id 
        LEFT JOIN roles r ON ru.role_id = r.id 
        GROUP BY u.id";

// ✅ CORRECT (cross-database compatible)
$users = $db->fetchAll("SELECT * FROM users");
foreach ($users as &$user) {
    $roles = $db->fetchAll(
        "SELECT r.slug FROM roles r 
         INNER JOIN role_user ru ON r.id = ru.role_id 
         WHERE ru.user_id = :uid",
        [':uid' => $user['id']]
    );
    $user['roles'] = implode(',', array_column($roles, 'slug'));
}
```

#### INSERT OR IGNORE

Use the database-agnostic helper method instead of raw SQL:

```php
// ❌ WRONG (SQLite only)
$db->query("INSERT OR IGNORE INTO roles (name, slug) VALUES (:n, :s)", [...]);

// ✅ CORRECT (cross-database compatible)
$db->insertOrIgnore('roles', ['name' => $name, 'slug' => $slug]);
```

#### Date Functions

Avoid database-specific date functions:

```php
// ❌ WRONG (SQLite only)
$sql = "SELECT * FROM users WHERE created_at > date('now', '-7 days')";

// ✅ CORRECT (calculate in PHP)
$sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
$sql = "SELECT * FROM users WHERE created_at > :date";
$db->fetchAll($sql, [':date' => $sevenDaysAgo]);
```

### Service Layer Classes

When implementing business logic, use or create services:

| Service | Purpose | Methods |
| ------- | ------- | ------- |
| `UserService` | User operations | `register()`, `assignRole()`, `getUserWithRoles()` |
| `RoleService` | Role management | `assignRole()`, `getUserRoles()` (cached) |
| `NotificationService` | Notifications | `create()`, `markRead()` |
| `CourseProgressService` | Progress tracking | `updateProgress()`, `isLessonLocked()` |
| `RateLimiter` | Brute-force protection | `attempt()`, `tooManyAttempts()`, `clear()` |
| `SecurityLogger` | Security audit trail | `logAuthEvent()`, `logAccessDenied()`, `logCsrfFailure()` |

---

## 4. Security Layers (MANDATORY)

> ⚠️ **CRITICAL**: Every security measure below is **NON-NEGOTIABLE**.

### 4.1 XSS Prevention

**ALWAYS escape user-generated output in views:**

```php
<!-- ✅ CORRECT -->
<h1><?= e($user['name']) ?></h1>
<input value="<?= e($value) ?>">
<p><?= htmlspecialchars($text) ?></p>

<!-- ❌ NEVER DO THIS -->
<h1><?= $user['name'] ?></h1>
<?= $_POST['data'] ?>
```

**When to escape:**

- User names, emails, bios
- Course/lesson titles and descriptions
- Any database content that originated from user input
- Form values being re-displayed

**When NOT to escape:**

- Numeric IDs (already safe)
- System-generated HTML (intentional)
- Already-escaped content (don't double-escape)

### 4.2 CSRF Protection

**All POST requests are automatically protected.** The router verifies CSRF tokens.

**In forms, always include:**

```php
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <!-- form fields -->
</form>
```

**For AJAX requests:**

```javascript
// Token is auto-injected via fetch wrapper
fetch('/api/endpoint', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
});
```

### 4.3 File Upload Security

**NEVER save uploaded files without validation:**

```php
// ✅ CORRECT - Required pattern
$validation = validateFileUpload(
    $_FILES['upload'],
    ['jpg', 'png', 'gif', 'webp'],           // Allowed extensions
    ['image/jpeg', 'image/png', 'image/gif', 'image/webp']  // MIME types
);

if (!$validation['valid']) {
    // Handle error
    return error($validation['error']);
}

// Only then save the file
```

**Allowed file types by category:**

| Category | Extensions | MIME Types |
|----------|------------|------------|
| Images | jpg, jpeg, png, gif, webp | image/jpeg, image/png, image/gif, image/webp |
| Videos | mp4, webm, mov | video/mp4, video/webm, video/quicktime |
| Documents | pdf, doc, docx, xls, xlsx | application/pdf, application/msword, etc. |

### 4.4 Input Validation

**ALWAYS validate required fields before database operations:**

```php
// ✅ CORRECT - Validate before insert/update
$errors = [];

// Required field check
if (empty(trim($title))) {
    $errors[] = 'Title is required.';
} elseif (strlen($title) < 3 || strlen($title) > 255) {
    $errors[] = 'Title must be 3-255 characters.';
}

// Type validation with whitelist
$validTypes = ['single_choice', 'multi_choice', 'true_false'];
if (!in_array($type, $validTypes, true)) {
    $errors[] = 'Invalid type.';
}

// Numeric validation
if ($points < 1) {
    $errors[] = 'Points must be at least 1.';
}

// Return errors before DB operation
if (!empty($errors)) {
    $_SESSION['flash_error'] = implode(' ', $errors);
    header('Location: /back');
    exit;
}

// ❌ NEVER let invalid data reach the database
$db->query("INSERT INTO table ...", $data);  // Crash!
```

**Validation patterns by data type:**

| Data Type | Validation |
|-----------|------------|
| Required text | `empty(trim($value))` |
| Min/Max length | `strlen($value) < 3` |
| Enum/Type | `in_array($value, $whitelist, true)` |
| Positive number | `$value < 1` |
| Foreign key | `$id < 1` (before DB lookup) |

### 4.5 SQL Injection Prevention

**ALWAYS use parameterized queries:**

```php
// ✅ CORRECT
$db->fetchOne("SELECT * FROM users WHERE id = :id", [':id' => $userId]);

// ❌ NEVER DO THIS
$db->query("SELECT * FROM users WHERE id = $userId");
```

### 4.6 Mass Assignment Protection

**Model updates must use whitelists:**

```php
// In model, define allowed columns:
private const ALLOWED_UPDATE_COLUMNS = ['name', 'email', 'bio', 'profile_picture'];

// Then filter input:
$filtered = array_intersect_key($input, array_flip(self::ALLOWED_UPDATE_COLUMNS));
```

### 4.7 Session Security

**After login, always regenerate session:**

```php
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
```

### 4.8 Redirect Validation

**NEVER use raw user input for redirects:**

```php
// ✅ CORRECT
$redirect = sanitizeRedirect($_POST['return'] ?? '', '/dashboard');
header("Location: $redirect");

// ❌ NEVER DO THIS
header("Location: " . $_POST['return']);
```

### 4.9 Authorization Checks

**Use the centralized Middleware class:**

```php
use App\Core\Middleware;

// In controller constructor or method:
Middleware::auth();           // Require logged in
Middleware::guest();          // Require NOT logged in
Middleware::admin();          // Require admin role
Middleware::instructor();     // Require instructor or higher
Middleware::role('student');  // Require specific role
Middleware::owner($id);       // Require resource ownership
```

**Manual checks (legacy):**

```php
// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Check specific roles
if (!in_array('admin', $_SESSION['user_roles'])) {
    http_response_code(403);
    exit('Forbidden');
}
```

### 4.10 Rate Limiting

**Brute-force protection is automatic on login/register. For custom endpoints:**

```php
use App\Services\RateLimiter;

$limiter = new RateLimiter();
$key = RateLimiter::key('action_name');  // Uses IP by default

// Check if blocked
if ($limiter->tooManyAttempts($key, 5, 15)) {  // 5 attempts / 15 min
    http_response_code(429);
    exit('Too many requests');
}

// Record attempt
$limiter->attempt($key, 5, 15);

// Clear on success
$limiter->clear($key);
```

### 4.11 Security Logging

**All security events are automatically logged. For custom events:**

```php
use App\Services\SecurityLogger;

$logger = new SecurityLogger();

// Log auth events
$logger->logAuthEvent('custom_action', $userId, ['context' => 'data']);

// Log access denials
$logger->logAccessDenied('/admin/resource', $userId);

// Log suspicious activity
$logger->logSuspiciousActivity('Description', ['ip' => $ip]);
```

**Logs stored in:** `storage/logs/security-YYYY-MM-DD.log`

### 4.12 CSP Headers

**Security headers are automatically sent via `SecurityHeaders::send()` in `index.php`.**

Current policy:

- Scripts: Self + CDNs (Tailwind, Alpine, FontAwesome)
- Styles: Self + Google Fonts + inline (for Tailwind)
- Frames: Same origin only (prevents clickjacking)

---

## 5. UI Design Rules

Reference: `docs/design_context.md`

### Color Palette (MANDATORY)

| Purpose | Color | Class |
|---------|-------|-------|
| Background | `#0a0a0a` | `bg-background` or direct |
| Surface | `#18181b` | `bg-zinc-900` |
| Card | `#27272a` | `bg-zinc-800` |
| Primary Accent | Indigo → Violet | `bg-gradient-to-br from-indigo-500 to-violet-600` |
| Text | `#e2e8f0` | `text-zinc-200` |
| Muted Text | | `text-zinc-400` or `text-zinc-500` |

### Component Standards

**Cards:**

```html
<div class="bg-zinc-900 rounded-2xl p-6 border border-zinc-800 hover:border-zinc-700 transition">
```

**Primary Buttons:**

```html
<button class="bg-gradient-to-r from-indigo-500 to-violet-600 text-white font-bold py-2 px-6 rounded-xl hover:opacity-90 transition">
```

**Secondary Buttons:**

```html
<button class="bg-zinc-900 border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-600 py-2 px-4 rounded-lg transition">
```

**Form Inputs:**

```html
<input class="w-full bg-zinc-900 border border-zinc-800 text-zinc-300 rounded-xl py-2 px-4 focus:outline-none focus:border-indigo-500 transition">
```

### Typography

- **Font**: Rubik (Latin), RB (Arabic/RTL)
- **Headings**: `font-bold` (700) or `font-extrabold` (800)
- **Body**: Regular (400)
- **Icons**: FontAwesome 6 (`fa-solid`, `fa-regular`)

### Layout Rules

1. **Always** use `class="dark"` on `<html>` tag
2. **Never** use pure white backgrounds
3. Use `rounded-xl` or `rounded-2xl` for containers
4. Use subtle borders (`border-zinc-800`) instead of heavy shadows
5. Mobile: Stack vertically, use off-canvas sidebar

---

## 6. Development Requirements

### Before Creating Any Page/Function

1. **Identify the route** - What URL will trigger this?
2. **Create the controller** - Extend base Controller class
3. **Define authorization** - Who can access this?
4. **Create the service** - Encapsulate business logic (if complex)
5. **Create the view** - Follow UI design rules
6. **Add security** - Apply all security layers

### Controller Template

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Database;

class YourController extends Controller
{
    private Database $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        // 1. Authorization check
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        // 2. Fetch data using parameterized queries
        $data = $this->db->fetchAll(
            "SELECT * FROM table WHERE user_id = :uid",
            [':uid' => $_SESSION['user_id']]
        );

        // 3. Render view with escaped data
        $this->view('your/view', [
            'data' => $data,
            'title' => 'Page Title'
        ]);
    }

    public function store(): void
    {
        // CSRF is auto-verified by router for POST

        // 1. Authorization
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            exit;
        }

        // 2. Validate input
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $_SESSION['flash_error'] = 'Name is required';
            header('Location: /your-page');
            exit;
        }

        // 3. File upload (if applicable)
        if (isset($_FILES['upload'])) {
            $validation = validateFileUpload($_FILES['upload'], ['jpg', 'png'], ['image/jpeg', 'image/png']);
            if (!$validation['valid']) {
                $_SESSION['flash_error'] = $validation['error'];
                header('Location: /your-page');
                exit;
            }
        }

        // 4. Database operation with parameterized query
        $this->db->query(
            "INSERT INTO table (name, user_id) VALUES (:name, :uid)",
            [':name' => $name, ':uid' => $_SESSION['user_id']]
        );

        // 5. Redirect safely
        $redirect = sanitizeRedirect($_POST['return'] ?? '', '/your-page');
        header("Location: $redirect");
    }
}
```

### View Template

```php
<?php ob_start(); ?>

<div class="max-w-7xl mx-auto space-y-8">
    <h1 class="text-3xl font-bold text-white"><?= e($title) ?></h1>
    
    <?php foreach ($items as $item): ?>
        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6">
            <h2 class="text-white font-bold"><?= e($item['name']) ?></h2>
            <p class="text-zinc-400"><?= e($item['description']) ?></p>
        </div>
    <?php endforeach; ?>
    
    <form method="POST" action="/your-action">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="text" name="name" value="<?= e($existingValue ?? '') ?>"
               class="w-full bg-zinc-900 border border-zinc-800 text-zinc-300 rounded-xl py-2 px-4 focus:outline-none focus:border-indigo-500">
        <button type="submit" class="bg-gradient-to-r from-indigo-500 to-violet-600 text-white font-bold py-2 px-6 rounded-xl">
            Submit
        </button>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>
```

### Git Workflow (Per GEMINI.md)

1. **Gemini** handles all commits and pushes
2. Before committing, ensure all tests pass
3. Update `docs/ai/change-log.md` with changes
4. Create GitHub issue for any bugs found
5. Close GitHub issues after resolution with comment

---

## 7. Quick Reference Checklists

### New Page Checklist

- [ ] Route defined in `routes/web.php`
- [ ] Controller created with proper authorization
- [ ] Service created (if complex logic)
- [ ] View follows design_context.md rules
- [ ] All user output uses `e()` or `htmlspecialchars()`
- [ ] Forms include CSRF token
- [ ] File uploads validated with `validateFileUpload()`
- [ ] Database queries use parameters
- [ ] Redirects use `sanitizeRedirect()`

### Security Audit Checklist

- [ ] No raw `$_POST`/`$_GET` output in HTML
- [ ] No unparameterized SQL queries
- [ ] No direct file saves without validation
- [ ] No redirects with raw user input
- [ ] Session regenerated after login
- [ ] Authorization checked for all protected routes
- [ ] Mass assignment protected with whitelists

### Code Review Checklist

- [ ] Follows MVC pattern
- [ ] Business logic in services, not controllers
- [ ] UI matches design_context.md
- [ ] Security layers properly applied
- [ ] Error handling implemented
- [ ] Flash messages for user feedback
- [ ] Mobile responsive design

---

## Related Documentation

- [Design Context](./design_context.md) - UI design system
- [XSS Protection Guidelines](./security/xss-protection-guidelines.md) - Detailed XSS guide
- [Change Log](./ai/change-log.md) - Development history
- [ADR Directory](./adr/) - Architectural Decision Records

---

**Last Updated:** 2026-01-19  
**Maintained by:** Gemini (Security & UI Team)
