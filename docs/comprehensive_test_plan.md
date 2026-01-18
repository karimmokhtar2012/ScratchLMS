# Comprehensive System Verification Plan (Post-Plan A Cutover)

This plan is designed to verify the integrity of the LMS after the migration to the "Unified Content Items" model. It specifically targets potential conflicts between the new database schema (`content_items`, `content_progress`) and the application logic/views.

## Phase 1: Authentication & Onboarding
**Goal:** Ensure user context is correctly established for role-based access.
- [ ] **Registration:** Create a new Student account. Verify `users` table insertion and default role.
- [ ] **Onboarding:** Complete the onboarding wizard. Verify redirection to Dashboard.
- [ ] **Login:** Log in as existing Instructor. Verify redirection to Instructor Dashboard.
- [ ] **Profile:** Update profile name/password. Verify persistence.

## Phase 2: Instructor Workflow (Write Path Verification)
**Goal:** **CRITICAL.** Verify that all content creation writes to `content_items` and legacy tables synchronously where required.

### 2.1 Course Creation & Settings
- [ ] **Create Course:** Create a new course "Test Course 101". Verify `courses` table insert.
- [ ] **Edit Settings:** Update title, price, and **Content Flow** (e.g., set to "Linear"). Verify updates.
- [ ] **Drip Rules:** Configure a "Drip by Time" rule for a lesson. Verify persistence in `lesson_drip_rules`.

### 2.2 Course Builder (The "Split-Brain" Risk Area)
- [ ] **Add Module:** Create "Module A". Verify `modules` table insert.
- [ ] **Add Lesson (Video):** Add a video lesson.
    *   **Verify:** Row exists in `lessons`.
    *   **Verify:** Row exists in `content_items` (linked to lesson, correct module).
    *   **UI:** Refresh page. Does the lesson appear in the builder list?
- [ ] **Add Lesson (Article):** Add an article lesson. Verify structure.
- [ ] **Reorder Lessons:** Drag Lesson 2 above Lesson 1.
    *   **Verify:** `content_items.position` is updated.
- [ ] **Delete Lesson:** Delete a lesson.
    *   **Verify:** Removed from `content_items` AND `lessons`.

### 2.3 Quiz Builder
- [ ] **Create Quiz:** Click "Quiz" on a lesson (or add independent quiz if UI supports).
    *   **Verify:** Row in `quizzes`.
    *   **Verify:** Row in `content_items` (item_type='quiz').
    *   **Verify:** `content_items.display_group_id` links it to the parent lesson (if applicable).
- [ ] **Add Questions:** Add Single Choice, Essay, and Matching questions. Verify `questions` table.

## Phase 3: Student Experience (Read Path Verification)
**Goal:** **CRITICAL.** Verify the Player reads the new structure and tracks progress correctly.

### 3.1 Course Discovery & Enrollment
- [ ] **Catalog:** View "Test Course 101".
- [ ] **Enrollment:** Enroll/Purchase. Verify `enrollments` table.

### 3.2 Course Player & Navigation
- [ ] **Load Player:** Open the course.
    *   **Verify:** Sidebar renders the correct order (matching `content_items`).
    *   **Verify:** "Lesson + Quiz" grouping renders correctly (visually connected).
- [ ] **Consumption:**
    *   Watch Video -> Click "Next".
    *   **Verify:** `content_progress` status = 'completed'.
    *   **Verify:** `completed_lessons` (legacy) entry exists.
    *   **Verify:** Dashboard progress bar updates.
- [ ] **Locking (Linear Mode):**
    *   Try to access Lesson 2 before Lesson 1. Verify "Locked" state/message.
    *   Complete Lesson 1. Verify Lesson 2 unlocks.

### 3.3 Quiz Runner
- [ ] **Start Quiz:** Launch the quiz created in Phase 2.
- [ ] **Submit:** Answer questions and submit.
    *   **Verify:** `quiz_attempts` entry created.
    *   **Verify:** `content_progress` for the Quiz item is updated.
    *   **Verify:** If passed, the "Next" item unlocks.

## Phase 4: Grading & Certification
**Goal:** Verify the feedback loop between Instructor and Student.

### 4.1 Manual Grading
- [ ] **Submission:** Submit an Essay answer as Student.
- [ ] **Instructor Queue:** Log in as Instructor -> Go to "Grading".
- [ ] **Grade:** Score the essay.
    *   **Verify:** `grading_queue` updates.
    *   **Verify:** Student's Quiz Score recalculates.
    *   **Verify:** If passed, Student's `content_progress` marks the quiz complete.

### 4.2 Certification
- [ ] **Completion:** Complete all items in the course.
- [ ] **Certificate:** Verify "Get Certificate" button appears (if enabled).
- [ ] **Manual Issue:** (If auto-issue off) Verify Instructor can issue via "Certificate Approvals".

## Phase 5: Admin & System Integrity
**Goal:** Ensure cleanup tools work on the new data model.

- [ ] **User Management:** Change a user's role.
- [ ] **Reset Progress:** Use Admin "Reset Progress" for a user.
    *   **Verify:** `content_progress`, `completed_lessons`, `enrollments` progress are all cleared.
    *   **Verify:** Student is back to 0% on Dashboard.

## Execution Strategy
1.  **Dry Run:** I will perform these actions on the "Trading Mastery" seed course first to verify the baseline.
2.  **Live Test:** I will create a *fresh* course through the UI to validate the Write Paths (Phase 2).
