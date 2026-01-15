<?php ob_start(); ?>

<div class="max-w-7xl mx-auto space-y-8" x-data="{ 
    showAddForm: false, 
    type: 'single_choice',
    options: [{ text: '' }, { text: '' }],
    addOption() { this.options.push({ text: '' }) },
    removeOption(index) { this.options.splice(index, 1) }
}">
    
    <!-- Header -->
    <div class="flex items-start justify-between">
        <div class="flex items-center space-x-4">
            <a href="/instructor/question-banks" class="w-10 h-10 rounded-xl bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400 hover:text-white transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white tracking-tight"><?= htmlspecialchars($bank['title']) ?></h1>
                <p class="text-zinc-500 mt-1"><?= htmlspecialchars($bank['description'] ?: 'Repository of questions.') ?></p>
            </div>
        </div>
        <button @click="showAddForm = !showAddForm" 
                class="bg-zinc-800 hover:bg-zinc-700 text-white px-6 py-2.5 rounded-xl font-bold transition flex items-center border border-zinc-700">
            <i class="fa-solid mr-2" :class="showAddForm ? 'fa-times' : 'fa-plus'"></i>
            <span x-text="showAddForm ? 'Cancel' : 'Add Question'"></span>
        </button>
    </div>

    <!-- Add Question Form -->
    <div x-show="showAddForm" x-transition 
         class="bg-zinc-900 border border-zinc-800 rounded-3xl p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
        
        <form action="/instructor/question-bank/question/add" method="POST" class="space-y-6">
            <input type="hidden" name="bank_id" value="<?= $bank['id'] ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Question Text -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-zinc-400 mb-2">Question Text</label>
                    <textarea name="question_text" required rows="2" 
                              class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition outline-none resize-none"
                              placeholder="e.g. What is the output of..."></textarea>
                </div>

                <!-- Type Selector -->
                <div>
                    <label class="block text-sm font-bold text-zinc-400 mb-2">Question Type</label>
                    <select name="type" x-model="type" 
                            class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-white focus:border-indigo-500 transition outline-none appearance-none">
                        <option value="single_choice">Single Choice</option>
                        <option value="multi_choice">Multiple Choice</option>
                        <option value="true_false">True / False</option>
                        <option value="short_text">Short Text (Exact Match)</option>
                    </select>
                </div>

                <!-- Explanation -->
                <div>
                    <label class="block text-sm font-bold text-zinc-400 mb-2">Explanation (Optional)</label>
                    <input type="text" name="explanation" placeholder="Shown after answer"
                           class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-white focus:border-indigo-500 transition outline-none">
                </div>
            </div>

            <!-- Options Section -->
            <div class="space-y-4 pt-4 border-t border-zinc-800/50">
                
                <!-- True / False -->
                <template x-if="type === 'true_false'">
                    <div class="flex space-x-4">
                        <label class="flex-1 bg-zinc-950 border border-zinc-800 p-4 rounded-xl cursor-pointer hover:border-indigo-500 transition flex items-center">
                            <input type="radio" name="correct_tf" value="1" checked class="w-4 h-4 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-3 font-bold text-white">True</span>
                        </label>
                        <label class="flex-1 bg-zinc-950 border border-zinc-800 p-4 rounded-xl cursor-pointer hover:border-indigo-500 transition flex items-center">
                            <input type="radio" name="correct_tf" value="0" class="w-4 h-4 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-3 font-bold text-white">False</span>
                        </label>
                    </div>
                </template>

                <!-- Short Text -->
                <template x-if="type === 'short_text'">
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-2">Correct Answer</label>
                        <input type="text" name="correct_text" required placeholder="Enter the exact correct answer"
                               class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-white focus:border-emerald-500 transition outline-none">
                    </div>
                </template>

                <!-- Choice Options -->
                <template x-if="type === 'single_choice' || type === 'multi_choice'">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-xs font-bold text-zinc-500 uppercase tracking-widest">Options & Correct Answer</label>
                            <button type="button" @click="addOption" class="text-xs text-indigo-400 hover:text-indigo-300 font-bold">+ Add Option</button>
                        </div>
                        <template x-for="(option, index) in options" :key="index">
                            <div class="flex items-center space-x-3 animate-fade-in">
                                <div class="flex-shrink-0">
                                    <input :type="type === 'single_choice' ? 'radio' : 'checkbox'" 
                                           :name="type === 'single_choice' ? 'correct_option' : 'correct_options[]'"
                                           :value="index"
                                           class="w-5 h-5 text-indigo-600 border-zinc-700 bg-zinc-900 rounded focus:ring-indigo-500">
                                </div>
                                <input type="text" name="option_text[]" required x-model="option.text"
                                       class="flex-1 bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 transition outline-none"
                                       :placeholder="'Option ' + (index + 1)">
                                <button type="button" @click="removeOption(index)" x-show="options.length > 2" class="text-zinc-600 hover:text-red-400 transition">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-white text-black px-8 py-3 rounded-xl font-bold hover:bg-zinc-200 transition shadow-xl">
                    Save to Bank
                </button>
            </div>
        </form>
    </div>

    <!-- Questions List -->
    <div class="space-y-4">
        <h2 class="text-xl font-bold text-white flex items-center">
            <i class="fa-solid fa-list-ul mr-2 text-zinc-500"></i>
            Stored Questions (<?= count($questions) ?>)
        </h2>

        <?php foreach ($questions as $q): ?>
            <?php 
                $options = json_decode($q['options'], true);
                $correct = json_decode($q['correct_answers'], true);
            ?>
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 group hover:border-zinc-700 transition">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center space-x-3">
                        <span class="bg-zinc-800 text-[10px] font-bold text-zinc-400 px-2 py-1 rounded border border-zinc-700 uppercase tracking-widest">
                            <?= str_replace('_', ' ', $q['type']) ?>
                        </span>
                    </div>
                    <form action="/instructor/question-bank/question/delete/<?= $q['id'] ?>" method="POST" onsubmit="return confirm('Delete this question?');">
                        <button type="submit" class="text-zinc-600 hover:text-red-400 transition">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </form>
                </div>

                <h3 class="text-lg text-white font-medium mb-4 leading-relaxed"><?= htmlspecialchars($q['question_text']) ?></h3>

                <?php if ($q['type'] === 'true_false'): ?>
                    <div class="flex space-x-4">
                        <div class="px-4 py-2 rounded-lg text-sm font-bold <?= $correct[0] == '1' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-zinc-800/50 text-zinc-500 border border-zinc-800' ?>">True</div>
                        <div class="px-4 py-2 rounded-lg text-sm font-bold <?= $correct[0] == '0' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-zinc-800/50 text-zinc-500 border border-zinc-800' ?>">False</div>
                    </div>
                <?php elseif (!empty($options)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <?php foreach ($options as $opt): ?>
                            <div class="px-4 py-3 rounded-xl text-sm flex items-center space-x-3 <?= in_array($opt['id'], $correct) ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-zinc-950 border border-zinc-800 text-zinc-400' ?>">
                                <i class="fa-solid <?= in_array($opt['id'], $correct) ? 'fa-circle-check' : 'fa-circle text-zinc-800' ?> text-xs"></i>
                                <span><?= htmlspecialchars($opt['text']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($q['type'] === 'short_text'): ?>
                    <div class="bg-zinc-950 border border-zinc-800 p-4 rounded-xl">
                        <p class="text-xs text-zinc-500 uppercase font-bold mb-1 tracking-widest">Correct Match</p>
                        <p class="text-emerald-400 font-medium"><?= htmlspecialchars($correct[0]) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($q['explanation']): ?>
                    <div class="mt-4 pt-4 border-t border-zinc-800 text-xs text-zinc-500">
                        <strong class="text-zinc-400">Explanation:</strong> <?= htmlspecialchars($q['explanation']) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (empty($questions)): ?>
            <div class="text-center py-12 bg-zinc-900/30 border border-dashed border-zinc-800 rounded-2xl text-zinc-600">
                No questions in this bank yet.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../../layouts/app.php'; 
?>
