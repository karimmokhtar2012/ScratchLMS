<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

function printUsage(): void
{
    echo "LearnPress Import\n";
    echo "Usage:\n";
    echo "  php database/import_learnpress_courses.php --file=PATH [options]\n";
    echo "Options:\n";
    echo "  --apply                Apply changes (default is dry-run)\n";
    echo "  --dry-run              Parse and report only\n";
    echo "  --instructor-id=ID     Default instructor ID\n";
    echo "  --instructor-email=EM  Default instructor email\n";
    echo "  --map-author-email     Map dc:creator email to users when possible\n";
    echo "  --skip-lessons         Skip lessons import\n";
    echo "  --skip-quizzes         Skip quiz lessons import\n";
    echo "  --vdo-url-template=T   Template for [vdo id] (use {id} placeholder)\n";
    echo "  --help                 Show this help\n";
}

function readElementValue(XMLReader $reader): string
{
    if ($reader->isEmptyElement) {
        return '';
    }

    $value = '';
    while ($reader->read()) {
        if ($reader->nodeType === XMLReader::TEXT || $reader->nodeType === XMLReader::CDATA) {
            $value .= $reader->value;
        } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
            break;
        }
    }

    return $value;
}

function skipElement(XMLReader $reader): void
{
    if ($reader->isEmptyElement) {
        return;
    }

    $depth = 1;
    while ($reader->read()) {
        if ($reader->nodeType === XMLReader::ELEMENT && !$reader->isEmptyElement) {
            $depth++;
        } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
            $depth--;
            if ($depth === 0) {
                break;
            }
        }
    }
}

function readPostmeta(XMLReader $reader): array
{
    $key = '';
    $value = '';

    if ($reader->isEmptyElement) {
        return ['key' => $key, 'value' => $value];
    }

    while ($reader->read()) {
        if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'postmeta') {
            break;
        }
        if ($reader->nodeType !== XMLReader::ELEMENT) {
            continue;
        }
        if ($reader->prefix === 'wp' && $reader->localName === 'meta_key') {
            $key = readElementValue($reader);
        } elseif ($reader->prefix === 'wp' && $reader->localName === 'meta_value') {
            $value = readElementValue($reader);
        }
    }

    return ['key' => $key, 'value' => $value];
}

function readSectionItem(XMLReader $reader): array
{
    $item = ['id' => null, 'order' => 0, 'type' => ''];

    if ($reader->isEmptyElement) {
        return $item;
    }

    while ($reader->read()) {
        if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'section_item') {
            break;
        }
        if ($reader->nodeType !== XMLReader::ELEMENT) {
            continue;
        }
        if ($reader->prefix !== 'wp') {
            continue;
        }

        if ($reader->localName === 'item_id') {
            $item['id'] = (int) readElementValue($reader);
        } elseif ($reader->localName === 'item_order') {
            $item['order'] = (int) readElementValue($reader);
        } elseif ($reader->localName === 'item_type') {
            $item['type'] = readElementValue($reader);
        }
    }

    return $item;
}

function readSection(XMLReader $reader): array
{
    $section = [
        'id' => null,
        'name' => '',
        'order' => 0,
        'description' => '',
        'items' => []
    ];

    if ($reader->isEmptyElement) {
        return $section;
    }

    while ($reader->read()) {
        if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'section') {
            break;
        }
        if ($reader->nodeType !== XMLReader::ELEMENT) {
            continue;
        }
        if ($reader->prefix !== 'wp') {
            continue;
        }

        if ($reader->localName === 'section_id') {
            $section['id'] = (int) readElementValue($reader);
        } elseif ($reader->localName === 'section_name') {
            $section['name'] = readElementValue($reader);
        } elseif ($reader->localName === 'section_order') {
            $section['order'] = (int) readElementValue($reader);
        } elseif ($reader->localName === 'section_description') {
            $section['description'] = readElementValue($reader);
        } elseif ($reader->localName === 'section_item') {
            $section['items'][] = readSectionItem($reader);
        }
    }

    return $section;
}

