<?php ob_start(); ?>

<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">My Notebook</h1>
            <p class="text-zinc-400 mt-2">All your saved insights and timestamps in one place.</p>
        </div>
        <!-- Search placeholder -->
        <div class="relative w-64 hidden md:block">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3"><i class="fa-solid fa-search text-zinc-500"></i></span>
            <input type="text" placeholder="Search notes..." class="w-full bg-zinc-900 border border-zinc-800 rounded-xl pl-10 pr-4 py-2 text-sm text-white focus:border-indigo-500 transition focus:outline-none">
        </div>
    </div>

    <?php if (empty($notes)): ?>
        <div class="p-12 border-2 border-dashed border-zinc-800 rounded-3xl text-center">
            <div class="w-20 h-20 bg-zinc-900 rounded-full flex items-center justify-center mx-auto mb-6 text-zinc-700 text-4xl">
                <i class="fa-solid fa-note-sticky"></i>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Your notebook is empty</h3>
            <p class="text-zinc-500 mb-6">Start taking notes while watching lessons to see them here.</p>
            <a href="/my-courses" class="inline-block bg-indigo-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-indigo-500 transition">Go to Learning</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($notes as $note): ?>
                <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 hover:border-indigo-500/30 transition group flex flex-col h-full relative">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3 overflow-hidden">
                            <div class="w-8 h-8 rounded-lg bg-zinc-800 flex items-center justify-center text-indigo-400 flex-shrink-0">
                                <i class="fa-solid <?= $note['lesson_type'] === 'video' ? 'fa-play' : 'fa-file-lines' ?> text-xs"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-white font-bold text-sm truncate"><?= htmlspecialchars($note['lesson_title']) ?></h3>
                                <p class="text-xs text-zinc-500 truncate"><?= htmlspecialchars($note['course_title']) ?></p>
                            </div>
                        </div>
                        <form action="/notes/delete/<?= $note['id'] ?>" method="POST" onsubmit="return confirm('Delete this note?');">
                            <button type="submit" class="text-zinc-600 hover:text-red-400 transition">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Content -->
                    <div class="flex-grow">
                        <p class="text-zinc-300 text-sm leading-relaxed whitespace-pre-wrap font-serif italic border-l-2 border-zinc-700 pl-3">"<?= htmlspecialchars($note['content']) ?>"</p>
                    </div>

                    <!-- Footer -->
                    <div class="mt-6 pt-4 border-t border-zinc-800/50 flex items-center justify-between">
                        <span class="text-[10px] text-zinc-600 font-mono uppercase"><?= date('M j, Y', strtotime($note['created_at'])) ?></span>
                        
                        <?php if ($note['timestamp']): ?>
                            <a href="/learn/<?= $note['course_id'] ?>/lesson/<?= $note['lesson_id'] ?>#t=<?= $note['timestamp'] ?>" class="text-xs font-bold text-indigo-400 hover:text-white transition flex items-center bg-indigo-500/10 px-2 py-1 rounded border border-indigo-500/20">
                                <i class="fa-solid fa-play mr-1.5"></i> <?= gmdate("i:s", $note['timestamp']) ?>
                            </a>
                        <?php else: ?>
                            <a href="/learn/<?= $note['course_id'] ?>/lesson/<?= $note['lesson_id'] ?>" class="text-xs font-bold text-zinc-500 hover:text-white transition">
                                Go to Lesson &rarr;
                            </a>
                        <?php endif; ?>
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
