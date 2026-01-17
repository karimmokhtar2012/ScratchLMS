<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Services\ContentRuleService;

$db = Database::getInstance();
$driver = $db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);

echo "Content Items Audit\n";
echo "Driver: {$driver}\n\n";

$tableCheck = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name='content_items'");
if (empty($tableCheck) && $driver !== 'sqlite') {
    $tableCheck = $db->fetchAll("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'content_items'");
}

if (empty($tableCheck)) {
    echo "Missing content_items table. Run database/migrate_content_items.php first.\n";
    exit(1);
}

$issues = [];

$invalidTypes = $db->fetchAll("SELECT COUNT(*) as count FROM content_items WHERE item_type NOT IN ('lesson','quiz','test')");
if (($invalidTypes[0]['count'] ?? 0) > 0) {
    $issues[] = "Invalid item_type rows: " . $invalidTypes[0]['count'];
}

$orphanLessons = $db->fetchAll("SELECT ci.id, ci.item_id FROM content_items ci
    LEFT JOIN lessons l ON ci.item_type = 'lesson' AND ci.item_id = l.id
    WHERE ci.item_type = 'lesson' AND l.id IS NULL");
if (!empty($orphanLessons)) {
    $issues[] = "Orphan lesson items: " . count($orphanLessons);
}

$orphanQuizzes = $db->fetchAll("SELECT ci.id, ci.item_id FROM content_items ci
    LEFT JOIN quizzes q ON ci.item_type = 'quiz' AND ci.item_id = q.id
    WHERE ci.item_type = 'quiz' AND q.id IS NULL");
if (!empty($orphanQuizzes)) {
    $issues[] = "Orphan quiz items: " . count($orphanQuizzes);
}

$missingLessonItems = $db->fetchAll("SELECT l.id, m.course_id
    FROM lessons l
    JOIN modules m ON l.module_id = m.id
    LEFT JOIN content_items ci ON ci.item_type = 'lesson' AND ci.item_id = l.id
    WHERE ci.id IS NULL AND l.type != 'quiz'");
if (!empty($missingLessonItems)) {
    $issues[] = "Lessons missing content_items: " . count($missingLessonItems);
}

$missingQuizItems = $db->fetchAll("SELECT q.id, m.course_id
    FROM quizzes q
    JOIN lessons l ON q.lesson_id = l.id
    JOIN modules m ON l.module_id = m.id
    LEFT JOIN content_items ci ON ci.item_type = 'quiz' AND ci.item_id = q.id
    WHERE ci.id IS NULL");
if (!empty($missingQuizItems)) {
    $issues[] = "Quizzes missing content_items: " . count($missingQuizItems);
}

$duplicateItems = $db->fetchAll("SELECT course_id, item_type, item_id, COUNT(*) as count
    FROM content_items
    GROUP BY course_id, item_type, item_id
    HAVING count > 1");
if (!empty($duplicateItems)) {
    $issues[] = "Duplicate content_items per course/type/item: " . count($duplicateItems);
}

$duplicatePositions = $db->fetchAll("SELECT course_id, position, COUNT(*) as count
    FROM content_items
    GROUP BY course_id, position
    HAVING count > 1");
if (!empty($duplicatePositions)) {
    $issues[] = "Duplicate positions per course: " . count($duplicatePositions);
}

$progressTableCheck = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name='content_progress'");
if (empty($progressTableCheck) && $driver !== 'sqlite') {
    $progressTableCheck = $db->fetchAll("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'content_progress'");
}

if (empty($progressTableCheck)) {
    $issues[] = "Missing content_progress table";
} else {
    $orphanProgress = $db->fetchAll("SELECT cp.id FROM content_progress cp
        LEFT JOIN content_items ci ON cp.item_id = ci.id
        WHERE ci.id IS NULL");
    if (!empty($orphanProgress)) {
        $issues[] = "Orphan content_progress rows: " . count($orphanProgress);
    }

    $duplicateProgress = $db->fetchAll("SELECT user_id, item_id, COUNT(*) as count
        FROM content_progress
        GROUP BY user_id, item_id
        HAVING count > 1");
    if (!empty($duplicateProgress)) {
        $issues[] = "Duplicate content_progress rows: " . count($duplicateProgress);
    }

    $legacyMissing = $db->fetchAll("SELECT COUNT(*) as count
        FROM completed_lessons cl
        JOIN content_items ci ON ci.item_type = 'lesson' AND ci.item_id = cl.lesson_id
        LEFT JOIN content_progress cp ON cp.item_id = ci.id AND cp.user_id = cl.user_id AND cp.status = 'completed'
        WHERE cp.id IS NULL");
    if (($legacyMissing[0]['count'] ?? 0) > 0) {
        $issues[] = "Parity mismatch: completed_lessons not reflected in content_progress (lesson items): " . $legacyMissing[0]['count'];
    }

    $newMissing = $db->fetchAll("SELECT COUNT(*) as count
        FROM content_progress cp
        JOIN content_items ci ON cp.item_id = ci.id AND ci.item_type = 'lesson'
        LEFT JOIN completed_lessons cl ON cl.lesson_id = ci.item_id AND cl.user_id = cp.user_id
        WHERE cp.status = 'completed' AND cl.lesson_id IS NULL");
    if (($newMissing[0]['count'] ?? 0) > 0) {
        $issues[] = "Parity mismatch: content_progress completed lessons missing in completed_lessons: " . $newMissing[0]['count'];
    }
}

$ruleIssues = [];
$rulesTableCheck = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name='content_item_rules'");
if (empty($rulesTableCheck) && $driver !== 'sqlite') {
    $rulesTableCheck = $db->fetchAll("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'content_item_rules'");
}

if (empty($rulesTableCheck)) {
    $issues[] = "Missing content_item_rules table";
} else {
    $rules = $db->fetchAll("SELECT id, rule_type, params_json FROM content_item_rules");
    if (!empty($rules)) {
        $ruleService = new ContentRuleService();
        foreach ($rules as $rule) {
            [$ok, $errors] = $ruleService->validateRuleRecord($rule);
            if (!$ok) {
                $ruleIssues[] = [
                    'id' => $rule['id'],
                    'errors' => $errors,
                ];
            }
        }
    }
}

if (!empty($ruleIssues)) {
    $issues[] = "Invalid content_item_rules: " . count($ruleIssues);
}

$ruleCatalog = (new ContentRuleService())->getRuleCatalog();
$ruleDefCheck = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name='content_rule_definitions'");
if (empty($ruleDefCheck) && $driver !== 'sqlite') {
    $ruleDefCheck = $db->fetchAll("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'content_rule_definitions'");
}

if (empty($ruleDefCheck)) {
    $issues[] = "Missing content_rule_definitions table";
} else {
    foreach ($ruleCatalog as $ruleType => $definition) {
        $version = (int) ($definition['version'] ?? 1);
        $check = $db->fetchAll("SELECT id FROM content_rule_definitions WHERE rule_type = :type AND version = :version", [
            ':type' => $ruleType,
            ':version' => $version
        ]);
        if (empty($check)) {
            $issues[] = "Missing rule definition: {$ruleType} v{$version}";
        }
    }
}

if (empty($issues)) {
    echo "Audit OK. No issues found.\n";
    exit(0);
}

echo "Audit issues found:\n";
foreach ($issues as $issue) {
    echo "- {$issue}\n";
}

if (!empty($ruleIssues)) {
    echo "\nRule validation failures (first 5):\n";
    $slice = array_slice($ruleIssues, 0, 5);
    foreach ($slice as $entry) {
        $errorList = implode('; ', $entry['errors']);
        echo "- Rule ID {$entry['id']}: {$errorList}\n";
    }
}

exit(1);
