# Post-Cutover Verification Checklist

## 1. Instructor Course Builder (Write Operations)
- [ ] **Add Lesson:** Create a new lesson. Verify it appears in `content_items` with the correct `item_type`, `item_id`, and `position`.
- [ ] **Delete Lesson:** Delete a lesson. Verify it is removed from both `content_items` and `lessons`.
- [ ] **Reorder Lessons:** Drag and drop lessons. Verify `content_items.position` updates correctly.
- [ ] **Create Quiz:** Add a quiz to a lesson. Verify a `content_items` row is created for the quiz with `display_group_id` matching the parent lesson (or set appropriately).
- [ ] **Edit Content:** Rename a lesson. Verify the title updates in the `lessons` table (payload) and reflects in the Builder (joined query).

## 2. Student Player (Read Operations)
- [ ] **Visibility:** Verify the new lesson created above appears in the Student Player sidebar.
- [ ] **Ordering:** Verify the lesson order in the Player matches the Builder.
- [ ] **Grouping:** Verify "Lesson + Quiz" pairs are visually grouped (if applicable) based on `display_group_id`.
- [ ] **Navigation:** Verify "Next" / "Prev" buttons navigate correctly through the `content_items` sequence.

## 3. Progress & Grading
- [ ] **Track Completion:** Mark a lesson as complete as a student. Verify `content_progress` is inserted/updated.
- [ ] **Manual Grading:** Grade an essay as an instructor. Verify `content_progress` status becomes 'completed' and score is recorded.
- [ ] **Reset Progress:** Use Admin Reset tool. Verify `content_progress` rows are deleted.

## 4. Data Integrity (Database Inspection)
- [ ] **Orphans:** Run a query to check for `content_items` pointing to non-existent `lessons` or `quizzes`.
- [ ] **Dual-Entry:** Verify no duplicate `content_items` for the same payload (unless intended).
- [ ] **Foreign Keys:** Ensure `content_items` foreign keys (`course_id`, `module_id`) align with their payloads.
