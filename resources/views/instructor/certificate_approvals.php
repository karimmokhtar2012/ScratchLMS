<?php ob_start(); ?>

<div class="max-w-7xl mx-auto">
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Certificate Approvals</h1>
            <p class="text-zinc-400 mt-2">Review completed enrollments and issue certificates when auto-issue is disabled.</p>
        </div>
        <div class="bg-surface border border-zinc-800 rounded-xl px-6 py-3 flex items-center space-x-8">
            <div class="text-center">
                <div class="text-2xl font-bold text-amber-400"><?= count($approvals) ?></div>
                <div class="text-[10px] text-zinc-500 uppercase tracking-widest font-bold">Pending Approvals</div>
            </div>
        </div>
    </div>

    <div class="bg-surface rounded-2xl border border-zinc-700/50 overflow-hidden shadow-xl">
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
                    <?php if (empty($approvals)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-zinc-500">
                                <i class="fa-solid fa-award text-4xl mb-4 block opacity-20"></i>
                                No certificate approvals are waiting.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($approvals as $approval): ?>
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
    </div>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../layouts/app.php'; 
?>
