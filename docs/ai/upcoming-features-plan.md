# Upcoming Features Plan

## Scope
- Quiz question types: MC, T/F, fill-blank, matching, essay, video submission.
- AI grading: AI pre-score placeholder for essays, manual review queue.
- Drip content: time-based, progress-based, and combined unlocks.

## Implementation Plan

### 1) Data Model
- Expand quiz question types with grading metadata.
- Add grading queue table for manual review and AI pre-score placeholders.
- Add per-lesson drip rules for time/progress unlocks.
- Add grading status to quiz attempts for pending/manual scoring.

### 2) Instructor Authoring
- Quiz builder: add new question types + grading policy controls for fill-blank/matching.
- Essay/video: capture grading policy and optional AI rubric.
- Lesson editor: provide drip rule inputs when course flow is drip-based.

### 3) Learner Experience
- Quiz runner: render new question types and save answers per type.
- Video submission: URL-only input with client + server validation.
- Quiz review: show text submissions, matching selections, and manual review notes.

### 4) Grading Pipeline
- Auto grade: MC, T/F, fill-blank, matching (strict/fuzzy/both).
- Manual queue: create grading items for essay/video submissions.
- AI placeholder: pre-score essays when policy is AI.
- Recalculate attempt score after manual grading and finalize pass/fail.

### 5) Drip Logic
- Time-based: unlock after N days from enrollment.
- Progress-based: unlock after a prerequisite lesson or progress percentage.
- Combined: require both time + progress rules.
- Enforce locks in the player and block non-unlocked lessons.

### 6) Instructor Workflows
- Pending approvals: expose grading queue with AI scores + final score input.
- Notifications/links: route to `/instructor/requests#grading`.

### 7) Testing Plan
- Quiz authoring: create each type, save, and re-open builder.
- Quiz runner: answer each type, submit, and review results.
- Fuzzy grading: verify minor typos still pass for fill-blank.
- Video submission: reject invalid URLs; accept valid http/https.
- Manual grading: ensure pending status, grade, and final score updates.
- Drip content: validate locks for time, progress, and combined rules.