function readItem(XMLReader $reader): array
{
    $item = [
        'meta' => [],
        'sections' => []
    ];

    if ($reader->isEmptyElement) {
        return $item;
    }

    while ($reader->read()) {
        if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'item') {
            break;
        }
        if ($reader->nodeType !== XMLReader::ELEMENT) {
            continue;
        }

        $prefix = $reader->prefix;
        $name = $reader->localName;

        if ($prefix === 'wp' && $name === 'attachment') {
            skipElement($reader);
            continue;
        }

        if ($prefix === '' && $name === 'title') {
            $item['title'] = readElementValue($reader);
        } elseif ($prefix === '' && $name === 'link') {
            $item['link'] = readElementValue($reader);
        } elseif ($prefix === 'content' && $name === 'encoded') {
            $item['content'] = readElementValue($reader);
        } elseif ($prefix === 'excerpt' && $name === 'encoded') {
            $item['excerpt'] = readElementValue($reader);
        } elseif ($prefix === 'dc' && $name === 'creator') {
            $item['creator'] = readElementValue($reader);
        } elseif ($prefix === 'wp' && $name === 'post_id') {
            $item['post_id'] = (int) readElementValue($reader);
        } elseif ($prefix === 'wp' && $name === 'post_name') {
            $item['post_name'] = readElementValue($reader);
        } elseif ($prefix === 'wp' && $name === 'status') {
            $item['status'] = readElementValue($reader);
        } elseif ($prefix === 'wp' && $name === 'post_type') {
            $item['post_type'] = readElementValue($reader);
        } elseif ($prefix === 'wp' && $name === 'post_date') {
            $item['post_date'] = readElementValue($reader);
        } elseif ($prefix === 'wp' && $name === 'postmeta') {
            $meta = readPostmeta($reader);
            if ($meta['key'] !== '') {
                $item['meta'][$meta['key']] = $meta['value'];
            }
        } elseif ($prefix === 'wp' && $name === 'section') {
            $item['sections'][] = readSection($reader);
        }
    }

    return $item;
}

function parseDuration(?string $raw): ?int
{
    if ($raw === null || trim($raw) === '') {
        return null;
    }

    $raw = trim($raw);
    if (preg_match('/(\d+)\s*(hour|hours|hr|hrs)/i', $raw, $m)) {
        return (int) $m[1] * 3600;
    }
    if (preg_match('/(\d+)\s*(minute|minutes|min|mins)/i', $raw, $m)) {
        return (int) $m[1] * 60;
    }
    if (preg_match('/(\d+)\s*(second|seconds|sec|secs)/i', $raw, $m)) {
        return (int) $m[1];
    }
    if (preg_match('/(\d+)/', $raw, $m)) {
        return (int) $m[1] * 60;
    }

    return null;
}

function extractVideoUrl(?string $content): ?string
{
    if (!$content) {
        return null;
    }

    if (preg_match('/src=["\']([^"\']+)["\']/', $content, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }
    if (preg_match('/https?:\/\/[^\s"\']+/', $content, $m)) {
        return html_entity_decode($m[0], ENT_QUOTES, 'UTF-8');
    }

    return null;
}

function isSupportedVideoUrl(string $url): bool
{
    if (stripos($url, 'youtube.com') !== false || stripos($url, 'youtu.be') !== false) {
        return true;
    }
    if (preg_match('/\.mp4(\?|$)/i', $url)) {
        return true;
    }
    return false;
}

function extractVdoId(?string $content): ?string
{
    if (!$content) {
        return null;
    }
    if (preg_match('/\[vdo\s+id="([^"]+)"\]/i', $content, $m)) {
        return $m[1];
    }
    return null;
}

function buildLessonText(string $content, string $link, ?string $vdoId, ?string $videoUrl): string
{
    $parts = [];
    if ($link !== '') {
        $parts[] = "Legacy content: [$link]($link)";
    }
    if ($videoUrl !== null && $videoUrl !== '') {
        $parts[] = "Legacy video URL: $videoUrl";
    } elseif ($vdoId !== null && $vdoId !== '') {
        $parts[] = "Legacy video id: $vdoId";
    }

    $plain = trim(strip_tags($content));
    if ($plain !== '') {
        $parts[] = $plain;
    }

    return implode("\n\n", $parts);
}

function slugify(string $value, int $postId): string
{
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    $value = trim($value ?? '', '-');
    if ($value === '') {
        return 'lp-' . $postId;
    }
    return $value;
}

$opts = getopt('', [
    'file:',
    'instructor-id:',
    'instructor-email:',
    'map-author-email',
    'skip-lessons',
    'skip-quizzes',
    'vdo-url-template:',
    'apply',
    'dry-run',
    'help'
]);

if (isset($opts['help'])) {
    printUsage();
    exit(0);
}

$file = $opts['file'] ?? null;
if ($file === null) {
    foreach ($argv as $index => $arg) {
        if ($index === 0) {
            continue;
        }
        if (strlen($arg) > 0 && $arg[0] !== '-') {
            $file = $arg;
            break;
        }
    }
}

if ($file === null) {
    printUsage();
    exit(1);
}

if (!is_file($file)) {
    echo "File not found: $file\n";
    exit(1);
}

$apply = isset($opts['apply']);
$dryRun = isset($opts['dry-run']) || !$apply;
$skipLessons = isset($opts['skip-lessons']);
$skipQuizzes = isset($opts['skip-quizzes']);
$mapAuthorEmail = isset($opts['map-author-email']);
$vdoTemplate = $opts['vdo-url-template'] ?? null;

$db = Database::getInstance()->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$defaultInstructorId = null;
if (isset($opts['instructor-id'])) {
    $defaultInstructorId = (int) $opts['instructor-id'];
} elseif (isset($opts['instructor-email'])) {
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $opts['instructor-email']]);
    $defaultInstructorId = (int) ($stmt->fetchColumn() ?: 0);
}

