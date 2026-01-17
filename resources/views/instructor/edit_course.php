<?php ob_start(); ?>

<div class="max-w-7xl mx-auto" x-data="courseEditor()">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="/instructor/course/<?= $course['id'] ?>/edit" class="text-zinc-500 hover:text-white text-sm flex items-center mb-2 transition w-fit">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Builder
            </a>
            <h1 class="text-3xl font-bold text-white tracking-tight">Course Settings</h1>
            <p class="text-zinc-400 mt-1">Manage metadata, pricing, progression, and publication.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/course/<?= $course['id'] ?>" target="_blank" class="hidden md:flex items-center px-4 py-2 text-sm font-medium text-zinc-400 hover:text-white bg-zinc-800/50 hover:bg-zinc-800 border border-zinc-700 rounded-lg transition">
                <i class="fa-solid fa-external-link-alt mr-2"></i> Preview
            </a>
            <button type="submit" form="course-settings-form" class="flex items-center px-6 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-lg shadow-lg shadow-blue-500/20 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-save mr-2"></i> Save Changes
            </button>
        </div>
    </div>

    <form id="course-settings-form" action="/instructor/course/<?= $course['id'] ?>/update" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        
        <!-- Left Column: Main Content (Wide) -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Basic Information Card -->
            <div class="bg-zinc-950 rounded-2xl border border-zinc-800 p-8 shadow-xl">
                <h2 class="text-lg font-bold text-white mb-6 flex items-center">
                    <span class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center mr-3 text-sm border border-indigo-500/20">
                        <i class="fa-solid fa-pen-nib"></i>
                    </span>
                    Basic Information
                </h2>
                
                <div class="space-y-6">
                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Course Title</label>
                        <input type="text" name="title" x-model="title" required
                            class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition placeholder-zinc-600 text-lg font-medium shadow-inner"
                            placeholder="e.g. Advanced Laravel Mastery">
                    </div>

                    <!-- Slug (Read Only) -->
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">URL Slug</label>
                        <div class="flex items-center bg-zinc-900/50 border border-zinc-700/50 rounded-xl px-4 py-3 text-zinc-400 text-sm font-mono cursor-not-allowed">
                            <span class="text-zinc-600 select-none">/course/</span>
                            <span class="text-zinc-300"><?= htmlspecialchars($course['slug']) ?></span>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Description</label>
                        <textarea name="description" rows="6" required
                            class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition placeholder-zinc-600 leading-relaxed shadow-inner"><?= htmlspecialchars($course['description']) ?></textarea>
                        <p class="text-right text-[10px] text-zinc-500 mt-2 font-mono">MARKDOWN SUPPORTED</p>
                    </div>
                </div>
            </div>

            <!-- Progression & Drip Content (New Location) -->
            <div class="bg-zinc-950 rounded-2xl border border-zinc-800 p-8 shadow-xl" x-data="{ flow: '<?= $course['content_flow'] ?? 'free' ?>' }">
                <h2 class="text-lg font-bold text-white mb-6 flex items-center">
                    <span class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center mr-3 text-sm border border-purple-500/20">
                        <i class="fa-solid fa-layer-group"></i>
                    </span>
                    Progression & Drip Content
                </h2>
                
                <!-- Flow Selector -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                    <!-- Free -->
                    <label class="relative flex items-start p-4 rounded-xl border cursor-pointer transition-all duration-200 group"
                           :class="flow === 'free' ? 'border-purple-500 bg-purple-500/5 shadow-[0_0_20px_-10px_rgba(168,85,247,0.3)]' : 'border-zinc-800 bg-zinc-900 hover:border-zinc-700'">
                        <input type="radio" name="content_flow" value="free" class="hidden" x-model="flow">
                        <div class="mt-0.5 mr-3 shrink-0">
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                 :class="flow === 'free' ? 'border-purple-500' : 'border-zinc-600 group-hover:border-zinc-500'">
                                <div class="w-2.5 h-2.5 rounded-full bg-purple-500" x-show="flow === 'free'" x-transition.scale></div>
                            </div>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-white mb-1">Free Movement</span>
                            <span class="block text-xs text-zinc-400 leading-snug">Students can jump between any available lessons freely. Best for reference courses.</span>
                        </div>
                    </label>

                    <!-- Linear -->
                    <label class="relative flex items-start p-4 rounded-xl border cursor-pointer transition-all duration-200 group"
                           :class="flow === 'linear' ? 'border-purple-500 bg-purple-500/5 shadow-[0_0_20px_-10px_rgba(168,85,247,0.3)]' : 'border-zinc-800 bg-zinc-900 hover:border-zinc-700'">
                        <input type="radio" name="content_flow" value="linear" class="hidden" x-model="flow">
                        <div class="mt-0.5 mr-3 shrink-0">
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                 :class="flow === 'linear' ? 'border-purple-500' : 'border-zinc-600 group-hover:border-zinc-500'">
                                <div class="w-2.5 h-2.5 rounded-full bg-purple-500" x-show="flow === 'linear'" x-transition.scale></div>
                            </div>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-white mb-1">Linear Sequence</span>
                            <span class="block text-xs text-zinc-400 leading-snug">Strict step-by-step progression. Lessons unlock only when the previous one is complete.</span>
                        </div>
                    </label>

                    <!-- Drip (Time) -->
                    <label class="relative flex items-start p-4 rounded-xl border cursor-pointer transition-all duration-200 group"
                           :class="['drip_time', 'drip_combined'].includes(flow) ? 'border-amber-500 bg-amber-500/5 shadow-[0_0_20px_-10px_rgba(245,158,11,0.3)]' : 'border-zinc-800 bg-zinc-900 hover:border-zinc-700'">
                        <input type="radio" name="content_flow" value="drip_time" class="hidden" x-model="flow">
                        <div class="mt-0.5 mr-3 shrink-0">
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                 :class="['drip_time', 'drip_combined'].includes(flow) ? 'border-amber-500' : 'border-zinc-600 group-hover:border-zinc-500'">
                                <div class="w-2.5 h-2.5 rounded-full bg-amber-500" x-show="['drip_time', 'drip_combined'].includes(flow)" x-transition.scale></div>
                            </div>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-white mb-1">Time-Based Drip</span>
                            <span class="block text-xs text-zinc-400 leading-snug">Unlock content periodically based on the student's enrollment date.</span>
                        </div>
                    </label>

                    <!-- Drip (Combined) -->
                    <label class="relative flex items-start p-4 rounded-xl border cursor-pointer transition-all duration-200 group"
                           :class="flow === 'drip_combined' ? 'border-pink-500 bg-pink-500/5 shadow-[0_0_20px_-10px_rgba(236,72,153,0.3)]' : 'border-zinc-800 bg-zinc-900 hover:border-zinc-700'">
                        <input type="radio" name="content_flow" value="drip_combined" class="hidden" x-model="flow">
                        <div class="mt-0.5 mr-3 shrink-0">
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                 :class="flow === 'drip_combined' ? 'border-pink-500' : 'border-zinc-600 group-hover:border-zinc-500'">
                                <div class="w-2.5 h-2.5 rounded-full bg-pink-500" x-show="flow === 'drip_combined'" x-transition.scale></div>
                            </div>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-white mb-1">Smart Drip (Hybrid)</span>
                            <span class="block text-xs text-zinc-400 leading-snug">Requires BOTH time delays AND prerequisites (e.g. passing a quiz) to unlock.</span>
                        </div>
                    </label>
                </div>

                <!-- Drip Rules Editor (Expanded) -->
                <?php if (!empty($course_lessons)): ?>
                <div x-show="['drip_time','drip_progress','drip_combined'].includes(flow)" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="mt-8 pt-8 border-t border-zinc-800">
                    
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-sm border border-indigo-500/20">
                                <i class="fa-solid fa-unlock-keyhole"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white">Unlock Rules</p>
                                <p class="text-[10px] text-zinc-400 uppercase tracking-wider">Define criteria per lesson</p>
                            </div>
                        </div>
                        <div class="text-[10px] text-zinc-500 font-mono bg-zinc-900 border border-zinc-800 px-2 py-1 rounded">
                            <i class="fa-solid fa-info-circle mr-1"></i> Empty rules = Unlocked
                        </div>
                    </div>

                    <div class="space-y-3">
                        <?php foreach ($course_lessons as $l): ?>
                            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 flex flex-col md:flex-row md:items-center gap-4 transition hover:border-zinc-700 group">
                                <!-- Lesson Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="bg-zinc-800 text-zinc-400 px-1.5 py-0.5 rounded text-[10px] font-bold border border-zinc-700 group-hover:border-zinc-600 transition">
                                            <?= $l['type'] === 'quiz' ? 'QUIZ' : 'LESSON' ?>
                                        </span>
                                        <h4 class="text-sm font-bold text-white truncate"><?= htmlspecialchars($l['title']) ?></h4>
                                    </div>
                                    <p class="text-[10px] text-zinc-500 font-mono truncate">ID: <?= $l['id'] ?></p>
                                </div>

                                <!-- Rules Inputs -->
                                <div class="flex items-center gap-3 shrink-0">
                                    <!-- Days Delay -->
                                    <div class="relative w-28" x-show="['drip_time', 'drip_combined'].includes(flow)">
                                        <label class="absolute -top-2 left-2 px-1 bg-zinc-900 text-[9px] font-bold text-amber-500 uppercase tracking-wider">Delay</label>
                                        <input type="number" name="drip_rules[<?= (int)$l['id'] ?>][days]" min="0" placeholder="0"
                                               value="<?= isset($l['unlock_after_days']) ? (int)$l['unlock_after_days'] : '' ?>"
                                               class="w-full bg-zinc-950 border border-zinc-800 text-white rounded-lg pl-3 pr-8 py-2 text-xs focus:border-amber-500 focus:ring-1 focus:ring-amber-500/50 transition">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[9px] text-zinc-500 font-bold pointer-events-none">DAY</span>
                                    </div>

                                    <!-- Prerequisite -->
                                    <div class="relative w-40" x-show="['drip_progress', 'drip_combined'].includes(flow)">
                                        <label class="absolute -top-2 left-2 px-1 bg-zinc-900 text-[9px] font-bold text-purple-500 uppercase tracking-wider">After</label>
                                        <select name="drip_rules[<?= (int)$l['id'] ?>][lesson]"
                                                class="w-full bg-zinc-950 border border-zinc-800 text-white rounded-lg pl-2 pr-6 py-2 text-xs focus:border-purple-500 focus:ring-1 focus:ring-purple-500/50 transition appearance-none truncate">
                                            <option value="">(No Prereq)</option>
                                            <?php foreach ($course_lessons as $opt): ?>
                                                <?php if ($opt['id'] !== $l['id']): // Prevent self-reference ?>
                                                <option value="<?= (int)$opt['id'] ?>"
                                                    <?= isset($l['unlock_after_lesson_id']) && (int)$l['unlock_after_lesson_id'] === (int)$opt['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($opt['title']) ?>
                                                </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                        <i class="fa-solid fa-chevron-down absolute right-2 top-1/2 -translate-y-1/2 text-[9px] text-zinc-600 pointer-events-none"></i>
                                    </div>

                                    <!-- Score/Percent -->
                                    <div class="relative w-24" x-show="['drip_progress', 'drip_combined'].includes(flow)">
                                        <label class="absolute -top-2 left-2 px-1 bg-zinc-900 text-[9px] font-bold text-emerald-500 uppercase tracking-wider">Pass %</label>
                                        <input type="number" name="drip_rules[<?= (int)$l['id'] ?>][percent]" min="0" max="100" placeholder="Any"
                                               value="<?= isset($l['unlock_after_percent']) ? (int)$l['unlock_after_percent'] : '' ?>"
                                               class="w-full bg-zinc-950 border border-zinc-800 text-white rounded-lg pl-3 pr-6 py-2 text-xs focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/50 transition">
                                        <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[9px] text-zinc-500 font-bold pointer-events-none">%</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Certification Card -->
            <div class="bg-zinc-950 rounded-2xl border border-zinc-800 p-8 shadow-xl">
                <h2 class="text-lg font-bold text-white mb-6 flex items-center">
                    <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center mr-3 text-sm border border-emerald-500/20">
                        <i class="fa-solid fa-award"></i>
                    </span>
                    Completion & Certification
                </h2>

                <div class="space-y-4">
                    <!-- Enable Certificate -->
                    <div class="flex items-center justify-between bg-zinc-900 p-5 rounded-xl border border-zinc-800 hover:border-zinc-700 transition cursor-pointer group" @click="hasCert = !hasCert">
                        <div class="flex-1">
                            <div class="font-bold text-white text-sm flex items-center">
                                Course Certificate
                                <span x-show="hasCert" class="ml-2 text-[9px] bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-1.5 py-0.5 rounded font-bold uppercase">Active</span>
                            </div>
                            <div class="text-xs text-zinc-400 mt-1">Grant a verified PDF certificate upon 100% completion.</div>
                        </div>
                        
                        <div class="relative inline-block w-12 h-6 transition duration-200 ease-in-out rounded-full"
                             :class="hasCert ? 'bg-emerald-500' : 'bg-zinc-700'">
                            <span class="block w-4 h-4 mt-1 ml-1 bg-white rounded-full shadow transform transition duration-200 ease-in-out"
                                  :class="hasCert ? 'translate-x-6' : 'translate-x-0'"></span>
                            <input type="checkbox" name="has_certificate" class="hidden" x-model="hasCert" value="1">
                        </div>
                    </div>

                    <!-- Auto Issue -->
                    <div class="flex items-center justify-between bg-zinc-900/50 p-5 rounded-xl border border-zinc-800 hover:border-zinc-700 transition" 
                         x-show="hasCert" x-transition.opacity.duration.300ms>
                        <div class="flex-1">
                            <div class="font-bold text-white text-sm">Automatic Issuance</div>
                            <div class="text-xs text-zinc-400 mt-1">Issue immediately without instructor review.</div>
                        </div>
                        
                        <div class="relative inline-block w-12 h-6 transition duration-200 ease-in-out rounded-full cursor-pointer"
                             @click="autoCert = !autoCert"
                             :class="autoCert ? 'bg-blue-500' : 'bg-zinc-700'">
                            <span class="block w-4 h-4 mt-1 ml-1 bg-white rounded-full shadow transform transition duration-200 ease-in-out"
                                  :class="autoCert ? 'translate-x-6' : 'translate-x-0'"></span>
                            <input type="checkbox" name="certificate_auto_issue" class="hidden" x-model="autoCert" value="1">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Sidebar (Narrow) -->
        <div class="space-y-8">

            <!-- Publishing Card -->
            <div class="bg-zinc-950 rounded-2xl border border-zinc-800 p-6 shadow-xl relative overflow-hidden">
                <h2 class="text-lg font-bold text-white mb-6 flex items-center">
                    <span class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center mr-3 text-sm border border-blue-500/20">
                        <i class="fa-solid fa-rocket"></i>
                    </span>
                    Publishing
                </h2>
                
                <div class="space-y-6">
                    <!-- Status -->
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Status</label>
                        <div class="relative">
                            <select name="status" x-model="status" 
                                class="w-full bg-zinc-900 border text-white rounded-xl pl-4 pr-10 py-3 appearance-none focus:outline-none focus:ring-2 transition font-medium"
                                :class="{
                                    'border-zinc-700 focus:border-zinc-500': status === 'draft',
                                    'border-emerald-500/50 focus:border-emerald-500 text-emerald-400 bg-emerald-500/5': status === 'published',
                                    'border-blue-500/50 focus:border-blue-500 text-blue-400 bg-blue-500/5': status === 'upcoming'
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
                        <label class="block text-xs font-bold text-blue-400 uppercase tracking-wider mb-2">Live Date</label>
                        <input type="datetime-local" name="published_at" required
                               value="<?= !empty($course['published_at']) ? date('Y-m-d\TH:i', strtotime($course['published_at'])) : '' ?>"
                               class="w-full bg-zinc-900 border border-blue-500/30 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition [color-scheme:dark]">
                    </div>

                    <!-- Status Explanation -->
                    <div class="p-4 rounded-xl text-xs leading-relaxed border" 
                         :class="{
                             'text-zinc-400 border-zinc-800 bg-zinc-900': status === 'draft',
                             'text-emerald-400 border-emerald-500/20 bg-emerald-500/5': status === 'published',
                             'text-blue-400 border-blue-500/20 bg-blue-500/5': status === 'upcoming'
                         }">
                        <div x-show="status === 'draft'"><i class="fa-solid fa-eye-slash mr-2"></i> Only you can see this course.</div>
                        <div x-show="status === 'published'"><i class="fa-solid fa-globe mr-2"></i> Live and visible to students.</div>
                        <div x-show="status === 'upcoming'"><i class="fa-regular fa-clock mr-2"></i> Scheduled to go live automatically.</div>
                    </div>
                </div>
            </div>

            <!-- Media Card -->
            <div class="bg-zinc-950 rounded-2xl border border-zinc-800 p-6 shadow-xl">
                <h2 class="text-lg font-bold text-white mb-6">Course Media</h2>
                
                <label class="block cursor-pointer group">
                    <div class="relative aspect-video w-full rounded-xl overflow-hidden bg-zinc-900 border-2 border-dashed border-zinc-700 group-hover:border-indigo-500 transition flex flex-col items-center justify-center">
                        <!-- Preview Image -->
                        <img x-show="thumbnailPreview" :src="thumbnailPreview" class="absolute inset-0 w-full h-full object-cover">
                        
                        <!-- Placeholder / Overlay -->
                        <div class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center transition duration-300"
                             :class="thumbnailPreview ? 'opacity-0 group-hover:opacity-100' : 'opacity-100'">
                            <div class="w-12 h-12 rounded-full bg-zinc-800 flex items-center justify-center mb-2 shadow-lg group-hover:scale-110 transition">
                                <i class="fa-solid fa-cloud-arrow-up text-indigo-400"></i>
                            </div>
                            <span class="text-[10px] font-bold text-zinc-300 uppercase tracking-wider">Click to Upload</span>
                        </div>
                    </div>
                    <input type="file" name="thumbnail" accept="image/*" class="hidden" @change="handleFileChange">
                </label>
                <p class="text-[10px] text-zinc-500 mt-3 text-center">1280x720 (16:9) Recommended</p>
            </div>

            <!-- Pricing Card -->
            <div class="bg-zinc-950 rounded-2xl border border-zinc-800 p-6 shadow-xl">
                <h2 class="text-lg font-bold text-white mb-6">Pricing</h2>
                
                <div class="space-y-6">
                    <div class="flex items-center justify-between p-4 rounded-xl border border-zinc-800 bg-zinc-900 cursor-pointer" @click="isFree = !isFree">
                        <span class="text-sm font-bold text-zinc-300">Free Course</span>
                        
                        <div class="relative inline-block w-12 h-6 transition duration-200 ease-in-out rounded-full"
                             :class="isFree ? 'bg-indigo-500' : 'bg-zinc-700'">
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
        thumbnailPreview: '<?= $course['thumbnail'] ? "/uploads/" . $course['thumbnail'] : "" ?>',
        
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