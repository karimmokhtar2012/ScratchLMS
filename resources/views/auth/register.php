<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Scratch LMS</title>
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
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-violet-900/20 via-black to-black z-0"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-violet-600/20 rounded-full blur-[100px] z-0"></div>

        <div class="relative z-10">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-lg">S</div>
                <span class="text-xl font-bold tracking-tight text-white">Scratch<span class="text-indigo-400">LMS</span></span>
            </div>
        </div>

        <div class="relative z-10 max-w-lg">
            <h1 class="text-5xl font-extrabold text-white tracking-tight leading-tight mb-6">
                Join the <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 to-fuchsia-400">next generation</span> of creators.
            </h1>
            <p class="text-lg text-zinc-400 leading-relaxed">
                Unlock your potential. Create your account today and get unlimited access to world-class education tools.
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
                <h2 class="text-3xl font-bold text-white">Create your account</h2>
                <p class="mt-2 text-zinc-400">
                    Already a member? 
                    <a href="/login" class="font-medium text-violet-400 hover:text-violet-300 transition">Log in</a>
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-lg text-sm flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" action="/register" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-zinc-400 mb-1">Full Name</label>
                        <input id="name" name="name" type="text" required 
                            class="w-full bg-zinc-950 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 transition placeholder-zinc-600"
                            placeholder="John Doe">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-zinc-400 mb-1">Email address</label>
                        <input id="email" name="email" type="email" required 
                            class="w-full bg-zinc-950 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 transition placeholder-zinc-600"
                            placeholder="you@example.com">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-zinc-400 mb-1">Password</label>
                        <input id="password" name="password" type="password" required 
                            class="w-full bg-zinc-950 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 transition placeholder-zinc-600"
                            placeholder="Create a password">
                    </div>
                </div>

                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg shadow-violet-500/20 text-sm font-bold text-white bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:from-violet-500 hover:to-fuchsia-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 transform hover:-translate-y-0.5 transition duration-200">
                    Sign Up
                </button>
                
                <p class="text-[10px] text-zinc-500 text-center leading-relaxed">
                    By registering, you agree to our Terms of Service and Privacy Policy.
                </p>
            </form>
        </div>
    </div>

</body>
</html>