if ($defaultInstructorId === null || $defaultInstructorId === 0) {
    $defaultInstructorId = 1;
}

$stmt = $db->prepare("SELECT id FROM users WHERE id = :id");
$stmt->execute([':id' => $defaultInstructorId]);
if (!$stmt->fetchColumn()) {
    echo "Default instructor not found: {$defaultInstructorId}\n";
    exit(1);
}

$courses = [];
$lessons = [];
$quizzes = [];

$reader = new XMLReader();
if (!$reader->open($file)) {
    echo "Failed to open XML file.\n";
    exit(1);
}

while ($reader->read()) {
    if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'item') {
        $item = readItem($reader);
        $postType = $item['post_type'] ?? '';
        $postId = $item['post_id'] ?? 0;
        if (!$postType || !$postId) {
            continue;
        }

        if ($postType === 'lp_course') {
            $courses[$postId] = $item;
        } elseif ($postType === 'lp_lesson') {
            $lessons[$postId] = $item;
        } elseif ($postType === 'lp_quiz') {
            $quizzes[$postId] = $item;
        }
    }
}
$reader->close();

echo "Parsed courses: " . count($courses) . "\n";
echo "Parsed lessons: " . count($lessons) . "\n";
echo "Parsed quizzes: " . count($quizzes) . "\n";

if ($dryRun) {
    echo "Dry run enabled. No database changes will be made.\n";
}

$authorCache = [];
function resolveInstructorId(PDO $db, array &$cache, string $email, int $fallback): int
{
    $email = strtolower(trim($email));
    if ($email === '') {
        return $fallback;
    }
    if (array_key_exists($email, $cache)) {
        return $cache[$email] ?? $fallback;
    }
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $id = $stmt->fetchColumn();
    $cache[$email] = $id ? (int) $id : null;
    return $cache[$email] ?? $fallback;
}

$existingSlugs = [];
$slugRows = $db->query("SELECT slug FROM courses")->fetchAll(PDO::FETCH_COLUMN);
foreach ($slugRows as $slug) {
    $existingSlugs[$slug] = true;
}

