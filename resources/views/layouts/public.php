<!DOCTYPE html>
<html lang="<?= \App\Core\Lang::getInstance()->current() ?>" dir="<?= \App\Core\Lang::getInstance()->direction() ?>" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'LMS' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
                        background: '#09090b',
                        surface: '#18181b',
                        primary: '#3b82f6',
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
        body.page-transition { opacity: 0; transform: translateY(8px); transition: opacity 320ms ease, transform 320ms ease; }
        body.page-ready { opacity: 1; transform: translateY(0); }
        body.page-exit { opacity: 0; transform: translateY(8px); }
        .page-transition-overlay { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; background: radial-gradient(80% 80% at 50% 40%, rgba(88, 28, 135, 0.25), rgba(9, 9, 11, 0.95)); opacity: 0; pointer-events: none; transition: opacity 240ms ease; z-index: 9999; }
        .page-transition-overlay.is-active { opacity: 1; pointer-events: auto; }
        .page-loader { position: relative; width: 64px; height: 64px; }
        .page-loader::before { content: ""; position: absolute; inset: 0; border-radius: 50%; background: conic-gradient(from 180deg, rgba(139, 92, 246, 0) 0deg, rgba(139, 92, 246, 0.95) 110deg, rgba(99, 102, 241, 0.9) 220deg, rgba(59, 130, 246, 0) 360deg); filter: drop-shadow(0 0 12px rgba(139, 92, 246, 0.5)); animation: spin 900ms linear infinite; }
        .page-loader::after { content: ""; position: absolute; inset: 10px; border-radius: 50%; background: radial-gradient(circle at 35% 35%, rgba(255, 255, 255, 0.9), rgba(99, 102, 241, 0.35), rgba(9, 9, 11, 0) 70%); border: 1px solid rgba(139, 92, 246, 0.4); animation: pulse 1200ms ease-in-out infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes pulse { 0%, 100% { transform: scale(0.92); opacity: 0.7; } 50% { transform: scale(1); opacity: 1; } }
        [dir="rtl"] { font-family: 'RB', sans-serif; }
        .gradient-text {
            background: linear-gradient(to right, #8b5cf6, #6366f1, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col page-transition">

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
                document.body.classList.add('page-ready');
            });
            window.addEventListener('pageshow', () => {
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

    <!-- Navigation (Professional Fixed Style) -->
    <nav class="fixed w-full z-50 top-0 bg-background/80 backdrop-blur-md border-b border-white/5">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <a href="<?= isset($_SESSION['user_id']) ? '/dashboard' : '/' ?>" class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-lg">S</div>
                    <span class="text-xl font-bold tracking-tight text-white">Scratch<span class="text-indigo-400">LMS</span></span>
                </a>
            </div>
            <div class="hidden md:flex items-center space-x-8 rtl:space-x-reverse text-sm font-medium text-zinc-400">
                <a href="/#features" class="hover:text-white transition"><?= __('nav_features') ?></a>
                <a href="/#how-it-works" class="hover:text-white transition"><?= __('nav_how_it_works') ?></a>
                <a href="/courses" class="hover:text-white transition"><?= __('nav_catalog') ?></a>
            </div>
            <div class="flex items-center space-x-4 rtl:space-x-reverse">
                <!-- Language Switcher -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center text-zinc-400 hover:text-white transition text-sm">
                        <i class="fa-solid fa-globe mr-1.5 rtl:ml-1.5 rtl:mr-0"></i> 
                        <span class="uppercase"><?= \App\Core\Lang::getInstance()->current() ?></span>
                    </button>
                    <div x-show="open" @click.away="open = false" class="absolute right-0 rtl:left-0 rtl:right-auto mt-2 w-32 bg-zinc-900 border border-zinc-800 rounded-xl shadow-xl py-1 z-50">
                        <a href="/language/en" class="block px-4 py-2 text-sm text-zinc-400 hover:text-white hover:bg-zinc-800">English</a>
                        <a href="/language/ar" class="block px-4 py-2 text-sm text-zinc-400 hover:text-white hover:bg-zinc-800 text-right font-['RB']">العربية</a>
                    </div>
                </div>

                <div class="h-6 w-px bg-zinc-800"></div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/dashboard" class="bg-white text-black px-6 py-2.5 rounded-full font-bold text-sm hover:bg-zinc-200 transition">
                        <?= __('nav_dashboard') ?>
                    </a>
                    <a href="/logout" class="text-sm font-medium text-zinc-500 hover:text-white transition">
                        Log Out
                    </a>
                <?php else: ?>
                    <a href="/login" class="text-zinc-400 hover:text-white text-sm font-medium transition"><?= __('nav_login') ?></a>
                    <a href="/register" class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2.5 rounded-full font-bold text-sm transition shadow-lg shadow-indigo-500/25">
                        <?= __('nav_get_started') ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow pt-20">
        <?php if (isset($content)) echo $content; ?>
    </main>

    <!-- Footer (Simplified) -->
    <footer class="py-12 border-t border-white/5 bg-background">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center">
            <div class="flex items-center space-x-2 mb-4 md:mb-0">
                <div class="w-6 h-6 rounded bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-xs">S</div>
                <span class="font-bold text-zinc-300">Scratch<span class="text-indigo-400">LMS</span></span>
            </div>
            <div class="text-zinc-500 text-sm">
                &copy; <?= date('Y') ?> <?= __('footer_rights') ?>
            </div>
        </div>
    </footer>

</body>
</html>
