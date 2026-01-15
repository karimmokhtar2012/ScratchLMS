<?php ob_start(); ?>

<div class="space-y-8">
    
    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="/admin/users" class="glass-panel p-5 rounded-2xl border border-zinc-800 hover:border-indigo-500/40 transition group flex items-center justify-between">
            <div>
                <p class="text-xs text-zinc-500 uppercase tracking-widest font-bold">Manage</p>
                <h3 class="text-lg font-bold text-white mt-1">Users</h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center group-hover:bg-indigo-500 group-hover:text-white transition">
                <i class="fa-solid fa-users-gear"></i>
            </div>
        </a>
        <a href="/admin/courses" class="glass-panel p-5 rounded-2xl border border-zinc-800 hover:border-emerald-500/40 transition group flex items-center justify-between">
            <div>
                <p class="text-xs text-zinc-500 uppercase tracking-widest font-bold">Manage</p>
                <h3 class="text-lg font-bold text-white mt-1">Courses</h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center group-hover:bg-emerald-500 group-hover:text-white transition">
                <i class="fa-solid fa-layer-group"></i>
            </div>
        </a>
        <a href="/courses" target="_blank" class="glass-panel p-5 rounded-2xl border border-zinc-800 hover:border-amber-500/40 transition group flex items-center justify-between">
            <div>
                <p class="text-xs text-zinc-500 uppercase tracking-widest font-bold">Preview</p>
                <h3 class="text-lg font-bold text-white mt-1">Public Catalog</h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center group-hover:bg-amber-500 group-hover:text-white transition">
                <i class="fa-solid fa-up-right-from-square"></i>
            </div>
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Metric 1 -->
        <div class="glass-panel p-6 rounded-2xl relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-500/10 rounded-full group-hover:scale-110 transition duration-500"></div>
            <p class="text-zinc-500 text-xs font-bold uppercase tracking-widest mb-1">Total Revenue</p>
            <h3 class="text-3xl font-bold text-white">$<?= number_format($stats['total_revenue'], 2) ?></h3>
        </div>
        <!-- Metric 2 -->
        <div class="glass-panel p-6 rounded-2xl relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/10 rounded-full group-hover:scale-110 transition duration-500"></div>
            <p class="text-zinc-500 text-xs font-bold uppercase tracking-widest mb-1">Active Users</p>
            <h3 class="text-3xl font-bold text-white"><?= number_format($stats['total_users']) ?></h3>
            <span class="text-xs text-emerald-400 mt-2 block">+<?= $stats['new_users'] ?> this week</span>
        </div>
        <!-- Metric 3 -->
        <div class="glass-panel p-6 rounded-2xl relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-500/10 rounded-full group-hover:scale-110 transition duration-500"></div>
            <p class="text-zinc-500 text-xs font-bold uppercase tracking-widest mb-1">Courses Published</p>
            <h3 class="text-3xl font-bold text-white"><?= $stats['published_courses'] ?> <span class="text-lg text-zinc-600 font-normal">/ <?= $stats['total_courses'] ?></span></h3>
        </div>
        <!-- Metric 4 -->
        <div class="glass-panel p-6 rounded-2xl relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-purple-500/10 rounded-full group-hover:scale-110 transition duration-500"></div>
            <p class="text-zinc-500 text-xs font-bold uppercase tracking-widest mb-1">System Status</p>
            <h3 class="text-3xl font-bold text-white flex items-center gap-2">
                <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span> Online
            </h3>
        </div>
    </div>

    <!-- Recent Signups -->
    <div class="glass-panel rounded-2xl p-8">
        <h3 class="text-lg font-bold text-white mb-6">Recent Signups</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-xs text-zinc-500 border-b border-zinc-800">
                        <th class="pb-4 font-bold uppercase tracking-wider pl-4">User</th>
                        <th class="pb-4 font-bold uppercase tracking-wider">Email</th>
                        <th class="pb-4 font-bold uppercase tracking-wider text-right pr-4">Joined</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/50">
                    <?php if (!empty($recent_users)): ?>
                        <?php foreach ($recent_users as $user): ?>
                        <tr class="group hover:bg-white/5 transition">
                            <td class="py-4 pl-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-zinc-800 flex items-center justify-center text-xs font-bold text-zinc-400">
                                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                    </div>
                                    <span class="text-sm font-medium text-white"><?= htmlspecialchars($user['name']) ?></span>
                                </div>
                            </td>
                            <td class="py-4 text-sm text-zinc-400"><?= htmlspecialchars($user['email']) ?></td>
                            <td class="py-4 text-sm text-zinc-500 text-right pr-4"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="py-8 text-center text-zinc-500 text-sm">No recent signups yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php 
$content = ob_get_clean(); 
$title = 'Dashboard Overview';
require __DIR__ . '/../layouts/admin.php'; 
?>
