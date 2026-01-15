<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ScratchLMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Space Grotesk', 'sans-serif'],
                    },
                    colors: {
                        background: '#0a0a0a',
                        surface: '#121212',
                        card: '#1e1e1e',
                        accent: '#6366f1', // Indigo
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
        body { background-color: #050505; color: #e5e5e5; }
        body.page-transition > :not(#page-transition) { opacity: 0; transform: translateY(8px); transition: opacity 320ms ease, transform 320ms ease; }
        body.page-ready > :not(#page-transition) { opacity: 1; transform: translateY(0); }
        body.page-exit > :not(#page-transition) { opacity: 0; transform: translateY(8px); }
        .page-transition-overlay { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; background: radial-gradient(80% 80% at 50% 40%, rgba(88, 28, 135, 0.25), rgba(9, 9, 11, 0.95)); opacity: 0; pointer-events: none; transition: opacity 240ms ease; z-index: 9999; }
        .page-transition-overlay.is-active { opacity: 1; pointer-events: auto; }
        .page-loader { position: relative; width: 64px; height: 64px; }
        .page-loader::before { content: ""; position: absolute; inset: 0; border-radius: 50%; background: conic-gradient(from 180deg, rgba(139, 92, 246, 0) 0deg, rgba(139, 92, 246, 0.95) 110deg, rgba(99, 102, 241, 0.9) 220deg, rgba(59, 130, 246, 0) 360deg); filter: drop-shadow(0 0 12px rgba(139, 92, 246, 0.5)); animation: spin 900ms linear infinite; }
        .page-loader::after { content: ""; position: absolute; inset: 10px; border-radius: 50%; background: radial-gradient(circle at 35% 35%, rgba(255, 255, 255, 0.9), rgba(99, 102, 241, 0.35), rgba(9, 9, 11, 0) 70%); border: 1px solid rgba(139, 92, 246, 0.4); animation: pulse 1200ms ease-in-out infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes pulse { 0%, 100% { transform: scale(0.92); opacity: 0.7; } 50% { transform: scale(1); opacity: 1; } }
        .glass-panel { background: rgba(30, 30, 30, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); }
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0a0a0a; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #555; }
    </style>
</head>
<body class="antialiased flex h-screen overflow-hidden page-transition">

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

    <!-- Flash Messages (Admin Only) -->
    <div class="fixed top-4 left-1/2 -translate-x-1/2 z-50 w-full max-w-md px-4">
        <?php if (isset($_SESSION['admin_flash_success'])): ?>
            <div class="bg-gradient-to-r from-purple-500/20 via-fuchsia-500/20 to-indigo-500/20 border border-purple-400/60 text-purple-100 px-4 py-3 rounded-xl shadow-lg backdrop-blur-sm relative overflow-hidden">
                <div class="absolute inset-0 pointer-events-none opacity-20"
                     style="background-image: linear-gradient(transparent 0, transparent 23px, rgba(255,255,255,0.08) 24px), linear-gradient(90deg, transparent 0, transparent 23px, rgba(255,255,255,0.08) 24px); background-size: 24px 24px;"></div>
                <?= $_SESSION['admin_flash_success'] ?>
                <?php unset($_SESSION['admin_flash_success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['admin_flash_error'])): ?>
            <div class="bg-gradient-to-r from-purple-500/20 via-fuchsia-500/20 to-indigo-500/20 border border-purple-400/60 text-purple-100 px-4 py-3 rounded-xl shadow-lg backdrop-blur-sm relative overflow-hidden">
                <div class="absolute inset-0 pointer-events-none opacity-20"
                     style="background-image: linear-gradient(transparent 0, transparent 23px, rgba(255,255,255,0.08) 24px), linear-gradient(90deg, transparent 0, transparent 23px, rgba(255,255,255,0.08) 24px); background-size: 24px 24px;"></div>
                <?= $_SESSION['admin_flash_error'] ?>
                <?php unset($_SESSION['admin_flash_error']); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <aside class="w-64 bg-black border-r border-zinc-900 flex flex-col z-20">
        <div class="h-20 flex items-center px-8 border-b border-zinc-900">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 rounded bg-white text-black flex items-center justify-center font-bold">A</div>
                <span class="text-lg font-bold tracking-tight">Admin<span class="text-zinc-500">Panel</span></span>
            </div>
        </div>

        <nav class="flex-1 p-4 space-y-1">
            <a href="/admin" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-zinc-900 text-zinc-400 hover:text-white transition <?= $_SERVER['REQUEST_URI'] === '/admin' ? 'bg-zinc-900 text-white' : '' ?>">
                <i class="fa-solid fa-chart-line w-5 text-center"></i>
                <span>Dashboard</span>
            </a>
            <a href="/admin/users" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-zinc-900 text-zinc-400 hover:text-white transition <?= $_SERVER['REQUEST_URI'] === '/admin/users' ? 'bg-zinc-900 text-white' : '' ?>">
                <i class="fa-solid fa-users-gear w-5 text-center"></i>
                <span>User Management</span>
            </a>
            <a href="/admin/courses" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-zinc-900 text-zinc-400 hover:text-white transition">
                <i class="fa-solid fa-layer-group w-5 text-center"></i>
                <span>Content & Courses</span>
            </a>
            <div class="pt-4 mt-4 border-t border-zinc-900">
                <a href="/" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-zinc-900 text-zinc-400 hover:text-white transition">
                    <i class="fa-solid fa-arrow-left w-5 text-center"></i>
                    <span>Back to Site</span>
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col relative bg-zinc-950">
        <!-- Top Bar -->
        <header class="h-20 border-b border-zinc-900 flex items-center justify-between px-8 bg-black/50 backdrop-blur-md sticky top-0 z-10">
            <h1 class="text-xl font-bold text-white"><?= $title ?? 'Dashboard' ?></h1>
            <div class="flex items-center space-x-4">
                <div class="text-right hidden md:block">
                    <p class="text-sm font-bold text-white"><?= $_SESSION['user_name'] ?></p>
                    <p class="text-xs text-zinc-500 uppercase tracking-wider">Super Admin</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center border border-zinc-700">
                    <i class="fa-solid fa-user-shield text-zinc-400"></i>
                </div>
            </div>
        </header>

        <!-- Scrollable Area -->
        <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
            <?php if (isset($content)) echo $content; ?>
        </div>
    </main>

</body>
</html>
