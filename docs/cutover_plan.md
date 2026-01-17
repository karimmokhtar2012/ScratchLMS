# Refactor Plan: "Cutover to Content Items" (No Dual-Write)

**Goal:** Establish `content_items` as the **Single Source of Truth** for course structure, ordering, and hierarchy. The `lessons` and `quizzes` tables will become simple "content payload" tables, effectively deprecated for structural logic.

**Target State:**
*   **Structure:** `content_items` table owns `course_id`, `module_id`, `position`, `display_group_id`.
*   **Content:** `lessons` / `quizzes` tables own `title`, `content`, `settings`.
*   **Controllers:** All Instructor actions (add/move/delete/reorder) operate primarily on `content_items`.

---

## Phase 1: Model & Service Layer (The Foundation)

1.  **Enhance `ContentItem` Model:**
    *   Create `App\Domain\Courses\ContentItem` class.
    *   Methods: `create($data)`, `update($id, $data)`, `delete($id)`, `findByModule($moduleId)`.
    *   **Critical:** Add `moveItem($itemId, $targetModuleId, $position)` logic that handles reordering automatically.

2.  **Refactor `Course` Model:**
    *   Update `modules()` or create `structure()` method to fetch the full hierarchy:
        *   `Course` -> `Modules` -> `ContentItems` (joined with `lessons`/`quizzes` for titles).
    *   Remove legacy `lessons()` calls that fetch via `module_id` directly from the `lessons` table.

3.  **Deprecation Prep:**
    *   Mark `lessons.module_id` and `lessons.sort_order` as **DEPRECATED** in code comments.
    *   Ensure all new queries join via `content_items`.

---

## Phase 2: Instructor Controller Refactor (The Heavy Lift)

We will systematically rewrite `InstructorController` methods to respect the new model.

### 2.1 Reading Data (The Course Builder)
*   **File:** `app/Http/Controllers/InstructorController.php`
*   **Method:** `edit($id)` (The Course Builder)
*   **Change:**
    *   Instead of fetching `modules` -> `lessons`, fetch `modules` -> `contentItems`.
    *   Map the `contentItems` result into the view structure expected by the frontend (or update the frontend loop).
    *   **Win:** This immediately fixes the "Invisible Content" bug because the Builder will see exactly what the Player sees.

### 2.2 Creating Content
*   **Methods:** `addLesson`, `createLibraryLesson` (and `QuizController::addQuestion` indirectly).
*   **Logic Change:**
    1.  Insert into `lessons` (Payload).
    2.  **IMMEDIATELY** Insert into `content_items` (Structure) linking to that lesson.
    3.  Calculate `position` = MAX(position) + 1 in that module.
    4.  Default `display_group_id` = NULL (Standalone) or Parent Lesson (if creating a quiz attached to lesson).

### 2.3 Updating & Moving
*   **Methods:** `updateLesson`, `addToCourse`, `importLesson`.
*   **Logic Change:**
    *   **Update Content:** Update `lessons` table (Title, Video URL).
    *   **Move Module:** Update `content_items.module_id`.
    *   **Reorder:** Update `content_items.position`.
    *   *Note:* Ignore `lessons.module_id` and `lessons.sort_order`. Leave them null or stale.

### 2.4 Deleting
*   **Method:** `deleteLesson`
*   **Logic Change:**
    1.  Find `content_item` by `item_id` + `item_type`.
    2.  Delete `content_item`.
    3.  (Optional but Good) Delete `lesson` row if it's not used in other courses (Library feature complexity). For now, strict delete is fine.

### 2.5 Reordering (Drag & Drop)
*   **Methods:** `reorderLessons`
*   **Logic Change:**
    *   Accept list of `item_id`s (from `content_items`).
    *   Batch update `content_items.position`.
    *   *Crucial:* Support reordering across modules if the UI supports it.

---

## Phase 3: Quiz & Grading Integration

### 3.1 Quiz Creation (`QuizController`)
*   **Method:** `getOrCreateQuiz` / `build`
*   **Logic:**
    *   When a "Quiz" is created, ensure a `content_items` row exists.
    *   **Logic Fix:** If the quiz is "inside" a lesson (Legacy UI), set `content_items.display_group_id` to the Lesson's `content_item_id`. This preserves the "Lesson + Quiz" visual grouping.

### 3.2 Grading (`InstructorController::gradeEssay`)
*   **Logic:**
    *   Upon grading completion, write to `content_progress` instead of (or in addition to) `completed_lessons`.
    *   Recalculate Course Progress using `content_items` count.

---

## Phase 4: Admin Tools & Resets

### 4.1 Progress Reset (`AdminController`)
*   **Method:** `resetProgress`
*   **Logic:**
    *   `DELETE FROM content_progress WHERE user_id = ? ...`
    *   Ensure `enrollments` table is reset (already done, but verify).

---

## Phase 5: Verification & Cleanup

1.  **Seed Update:**
    *   Update `seed_test_trading_course.php` to populate *only* via the new Logic (or manually insert correctly) to ensure the testbed is valid.
2.  **Frontend Check:**
    *   Verify the Course Builder (Drag and Drop) works with `content_items` IDs.
    *   Verify the Player reflects changes immediately.

---

## Execution Order

1.  **Models:** Create `ContentItem` class + Helper methods.
2.  **Read:** Update `InstructorController::edit` to read from `content_items`. (Builder will look empty initially until we re-seed).
3.  **Write (Create/Delete):** Update `addLesson`, `deleteLesson`.
4.  **Write (Update/Move):** Update `reorderLessons`, `updateLesson`.
5.  **Quizzes:** Update `QuizController`.
6.  **Progress:** Update `gradeEssay` / `resetProgress`.
7.  **Wipe & Reseed:** Run the seeder to fill `content_items` correctly.
8.  **Verify.**
