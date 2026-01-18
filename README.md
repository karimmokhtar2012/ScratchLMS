# ScratchLMS

A high-performance, lightweight Learning Management System (LMS) built from scratch using a custom MVC architecture in PHP. ScratchLMS provides a comprehensive suite of tools for students, instructors, and administrators, focusing on speed, simplicity, and flexibility without the overhead of heavy frameworks.

## 🌟 Version 0.7 Release (January 17-18, 2026)

**Introducing Scracha, Multi-Database Support, and a massive UI/UX overhaul.**

### 💾 Enterprise-Ready Data Layer
**New in v0.7!** ScratchLMS now supports **MySQL**, **PostgreSQL**, and **SQLite** out of the box.
- **Database Agnostic**: Seamlessly switch between database drivers using `.env` configuration.
- **Advanced Migrations**: New CLI migration tool (`php migrate.php`) for managing schema changes across any supported database.
- **Portable Architecture**: Built on a custom adapter pattern that abstracts SQL differences (e.g., `INSERT OR IGNORE` vs. `ON DUPLICATE KEY`).

### 🤖 Meet "Scracha"
**Scracha** is our purpose-built AI engine designed to augment the educational experience. It doesn't just grade; it understands.
- **Smart Grading**: Automatically scores subjective answers like essays and short text with high accuracy.
- **Contextual Rationale**: Provides instructors with a detailed "Why?" for every AI-suggested score, which can be transparently shared with students.
- **Rubric Awareness**: Follows instructor-defined rubrics to ensure grading consistency across hundreds of students.
- **Adaptive Feedback**: Offers personalized study tips based on quiz performance (Coming Soon).

### 🎨 Instructor Studio Redesign
We've completely reimagined the course creation workflow to be faster, cleaner, and more powerful.
- **Visual Quiz Builder**: A new timeline-based interface for crafting assessments.
- **WYSIWYG Editor**: A visual question editor with live styling for Bold, Italic, Underline, and Images.
- **Collapsible Workspace**: A professional 3-column layout (Settings | Editor | Formats) that adapts to your focus.
- **Unified Approvals**: A single command center for managing Quiz Retakes, Manual Grading, and Certificate Issuance.

## 📦 Version 0.6 Release (January 15-16, 2026)

**Focus: Security Core, Instructor Tools, and Content Flow.**

- **🛡️ Security Architecture**: Implemented Rate Limiting, CSRF Protection on all forms/AJAX, and Centralized RBAC (Role-Based Access Control).
- **🏗️ Course Builder V2**: Added Drag-and-drop module ordering, in-place renaming, and a global Lesson Library for reusing content.
- **🌊 Content Flow Control**: New "Linear" (sequential) vs. "Free" (open navigation) progression modes for courses.
- **📜 Manual Certificates**: Instructors can now manually approve and issue certificates for completed enrollments.
- **🎯 Grading Controls**: Introduced "Strict" (exact match) vs. "Fuzzy" (typo-tolerant) matching policies for text answers.
- **📹 Video Validation**: Added server-side validation for video submission URLs.

## 🕰️ Version 0.5 - 0.1 (Early January 2026)

- **v0.5**: **Data Integrity & Tracking**: Fixed foreign key constraints for progress tracking and xAPI logs.
- **v0.4**: **Instructor Analytics**: Dashboard for viewing enrollment stats and quiz performance.
- **v0.3**: **Student Experience**: Implemented the Immersive Player, auto-resume, and note-taking.
- **v0.2**: **Core Commerce**: Added Shopping Cart and Checkout flow.
- **v0.1**: **Foundation**: Initial MVC framework setup and User Authentication.

---

## 🚀 Features

### 🎓 For Students
- **Immersive Learning Player**: Distraction-free video playback with lesson navigation and auto-resume.
- **Adaptive Quizzes**: Interactive assessments supporting multiple choice, true/false, fill-in-the-blank, matching, essay, and video submissions.
- **Real-time Feedback**: View per-question scores and approved **Scracha** AI rationales immediately after instructor review.
- **Notes System**: Take private notes timestamped to specific lessons.
- **Global Notifications**: Instant feedback via **Toast Notifications** with audio alerts for grading results and course updates.
- **Dashboard**: Centralized hub for enrolled courses, notifications, and achievements.

### 👨‍🏫 For Instructors
- **Visual Quiz Builder**: Craft assessments using a modern timeline interface with a 3-column collapsible editor.
- **Advanced Grading Queue**: Dedicated workflow for reviewing subjective answers with **Scracha** AI-assisted scoring and rationales.
- **Drip Content Management**: Configure unlock rules (Days, Completion %, Sequence) directly from the course settings.
- **Course Builder**: Drag-and-drop curriculum management with a global **Lesson Library**.
- **Unified Approvals**: streamlined dashboard for managing quiz retake requests and manual certificate issuance.
- **Analytics**: Track student enrollment, progress, and detailed quiz performance.

### 🛡️ For Administrators
- **User Management**: Bulk actions for enrolling, unenrolling, and managing user roles.
- **Course Governance**: Review, publish, or unpublish courses.
- **System Health**: Monitor application logs and system integrity.
- **Security Core**: Built-in CSRF protection, Rate Limiting, and Role-Based Access Control (RBAC).

