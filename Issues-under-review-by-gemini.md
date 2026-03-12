# Issues Under Review by Gemini

This document tracks logical, design, and technical issues identified during codebase audits.

## Critical Security Vulnerabilities

### [SEC-001] SQL Injection Risk in `User::update`
- **File:** `app/Domain/Users/User.php`
- **Description:** The `update` method dynamically constructs SQL keys from input arrays without validation or whitelisting.
- **Status:** **Resolved**
- **Priority:** High
- **Notes:** Implemented strict `ALLOWED_UPDATE_COLUMNS` whitelist.

### [SEC-002] Cross-Site Scripting (XSS) Gaps
- **File:** `app/Core/Controller.php` & All Views
- **Description:** No automatic output escaping in the view rendering engine. Views rely on manual escaping which is missing in several locations.
- **Status:** **Partially Solved**
- **Priority:** High
- **Notes:** Added `e()` helper. Manual migration of all views to use `e()` instead of raw echo is still in progress.

### [SEC-003] Inconsistent CSRF Enforcement
- **File:** `app/Http/Controllers/`
- **Description:** CSRF tokens are generated but not consistently verified in POST controllers (e.g., AuthController).
- **Status:** **Resolved**
- **Priority:** Medium
- **Notes:** Global CSRF check enforced in `Router.php` for all POST requests.

### [SEC-004] Remote Code Execution (RCE) via File Upload
- **File:** `app/Http/Controllers/UploadController.php`, `app/Http/Controllers/InstructorController.php`
- **Description:** File uploads for thumbnails, videos, and attachments do not validate file extensions or MIME types. PHP files can be uploaded to public directories (`/public/uploads/`) and executed.
- **Status:** **Resolved**
- **Priority:** Critical
- **Notes:** Implemented `validateFileUpload` with strict whitelist and MIME type checking.

### [SEC-005] Session Fixation Vulnerability
- **File:** `app/Http/Controllers/AuthController.php`
- **Description:** `AuthController::login` does not regenerate the session ID (`session_regenerate_id(true)`) upon successful authentication, allowing attackers to hijack sessions.
- **Status:** **Resolved**
- **Priority:** High
- **Notes:** Added `session_regenerate_id(true)` to login flow.

### [SEC-006] Logic Bypass in Progress Tracking
- **File:** `app/Http/Controllers/LearnController.php`
- **Description:** The `track` method allows marking any enrolled lesson as 'completed' without verifying if the lesson is currently unlocked (e.g., in linear courses), allowing students to skip required content.
- **Status:** **Resolved**
- **Priority:** High
- **Notes:** Added `isLessonLocked` check before processing completion in `LearnController::track`.

### [SEC-007] Open Redirect Vulnerability
- **File:** `app/Http/Controllers/InstructorController.php`
- **Description:** The `handleRequest` and `issueCertificate` methods use `$_POST['return']` for redirection without validating that the target is a local, safe path.
- **Status:** **Resolved**
- **Priority:** Medium
- **Notes:** Implemented `sanitizeRedirect` to strictly enforce local-only redirects.

## Architectural & Design Issues

### [ARCH-001] MVC Violations in AuthController
- **File:** `app/Http/Controllers/AuthController.php`
- **Description:** Business logic (role assignment, notifications) and raw SQL queries are present in the controller.
- **Status:** **Partially Solved**
- **Priority:** Medium
- **Notes:** Some queries moved to Models, but controller still handles heavy business logic.

### [ARCH-002] Lack of Request/Response Abstractions
- **File:** Global
- **Description:** Extensive use of `$_POST`, `$_SESSION`, and `header()` instead of encapsulated objects.
- **Status:** **Pending**
- **Priority:** Low
- **Notes:** Requires major refactor. Not addressed in this sprint.

## Technical & Logical Errors

### [LOG-001] Silent Database Failures
- **File:** `app/Core/Database/` & Models
- **Description:** Database operations return `false` without logging errors or throwing exceptions, making debugging difficult.
- **Status:** **Resolved**
- **Priority:** Medium
- **Notes:** Added `logDatabaseError` helper and improved error handling in key models.

### [LOG-002] Missing Helper Functions
- **File:** `public/index.php`
- **Description:** `app/helpers.php` (containing `env()` and `config()`) was not being loaded, causing critical runtime errors on database connection.
- **Status:** **Resolved**
- **Priority:** Critical
- **Notes:** Added explicit `require_once` for `app/helpers.php` in `public/index.php`.

### [PERF-001] N+1 Query Patterns
- **File:** `app/Http/Controllers/AuthController.php`
- **Description:** Manual role fetching after user login; indicates a pattern that leads to N+1 issues in list views.
- **Status:** **Resolved**
- **Priority:** Low
- **Notes:** Optimized role fetching in Admin/User lists using `GROUP_CONCAT`.