<?php 
    $lockedLessonIds = $lockedLessonIds ?? [];
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($currentLesson['title']) ?> - <?= htmlspecialchars($course['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://www.youtube.com/iframe_api"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Rubik', 'RB', 'sans-serif'],
                    },
                    colors: {
                        background: '#09090b', // zinc-950
                        surface: '#18181b', // zinc-900
                        active: '#27272a', // zinc-800
                        primary: '#3b82f6', // blue-500
                    }
                }
            }
        }
    </script>
    <meta name="csrf-token" content="<?= $csrf_token ?? '' ?>">
    <script>
        window.CSRF_TOKEN = <?= json_encode($csrf_token ?? '') ?>;
        (function() {
            const originalFetch = window.fetch;
            if (!originalFetch) return;
            window.fetch = function(input, init) {
                const opts = init || {};
                const method = (opts.method || 'GET').toUpperCase();
                if (method !== 'GET' && method !== 'HEAD') {
                    if (!opts.headers) {
                        opts.headers = {};
                    }
                    if (opts.headers instanceof Headers) {
                        if (!opts.headers.has('X-CSRF-TOKEN')) {
                            opts.headers.set('X-CSRF-TOKEN', window.CSRF_TOKEN || '');
                        }
                    } else if (!('X-CSRF-TOKEN' in opts.headers)) {
                        opts.headers['X-CSRF-TOKEN'] = window.CSRF_TOKEN || '';
                    }
                }
                return originalFetch(input, opts);
            };
        })();
    </script>
    <style>
        @font-face { font-family: 'RB'; src: url('/assets/fonts/RB REGULAR.TTF') format('truetype'); font-weight: 400; font-style: normal; }
        @font-face { font-family: 'RB'; src: url('/assets/fonts/RB BOLD.TTF') format('truetype'); font-weight: 700; font-style: normal; }
        @font-face { font-family: 'RB'; src: url('/assets/fonts/RB LIGHT.TTF') format('truetype'); font-weight: 300; font-style: normal; }

        body { background-color: #09090b; color: #e4e4e7; }
        body.page-transition > :not(#page-transition) { opacity: 0; transform: translateY(8px); transition: opacity 320ms ease, transform 320ms ease; }
        body.page-ready > :not(#page-transition) { opacity: 1; transform: none; }
        body.page-exit > :not(#page-transition) { opacity: 0; transform: translateY(8px); }
        .page-transition-overlay { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; background: radial-gradient(80% 80% at 50% 40%, rgba(88, 28, 135, 0.25), rgba(9, 9, 11, 0.95)); opacity: 0; pointer-events: none; transition: opacity 240ms ease; z-index: 9999; }
        .page-transition-overlay.is-active { opacity: 1; pointer-events: auto; }
        .page-loader { position: relative; width: 64px; height: 64px; }
        .page-loader::before { content: ""; position: absolute; inset: 0; border-radius: 50%; background: conic-gradient(from 180deg, rgba(139, 92, 246, 0) 0deg, rgba(139, 92, 246, 0.95) 110deg, rgba(99, 102, 241, 0.9) 220deg, rgba(59, 130, 246, 0) 360deg); filter: drop-shadow(0 0 12px rgba(139, 92, 246, 0.5)); animation: spin 900ms linear infinite; }
        .page-loader::after { content: ""; position: absolute; inset: 10px; border-radius: 50%; background: radial-gradient(circle at 35% 35%, rgba(255, 255, 255, 0.9), rgba(99, 102, 241, 0.35), rgba(9, 9, 11, 0) 70%); border: 1px solid rgba(139, 92, 246, 0.4); animation: pulse 1200ms ease-in-out infinite; }
        .modern-loader { position: relative; width: 18px; height: 18px; display: inline-block; }
        .modern-loader::before { content: ""; position: absolute; inset: 0; border-radius: 50%; background: conic-gradient(from 180deg, rgba(139, 92, 246, 0) 0deg, rgba(139, 92, 246, 0.9) 120deg, rgba(99, 102, 241, 0.7) 240deg, rgba(59, 130, 246, 0) 360deg); animation: spin 800ms linear infinite; }
        .modern-loader::after { content: ""; position: absolute; inset: 4px; border-radius: 50%; background: radial-gradient(circle at 35% 35%, rgba(255, 255, 255, 0.8), rgba(99, 102, 241, 0.25), rgba(9, 9, 11, 0) 70%); }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes pulse { 0%, 100% { transform: scale(0.92); opacity: 0.7; } 50% { transform: scale(1); opacity: 1; } }
        [dir="rtl"] { font-family: 'RB', sans-serif; }
        .playlist-scroll::-webkit-scrollbar { width: 6px; }
        .playlist-scroll::-webkit-scrollbar-track { background: #18181b; }
        .playlist-scroll::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 3px; }
        
        /* Smooth Width Transition */
        .sidebar-transition {
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body class="bg-black font-sans h-[100dvh] flex flex-col overflow-hidden page-transition" x-data="{ playlistOpen: false, desktopSidebarOpen: true }">

    <div id="page-transition" class="page-transition-overlay" aria-hidden="true">
        <div class="page-loader" role="status" aria-label="Loading"></div>
    </div>
    <script>
        (function() {
            const overlay = document.getElementById('page-transition');
            const showOverlay = () => {
                if (overlay) overlay.classList.add('is-active');
                document.body.classList.add('page-exit');
            };
            const markReady = () => {
                document.body.classList.remove('page-exit');
                document.body.classList.add('page-ready');
                if (overlay) overlay.classList.remove('is-active');
            };
            document.addEventListener('DOMContentLoaded', markReady);
            window.addEventListener('pageshow', () => {
                markReady();
            });
            setTimeout(markReady, 1200);
            document.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (!link) return;
                if (link.target === '_blank' || link.hasAttribute('download')) return;
                const href = link.getAttribute('href') || '';
                if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) return;
                if (link.dataset.noTransition !== undefined) return;
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                const url = new URL(href, window.location.href);
                if (url.origin !== window.location.origin) return;
                showOverlay();
            }, true);
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (!form || form.dataset.noTransition !== undefined) return;
                showOverlay();
            }, true);
        })();
    </script>

    <!-- ================= HEADER (Desktop & Mobile) ================= -->
    <header class="h-16 bg-background border-b border-zinc-800 flex items-center justify-between px-4 md:px-6 z-30 flex-shrink-0">
        
        <!-- Left: Branding & Toggle -->
        <div class="flex items-center space-x-4 min-w-0">
            <!-- Mobile Menu Toggle -->
            <button @click="playlistOpen = true" class="md:hidden w-10 h-10 bg-gradient-to-br from-indigo-600 to-violet-600 text-white rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30 active:scale-95 transition">
                <i class="fa-solid fa-bars"></i>
            </button>

            <!-- Desktop Back & Toggle -->
            <div class="hidden md:flex items-center space-x-3">
                <a href="/dashboard" class="w-8 h-8 rounded-lg bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400 hover:text-white transition" title="Back to Dashboard">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                
                <!-- Desktop Sidebar Toggle -->
                <button @click="desktopSidebarOpen = !desktopSidebarOpen" class="w-8 h-8 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-white flex items-center justify-center transition" title="Toggle Sidebar">
                    <i class="fa-solid" :class="desktopSidebarOpen ? 'fa-expand' : 'fa-list'"></i>
                </button>
            </div>

            <span class="text-zinc-600 flex-shrink-0 hidden md:inline">|</span>
            
            <!-- Lesson Title -->
            <div class="flex items-center space-x-2 min-w-0">
                <h1 class="font-bold text-white text-sm md:text-base truncate"><?= htmlspecialchars($currentLesson['title']) ?></h1>
                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $course['instructor_id']): ?>
                                    <span class="text-zinc-600 flex-shrink-0">|</span>
                                    <div class="flex items-center space-x-2 flex-shrink-0">
                                        <a href="/instructor/course/<?= $course['id'] ?>/edit" class="px-3 py-1.5 bg-zinc-800 hover:bg-zinc-700 text-white rounded-lg text-xs font-bold transition flex items-center border border-zinc-700">
                                            <i class="fa-solid fa-pen-to-square mr-2 text-indigo-400"></i> Edit Course
                                        </a>
                                        
                                                                <?php if ($currentLesson['type'] === 'quiz'): ?>
                                        
                                                                    <a href="/instructor/lesson/<?= $currentLesson['id'] ?>/quiz" class="px-3 py-1.5 bg-purple-600/20 hover:bg-purple-600/30 text-purple-300 rounded-lg text-xs font-bold transition flex items-center border border-purple-500/30">
                                        
                                                                        <i class="fa-solid fa-list-check mr-2"></i> Edit Quiz
                                        
                                                                    </a>
                                        
                                                                <?php else: ?>
                                        
                                                                    <a href="/instructor/lesson/<?= $currentLesson['id'] ?>/edit" class="px-3 py-1.5 bg-zinc-800 hover:bg-zinc-700 text-white rounded-lg text-xs font-bold transition flex items-center border border-zinc-700">
                                        
                                                                        <i class="fa-solid fa-pencil mr-2 text-indigo-400"></i> Edit Lesson
                                        
                                                                    </a>
                                        
                                                                <?php endif; ?>
                                        
                                        
                                    </div>
                                <?php endif; ?>            </div>
        </div>

        <!-- Right: Navigation -->
        <div class="flex items-center space-x-2 md:space-x-3">
             <?php if ($prevLesson): ?>
                <a href="/learn/<?= $course['id'] ?>/lesson/<?= $prevLesson['id'] ?>" class="hidden md:flex px-3 py-2 text-xs font-bold text-zinc-400 hover:text-white border border-zinc-800 rounded-xl transition items-center">
                    <i class="fa-solid fa-chevron-left mr-1.5"></i> Prev
                </a>
            <?php endif; ?>

            <?php if ($nextLesson): ?>
                <a href="/learn/<?= $course['id'] ?>/lesson/<?= $nextLesson['id'] ?>" class="px-4 py-2 text-xs bg-zinc-800 hover:bg-zinc-700 text-white rounded-xl transition flex items-center font-bold">
                    <span class="hidden sm:inline">Next</span> <i class="fa-solid fa-chevron-right ml-1.5"></i>
                </a>
            <?php else: ?>
                <a href="/dashboard" class="px-4 py-2 text-xs bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl transition font-bold flex items-center">
                    <span class="hidden sm:inline">Finish</span> <i class="fa-solid fa-flag-checkered ml-1.5"></i>
                </a>
            <?php endif; ?>
        </div>
    </header>


    <!-- ================= CONTENT AREA (Flex Container) ================= -->
    <div class="flex-1 flex overflow-hidden relative">

        <!-- 1. MAIN STAGE (Video/Article) -->
        <main class="flex-1 bg-black flex flex-col relative overflow-y-auto w-full transition-all duration-300">
            
        <!-- Player Area -->
        <div class="flex-1 bg-black flex flex-col items-center relative group overflow-y-auto w-full pt-8">
            <?php if ($currentLesson['type'] === 'video'): ?>
                <div class="w-full max-w-5xl px-4 flex flex-col gap-6">
                    <div class="aspect-video bg-zinc-900 rounded-xl overflow-hidden shadow-2xl border border-zinc-800 relative">
                        <?php 
                            $url = $currentLesson['content_url'];
                            $isYoutube = str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be');
                            $youtubeId = '';
                            if ($isYoutube) {
                                if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
                                    $youtubeId = $match[1];
                                }
                            }
                        ?>

                        <?php if ($isYoutube): ?>
                            <div id="youtube-player" class="w-full h-full" data-video-id="<?= $youtubeId ?>"></div>
                        <?php else: ?>
                            <video id="course-video" class="w-full h-full outline-none" controls autoplay>
                                <source src="<?= htmlspecialchars($currentLesson['content_url']) ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php endif; ?>
                    </div>
                    
                    <?php $isCurrentCompleted = in_array($currentLesson['id'], $completedLessonIds ?? []); ?>
                    <div class="flex justify-end pb-12">
                        <button id="mark-complete-btn"
                                class="bg-zinc-800 hover:bg-zinc-700 text-white px-6 py-3 rounded-xl font-bold transition flex items-center border border-zinc-700 <?= $isCurrentCompleted ? 'opacity-50 cursor-not-allowed' : '' ?>"
                                <?= $isCurrentCompleted ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-check mr-2 text-emerald-500"></i>
                            <?= $isCurrentCompleted ? 'Completed' : 'Mark as Complete' ?>
                        </button>
                    </div>
                </div>
            
            <!-- Article Reader -->
            <?php elseif ($currentLesson['type'] === 'article'): ?>
                <div class="w-full max-w-4xl mx-auto p-6 md:p-12 h-full">
                    <div class="prose prose-invert prose-indigo max-w-none prose-sm md:prose-base lg:prose-lg">
                        <?= \App\Core\Markdown::parse($currentLesson['content_text']) ?>
                    </div>
                    <?php $isCurrentCompleted = in_array($currentLesson['id'], $completedLessonIds ?? []); ?>
                    <div class="mt-16 border-t border-zinc-800/50 pt-10 text-center pb-20">
                        <button id="mark-complete-btn"
                                class="bg-indigo-600 hover:bg-indigo-500 text-white px-10 py-4 rounded-2xl font-bold transition shadow-lg shadow-indigo-500/20 transform hover:-translate-y-1 <?= $isCurrentCompleted ? 'opacity-50 cursor-not-allowed' : '' ?>"
                                <?= $isCurrentCompleted ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-check-double mr-2"></i> <?= $isCurrentCompleted ? 'Completed' : 'Complete Lesson' ?>
                        </button>
                    </div>
                </div>

            <!-- Quiz Result -->
            <?php elseif (isset($quizResult)): ?>
                <div class="w-full max-w-2xl mx-auto p-8 text-center flex flex-col justify-center h-full">
                    <?php if (($quizResult['grading_status'] ?? 'final') === 'pending'): ?>
                        <div class="w-24 h-24 bg-amber-500/20 text-amber-400 rounded-full flex items-center justify-center mx-auto mb-6 text-5xl border-4 border-amber-500/30">
                            <i class="fa-solid fa-hourglass-half"></i>
                        </div>
                        <h1 class="text-3xl font-bold text-white mb-2">Pending Review</h1>
                        <p class="text-zinc-400 mb-8">Your submission is being reviewed. Scores will update soon.</p>
                    <?php elseif ($quizResult['is_passed']): ?>
                        <div class="w-24 h-24 bg-green-500/20 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 text-5xl border-4 border-green-500/30 shadow-lg shadow-green-500/20 animate-bounce">
                            <i class="fa-solid fa-trophy"></i>
                        </div>
                        <h1 class="text-4xl font-bold text-white mb-2">Excellent!</h1>
                        <p class="text-zinc-400 mb-8 text-lg">You passed the exam.</p>
                    <?php else: ?>
                        <div class="w-24 h-24 bg-red-500/20 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6 text-5xl border-4 border-red-500/30">
                            <i class="fa-solid fa-xmark"></i>
                        </div>
                        <h1 class="text-3xl font-bold text-white mb-2">Keep Trying!</h1>
                        <p class="text-zinc-400 mb-8">You didn't meet the passing score.</p>
                    <?php endif; ?>

                    <div class="mb-10 bg-zinc-900/50 p-8 rounded-3xl border border-zinc-800">
                        <div class="text-6xl font-black text-white tracking-tighter"><?= $quizResult['score'] ?>%</div>
                        <div class="text-sm text-zinc-500 uppercase tracking-widest mt-2 font-bold">
                            <?= ($quizResult['grading_status'] ?? 'final') === 'pending' ? 'Provisional Score' : 'Total Score' ?>
                        </div>
                    </div>

                    <!-- Review Section -->
                    <?php if (!empty($reviewData) && ($quizResult['grading_status'] ?? 'final') !== 'pending'): ?>
                        <div class="mt-12 w-full bg-zinc-900 border border-zinc-800 rounded-3xl p-8 shadow-2xl relative overflow-hidden text-left" 
                             x-data="{ reviewIndex: 0, total: <?= count($reviewData) ?> }">
                            
                            <h3 class="text-xl font-bold text-white mb-6 flex items-center border-b border-zinc-800 pb-4">
                                <i class="fa-solid fa-list-check mr-3 text-indigo-500"></i> Review Answers
                                <span class="ml-auto text-sm font-mono text-zinc-500">Q<span x-text="reviewIndex + 1"></span> / <span x-text="total"></span></span>
                            </h3>

                            <?php foreach ($reviewData as $index => $q): ?>
                                <div x-show="reviewIndex === <?= $index ?>" x-transition.opacity class="space-y-6">
                                    <div class="mb-4">
                                        <h4 class="text-lg font-medium text-white leading-relaxed"><?= htmlspecialchars($q['question_text']) ?></h4>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <?php if ($q['type'] === 'true_false'): ?>
                                            <?php 
                                                $userAns = $q['user_answer'];
                                                $correctAns = $q['correct_answers'][0];
                                                $map = [1 => 'True', 0 => 'False'];
                                            ?>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="p-4 rounded-xl border-2 text-center font-bold
                                                    <?= $userAns == 1 ? ($userAns == $correctAns ? 'border-green-500 bg-green-500/10 text-green-400' : 'border-red-500 bg-red-500/10 text-red-400') : ($correctAns == 1 ? 'border-green-500 border-dashed text-green-500 opacity-70' : 'border-zinc-700 bg-zinc-800 text-zinc-500') ?>">
                                                    True
                                                </div>
                                                <div class="p-4 rounded-xl border-2 text-center font-bold
                                                    <?= $userAns === 0 ? ($userAns == $correctAns ? 'border-green-500 bg-green-500/10 text-green-400' : 'border-red-500 bg-red-500/10 text-red-400') : ($correctAns === 0 ? 'border-green-500 border-dashed text-green-500 opacity-70' : 'border-zinc-700 bg-zinc-800 text-zinc-500') ?>">
                                                    False
                                                </div>
                                            </div>

                                        <?php elseif (in_array($q['type'], ['single_choice', 'multi_choice'])): ?>
                                            <?php foreach ($q['options'] as $opt): ?>
                                                <?php 
                                                    $isUserSelected = is_array($q['user_answer']) ? in_array($opt['id'], $q['user_answer']) : $q['user_answer'] == $opt['id'];
                                                    $isCorrect = in_array($opt['id'], $q['correct_answers']);
                                                ?>
                                                <div class="flex items-center p-4 rounded-xl border-2 transition
                                                    <?= $isUserSelected 
                                                        ? ($isCorrect ? 'border-green-500 bg-green-500/10' : 'border-red-500 bg-red-500/10') 
                                                        : ($isCorrect ? 'border-green-500 border-dashed bg-green-500/5' : 'border-zinc-800 bg-zinc-900/50') ?>">
                                                    
                                                    <div class="w-6 h-6 rounded-full flex items-center justify-center mr-4 border 
                                                        <?= $isUserSelected ? ($isCorrect ? 'border-green-500 bg-green-500 text-white' : 'border-red-500 bg-red-500 text-white') : 'border-zinc-600' ?>">
                                                        <?php if ($isUserSelected): ?>
                                                            <i class="fa-solid <?= $isCorrect ? 'fa-check' : 'fa-xmark' ?> text-xs"></i>
                                                        <?php elseif ($isCorrect): ?>
                                                            <i class="fa-solid fa-check text-green-500 text-xs"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <span class="<?= $isUserSelected ? ($isCorrect ? 'text-green-300' : 'text-red-300') : ($isCorrect ? 'text-green-500' : 'text-zinc-400') ?>">
                                                        <?= htmlspecialchars($opt['text']) ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($q['explanation'])): ?>
                                        <div class="mt-4 p-4 bg-blue-500/10 border-l-4 border-blue-500 text-blue-200 text-sm rounded-r-lg">
                                            <strong>Explanation:</strong> <?= htmlspecialchars($q['explanation']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <!-- Slider Navigation -->
                            <div class="flex justify-between items-center mt-8 pt-6 border-t border-zinc-800">
                                <button @click="reviewIndex > 0 ? reviewIndex-- : null" 
                                        :class="reviewIndex === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-zinc-800'"
                                        class="px-4 py-2 rounded-lg text-zinc-400 transition font-bold flex items-center">
                                    <i class="fa-solid fa-chevron-left mr-2"></i> Prev
                                </button>
                                <button @click="reviewIndex < total - 1 ? reviewIndex++ : null" 
                                        :class="reviewIndex === total - 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-zinc-800'"
                                        class="px-4 py-2 rounded-lg text-zinc-400 transition font-bold flex items-center">
                                    Next <i class="fa-solid fa-chevron-right ml-2"></i>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="space-y-4">
                        <?php if ($quizResult['show_correct_answers']): ?>
                            <a href="/learn/quiz/review/<?= $quizResult['id'] ?>" class="block w-full bg-zinc-800 border border-zinc-700 text-zinc-300 font-bold py-4 rounded-xl hover:text-white hover:bg-zinc-700 transition text-lg">
                                <i class="fa-solid fa-list-check mr-2"></i> Review Answers
                            </a>
                        <?php endif; ?>

                        <?php if ($quizResult['is_passed'] && $nextLesson): ?>
                            <a href="/learn/<?= $course['id'] ?>/lesson/<?= $nextLesson['id'] ?>" class="block w-full bg-white text-black font-bold py-4 rounded-xl hover:bg-zinc-200 transition text-lg shadow-lg shadow-white/10">Next Lesson <i class="fa-solid fa-arrow-right ml-2"></i></a>
                        <?php elseif ($quizResult['is_passed']): ?>
                            <a href="/dashboard" class="block w-full bg-green-600 text-white font-bold py-4 rounded-xl hover:bg-green-500 transition text-lg">Return to Dashboard</a>
                        <?php else: ?>
                            <a href="/learn/quiz/start/<?= $quizResult['quiz_id'] ?>" class="block w-full bg-white text-black font-bold py-4 rounded-xl hover:bg-zinc-200 transition text-lg"><i class="fa-solid fa-rotate-right mr-2"></i> Retake Exam</a>
                        <?php endif; ?>
                    </div>
                </div>

            <!-- Quiz Start / External -->
            <?php else: ?>
                <div class="text-center p-8 flex flex-col justify-center h-full">
                                          <?php if ($currentLesson['type'] === 'quiz' && isset($quizId)): ?>
                                             <i class="fa-solid fa-list-check text-6xl text-purple-500 mb-4"></i>
                                             <h2 class="text-2xl font-bold text-white mb-2"><?= htmlspecialchars($currentLesson['title']) ?></h2>
                                             <p class="text-zinc-400 mb-8">Test your knowledge with this exam.</p>
                                             
                                             <a href="/learn/quiz/intro/<?= $quizId ?>" class="bg-purple-600 hover:bg-purple-500 text-white px-8 py-4 rounded-xl font-bold text-lg transition shadow-lg shadow-purple-500/25 flex items-center justify-center mx-auto max-w-sm">
                                                 <i class="fa-solid fa-play mr-3"></i> View Exam Details
                                             </a>
                                          <?php else: ?>
                     
                        <i class="fa-solid fa-file-lines text-6xl text-zinc-700 mb-4"></i>
                        <h2 class="text-2xl font-bold text-white mb-2">External Content</h2>
                        <a href="<?= htmlspecialchars($currentLesson['content_url']) ?>" target="_blank" class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-3 rounded-lg font-medium transition inline-block mx-auto">Open Content</a>
                        <?php $isCurrentCompleted = in_array($currentLesson['id'], $completedLessonIds ?? []); ?>
                        <div class="mt-8">
                            <button id="mark-complete-btn"
                                    class="text-zinc-400 hover:text-white underline <?= $isCurrentCompleted ? 'opacity-50 cursor-not-allowed' : '' ?>"
                                    <?= $isCurrentCompleted ? 'disabled' : '' ?>>
                                <?= $isCurrentCompleted ? 'Completed' : 'Mark as Completed' ?>
                            </button>
                        </div>
                     <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Attachment Button -->
            <?php if (!empty($currentLesson['attachment_url'])): ?>
                <div class="absolute bottom-8 right-8 z-30">
                    <a href="<?= htmlspecialchars($currentLesson['attachment_url']) ?>" download class="flex items-center space-x-2 bg-zinc-800 hover:bg-zinc-700 text-white px-4 py-2.5 rounded-lg border border-zinc-700 shadow-xl transition text-sm font-medium">
                        <i class="fa-solid fa-paperclip text-blue-400"></i><span>Download Resources</span>
                    </a>
                </div>
            <?php endif; ?>
        </main>


        <!-- 2. DESKTOP SIDEBAR (Collapsible) -->
        <div class="hidden md:block sidebar-transition bg-surface border-l border-zinc-800 z-20 flex-shrink-0 h-full overflow-hidden"
             :style="'width: ' + (desktopSidebarOpen ? '20rem' : '0px')"
             x-data="{ activeTab: 'content' }">
            
            <div class="w-80 h-full flex flex-col">
                <!-- Tab Header -->
                <div class="flex border-b border-zinc-800 bg-surface">
                    <button @click="activeTab = 'content'" 
                            class="flex-1 py-4 text-xs font-bold uppercase tracking-wide transition border-b-2"
                            :class="activeTab === 'content' ? 'text-white border-indigo-500 bg-zinc-800/50' : 'text-zinc-500 border-transparent hover:text-zinc-300'">
                        Content
                    </button>
                    <button @click="activeTab = 'notes'" 
                            class="flex-1 py-4 text-xs font-bold uppercase tracking-wide transition border-b-2"
                            :class="activeTab === 'notes' ? 'text-white border-indigo-500 bg-zinc-800/50' : 'text-zinc-500 border-transparent hover:text-zinc-300'">
                        Notes
                    </button>
                </div>

                <!-- Content Tab -->
                <div x-show="activeTab === 'content'" class="flex-1 overflow-y-auto playlist-scroll">
                    <!-- Playlist Loop (Same Logic) -->
                    <?php foreach ($modules as $module): ?>
                        <div class="border-b border-zinc-800/50">
                            <div class="bg-zinc-800/30 px-4 py-3 text-xs font-bold text-zinc-500 uppercase"><?= htmlspecialchars($module['title']) ?></div>
                            <div>
                                <?php foreach ($module['lessons'] as $lesson): ?>
                                    <?php 
                                        $isActive = $lesson['id'] === $currentLesson['id'];
                                        $isCompleted = in_array($lesson['id'], $completedLessonIds);
                                        $isLocked = in_array($lesson['id'], $lockedLessonIds);
                                        
                                        $typeConfig = match($lesson['type']) {
                                            'video' => ['icon' => 'fa-circle-play', 'color' => 'text-indigo-400', 'bg' => 'bg-indigo-500/10', 'label' => 'Video'],
                                            'quiz' => ['icon' => 'fa-clipboard-question', 'color' => 'text-purple-400', 'bg' => 'bg-purple-500/10', 'label' => 'Quiz'],
                                            'article' => ['icon' => 'fa-file-lines', 'color' => 'text-emerald-400', 'bg' => 'bg-emerald-500/10', 'label' => 'Article'],
                                            default => ['icon' => 'fa-circle', 'color' => 'text-zinc-400', 'bg' => 'bg-zinc-800', 'label' => 'Lesson']
                                        };
                                        $durationMinutes = !empty($lesson['duration']) ? floor($lesson['duration'] / 60) : 0;
                                        $metaText = $typeConfig['label'] . ($durationMinutes > 0 ? ' • ' . $durationMinutes . ' mins' : '');
                                    ?>
                                    
                                    <?php if ($isLocked): ?>
                                        <div class="flex items-start space-x-3 px-4 py-3 text-sm border-l-2 border-transparent bg-zinc-900/30 opacity-50 cursor-not-allowed group relative" title="Complete previous lesson to unlock">
                                            <div class="mt-0.5 flex-shrink-0">
                                                <div class="w-5 h-5 rounded-full bg-zinc-800 text-zinc-600 flex items-center justify-center text-[10px]">
                                                    <i class="fa-solid fa-lock"></i>
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium truncate text-zinc-500"><?= htmlspecialchars($lesson['title']) ?></div>
                                                <div class="text-[10px] mt-0.5 font-medium text-zinc-600">Locked</div>
                                            </div>
                                            <!-- Tooltip on Hover -->
                                            <div class="absolute left-10 top-full mt-1 w-48 p-2 bg-black border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl z-50 hidden group-hover:block text-center">
                                                Complete previous lesson first
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <a href="/learn/<?= $course['id'] ?>/lesson/<?= $lesson['id'] ?>" 
                                           <?= $isActive ? 'id="active-lesson"' : '' ?>
                                           class="flex items-start space-x-3 px-4 py-3 text-sm transition border-l-2 group
                                           <?= $isActive ? 'border-indigo-500 bg-zinc-800/50' : 'border-transparent hover:bg-zinc-800/30' ?>">
                                            
                                            <!-- Status Icon -->
                                            <div class="mt-0.5 flex-shrink-0">
                                                <?php if ($isCompleted): ?><div class="w-5 h-5 rounded-full bg-green-500/20 text-green-500 flex items-center justify-center text-xs"><i class="fa-solid fa-check"></i></div>
                                                <?php elseif ($isActive): ?><div class="w-5 h-5 rounded-full <?= $typeConfig['bg'] ?> <?= $typeConfig['color'] ?> flex items-center justify-center text-xs animate-pulse"><i class="fa-solid <?= $typeConfig['icon'] ?>"></i></div>
                                                <?php else: ?><div class="w-5 h-5 rounded-full bg-zinc-800 text-zinc-600 flex items-center justify-center text-[10px] group-hover:text-zinc-400 transition"><i class="fa-solid <?= $typeConfig['icon'] ?>"></i></div><?php endif; ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium truncate <?= $isActive ? 'text-white' : ($isCompleted ? 'text-zinc-500 line-through decoration-zinc-700' : 'text-zinc-400 group-hover:text-zinc-200') ?>"><?= htmlspecialchars($lesson['title']) ?></div>
                                                <div class="text-[10px] mt-0.5 flex items-center space-x-2 font-medium <?= $isActive ? $typeConfig['color'] : 'text-zinc-600' ?>"><span><?= $metaText ?></span></div>
                                            </div>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Notes Tab -->
                <div x-show="activeTab === 'notes'" class="flex-1 flex flex-col h-full bg-zinc-900/30">
                    <!-- Notes Form -->
                    <div class="p-4 border-b border-zinc-800 bg-surface">
                        <form id="note-form" class="space-y-3">
                            <textarea id="note-content" rows="3" placeholder="Jot down a thought..." 
                                      class="w-full bg-zinc-950 border border-zinc-700 rounded-lg p-3 text-sm text-white placeholder-zinc-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition resize-none"></textarea>
                            <div class="flex justify-between items-center">
                                <button type="button" id="timestamp-btn" class="text-xs font-bold text-zinc-500 hover:text-indigo-400 transition flex items-center group">
                                    <i class="fa-regular fa-clock mr-1.5 group-hover:animate-pulse"></i> <span id="current-time-display">0:00</span>
                                </button>
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-1.5 rounded-lg text-xs font-bold transition shadow-lg shadow-indigo-500/20">
                                    Save Note
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Notes List -->
                    <div class="flex-1 overflow-y-auto playlist-scroll p-4 space-y-4" id="notes-list">
                        <!-- Content injected via JS -->
                        <div class="text-center py-8 text-zinc-600 text-xs">
                            <span class="modern-loader mr-2"></span> Loading notes...
                        </div>
                    </div>
                </div>
            </div>
    </div>


    <!-- 3. MOBILE SIDEBAR (Overlay) -->
    <div x-show="playlistOpen" class="relative z-[100] md:hidden" role="dialog" aria-modal="true">
        <div x-show="playlistOpen" x-transition.opacity class="fixed inset-0 bg-black/80" @click="playlistOpen = false"></div>
        <div x-show="playlistOpen" 
             x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="fixed inset-y-0 right-0 z-[101] w-full max-w-xs bg-surface shadow-xl flex flex-col">
             <div class="px-6 py-4 border-b border-zinc-800 flex items-center justify-between bg-surface">
                <h3 class="text-white font-bold">Course Content</h3>
                <button @click="playlistOpen = false" class="p-2 rounded-lg bg-zinc-800 text-zinc-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
             </div>
             <!-- Mobile Playlist (Same Logic) -->
             <div class="flex-1 overflow-y-auto playlist-scroll">
                 <!-- (Repeat loop logic for mobile or reuse shared partial if MVC supported it) -->
                 <!-- For simplicity in this single file view, duplicating the inner loop content logic -->
                 <?php foreach ($modules as $module): ?>
                    <div class="border-b border-zinc-800/50">
                        <div class="bg-zinc-800/30 px-4 py-3 text-xs font-bold text-zinc-500 uppercase"><?= htmlspecialchars($module['title']) ?></div>
                        <div>
                            <?php foreach ($module['lessons'] as $lesson): ?>
                                <!-- (Simplified render for mobile) -->
                                <a href="/learn/<?= $course['id'] ?>/lesson/<?= $lesson['id'] ?>" class="flex items-center space-x-3 px-4 py-3 text-sm border-l-2 border-transparent hover:bg-zinc-800/30">
                                    <div class="font-medium text-zinc-300 truncate"><?= htmlspecialchars($lesson['title']) ?></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
             </div>
        </div>
    </div>

    <!-- Tracker JS -->
    <script>
        const LESSON_ID = <?= $currentLesson['id'] ?>;
        const NEXT_LESSON_ID = <?= $nextLesson['id'] ?? 'null' ?>;
        const COURSE_ID = <?= $course['id'] ?>;
        
        // Auto-scroll playlist to active lesson
        document.addEventListener('DOMContentLoaded', () => {
            const active = document.getElementById('active-lesson');
            if (active) {
                active.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>
    <script src="/assets/js/player.min.js?v=<?= time() ?>"></script>
    <?php require __DIR__ . '/../partials/toast_notifications.php'; ?>
</body>
</html>
