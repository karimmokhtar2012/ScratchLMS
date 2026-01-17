# ScratchLMS

A high-performance, lightweight Learning Management System (LMS) built from scratch using a custom MVC architecture in PHP. ScratchLMS provides a comprehensive suite of tools for students, instructors, and administrators, focusing on speed, simplicity, and flexibility without the overhead of heavy frameworks.

## 🌟 Version 0.7 Release (January 17, 2026)

**Introducing Scracha**, the AI backbone of ScratchLMS, alongside a massive UI/UX overhaul for the Instructor Studio.

### 🤖 Meet "Scracha"
**Scracha** is our purpose-built AI engine designed to augment the educational experience. It doesn't just grade; it understands.
- **Smart Grading**: Automatically scores subjective answers like essays and short text with high accuracy.
- **Contextual Rationale**: Provides instructors with a detailed "Why?" for every AI-suggested score, which can be transparently shared with students.
- **Rubric Awareness**: Follows instructor-defined rubrics to ensure grading consistency across hundreds of students.
- **Adaptive Feedback**: (Coming Soon) Offers personalized study tips based on quiz performance.

### 🎨 Instructor Studio Redesign
We've completely reimagined the course creation workflow to be faster, cleaner, and more powerful.
- **Visual Quiz Builder**: A new timeline-based interface for crafting assessments.
- **WYSIWYG Editor**: A rich-text question editor with live preview for Bold, Italic, Underline, and Images.
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

- **v0.5 (Jan 14)**: **Data Integrity & Tracking**: Fixed critical foreign key constraints for progress tracking and xAPI logs. Standardized database migrations.
- **v0.4 (Jan 12)**: **Instructor Analytics**: Dashboard for viewing enrollment stats, completion rates, and quiz averages.
- **v0.3 (Jan 10)**: **Student Experience**: Implemented the "Immersive Player" for distraction-free learning, auto-resume, and note-taking.
- **v0.2 (Jan 08)**: **Core Commerce**: Added Shopping Cart, Checkout flow, and initial Order management.
- **v0.1 (Jan 05)**: **Foundation**: Initial MVC framework setup, User Authentication (Login/Register), and basic Course/Lesson CRUD.

---

## 🚀 Features

### 🎓 For Students
- **Immersive Learning Player**: Distraction-free video playback with lesson navigation and auto-resume.
- **Adaptive Quizzes**: Interactive assessments supporting multiple choice, true/false, fill-in-the-blank, matching, essay, and video submissions.
- **Progress Tracking**: Real-time course progress calculation with visual indicators.
- **Notes System**: Take private notes timestamped to specific lessons.
- **Certificates**: Auto-generated certificates upon completion, with support for manual instructor issuance.
- **Dashboard**: Centralized hub for enrolled courses, pending retakes, notifications, and achievements.

### 👨‍🏫 For Instructors
- **Course Builder**: Drag-and-drop curriculum management (Modules & Lessons) with bulk reordering.
- **Advanced Quiz Engine**: 
    - **Grading Policies**: Configure Strict (exact match), Fuzzy (typo-tolerant), or Manual (instructor review) grading per question.
    - **Question Bank**: Reusable repository of questions for rapid quiz assembly.
    - **Manual Grading Queue**: Dedicated workflow for reviewing essays and video submissions with **Scracha** AI assistance.
- **Unified Approvals**: Streamlined dashboard for managing quiz retake requests and certificate issuance.
- **Rich Media Support**: Upload and manage video lessons, resources, and attachments.
- **Analytics**: Track student enrollment, progress, and quiz performance.

### 🛡️ For Administrators
- **User Management**: Bulk actions for enrolling, unenrolling, and managing user roles.
- **Course Governance**: Review, publish, or unpublish courses.
- **System Health**: Monitor application logs and system integrity.
- **Security Core**: Built-in CSRF protection, Rate Limiting, and Role-Based Access Control (RBAC).

## 🧠 Smart Grading Engine

ScratchLMS introduces a flexible grading system designed for modern learning needs:

- **Strict Mode**: Requires exact matches (case-insensitive). Ideal for coding terms or precise answers.
- **Fuzzy Match**: Uses similarity algorithms (e.g., Levenshtein distance) to accept answers with minor typos (85%+ match).
- **AI-Assisted Grading (Scracha)**: Pre-scores subjective responses to assist instructors, who can then "Accept" or modify the score based on the AI's rationale.
- **Manual Review**: Flags subjective answers (Essays, Video URLs) for instructor approval before finalizing the quiz attempt.

## 🗺️ Adaptive Learning Paths

Instructors can define the student journey through flexible "Content Flow" settings:

- **Linear Sequencing**: Enforce a step-by-step progression. Students must complete the current lesson before unlocking the next.
- **Free Exploration**: Allow students to jump between lessons and modules freely.
- **Drip Content**: Schedule content release based on enrollment duration or specific dates (Integrated in Course Settings).

### 🛒 E-Commerce
- **Shopping Cart**: Fully functional cart system.
- **Checkout Flow**: Secure checkout process for course purchases.
- **Coupons**: Discount code support.

## 🛠️ Technology Stack

- **Language**: PHP 8.1+
- **Database**: SQLite (Zero-config, file-based database)
- **Architecture**: Custom Model-View-Controller (MVC)
- **Frontend**: Vanilla JS, HTML5, Tailwind CSS (Dark Mode Native), Alpine.js
- **Dependencies**: Managed via Composer (Minimal external dependencies)

## 📋 Prerequisites

Ensure you have the following installed on your machine:
- **PHP 8.1** or higher
- **Composer**
- **SQLite3** driver for PHP

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

3.  **Initialize Database**
    This script creates the SQLite database file and runs all necessary migrations.
    ```bash
    php setup_db.php
    ```
    *Note: This will create/reset `database/database.sqlite`.*

4.  **Directory Permissions**
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
│   ├── Core/           # Framework core (Router, Database, Model, Controller)
│   ├── Domain/         # Domain entities (Courses, Users, Orders, etc.)
│   ├── Http/           # Controllers, Middleware, Requests
│   └── Services/       # Business logic services (QuizService, CourseProgressService)
├── config/             # Application configuration
├── database/           # SQLite DB, migrations, and seeders
├── public/             # Web entry point (index.php), assets, uploads
├── resources/
│   ├── lang/           # Localization files
│   └── views/          # HTML templates/views
├── routes/             # Route definitions (web.php)
└── docs/               # Documentation and architectural decision records
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