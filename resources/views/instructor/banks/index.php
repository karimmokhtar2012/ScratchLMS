<?php ob_start(); ?>

<div class="max-w-7xl mx-auto space-y-8" x-data="{ showCreateModal: false }">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Question Bank</h1>
            <p class="text-zinc-400 mt-2">Manage your central repository of quiz questions.</p>
        </div>
        <button @click="showCreateModal = true" class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2.5 rounded-xl font-bold transition shadow-lg shadow-indigo-500/20 flex items-center">
            <i class="fa-solid fa-plus mr-2"></i> Create New Bank
        </button>
    </div>

    <!-- Banks Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($banks as $bank): ?>
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 hover:border-zinc-700 transition group flex flex-col h-full relative">
                <!-- Actions Dropdown (Optional or simple icons) -->
                <div class="absolute top-4 right-4">
                    <form action="/instructor/question-bank/delete/<?= $bank['id'] ?>" method="POST" onsubmit="return confirm('Delete this entire bank and all its questions?');">
                        <button type="submit" class="text-zinc-600 hover:text-red-400 transition">
                            <i class="fa-solid fa-trash-can text-sm"></i>
                        </button>
                    </form>
                </div>

                <div class="flex items-center space-x-4 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 border border-indigo-500/20">
                        <i class="fa-solid fa-database text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-bold group-hover:text-indigo-400 transition"><?= htmlspecialchars($bank['title']) ?></h3>
                        <p class="text-xs text-zinc-500 font-medium uppercase tracking-wider"><?= $bank['question_count'] ?> Questions</p>
                    </div>
                </div>

                <p class="text-sm text-zinc-400 line-clamp-2 mb-6 flex-grow">
                    <?= htmlspecialchars($bank['description'] ?: 'No description provided.') ?>
                </p>

                <div class="pt-4 border-t border-zinc-800/50 flex items-center justify-between mt-auto">
                    <span class="text-[10px] text-zinc-600 font-mono uppercase">Created <?= date('M j, Y', strtotime($bank['created_at'])) ?></span>
                    <a href="/instructor/question-bank/<?= $bank['id'] ?>" class="text-xs font-bold text-white hover:text-indigo-400 transition flex items-center">
                        Manage Questions <i class="fa-solid fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($banks)): ?>
            <div class="col-span-full py-20 text-center border-2 border-dashed border-zinc-800 rounded-3xl">
                <i class="fa-solid fa-folder-open text-5xl text-zinc-700 mb-4 block"></i>
                <h3 class="text-xl font-bold text-white mb-2">No Question Banks yet</h3>
                <p class="text-zinc-500 mb-6">Create your first bank to start building your repository.</p>
                <button @click="showCreateModal = true" class="bg-zinc-800 hover:bg-zinc-700 text-white px-6 py-2 rounded-xl transition font-bold border border-zinc-700">
                    Get Started
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Create Modal -->
    <div x-show="showCreateModal" 
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         x-transition.opacity>
        
        <div @click.away="showCreateModal = false" 
             class="bg-zinc-900 border border-zinc-800 w-full max-w-md rounded-3xl p-8 shadow-2xl">
            
            <h2 class="text-2xl font-bold text-white mb-6">New Question Bank</h2>
            
            <form action="/instructor/question-banks/store" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-zinc-400 mb-2">Bank Title</label>
                    <input type="text" name="title" required placeholder="e.g., PHP Advanced Patterns"
                           class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition outline-none">
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-400 mb-2">Description (Optional)</label>
                    <textarea name="description" rows="3" placeholder="What kind of questions are in here?"
                              class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition outline-none resize-none"></textarea>
                </div>

                <div class="flex space-x-3 pt-4">
                    <button type="button" @click="showCreateModal = false" class="flex-1 px-6 py-3 bg-zinc-800 text-zinc-400 rounded-xl font-bold hover:bg-zinc-700 transition">Cancel</button>
                    <button type="submit" class="flex-1 px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-500 transition shadow-lg shadow-indigo-500/20">Create Bank</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../../layouts/app.php'; 
?>
