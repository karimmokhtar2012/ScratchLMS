<?php ob_start(); ?>

    <!-- Hero Section -->
    <section class="relative pt-16 pb-20 lg:pt-24 lg:pb-32 overflow-hidden grid-bg">
        <!-- Glow Effect -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[500px] bg-indigo-600/20 rounded-full blur-[120px] -z-10"></div>

        <div class="max-w-7xl mx-auto px-6 text-center relative z-10">
            <span class="inline-block py-1.5 px-4 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-mono mb-6 backdrop-blur-sm shadow-[0_0_20px_rgba(99,102,241,0.3)]">
                <?= __('hero_badge') ?>
            </span>
            <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight mb-8 text-white leading-tight">
                <?= __('hero_title_line1') ?> <br>
                <span class="gradient-text">V0.7 Beta is Here</span>
            </h1>
            <p class="text-lg md:text-xl text-zinc-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                The ultimate LMS for modern education just got better. Now with AI-powered grading, adaptive learning paths, and enterprise-grade security.
            </p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/dashboard" class="w-full sm:w-auto px-8 py-4 bg-white text-black rounded-full font-bold text-lg hover:bg-zinc-200 transition transform hover:-translate-y-1">
                        <?= __('nav_dashboard') ?>
                    </a>
                <?php else: ?>
                    <a href="/register" class="w-full sm:w-auto px-8 py-4 bg-white text-black rounded-full font-bold text-lg hover:bg-zinc-200 transition transform hover:-translate-y-1">
                        <?= __('hero_cta_learning') ?>
                    </a>
                <?php endif; ?>
                <a href="/courses" class="w-full sm:w-auto px-8 py-4 bg-zinc-800/50 text-white border border-zinc-700 rounded-full font-bold text-lg hover:bg-zinc-800 transition backdrop-blur-sm">
                    <?= __('hero_cta_catalog') ?>
                </a>
            </div>

            <!-- Dashboard Mockup (CSS Only) -->
            <div class="mt-20 relative mx-auto max-w-5xl animate-float">
                <div class="rounded-2xl border border-white/10 bg-zinc-950/80 backdrop-blur-xl shadow-2xl p-4 ring-1 ring-white/5">
                    
                    <!-- Mockup Window -->
                    <div class="flex h-[500px] rounded-xl overflow-hidden bg-zinc-900/50 border border-zinc-800">
                        
                        <!-- Sidebar -->
                        <div class="w-64 border-r border-zinc-800 p-4 hidden md:flex flex-col gap-4 bg-black/20 rtl:border-l rtl:border-r-0">
                            <div class="h-8 w-24 bg-zinc-800 rounded-md mb-4"></div>
                            <div class="h-10 w-full bg-zinc-800/50 rounded-lg"></div>
                            <div class="h-10 w-full bg-indigo-500/10 border border-indigo-500/20 rounded-lg"></div>
                            <div class="h-10 w-full bg-zinc-800/50 rounded-lg"></div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 p-6 flex flex-col gap-6">
                            <!-- Header -->
                            <div class="flex justify-between items-center">
                                <div class="h-8 w-48 bg-zinc-800 rounded-md"></div>
                                <div class="h-10 w-10 rounded-full bg-zinc-800"></div>
                            </div>

                            <!-- Stats Row -->
                            <div class="grid grid-cols-3 gap-4">
                                <div class="h-32 rounded-xl bg-zinc-800/50 border border-zinc-800/50 p-4 flex flex-col justify-between">
                                    <div class="h-8 w-8 rounded-lg bg-indigo-500/20"></div>
                                    <div class="h-4 w-12 bg-zinc-700 rounded"></div>
                                </div>
                                <div class="h-32 rounded-xl bg-zinc-800/50 border border-zinc-800/50 p-4 flex flex-col justify-between">
                                    <div class="h-8 w-8 rounded-lg bg-emerald-500/20"></div>
                                    <div class="h-4 w-12 bg-zinc-700 rounded"></div>
                                </div>
                                <div class="h-32 rounded-xl bg-zinc-800/50 border border-zinc-800/50 p-4 flex flex-col justify-between">
                                    <div class="h-8 w-8 rounded-lg bg-amber-500/20"></div>
                                    <div class="h-4 w-12 bg-zinc-700 rounded"></div>
                                </div>
                            </div>

                            <!-- Animated Graph Area -->
                            <div class="flex-1 rounded-xl bg-zinc-800/30 border border-zinc-800 relative overflow-hidden flex items-end px-4 pb-0 gap-2">
                                <!-- Bars -->
                                <div class="w-full bg-indigo-500/20 rounded-t-lg animate-pulse-slow" style="height: 40%"></div>
                                <div class="w-full bg-indigo-500/40 rounded-t-lg animate-pulse-slow" style="height: 70%; animation-delay: 0.2s"></div>
                                <div class="w-full bg-indigo-500/30 rounded-t-lg animate-pulse-slow" style="height: 50%; animation-delay: 0.4s"></div>
                                <div class="w-full bg-indigo-500/60 rounded-t-lg animate-pulse-slow" style="height: 85%; animation-delay: 0.1s"></div>
                                <div class="w-full bg-indigo-500/40 rounded-t-lg animate-pulse-slow" style="height: 60%; animation-delay: 0.3s"></div>
                                
                                <!-- Line Overlay -->
                                <div class="absolute inset-0 bg-gradient-to-t from-zinc-900 via-transparent to-transparent"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Bento Grid -->
    <section id="features" class="py-24 bg-background">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4"><?= __('sec_everything') ?></h2>
                <p class="text-zinc-400"><?= __('sec_everything_desc') ?></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card 1: Course Builder (Large) -->
                <div class="md:col-span-2 bg-zinc-900/50 border border-zinc-800 p-8 rounded-3xl hover:border-indigo-500/30 transition group overflow-hidden relative">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl -mr-16 -mt-16 transition group-hover:bg-indigo-500/20"></div>
                    <div class="relative z-10">
                        <div class="w-12 h-12 bg-indigo-500/20 rounded-xl flex items-center justify-center text-indigo-400 mb-6">
                            <i class="fa-solid fa-wand-magic-sparkles text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-3">Studio-Grade Course Builder</h3>
                        <p class="text-zinc-400 max-w-md">Drag & drop curriculum design, batch editing, and seamless content organization. Building courses has never been this fluid.</p>
                    </div>
                </div>

                <!-- Card 2: Security -->
                <div class="bg-zinc-900/50 border border-zinc-800 p-8 rounded-3xl hover:border-violet-500/30 transition group relative overflow-hidden">
                    <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-violet-500 to-fuchsia-500"></div>
                    <div class="w-12 h-12 bg-violet-500/20 rounded-xl flex items-center justify-center text-violet-400 mb-6">
                        <i class="fa-solid fa-shield-halved text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Enterprise Security</h3>
                    <p class="text-zinc-400 text-sm">Advanced protection with CSRF tokens, rate limiting, and centralized authorization.</p>
                </div>

                <!-- Card 3: Lesson Library -->
                <div class="bg-zinc-900/50 border border-zinc-800 p-8 rounded-3xl hover:border-emerald-500/30 transition group relative overflow-hidden">
                    <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
                    <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center text-emerald-400 mb-6">
                        <i class="fa-solid fa-box-open text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Asset Library</h3>
                    <p class="text-zinc-400 text-sm">Create once, use everywhere. Manage lessons independently and import them into any course.</p>
                </div>

                <!-- Card 4: Certification (Large) -->
                <div class="md:col-span-2 bg-zinc-900/50 border border-zinc-800 p-8 rounded-3xl hover:border-blue-500/30 transition group overflow-hidden relative">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl -mr-16 -mt-16 transition group-hover:bg-blue-500/20"></div>
                    <div class="relative z-10">
                        <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center text-blue-400 mb-6">
                            <i class="fa-solid fa-award text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-3">Smart Certification System</h3>
                        <p class="text-zinc-400 max-w-md">Automated certificate issuance upon completion. Configurable requirements and verification tracking.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- New Feature Showcase (V0.7 Beta) -->
    <section class="py-24 border-t border-white/5 relative overflow-hidden bg-zinc-950">
        <!-- Background Glow -->
        <div class="absolute top-1/2 left-0 w-[500px] h-[500px] bg-indigo-900/10 rounded-full blur-[120px] -translate-y-1/2 -z-10"></div>

        <div class="max-w-7xl mx-auto px-6">
            <div class="mb-20">
                <span class="text-indigo-400 font-mono text-xs font-bold tracking-widest uppercase mb-4 block"><?= __('sec_new_features') ?></span>
                <h2 class="text-4xl md:text-5xl font-bold text-white"><?= __('sec_power_tools') ?></h2>
            </div>

            <!-- Feature 1: Adaptive Learning Paths -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center mb-24">
                <div class="order-2 lg:order-1 relative group">
                    <div class="absolute -inset-1 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-1000"></div>
                    <div class="relative bg-zinc-900 border border-zinc-800 rounded-2xl p-8 h-72 flex flex-col justify-center overflow-hidden">
                        <!-- Abstract Flow Visual -->
                        <div class="flex items-center justify-center space-x-4 h-full px-8">
                            <div class="w-16 h-16 rounded-xl bg-zinc-800 border border-zinc-700 flex items-center justify-center text-zinc-500"><i class="fa-solid fa-lock-open"></i></div>
                            <div class="flex-1 h-1 bg-gradient-to-r from-zinc-700 to-purple-500 rounded relative">
                                <div class="absolute right-0 -top-1 w-3 h-3 bg-purple-500 rounded-full animate-pulse"></div>
                            </div>
                            <div class="w-16 h-16 rounded-xl bg-purple-500/20 border border-purple-500/50 flex items-center justify-center text-purple-400 shadow-lg shadow-purple-500/20"><i class="fa-solid fa-play"></i></div>
                            <div class="flex-1 h-1 bg-zinc-800 rounded"></div>
                            <div class="w-16 h-16 rounded-xl bg-zinc-800 border border-zinc-700 flex items-center justify-center text-zinc-600"><i class="fa-solid fa-lock"></i></div>
                        </div>
                    </div>
                </div>
                <div class="order-1 lg:order-2">
                    <div class="w-12 h-12 bg-purple-500/10 rounded-xl flex items-center justify-center text-purple-400 mb-6 border border-purple-500/20">
                        <i class="fa-solid fa-route text-xl"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-white mb-4">Adaptive Learning Paths</h3>
                    <p class="text-zinc-400 text-lg leading-relaxed mb-6">
                        Design the perfect journey for your students. Choose between flexible exploration or strict linear progression to ensure mastery.
                    </p>
                    <ul class="space-y-3 text-zinc-300">
                        <li class="flex items-center"><i class="fa-solid fa-check text-purple-500 mr-3 rtl:ml-3 rtl:mr-0"></i> <strong>Linear Sequencing:</strong> Enforce step-by-step completion.</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-purple-500 mr-3 rtl:ml-3 rtl:mr-0"></i> <strong>Prerequisite Locking:</strong> Ensure foundational knowledge.</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-purple-500 mr-3 rtl:ml-3 rtl:mr-0"></i> <strong>Free Movement:</strong> Allow open access for reference courses.</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 2: Quiz Engine -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center mb-24">
                <div>
                    <div class="w-12 h-12 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400 mb-6 border border-indigo-500/20">
                        <i class="fa-solid fa-microchip text-xl"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-white mb-4"><?= __('feat_qbank_title') ?></h3>
                    <p class="text-zinc-400 text-lg leading-relaxed mb-8">
                        <?= __('feat_qbank_desc') ?>
                    </p>
                    
                    <div class="space-y-4">
                        <div class="text-xs font-bold text-zinc-500 uppercase tracking-widest mb-4"><?= __('feat_quiz_types_title') ?></div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex items-center gap-3 text-zinc-300 text-sm bg-zinc-900/50 border border-zinc-800 p-3 rounded-xl">
                                <i class="fa-solid fa-circle-dot text-indigo-500"></i> <?= __('feat_quiz_type_mc') ?>
                            </div>
                            <div class="flex items-center gap-3 text-zinc-300 text-sm bg-zinc-900/50 border border-zinc-800 p-3 rounded-xl">
                                <i class="fa-solid fa-list-check text-indigo-500"></i> <?= __('feat_quiz_type_tf') ?>
                            </div>
                            <div class="flex items-center gap-3 text-zinc-300 text-sm bg-zinc-900/50 border border-zinc-800 p-3 rounded-xl">
                                <i class="fa-solid fa-keyboard text-indigo-500"></i> <?= __('feat_quiz_type_short') ?>
                            </div>
                            <div class="flex items-center gap-3 text-zinc-300 text-sm bg-zinc-900/50 border border-zinc-800 p-3 rounded-xl">
                                <i class="fa-solid fa-pen-to-square text-indigo-500"></i> <?= __('feat_quiz_type_fitb') ?>
                            </div>
                            <div class="flex items-center gap-3 text-zinc-300 text-sm bg-zinc-900/50 border border-zinc-800 p-3 rounded-xl">
                                <i class="fa-solid fa-puzzle-piece text-indigo-500"></i> <?= __('feat_quiz_type_match') ?>
                            </div>
                            <div class="flex items-center gap-3 text-zinc-300 text-sm bg-zinc-900/50 border border-zinc-800 p-3 rounded-xl">
                                <i class="fa-solid fa-robot text-purple-500"></i> <?= __('feat_quiz_type_essay') ?>
                            </div>
                            <div class="flex items-center gap-3 text-zinc-300 text-sm bg-zinc-900/50 border border-zinc-800 p-3 rounded-xl col-span-2">
                                <i class="fa-solid fa-video text-rose-500"></i> <?= __('feat_quiz_type_video') ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="relative group">
                    <div class="absolute -inset-1 bg-gradient-to-r from-indigo-600 to-blue-600 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-1000"></div>
                    <div class="relative bg-zinc-900 border border-zinc-800 rounded-2xl p-8 min-h-[400px] flex flex-col justify-between overflow-hidden">
                        
                        <!-- Dynamic Feature List -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-black/40 border border-zinc-800 rounded-2xl">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-red-500/10 rounded-lg flex items-center justify-center text-red-500 border border-red-500/20">
                                        <i class="fa-solid fa-shield-halved"></i>
                                    </div>
                                    <span class="text-white font-bold"><?= __('feat_quiz_feature_anticheat') ?></span>
                                </div>
                                <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-1 rounded-md font-mono font-bold">ACTIVE</span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-black/40 border border-zinc-800 rounded-2xl">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-amber-500/10 rounded-lg flex items-center justify-center text-amber-500 border border-amber-500/20">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </div>
                                    <span class="text-white font-bold"><?= __('feat_quiz_feature_retakes') ?></span>
                                </div>
                                <span class="text-[10px] bg-zinc-800 text-zinc-400 px-2 py-1 rounded-md font-mono font-bold">SMART</span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-black/40 border border-zinc-800 rounded-2xl">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-indigo-500/10 rounded-lg flex items-center justify-center text-indigo-500 border border-indigo-500/20">
                                        <i class="fa-solid fa-layer-group"></i>
                                    </div>
                                    <span class="text-white font-bold"><?= __('feat_quiz_feature_bank') ?></span>
                                </div>
                                <span class="text-[10px] bg-zinc-800 text-zinc-400 px-2 py-1 rounded-md font-mono font-bold">SYNCED</span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-black/40 border border-zinc-800 rounded-2xl">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-emerald-500/10 rounded-lg flex items-center justify-center text-emerald-500 border border-emerald-500/20">
                                        <i class="fa-solid fa-clipboard-check"></i>
                                    </div>
                                    <span class="text-white font-bold"><?= __('feat_quiz_feature_queue') ?></span>
                                </div>
                                <span class="text-[10px] bg-emerald-500/20 text-emerald-400 px-2 py-1 rounded-md font-mono font-bold">HYBRID</span>
                            </div>
                        </div>

                        <!-- Bottom Action Mockup -->
                        <div class="mt-8 flex justify-end">
                            <div class="bg-indigo-600 text-white px-6 py-3 rounded-xl text-xs font-bold shadow-lg flex items-center gap-3 hover:bg-indigo-500 transition cursor-pointer">
                                <i class="fa-solid fa-bolt"></i> RUN ENGINE TEST
                            </div>
                        </div>

                        <!-- Abstract Background Detail -->
                        <div class="absolute -bottom-12 -left-12 w-48 h-48 bg-indigo-500/5 rounded-full blur-3xl"></div>
                    </div>
                </div>
            </div>

            <!-- Feature 3: Student Experience -->
            <div class="bg-zinc-900/30 border border-white/5 rounded-3xl p-8 md:p-12 text-center">
                <h3 class="text-3xl font-bold text-white mb-8"><?= __('feat_student_exp_title') ?></h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-6xl mx-auto">
                    <div class="bg-black/40 p-6 rounded-2xl border border-zinc-800 hover:border-zinc-700 transition text-left group">
                        <i class="fa-solid fa-certificate text-3xl text-amber-500 mb-4 group-hover:scale-110 transition"></i>
                        <h4 class="text-xl font-bold text-white mb-2"><?= __('feat_cert_title') ?></h4>
                        <p class="text-zinc-400 text-sm"><?= __('feat_cert_desc') ?></p>
                    </div>
                    <div class="bg-black/40 p-6 rounded-2xl border border-zinc-800 hover:border-zinc-700 transition text-left group">
                        <i class="fa-solid fa-book-bookmark text-3xl text-blue-500 mb-4 group-hover:scale-110 transition"></i>
                        <h4 class="text-xl font-bold text-white mb-2"><?= __('feat_notebook_title') ?></h4>
                        <p class="text-zinc-400 text-sm"><?= __('feat_notebook_desc') ?></p>
                    </div>
                    <div class="bg-black/40 p-6 rounded-2xl border border-zinc-800 hover:border-zinc-700 transition text-left group">
                        <i class="fa-solid fa-bell text-3xl text-indigo-500 mb-4 group-hover:scale-110 transition"></i>
                        <h4 class="text-xl font-bold text-white mb-2"><?= __('feat_toast_title') ?></h4>
                        <p class="text-zinc-400 text-sm"><?= __('feat_toast_desc') ?></p>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Scratcha AI Section -->
    <section class="py-24 bg-zinc-950 relative overflow-hidden border-t border-white/5">
        <!-- AI Glow Effect -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-purple-600/10 rounded-full blur-[120px] -z-10"></div>
        
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <span class="inline-block py-1 px-3 rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-400 text-[10px] font-bold uppercase tracking-widest mb-6">
                        <?= __('sec_scratcha_badge') ?>
                    </span>
                    <h2 class="text-4xl md:text-6xl font-extrabold text-white mb-6 leading-tight">
                        <?= __('sec_scratcha_title') ?>
                    </h2>
                    <p class="text-zinc-400 text-lg leading-relaxed mb-8">
                        <?= __('sec_scratcha_desc') ?>
                    </p>

                    <div class="space-y-6">
                        <div class="flex items-start gap-4 group">
                            <div class="w-12 h-12 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-400 shrink-0 border border-purple-500/20 group-hover:bg-purple-500/20 transition">
                                <i class="fa-solid fa-pen-nib text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-bold text-lg"><?= __('feat_ai_essay_title') ?></h4>
                                <p class="text-zinc-500 text-sm mt-1"><?= __('feat_ai_essay_desc') ?></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 group">
                            <div class="w-12 h-12 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 shrink-0 border border-indigo-500/20 group-hover:bg-indigo-500/20 transition">
                                <i class="fa-solid fa-quote-left text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-bold text-lg"><?= __('feat_ai_rationale_title') ?></h4>
                                <p class="text-zinc-500 text-sm mt-1"><?= __('feat_ai_rationale_desc') ?></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 group">
                            <div class="w-12 h-12 rounded-xl bg-fuchsia-500/10 flex items-center justify-center text-fuchsia-400 shrink-0 border border-fuchsia-500/20 group-hover:bg-fuchsia-500/20 transition">
                                <i class="fa-solid fa-list-check text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-bold text-lg"><?= __('feat_ai_rubric_title') ?></h4>
                                <p class="text-zinc-500 text-sm mt-1"><?= __('feat_ai_rubric_desc') ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <!-- AI Mockup Card -->
                    <div class="bg-zinc-900/80 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-500/5 to-transparent"></div>
                        
                        <!-- AI Header -->
                        <div class="flex items-center gap-4 mb-8 border-b border-white/5 pb-6">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-purple-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-purple-500/20">
                                <i class="fa-solid fa-robot text-xl animate-pulse"></i>
                            </div>
                            <div>
                                <h3 class="text-white font-bold uppercase tracking-tight">Scratcha AI</h3>
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                                    <span class="text-[10px] text-zinc-500 uppercase font-mono tracking-widest"><?= __('ai_mockup_analyzing') ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- AI Logic Flow -->
                        <div class="space-y-4">
                            <div class="p-4 bg-black/40 rounded-xl border border-zinc-800">
                                <div class="text-[10px] text-zinc-500 mb-2 uppercase font-bold tracking-widest"><?= __('ai_mockup_answer_label') ?></div>
                                <p class="text-xs text-zinc-300 italic"><?= __('ai_mockup_answer_text') ?></p>
                            </div>

                            <div class="flex justify-center my-2">
                                <div class="h-6 w-0.5 bg-gradient-to-b from-purple-500 to-transparent"></div>
                            </div>

                            <div class="p-4 bg-purple-500/5 rounded-xl border border-purple-500/20 relative">
                                <div class="absolute -top-3 left-6 px-2 bg-zinc-900 border border-purple-500/30 rounded text-[9px] text-purple-400 font-bold uppercase"><?= __('ai_mockup_analysis_label') ?></div>
                                <div class="flex justify-between items-center mb-3 mt-1">
                                    <div class="flex gap-1">
                                        <div class="w-1.5 h-1.5 bg-purple-500 rounded-full"></div>
                                        <div class="w-1.5 h-1.5 bg-purple-500/50 rounded-full"></div>
                                        <div class="w-1.5 h-1.5 bg-purple-500/20 rounded-full"></div>
                                    </div>
                                    <div class="text-xs font-bold text-white bg-purple-600 px-2 py-0.5 rounded shadow-[0_0_10px_rgba(147,51,234,0.5)]"><?= __('ai_mockup_score') ?></div>
                                </div>
                                <p class="text-[11px] text-zinc-400 leading-relaxed">
                                    <?= __('ai_mockup_analysis_text') ?>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Power Indicator -->
                        <div class="mt-8 pt-6 border-t border-white/5 flex items-center justify-between">
                            <div class="text-[10px] text-zinc-500 font-mono italic"><?= __('ai_mockup_engine') ?></div>
                            <div class="flex gap-1.5">
                                <div class="w-1.5 h-1.5 bg-purple-500 rounded-full animate-bounce [animation-duration:1s]"></div>
                                <div class="w-1.5 h-1.5 bg-purple-500 rounded-full animate-bounce [animation-duration:1s] [animation-delay:0.2s]"></div>
                                <div class="w-1.5 h-1.5 bg-purple-500 rounded-full animate-bounce [animation-duration:1s] [animation-delay:0.4s]"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Badge -->
                    <div class="absolute -bottom-6 -right-6 bg-white text-black px-6 py-4 rounded-2xl shadow-2xl transform -rotate-3 hover:rotate-0 transition duration-500 hidden md:block border-4 border-zinc-950">
                        <div class="text-[10px] font-bold uppercase tracking-tighter mb-1 text-zinc-500"><?= __('ai_mockup_speed_label') ?></div>
                        <div class="text-3xl font-black leading-none"><?= __('ai_mockup_speed_value') ?></div>
                        <div class="text-[10px] font-bold text-indigo-600 uppercase mt-1"><?= __('ai_mockup_speed_unit') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Performance (New Section) -->
    <section class="py-24 bg-background relative overflow-hidden border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div>
                <span class="text-cyan-400 font-mono text-xs font-bold tracking-widest uppercase mb-4 block">Unmatched Performance</span>
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">Engineered for <span class="text-cyan-400">Speed</span></h2>
                <p class="text-zinc-400 text-lg leading-relaxed mb-8">
                    Built on a custom high-performance architecture, our LMS delivers sub-50ms latency globally. Experience instant page loads, real-time sync, and zero buffering.
                </p>
                
                <div class="space-y-6">
                    <div class="flex items-start">
                        <div class="w-12 h-12 rounded-lg bg-cyan-500/10 flex items-center justify-center text-cyan-400 shrink-0 mr-4 border border-cyan-500/20">
                            <i class="fa-solid fa-bolt text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-lg">Optimized Runtime</h4>
                            <p class="text-zinc-500 text-sm mt-1">Lightweight PHP 8.x core with zero bloat. Every line of code is optimized for maximum throughput.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="w-12 h-12 rounded-lg bg-cyan-500/10 flex items-center justify-center text-cyan-400 shrink-0 mr-4 border border-cyan-500/20">
                            <i class="fa-solid fa-globe text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-lg">Edge Caching Ready</h4>
                            <p class="text-zinc-500 text-sm mt-1">Static assets and content are pre-optimized for CDN distribution, ensuring global availability.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="relative">
                <div class="absolute inset-0 bg-cyan-500/20 blur-[100px] rounded-full"></div>
                <div class="relative bg-zinc-900 border border-zinc-800 rounded-2xl p-8 shadow-2xl">
                    <div class="space-y-6">
                        <!-- Metric 1 -->
                        <div>
                            <div class="flex justify-between text-sm font-bold text-zinc-400 mb-2">
                                <span>Server Response Time</span>
                                <span class="text-cyan-400">12ms</span>
                            </div>
                            <div class="h-2 bg-zinc-800 rounded-full overflow-hidden">
                                <div class="h-full bg-cyan-500 w-[5%] shadow-[0_0_10px_#06b6d4]"></div>
                            </div>
                        </div>
                        <!-- Metric 2 -->
                        <div>
                            <div class="flex justify-between text-sm font-bold text-zinc-400 mb-2">
                                <span>Database Query</span>
                                <span class="text-cyan-400">0.4ms</span>
                            </div>
                            <div class="h-2 bg-zinc-800 rounded-full overflow-hidden">
                                <div class="h-full bg-cyan-500 w-[2%] shadow-[0_0_10px_#06b6d4]"></div>
                            </div>
                        </div>
                        <!-- Metric 3 -->
                        <div>
                            <div class="flex justify-between text-sm font-bold text-zinc-400 mb-2">
                                <span>Concurrent Users</span>
                                <span class="text-cyan-400">10,000+</span>
                            </div>
                            <div class="h-2 bg-zinc-800 rounded-full overflow-hidden">
                                <div class="h-full bg-cyan-500 w-[85%] shadow-[0_0_10px_#06b6d4]"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Smart Attempt Workflow (V0.7 Beta) -->
    <section class="py-24 bg-black relative overflow-hidden border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="text-center mb-20">
                <span class="text-fuchsia-500 font-mono text-xs font-bold tracking-widest uppercase mb-4 block"><?= __('sec_workflow') ?></span>
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-6"><?= __('sec_smart_attempt') ?></h2>
                <p class="text-zinc-400 text-lg max-w-2xl mx-auto">
                    <?= __('sec_smart_attempt_desc') ?>
                </p>
            </div>

            <div class="relative">
                <!-- Connector Line -->
                <div class="hidden md:block absolute top-[3.5rem] left-[16%] right-[16%] h-0.5 bg-gradient-to-r from-zinc-800 via-fuchsia-900 to-zinc-800 z-0"></div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Step 1 -->
                    <div class="relative z-10 group">
                        <div class="w-28 h-28 mx-auto bg-zinc-950 border border-zinc-800 rounded-full flex items-center justify-center mb-8 shadow-2xl group-hover:border-fuchsia-500/50 group-hover:shadow-fuchsia-500/20 transition duration-500">
                            <i class="fa-solid fa-hand-holding-heart text-3xl text-zinc-500 group-hover:text-fuchsia-400 transition"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white text-center mb-3"><?= __('workflow_step1_title') ?></h3>
                        <p class="text-zinc-500 text-center text-sm px-4"><?= __('workflow_step1_desc') ?></p>
                    </div>

                    <!-- Step 2 -->
                    <div class="relative z-10 group">
                        <div class="w-28 h-28 mx-auto bg-zinc-950 border border-zinc-800 rounded-full flex items-center justify-center mb-8 shadow-2xl group-hover:border-pink-500/50 group-hover:shadow-pink-500/20 transition duration-500">
                            <div class="relative">
                                <i class="fa-solid fa-user-shield text-3xl text-zinc-500 group-hover:text-pink-400 transition"></i>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-ping"></div>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-black"></div>
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-white text-center mb-3"><?= __('workflow_step2_title') ?></h3>
                        <p class="text-zinc-500 text-center text-sm px-4"><?= __('workflow_step2_desc') ?></p>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative z-10 group">
                        <div class="w-28 h-28 mx-auto bg-zinc-950 border border-zinc-800 rounded-full flex items-center justify-center mb-8 shadow-2xl group-hover:border-indigo-500/50 group-hover:shadow-indigo-500/20 transition duration-500">
                            <i class="fa-solid fa-rotate-right text-3xl text-zinc-500 group-hover:text-indigo-400 transition"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white text-center mb-3"><?= __('workflow_step3_title') ?></h3>
                        <p class="text-zinc-500 text-center text-sm px-4"><?= __('workflow_step3_desc') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Anti-Cheat (V0.7 Beta) -->
    <section class="py-24 bg-zinc-950 relative overflow-hidden border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div class="relative">
                <div class="absolute inset-0 bg-red-600/20 blur-[100px] rounded-full"></div>
                <div class="relative bg-black/80 border border-red-900/30 p-8 rounded-3xl backdrop-blur-sm">
                    <div class="flex justify-between items-center mb-8 border-b border-white/5 pb-4">
                        <div class="flex items-center space-x-2 text-red-500">
                            <i class="fa-solid fa-triangle-exclamation animate-pulse"></i>
                            <span class="font-mono text-xs font-bold uppercase tracking-widest"><?= __('anticheat_active') ?></span>
                        </div>
                        <div class="font-mono text-xl font-bold text-white tabular-nums">00:00:00</div>
                    </div>
                    <div class="space-y-4">
                        <div class="h-2 bg-zinc-800 rounded-full overflow-hidden">
                            <div class="h-full bg-red-600 w-full animate-pulse"></div>
                        </div>
                        <p class="text-center text-zinc-500 text-xs"><?= __('anticheat_closed') ?></p>
                    </div>
                </div>
            </div>
            <div>
                <span class="text-red-500 font-mono text-xs font-bold tracking-widest uppercase mb-4 block"><?= __('sec_anti_cheat') ?></span>
                <h2 class="text-4xl font-bold text-white mb-6"><?= __('sec_integrity') ?></h2>
                <p class="text-zinc-400 text-lg leading-relaxed mb-6">
                    <?= __('anticheat_main_desc') ?>
                </p>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-zinc-900/50 p-4 rounded-xl border border-zinc-800">
                        <i class="fa-solid fa-server text-zinc-500 mb-2"></i>
                        <h4 class="text-white font-bold text-sm"><?= __('anticheat_item1') ?></h4>
                        <p class="text-xs text-zinc-600 mt-1">Real-time IP & session monitoring.</p>
                    </div>
                    <div class="bg-zinc-900/50 p-4 rounded-xl border border-zinc-800">
                        <i class="fa-solid fa-ban text-zinc-500 mb-2"></i>
                        <h4 class="text-white font-bold text-sm"><?= __('anticheat_item2') ?></h4>
                        <p class="text-xs text-zinc-600 mt-1">Prevents copy-paste & tab switching.</p>
                    </div>
                    <div class="bg-zinc-900/50 p-4 rounded-xl border border-zinc-800">
                        <i class="fa-solid fa-eye text-zinc-500 mb-2"></i>
                        <h4 class="text-white font-bold text-sm">Proctoring AI</h4>
                        <p class="text-xs text-zinc-600 mt-1">Automated behavior analysis.</p>
                    </div>
                    <div class="bg-zinc-900/50 p-4 rounded-xl border border-zinc-800">
                        <i class="fa-solid fa-laptop-code text-zinc-500 mb-2"></i>
                        <h4 class="text-white font-bold text-sm">Device Lockdown</h4>
                        <p class="text-xs text-zinc-600 mt-1">Full-screen enforcement mode.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Role Switching (Interactive) -->
    <section class="py-32 bg-background relative border-t border-white/5 overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 text-center" x-data="{ mode: 'student' }">
            
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6"><?= __('sec_role_switch') ?></h2>
            <p class="text-zinc-400 text-lg max-w-2xl mx-auto mb-16">
                <?= __('sec_role_switch_desc') ?>
            </p>

            <!-- Interactive Toggle -->
            <div class="relative inline-flex bg-zinc-900 rounded-full p-1 border border-zinc-800 cursor-pointer mb-20 shadow-2xl">
                <!-- Sliding Background -->
                <div class="absolute inset-y-1 left-1 w-[calc(50%-4px)] bg-indigo-600 rounded-full transition-transform duration-500 cubic-bezier(0.34, 1.56, 0.64, 1)"
                     :class="mode === 'student' ? 'translate-x-0' : 'translate-x-full rtl:-translate-x-full'"></div>

                <button @click="mode = 'student'" 
                        class="relative z-10 px-10 py-4 rounded-full text-base font-bold transition-colors duration-300 w-48"
                        :class="mode === 'student' ? 'text-white' : 'text-zinc-400 hover:text-white'">
                    <?= __('role_student') ?>
                </button>
                <button @click="mode = 'instructor'" 
                        class="relative z-10 px-10 py-4 rounded-full text-base font-bold transition-colors duration-300 w-48"
                        :class="mode === 'instructor' ? 'text-white' : 'text-zinc-400 hover:text-white'">
                    <?= __('role_instructor') ?>
                </button>
            </div>

            <!-- Dynamic Preview Card -->
            <div class="relative max-w-3xl mx-auto h-96 group perspective-1000">
                
                <!-- Student View -->
                <div class="absolute inset-0 bg-zinc-950 border border-zinc-800 rounded-[2.5rem] p-10 shadow-2xl transform transition-all duration-700 ease-out backface-hidden flex flex-col justify-center items-center overflow-hidden"
                     :class="mode === 'student' ? 'opacity-100 rotate-y-0 z-20 scale-100' : 'opacity-0 -rotate-y-180 z-10 scale-90'">
                    
                    <!-- Background decoration -->
                    <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-indigo-500/5 to-transparent"></div>

                    <div class="relative z-10 w-24 h-24 bg-zinc-900 rounded-2xl flex items-center justify-center mb-8 border border-zinc-800 shadow-xl">
                        <i class="fa-solid fa-graduation-cap text-5xl text-indigo-500"></i>
                    </div>
                    <h3 class="relative z-10 text-3xl font-bold text-white mb-3"><?= __('role_student_title') ?></h3>
                    <p class="relative z-10 text-zinc-400 max-w-sm text-center text-lg"><?= __('role_student_desc') ?></p>
                    
                    <!-- Fake UI Element -->
                    <div class="relative z-10 mt-10 w-72 bg-zinc-900 rounded-full h-3 overflow-hidden border border-zinc-800">
                        <div class="h-full bg-indigo-500 w-3/4 rounded-full shadow-[0_0_15px_rgba(99,102,241,0.5)]"></div>
                    </div>
                </div>

                <!-- Instructor View -->
                <div class="absolute inset-0 bg-zinc-950 border border-zinc-800 rounded-[2.5rem] p-10 shadow-2xl transform transition-all duration-700 ease-out backface-hidden flex flex-col justify-center items-center overflow-hidden"
                     :class="mode === 'instructor' ? 'opacity-100 rotate-y-0 z-20 scale-100' : 'opacity-0 rotate-y-180 z-10 scale-90'">
                    
                    <!-- Background decoration -->
                    <div class="absolute top-0 right-0 w-full h-full bg-gradient-to-bl from-emerald-500/5 to-transparent"></div>

                    <div class="relative z-10 w-24 h-24 bg-zinc-900 rounded-2xl flex items-center justify-center mb-8 border border-zinc-800 shadow-xl">
                        <i class="fa-solid fa-chart-pie text-5xl text-emerald-500"></i>
                    </div>
                    <h3 class="relative z-10 text-3xl font-bold text-white mb-3"><?= __('role_instructor_title') ?></h3>
                    <p class="relative z-10 text-zinc-400 max-w-sm text-center text-lg"><?= __('role_instructor_desc') ?></p>
                    
                    <!-- Fake UI Element -->
                    <div class="relative z-10 mt-10 flex items-end gap-3 h-12">
                        <div class="w-4 bg-emerald-500/20 rounded-t-sm h-6"></div>
                        <div class="w-4 bg-emerald-500/40 rounded-t-sm h-8"></div>
                        <div class="w-4 bg-emerald-500/60 rounded-t-sm h-5"></div>
                        <div class="w-4 bg-emerald-500 rounded-t-sm h-full shadow-[0_0_15px_rgba(16,185,129,0.5)]"></div>
                        <div class="w-4 bg-emerald-500/30 rounded-t-sm h-7"></div>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <style>
        .perspective-1000 { perspective: 1000px; }
        .backface-hidden { backface-visibility: hidden; }
        .rotate-y-0 { transform: rotateY(0deg); }
        .rotate-y-180 { transform: rotateY(180deg); }
        .-rotate-y-180 { transform: rotateY(-180deg); }
    </style>

    <!-- Architecture / Speed -->
    <section class="py-24 bg-zinc-950 border-y border-white/5 relative overflow-hidden">
        <!-- Abstract Grid -->
        <div class="absolute inset-0 grid-bg opacity-20"></div>
        
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-12 relative z-10">
            <div class="md:col-span-1">
                <span class="text-cyan-400 font-mono text-xs font-bold tracking-widest uppercase mb-4 block"><?= __('sec_core') ?></span>
                <h2 class="text-4xl font-bold text-white mb-6"><?= __('sec_fast') ?></h2>
                <p class="text-zinc-400 text-lg leading-relaxed">
                    <?= __('sec_core_desc') ?>
                </p>
            </div>
            <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Feature 1 -->
                <div class="bg-black border border-zinc-800 p-6 rounded-2xl hover:border-cyan-500/50 transition duration-500 group">
                    <div class="w-10 h-10 bg-cyan-900/30 rounded-lg flex items-center justify-center text-cyan-400 mb-4 group-hover:scale-110 transition">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <h3 class="text-white font-bold mb-2"><?= __('core_feat1_title') ?></h3>
                    <p class="text-zinc-500 text-sm"><?= __('core_feat1_desc') ?></p>
                </div>
                <!-- Feature 2 -->
                <div class="bg-black border border-zinc-800 p-6 rounded-2xl hover:border-cyan-500/50 transition duration-500 group">
                    <div class="w-10 h-10 bg-cyan-900/30 rounded-lg flex items-center justify-center text-cyan-400 mb-4 group-hover:scale-110 transition">
                        <i class="fa-solid fa-link"></i>
                    </div>
                    <h3 class="text-white font-bold mb-2"><?= __('core_feat2_title') ?></h3>
                    <p class="text-zinc-500 text-sm"><?= __('core_feat2_desc') ?></p>
                </div>
                <!-- Feature 3 -->
                <div class="bg-black border border-zinc-800 p-6 rounded-2xl hover:border-cyan-500/50 transition duration-500 group">
                    <div class="w-10 h-10 bg-cyan-900/30 rounded-lg flex items-center justify-center text-cyan-400 mb-4 group-hover:scale-110 transition">
                        <i class="fa-solid fa-lock-open"></i>
                    </div>
                    <h3 class="text-white font-bold mb-2"><?= __('core_feat3_title') ?></h3>
                    <p class="text-zinc-500 text-sm"><?= __('core_feat3_desc') ?></p>
                </div>
                <!-- Feature 4 -->
                <div class="bg-black border border-zinc-800 p-6 rounded-2xl hover:border-cyan-500/50 transition duration-500 group">
                    <div class="w-10 h-10 bg-cyan-900/30 rounded-lg flex items-center justify-center text-cyan-400 mb-4 group-hover:scale-110 transition">
                        <i class="fa-solid fa-server"></i>
                    </div>
                    <h3 class="text-white font-bold mb-2"><?= __('core_feat4_title') ?></h3>
                    <p class="text-zinc-500 text-sm"><?= __('core_feat4_desc') ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works -->
    <section id="how-it-works" class="py-24 bg-zinc-900/30 border-y border-white/5">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4"><?= __('sec_start_teaching') ?></h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 relative">
                <!-- Connector Line -->
                <div class="hidden md:block absolute top-12 left-0 w-full h-0.5 bg-zinc-800 -z-10 rtl:left-auto rtl:right-0"></div>

                <!-- Step 1 -->
                <div class="text-center relative">
                    <div class="w-24 h-24 bg-surface border border-zinc-700 rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl relative z-10">
                        <span class="text-3xl font-bold text-white">1</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2"><?= __('how_step1_title') ?></h3>
                    <p class="text-zinc-400 text-sm"><?= __('how_step1_desc') ?></p>
                </div>

                <!-- Step 2 -->
                <div class="text-center relative">
                    <div class="w-24 h-24 bg-surface border border-zinc-700 rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl relative z-10">
                        <span class="text-3xl font-bold text-white">2</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2"><?= __('how_step2_title') ?></h3>
                    <p class="text-zinc-400 text-sm"><?= __('how_step2_desc') ?></p>
                </div>

                <!-- Step 3 -->
                <div class="text-center relative">
                    <div class="w-24 h-24 bg-surface border border-zinc-700 rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl relative z-10">
                        <span class="text-3xl font-bold text-white">3</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2"><?= __('how_step3_title') ?></h3>
                    <p class="text-zinc-400 text-sm"><?= __('how_step3_desc') ?></p>
                </div>

                <!-- Step 4 -->
                <div class="text-center relative">
                    <div class="w-24 h-24 bg-surface border border-zinc-700 rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl relative z-10">
                        <span class="text-3xl font-bold text-white">4</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2"><?= __('how_step4_title') ?></h3>
                    <p class="text-zinc-400 text-sm"><?= __('how_step4_desc') ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Strengths by Numbers -->
    <section class="py-24 bg-zinc-950 relative border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-white mb-4"><?= __('sec_why') ?></h2>
                <p class="text-zinc-400"><?= __('sec_why_desc') ?></p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Card 1 -->
                <div x-data="{ hover: false }" @mouseenter="hover = true" @mouseleave="hover = false" class="bg-zinc-900 border border-zinc-800 rounded-2xl p-8 text-center cursor-pointer transition transform hover:-translate-y-2 hover:border-indigo-500/50 hover:shadow-2xl hover:shadow-indigo-500/10">
                    <div class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-violet-400 mb-2">0%</div>
                    <h3 class="text-white font-bold mb-2"><?= __('why_rev_title') ?></h3>
                    <p class="text-zinc-500 text-sm"><?= __('why_rev_desc') ?></p>
                </div>

                <!-- Card 2 -->
                <div x-data="{ hover: false }" @mouseenter="hover = true" @mouseleave="hover = false" class="bg-zinc-900 border border-zinc-800 rounded-2xl p-8 text-center cursor-pointer transition transform hover:-translate-y-2 hover:border-emerald-500/50 hover:shadow-2xl hover:shadow-emerald-500/10">
                    <div class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-400 mb-2">100%</div>
                    <h3 class="text-white font-bold mb-2"><?= __('why_open_title') ?></h3>
                    <p class="text-zinc-500 text-sm"><?= __('why_open_desc') ?></p>
                </div>

                <!-- Card 3 -->
                <div x-data="{ hover: false }" @mouseenter="hover = true" @mouseleave="hover = false" class="bg-zinc-900 border border-zinc-800 rounded-2xl p-8 text-center cursor-pointer transition transform hover:-translate-y-2 hover:border-cyan-500/50 hover:shadow-2xl hover:shadow-cyan-500/10">
                    <div class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-400 mb-2">&lt;50ms</div>
                    <h3 class="text-white font-bold mb-2"><?= __('why_latency_title') ?></h3>
                    <p class="text-zinc-500 text-sm"><?= __('why_latency_desc') ?></p>
                </div>

                <!-- Card 4 -->
                <div x-data="{ hover: false }" @mouseenter="hover = true" @mouseleave="hover = false" class="bg-zinc-900 border border-zinc-800 rounded-2xl p-8 text-center cursor-pointer transition transform hover:-translate-y-2 hover:border-fuchsia-500/50 hover:shadow-2xl hover:shadow-fuchsia-500/10">
                    <div class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-fuchsia-400 to-pink-400 mb-2">∞</div>
                    <h3 class="text-white font-bold mb-2"><?= __('why_scale_title') ?></h3>
                    <p class="text-zinc-500 text-sm"><?= __('why_scale_desc') ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Roadmap & Capabilities -->
    <section class="py-24 bg-background border-t border-white/5 relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20"></div>
        
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="text-center mb-20">
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">Built for Today. <span class="gradient-text">Ready for Tomorrow.</span></h2>
                <p class="text-zinc-400 text-lg max-w-2xl mx-auto">
                    <?= __('sec_coming_soon_desc') ?>
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                
                <!-- Live Capabilities (Left - 7 cols) -->
                <div class="lg:col-span-7 space-y-8">
                    <h3 class="text-2xl font-bold text-white flex items-center">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full mr-3 shadow-[0_0_10px_#10b981]"></span>
                        <?= __('sec_live') ?>
                    </h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Card: Core -->
                        <div class="bg-zinc-900/50 border border-zinc-800 p-5 rounded-2xl hover:bg-zinc-900 transition">
                            <div class="flex items-center mb-3 text-indigo-400">
                                <i class="fa-solid fa-microchip mr-2"></i> <span class="font-bold text-sm uppercase tracking-wider"><?= __('roadmap_feat_core') ?></span>
                            </div>
                            <ul class="space-y-2 text-sm text-zinc-400">
                                <li class="flex items-center"><i class="fa-solid fa-check text-emerald-500 mr-2 text-xs rtl:ml-2 rtl:mr-0"></i> <?= __('roadmap_feat_core_1') ?></li>
                                <li class="flex items-center"><i class="fa-solid fa-check text-emerald-500 mr-2 text-xs rtl:ml-2 rtl:mr-0"></i> <?= __('roadmap_feat_core_2') ?></li>
                                <li class="flex items-center"><i class="fa-solid fa-check text-emerald-500 mr-2 text-xs rtl:ml-2 rtl:mr-0"></i> <?= __('roadmap_feat_core_3') ?></li>
                            </ul>
                        </div>

                        <!-- Card: Assessment -->
                        <div class="bg-zinc-900/50 border border-zinc-800 p-5 rounded-2xl hover:bg-zinc-900 transition">
                            <div class="flex items-center mb-3 text-fuchsia-400">
                                <i class="fa-solid fa-brain mr-2"></i> <span class="font-bold text-sm uppercase tracking-wider"><?= __('roadmap_feat_assess') ?></span>
                            </div>
                            <ul class="space-y-2 text-sm text-zinc-400">
                                <li class="flex items-center"><i class="fa-solid fa-check text-emerald-500 mr-2 text-xs rtl:ml-2 rtl:mr-0"></i> <?= __('roadmap_feat_assess_1') ?></li>
                                <li class="flex items-center"><i class="fa-solid fa-check text-emerald-500 mr-2 text-xs rtl:ml-2 rtl:mr-0"></i> <?= __('roadmap_feat_assess_2') ?></li>
                                <li class="flex items-center"><i class="fa-solid fa-check text-emerald-500 mr-2 text-xs rtl:ml-2 rtl:mr-0"></i> <?= __('roadmap_feat_assess_3') ?></li>
                            </ul>
                        </div>

                        <!-- Card: Commerce -->
                        <div class="bg-zinc-900/50 border border-zinc-800 p-5 rounded-2xl hover:bg-zinc-900 transition">
                            <div class="flex items-center mb-3 text-emerald-400">
                                <i class="fa-solid fa-sack-dollar mr-2"></i> <span class="font-bold text-sm uppercase tracking-wider"><?= __('roadmap_feat_comm') ?></span>
                            </div>
                            <ul class="space-y-2 text-sm text-zinc-400">
                                <li class="flex items-center"><i class="fa-solid fa-check text-emerald-500 mr-2 text-xs rtl:ml-2 rtl:mr-0"></i> <?= __('roadmap_feat_comm_1') ?></li>
                                <li class="flex items-center"><i class="fa-solid fa-check text-emerald-500 mr-2 text-xs rtl:ml-2 rtl:mr-0"></i> <?= __('roadmap_feat_comm_2') ?></li>
                                <li class="flex items-center"><i class="fa-solid fa-check text-emerald-500 mr-2 text-xs rtl:ml-2 rtl:mr-0"></i> <?= __('roadmap_feat_comm_3') ?></li>
                            </ul>
                        </div>

                        <!-- Card: Experience -->
                        <div class="bg-zinc-900/50 border border-zinc-800 p-5 rounded-2xl hover:bg-zinc-900 transition">
                            <div class="flex items-center mb-3 text-amber-400">
                                <i class="fa-solid fa-user-graduate mr-2"></i> <span class="font-bold text-sm uppercase tracking-wider"><?= __('roadmap_feat_exp') ?></span>
                            </div>
                            <ul class="space-y-2 text-sm text-zinc-400">
                                <li class="flex items-center"><i class="fa-solid fa-check text-emerald-500 mr-2 text-xs rtl:ml-2 rtl:mr-0"></i> <?= __('roadmap_feat_exp_1') ?></li>
                                <li class="flex items-center"><i class="fa-solid fa-check text-emerald-500 mr-2 text-xs rtl:ml-2 rtl:mr-0"></i> <?= __('roadmap_feat_exp_2') ?></li>
                                <li class="flex items-center"><i class="fa-solid fa-check text-emerald-500 mr-2 text-xs rtl:ml-2 rtl:mr-0"></i> <?= __('roadmap_feat_exp_3') ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Roadmap (Right - 5 cols) -->
                <div class="lg:col-span-5 space-y-8">
                    <h3 class="text-2xl font-bold text-white flex items-center">
                        <i class="fa-solid fa-rocket mr-3 rtl:ml-3 rtl:mr-0 text-indigo-500"></i>
                        <?= __('sec_coming_soon') ?>
                    </h3>

                    <div class="space-y-4 relative before:absolute before:inset-y-0 before:left-8 before:w-0.5 before:bg-zinc-800 rtl:before:left-auto rtl:before:right-8">
                        
                        <!-- Item 1 -->
                        <div class="relative pl-20 rtl:pl-0 rtl:pr-20 group">
                            <div class="absolute left-6 rtl:left-auto rtl:right-6 top-1 w-4 h-4 bg-zinc-900 border-2 border-indigo-500 rounded-full z-10 group-hover:scale-125 transition"></div>
                            <div class="bg-zinc-900 border border-zinc-800 p-4 rounded-xl hover:border-indigo-500/50 transition">
                                <div class="flex justify-between items-start mb-1">
                                    <h4 class="text-white font-bold"><?= __('roadmap_future_1_title') ?></h4>
                                    <span class="text-[10px] bg-indigo-500/10 text-indigo-400 px-2 py-0.5 rounded border border-indigo-500/20 font-bold"><?= __('roadmap_future_1_tag') ?></span>
                                </div>
                                <p class="text-xs text-zinc-400"><?= __('roadmap_future_1_desc') ?></p>
                            </div>
                        </div>

                        <!-- Item 2 -->
                        <div class="relative pl-20 rtl:pl-0 rtl:pr-20 group">
                            <div class="absolute left-6 rtl:left-auto rtl:right-6 top-1 w-4 h-4 bg-zinc-900 border-2 border-blue-500 rounded-full z-10 group-hover:scale-125 transition"></div>
                            <div class="bg-zinc-900 border border-zinc-800 p-4 rounded-xl hover:border-blue-500/50 transition">
                                <div class="flex justify-between items-start mb-1">
                                    <h4 class="text-white font-bold"><?= __('roadmap_future_2_title') ?></h4>
                                    <span class="text-[10px] bg-blue-500/10 text-blue-400 px-2 py-0.5 rounded border border-blue-500/20 font-bold"><?= __('roadmap_future_2_tag') ?></span>
                                </div>
                                <p class="text-xs text-zinc-400"><?= __('roadmap_future_2_desc') ?></p>
                            </div>
                        </div>

                        <!-- Item 3 -->
                        <div class="relative pl-20 rtl:pl-0 rtl:pr-20 group">
                            <div class="absolute left-6 rtl:left-auto rtl:right-6 top-1 w-4 h-4 bg-zinc-900 border-2 border-zinc-600 rounded-full z-10 group-hover:scale-125 transition"></div>
                            <div class="bg-zinc-900 border border-zinc-800 p-4 rounded-xl hover:border-zinc-600 transition">
                                <div class="flex justify-between items-start mb-1">
                                    <h4 class="text-white font-bold"><?= __('roadmap_future_3_title') ?></h4>
                                    <span class="text-[10px] bg-zinc-800 text-zinc-400 px-2 py-0.5 rounded border border-zinc-700 font-bold"><?= __('roadmap_future_3_tag') ?></span>
                                </div>
                                <p class="text-xs text-zinc-400"><?= __('roadmap_future_3_desc') ?></p>
                            </div>
                        </div>

                        <!-- Item 4 -->
                        <div class="relative pl-20 rtl:pl-0 rtl:pr-20 group">
                            <div class="absolute left-6 rtl:left-auto rtl:right-6 top-1 w-4 h-4 bg-zinc-900 border-2 border-zinc-600 rounded-full z-10 group-hover:scale-125 transition"></div>
                            <div class="bg-zinc-900 border border-zinc-800 p-4 rounded-xl hover:border-zinc-600 transition">
                                <div class="flex justify-between items-start mb-1">
                                    <h4 class="text-white font-bold"><?= __('roadmap_future_4_title') ?></h4>
                                    <span class="text-[10px] bg-zinc-800 text-zinc-400 px-2 py-0.5 rounded border border-zinc-700 font-bold"><?= __('roadmap_future_4_tag') ?></span>
                                </div>
                                <p class="text-xs text-zinc-400"><?= __('roadmap_future_4_desc') ?></p>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/layouts/public.php'; 
?>