## 🔐 Security Layers

ScratchLMS is built with a "Secure by Default" philosophy, implementing defense-in-depth strategies at the framework level:

- **1. Authorization (RBAC)**: Centralized Policy logic ensures that only the rightful owners or administrators can modify resources (Courses, Lessons, Quizzes).
- **2. Request Integrity (CSRF)**: Automated Double-Submit Cookie protection for all POST/PUT/DELETE requests. JavaScript fetch wrappers automatically inject the required `X-CSRF-TOKEN` headers.
- **3. Traffic Control (Rate Limiting)**: Leaky bucket algorithm protects sensitive endpoints (e.g., Login, Quiz Submissions, Video Uploads) from brute-force and DoS attacks.
- **4. Data Safety**: Strict usage of PDO Prepared Statements prevents SQL Injection. Input sanitization utilities strip malicious tags from user-generated content.

## 🧠 Smart Grading Engine (Powered by Scracha)

ScratchLMS introduces a flexible grading system designed for modern learning needs:

- **Strict Mode**: Requires exact matches (case-insensitive). Ideal for coding terms or precise answers.
- **Fuzzy Match**: Uses similarity algorithms to accept answers with minor typos (85%+ match).
- **AI-Assisted Grading**: **Scracha** pre-scores subjective responses and provides a detailed rationale to assist instructors.
- **Manual Review**: Flags subjective answers (Essays, Video URLs) for instructor approval before finalizing the quiz attempt.

## 🗺️ Adaptive Learning Paths

Instructors can define the student journey through flexible "Content Flow" settings:

- **Linear Sequencing**: Enforce a step-by-step progression. Students must complete the current lesson before unlocking the next.
- **Free Exploration**: Allow students to jump between lessons and modules freely.
- **Drip Content**: Schedule content release based on enrollment duration, specific dates, or completion milestones.

## 🛠️ Technology Stack

- **Language**: PHP 8.1+
- **Database**:
    - **Primary Support**: MySQL 8.0+, PostgreSQL 13+, SQLite 3
    - **Configuration**: Environment-based (.env) via custom Adapter factory
    - **ORM/Query**: Native PDO with custom Database helper and Grammar classes
- **AI Engine**: **Scracha** (Built on Groq Llama 3.1 70B architecture)
- **Architecture**: Custom Model-View-Controller (MVC)
- **Frontend**: Vanilla JS, HTML5, Tailwind CSS (Dark Mode Native), Alpine.js
- **Audio/UI**: Reusable Toast System with embedded Base64 audio feedback.
- **Dependencies**: Managed via Composer (Minimal external dependencies)

## 📋 Prerequisites

Ensure you have the following installed on your machine:
- **PHP 8.1** or higher
- **Composer**
- **Database**: SQLite3 (bundled with PHP), MySQL, or PostgreSQL

## ⚡ Installation

1.  **Clone the Repository**
    ```bash
    git clone https://github.com/karimmokhtar/lms.git
    cd lms
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    ```

3.  **Configure Environment**
    Copy the example configuration file and set your database credentials.
    ```bash
    cp .env.example .env
    # Edit .env to set DB_CONNECTION=sqlite, mysql, or pgsql
    ```

4.  **Run Migrations**
    Use the new CLI tool to set up your database schema.
    ```bash
    php migrate.php
    ```
    *Note: For development with SQLite, `php setup_db.php` is also available for a full reset.*

5.  **Directory Permissions**
    Ensure the web server can write to the storage and uploads directories.
    ```bash
    chmod -R 775 storage public/uploads
    ```

## 🚀 Usage

### Running Locally
You can use the built-in PHP development server for testing:

```bash
php -S localhost:8000 -t public
```

Visit `http://localhost:8000` in your browser.

## 📂 Project Structure

```
LMS/
├── app/
│   ├── Core/           # System core (Router, Database, CSRF, RateLimiter)
│   │   ├── Database/   # Database Adapters, Grammars, and Migrations
│   ├── Domain/         # Domain Entities (Courses, Lessons, Quizzes, Users)
│   ├── Http/           # Controllers, Middleware, Requests, and Responses
│   ├── Policies/       # Authorization and Access Control Logic
│   └── Services/       # Business Logic (Quiz Engine, Progress Calculation)
├── config/             # Application & Database configuration
├── database/           # SQLite database, Migrations, and Seeders
├── docs/               # ADRs, AI Context, and Daily Progress Reports
├── public/             # Web root (Entry point, Assets, User Uploads)
├── resources/
│   ├── lang/           # Localization (English/Arabic support)
│   └── views/          # HTML templates (Instructor, Student, Admin)
├── routes/             # Route definitions (web.php)
├── storage/            # Application logs and temporary files
└── tests/              # Feature simulations and system audits
```

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1.  Fork the repository.
2.  Create a feature branch: `git checkout -b feature/amazing-feature`.
3.  Commit your changes: `git commit -m 'Add some amazing feature'`.
4.  Push to the branch: `git push origin feature/amazing-feature`.
5.  Open a Pull Request.

Please refer to `GEMINI.md` and `CODEX.md` for specific AI-agent workflows if you are working within the AI-assisted development environment.

## 📄 License

This project is open-source and available under the [MIT License](LICENSE).