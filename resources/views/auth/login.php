<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Scratch LMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        background: '#09090b', // zinc-950
                        surface: '#18181b', // zinc-900
                    }
                }
            }
        }
    </script>
    <style>
        .grid-bg {
            background-color: #000;
            background-image: linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 40px 40px;
        }
    </style>
</head>

<body class="h-screen flex overflow-hidden bg-background font-sans text-zinc-200">

    <!-- Left Side: Brand & Slogan -->
    <div class="hidden lg:flex w-1/2 relative flex-col justify-between p-12 grid-bg border-r border-zinc-800">
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-indigo-900/20 via-black to-black z-0"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-indigo-600/20 rounded-full blur-[100px] z-0"></div>

        <div class="relative z-10">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-lg">S</div>
                <span class="text-xl font-bold tracking-tight text-white">Scratch<span class="text-indigo-400">LMS</span></span>
            </div>
        </div>

        <div class="relative z-10 max-w-lg">
            <h1 class="text-5xl font-extrabold text-white tracking-tight leading-tight mb-6">
                Welcome back to the <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-violet-400">future of learning.</span>
            </h1>
            <p class="text-lg text-zinc-400 leading-relaxed">
                Continue your journey. Access your courses, track your progress, and master new skills today.
            </p>
        </div>

        <div class="relative z-10 text-sm text-zinc-500">
            &copy; <?= date('Y') ?> Scratch LMS.
        </div>
    </div>

    <!-- Right Side: Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-surface">
        <div class="w-full max-w-md space-y-8">
            <div class="text-center lg:text-left">
                <!-- Mobile Logo -->
                <div class="flex lg:hidden justify-center mb-8">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-lg">S</div>
                        <span class="text-xl font-bold tracking-tight text-white">Scratch<span class="text-indigo-400">LMS</span></span>
                    </div>
                </div>
                <!-- End Mobile Logo -->
                <h2 class="text-3xl font-bold text-white">Sign in to your account</h2>
                <p class="mt-2 text-zinc-400">
                    Don't have an account?
                    <a href="/register" class="font-medium text-indigo-400 hover:text-indigo-300 transition">Get started for free</a>
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-lg text-sm flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" action="/login" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <div class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-zinc-400 mb-1">Email address</label>
                        <input id="email" name="email" type="email" required
                            class="w-full bg-zinc-950 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition placeholder-zinc-600"
                            placeholder="you@example.com">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-zinc-400 mb-1">Password</label>
                        <input id="password" name="password" type="password" required
                            class="w-full bg-zinc-950 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition placeholder-zinc-600"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-zinc-700 rounded bg-zinc-900">
                        <label for="remember-me" class="ml-2 block text-sm text-zinc-400">Remember me</label>
                    </div>
                    <div class="text-sm">
                        <a href="#" class="font-medium text-indigo-400 hover:text-indigo-300">Forgot password?</a>
                    </div>
                </div>

                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg shadow-indigo-500/20 text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transform hover:-translate-y-0.5 transition duration-200">
                    Sign in
                </button>
            </form>
        </div>
    </div>

</body>

</html>