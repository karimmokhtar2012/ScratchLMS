<?php
// Capture dashboard content
ob_start();
?>

<div class="max-w-7xl mx-auto space-y-10">

    <!-- Header Section -->
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-6" data-tour="header">
        <div>
            <h1 class="text-4xl font-extrabold tracking-tight text-white">
                <?= $mode === 'student' ? 'Student' : 'Instructor' ?> <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-violet-400">Dashboard</span>
            </h1>
            <p class="text-zinc-400 mt-1">
                <?= $mode === 'student' ? 'Continue your learning journey.' : 'Manage your courses and track student performance.' ?>
            </p>
        </div>

        <div class="flex items-center gap-3" data-tour="actions">
            <!-- Mode Switcher (Only if allowed) -->
            <?php if ($can_switch): ?>
                <form action="/dashboard/switch-mode" method="POST">
                    <button type="submit" class="px-5 py-2.5 rounded-full bg-zinc-800 border border-zinc-700 text-zinc-300 hover:text-white hover:bg-zinc-700 transition font-bold text-sm flex items-center gap-2">
                        <i class="fa-solid fa-repeat text-indigo-400"></i>
                        Switch to <?= $mode === 'student' ? 'Instructor' : 'Student' ?> View
                    </button>
                </form>
            <?php endif; ?>

            <!-- Action Buttons -->
            <?php if ($mode === 'student'): ?>
                <?php if (isset($last_action)): ?>
                    <a href="/learn/<?= $last_action['course_id'] ?>/lesson/<?= $last_action['lesson_id'] ?>" class="bg-white text-black px-6 py-2.5 rounded-full font-bold transition shadow-lg shadow-white/5 hover:bg-zinc-200 flex items-center text-sm" data-tour="quick-action">
                        <i class="fa-solid fa-play mr-2"></i> Resume Learning
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="/instructor/course/new" class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2.5 rounded-full font-bold transition shadow-lg shadow-indigo-500/25 text-sm flex items-center" data-tour="quick-action">
                    <i class="fa-solid fa-plus mr-2"></i> Create Course
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Guided Tour (Spotlight) -->
    <?php if ($show_tutorial): ?>
        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
        <div x-data="guidedTour()" x-show="show" x-cloak class="fixed inset-0 z-[200]">
            <div class="fixed inset-0 bg-black/60"></div>
            <div class="fixed rounded-2xl border border-indigo-500/60 shadow-[0_0_0_9999px_rgba(0,0,0,0.65)] pointer-events-none transition-all duration-300"
                :style="spotlightStyle">
                <div class="absolute inset-0 rounded-2xl pointer-events-none ring-2 ring-indigo-400/40 shadow-[0_0_24px_rgba(99,102,241,0.45)]"></div>
            </div>

            <div class="fixed w-[92%] max-w-xl bg-zinc-900/95 border border-zinc-800 rounded-2xl p-6 shadow-2xl transition-all duration-300"
                :style="tourCardStyle">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-widest text-indigo-400 font-bold">Guided Tour</p>
                        <h3 class="text-xl font-bold text-white mt-1" x-text="currentStep.title"></h3>
                        <p class="text-zinc-400 text-sm mt-2" x-text="currentStep.body"></p>
                    </div>
                    <button @click="finish()" class="text-zinc-500 hover:text-white text-sm">Skip</button>
                </div>

                <div class="flex items-center justify-between mt-6">
                    <div class="text-xs text-zinc-500 font-bold" x-text="(stepIndex + 1) + ' / ' + steps.length"></div>
                    <div class="flex items-center gap-3">
                        <button @click="prev()" :disabled="stepIndex === 0"
                            class="px-4 py-2 rounded-full text-sm font-bold border border-zinc-700 text-zinc-300 hover:text-white hover:border-zinc-500 transition disabled:opacity-40 disabled:cursor-not-allowed">
                            Back
                        </button>
                        <button @click="next()" class="px-5 py-2 rounded-full text-sm font-bold bg-white text-black hover:bg-zinc-200 transition">
                            <span x-show="!isLast">Next</span>
                            <span x-show="isLast">Finish</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            function guidedTour() {
                const steps = [{
                        selector: '[data-tour=\"header\"]',
                        title: 'Welcome to Your Dashboard',
                        body: 'This is your command center for everything you do in ScratchLMS.'
                    },
                    {
                        selector: '[data-tour=\"quick-action\"]',
                        title: 'Quick Actions',
                        body: 'Jump back into learning or start building a new course instantly.'
                    },
                    {
                        selector: '[data-tour=\"stats\"]',
                        title: 'Your Progress',
                        body: 'Key stats help you track momentum at a glance.'
                    },
                    <?php if ($mode === 'student'): ?> {
                            selector: '[data-tour=\"learning\"]',
                            title: 'My Learning',
                            body: 'All your enrolled courses live here. Pick up where you left off.'
                        },
                        {
                            selector: '[data-tour=\"activity\"]',
                            title: 'Recent Activity',
                            body: 'See your latest lessons and quiz actions here.'
                        },
                        {
                            selector: '[data-tour=\"notifications\"]',
                            title: 'Notifications',
                            body: 'Important alerts and updates show up in the bell.'
                        },
                        {
                            selector: '[data-tour=\"sidebar\"]',
                            title: 'Sidebar Navigation',
                            body: 'Use the sidebar to jump between dashboard sections.'
                        }
                    <?php else: ?> {
                            selector: '[data-tour=\"created\"]',
                            title: 'Your Courses',
                            body: 'Manage the courses you have created and keep them updated.'
                        }
                    <?php endif; ?>
                ];

                return {
                    show: true,
                    stepIndex: 0,
                    steps: steps,
                    spotlightStyle: '',
                    tourCardStyle: '',
                    get currentStep() {
                        return this.steps[this.stepIndex] || this.steps[0];
                    },
                    get isLast() {
                        return this.stepIndex === this.steps.length - 1;
                    },
                    init() {
                        this.updateSpotlight();
                        window.addEventListener('resize', () => this.updateSpotlight());
                        window.addEventListener('scroll', () => this.updateSpotlight(), {
                            passive: true
                        });
                    },
                    updateSpotlight() {
                        const step = this.steps[this.stepIndex];
                        if (!step) return;
                        const el = document.querySelector(step.selector);
                        if (!el) return;
                        el.scrollIntoView({
                            behavior: 'auto',
                            block: 'center'
                        });
                        requestAnimationFrame(() => {
                            const rect = el.getBoundingClientRect();
                            const pad = 6;
                            const top = Math.max(rect.top - pad, 12);
                            const left = Math.max(rect.left - pad, 12);
                            const width = Math.max(rect.width + pad * 2, 80);
                            const height = Math.max(rect.height + pad * 2, 40);
                            this.spotlightStyle = `top:${top}px; left:${left}px; width:${width}px; height:${height}px;`;

                            const cardWidth = Math.min(520, window.innerWidth - 24);
                            const preferBelow = rect.bottom + 16 + 220 < window.innerHeight;
                            const cardTop = preferBelow ? (rect.bottom + 16) : Math.max(rect.top - 16 - 200, 16);
                            let cardLeft = Math.min(Math.max(rect.left, 12), window.innerWidth - cardWidth - 12);
                            if (step.selector === '[data-tour=\"sidebar\"]') {
                                cardLeft = Math.min(rect.right + 16, window.innerWidth - cardWidth - 12);
                            }
                            this.tourCardStyle = `top:${cardTop}px; left:${cardLeft}px; width:${cardWidth}px;`;
                        });
                    },
                    next() {
                        if (this.isLast) {
                            this.finish();
                            return;
                        }
                        this.stepIndex++;
                        this.updateSpotlight();
                    },
                    prev() {
                        if (this.stepIndex === 0) return;
                        this.stepIndex--;
                        this.updateSpotlight();
                    },
                    finish() {
                        this.show = false;
                        fetch('/dashboard/dismiss-tutorial', {
                            method: 'POST'
                        });
                    }
                }
            }
        </script>
    <?php endif; ?>

    <!-- Stats Grid (Solid Bento Style) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6" data-tour="stats">
        <?php if ($mode === 'student'): ?>
            <!-- Student Stats -->
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 hover:border-indigo-500/50 transition duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-zinc-400 text-sm font-medium flex items-center">
                        Active Courses
                        <div class="relative inline-block ml-2 group" x-data="{ tooltip: false }">
                            <button @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="text-zinc-500 hover:text-indigo-400 transition focus:outline-none">
                                <i class="fa-solid fa-circle-info text-xs"></i>
                            </button>
                            <div x-show="tooltip" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-zinc-800 border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl z-50 text-center pointer-events-none">
                                Courses you are currently enrolled in and have not yet finished.
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-700"></div>
                            </div>
                        </div>
                    </h3>
                    <span class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20"><i class="fa-solid fa-book-open"></i></span>
                </div>
                <p class="text-3xl font-bold text-white"><?= $stats['active_courses'] ?></p>
            </div>
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 hover:border-emerald-500/50 transition duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-zinc-400 text-sm font-medium flex items-center">
                        Completed
                        <div class="relative inline-block ml-2 group" x-data="{ tooltip: false }">
                            <button @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="text-zinc-500 hover:text-indigo-400 transition focus:outline-none">
                                <i class="fa-solid fa-circle-info text-xs"></i>
                            </button>
                            <div x-show="tooltip" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-zinc-800 border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl z-50 text-center pointer-events-none">
                                Total number of courses you have finished 100%.
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-700"></div>
                            </div>
                        </div>
                    </h3>
                    <span class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"><i class="fa-solid fa-circle-check"></i></span>
                </div>
                <p class="text-3xl font-bold text-white"><?= $stats['completed_courses'] ?></p>
            </div>
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 hover:border-amber-500/50 transition duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-zinc-400 text-sm font-medium flex items-center">
                        Certificates
                        <div class="relative inline-block ml-2 group" x-data="{ tooltip: false }">
                            <button @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="text-zinc-500 hover:text-indigo-400 transition focus:outline-none">
                                <i class="fa-solid fa-circle-info text-xs"></i>
                            </button>
                            <div x-show="tooltip" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-zinc-800 border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl z-50 text-center pointer-events-none">
                                Official certificates earned from completing courses.
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-700"></div>
                            </div>
                        </div>
                    </h3>
                    <span class="p-2 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20"><i class="fa-solid fa-award"></i></span>
                </div>
                <p class="text-3xl font-bold text-white"><?= $stats['certificates'] ?></p>
            </div>
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 hover:border-violet-500/50 transition duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-zinc-400 text-sm font-medium flex items-center">
                        Hours Learned
                        <div class="relative inline-block ml-2 group" x-data="{ tooltip: false }">
                            <button @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="text-zinc-500 hover:text-indigo-400 transition focus:outline-none">
                                <i class="fa-solid fa-circle-info text-xs"></i>
                            </button>
                            <div x-show="tooltip" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-zinc-800 border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl z-50 text-center pointer-events-none">
                                Total time spent watching lessons and taking quizzes.
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-700"></div>
                            </div>
                        </div>
                    </h3>
                    <span class="p-2 rounded-lg bg-violet-500/10 text-violet-400 border border-violet-500/20"><i class="fa-solid fa-clock"></i></span>
                </div>
                <p class="text-3xl font-bold text-white"><?= $stats['hours_learned'] ?>h</p>
            </div>
        <?php else: ?>
            <!-- Instructor Stats -->
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 hover:border-indigo-500/50 transition duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-zinc-400 text-sm font-medium flex items-center">
                        Total Students
                        <div class="relative inline-block ml-2 group" x-data="{ tooltip: false }">
                            <button @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="text-zinc-500 hover:text-indigo-400 transition focus:outline-none">
                                <i class="fa-solid fa-circle-info text-xs"></i>
                            </button>
                            <div x-show="tooltip" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-zinc-800 border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl z-50 text-center pointer-events-none">
                                Unique students enrolled across all your courses.
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-700"></div>
                            </div>
                        </div>
                    </h3>
                    <span class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20"><i class="fa-solid fa-users"></i></span>
                </div>
                <p class="text-3xl font-bold text-white"><?= $stats['total_students'] ?></p>
            </div>
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 hover:border-emerald-500/50 transition duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-zinc-400 text-sm font-medium flex items-center">
                        Courses Published
                        <div class="relative inline-block ml-2 group" x-data="{ tooltip: false }">
                            <button @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="text-zinc-500 hover:text-indigo-400 transition focus:outline-none">
                                <i class="fa-solid fa-circle-info text-xs"></i>
                            </button>
                            <div x-show="tooltip" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-zinc-800 border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl z-50 text-center pointer-events-none">
                                Live courses available for students to purchase.
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-700"></div>
                            </div>
                        </div>
                    </h3>
                    <span class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"><i class="fa-solid fa-layer-group"></i></span>
                </div>
                <p class="text-3xl font-bold text-white"><?= $stats['courses_created'] ?></p>
            </div>
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 hover:border-amber-500/50 transition duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-zinc-400 text-sm font-medium flex items-center">
                        Pending Requests
                        <div class="relative inline-block ml-2 group" x-data="{ tooltip: false }">
                            <button @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="text-zinc-500 hover:text-indigo-400 transition focus:outline-none">
                                <i class="fa-solid fa-circle-info text-xs"></i>
                            </button>
                            <div x-show="tooltip" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-zinc-800 border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl z-50 text-center pointer-events-none">
                                Quiz retake requests waiting for your approval.
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-700"></div>
                            </div>
                        </div>
                    </h3>
                    <span class="p-2 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20"><i class="fa-solid fa-bell-concierge"></i></span>
                </div>
                <p class="text-3xl font-bold text-white"><?= $stats['pending_requests'] ?></p>
            </div>
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 hover:border-violet-500/50 transition duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-zinc-400 text-sm font-medium flex items-center">
                        Total Lessons
                        <div class="relative inline-block ml-2 group" x-data="{ tooltip: false }">
                            <button @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="text-zinc-500 hover:text-indigo-400 transition focus:outline-none">
                                <i class="fa-solid fa-circle-info text-xs"></i>
                            </button>
                            <div x-show="tooltip" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-zinc-800 border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl z-50 text-center pointer-events-none">
                                Number of video/text lessons created across all courses.
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-700"></div>
                            </div>
                        </div>
                    </h3>
                    <span class="p-2 rounded-lg bg-violet-500/10 text-violet-400 border border-violet-500/20"><i class="fa-solid fa-photo-film"></i></span>
                </div>
                <p class="text-3xl font-bold text-white"><?= $stats['total_content'] ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- ================= STUDENT VIEW ================= -->
        <?php if ($mode === 'student'): ?>
            <div class="lg:col-span-8 space-y-8" data-tour="learning">
                <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                    <i class="fa-solid fa-graduation-cap text-indigo-500"></i> My Learning
                    <div class="relative inline-block ml-2 group" x-data="{ tooltip: false }">
                        <button @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="text-zinc-500 hover:text-indigo-400 transition focus:outline-none">
                            <i class="fa-solid fa-circle-info text-xs"></i>
                        </button>
                        <div x-show="tooltip" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-zinc-800 border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl z-50 text-center pointer-events-none">
                            Access all your purchased content and track your progress.
                            <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-700"></div>
                        </div>
                    </div>
                </h2>
                <?php if (empty($enrolled_courses)): ?>
                    <div class="p-12 border-2 border-dashed border-zinc-800 rounded-3xl text-center">
                        <p class="text-zinc-500 mb-6">You haven't enrolled in any courses yet.</p>
                        <a href="/courses" class="inline-block bg-indigo-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-indigo-500 transition">Explore Catalog</a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($enrolled_courses as $course): ?>
                            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-5 hover:border-indigo-500/30 transition group">
                                <div class="flex items-start gap-4">
                                    <div class="w-20 aspect-video rounded-lg bg-zinc-800 overflow-hidden flex-shrink-0">
                                        <img src="<?= $course['thumbnail'] ?: '/assets/images/course.png' ?>" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <h3 class="text-white font-bold truncate text-sm"><?= htmlspecialchars($course['title']) ?></h3>
                                        <div class="flex items-center justify-between mt-4">
                                            <span class="text-[9px] font-bold text-zinc-500 uppercase tracking-widest"><?= round($course['progress_percent']) ?>% Complete</span>
                                            <a href="/learn/<?= $course['id'] ?>" class="text-[10px] font-bold text-indigo-400 hover:text-indigo-300 transition">RESUME &rarr;</a>
                                        </div>
                                        <div class="w-full bg-zinc-800 rounded-full h-1 mt-2">
                                            <div class="bg-indigo-500 h-full rounded-full" style="width: <?= $course['progress_percent'] ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Student Sidebar -->
            <div class="lg:col-span-4 space-y-8" data-tour="activity">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    Recent Activity
                    <div class="relative inline-block ml-2 group" x-data="{ tooltip: false }">
                        <button @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="text-zinc-500 hover:text-indigo-400 transition focus:outline-none">
                            <i class="fa-solid fa-circle-info text-xs"></i>
                        </button>
                        <div x-show="tooltip" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-zinc-800 border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl z-50 text-center pointer-events-none">
                            A log of your latest actions, lesson completions, and quiz attempts.
                            <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-700"></div>
                        </div>
                    </div>
                </h2>
                <div class="space-y-4">
                    <?php foreach ($recent_activity as $activity): ?>
                        <div class="bg-zinc-900/50 border border-zinc-800 rounded-xl p-4 flex items-center gap-4">
                            <div class="w-8 h-8 rounded-full bg-zinc-800 flex items-center justify-center text-xs text-indigo-400">
                                <i class="fa-solid <?= $activity['verb'] === 'completed' ? 'fa-check' : 'fa-play' ?>"></i>
                            </div>
                            <div class="flex-grow min-w-0">
                                <p class="text-xs text-zinc-300 truncate"><span class="font-bold text-white capitalize"><?= $activity['verb'] ?></span> lesson</p>
                                <p class="text-[10px] text-zinc-500 truncate"><?= htmlspecialchars($activity['course_title']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ================= INSTRUCTOR VIEW ================= -->
        <?php else: ?>
            <div class="lg:col-span-8 space-y-8" data-tour="created">
                <!-- Notifications (Pending Requests) -->
                <?php if (!empty($notifications)): ?>
                    <section class="bg-amber-500/5 border border-amber-500/20 rounded-2xl p-6">
                        <h2 class="text-amber-400 font-bold mb-4 flex items-center text-sm uppercase tracking-wider">
                            <i class="fa-solid fa-bell mr-2 animate-pulse"></i> Attention Required (<?= count($notifications) ?>)
                            <div class="relative inline-block ml-2 group" x-data="{ tooltip: false }">
                                <button @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="text-amber-500 hover:text-amber-300 transition focus:outline-none">
                                    <i class="fa-solid fa-circle-info text-xs"></i>
                                </button>
                                <div x-show="tooltip" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-zinc-800 border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl z-50 text-center pointer-events-none">
                                    Urgent tasks like student quiz retake requests.
                                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-700"></div>
                                </div>
                            </div>
                        </h2>
                        <div class="space-y-3">
                            <?php foreach ($notifications as $notif): ?>
                                <div class="bg-zinc-900/80 border border-zinc-800 p-4 rounded-xl flex items-center justify-between">
                                    <div class="min-w-0 pr-4">
                                        <p class="text-white text-sm font-bold truncate"><?= htmlspecialchars($notif['student_name']) ?> <span class="text-zinc-500 font-normal">requested a retake</span></p>
                                        <p class="text-[10px] text-zinc-500 mt-1"><?= htmlspecialchars($notif['quiz_title']) ?></p>
                                    </div>
                                    <a href="/instructor/requests#attempts" class="px-4 py-2 bg-amber-600/20 text-amber-400 hover:bg-amber-600 hover:text-white text-xs font-bold rounded-lg transition border border-amber-500/30 whitespace-nowrap">
                                        Handle
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                    <i class="fa-solid fa-person-chalkboard text-indigo-500"></i> My Created Courses
                    <div class="relative inline-block ml-2 group" x-data="{ tooltip: false }">
                        <button @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="text-zinc-500 hover:text-indigo-400 transition focus:outline-none">
                            <i class="fa-solid fa-circle-info text-xs"></i>
                        </button>
                        <div x-show="tooltip" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-zinc-800 border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl z-50 text-center pointer-events-none">
                            List of all courses you have authored. Manage content and view analytics here.
                            <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-700"></div>
                        </div>
                    </div>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($created_courses as $course): ?>
                        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden hover:border-indigo-500/50 transition group">
                            <div class="aspect-video bg-black relative">
                                <img src="<?= $course['thumbnail'] ?: '/assets/images/course.png' ?>" class="w-full h-full object-cover opacity-60 group-hover:opacity-100 transition duration-500">
                                <div class="absolute inset-0 bg-gradient-to-t from-zinc-900 to-transparent"></div>
                                <div class="absolute bottom-4 left-4">
                                    <span class="px-2 py-1 rounded bg-black/50 backdrop-blur-md text-[10px] font-bold text-white border border-white/10"><?= $course['price'] > 0 ? '$' . number_format($course['price'], 2) : 'FREE' ?></span>
                                </div>
                            </div>
                            <div class="p-5">
                                <h3 class="text-white font-bold truncate mb-4 group-hover:text-indigo-400 transition"><?= htmlspecialchars($course['title']) ?></h3>
                                <div class="flex gap-2">
                                    <a href="/instructor/course/<?= $course['id'] ?>/edit" class="flex-1 text-center py-1.5 bg-zinc-800 text-zinc-300 rounded-md text-[10px] font-bold uppercase tracking-wider hover:bg-zinc-700 hover:text-white transition border border-zinc-700">Builder</a>
                                    <a href="/instructor/course/<?= $course['id'] ?>/students" class="flex-1 text-center py-1.5 bg-zinc-800 text-zinc-300 rounded-md text-[10px] font-bold uppercase tracking-wider hover:bg-zinc-700 hover:text-white transition border border-zinc-700">Analytics</a>
                                    <form action="/instructor/course/<?= $course['id'] ?>/delete" method="POST" onsubmit="return confirm('⚠️ DANGER: This will PERMANENTLY DELETE this course, all modules, all lessons, all quizzes, and all student enrollments. This cannot be undone. Type DELETE to confirm?');" class="flex-shrink-0">
                                        <button type="submit" class="w-8 h-8 flex items-center justify-center bg-red-900/20 text-red-500 rounded-md hover:bg-red-600 hover:text-white transition border border-red-500/30">
                                            <i class="fa-solid fa-trash-can text-[10px]"></i>
                                        </button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Instructor Sidebar -->
            <div class="lg:col-span-4 space-y-8">
                <div class="bg-indigo-600/5 border border-indigo-500/20 rounded-3xl p-6">
                    <h3 class="text-white font-bold mb-4 flex items-center"><i class="fa-solid fa-lightbulb text-amber-400 mr-2"></i> Teacher Tip
                        <div class="relative inline-block ml-2 group" x-data="{ tooltip: false }">
                            <button @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="text-zinc-500 hover:text-indigo-400 transition focus:outline-none">
                                <i class="fa-solid fa-circle-info text-xs"></i>
                            </button>
                            <div x-show="tooltip" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-zinc-800 border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl z-50 text-center pointer-events-none">
                                Automated suggestions to help you improve your course engagement.
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-700"></div>
                            </div>
                        </div>
                    </h3>
                    <p class="text-zinc-500 text-sm leading-relaxed">
                        Courses with quizzes have 40% higher completion rates. Try adding an assessment to your latest module.
                    </p>
                </div>
                <a href="/instructor/course/new" class="block p-6 rounded-3xl border-2 border-dashed border-zinc-800 text-center hover:border-indigo-500/50 hover:bg-indigo-500/5 transition group">
                    <div class="w-12 h-12 bg-zinc-800 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-indigo-500 transition">
                        <i class="fa-solid fa-plus text-zinc-500 group-hover:text-white"></i>
                    </div>
                    <span class="text-sm font-bold text-zinc-400 group-hover:text-white">Build Another Course</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/app.php';
?>