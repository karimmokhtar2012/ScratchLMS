<?php ob_start(); ?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <a href="/instructor/course/<?= $course['id'] ?>/edit" class="text-zinc-500 hover:text-white text-sm flex items-center mb-2 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Builder
            </a>
            <h1 class="text-3xl font-bold text-white tracking-tight">Student Analytics</h1>
            <p class="text-zinc-400 mt-2">Manage enrollments and track student progress for <span class="text-blue-400 font-semibold"><?= htmlspecialchars($course['title']) ?></span></p>
        </div>
        
        <div class="bg-surface border border-zinc-800 rounded-xl px-6 py-3 flex items-center space-x-8">
            <div class="text-center">
                <div class="text-2xl font-bold text-white"><?= count($students) ?></div>
                <div class="text-[10px] text-zinc-500 uppercase tracking-widest font-bold">Total Students</div>
            </div>
            <div class="w-px h-8 bg-zinc-800"></div>
            <div class="text-center">
                <?php 
                    $completedCount = count(array_filter($students, fn($s) => $s['is_completed']));
                ?>
                <div class="text-2xl font-bold text-green-500"><?= $completedCount ?></div>
                <div class="text-[10px] text-zinc-500 uppercase tracking-widest font-bold">Completed</div>
            </div>
        </div>
    </div>

    <!-- Manual Enrollment -->
    <div class="bg-surface rounded-2xl border border-zinc-700/50 p-6 shadow-xl mb-8">
        <h3 class="text-lg font-bold text-white mb-4">Manual Enrollment</h3>
        <form action="/instructor/course/<?= $course['id'] ?>/enroll-student" method="POST" class="flex items-end gap-4">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="w-full md:w-1/3">
                <label for="student_email" class="block text-xs font-medium text-zinc-400 mb-1">Student Email</label>
                <input type="email" name="email" id="student_email" required 
                       class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500/50 placeholder-zinc-600"
                       placeholder="student@example.com">
            </div>
            <button type="submit" class="bg-zinc-700 hover:bg-zinc-600 text-white font-medium py-2 px-6 rounded-lg transition shadow-lg border border-zinc-600 flex-shrink-0">
                <i class="fa-solid fa-user-plus mr-2"></i> Add Student
            </button>
        </form>
    </div>

    <!-- Students Table -->
    <div class="bg-surface rounded-2xl border border-zinc-700/50 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-zinc-800/50 border-b border-zinc-700/50">
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Student</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Enrollment Date</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Progress</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-zinc-400 uppercase tracking-wider text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800">
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-zinc-500">
                                <i class="fa-solid fa-users-slash text-4xl mb-4 block opacity-20"></i>
                                No students have enrolled in this course yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <tr class="hover:bg-zinc-800/30 transition group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full bg-zinc-700 flex items-center justify-center text-white font-bold text-sm border border-zinc-600">
                                            <?= strtoupper(substr($student['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-white"><?= htmlspecialchars($student['name']) ?></div>
                                            <div class="text-xs text-zinc-500 group-hover:text-zinc-400 transition"><?= htmlspecialchars($student['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-400">
                                    <?= date('M j, Y', strtotime($student['enrolled_at'])) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="w-full max-w-[120px]">
                                        <div class="flex justify-between text-[10px] text-zinc-500 mb-1">
                                            <span><?= round($student['progress_percent']) ?>%</span>
                                        </div>
                                        <div class="w-full bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-blue-500 h-full rounded-full transition-all duration-500" style="width: <?= $student['progress_percent'] ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($student['is_completed']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500/10 text-green-400 border border-green-500/20">
                                            <i class="fa-solid fa-check-circle mr-1"></i> Completed
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                            In Progress
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="/instructor/course/<?= $course['id'] ?>/student/<?= $student['id'] ?>" class="text-zinc-400 hover:text-white transition p-2 rounded hover:bg-zinc-700" title="View Activity">
                                        <i class="fa-solid fa-chart-line"></i>
                                    </a>
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
