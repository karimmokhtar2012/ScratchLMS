<?php ob_start(); ?>

<section class="relative min-h-[80vh] flex items-center justify-center overflow-hidden grid-bg">
    <!-- Glow Effect -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-600/10 rounded-full blur-[120px] -z-10"></div>

    <div class="max-w-7xl mx-auto px-6 text-center relative z-10">
        <!-- 404 Text -->
        <h1 class="text-9xl md:text-[12rem] font-black text-transparent bg-clip-text bg-gradient-to-r from-zinc-700 via-zinc-800 to-zinc-900 select-none opacity-50 animate-pulse-slow">
            404
        </h1>
        
        <!-- Message -->
        <div class="relative -mt-12 md:-mt-20">
            <h2 class="text-3xl md:text-5xl font-bold text-white mb-6">Page Not Found</h2>
            <p class="text-lg text-zinc-400 max-w-lg mx-auto mb-10 leading-relaxed">
                The content you are looking for might have been removed, had its name changed, or is temporarily unavailable.
            </p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/" class="w-full sm:w-auto px-8 py-3 bg-white text-black rounded-full font-bold transition transform hover:-translate-y-1 hover:shadow-lg hover:shadow-white/10">
                    <i class="fa-solid fa-house mr-2"></i> Go Home
                </a>
                <a href="/courses" class="w-full sm:w-auto px-8 py-3 bg-zinc-900 text-white border border-zinc-700 rounded-full font-bold transition hover:bg-zinc-800 hover:border-zinc-600">
                    <i class="fa-solid fa-compass mr-2"></i> Browse Courses
                </a>
            </div>
        </div>
    </div>
</section>

<?php 
$content = ob_get_clean(); 
// We use the public layout, but we need to ensure the path is correct relative to this file
require __DIR__ . '/../layouts/public.php'; 
?>
