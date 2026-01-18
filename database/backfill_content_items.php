<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Services\ContentRuleService;

$db = Database::getInstance();
$driver = $db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);

$options = getopt('', ['course_id::', 'reset', 'dry-run']);
$courseId = isset($options['course_id']) ? (int) $options['course_id'] : null;
$reset = array_key_exists('reset', $options);
$dryRun = array_key_exists('dry-run', $options);

$tableCheck = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name='content_items'");
if (empty($tableCheck) && $driver !== 'sqlite') {
    $tableCheck = $db->fetchAll("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'content_items'");
}

if (empty($tableCheck)) {
    echo "Missing content_items table. Run database/migrate_content_items.php first.\n";
    exit(1);
}

$courses = [];
if ($courseId) {
    $courses = $db->fetchAll("SELECT id FROM courses WHERE id = :id", [':id' => $courseId]);
} else {
    $courses = $db->fetchAll("SELECT id FROM courses");
}

if (empty($courses)) {
    echo "No courses found to backfill.\n";
    exit(0);
}

$ruleService = new ContentRuleService();
$insertedItems = 0;
$insertedRules = 0;
$skippedRules = 0;

foreach ($courses as $course) {
    $cid = (int) $course['id'];
    echo "Backfilling course {$cid}...\n";

    if ($reset) {
        if ($dryRun) {
            echo "  [dry-run] Would delete existing content items and rules.\n";
        } else {
            $db->query("DELETE FROM content_item_rules WHERE item_id IN (SELECT id FROM content_items WHERE course_id = :cid)", [
                ':cid' => $cid
            ]);
            $db->query("DELETE FROM content_prerequisites WHERE item_id IN (SELECT id FROM content_items WHERE course_id = :cid) OR prerequisite_item_id IN (SELECT id FROM content_items WHERE course_id = :cid)", [
                ':cid' => $cid
            ]);
            $db->query("DELETE FROM content_items WHERE course_id = :cid", [':cid' => $cid]);
        }
    }

    $modules = $db->fetchAll("SELECT id FROM modules WHERE course_id = :cid ORDER BY sort_order ASC, id ASC", [':cid' => $cid]);
    $position = 1;

    foreach ($modules as $module) {
        $mid = (int) $module['id'];
        $lessons = $db->fetchAll("SELECT id, type FROM lessons WHERE module_id = :mid ORDER BY sort_order ASC, id ASC", [':mid' => $mid]);

        foreach ($lessons as $lesson) {
            $lessonId = (int) $lesson['id'];
            $lessonType = (string) ($lesson['type'] ?? '');

            if ($lessonType === 'quiz') {
                $quiz = $db->fetchAll("SELECT id FROM quizzes WHERE lesson_id = :lid", [':lid' => $lessonId])[0] ?? null;
                if ($quiz) {
                    if (!$dryRun) {
                        $db->query("INSERT INTO content_items (course_id, module_id, item_type, item_id, position, display_group_id, created_at)
                                    VALUES (:cid, :mid, 'quiz', :qid, :pos, NULL, CURRENT_TIMESTAMP)", [
                            ':cid' => $cid,
                            ':mid' => $mid,
                            ':qid' => (int) $quiz['id'],
                            ':pos' => $position
                        ]);
                    }
                    $insertedItems++;
                    $position++;
                    continue;
                }
            }

            if (!$dryRun) {
                $db->query("INSERT INTO content_items (course_id, module_id, item_type, item_id, position, display_group_id, created_at)
                            VALUES (:cid, :mid, 'lesson', :lid, :pos, NULL, CURRENT_TIMESTAMP)", [
                    ':cid' => $cid,
                    ':mid' => $mid,
                    ':lid' => $lessonId,
                    ':pos' => $position
                ]);
            }
            $insertedItems++;
            $position++;
        }
    }

    $rules = $db->fetchAll("SELECT * FROM lesson_drip_rules");
    foreach ($rules as $rule) {
        $itemRow = $db->fetchAll("SELECT id FROM content_items WHERE course_id = :cid AND item_type = 'lesson' AND item_id = :lid", [
            ':cid' => $cid,
            ':lid' => (int) $rule['lesson_id']
        ])[0] ?? null;

        if (!$itemRow) {
            $skippedRules++;
            continue;
        }

        $itemId = (int) $itemRow['id'];

        $ruleRecords = [];
        if (!empty($rule['unlock_after_days'])) {
            $ruleRecords[] = [
                'rule_type' => 'unlock_after_days',
                'params' => ['days' => (int) $rule['unlock_after_days']],
            ];
        }
        if (!empty($rule['unlock_after_lesson_id'])) {
            $unlockItem = $db->fetchAll("SELECT id FROM content_items WHERE course_id = :cid AND item_type = 'lesson' AND item_id = :lid", [
                ':cid' => $cid,
                ':lid' => (int) $rule['unlock_after_lesson_id']
            ])[0] ?? null;
            if ($unlockItem) {
                $ruleRecords[] = [
                    'rule_type' => 'unlock_after_item_completion',
                    'params' => ['item_id' => (int) $unlockItem['id']],
                ];
            } else {
                $skippedRules++;
            }
        }
        if (!empty($rule['unlock_after_percent'])) {
            $ruleRecords[] = [
                'rule_type' => 'unlock_after_percent',
                'params' => ['percent' => (int) $rule['unlock_after_percent']],
            ];
        }

        foreach ($ruleRecords as $record) {
            [$isValid, $errors] = $ruleService->validateRuleParams($record['rule_type'], $record['params']);
            if (!$isValid) {
                $skippedRules++;
                continue;
            }

            if (!$dryRun) {
                $db->query("INSERT INTO content_item_rules (item_id, rule_type, params_json, version)
                            VALUES (:item_id, :rule_type, :params_json, :version)", [
                    ':item_id' => $itemId,
                    ':rule_type' => $record['rule_type'],
                    ':params_json' => json_encode($record['params']),
                    ':version' => 1
                ]);
            }
            $insertedRules++;
        }
    }
}

echo "Backfill complete. Items inserted: {$insertedItems}, Rules inserted: {$insertedRules}, Rules skipped: {$skippedRules}\n";
