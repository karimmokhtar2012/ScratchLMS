# AI Context

## Project Overview
A custom Learning Management System (LMS) built with PHP (Core), SQLite (with MySQL portability), and Tailwind CSS. Focused on self-hosting and full control over content flow.

## 🛠 Tech Stack
- **Backend:** PHP 8.x (No framework, custom MVC-like structure)
- **Database:** SQLite (Current), MySQL (Portable via `Database` helper)
- **Frontend:** Tailwind CSS, Alpine.js, FontAwesome 6
- **Content:** Video (local/Youtube), Markdown articles, Quizzes

## 🏗 Key Conventions & Architecture

### Unified Content Model (Plan A)
- **ContentItems:** The source of truth for course structure. Table `content_items` manages ordering, module assignment, and visual grouping.
- **Polymorphism:** `content_items` links to `lessons` or `quizzes` via `item_type` and `item_id`.
- **Navigation:** Students navigate via `/learn/{course_id}/item/{content_item_id}`.
- **Visual Grouping:** Shared `display_group_id` in `content_items` pairs items (e.g., Lesson + Quiz) visually in the player sidebar.

### Security & Roles
- **Roles:** admin, instructor, student, master_instructor, viewer.
- **Authorization:** Handled in `app/Policies/Authorization.php`.
- **Onboarding:** Required for all users before dashboard access. Controlled by `users.is_onboarded`.

### Database Operations
- **Agnosticism:** Use `$db->upsert()` and `$db->insertIgnore()` instead of raw SQLite strings to maintain MySQL portability.
- **Wipe & Reseed:** Recommended dev workflow: `php setup_db.php && php database/seed_test_trading_course.php`.

## Key Flows
- **Course Building**: Handled in `InstructorController`. Uses a "Batch Save" pattern where reordering is stored in memory (DOM) and persisted on click.
- **Lesson Library**: Lessons can be created independently of courses (`module_id` is nullable). Instructors can import/copy library lessons into courses.
- **Enrollment**: 
    - Free courses: Direct enrollment via `CourseController::enroll`.
    - Paid courses: Redirects to cart; enrollment happens after `CheckoutController::process`.
- **Quizzes**: Treated as separate units from Video/Article lessons in the UI. Access is secured by `Authorization` and `RateLimiter`.
- **Quiz Grading**: Fill-blank and matching can use strict/fuzzy/both matching via `grading_policy`. Essay/video submissions enter the manual grading queue; essays can receive AI pre-scores. Short-text and fill-blank can use AI grading when `ai_rubric` is set and `GROQ_API_KEY` is configured.
- **Instructor Approvals**: Unified pending approvals view at `/instructor/requests` for quiz retake requests and manual certificate issuance.
- **Content Flow**: Supports Free, Linear, and drip (time/progress/combined) rules. Item-based locking/progress uses `content_items` + `content_item_rules` when present; legacy `lesson_drip_rules` still exist for older courses.

## Plan A (Content Items)
- **Schema**: `content_items`, `content_progress`, `content_item_rules`, `content_prerequisites`, `content_rule_definitions`.
- **Player**: `/learn/{course}/item/{id}` renders item-centric navigation with optional `display_group_id` grouping.
- **Dev Reset**: `database/reset_dev_plan_a.sh` wipes and recreates SQLite DB, migrates, seeds rules, seeds the Trading Mastery course, and runs the audit.
- **Audit**: `database/audit_content_items.php` validates item integrity and rule definitions.
- **Backfill**: `database/backfill_content_items.php` builds `content_items` from lessons/quizzes and maps `lesson_drip_rules` to item rules.
- **Rule Seeds**: `database/seed_content_rule_definitions.php` populates `content_rule_definitions` from the rule catalog.
