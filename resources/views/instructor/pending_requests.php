<?php ob_start(); ?>

<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Pending Requests</h1>
            <p class="text-zinc-400 mt-2">Approve quiz retakes and issue certificates when manual review is required.</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="bg-surface border border-zinc-800 rounded-xl px-5 py-3 text-center">
                <div class="text-xl font-bold text-amber-400"><?= count($attemptRequests) ?></div>
                <div class="text-[10px] text-zinc-500 uppercase tracking-widest font-bold">Quiz Requests</div>
            </div>
            <div class="bg-surface border border-zinc-800 rounded-xl px-5 py-3 text-center">
                <div class="text-xl font-bold text-emerald-400"><?= count($certificateApprovals) ?></div>
                <div class="text-[10px] text-zinc-500 uppercase tracking-widest font-bold">Cert Approvals</div>
            </div>
        </div>
    </div>

    <section id="attempts" class="bg-surface rounded-2xl border border-zinc-700/50 overflow-hidden shadow-xl">
        <div class="px-6 py-4 border-b border-zinc-800 flex items-center justify-between">
            <h2 class="text-sm font-bold text-amber-400 uppercase tracking-wider">Quiz Retake Requests</h2>
        </div>
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
                    <?php if (empty($attemptRequests)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-zinc-800/50 mb-4 ring-1 ring-zinc-700/50">
                                    <i class="fa-solid fa-clipboard-check text-2xl text-zinc-600"></i>
                                </div>
                                <h3 class="text-zinc-400 font-medium mb-1">All Caught Up</h3>
                                <p class="text-zinc-500 text-sm">No quiz retake requests waiting for review.</p>
                            </td>
                        </tr>
                    <?php else: ?>
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
                                            <input type="hidden" name="return" value="/instructor/requests#attempts">
                                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-4 py-2 rounded-lg transition shadow-lg shadow-emerald-500/20">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="/instructor/request/<?= $req['id'] ?>/reject" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="return" value="/instructor/requests#attempts">
                                            <button type="submit" class="bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-bold px-4 py-2 rounded-lg transition border border-zinc-700">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section id="certificates" class="bg-surface rounded-2xl border border-zinc-700/50 overflow-hidden shadow-xl">
        <div class="px-6 py-4 border-b border-zinc-800 flex items-center justify-between">
            <h2 class="text-sm font-bold text-emerald-400 uppercase tracking-wider">Certificate Approvals</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-zinc-800/50 border-b border-zinc-700/50">
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Student</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Course</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Progress</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Enrollment Date</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800">
                    <?php if (empty($certificateApprovals)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-zinc-800/50 mb-4 ring-1 ring-zinc-700/50">
                                    <i class="fa-solid fa-award text-2xl text-zinc-600"></i>
                                </div>
                                <h3 class="text-zinc-400 font-medium mb-1">No Pending Certificates</h3>
                                <p class="text-zinc-500 text-sm">There are no certificate approvals waiting.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($certificateApprovals as $approval): ?>
                            <tr class="hover:bg-zinc-800/30 transition group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full bg-zinc-700 flex items-center justify-center text-white font-bold text-sm border border-zinc-600">
                                            <?= strtoupper(substr($approval['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-white"><?= htmlspecialchars($approval['name']) ?></div>
                                            <div class="text-xs text-zinc-500 group-hover:text-zinc-400 transition"><?= htmlspecialchars($approval['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-300 font-semibold">
                                    <?= htmlspecialchars($approval['course_title']) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="w-full max-w-[140px]">
                                        <div class="flex justify-between text-[10px] text-zinc-500 mb-1">
                                            <span><?= round($approval['progress_percent']) ?>%</span>
                                        </div>
                                        <div class="w-full bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: <?= $approval['progress_percent'] ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-400">
                                    <?= date('M j, Y', strtotime($approval['enrolled_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form action="/instructor/certificates/issue" method="post" class="inline-flex">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <input type="hidden" name="enrollment_id" value="<?= $approval['enrollment_id'] ?>">
                                        <input type="hidden" name="return" value="/instructor/requests#certificates">
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-4 py-2 rounded-lg transition shadow-lg shadow-emerald-500/20">
                                            Issue Certificate
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section id="grading" class="bg-surface rounded-2xl border border-zinc-700/50 overflow-hidden shadow-xl">
        <div class="px-6 py-4 border-b border-zinc-800 flex items-center justify-between">
            <h2 class="text-sm font-bold text-indigo-400 uppercase tracking-wider">Manual Grading Queue</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-zinc-800/50 border-b border-zinc-700/50">
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Student</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Course</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Prompt</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">AI Score</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Final Score</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800">
                    <?php if (empty($gradingQueue)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-zinc-500">
                                <i class="fa-solid fa-pen-to-square text-4xl mb-4 block opacity-20"></i>
                                No submissions waiting for grading.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($gradingQueue as $item): ?>
                            <tr class="hover:bg-zinc-800/30 transition group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full bg-zinc-700 flex items-center justify-center text-white font-bold text-sm border border-zinc-600">
                                            <?= strtoupper(substr($item['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-white"><?= htmlspecialchars($item['name']) ?></div>
                                            <div class="text-xs text-zinc-500 group-hover:text-zinc-400 transition"><?= htmlspecialchars($item['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-300 font-semibold">
                                    <?= htmlspecialchars($item['course_title']) ?>
                                    <div class="text-xs text-zinc-500"><?= htmlspecialchars($item['lesson_title']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-400 max-w-sm truncate" title="<?= htmlspecialchars($item['question_text']) ?>">
                                    <?= htmlspecialchars($item['question_text']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-400">
                                    <?= $item['ai_score'] !== null ? $item['ai_score'] : '—' ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form action="/instructor/grading/score" method="post" class="inline-flex items-center gap-2">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <input type="hidden" name="grading_id" value="<?= $item['grading_id'] ?>">
                                        <input type="number" name="final_score" min="0" max="<?= (int) $item['points'] ?>" step="0.1"
                                               class="w-24 bg-zinc-900 border border-zinc-700 text-white rounded-lg px-2 py-1 text-xs focus:outline-none focus:border-indigo-500"
                                               placeholder="<?= (int) $item['points'] ?>">
                                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-3 py-2 rounded-lg transition shadow-lg shadow-indigo-500/20">
                                            Save
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../layouts/app.php'; 
?>
