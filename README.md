# ScratchLMS

ScratchLMS is a lightweight learning management system built with PHP and SQLite.
It includes student and instructor dashboards, course playback, quizzes, notes, and admin tools.

## Features
- Student dashboard and onboarding flow
- Instructor course builder and analytics
- Course playback with progress tracking
- Quizzes with attempts and retake requests
- Notes per lesson
- Admin user/course management

## Requirements
- PHP 8.1+
- SQLite
- Composer

## Setup
1) Install dependencies
```bash
composer install
```

2) Initialize the database
```bash
php setup_db.php
```

3) Run the app (example with PHP built-in server)
```bash
php -S localhost:8000 -t public
```

Then open `http://localhost:8000`.

## Tests
If you have tests configured:
```bash
php vendor/bin/phpunit
```

## Project Structure
- `app/` core application code
- `resources/views/` PHP views
- `routes/` route definitions
- `public/` public assets and entry point
- `database/` migrations and scripts
- `docs/` documentation

## Contributing
- Use feature branches: `feat/<name>` or `fix/<name>`
- Open a PR for every change
- Keep changes small and focused

## License
No license specified yet.

