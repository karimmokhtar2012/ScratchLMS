<?php ob_start(); ?>

<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">My Certificates</h1>
            <p class="text-zinc-400 mt-2">Credentials you have earned.</p>
        </div>
    </div>

    <?php if (empty($certificates)): ?>
        <div class="p-12 border-2 border-dashed border-zinc-800 rounded-3xl text-center">
            <div class="w-20 h-20 bg-zinc-900 rounded-full flex items-center justify-center mx-auto mb-6 text-zinc-700 text-4xl">
                <i class="fa-solid fa-award"></i>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">No Certificates Yet</h3>
            <p class="text-zinc-500 mb-6">Complete a course to earn your first credential.</p>
            <a href="/my-courses" class="inline-block bg-indigo-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-indigo-500 transition">Go to My Courses</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($certificates as $cert): ?>
                <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden hover:border-amber-500/50 transition group relative">
                    <!-- Glow -->
                    <div class="absolute inset-0 bg-gradient-to-tr from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition duration-500"></div>
                    
                    <div class="p-6 relative z-10">
                        <div class="w-12 h-12 bg-amber-500/10 text-amber-500 rounded-xl flex items-center justify-center mb-4 text-xl border border-amber-500/20">
                            <i class="fa-solid fa-certificate"></i>
                        </div>
                        <h3 class="text-lg font-bold text-white mb-1 line-clamp-1"><?= htmlspecialchars($cert['course_title']) ?></h3>
                        <p class="text-xs text-zinc-500 font-mono mb-6">Issued: <?= date('M j, Y', strtotime($cert['certified_at'])) ?></p>
                        
                        <a href="/certificate/<?= $cert['certificate_id'] ?>" target="_blank" class="block w-full text-center py-2.5 bg-black border border-zinc-700 text-zinc-300 rounded-lg text-sm font-bold hover:text-white hover:border-amber-500/50 transition">
                            View Certificate
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../layouts/app.php'; 
?>
