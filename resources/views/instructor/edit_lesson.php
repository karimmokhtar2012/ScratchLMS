<?php ob_start(); ?>

<div class="max-w-3xl mx-auto">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <?php 
                $backLink = $course_id ? "/instructor/course/$course_id/edit" : "/instructor/lessons";
                $backLabel = $course_id ? "Back to Course Builder" : "Back to Library";
            ?>
            <a href="<?= $backLink ?>" class="text-zinc-500 hover:text-white text-sm flex items-center mb-2 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> <?= $backLabel ?>
            </a>
            <h1 class="text-3xl font-bold text-white tracking-tight flex items-center">
                Edit Lesson
                <div class="relative inline-block ml-3 group" x-data="{ tooltip: false }">
                    <button @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="text-zinc-500 hover:text-indigo-400 transition focus:outline-none">
                        <i class="fa-solid fa-circle-info text-sm"></i>
                    </button>
                    <div x-show="tooltip" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-zinc-800 border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl z-50 text-center pointer-events-none">
                        Update lesson content, type, and resources.
                        <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-700"></div>
                    </div>
                </div>
            </h1>
            <p class="text-zinc-400 mt-2">Update content for <span class="text-indigo-400 font-semibold"><?= htmlspecialchars($lesson['title']) ?></span></p>
        </div>
    </div>

    <div class="bg-surface rounded-2xl border border-zinc-700/50 p-8 shadow-xl">
        <form action="/instructor/lesson/<?= $lesson['id'] ?>/update" method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <!-- Hidden Fields -->
            <input type="hidden" name="course_id" value="<?= $course_id ?>">

            <!-- Title -->
            <div>
                <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Lesson Title</label>
                <input type="text" name="title" required value="<?= htmlspecialchars($lesson['title'] ?? '') ?>"
                    class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition">
            </div>

            <!-- Content Type Switcher -->
            <div x-data="{ type: '<?= $lesson['type'] ?>' }">
                <div class="mb-6">
                    <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Lesson Type</label>
                    <select name="type" x-model="type" class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition">
                        <option value="video">Video Lesson</option>
                        <option value="article">Article / Text</option>
                    </select>
                </div>

                <!-- Video Inputs -->
                <div x-show="type === 'video'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Video Source</label>
                        <input type="text" name="content_url" value="<?= htmlspecialchars($lesson['content_url'] ?? '') ?>"
                            class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition mb-2"
                            placeholder="Paste YouTube URL here...">
                        
                        <div class="relative flex items-center gap-4 my-4">
                            <div class="flex-grow h-px bg-zinc-800"></div>
                            <span class="text-xs text-zinc-600 font-bold uppercase">OR Upload File</span>
                            <div class="flex-grow h-px bg-zinc-800"></div>
                        </div>

                        <div class="p-6 border-2 border-dashed border-zinc-700 rounded-xl bg-zinc-900/50 text-center hover:border-indigo-500/50 transition group cursor-pointer relative">
                            <input type="file" name="video_file" accept="video/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <div class="pointer-events-none">
                                <i class="fa-solid fa-cloud-arrow-up text-3xl text-zinc-600 group-hover:text-indigo-500 transition mb-3"></i>
                                <p class="text-sm text-zinc-400 font-medium">Drag & drop or click to upload</p>
                                <p class="text-xs text-zinc-600 mt-1">MP4, WebM (Max 2GB)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Article Inputs -->
                <div x-show="type === 'article'">
                    <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Article Content</label>
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
                    <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
                    <textarea name="content_text" id="markdown_editor"><?= htmlspecialchars($lesson['content_text'] ?? '') ?></textarea>
                    <script>
                        // Init editor only if visible or on load
                        document.addEventListener('DOMContentLoaded', () => {
                            if (document.getElementById('markdown_editor')) {
                                new EasyMDE({ 
                                    element: document.getElementById('markdown_editor'), 
                                    spellChecker: false, 
                                    minHeight: "400px",
                                    status: false,
                                    toolbar: ["bold", "italic", "heading", "|", "quote", "code", "unordered-list", "|", "link", "image", "|", "preview"]
                                });
                            }
                        });
                    </script>
                </div>
            </div>

            <!-- Duration -->
            <div>
                <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Duration (Minutes)</label>
                <input type="number" name="duration" min="0" value="<?= round($lesson['duration'] / 60) ?>"
                    class="w-full md:w-1/3 bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition">
            </div>

            <?php if (!empty($course_id) && in_array($course_flow ?? 'free', ['drip_time', 'drip_progress', 'drip_combined'], true)): ?>
            <div class="bg-zinc-900/50 rounded-xl p-6 border border-zinc-800">
                <h3 class="text-sm font-bold text-indigo-400 uppercase tracking-wider mb-4">Drip Rules</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Unlock After Days</label>
                        <input type="number" name="unlock_after_days" min="0" value="<?= (int) ($drip_rule['unlock_after_days'] ?? 0) ?>"
                            class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition">
                        <p class="text-[10px] text-zinc-500 mt-2">Days after enrollment to unlock.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Unlock After Lesson</label>
                        <select name="unlock_after_lesson_id" class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition">
                            <option value="">None</option>
                            <?php foreach ($course_lessons ?? [] as $cLesson): ?>
                                <option value="<?= $cLesson['id'] ?>" <?= !empty($drip_rule['unlock_after_lesson_id']) && (int)$drip_rule['unlock_after_lesson_id'] === (int)$cLesson['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cLesson['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-[10px] text-zinc-500 mt-2">Require completion of a specific lesson.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Unlock After %</label>
                        <input type="number" name="unlock_after_percent" min="0" max="100" value="<?= (int) ($drip_rule['unlock_after_percent'] ?? 0) ?>"
                            class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition">
                        <p class="text-[10px] text-zinc-500 mt-2">Course completion percentage required.</p>
                    </div>
                </div>
                <p class="text-xs text-zinc-500 mt-4">For combined drip, all rules must be satisfied.</p>
            </div>
            <?php endif; ?>

            <!-- Attachment -->
            <div>
                <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Attachment</label>
                <div class="flex items-center gap-4">
                    <?php if ($lesson['attachment_url']): ?>
                        <div class="flex items-center text-sm text-emerald-400 bg-emerald-500/10 px-3 py-2 rounded-lg border border-emerald-500/20">
                            <i class="fa-solid fa-file-arrow-down mr-2"></i> Current File
                        </div>
                    <?php endif; ?>
                    <input type="file" name="attachment" class="block w-full text-sm text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-zinc-800 file:text-indigo-400 hover:file:bg-zinc-700">
                </div>
            </div>

            <!-- Submit -->
            <div class="pt-6 border-t border-zinc-700/50 flex justify-end">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-indigo-500/20 transform hover:-translate-y-0.5 transition duration-200">
                    Update Lesson
                </button>
            </div>

        </form>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../layouts/app.php'; 
?>
