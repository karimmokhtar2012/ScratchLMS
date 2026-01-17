<?php ob_start(); ?>

<div class="max-w-7xl mx-auto space-y-8">
    
    <!-- Top Header: Course Overview & Quick Actions -->
    <div class="bg-surface rounded-2xl border border-zinc-700/50 p-6 shadow-xl flex flex-col md:flex-row items-start md:items-center gap-6">
        <!-- Thumbnail -->
        <div class="w-full md:w-48 aspect-video bg-zinc-800 rounded-xl overflow-hidden shrink-0 relative border border-zinc-700">
            <img src="<?= $course['thumbnail'] ?: '/assets/images/course.png' ?>" alt="Course Thumbnail" class="w-full h-full object-cover">
        </div>
        
        <!-- Info -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 mb-1">
                <h1 class="text-2xl font-bold text-white truncate" title="<?= htmlspecialchars($course['title']) ?>">
                    <?= htmlspecialchars($course['title']) ?>
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider
                    <?= match($course['status'] ?? 'draft') {
                        'published' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
                        'upcoming' => 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
                        default => 'bg-zinc-700/50 text-zinc-400 border border-zinc-700'
                    } ?>">
                    <?= ucfirst($course['status'] ?? 'draft') ?>
                </span>
            </div>
            
            <p class="text-zinc-400 text-sm mb-4 line-clamp-2 max-w-2xl">
                <?= htmlspecialchars($course['description']) ?>
            </p>
            
            <div class="flex flex-wrap items-center gap-3">
                <a href="/instructor/course/<?= $course['id'] ?>/settings" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-zinc-800 hover:bg-zinc-700 rounded-lg border border-zinc-700 transition">
                    <i class="fa-solid fa-gear mr-2"></i> Settings
                </a>
                <a href="/instructor/course/<?= $course['id'] ?>/students" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-zinc-800 hover:bg-zinc-700 rounded-lg border border-zinc-700 transition">
                    <i class="fa-solid fa-users mr-2"></i> Students
                </a>
                <a href="/course/<?= $course['id'] ?>" target="_blank" class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-400 hover:text-white hover:bg-blue-500/10 rounded-lg transition">
                    <i class="fa-solid fa-external-link-alt mr-2"></i> Public Page
                </a>
            </div>
        </div>

        <!-- Price (Right aligned on desktop) -->
        <div class="hidden md:block text-right">
            <div class="text-2xl font-bold text-white mb-1">
                <?= $course['price'] > 0 ? '$' . number_format($course['price'], 2) : 'Free' ?>
            </div>
            <div class="text-xs text-zinc-500 uppercase tracking-wider">Price</div>
        </div>
    </div>

    <!-- Chunked Uploader Script -->
    <script src="/assets/js/uploader.min.js?v=<?= filemtime(BASE_PATH . 'public/assets/js/uploader.min.js') ?>"></script>

    <!-- Curriculum Builder -->
    <div>
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-white flex items-center">
                    Curriculum Builder
                </h2>
                <p class="text-zinc-400 text-sm mt-1">Drag and drop to reorder. Toggle edit mode to manage content.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <!-- Action Buttons -->
                <button id="btn-edit-course" class="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-indigo-500/20 transition flex items-center">
                    <i class="fa-solid fa-pen-to-square mr-2"></i> Toggle Edit Mode
                </button>
                
                <button id="btn-save-changes" disabled class="hidden bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-emerald-500/20 transition flex items-center">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Save Changes
                </button>
                
                <button id="btn-cancel-edit" class="hidden text-zinc-400 hover:text-white px-4 py-2.5 transition font-medium">
                    Cancel
                </button>

                <!-- Add Module (Only in Edit Mode) -->
                <div x-data="{ open: false }" class="relative edit-mode-only hidden">
                    <button @click="open = !open" class="flex items-center space-x-2 bg-zinc-800 hover:bg-zinc-700 text-white px-5 py-2.5 rounded-xl font-bold border border-zinc-700 transition">
                        <i class="fa-solid fa-plus"></i>
                        <span>Add Module</span>
                    </button>
                    
                    <!-- Add Module Dropdown Form -->
                    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-80 bg-zinc-900 rounded-xl shadow-2xl border border-zinc-700 z-20 p-5" style="display: none;">
                        <h3 class="text-sm font-bold text-white mb-3">New Module</h3>
                        <form action="/instructor/module/add" method="POST" class="protected-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Title</label>
                                <input type="text" name="title" required class="w-full bg-zinc-950 border border-zinc-800 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" placeholder="e.g., Introduction to PHP">
                            </div>
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold py-2.5 rounded-lg transition">Create Module</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modules List -->
        <div id="modules-list" class="space-y-6">
            <?php foreach ($modules as $module): ?>
                <div class="bg-surface rounded-xl border border-zinc-700/50 overflow-hidden module-item transition-all duration-200" data-id="<?= $module['id'] ?>" draggable="false" x-data="{ openLessonForm: false, lessonType: 'video', module_id: <?= $module['id'] ?> }">
                    <!-- Module Header -->
                    <div class="bg-zinc-800/40 px-6 py-4 flex items-center justify-between border-b border-zinc-700/50 cursor-default group" x-data="{ editingTitle: false }">
                        <div class="flex items-center space-x-4 w-full">
                            <!-- Drag Handle -->
                            <div class="w-6 flex items-center justify-center edit-mode-only hidden cursor-move text-zinc-600 hover:text-zinc-400">
                                <i class="fa-solid fa-grip-vertical"></i>
                            </div>
                            
                            <!-- View Mode Title -->
                            <h3 x-show="!editingTitle" class="font-bold text-white text-lg flex items-center gap-3">
                                <?= htmlspecialchars($module['title']) ?>
                                <span class="text-xs font-normal text-zinc-500 bg-zinc-800 px-2 py-0.5 rounded-full border border-zinc-700/50">
                                    <?= count($module['lessons']) ?> lessons
                                </span>
                            </h3>
                            
                            <!-- Edit Mode Title Form -->
                            <form x-show="editingTitle" action="/instructor/module/update/<?= $module['id'] ?>" method="POST" class="flex items-center space-x-2 w-full max-w-md protected-form" @click.away="editingTitle = false">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="text" name="title" value="<?= htmlspecialchars($module['title']) ?>" class="bg-zinc-950 border border-zinc-700 text-white rounded px-3 py-1.5 text-lg font-bold w-full focus:outline-none focus:border-indigo-500">
                                <button type="submit" class="text-emerald-500 hover:text-emerald-400 p-1"><i class="fa-solid fa-check"></i></button>
                                <button type="button" @click="editingTitle = false" class="text-red-500 hover:text-red-400 p-1"><i class="fa-solid fa-xmark"></i></button>
                            </form>

                            <!-- Inline Actions -->
                            <div class="flex items-center space-x-1 edit-mode-only hidden" x-show="!editingTitle">
                                <button @click="editingTitle = true; setTimeout(() => $el.closest('.flex').previousElementSibling.querySelector('input').focus(), 50)" class="text-zinc-500 hover:text-indigo-400 p-2 rounded-lg transition" title="Rename Module">
                                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                                </button>
                                <form action="/instructor/module/delete/<?= $module['id'] ?>" method="POST" class="protected-form" onsubmit="return confirm('Delete this module and all its lessons?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <button type="submit" class="text-zinc-500 hover:text-red-400 p-2 rounded-lg transition" title="Delete Module">
                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Right Actions -->
                        <div class="flex items-center space-x-4">
                            <!-- Import Lesson -->
                            <div class="relative edit-mode-only hidden" x-data="{ showImport: false }">
                                <button @click="showImport = !showImport" class="text-zinc-400 hover:text-white text-sm font-medium flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-zinc-800 transition">
                                    <i class="fa-solid fa-file-import"></i>
                                    <span>Import</span>
                                </button>
                                
                                <div x-show="showImport" @click.away="showImport = false" class="absolute right-0 top-full mt-2 w-72 bg-zinc-900 border border-zinc-700 rounded-xl shadow-xl z-30 p-2 max-h-60 overflow-y-auto custom-scrollbar" style="display: none;">
                                    <?php if (empty($libraryLessons)): ?>
                                        <div class="p-4 text-center text-zinc-500 text-xs">
                                            <i class="fa-solid fa-box-open text-lg mb-1 block opacity-50"></i>
                                            Library is empty
                                        </div>
                                    <?php else: ?>
                                        <div class="text-[10px] uppercase font-bold text-zinc-600 px-2 py-1.5 mb-1 tracking-wider">From Library</div>
                                        <?php foreach ($libraryLessons as $libLesson): ?>
                                            <form action="/instructor/lesson/import" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                                <input type="hidden" name="module_id" value="<?= $module['id'] ?>">
                                                <input type="hidden" name="lesson_id" value="<?= $libLesson['id'] ?>">
                                                <button type="submit" class="w-full text-left px-3 py-2.5 text-xs text-zinc-300 hover:bg-zinc-800 rounded-lg transition truncate flex items-center group">
                                                    <?php if ($libLesson['type'] == 'video'): ?>
                                                        <i class="fa-solid fa-play mr-2.5 text-blue-500 group-hover:text-blue-400"></i>
                                                    <?php elseif ($libLesson['type'] == 'quiz'): ?>
                                                        <i class="fa-solid fa-list-check mr-2.5 text-purple-500 group-hover:text-purple-400"></i>
                                                    <?php else: ?>
                                                        <i class="fa-solid fa-file-lines mr-2.5 text-emerald-500 group-hover:text-emerald-400"></i>
                                                    <?php endif; ?>
                                                    <span class="truncate"><?= htmlspecialchars($libLesson['title']) ?></span>
                                                </button>
                                            </form>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Add Lesson Toggle -->
                            <div class="flex items-center space-x-2 edit-mode-only hidden">
                                <button @click="lessonType = 'quiz'; openLessonForm = !openLessonForm" class="text-sm font-bold text-purple-400 hover:text-purple-300 flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-purple-500/10 transition">
                                    <i class="fa-solid fa-list-check"></i>
                                    <span>Add Quiz</span>
                                </button>
                                <button @click="lessonType = 'video'; openLessonForm = !openLessonForm" class="text-sm font-bold text-indigo-400 hover:text-indigo-300 flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-indigo-500/10 transition">
                                    <i class="fa-solid fa-plus"></i>
                                    <span>Add Lesson</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Add Lesson Form (Toggleable) -->
                    <div x-show="openLessonForm" class="bg-zinc-950 p-6 border-b border-zinc-800" x-transition>
                        <form action="/instructor/lesson/add" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6 protected-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                            <input type="hidden" name="module_id" :value="module_id">
                            <input type="hidden" name="type" x-model="lessonType">
                            
                            <div class="flex items-start justify-between">
                                <h4 class="text-sm font-bold text-white uppercase tracking-wider" x-text="lessonType === 'quiz' ? 'New Quiz' : 'New Lesson'">New Lesson</h4>
                                <button type="button" @click="openLessonForm = false" class="text-zinc-500 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                                <!-- Basic Info -->
                                <div class="md:col-span-8 space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1.5">Title</label>
                                        <input type="text" name="title" required 
                                            class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition" 
                                            placeholder="e.g., Understanding the MVC Pattern">
                                    </div>
                                    
                                    <!-- Dynamic Inputs -->
                                    <div class="bg-zinc-900/50 rounded-xl border border-zinc-800 p-4">
                                        <!-- VIDEO -->
                                        <div x-show="lessonType === 'video'">
                                            <label class="block text-xs font-bold text-zinc-500 uppercase mb-2">Video File</label>
                                            <div class="border-2 border-dashed border-zinc-700 rounded-lg p-6 text-center hover:border-indigo-500/50 transition bg-zinc-900">
                                                <input type="file" :id="'video_file_' + module_id" class="hidden" @change="$refs.fileName.innerText = $event.target.files[0].name">
                                                <label :for="'video_file_' + module_id" class="cursor-pointer block">
                                                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-zinc-600 mb-3"></i>
                                                    <div class="text-sm text-zinc-300 font-medium" x-ref="fileName">Click to upload video</div>
                                                    <div class="text-xs text-zinc-500 mt-1">MP4, WebM (Max 500MB)</div>
                                                </label>
                                                <!-- Chunked uploader logic would hook here -->
                                                <input type="hidden" name="content_url" :id="'content_url_' + module_id">
                                            </div>
                                            <!-- Progress Bar Placeholder -->
                                            <div :id="'progress_container_' + module_id" class="mt-3 w-full bg-zinc-800 rounded-full h-2 hidden">
                                                <div :id="'progress_bar_' + module_id" class="bg-indigo-500 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                            </div>
                                        </div>

                                        <!-- ARTICLE -->
                                        <div x-show="lessonType === 'article'">
                                            <label class="block text-xs font-bold text-zinc-500 uppercase mb-2">Content</label>
                                            <textarea name="content_text" rows="4" class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-500"></textarea>
                                        </div>

                                        <!-- QUIZ -->
                                        <div x-show="lessonType === 'quiz'" class="text-center py-4">
                                            <div class="w-12 h-12 bg-purple-500/10 text-purple-400 rounded-full flex items-center justify-center mx-auto mb-3">
                                                <i class="fa-solid fa-lightbulb text-xl"></i>
                                            </div>
                                            <p class="text-sm text-zinc-300 font-medium">Quiz Builder</p>
                                            <p class="text-xs text-zinc-500 mt-1">Create the quiz unit first, then add questions.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sidebar Settings -->
                                <div class="md:col-span-4 space-y-4">
                                    <div x-show="lessonType !== 'quiz'">
                                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1.5">Type</label>
                                        <div class="space-y-2">
                                            <label class="flex items-center p-3 rounded-lg border border-zinc-800 bg-zinc-900 cursor-pointer hover:border-zinc-700 transition" :class="{'border-indigo-500 ring-1 ring-indigo-500 bg-indigo-500/5': lessonType === 'video'}">
                                                <input type="radio" name="type" value="video" x-model="lessonType" class="hidden">
                                                <div class="w-8 h-8 rounded-full bg-blue-500/10 text-blue-400 flex items-center justify-center mr-3"><i class="fa-solid fa-play text-xs"></i></div>
                                                <span class="text-sm font-medium text-white">Video</span>
                                            </label>
                                            <label class="flex items-center p-3 rounded-lg border border-zinc-800 bg-zinc-900 cursor-pointer hover:border-zinc-700 transition" :class="{'border-emerald-500 ring-1 ring-emerald-500 bg-emerald-500/5': lessonType === 'article'}">
                                                <input type="radio" name="type" value="article" x-model="lessonType" class="hidden">
                                                <div class="w-8 h-8 rounded-full bg-emerald-500/10 text-emerald-400 flex items-center justify-center mr-3"><i class="fa-solid fa-file-lines text-xs"></i></div>
                                                <span class="text-sm font-medium text-white">Article</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div x-show="lessonType !== 'quiz'">
                                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1.5">Duration (Min)</label>
                                        <input type="number" name="duration" min="0" class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-500">
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-zinc-800 flex justify-end">
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2.5 px-6 rounded-lg shadow-lg shadow-indigo-500/20 transition" x-text="lessonType === 'quiz' ? 'Create Quiz' : 'Create Lesson'">
                                    Create Lesson
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Lessons List -->
                    <div class="lessons-list bg-zinc-900/20" data-module-id="<?= $module['id'] ?>">
                        <?php if (empty($module['lessons'])): ?>
                            <div class="p-8 text-center text-sm text-zinc-600 border-t border-zinc-800/50">
                                No lessons in this module yet. Click "Add Lesson" to start.
                            </div>
                        <?php else: ?>
                            <?php foreach ($module['lessons'] as $lesson): ?>
                                <div class="px-6 py-3 flex items-center justify-between border-t border-zinc-800/50 group hover:bg-zinc-800/40 transition lesson-item" draggable="false" data-id="<?= $lesson['id'] ?>">
                                    <div class="flex items-center space-x-4">
                                        <!-- Drag Handle -->
                                        <div class="w-6 flex items-center justify-center edit-mode-only hidden cursor-move text-zinc-600 hover:text-zinc-400">
                                            <i class="fa-solid fa-grip-lines"></i>
                                        </div>

                                        <!-- Icon -->
                                        <?php if ($lesson['type'] === 'video'): ?>
                                            <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-400 border border-blue-500/20">
                                                <i class="fa-solid fa-play text-xs"></i>
                                            </div>
                                        <?php elseif ($lesson['type'] === 'article'): ?>
                                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-400 border border-emerald-500/20">
                                                <i class="fa-solid fa-file-lines text-xs"></i>
                                            </div>
                                        <?php else: ?>
                                             <div class="w-8 h-8 rounded-lg bg-purple-500/10 flex items-center justify-center text-purple-400 border border-purple-500/20">
                                                <i class="fa-solid fa-list-check text-xs"></i>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Title -->
                                        <span class="text-zinc-300 text-sm font-medium group-hover:text-white transition"><?= htmlspecialchars($lesson['title']) ?></span>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center space-x-2">
                                        <?php if ($lesson['type'] === 'quiz'): ?>
                                            <a href="/instructor/lesson/<?= $lesson['id'] ?>/quiz" class="text-xs bg-purple-600 text-white px-3 py-1.5 rounded-lg hover:bg-purple-500 transition shadow-lg shadow-purple-900/20 edit-mode-only hidden font-bold">
                                                Quiz Builder
                                            </a>
                                        <?php else: ?>
                                            <a href="/instructor/lesson/<?= $lesson['id'] ?>/edit" class="text-zinc-500 hover:text-white p-2 transition edit-mode-only hidden" title="Edit Content">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                        <?php endif; ?>
                                         
                                         <form action="/instructor/lesson/delete/<?= $lesson['id'] ?>" method="POST" class="protected-form edit-mode-only hidden" onsubmit="return confirm('Delete this lesson?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <button type="submit" class="text-zinc-500 hover:text-red-400 p-2 transition opacity-0 group-hover:opacity-100" title="Delete">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                         </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($modules)): ?>
                <div class="text-center py-16 bg-zinc-800/20 rounded-xl border-2 border-dashed border-zinc-700/50">
                    <div class="w-16 h-16 bg-zinc-800 rounded-full flex items-center justify-center mx-auto mb-4 text-zinc-600">
                        <i class="fa-solid fa-layer-group text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Start Building Your Course</h3>
                    <p class="text-zinc-500 max-w-sm mx-auto mb-6">Create modules to organize your lessons, quizzes, and assignments.</p>
                    <button @click="document.querySelector('#btn-edit-course').click(); setTimeout(() => document.querySelector('[x-data]').__x.$data.open = true, 100)" class="text-indigo-400 hover:text-white font-bold text-sm">
                        Enter Edit Mode to Add Module &rarr;
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../layouts/app.php'; 
?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modulesList = document.getElementById('modules-list');
    const btnEdit = document.getElementById('btn-edit-course');
    const btnSave = document.getElementById('btn-save-changes');
    const btnCancel = document.getElementById('btn-cancel-edit');
    
    let isEditing = false;
    let hasUnsavedChanges = false;
    let draggedItem = null;
    let draggedType = null; // 'module' or 'lesson'

    // --- Mode Management ---
    function enableEditMode() {
        isEditing = true;
        btnEdit.classList.add('hidden');
        btnSave.classList.remove('hidden');
        btnCancel.classList.remove('hidden');

        document.querySelectorAll('.edit-mode-only').forEach(el => el.classList.remove('hidden'));
        document.querySelectorAll('.module-item').forEach(el => el.setAttribute('draggable', 'true'));
        document.querySelectorAll('.lesson-item').forEach(el => el.setAttribute('draggable', 'true'));
    }

    function disableEditMode() {
        if (hasUnsavedChanges) {
             if (!confirm('You have unsaved changes. Are you sure you want to exit edit mode? Your changes will be lost.')) return;
             // If confirmed, reload page to reset state
             window.location.reload();
             return;
        }

        isEditing = false;
        btnEdit.classList.remove('hidden');
        btnSave.classList.add('hidden');
        btnCancel.classList.add('hidden');

        document.querySelectorAll('.edit-mode-only').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.module-item').forEach(el => el.setAttribute('draggable', 'false'));
        document.querySelectorAll('.lesson-item').forEach(el => el.setAttribute('draggable', 'false'));
    }

    function markDirty() {
        hasUnsavedChanges = true;
        btnSave.disabled = false;
        window.onbeforeunload = () => "You have unsaved changes. Are you sure you want to leave?";
    }

    function markClean() {
        hasUnsavedChanges = false;
        btnSave.disabled = true;
        window.onbeforeunload = null;
    }

    btnEdit.addEventListener('click', enableEditMode);
    btnCancel.addEventListener('click', disableEditMode);
    
    btnSave.addEventListener('click', () => {
        if (!confirm('Are you sure you want to save these changes?')) return;

        // Collect all data
        const moduleOrder = [...document.querySelectorAll('.module-item')].map(m => m.dataset.id);
        const lessonUpdates = [];

        document.querySelectorAll('.lessons-list').forEach(list => {
            const moduleId = list.dataset.moduleId;
            const lessonOrder = [...list.querySelectorAll('.lesson-item')].map(l => l.dataset.id);
            if (lessonOrder.length > 0) {
                lessonUpdates.push({ moduleId, order: lessonOrder });
            }
        });

        // We will chain the promises to save everything
        const promises = [];

        // Save Modules
        promises.push(fetch('/instructor/modules/reorder', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order: moduleOrder })
        }));

        // Save Lessons (One request per module for now, as API is structured that way)
        lessonUpdates.forEach(update => {
             promises.push(fetch('/instructor/lessons/reorder', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order: update.order })
            }));
        });

        Promise.all(promises)
            .then(responses => {
                const allOk = responses.every(r => r.ok);
                if (allOk) {
                    markClean();
                    alert('Changes saved successfully!');
                    window.location.reload();
                } else {
                    alert('Some changes failed to save. Please refresh and try again.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred while saving.');
            });
    });

    // Protect "Add/Delete" forms from submitting when dirty
    document.querySelectorAll('.protected-form').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (hasUnsavedChanges) {
                e.preventDefault();
                alert('You have unsaved reorder changes. Please save your changes before adding or deleting items.');
            }
        });
    });

    // --- Drag and Drop Logic (Modified to not auto-save) ---
    
    // Modules
    if (modulesList) {
        modulesList.addEventListener('dragstart', (e) => {
            if (!isEditing) { e.preventDefault(); return; }
            if (!e.target.classList.contains('module-item')) return;
            draggedItem = e.target;
            draggedType = 'module';
            e.dataTransfer.effectAllowed = 'move';
            e.target.classList.add('opacity-50');
        });

        modulesList.addEventListener('dragend', (e) => {
            if (draggedType !== 'module') return;
            e.target.classList.remove('opacity-50');
            draggedItem = null;
            draggedType = null;
        });

        modulesList.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (draggedType !== 'module') return;
            const afterElement = getDragAfterElement(modulesList, e.clientY, '.module-item');
            if (afterElement == null) {
                modulesList.appendChild(draggedItem);
            } else {
                modulesList.insertBefore(draggedItem, afterElement);
            }
        });

        modulesList.addEventListener('drop', (e) => {
            e.preventDefault();
            if (draggedType === 'module') {
                markDirty();
            }
        });
    }

    // Lessons
    const lessonLists = document.querySelectorAll('.lessons-list');
    lessonLists.forEach(list => {
        list.addEventListener('dragstart', (e) => {
            if (!isEditing) { e.preventDefault(); return; }
            if (!e.target.classList.contains('lesson-item')) return;
            draggedItem = e.target;
            draggedType = 'lesson';
            e.stopPropagation(); 
            e.dataTransfer.effectAllowed = 'move';
            e.target.classList.add('opacity-50');
        });

        list.addEventListener('dragend', (e) => {
             if (draggedType !== 'lesson') return;
             e.target.classList.remove('opacity-50');
             draggedItem = null;
             draggedType = null;
        });

        list.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (draggedType !== 'lesson') return;
            const afterElement = getDragAfterElement(list, e.clientY, '.lesson-item');
            if (afterElement == null) {
                list.appendChild(draggedItem);
            } else {
                list.insertBefore(draggedItem, afterElement);
            }
        });

        list.addEventListener('drop', (e) => {
            e.preventDefault();
            if (draggedType === 'lesson') {
                markDirty();
            }
        });
    });

    function getDragAfterElement(container, y, selector) {
        const draggableElements = [...container.querySelectorAll(`${selector}:not(.opacity-50)`)];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
});
</script>
