# ScratchLMS

A high-performance, lightweight Learning Management System (LMS) built from scratch using a custom MVC architecture in PHP. ScratchLMS provides a comprehensive suite of tools for students, instructors, and administrators, focusing on speed, simplicity, and flexibility without the overhead of heavy frameworks.

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
    - **Manual Grading Queue**: Dedicated workflow for reviewing essays and video submissions with AI score acceptance.
- **Unified Approvals**: streamlined dashboard for managing quiz retake requests and certificate issuance.
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
- **AI-Assisted Grading**: (Experimental) Pre-scores essay responses to assist instructors, who can then "Accept" or modify the score.
- **Manual Review**: Flags subjective answers (Essays, Video URLs) for instructor approval before finalizing the quiz attempt.

## 🗺️ Adaptive Learning Paths

Instructors can define the student journey through flexible "Content Flow" settings:

- **Linear Sequencing**: Enforce a step-by-step progression. Students must complete the current lesson before unlocking the next.
- **Free Exploration**: Allow students to jump between lessons and modules freely.
- **Drip Content**: (Coming Soon) Schedule content release based on enrollment duration or specific dates.

### 🛒 E-Commerce
- **Shopping Cart**: Fully functional cart system.
- **Checkout Flow**: Secure checkout process for course purchases.
- **Coupons**: Discount code support (planned/implemented).

## 🛠️ Technology Stack

- **Language**: PHP 8.1+
- **Database**: SQLite (Zero-config, file-based database)
- **Architecture**: Custom Model-View-Controller (MVC)
- **Frontend**: Vanilla JS, HTML5, Tailwind CSS (Dark Mode Native)
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
