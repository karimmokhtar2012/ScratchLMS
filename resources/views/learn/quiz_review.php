<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Review</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        background: '#09090b',
                        surface: '#18181b', 
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-background text-zinc-200 h-screen flex flex-col font-sans">

    <!-- Header -->
    <header class="h-16 bg-surface border-b border-zinc-800 flex items-center justify-between px-6 z-20 flex-shrink-0">
        <div class="flex items-center space-x-4">
            <a href="javascript:history.back()" class="text-zinc-400 hover:text-white transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Results
            </a>
            <span class="text-zinc-600">|</span>
            <h1 class="font-bold text-white">Reviewing Answers</h1>
        </div>
        <div class="text-sm text-zinc-400">
            Score: <span class="text-white font-bold"><?= $attempt['score'] ?>%</span>
        </div>
    </header>

    <!-- Main Content -->
    <div class="flex-1 flex items-center justify-center p-6 overflow-hidden">
        <div class="w-full max-w-4xl bg-surface border border-zinc-800 rounded-3xl p-8 shadow-2xl relative overflow-hidden h-full max-h-[600px] flex flex-col" 
             x-data="{ reviewIndex: 0, total: <?= count($reviewData) ?> }">
            
            <h3 class="text-xl font-bold text-white mb-6 flex items-center border-b border-zinc-800 pb-4 flex-shrink-0">
                <span class="bg-indigo-500/10 text-indigo-400 px-3 py-1 rounded-lg text-sm border border-indigo-500/20 mr-3">
                    Q <span x-text="reviewIndex + 1"></span> / <span x-text="total"></span>
                </span>
                Review Mode
            </h3>

            <!-- Scrollable Question Area -->
            <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar">
                <?php foreach ($reviewData as $index => $q): ?>
                    <div x-show="reviewIndex === <?= $index ?>" x-transition.opacity class="space-y-6">
                        <div class="mb-4">
                            <h4 class="text-2xl font-medium text-white leading-relaxed"><?= htmlspecialchars($q['question_text']) ?></h4>
                        </div>
                        
                        <div class="space-y-3">
                            <?php if ($q['type'] === 'true_false'): ?>
                                <?php 
                                    $userAns = $q['user_answer'];
                                    $correctAns = $q['correct_answers'][0];
                                ?>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-6 rounded-xl border-2 text-center font-bold text-lg
                                        <?= $userAns == 1 ? ($userAns == $correctAns ? 'border-green-500 bg-green-500/10 text-green-400' : 'border-red-500 bg-red-500/10 text-red-400') : ($correctAns == 1 ? 'border-green-500 border-dashed text-green-500 opacity-70' : 'border-zinc-700 bg-zinc-800 text-zinc-500') ?>">
                                        True
                                    </div>
                                    <div class="p-6 rounded-xl border-2 text-center font-bold text-lg
                                        <?= $userAns === 0 ? ($userAns == $correctAns ? 'border-green-500 bg-green-500/10 text-green-400' : 'border-red-500 bg-red-500/10 text-red-400') : ($correctAns === 0 ? 'border-green-500 border-dashed text-green-500 opacity-70' : 'border-zinc-700 bg-zinc-800 text-zinc-500') ?>">
                                        False
                                    </div>
                                </div>

                            <?php elseif (in_array($q['type'], ['single_choice', 'multi_choice'])): ?>
                                <?php foreach ($q['options'] as $opt): ?>
                                    <?php 
                                        $isUserSelected = is_array($q['user_answer']) ? in_array($opt['id'], $q['user_answer']) : $q['user_answer'] == $opt['id'];
                                        $isCorrect = in_array($opt['id'], $q['correct_answers']);
                                    ?>
                                    <div class="flex items-center p-4 rounded-xl border-2 transition
                                        <?= $isUserSelected 
                                            ? ($isCorrect ? 'border-green-500 bg-green-500/10' : 'border-red-500 bg-red-500/10') 
                                            : ($isCorrect ? 'border-green-500 border-dashed bg-green-500/5' : 'border-zinc-800 bg-zinc-900/50') ?>">
                                        
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center mr-4 border 
                                            <?= $isUserSelected ? ($isCorrect ? 'border-green-500 bg-green-500 text-white' : 'border-red-500 bg-red-500 text-white') : 'border-zinc-600' ?>">
                                            <?php if ($isUserSelected): ?>
                                                <i class="fa-solid <?= $isCorrect ? 'fa-check' : 'fa-xmark' ?>"></i>
                                            <?php elseif ($isCorrect): ?>
                                                <i class="fa-solid fa-check text-green-500"></i>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <span class="text-lg <?= $isUserSelected ? ($isCorrect ? 'text-green-200' : 'text-red-200') : ($isCorrect ? 'text-green-500' : 'text-zinc-400') ?>">
                                            <?= htmlspecialchars($opt['text']) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($q['explanation'])): ?>
                            <div class="mt-6 p-5 bg-blue-500/10 border-l-4 border-blue-500 text-blue-200 text-base rounded-r-xl shadow-inner">
                                <strong><i class="fa-solid fa-circle-info mr-2"></i> Explanation:</strong> <?= htmlspecialchars($q['explanation']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Slider Navigation -->
            <div class="flex justify-between items-center mt-6 pt-6 border-t border-zinc-800 flex-shrink-0">
                <button @click="reviewIndex > 0 ? reviewIndex-- : null" 
                        :class="reviewIndex === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-zinc-800 text-white'"
                        class="px-6 py-3 rounded-xl bg-zinc-900 text-zinc-400 transition font-bold flex items-center border border-zinc-800">
                    <i class="fa-solid fa-chevron-left mr-2"></i> Prev
                </button>
                <button @click="reviewIndex < total - 1 ? reviewIndex++ : null" 
                        :class="reviewIndex === total - 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-zinc-800 text-white'"
                        class="px-6 py-3 rounded-xl bg-zinc-900 text-zinc-400 transition font-bold flex items-center border border-zinc-800">
                    Next <i class="fa-solid fa-chevron-right ml-2"></i>
                </button>
            </div>
        </div>
    </div>

</body>
</html>
