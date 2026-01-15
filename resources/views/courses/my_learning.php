<?php ob_start(); ?>

<div class="max-w-7xl mx-auto space-y-8">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">My Learning</h1>
            <p class="text-zinc-400 mt-2">Pick up where you left off and continue your journey.</p>
        </div>
        <a href="/courses" class="text-sm text-blue-400 hover:text-blue-300 font-medium">Browse more courses &rarr;</a>
    </div>

    <!-- Course Grid -->
    <?php if (empty($courses)): ?>
        <div class="text-center py-20 bg-zinc-800/30 rounded-2xl border border-dashed border-zinc-700">
            <div class="bg-zinc-800 p-4 rounded-full inline-block mb-4">
                <i class="fa-solid fa-graduation-cap text-4xl text-zinc-600"></i>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">You haven't enrolled in any courses yet</h3>
            <p class="text-zinc-400">Discover new skills in our catalog.</p>
            <a href="/courses" class="inline-block mt-6 bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 px-8 rounded-xl transition shadow-lg shadow-indigo-500/20">
                Explore Catalog
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($courses as $course): ?>
                <div class="bg-surface rounded-2xl border border-zinc-700/50 overflow-hidden hover:border-zinc-500 transition duration-300 flex flex-col h-full">
                    
                    <!-- Thumbnail -->
                    <div class="aspect-video bg-zinc-800 relative">
                        <img src="<?= $course['thumbnail'] ?: '/assets/images/course.png' ?>" class="w-full h-full object-cover opacity-80">
                        
                        <!-- Progress Overlay -->
                        <div class="absolute bottom-0 left-0 w-full bg-black/60 backdrop-blur-sm p-3">
                            <div class="flex justify-between text-[10px] font-bold text-white uppercase tracking-widest mb-1.5">
                                <span>Progress</span>
                                <span><?= round($course['progress_percent']) ?>%</span>
                            </div>
                            <div class="w-full bg-zinc-700 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-blue-500 h-full rounded-full transition-all duration-500" style="width: <?= $course['progress_percent'] ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6 flex flex-col flex-grow">
                        <h3 class="text-lg font-bold text-white mb-1 line-clamp-1"><?= htmlspecialchars($course['title']) ?></h3>
                        <p class="text-xs text-zinc-500 mb-6">By <?= htmlspecialchars($course['instructor_name']) ?></p>

                        <div class="mt-auto">
                            <?php if ($course['is_completed']): ?>
                                <a href="/learn/<?= $course['id'] ?>" class="w-full block text-center bg-green-600/10 text-green-400 border border-green-500/20 hover:bg-green-600 hover:text-white font-medium py-3 rounded-xl transition">
                                    <i class="fa-solid fa-circle-check mr-2"></i> Review Course
                                </a>
                            <?php else: ?>
                                <a href="/learn/<?= $course['id'] ?>" class="w-full block text-center bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-indigo-500/20">
                                    Resume Learning
                                </a>
                            <?php endif; ?>
                        </div>
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
