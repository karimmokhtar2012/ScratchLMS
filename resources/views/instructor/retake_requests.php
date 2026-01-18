<?php ob_start(); ?>

<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Retake Requests</h1>
            <p class="text-zinc-400 mt-2">Manage student requests to retake quizzes.</p>
        </div>
    </div>

    <?php if (empty($attemptRequests)): ?>
        <div class="bg-surface rounded-2xl border border-zinc-700/50 p-12 text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-zinc-800/50 mb-6 ring-1 ring-zinc-700/50">
                <i class="fa-solid fa-rotate-right text-3xl text-zinc-600"></i>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">No Requests</h3>
            <p class="text-zinc-400">There are no pending quiz retake requests.</p>
        </div>
    <?php else: ?>
        <div class="bg-surface rounded-2xl border border-zinc-700/50 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-zinc-800/50 border-b border-zinc-700/50">
                            <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Course</th>
                            <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Quiz</th>
                            <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Requested</th>
                            <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800">
                        <?php foreach ($attemptRequests as $req): ?>
                            <tr class="hover:bg-zinc-800/30 transition group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full bg-zinc-700 flex items-center justify-center text-white font-bold text-sm border border-zinc-600">
                                            <?= strtoupper(substr($req['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-white"><?= htmlspecialchars($req['name']) ?></div>
                                            <div class="text-xs text-zinc-500 group-hover:text-zinc-400 transition"><?= htmlspecialchars($req['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-300 font-semibold">
                                    <?= htmlspecialchars($req['course_title']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-400">
                                    <?= htmlspecialchars($req['quiz_title']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-500">
                                    <?= date('M j, Y', strtotime($req['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <form action="/instructor/request/<?= $req['id'] ?>/approve" method="POST" onsubmit="return confirm('This will delete previous attempts and scores for this quiz. Continue?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="return" value="/instructor/requests">
                                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-4 py-2 rounded-lg transition shadow-lg shadow-emerald-500/20">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="/instructor/request/<?= $req['id'] ?>/reject" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="return" value="/instructor/requests">
                                            <button type="submit" class="bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-bold px-4 py-2 rounded-lg transition border border-zinc-700">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../layouts/app.php'; 
?>
