<?php ob_start(); ?>

<div class="max-w-7xl mx-auto" x-data="{ createModalOpen: false, addToCourseModalOpen: false, selectedLessonId: null }">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Lesson Library</h1>
            <p class="text-zinc-400 mt-2">Manage all your video and article lessons in one place.</p>
        </div>
        <div class="flex gap-3">
            <button @click="createModalOpen = true" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-blue-500/20 transition flex items-center">
                <i class="fa-solid fa-plus mr-2"></i> Create Lesson
            </button>
        </div>
    </div>

    <!-- Filters & Search -->
    <form method="GET" class="bg-surface border border-zinc-700/50 rounded-2xl p-4 mb-6 flex flex-col md:flex-row gap-4">
        <div class="flex-1 relative">
            <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500"></i>
            <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Search lessons..." 
                   class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl pl-10 pr-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
        </div>
        <div class="flex gap-4">
            <select name="filter" class="bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                <option value="all" <?= ($filters['type'] ?? 'all') === 'all' ? 'selected' : '' ?>>All Types</option>
                <option value="video" <?= ($filters['type'] ?? '') === 'video' ? 'selected' : '' ?>>Videos</option>
                <option value="article" <?= ($filters['type'] ?? '') === 'article' ? 'selected' : '' ?>>Articles</option>
            </select>
            <select name="sort" class="bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                <option value="date_desc" <?= ($filters['sort'] ?? 'date_desc') === 'date_desc' ? 'selected' : '' ?>>Newest First</option>
                <option value="date_asc" <?= ($filters['sort'] ?? '') === 'date_asc' ? 'selected' : '' ?>>Oldest First</option>
                <option value="name_asc" <?= ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                <option value="name_desc" <?= ($filters['sort'] ?? '') === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
            </select>
            <button type="submit" class="bg-zinc-800 hover:bg-zinc-700 text-white px-4 py-2 rounded-xl transition">
                Filter
            </button>
        </div>
    </form>

    <!-- Lesson Table -->
    <div class="bg-surface border border-zinc-700/50 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-zinc-800/50 border-b border-zinc-700/50">
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Lesson Title</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Date Created</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Usage</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800">
                    <?php 
                    // Filter out quizzes for this view since user wants them separate
                    $filteredLessons = array_filter($lessons ?? [], fn($l) => $l['type'] !== 'quiz');
                    ?>
                    
                    <?php foreach ($filteredLessons as $lesson): ?>
                        <tr class="group hover:bg-zinc-800/30 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white
                                        <?= match($lesson['type']) {
                                            'video' => 'bg-blue-500/10 text-blue-400',
                                            'article' => 'bg-emerald-500/10 text-emerald-400',
                                            default => 'bg-zinc-700 text-zinc-400'
                                        } ?>">
                                        <?php if ($lesson['type'] === 'video'): ?>
                                            <i class="fa-solid fa-play text-xs"></i>
                                        <?php elseif ($lesson['type'] === 'article'): ?>
                                            <i class="fa-solid fa-file-lines text-xs"></i>
                                        <?php else: ?>
                                            <i class="fa-solid fa-file text-xs"></i>
                                        <?php endif; ?>
                                    </div>
                                    <span class="font-medium text-white"><?= htmlspecialchars($lesson['title']) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="capitalize text-sm text-zinc-400"><?= $lesson['type'] ?></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-400">
                                <?= date('M j, Y', strtotime($lesson['created_at'] ?? 'now')) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-400">
                                <?= $lesson['type'] === 'video' ? gmdate("i:s", $lesson['duration']) : '-' ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if (!empty($lesson['course_id'])): ?>
                                    <div class="relative group/tooltip inline-block">
                                        <span class="text-green-400 flex items-center cursor-help">
                                            <i class="fa-solid fa-link mr-1.5 text-xs"></i> Used
                                        </span>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-black border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl opacity-0 group-hover/tooltip:opacity-100 transition pointer-events-none z-10 text-center">
                                            <?= htmlspecialchars($lesson['course_title'] ?? 'Unknown Course') ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-zinc-600">Unused</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <?php if (empty($lesson['course_id'])): ?>
                                        <button @click="selectedLessonId = <?= $lesson['id'] ?>; addToCourseModalOpen = true" class="text-zinc-400 hover:text-blue-400 p-2 rounded hover:bg-blue-500/10 transition" title="Add to Course">
                                            <i class="fa-solid fa-plus-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="/instructor/lesson/<?= $lesson['id'] ?>/edit?source=library" class="text-zinc-400 hover:text-white p-2 rounded hover:bg-zinc-700 transition" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form action="/instructor/lesson/delete/<?= $lesson['id'] ?>" method="POST" onsubmit="return confirm('Delete this lesson permanently?');" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <input type="hidden" name="source" value="library">
                                        <button type="submit" class="text-zinc-400 hover:text-red-400 p-2 rounded hover:bg-red-500/10 transition" title="Delete">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($filteredLessons)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-zinc-500">
                                <div class="w-12 h-12 bg-zinc-800 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fa-solid fa-layer-group text-zinc-600"></i>
                                </div>
                                <p>No lessons found matching your filters.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Modal -->
    <div x-show="createModalOpen" style="display: none;" class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4" x-transition.opacity>
        <div class="bg-surface border border-zinc-700 rounded-2xl w-full max-w-lg shadow-2xl" @click.away="createModalOpen = false">
            <div class="p-6 border-b border-zinc-700/50 flex justify-between items-center">
                <h3 class="text-xl font-bold text-white">Create New Lesson</h3>
                <button @click="createModalOpen = false" class="text-zinc-500 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="/instructor/lesson/create-library" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-2">Title</label>
                    <input type="text" name="title" required class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-2">Type</label>
                    <select name="type" class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                        <option value="video">Video Lesson</option>
                        <option value="article">Article / Text</option>
                        <!-- Quiz Removed -->
                    </select>
                </div>
                <div class="pt-4 flex justify-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-8 rounded-xl transition">Create & Edit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add to Course Modal -->
    <div x-show="addToCourseModalOpen" style="display: none;" class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4" x-transition.opacity>
        <div class="bg-surface border border-zinc-700 rounded-2xl w-full max-w-lg shadow-2xl" @click.away="addToCourseModalOpen = false">
            <div class="p-6 border-b border-zinc-700/50 flex justify-between items-center">
                <h3 class="text-xl font-bold text-white">Add Lesson to Course</h3>
                <button @click="addToCourseModalOpen = false" class="text-zinc-500 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="/instructor/lesson/add-to-course" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="lesson_id" :value="selectedLessonId">
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-2">Select Course Module</label>
                    <select name="module_id" required class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                        <option value="" disabled selected>Choose a destination...</option>
                        <?php foreach ($courses as $course): ?>
                            <optgroup label="<?= htmlspecialchars($course['title']) ?>">
                                <?php if (empty($course['modules'])): ?>
                                    <option disabled>No modules</option>
                                <?php else: ?>
                                    <?php foreach ($course['modules'] as $module): ?>
                                        <option value="<?= $module['id'] ?>"><?= htmlspecialchars($module['title']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pt-4 flex justify-end">
                    <button type="submit" class="bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-8 rounded-xl transition">Add to Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../../layouts/app.php'; 
?>