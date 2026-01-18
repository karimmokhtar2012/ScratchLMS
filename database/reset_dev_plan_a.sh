#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DB_PATH="${ROOT_DIR}/database/database.sqlite"

echo "Resetting dev database for Plan A..."

rm -f "${DB_PATH}"

php "${ROOT_DIR}/setup_db.php"
php "${ROOT_DIR}/database/migrate_auth.php"
php "${ROOT_DIR}/database/migrate_courses.php"
php "${ROOT_DIR}/database/migrate_rich_content.php"
php "${ROOT_DIR}/database/migrate_jan15.php"
php "${ROOT_DIR}/database/migrate_content_flow.php"
php "${ROOT_DIR}/database/migrate_quiz_engine.php"
php "${ROOT_DIR}/database/migrate_draft_upcoming.php"
php "${ROOT_DIR}/database/migrate_question_bank.php"
php "${ROOT_DIR}/database/migrate_quiz_extensions.php"
php "${ROOT_DIR}/database/migrate_enrollments.php"
php "${ROOT_DIR}/database/migrate_tracking.php"
php "${ROOT_DIR}/database/migrate_notifications.php"
php "${ROOT_DIR}/database/migrate_notes.php"
php "${ROOT_DIR}/database/migrate_requests.php"
php "${ROOT_DIR}/database/migrate_commerce.php"
php "${ROOT_DIR}/database/migrate_content_items.php"
php "${ROOT_DIR}/database/seed_content_rule_definitions.php"
php "${ROOT_DIR}/database/seed_test_trading_course.php"

echo "Running content items audit..."
php "${ROOT_DIR}/database/audit_content_items.php"

echo "Done."
