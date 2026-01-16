<?php ob_start(); ?>

<div class="max-w-7xl mx-auto" x-data="courseEditor()">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="/instructor/course/<?= $course['id'] ?>/edit" class="text-zinc-500 hover:text-white text-sm flex items-center mb-2 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Builder
            </a>
            <h1 class="text-3xl font-bold text-white tracking-tight">Course Settings</h1>
            <p class="text-zinc-400 mt-1">Manage metadata, pricing, and publication status.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/course/<?= $course['id'] ?>" target="_blank" class="hidden md:flex items-center px-4 py-2 text-sm font-medium text-zinc-400 hover:text-white bg-zinc-800/50 hover:bg-zinc-800 border border-zinc-700 rounded-lg transition">
                <i class="fa-solid fa-external-link-alt mr-2"></i> Preview Page
            </a>
            <button type="submit" form="course-settings-form" class="flex items-center px-6 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-lg shadow-lg shadow-blue-500/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-save mr-2"></i> Save Changes
            </button>
        </div>
    </div>

    <form id="course-settings-form" action="/instructor/course/<?= $course['id'] ?>/update" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        
        <!-- Left Column: Main Content -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Basic Information Card -->
            <div class="bg-surface rounded-2xl border border-zinc-700/50 p-6 shadow-xl">
                <h2 class="text-lg font-bold text-white mb-6 flex items-center">
                    <span class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center mr-3 text-sm">
                        <i class="fa-solid fa-pen-nib"></i>
                    </span>
                    Basic Information
                </h2>
                
                <div class="space-y-6">
                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-bold text-zinc-400 mb-2">Course Title</label>
                        <input type="text" name="title" x-model="title" required
                            class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition placeholder-zinc-600 text-lg font-medium"
                            placeholder="e.g. Advanced Laravel Mastery">
                    </div>

                    <!-- Slug (Read Only) -->
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">URL Slug</label>
                        <div class="flex items-center bg-zinc-900/50 border border-zinc-700/50 rounded-xl px-4 py-3 text-zinc-400 text-sm font-mono">
                            <span class="text-zinc-600 select-none">/course/</span>
                            <span class="text-zinc-300"><?= htmlspecialchars($course['slug']) ?></span>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-bold text-zinc-400 mb-2">Description</label>
                        <textarea name="description" rows="8" required
                            class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition placeholder-zinc-600 leading-relaxed"><?= htmlspecialchars($course['description']) ?></textarea>
                        <p class="text-right text-xs text-zinc-500 mt-2">Markdown supported</p>
                    </div>
                </div>
            </div>

            <!-- Certification Card -->
            <div class="bg-surface rounded-2xl border border-zinc-700/50 p-6 shadow-xl">
                <h2 class="text-lg font-bold text-white mb-6 flex items-center">
                    <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center mr-3 text-sm">
                        <i class="fa-solid fa-award"></i>
                    </span>
                    Completion & Certification
                </h2>

                <div class="space-y-4">
                    <!-- Enable Certificate -->
                    <div class="flex items-center justify-between bg-zinc-900/50 p-4 rounded-xl border border-zinc-700/50 hover:border-zinc-600 transition cursor-pointer" @click="hasCert = !hasCert">
                        <div class="flex-1">
                            <div class="font-bold text-white text-sm">Course Certificate</div>
                            <div class="text-xs text-zinc-400 mt-1">Grant a verified certificate upon 100% completion.</div>
                        </div>
                        
                        <!-- Toggle -->
                        <div class="relative inline-block w-12 h-6 transition duration-200 ease-in-out rounded-full cursor-pointer"
                             :class="hasCert ? 'bg-emerald-500' : 'bg-zinc-600'">
                            <span class="block w-4 h-4 mt-1 ml-1 bg-white rounded-full shadow transform transition duration-200 ease-in-out"
                                  :class="hasCert ? 'translate-x-6' : 'translate-x-0'"></span>
                            <input type="checkbox" name="has_certificate" class="hidden" x-model="hasCert" value="1">
                        </div>
                    </div>

                    <!-- Auto Issue -->
                    <div class="flex items-center justify-between bg-zinc-900/50 p-4 rounded-xl border border-zinc-700/50 hover:border-zinc-600 transition" 
                         x-show="hasCert" x-transition.opacity.duration.300ms>
                        <div class="flex-1">
                            <div class="font-bold text-white text-sm">Automatic Issuance</div>
                            <div class="text-xs text-zinc-400 mt-1">Issue certificate immediately upon completion without manual review.</div>
                        </div>
                        
                        <!-- Toggle -->
                        <div class="relative inline-block w-12 h-6 transition duration-200 ease-in-out rounded-full cursor-pointer"
                             @click="autoCert = !autoCert"
                             :class="autoCert ? 'bg-blue-500' : 'bg-zinc-600'">
                            <span class="block w-4 h-4 mt-1 ml-1 bg-white rounded-full shadow transform transition duration-200 ease-in-out"
                                  :class="autoCert ? 'translate-x-6' : 'translate-x-0'"></span>
                            <input type="checkbox" name="certificate_auto_issue" class="hidden" x-model="autoCert" value="1">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Sidebar -->
        <div class="space-y-8">

            <!-- Publishing Card -->
            <div class="bg-surface rounded-2xl border border-zinc-700/50 p-6 shadow-xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10 pointer-events-none">
                    <i class="fa-solid fa-globe text-6xl text-white"></i>
                </div>
                <h2 class="text-lg font-bold text-white mb-6">Publishing</h2>
                
                <div class="space-y-6">
                    <!-- Status -->
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Status</label>
                        <div class="relative">
                            <select name="status" x-model="status" 
                                class="w-full bg-zinc-900 border text-white rounded-xl pl-4 pr-10 py-3 appearance-none focus:outline-none focus:ring-2 transition font-medium"
                                :class="{
                                    'border-zinc-700 focus:border-zinc-500': status === 'draft',
                                    'border-emerald-500/50 focus:border-emerald-500 text-emerald-400': status === 'published',
                                    'border-blue-500/50 focus:border-blue-500 text-blue-400': status === 'upcoming'
                                }">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="upcoming">Upcoming</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-zinc-500">
                                <i class="fa-solid fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Publish Date -->
                    <div x-show="status === 'upcoming'" x-transition>
                        <label class="block text-xs font-bold text-blue-400 uppercase tracking-wider mb-2">Scheduled For</label>
                        <input type="datetime-local" name="published_at" 
                               value="<?= !empty($course['published_at']) ? date('Y-m-d\TH:i', strtotime($course['published_at'])) : '' ?>"
                               class="w-full bg-zinc-900 border border-blue-500/30 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition [color-scheme:dark]">
                    </div>

                    <div class="p-4 rounded-xl bg-zinc-900/50 border border-zinc-700/50 text-xs leading-relaxed" 
                         :class="{
                             'text-zinc-400': status === 'draft',
                             'text-emerald-400/80 border-emerald-500/20 bg-emerald-500/5': status === 'published',
                             'text-blue-400/80 border-blue-500/20 bg-blue-500/5': status === 'upcoming'
                         }">
                        <div x-show="status === 'draft'"><i class="fa-solid fa-pencil mr-2"></i> Only you can see this course.</div>
                        <div x-show="status === 'published'"><i class="fa-solid fa-check-circle mr-2"></i> Course is live and visible to students.</div>
                        <div x-show="status === 'upcoming'"><i class="fa-solid fa-clock mr-2"></i> Course will go live automatically on the scheduled date.</div>
                    </div>
                </div>
            </div>

            <!-- Content Flow Card -->
            <div class="bg-surface rounded-2xl border border-zinc-700/50 p-6 shadow-xl" x-data="{ flow: '<?= $course['content_flow'] ?? 'free' ?>' }">
                <h2 class="text-lg font-bold text-white mb-6 flex items-center">
                    <span class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center mr-3 text-sm">
                        <i class="fa-solid fa-layer-group"></i>
                    </span>
                    Progression
                </h2>
                
                <div class="space-y-3">
                    <label class="flex items-start p-3 rounded-xl border cursor-pointer transition"
                           :class="flow === 'free' ? 'border-purple-500 bg-purple-500/10' : 'border-zinc-700 bg-zinc-900 hover:border-zinc-600'">
                        <input type="radio" name="content_flow" value="free" class="hidden" x-model="flow">
                        <div class="mt-0.5 mr-3">
                            <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                 :class="flow === 'free' ? 'border-purple-500' : 'border-zinc-500'">
                                <div class="w-2 h-2 rounded-full bg-purple-500" x-show="flow === 'free'"></div>
                            </div>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-white">Free Movement</span>
                            <span class="block text-xs text-zinc-400 mt-0.5">Students can access any lesson in any order.</span>
                        </div>
                    </label>

                    <label class="flex items-start p-3 rounded-xl border cursor-pointer transition"
                           :class="flow === 'linear' ? 'border-purple-500 bg-purple-500/10' : 'border-zinc-700 bg-zinc-900 hover:border-zinc-600'">
                        <input type="radio" name="content_flow" value="linear" class="hidden" x-model="flow">
                        <div class="mt-0.5 mr-3">
                            <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                 :class="flow === 'linear' ? 'border-purple-500' : 'border-zinc-500'">
                                <div class="w-2 h-2 rounded-full bg-purple-500" x-show="flow === 'linear'"></div>
                            </div>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-white">Linear Progression</span>
                            <span class="block text-xs text-zinc-400 mt-0.5">Students must complete lessons in sequential order.</span>
                        </div>
                    </label>

                    <label class="flex items-start p-3 rounded-xl border cursor-pointer transition"
                           :class="flow === 'drip_time' ? 'border-purple-500 bg-purple-500/10' : 'border-zinc-700 bg-zinc-900 hover:border-zinc-600'">
                        <input type="radio" name="content_flow" value="drip_time" class="hidden" x-model="flow">
                        <div class="mt-0.5 mr-3">
                            <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                 :class="flow === 'drip_time' ? 'border-purple-500' : 'border-zinc-500'">
                                <div class="w-2 h-2 rounded-full bg-purple-500" x-show="flow === 'drip_time'"></div>
                            </div>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-white">Drip (Time-Based)</span>
                            <span class="block text-xs text-zinc-400 mt-0.5">Unlock lessons after a set number of days from enrollment.</span>
                        </div>
                    </label>

                    <label class="flex items-start p-3 rounded-xl border cursor-pointer transition"
                           :class="flow === 'drip_progress' ? 'border-purple-500 bg-purple-500/10' : 'border-zinc-700 bg-zinc-900 hover:border-zinc-600'">
                        <input type="radio" name="content_flow" value="drip_progress" class="hidden" x-model="flow">
                        <div class="mt-0.5 mr-3">
                            <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                 :class="flow === 'drip_progress' ? 'border-purple-500' : 'border-zinc-500'">
                                <div class="w-2 h-2 rounded-full bg-purple-500" x-show="flow === 'drip_progress'"></div>
                            </div>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-white">Drip (Progress-Based)</span>
                            <span class="block text-xs text-zinc-400 mt-0.5">Unlock lessons after required completion conditions.</span>
                        </div>
                    </label>

                    <label class="flex items-start p-3 rounded-xl border cursor-pointer transition"
                           :class="flow === 'drip_combined' ? 'border-purple-500 bg-purple-500/10' : 'border-zinc-700 bg-zinc-900 hover:border-zinc-600'">
                        <input type="radio" name="content_flow" value="drip_combined" class="hidden" x-model="flow">
                        <div class="mt-0.5 mr-3">
                            <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                 :class="flow === 'drip_combined' ? 'border-purple-500' : 'border-zinc-500'">
                                <div class="w-2 h-2 rounded-full bg-purple-500" x-show="flow === 'drip_combined'"></div>
                            </div>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-white">Drip (Time + Progress)</span>
                            <span class="block text-xs text-zinc-400 mt-0.5">Require both time delay and progress rules.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Media Card -->
            <div class="bg-surface rounded-2xl border border-zinc-700/50 p-6 shadow-xl">
                <h2 class="text-lg font-bold text-white mb-6">Course Media</h2>
                
                <label class="block cursor-pointer group">
                    <div class="relative aspect-video w-full rounded-xl overflow-hidden bg-zinc-900 border-2 border-dashed border-zinc-700 group-hover:border-indigo-500 transition flex flex-col items-center justify-center">
                        <!-- Preview Image -->
                        <img x-show="thumbnailPreview" :src="thumbnailPreview" class="absolute inset-0 w-full h-full object-cover">
                        
                        <!-- Placeholder / Overlay -->
                        <div class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300"
                             :class="{'opacity-100': !thumbnailPreview}">
                            <i class="fa-solid fa-cloud-arrow-up text-3xl text-white mb-2"></i>
                            <span class="text-xs font-bold text-white uppercase tracking-wider">Upload Thumbnail</span>
                        </div>
                    </div>
                    <input type="file" name="thumbnail" accept="image/*" class="hidden" @change="handleFileChange">
                </label>
                <p class="text-xs text-zinc-500 mt-3 text-center">Recommended: 1280x720 (16:9)</p>
            </div>

            <!-- Pricing Card -->
            <div class="bg-surface rounded-2xl border border-zinc-700/50 p-6 shadow-xl">
                <h2 class="text-lg font-bold text-white mb-6">Pricing</h2>
                
                <div class="space-y-6">
                    <div class="flex items-center justify-between p-3 rounded-xl border border-zinc-700/50 bg-zinc-900/30">
                        <span class="text-sm font-bold text-zinc-400">Free Course</span>
                        
                        <!-- Toggle -->
                        <div class="relative inline-block w-12 h-6 transition duration-200 ease-in-out rounded-full cursor-pointer"
                             @click="isFree = !isFree"
                             :class="isFree ? 'bg-indigo-500' : 'bg-zinc-600'">
                            <span class="block w-4 h-4 mt-1 ml-1 bg-white rounded-full shadow transform transition duration-200 ease-in-out"
                                  :class="isFree ? 'translate-x-6' : 'translate-x-0'"></span>
                        </div>
                    </div>

                    <div x-show="!isFree" x-transition>
                        <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Price (USD)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-400 font-bold">$</span>
                            <input type="number" name="price" min="0" step="0.01" x-model="price"
                                class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl pl-8 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition font-mono font-bold text-lg">
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
function courseEditor() {
    return {
        title: '<?= addslashes($course['title']) ?>',
        status: '<?= $course['status'] ?? 'draft' ?>',
        isFree: <?= $course['price'] == 0 ? 'true' : 'false' ?>,
        price: '<?= $course['price'] ?>',
        hasCert: <?= ($course['has_certificate'] ?? 1) ? 'true' : 'false' ?>,
        autoCert: <?= ($course['certificate_auto_issue'] ?? 1) ? 'true' : 'false' ?>,
        thumbnailPreview: '<?= $course['thumbnail'] ?>',
        
        init() {
            // Watchers to ensure data integrity
            this.$watch('isFree', value => {
                if (value) this.price = 0;
            });
        },

        handleFileChange(event) {
            const file = event.target.files[0];
            if (file) {
                this.thumbnailPreview = URL.createObjectURL(file);
            }
        }
    }
}
</script>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../layouts/app.php'; 
?>