$courseStmt = $db->prepare("
    INSERT INTO courses (title, slug, description, price, thumbnail, instructor_id, status, published_at)
    VALUES (:title, :slug, :description, :price, :thumbnail, :instructor_id, :status, :published_at)
");
$moduleStmt = $db->prepare("
    INSERT INTO modules (course_id, title, sort_order)
    VALUES (:course_id, :title, :sort_order)
");
$lessonStmt = $db->prepare("
    INSERT INTO lessons (module_id, instructor_id, title, type, content_url, content_text, duration, sort_order)
    VALUES (:module_id, :instructor_id, :title, :type, :content_url, :content_text, :duration, :sort_order)
");
$quizStmt = $db->prepare("
    INSERT INTO quizzes (lesson_id, status)
    VALUES (:lesson_id, :status)
");

$importedCourses = 0;
$importedModules = 0;
$importedLessons = 0;
$importedQuizLessons = 0;
$skippedCourses = 0;
$missingItems = 0;

if (!$dryRun) {
    $db->beginTransaction();
}

foreach ($courses as $postId => $course) {
    $title = trim($course['title'] ?? '');
    if ($title === '') {
        $title = 'Untitled Course';
    }

    $slugSource = $course['post_name'] ?? $title;
    $slug = slugify($slugSource, (int) $postId);
    if (isset($existingSlugs[$slug])) {
        $skippedCourses++;
        continue;
    }

    $description = '';
    $excerpt = trim($course['excerpt'] ?? '');
    if ($excerpt !== '') {
        $description = strip_tags($excerpt);
    } else {
        $description = trim(strip_tags($course['content'] ?? ''));
    }

    $price = 0.0;
    $meta = $course['meta'] ?? [];
    $priceRaw = $meta['_lp_sale_price'] ?? '';
    if ($priceRaw === '') {
        $priceRaw = $meta['_lp_price'] ?? ($meta['_lp_regular_price'] ?? '');
    }
    if ($priceRaw !== '') {
        $price = (float) $priceRaw;
    }

    $status = 'draft';
    if (($meta['_lp_coming_soon'] ?? '') === 'yes') {
        $status = 'upcoming';
    } elseif (($course['status'] ?? '') === 'publish') {
        $status = 'published';
    }

    $publishedAt = null;
    if ($status === 'published') {
        $publishedAt = $course['post_date'] ?? null;
    }

    $creator = $course['creator'] ?? '';
    $instructorId = $defaultInstructorId;
    if ($mapAuthorEmail && $creator !== '') {
        $instructorId = resolveInstructorId($db, $authorCache, $creator, $defaultInstructorId);
    }

    if ($dryRun) {
        $courseId = 0;
    } else {
        $courseStmt->execute([
            ':title' => $title,
            ':slug' => $slug,
            ':description' => $description,
            ':price' => $price,
            ':thumbnail' => '',
            ':instructor_id' => $instructorId,
            ':status' => $status,
            ':published_at' => $publishedAt
        ]);
        $courseId = (int) $db->lastInsertId();
        $existingSlugs[$slug] = true;
    }

    $importedCourses++;

    $sections = $course['sections'] ?? [];
    usort($sections, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

    foreach ($sections as $section) {
        $moduleTitle = trim($section['name'] ?? '');
        if ($moduleTitle === '') {
            $moduleTitle = 'Untitled Module';
        }
        $moduleOrder = (int) ($section['order'] ?? 0);

        if ($dryRun) {
            $moduleId = 0;
        } else {
            $moduleStmt->execute([
                ':course_id' => $courseId,
                ':title' => $moduleTitle,
                ':sort_order' => $moduleOrder
            ]);
            $moduleId = (int) $db->lastInsertId();
        }

        $importedModules++;

        $items = $section['items'] ?? [];
        usort($items, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        foreach ($items as $item) {
            if ($skipLessons && $item['type'] === 'lp_lesson') {
                continue;
            }
            if ($skipQuizzes && $item['type'] === 'lp_quiz') {
                continue;
            }

            $refId = (int) ($item['id'] ?? 0);
            if ($refId === 0) {
                $missingItems++;
                continue;
            }

            if ($item['type'] === 'lp_lesson') {
                $lesson = $lessons[$refId] ?? null;
                if ($lesson === null) {
                    $missingItems++;
                    continue;
                }

                $lessonTitle = trim($lesson['title'] ?? '');
                if ($lessonTitle === '') {
                    $lessonTitle = 'Untitled Lesson';
                }

                $content = $lesson['content'] ?? '';
                $link = $lesson['link'] ?? '';
                $duration = parseDuration($lesson['meta']['_lp_duration'] ?? null);
                $vdoId = extractVdoId($content);
                $videoUrl = extractVideoUrl($content);
                if ($videoUrl === null && $vdoId !== null && $vdoTemplate !== null) {
                    $videoUrl = str_replace('{id}', $vdoId, $vdoTemplate);
                }

                $type = 'article';
                $contentUrl = null;
                $contentText = buildLessonText($content, $link, $vdoId, $videoUrl);

                if ($videoUrl !== null && isSupportedVideoUrl($videoUrl)) {
                    $type = 'video';
                    $contentUrl = $videoUrl;
                    $contentText = null;
                }

                if (!$dryRun) {
                    $lessonStmt->execute([
                        ':module_id' => $moduleId,
                        ':instructor_id' => $instructorId,
                        ':title' => $lessonTitle,
                        ':type' => $type,
                        ':content_url' => $contentUrl,
                        ':content_text' => $contentText,
                        ':duration' => $duration,
                        ':sort_order' => (int) ($item['order'] ?? 0)
                    ]);
                }

                $importedLessons++;
            } elseif ($item['type'] === 'lp_quiz') {
                $quiz = $quizzes[$refId] ?? null;
                if ($quiz === null) {
                    $missingItems++;
                    continue;
                }

                $quizTitle = trim($quiz['title'] ?? '');
                if ($quizTitle === '') {
                    $quizTitle = 'Untitled Quiz';
                }

                if (!$dryRun) {
                    $lessonStmt->execute([
                        ':module_id' => $moduleId,
                        ':instructor_id' => $instructorId,
                        ':title' => $quizTitle,
                        ':type' => 'quiz',
                        ':content_url' => null,
                        ':content_text' => null,
                        ':duration' => null,
                        ':sort_order' => (int) ($item['order'] ?? 0)
                    ]);
                    $lessonId = (int) $db->lastInsertId();
                    $quizStmt->execute([
                        ':lesson_id' => $lessonId,
                        ':status' => $status === 'published' ? 'published' : 'draft'
                    ]);
                }

                $importedQuizLessons++;
            }
        }
    }
}

if (!$dryRun) {
    $db->commit();
}

echo "Imported courses: $importedCourses\n";
echo "Imported modules: $importedModules\n";
echo "Imported lessons: $importedLessons\n";
echo "Imported quiz lessons: $importedQuizLessons\n";
echo "Skipped courses (slug exists): $skippedCourses\n";
echo "Missing referenced items: $missingItems\n";

if ($dryRun) {
    echo "Dry run complete. Re-run with --apply to import.\n";
}
