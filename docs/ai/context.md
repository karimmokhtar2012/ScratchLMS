# AI Context

> **Purpose:** Quick reference for AI agents on project architecture and key flows  
> **Last Updated:** 2026-01-19

---

## 1. Project Structure & Conventions

### Routing

- `routes/web.php` maps URLs to Controller methods
- `App\Core\Router` enforces CSRF verification on all `POST` requests
- Supports `{id}` placeholders converted to regex `([^/]+)`

### Authorization

- Centralized in `App\Core\Authorization`
- Use `requireEnrollment()` or `requireInstructor()`
- Middleware classes at `App\Http\Middleware\`

### Security

| Layer | Implementation |
|-------|----------------|
| CSRF | `App\Core\Csrf` - auto-verified by Router |
| XSS | `e()` helper function in views |
| Rate Limiting | `App\Services\RateLimiter` |
| File Uploads | `validateFileUpload()` helper |
| Redirects | `sanitizeRedirect()` helper |

---

## 2. Database Architecture

### Multi-DB Support

- Configured via `config/database.php` and `.env`
- `App\Core\Database` singleton with adapter pattern
- Adapters at `App\Core\Database\Adapters\`
- Grammar classes at `App\Core\Database\Grammar\`

### PostgreSQL Compatibility Rules

| Pattern | SQLite/MySQL | PostgreSQL | Solution |
|---------|--------------|------------|----------|
| Boolean | `= 1` / `= 0` | `= true` / `= false` | Use SQL boolean literals |
| PDO Binding | PHP booleans | ❌ Fails | Use inline SQL literals |
| GROUP_CONCAT | ✅ | ❌ | Separate queries |
| INSERT OR IGNORE | ✅ | ❌ | Use `$db->insertOrIgnore()` |

### Migrations

- Location: `database/migrations/`
- Uses Blueprint schema builder
- Run with: `php migrate.php`

---

## 3. Key Flows

### Course Building

- Handled in `InstructorController`
- "Batch Save" pattern: reordering in DOM, persisted on click
- Content items tracked via `content_items` table

### Lesson Library

- Lessons can exist independently (`module_id` nullable)
- Instructors import/copy lessons into courses

### Enrollment

| Type | Flow |
|------|------|
| Free | Direct via `CourseController::enroll` |
| Paid | Cart → `CheckoutController::process` |

### Quiz System

- Quizzes linked to lessons
- Question types: `single_choice`, `multi_choice`, `true_false`, `short_text`, `fill_blank`, `matching`, `essay`, `video_submission`
- Grading policies: `auto`, `manual`, `ai`, `both`, `strict`

### Content Flow

- Modes: `free`, `linear`, `drip_time`, `drip_progress`, `drip_combined`
- Drip rules stored in `lesson_drip_rules` table
- Enforced via `CourseProgressService::isLessonLocked()`

### Guided Tour

- One-time tutorial for new users
- `seen_tutorial` column in `users` table
- Dismissed via `DashboardController::dismissTutorial()`

---

## 4. UI Components

### Framework

- Tailwind CSS for styling
- Alpine.js for interactions
- FontAwesome 6 for icons
- Custom PHP views (Blade-like syntax)

### Color Palette

| Purpose | Class |
|---------|-------|
| Background | `#0a0a0a` |
| Surface | `bg-zinc-900` |
| Card | `bg-zinc-800` |
| Primary | `from-indigo-500 to-violet-600` |
| Text | `text-zinc-200` |

---

## 5. Controllers Reference

| Controller | Purpose | Auth |
|------------|---------|------|
| `AuthController` | Login, logout, registration | No |
| `DashboardController` | User dashboard | Yes |
| `InstructorController` | Course building | Instructor+ |
| `CourseController` | Public course browsing | No |
| `LearnController` | Course player | Yes |
| `QuizController` | Quiz management | Instructor+ |
| `QuizRunnerController` | Quiz taking | Yes |
| `AdminController` | Platform admin | Admin |

---

## 6. Recent Changes (Session Summary)

### 2026-01-19

- **PostgreSQL Compatibility Audit**
  - Fixed boolean comparisons in SQL queries
  - Replaced `GROUP_CONCAT` with separate queries
  - Added all question types to CHECK constraints
  - Added `status` column to `quizzes` table
- **Input Validation**
  - Added required field validation for courses
  - Added type/field validation for quiz questions
- **Course Settings Fix**
  - Fixed `published_at` field causing silent form submission failure
  - Made field conditionally required only when status is "upcoming"

---

*Reference `docs/developer-guide.md` for complete development standards.*
