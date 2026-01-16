<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'LMS Dashboard' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
    <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
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
                        background: '#0a0a0a',
                        surface: '#18181b', // zinc-900
                        card: '#27272a', // zinc-800
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

        body { background-color: #0a0a0a; color: #e2e8f0; }
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
        .bento-card { @apply bg-card rounded-2xl p-6 border border-zinc-700/50 shadow-lg hover:border-zinc-600 transition duration-300; }
        [dir="rtl"] { font-family: 'RB', sans-serif; }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col md:flex-row page-transition" x-data="{ sidebarOpen: false, sidebarCollapsed: false }">

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
            document.addEventListener('DOMContentLoaded', () => {
                document.body.classList.remove('page-exit');
                document.body.classList.add('page-ready');
                if (overlay) overlay.classList.remove('is-active');
            });
            window.addEventListener('pageshow', () => {
                document.body.classList.remove('page-exit');
                document.body.classList.add('page-ready');
                if (overlay) overlay.classList.remove('is-active');
            });
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
        })();
    </script>

    <!-- Flash Messages -->
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" class="fixed top-4 left-1/2 -translate-x-1/2 z-50 w-full max-w-md px-4">
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div x-show="show" x-transition class="bg-gradient-to-r from-purple-500/20 via-fuchsia-500/20 to-indigo-500/20 border border-purple-400/60 text-purple-100 px-4 py-3 rounded-xl shadow-lg backdrop-blur-sm relative overflow-hidden">
                <div class="absolute inset-0 pointer-events-none opacity-20"
                     style="background-image: linear-gradient(transparent 0, transparent 23px, rgba(255,255,255,0.08) 24px), linear-gradient(90deg, transparent 0, transparent 23px, rgba(255,255,255,0.08) 24px); background-size: 24px 24px;"></div>
                <?= $_SESSION['flash_success'] ?>
                <?php unset($_SESSION['flash_success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div x-show="show" x-transition class="bg-gradient-to-r from-purple-500/20 via-fuchsia-500/20 to-indigo-500/20 border border-purple-400/60 text-purple-100 px-4 py-3 rounded-xl shadow-lg backdrop-blur-sm relative overflow-hidden">
                <div class="absolute inset-0 pointer-events-none opacity-20"
                     style="background-image: linear-gradient(transparent 0, transparent 23px, rgba(255,255,255,0.08) 24px), linear-gradient(90deg, transparent 0, transparent 23px, rgba(255,255,255,0.08) 24px); background-size: 24px 24px;"></div>
                <?= $_SESSION['flash_error'] ?>
                <?php unset($_SESSION['flash_error']); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Mobile Header -->
    <div class="md:hidden flex items-center justify-between px-6 py-4 bg-black border-b border-zinc-800 z-40 sticky top-0">
        <a href="/" class="flex items-center space-x-2">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-lg">S</div>
            <span class="text-xl font-bold tracking-tight text-white">Scratch<span class="text-indigo-400">LMS</span></span>
        </a>
        <button @click="sidebarOpen = !sidebarOpen" class="text-zinc-400 hover:text-white">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>
    </div>

    <!-- Sidebar Backdrop (Mobile) -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition.opacity 
         class="fixed inset-0 bg-black/80 z-40 md:hidden"></div>

    <!-- Sidebar -->
    <aside :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', sidebarCollapsed ? 'md:w-16' : 'md:w-64']"
           class="w-64 bg-black border-r border-zinc-800 flex-shrink-0 fixed md:static h-full z-50 flex flex-col justify-between transition-all duration-300 ease-in-out md:translate-x-0 overflow-hidden"
           data-tour="sidebar">
        <div>
            <!-- Desktop Logo -->
            <div class="h-20 hidden md:flex items-center border-b border-zinc-800"
                 :class="sidebarCollapsed ? 'justify-center px-0' : 'px-8'">
                <a href="/" class="flex items-center space-x-2 group hover:opacity-80 transition"
                   :class="sidebarCollapsed ? 'justify-center w-full' : ''">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-lg group-hover:scale-105 transition">S</div>
                    <span x-show="!sidebarCollapsed" class="text-xl font-bold tracking-tight text-white">Scratch<span class="text-indigo-400">LMS</span></span>
                </a>
            </div>
            
            <!-- Mobile Close Button -->
            <div class="md:hidden flex justify-end p-4">
                <button @click="sidebarOpen = false" class="text-zinc-500 hover:text-white">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <nav class="p-4 space-y-6">
                <?php 
                    $mode = $_SESSION['dashboard_mode'] ?? 'student';
                    $roles = $_SESSION['user_roles'] ?? [];
                    $isInstructor = in_array('instructor', $roles) || in_array('admin', $roles);
                    $pendingAttemptCount = 0;
                    $pendingCertificateCount = 0;
                    $userId = $_SESSION['user_id'] ?? null;
                    if ($isInstructor && $userId) {
                        $db = \App\Core\Database::getInstance();
                        $sqlAttempts = "SELECT COUNT(*) as count
                                        FROM attempt_requests ar
                                        JOIN quizzes q ON ar.quiz_id = q.id
                                        JOIN lessons l ON q.lesson_id = l.id
                                        JOIN modules m ON l.module_id = m.id
                                        JOIN courses c ON m.course_id = c.id
                                        WHERE c.instructor_id = :uid AND ar.status = 'pending'";
                        $resAttempts = $db->fetchAll($sqlAttempts, [':uid' => $userId]);
                        $pendingAttemptCount = (int) ($resAttempts[0]['count'] ?? 0);

                        $sqlCerts = "SELECT COUNT(*) as count
                                     FROM enrollments e
                                     JOIN courses c ON e.course_id = c.id
                                     WHERE c.instructor_id = :uid
                                       AND c.has_certificate = 1
                                       AND c.certificate_auto_issue = 0
                                       AND e.is_completed = 1
                                       AND e.certificate_id IS NULL";
                        $resCerts = $db->fetchAll($sqlCerts, [':uid' => $userId]);
                        $pendingCertificateCount = (int) ($resCerts[0]['count'] ?? 0);
                    }
                ?>

                <!-- STUDENT MENU -->
                <?php if ($mode === 'student'): ?>
                    <div>
                        <p class="px-4 text-[10px] font-bold text-zinc-600 uppercase tracking-widest mb-2" x-show="!sidebarCollapsed">Learning</p>
                        <div class="space-y-1">
                            <a href="/dashboard" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                               :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <i class="fa-solid fa-house w-5 text-center group-hover:text-indigo-400 transition"></i>
                                <span x-show="!sidebarCollapsed">Home</span>
                                <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Home</span>
                            </a>
                            <a href="/my-courses" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                               :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <i class="fa-solid fa-book-open w-5 text-center group-hover:text-indigo-400 transition"></i>
                                <span x-show="!sidebarCollapsed">My Library</span>
                                <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">My Library</span>
                            </a>
                            <a href="/cart" class="relative flex items-center px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                               :class="sidebarCollapsed ? 'justify-center px-0' : 'justify-between'">
                                <div class="flex items-center space-x-3" :class="sidebarCollapsed ? 'space-x-0' : ''">
                                    <i class="fa-solid fa-cart-shopping w-5 text-center group-hover:text-indigo-400 transition"></i>
                                    <span x-show="!sidebarCollapsed">Cart</span>
                                </div>
                                <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Cart</span>
                                <?php if (!empty($_SESSION['cart'])): ?>
                                    <span class="bg-indigo-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-lg shadow-indigo-500/50"
                                          :class="sidebarCollapsed ? 'absolute -top-1 right-2' : ''"><?= count($_SESSION['cart']) ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="/certificates" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                               :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <i class="fa-solid fa-award w-5 text-center group-hover:text-indigo-400 transition"></i>
                                <span x-show="!sidebarCollapsed">Certificates</span>
                                <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Certificates</span>
                            </a>
                            <a href="/notes" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                               :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <i class="fa-solid fa-book-bookmark w-5 text-center group-hover:text-indigo-400 transition"></i>
                                <span x-show="!sidebarCollapsed">Notebook</span>
                                <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Notebook</span>
                            </a>
                        </div>
                    </div>
                
                <!-- INSTRUCTOR MENU -->
                <?php else: ?>
                    <div>
                        <p class="px-4 text-[10px] font-bold text-zinc-600 uppercase tracking-widest mb-2" x-show="!sidebarCollapsed">Teaching</p>
                        <div class="space-y-1">
                            <a href="/dashboard" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                               :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <i class="fa-solid fa-chart-pie w-5 text-center group-hover:text-indigo-400 transition"></i>
                                <span x-show="!sidebarCollapsed">Overview</span>
                                <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Overview</span>
                            </a>
                            <a href="/instructor/course/new" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                               :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <i class="fa-solid fa-plus-circle w-5 text-center group-hover:text-indigo-400 transition"></i>
                                <span x-show="!sidebarCollapsed">Create Course</span>
                                <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Create Course</span>
                            </a>
                            <a href="/instructor/lessons" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                               :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <i class="fa-solid fa-film w-5 text-center group-hover:text-indigo-400 transition"></i>
                                <span x-show="!sidebarCollapsed">Lesson Library</span>
                                <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Lesson Library</span>
                            </a>
                            <a href="/instructor/analytics" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                               :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <i class="fa-solid fa-chart-line w-5 text-center group-hover:text-indigo-400 transition"></i>
                                <span x-show="!sidebarCollapsed">Global Analytics</span>
                                <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Global Analytics</span>
                            </a>
                            <a href="/instructor/question-banks" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                               :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                <i class="fa-solid fa-database w-5 text-center group-hover:text-indigo-400 transition"></i>
                                <span x-show="!sidebarCollapsed">Question Bank</span>
                                <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Question Bank</span>
                            </a>
                        </div>
                        <p class="px-4 mt-4 text-[10px] font-bold text-zinc-600 uppercase tracking-widest mb-2" x-show="!sidebarCollapsed">Bending Requests</p>
                        <div class="space-y-1">
                            <a href="/dashboard" class="relative flex items-center px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                               :class="sidebarCollapsed ? 'justify-center px-0' : 'justify-between'">
                                <div class="flex items-center space-x-3" :class="sidebarCollapsed ? 'space-x-0' : ''">
                                    <i class="fa-solid fa-clipboard-check w-5 text-center group-hover:text-amber-400 transition"></i>
                                    <span x-show="!sidebarCollapsed">Attempt Approvals</span>
                                </div>
                                <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Attempt Approvals</span>
                                <span class="bg-amber-500/10 text-amber-300 text-[10px] font-bold px-2 py-0.5 rounded-full border border-amber-500/30"
                                      :class="sidebarCollapsed ? 'absolute -top-1 right-2' : ''"><?= $pendingAttemptCount ?></span>
                            </a>
                            <a href="/dashboard" class="relative flex items-center px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                               :class="sidebarCollapsed ? 'justify-center px-0' : 'justify-between'">
                                <div class="flex items-center space-x-3" :class="sidebarCollapsed ? 'space-x-0' : ''">
                                    <i class="fa-solid fa-award w-5 text-center group-hover:text-amber-400 transition"></i>
                                    <span x-show="!sidebarCollapsed">Certificate Approvals</span>
                                </div>
                                <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Certificate Approvals</span>
                                <span class="bg-amber-500/10 text-amber-300 text-[10px] font-bold px-2 py-0.5 rounded-full border border-amber-500/30"
                                      :class="sidebarCollapsed ? 'absolute -top-1 right-2' : ''"><?= $pendingCertificateCount ?></span>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ACCOUNT MENU (Global) -->
                <div>
                    <p class="px-4 text-[10px] font-bold text-zinc-600 uppercase tracking-widest mb-2" x-show="!sidebarCollapsed">Account</p>
                    <div class="space-y-1">
                        <a href="/profile" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                           :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                            <i class="fa-solid fa-user-circle w-5 text-center group-hover:text-indigo-400 transition"></i>
                            <span x-show="!sidebarCollapsed">Profile Settings</span>
                            <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Profile Settings</span>
                        </a>
                    </div>
                </div>
            </nav>
        </div>
        <div class="p-4 border-t border-zinc-800">
             <a href="/logout" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl text-zinc-500 hover:bg-red-500/10 hover:text-red-400 transition w-full"
                :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i>
                <span x-show="!sidebarCollapsed">Logout</span>
                <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-black">
        
        <!-- Desktop App Header -->
        <header class="hidden md:flex items-center justify-between px-8 py-4 border-b border-zinc-800 bg-black/80 backdrop-blur-xl z-30 sticky top-0" x-data="notificationSystem()">
            <!-- Search -->
            <div class="flex items-center gap-4">
                <button @click="sidebarCollapsed = !sidebarCollapsed"
                        class="p-2 rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-600 transition"
                        :aria-pressed="sidebarCollapsed.toString()" aria-label="Toggle sidebar">
                    <i class="fa-solid" :class="sidebarCollapsed ? 'fa-angles-right' : 'fa-angles-left'"></i>
                </button>
                <div class="relative w-96">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-500">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" placeholder="Search anything..." class="w-full bg-zinc-900 border border-zinc-800 text-zinc-300 rounded-xl py-2 pl-10 pr-4 focus:outline-none focus:border-indigo-500 transition text-sm">
                </div>
            </div>
            
            <!-- Right Actions -->
            <div class="flex items-center space-x-6">
                <?php if (in_array('admin', $_SESSION['user_roles'] ?? [])): ?>
                    <a href="/admin" class="px-4 py-2 rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-300 hover:text-white hover:border-indigo-500/60 hover:bg-zinc-800 transition text-sm font-bold">
                        Admin
                    </a>
                <?php endif; ?>
                
                <!-- Notifications -->
                <div class="relative" x-data="{ open: false }" data-tour="notifications">
                    <button @click="open = !open" class="relative text-zinc-400 hover:text-white transition p-2">
                        <i class="fa-solid fa-bell text-lg"></i>
                        <span x-show="unreadCount > 0" x-text="unreadCount" class="absolute top-0 right-0 bg-red-500 text-white text-[10px] font-bold h-4 w-4 flex items-center justify-center rounded-full border border-black transform translate-x-1 -translate-y-1"></span>
                    </button>

                    <!-- Dropdown -->
                    <div x-show="open" @click.away="open = false" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-2"
                         class="absolute right-0 mt-4 w-80 bg-zinc-900 border border-zinc-800 rounded-2xl shadow-2xl overflow-hidden z-50 origin-top-right"
                         style="display: none;">
                        
                        <div class="px-4 py-3 border-b border-zinc-800 flex justify-between items-center bg-zinc-950/50">
                            <h3 class="font-bold text-white text-xs uppercase tracking-wider">Notifications</h3>
                            <button @click="markAllRead()" class="text-[10px] text-indigo-400 hover:text-indigo-300 font-bold uppercase transition">Mark all read</button>
                        </div>

                        <div class="max-h-[20rem] overflow-y-auto custom-scrollbar">
                            <template x-for="notif in notifications" :key="notif.id">
                                <a :href="notif.link || '#'" @click="markRead(notif.id)" class="block p-4 border-b border-zinc-800/50 hover:bg-zinc-800/50 transition relative group">
                                    <div class="flex gap-3 items-start">
                                        <div class="flex-shrink-0 mt-1">
                                            <div class="w-2 h-2 rounded-full ring-2 ring-black" :class="notif.is_read == 0 ? 'bg-blue-500' : 'bg-zinc-700'"></div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-white truncate" x-text="notif.title"></p>
                                            <p class="text-xs text-zinc-400 mt-0.5 line-clamp-2" x-text="notif.message"></p>
                                            <p class="text-[10px] text-zinc-600 mt-2 font-mono" x-text="formatDate(notif.created_at)"></p>
                                        </div>
                                    </div>
                                </a>
                            </template>
                            <div x-show="notifications.length === 0" class="p-8 text-center text-zinc-500 text-sm flex flex-col items-center">
                                <i class="fa-regular fa-bell-slash text-2xl mb-2 opacity-50"></i>
                                <p>No notifications yet</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-3 focus:outline-none group">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-tr from-indigo-500 to-violet-500 flex items-center justify-center text-white font-bold border border-white/10 shadow-lg group-hover:scale-105 transition">
                            <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                        </div>
                    </button>
                    <!-- User Dropdown Menu -->
                    <div x-show="open" @click.away="open = false" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 mt-4 w-56 bg-zinc-900 border border-zinc-800 rounded-xl shadow-xl py-2 z-50 origin-top-right" style="display: none;">
                        <div class="px-4 py-3 border-b border-zinc-800 mb-2 bg-zinc-950/30">
                            <p class="text-sm text-white font-bold truncate"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></p>
                            <p class="text-xs text-zinc-500 truncate">Student</p>
                        </div>
                        <a href="/profile" class="block px-4 py-2 text-sm text-zinc-300 hover:bg-zinc-800 hover:text-white transition"><i class="fa-solid fa-user-gear mr-2 w-4"></i> Profile Settings</a>
                        <div class="border-t border-zinc-800 my-1"></div>
                        <a href="/logout" class="block px-4 py-2 text-sm text-red-400 hover:bg-red-900/10 transition"><i class="fa-solid fa-power-off mr-2 w-4"></i> Sign Out</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Scrollable Page Content -->
        <div class="flex-1 overflow-y-auto p-4 md:p-8 custom-scrollbar">
            <?php if (isset($content)) echo $content; ?>
        </div>
    </main>

    <script>
        function notificationSystem() {
            return {
                notifications: [],
                unreadCount: 0,
                init() {
                    this.fetchNotifications();
                    // Poll every 60 seconds
                    setInterval(() => this.fetchNotifications(), 60000);
                },
                fetchNotifications() {
                    fetch('/api/notifications')
                        .then(res => res.json())
                        .then(data => {
                            this.notifications = data.notifications;
                            this.unreadCount = data.unread_count;
                        });
                },
                markRead(id) {
                    fetch(`/api/notifications/read/${id}`, { method: 'POST' })
                        .then(() => {
                            const n = this.notifications.find(i => i.id == id);
                            if (n && n.is_read == 0) {
                                n.is_read = 1;
                                this.unreadCount = Math.max(0, this.unreadCount - 1);
                            }
                        });
                },
                markAllRead() {
                     fetch('/api/notifications/read-all', { method: 'POST' })
                        .then(() => {
                            this.notifications.forEach(n => n.is_read = 1);
                            this.unreadCount = 0;
                        });
                },
                formatDate(dateString) {
                    const date = new Date(dateString);
                    const now = new Date();
                    const diff = (now - date) / 1000; // seconds

                    if (diff < 60) return 'Just now';
                    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
                    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
                    
                    return date.toLocaleDateString();
                }
            }
        }
    </script>

</body>
</html>
