<?php ob_start(); ?>

<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Grading Queue</h1>
            <p class="text-zinc-400 mt-2">Review and grade pending student submissions.</p>
        </div>
        <div>
            <a href="/instructor/requests" class="text-sm font-medium text-zinc-400 hover:text-white transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Requests
            </a>
        </div>
    </div>

    <?php if (empty($gradingQueue)): ?>
        <div class="bg-surface rounded-2xl border border-zinc-700/50 p-12 text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-zinc-800/50 mb-6 ring-1 ring-zinc-700/50">
                <i class="fa-solid fa-clipboard-check text-3xl text-zinc-600"></i>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">All Caught Up!</h3>
            <p class="text-zinc-400">There are no submissions waiting for manual grading.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-6">
            <?php foreach ($gradingQueue as $item): ?>
                <div class="bg-surface rounded-2xl border border-zinc-700/50 overflow-hidden shadow-lg hover:border-zinc-600 transition duration-300">
                    <!-- Header -->
                    <div class="bg-zinc-800/30 px-6 py-4 border-b border-zinc-700/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-sm border border-indigo-500/30">
                                <?= strtoupper(substr($item['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-white"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="text-xs text-zinc-500"><?= htmlspecialchars($item['email']) ?></div>
                            </div>
                        </div>
                        <div class="text-right md:text-right">
                            <div class="text-sm font-medium text-zinc-300"><?= htmlspecialchars($item['course_title']) ?></div>
                            <div class="text-xs text-zinc-500"><?= htmlspecialchars($item['lesson_title']) ?></div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Question & Answer -->
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Question</h4>
                                <div class="text-sm text-zinc-200 bg-zinc-900/50 p-4 rounded-xl border border-zinc-800">
                                    <?= htmlspecialchars($item['question_text']) ?>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Student Submission</h4>
                                <div class="text-sm text-zinc-300 bg-zinc-900 p-4 rounded-xl border border-zinc-800 font-mono whitespace-pre-wrap">
                                    <?= htmlspecialchars($item['submission_text'] ?? '') ?>
                                </div>
                                <div class="mt-2">
                                    <a href="/learn/quiz/review/<?= $item['attempt_id'] ?>" target="_blank" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 transition flex items-center">
                                        View Full Quiz Attempt <i class="fa-solid fa-external-link-alt ml-1.5"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Grading Controls -->
                        <div class="bg-zinc-900/30 rounded-xl p-6 border border-zinc-800/50 flex flex-col justify-between">
                            <div>
                                <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-4">Grading & Feedback</h4>
                                
                                <?php if ($item['ai_score'] !== null): ?>
                                    <div class="mb-6 p-4 rounded-xl bg-indigo-500/10 border border-indigo-500/20">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-bold text-indigo-400 uppercase">AI Suggested Score</span>
                                            <span class="text-lg font-bold text-white"><?= $item['ai_score'] ?> <span class="text-sm text-zinc-500 font-normal">/ <?= (int)$item['points'] ?></span></span>
                                        </div>
                                        <?php if (!empty($item['ai_feedback'])): ?>
                                            <div class="text-xs text-zinc-400 italic mb-3">"<?= htmlspecialchars($item['ai_feedback']) ?>"</div>
                                        <?php endif; ?>
                                        
                                        <form action="/instructor/grading/score" method="post">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="grading_id" value="<?= $item['grading_id'] ?>">
                                            <input type="hidden" name="final_score" value="<?= $item['ai_score'] ?>">
                                            <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold transition shadow-lg shadow-indigo-500/20">
                                                Accept AI Score
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <form action="/instructor/grading/score" method="post" class="mt-4">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="grading_id" value="<?= $item['grading_id'] ?>">
                                
                                <div class="mb-4">
                                    <label class="block text-xs font-bold text-zinc-400 mb-2">Final Score (Max: <?= (int)$item['points'] ?>)</label>
                                    <input type="number" name="final_score" min="0" max="<?= (int)$item['points'] ?>" step="0.1" required
                                           class="w-full bg-zinc-950 border border-zinc-700 text-white rounded-lg px-4 py-3 focus:outline-none focus:border-indigo-500 transition text-lg font-bold"
                                           placeholder="Enter score...">
                                </div>
                                
                                <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold transition shadow-lg shadow-emerald-500/20 flex items-center justify-center">
                                    <i class="fa-solid fa-check mr-2"></i> Submit Grade
                                </button>
                            </form>
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
