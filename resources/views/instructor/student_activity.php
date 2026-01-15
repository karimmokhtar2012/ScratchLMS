<?php ob_start(); ?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <a href="/instructor/course/<?= $course['id'] ?>/students" class="text-zinc-500 hover:text-white text-sm flex items-center mb-2 transition">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back to Student List
        </a>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white tracking-tight flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-sm">
                        <?= strtoupper(substr($student['name'], 0, 1)) ?>
                    </div>
                    <?= htmlspecialchars($student['name']) ?>
                </h1>
                <p class="text-zinc-400 mt-1 ml-14"><?= htmlspecialchars($student['email']) ?></p>
            </div>
            <div class="text-right">
                <div class="text-sm text-zinc-500 uppercase font-bold tracking-wider">Lessons Completed</div>
                <div class="text-3xl font-bold text-white"><?= $completedCount ?></div>
            </div>
        </div>
    </div>

    <!-- Pending Requests -->
    <?php if (!empty($requests)): ?>
        <div class="mb-8 bg-amber-500/10 border border-amber-500/20 rounded-2xl p-6">
            <h3 class="font-bold text-amber-400 mb-4 flex items-center"><i class="fa-solid fa-bell mr-2"></i> Pending Reset Requests</h3>
            <div class="space-y-3">
                <?php foreach ($requests as $req): ?>
                    <div class="flex items-center justify-between bg-zinc-900/80 p-4 rounded-xl border border-amber-500/10">
                        <div>
                            <p class="text-white font-bold">Request to retake: <span class="text-indigo-400"><?= htmlspecialchars($req['quiz_title']) ?></span></p>
                            <p class="text-xs text-zinc-500">Requested on <?= date('M j, Y', strtotime($req['created_at'])) ?></p>
                        </div>
                                        <div class="flex gap-2 justify-end">
                                            <form action="/instructor/request/<?= $req['id'] ?>/approve" method="POST" onsubmit="return confirm('⚠️ WARNING: This will DELETE all previous attempts and scores for this student on this quiz. This action cannot be undone. Proceed?');">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <button type="submit" class="text-xs bg-green-600 hover:bg-green-500 text-white px-3 py-1.5 rounded-lg transition">Approve & Reset</button>
                                            </form>
                                            <form action="/instructor/request/<?= $req['id'] ?>/reject" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <button type="submit" class="text-xs bg-red-600 hover:bg-red-500 text-white px-3 py-1.5 rounded-lg transition">Reject</button>
                                            </form>
                                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Quiz Performance -->
    <div class="bg-surface rounded-2xl border border-zinc-700/50 overflow-hidden shadow-xl">
        <div class="px-6 py-4 border-b border-zinc-800 flex justify-between items-center bg-zinc-900/50">
            <h3 class="font-bold text-white text-lg"><i class="fa-solid fa-clipboard-list mr-2 text-indigo-500"></i> Quiz Performance</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-zinc-800/30 text-xs font-bold text-zinc-500 uppercase tracking-wider">
                        <th class="px-6 py-3">Quiz Name</th>
                        <th class="px-6 py-3">Date Taken</th>
                        <th class="px-6 py-3">Score</th>
                        <th class="px-6 py-3">Result</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800">
                    <?php if (empty($attempts)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-zinc-500">No quiz attempts recorded for this student.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attempts as $attempt): ?>
                            <tr class="hover:bg-zinc-800/30 transition">
                                <td class="px-6 py-4 font-medium text-white"><?= htmlspecialchars($attempt['quiz_title']) ?></td>
                                <td class="px-6 py-4 text-sm text-zinc-400"><?= date('M j, Y H:i', strtotime($attempt['started_at'])) ?></td>
                                <td class="px-6 py-4 font-bold text-white"><?= $attempt['score'] ?>%</td>
                                <td class="px-6 py-4">
                                    <?php if ($attempt['completed_at']): ?>
                                        <span class="px-2 py-1 rounded text-[10px] font-bold uppercase <?= $attempt['is_passed'] ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20' ?>">
                                            <?= $attempt['is_passed'] ? 'Passed' : 'Failed' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-yellow-500 text-[10px] uppercase font-bold">In Progress</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($attempt['completed_at']): ?>
                                        <a href="/learn/quiz/review/<?= $attempt['id'] ?>" class="text-indigo-400 hover:text-white text-xs font-bold transition flex items-center justify-end gap-1">
                                            <i class="fa-solid fa-eye"></i> View Answers
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../layouts/app.php'; 
?>
