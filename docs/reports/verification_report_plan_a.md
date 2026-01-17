# Verification Report: Issue #38 (Unified Content Items Model)

**Verifier:** Gemini
**Date:** 2026-01-17
**Status:** Verification Complete

## 1. Summary of Changes

Codex (acting as System) has implemented **Plan A: Unified Content Items Model** to resolve the architectural coupling between quizzes and lessons. The implementation involves a full stack update:

1.  **Database:**
    *   New Tables: `content_items`, `content_progress`, `content_item_rules`, `content_prerequisites`.
    *   Schema allows polymorphic content (items can be 'lesson', 'quiz', etc.).
    *   Added `display_group_id` to `content_items` to support the visual grouping of "Lesson + Quiz".

2.  **Backend Services:**
    *   `CourseProgressService`: Refactored to calculate progress based on `content_items` count vs `content_progress`. Added `isItemLocked()` for item-level gating.
    *   `LearnController`: Completely refactored `show()` and added `showItem()`. It now builds the playlist from `content_items` if available, or falls back to legacy `lessons`.
    *   `QuizRunnerController`: Updated `submit()` to dual-write progress to both `completed_lessons` (legacy) and `content_progress` (new).

3.  **Frontend (Course Player):**
    *   **Sidebar:** Updated logic to loop through `content_items`. It successfully groups items sharing a `display_group_id` into a single UI card while keeping them as distinct clickable nodes.
    *   **Navigation:** Prev/Next logic now follows `content_items` position order.
    *   **Tracking:** `player.js` tracks `item_id` in addition to `lesson_id`.

4.  **Seeding:**
    *   `seed_test_trading_course.php`: Updated to generate 3 scenarios:
        1.  Standard Grouping (Lesson + Quiz).
        2.  Ungrouped (Lesson -> Standalone Quiz).
        3.  Section Test (Quiz in its own module).

## 2. Verification Findings

### ✅ Logic Integrity
*   **Dual-Write:** `QuizRunnerController::submit` correctly writes to both tables. This ensures legacy reporting tools won't break immediately.
*   **Fallback:** `LearnController` checks for table existence (`hasTable`) before querying, providing safety during migration.
*   **Locking:** `CourseProgressService` has a clear hierarchy: Item Locking -> Legacy Lesson Locking.

### ✅ UX Preservation
*   The `display_group_id` implementation in `player.php` is robust. It groups items visually but renders individual links. This meets the requirement of "preserving the mental model."

### ⚠️ Minor Concerns (Non-Critical)
1.  **Complexity in `LearnController`:** The controller is becoming large (monolithic). The logic for building the playlist (`$flatItems` vs `$playlist` with nesting) is complex and might be hard to maintain. *Recommendation: Extract PlaylistBuilderService in Phase 2.*
2.  **Raw SQL in Controller:** There are still raw SQL queries in `LearnController::track` for fetching course IDs. Ideally, this should be in a Repository or Model.
3.  **ON CONFLICT Clause:** The `track` method uses SQLite specific `ON CONFLICT` syntax or `INSERT OR REPLACE`. If the system moves to MySQL, ensure `ON DUPLICATE KEY UPDATE` is supported or abstracted.

## 3. List of "Broken" or Incomplete Elements

Since this was a "Wipe + Reseed" dev-only rollout, there are no "broken" legacy courses (because they were wiped). However, looking at the code:

1.  **Admin / Instructor Course Builder:**
    *   **Status:** **BROKEN / PENDING.**
    *   **Details:** The implementation focused on the *Consumer* (Student) side. The *Instructor* side (`CourseBuilder`, `EditCourse`) likely still saves to `lessons` and `quizzes` tables but **does not** yet write to `content_items`.
    *   **Impact:** If an instructor adds a new lesson in the UI, it will show up in the legacy tables but *will not* appear in the `content_items` playlist, effectively making it invisible to the new player logic.
    *   **Fix Required:** The `InstructorController` needs a refactor to write to `content_items` on save.

2.  **Rule Editor:**
    *   **Status:** **NOT IMPLEMENTED.**
    *   **Details:** The `content_item_rules` table exists, but there is no UI to create/edit these rules.
    *   **Impact:** Locking is currently limited to linear flow or legacy drip.

## 4. Final Verdict

**Plan A Implementation is VALID for the Consumer/Student View.**
The core architectural goal (decoupling) is achieved. The player works.

**Action Required:** The Instructor/Admin side is now out of sync with the data model. The next immediate task must be updating the **Course Builder** to write to the `content_items` table.
