# ScratchLMS

A high-performance, lightweight Learning Management System (LMS) built from scratch using a custom MVC architecture in PHP. ScratchLMS provides a comprehensive suite of tools for students, instructors, and administrators, focusing on speed, simplicity, and flexibility without the overhead of heavy frameworks.

## 🚀 Features

### 🎓 For Students
- **Immersive Learning Player**: Distraction-free video playback with lesson navigation.
- **Progress Tracking**: Real-time course progress calculation.
- **Quizzes & Assessments**: Interactive quizzes with instant feedback, retry limits, and result review.
- **Notes System**: Take private notes timestamped to specific lessons.
- **Certificates**: Auto-generated certificates upon course completion.
- **Dashboard**: Centralized hub for enrolled courses, notifications, and achievements.

### 👨‍🏫 For Instructors
- **Course Builder**: Drag-and-drop curriculum management (Modules & Lessons).
- **Question Bank**: reusable repository of questions for building dynamic quizzes.
- **Rich Media Support**: Upload and manage video lessons and resources.
- **Analytics**: Track student enrollment, progress, and quiz performance.
- **Student Management**: Monitor activity and handle enrollment requests.

### 🛡️ For Administrators
- **User Management**: Bulk actions for enrolling, unenrolling, and managing user roles.
- **Course Governance**: Review, publish, or unpublish courses.
- **System Health**: Monitor application logs and system integrity.

### 🛒 E-Commerce
- **Shopping Cart**: Fully functional cart system.
- **Checkout Flow**: Secure checkout process for course purchases.
- **Coupons**: Discount code support (planned/implemented).

## 🛠️ Technology Stack

- **Language**: PHP 8.1+
- **Database**: SQLite (Zero-config, file-based database)
- **Architecture**: Custom Model-View-Controller (MVC)
- **Frontend**: Vanilla JS, HTML5, CSS3 (No heavy frontend framework dependencies)
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

### Default Accounts (if seeded)
*Check `database/seeds` or `setup_db.php` logic for initial credentials if applicable. Otherwise, use the Register page to create a new account.*

## 📂 Project Structure

```
LMS/
├── app/
│   ├── Core/           # Framework core (Router, Database, Model, Controller)
│   ├── Domain/         # Domain entities (Courses, Users, Orders, etc.)
│   ├── Http/           # Controllers, Middleware, Requests
│   └── Services/       # Business logic services
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