<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Services\ContentRuleService;

$db = Database::getInstance();
$driver = $db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);

$tableCheck = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name='content_rule_definitions'");
if (empty($tableCheck) && $driver !== 'sqlite') {
    $tableCheck = $db->fetchAll("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'content_rule_definitions'");
}

if (empty($tableCheck)) {
    echo "Missing content_rule_definitions table. Run database/migrate_content_items.php first.\n";
    exit(1);
}

$ruleService = new ContentRuleService();
$catalog = $ruleService->getRuleCatalog();

$inserted = 0;
$updated = 0;

foreach ($catalog as $ruleType => $definition) {
    $version = (int) ($definition['version'] ?? 1);
    $schemaJson = json_encode($definition['params'], JSON_UNESCAPED_SLASHES);
    $uiJson = json_encode($definition['ui'], JSON_UNESCAPED_SLASHES);

    $existing = $db->fetchAll("SELECT id, schema_json, ui_schema_json FROM content_rule_definitions WHERE rule_type = :type AND version = :version", [
        ':type' => $ruleType,
        ':version' => $version
    ]);

    if (empty($existing)) {
        $db->query("INSERT INTO content_rule_definitions (rule_type, version, schema_json, ui_schema_json) VALUES (:type, :version, :schema, :ui)", [
            ':type' => $ruleType,
            ':version' => $version,
            ':schema' => $schemaJson,
            ':ui' => $uiJson
        ]);
        $inserted++;
        continue;
    }

    $current = $existing[0];
    if ($current['schema_json'] !== $schemaJson || $current['ui_schema_json'] !== $uiJson) {
        $db->query("UPDATE content_rule_definitions SET schema_json = :schema, ui_schema_json = :ui WHERE id = :id", [
            ':schema' => $schemaJson,
            ':ui' => $uiJson,
            ':id' => $current['id']
        ]);
        $updated++;
    }
}

echo "Seeded content_rule_definitions. Inserted: {$inserted}, Updated: {$updated}\n";
