<?php ob_start(); ?>

<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white tracking-tight">Account Settings</h1>
        <p class="text-zinc-400 mt-2">Manage your public profile and account security.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Sidebar / User Card -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-surface rounded-2xl border border-zinc-700/50 p-8 shadow-xl text-center">
                <div class="w-24 h-24 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 mx-auto mb-4 flex items-center justify-center text-3xl font-bold text-white shadow-lg shadow-blue-500/20">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <h2 class="text-xl font-bold text-white"><?= htmlspecialchars($user['name']) ?></h2>
                <p class="text-zinc-500 text-sm mb-6"><?= htmlspecialchars($user['email']) ?></p>
                
                <div class="grid grid-cols-2 gap-4 border-t border-zinc-800 pt-6">
                    <div>
                        <div class="text-2xl font-bold text-white"><?= $stats['enrolled_courses'] ?></div>
                        <div class="text-[10px] text-zinc-500 uppercase font-bold tracking-widest">Courses</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?= $stats['completed_lessons'] ?></div>
                        <div class="text-[10px] text-zinc-500 uppercase font-bold tracking-widest">Lessons</div>
                    </div>
                </div>
            </div>

            <div class="bg-surface rounded-2xl border border-zinc-700/50 p-6 shadow-xl">
                <h3 class="text-white font-bold mb-4 flex items-center">
                    <i class="fa-solid fa-shield-halved mr-2 text-blue-500"></i> Data Privacy
                </h3>
                <p class="text-zinc-400 text-xs leading-relaxed">
                    Your personal information is stored securely. We do not share your data with third parties.
                </p>
            </div>
        </div>

        <!-- Main Form -->
        <div class="lg:col-span-8 space-y-6">
            <div class="bg-surface rounded-2xl border border-zinc-700/50 p-8 shadow-xl">
                <form action="/profile/update" method="POST" class="space-y-8">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <!-- General Section -->
                    <section>
                        <h3 class="text-lg font-bold text-white mb-6 flex items-center">
                            <i class="fa-solid fa-user-gear mr-3 text-zinc-500"></i> Personal Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Full Name</label>
                                <input type="text" name="name" id="name" required value="<?= htmlspecialchars($user['name']) ?>"
                                    class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition">
                            </div>
                            <div>
                                <label for="email" class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Email Address</label>
                                <input type="email" name="email" id="email" required value="<?= htmlspecialchars($user['email']) ?>"
                                    class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition">
                            </div>
                        </div>
                    </section> section

                    <hr class="border-zinc-800">

                    <!-- Security Section -->
                    <section x-data="{ changePassword: false }">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-white flex items-center">
                                <i class="fa-solid fa-lock mr-3 text-zinc-500"></i> Account Security
                            </h3>
                            <button type="button" @click="changePassword = !changePassword" class="text-xs text-blue-400 hover:text-blue-300 font-bold uppercase tracking-wider">
                                <span x-show="!changePassword">Change Password</span>
                                <span x-show="changePassword">Cancel</span>
                            </button>
                        </div>

                        <div x-show="changePassword" x-transition class="space-y-6 bg-zinc-900/50 p-6 rounded-2xl border border-zinc-800">
                            <div>
                                <label for="current_password" class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Current Password</label>
                                <input type="password" name="current_password" id="current_password"
                                    class="w-full bg-zinc-800 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition">
                            </div>
                            <div>
                                <label for="new_password" class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">New Password</label>
                                <input type="password" name="new_password" id="new_password"
                                    class="w-full bg-zinc-800 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition">
                                <p class="text-[10px] text-zinc-600 mt-2">Must be at least 6 characters long.</p>
                            </div>
                        </div>
                        
                        <div x-show="!changePassword" class="text-zinc-500 text-sm py-4">
                            Your password is encrypted and cannot be viewed by anyone.
                        </div>
                    </section>

                    <div class="pt-6 border-t border-zinc-800 flex justify-end">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-indigo-500/20 transform hover:-translate-y-0.5 transition duration-200">
                            Save Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/layouts/app.php'; 
?>
