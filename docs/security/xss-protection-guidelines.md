# XSS Protection - Developer Guidelines

## Overview

This document provides guidelines for preventing Cross-Site Scripting (XSS) vulnerabilities in view templates.

## The e() Helper Function

We provide a global `e()` helper function for HTML output escaping:

```php
<?php
// Located in: app/Core/helpers.php
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
```

## When to Use e()

### ✅ ALWAYS Escape User-Generated Content

```php
<!-- User names -->
<h1><?= e($user['name']) ?></h1>

<!-- Course/Lesson titles -->
<h2><?= e($course['title']) ?></h2>

<!-- Descriptions -->
<p><?= e($course['description']) ?></p>

<!-- Email addresses -->
<span><?= e($user['email']) ?></span>

<!-- Form input values -->
<input value="<?= e($data['field']) ?>">

<!-- URL parameters (in href attributes) -->
<a href="<?= e($url) ?>"></a>
```

### ✅ Data from Database = Escape It

**Rule of Thumb**: If the data comes from the database and was entered by a user, escape it.

```php
<!-- Student/Instructor names -->
<?= e($student['name']) ?>

<!-- Quiz questions/answers entered by instructors -->
<?= e($question['text']) ?>

<!-- User-submitted notes or comments -->
<?= e($note['content']) ?>
```

### ❌ DON'T Escape These

#### 1. Already-Escaped Content

```php
<!-- Don't double-escape -->
<?= htmlspecialchars($error) ?>  <!-- Already safe -->
<?= e($already_escaped) ?>  <!-- DON'T escape again -->
```

#### 2. System-Generated HTML

```php
<!-- Icons, badges, system messages -->
<i class="fa-solid fa-check"></i>  <!-- Safe HTML -->

<!-- Intentional HTML content -->
<?= $lesson['html_content'] ?>  <!-- Use HTML Purifier instead -->
```

#### 3. Numbers and IDs

```php
<!-- Numeric values -->
<?= $course['price'] ?>  <!-- Safe (number) -->
<?= $user['id'] ?>  <!-- Safe (numeric ID) -->
<?= $stats['count'] ?>  <!-- Safe (number) -->
```

#### 4. Trusted Administrator Content

```php
<!-- System messages, static content -->
<p>Welcome to ScratchLMS!</p>  <!-- Safe static text -->
```

---

## Common Patterns

### Pattern 1: Displaying User Profiles

```php
<div class="profile">
    <h2><?= e($user['name']) ?></h2>
    <p><?= e($user['bio']) ?></p>
    <span><?= e($user['email']) ?></span>
</div>
```

### Pattern 2: Course Listings

```php
<?php foreach ($courses as $course): ?>
    <div class="course-card">
        <h3><?= e($course[' title']) ?></h3>
        <p><?= e($course['description']) ?></p>
        <span>By <?= e($course['instructor_name']) ?></span>
    </div>
<?php endforeach; ?>
```

### Pattern 3: Form Values

```php
<input 
    type="text" 
    name="title" 
    value="<?= e($data['title'] ?? '') ?>"
>

<textarea name="description"><?= e($data['description'] ?? '') ?></textarea>
```

### Pattern 4: JavaScript Data

```php
<script>
    // NEVER do this:
    const userName = "<?= $user['name'] ?>";  // UNSAFE!
    
    // DO this instead:
    const userName = <?= json_encode($user['name']) ?>;  // Safe
</script>
```

---

## Special Cases

### HTML Content (Lesson Bodies, Rich Text)

For intentional HTML content (e.g., lesson content that includes formatting):

```php
<!-- Option 1: Strip all HTML tags -->
<?= strip_tags($lesson['content']) ?>

<!-- Option 2: Use HTML Purifier (recommended) -->
<?php
// TODO: Implement HTML Purifier library
// $cleanHtml = $purifier->purify($lesson['content']);
?>
```

### URLs in href Attributes

```php
<!-- Basic URLs -->
<a href="<?= e($url) ?>">Link</a>

<!-- For user-generated URLs, validate first -->
<?php
$safeUrl = filter_var($user_url, FILTER_VALIDATE_URL) ? e($user_url) : '/';
?>
<a href="<?= $safeUrl ?>">Link</a>
```

### Attributes with JSON Data

```php
<!-- Use json_encode for data attributes -->
<div data-config='<?= json_encode($config) ?>'></div>

<!-- NOT this -->
<div data-config="<?= e(json_encode($config)) ?>"></div>  <!-- Double encoding! -->
```

---

## Audit Checklist

When reviewing a view file, check:

- [ ] All `<?= $variable ?>` instances are wrapped in `e()` or `htmlspecialchars()`
- [ ] Form input `value` attributes are escaped
- [ ] User names, emails, and descriptions are escaped
- [ ] Content from database (titles, descriptions, etc.) is escaped
- [ ] No raw user input in `<script>` tags (use `json_encode` instead)

---

## Current Status

### ✅ Already Protected Views

- `auth/login.php` - Uses `htmlspecialchars()` for errors
- `auth/register.php` - Uses `htmlspecialchars()` for errors  
- `profile.php` - Uses `htmlspecialchars()` for user data
- `courses/show.php` - Uses `htmlspecialchars()` for course/instructor data
- `dashboard.php` - Uses `htmlspecialchars()` for user/course data

### ⚠️ Views Requiring Audit

See `xss-audit-stages.md` for staged audit plan.

---

## Tools & Resources

### Quick Find-and-Replace Patterns

**Find dangerous patterns:**

```bash
# Find unescaped output (may have false positives)
grep -r "<?=\s*\$" resources/views/

# Find specific variables
grep -r "<?=\s*\$user\[" resources/views/
grep -r "<?=\s*\$course\[" resources/views/
```

**Safe patterns to look for:**

- `<?= e($var) ?>`
- `<?= htmlspecialchars($var) ?>`
- `<?= json_encode($var) ?>`

---

## When in Doubt

**If you're unsure whether to escape:**

1. Is it user-generated? **Escape it.**
2. Does it come from the database? **Probably escape it.**
3. Is it a number or ID? **No need to escape.**
4. Is it system-generated HTML? **Don't escape** (but be careful).

**Better safe than sorry**: When in doubt, use `e()`.

---

## Examples from Codebase

### ✅ Good (Already Fixed)

```php
<!-- From profile.php -->
<h2><?= htmlspecialchars($user['name']) ?></h2>
<p><?= htmlspecialchars($user['email']) ?></p>

<!-- From courses/show.php -->
<h1><?= htmlspecialchars($course['title']) ?></h1>
<p><?= htmlspecialchars($course['description']) ?></p>
```

### ❌ Bad (Needs Fixing)

```php
<!-- Hypothetical unsafe code -->
<h1><?= $user_input ?></h1>  <!-- UNSAFE! -->
<script>alert('<?= $user['name'] ?>')</script>  <!-- VERY UNSAFE! -->
```

---

## Summary

- **Always** escape user-generated content with `e()` or `htmlspecialchars()`
- **Never** put user input directly in `<script>` tags
- **Use** `json_encode()` for JavaScript data
- **Don't** double-escape already-safe content
- **When in doubt**, escape it!

---

**Last Updated**: 2026-01-18  
**Maintained by**: Gemini (Security Team)
