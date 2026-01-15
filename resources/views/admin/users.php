<?php
// Defensive coding: Ensure variables exist and calculate stats before HTML output
$users = isset($users) && is_array($users) ? $users : [];

$totalUsers = count($users);
$instructorCount = 0;
$studentCount = 0;

foreach ($users as $u) {
    $uRoles = $u['role_slugs'] ?? '';
    if (strpos($uRoles, 'instructor') !== false) {
        $instructorCount++;
    }
    if (strpos($uRoles, 'student') !== false) {
        $studentCount++;
    }
}

ob_start(); 
?>

<script>
    function userManager() {
        return {
            search: '',
            showModal: false,
            showEnrollModal: false,
            targetId: null,
            targetName: '',
            currentRole: '',
            selectedUsers: [],
            selectAll: false,
            allUserIds: <?= json_encode(array_column($users, 'id')) ?>,
            bulkAction: '',
            bulkRole: '',
            bulkCourseId: '',
            bulkCanSwitch: '1',
            
            // Enrollment Data
            enrolledCourses: [],
            availableCourses: [],
            selectedCourseId: '',

            init() {
                this.$watch('selectedUsers', () => {
                    this.selectAll = this.selectedUsers.length === this.allUserIds.length;
                });
            },

            matchesSearch(name, email) {
                if (this.search === '') return true;
                const query = this.search.toLowerCase();
                return name.includes(query) || email.includes(query);
            },

            openRoleModal(id, role, name) {
                this.targetId = id;
                this.currentRole = role === 'master' ? 'master_instructor' : role;
                this.targetName = name;
                this.showModal = true;
            },

            openEnrollModal(id, name) {
                this.targetId = id;
                this.targetName = name;
                this.showEnrollModal = true;
                this.fetchEnrollments();
            },

            fetchEnrollments() {
                fetch(`/admin/users/enrollments/${this.targetId}`)
                    .then(res => res.json())
                    .then(data => {
                        this.enrolledCourses = data.enrolled;
                        this.availableCourses = data.available;
                        this.selectedCourseId = ''; // Reset selection
                    });
            },

            enrollUser() {
                if (!this.selectedCourseId) return;
                fetch('/admin/users/enroll', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `user_id=${this.targetId}&course_id=${this.selectedCourseId}`
                }).then(res => res.json()).then(data => {
                    if(data.status === 'success') this.fetchEnrollments();
                });
            },

            unenrollUser(courseId) {
                if(!confirm('Remove user from this course?')) return;
                fetch('/admin/users/unenroll', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `user_id=${this.targetId}&course_id=${courseId}`
                }).then(res => res.json()).then(data => {
                    if(data.status === 'success') this.fetchEnrollments();
                });
            },

            resetProgress(courseId) {
                if(!confirm('Reset all progress for this user in this course? This cannot be undone.')) return;
                fetch('/admin/users/reset-progress', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `user_id=${this.targetId}&course_id=${courseId}`
                }).then(res => res.json()).then(data => {
                    if(data.status === 'success') {
                        alert('Progress reset successfully.');
                        this.fetchEnrollments();
                    }
                });
            },

            setRole(slug) {
                if(!this.targetId) return;
                
                fetch('/admin/users/update-role', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `user_id=${this.targetId}&role_slug=${slug}`
                })
                .then(res => {
                    if (res.ok) {
                        window.location.reload(); 
                    } else {
                        alert('Failed to update role. You cannot change your own role.');
                    }
                });
            },

            toggleSwitch(id, state) {
                fetch('/admin/users/toggle-switch', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `user_id=${id}&can_switch=${state ? 1 : 0}`
                });
            },

            toggleAllUsers() {
                if (this.selectAll) {
                    this.selectedUsers = [...this.allUserIds];
                } else {
                    this.selectedUsers = [];
                }
            },

            applyBulk() {
                if (this.selectedUsers.length === 0 || !this.bulkAction) return;
                const payload = { user_ids: this.selectedUsers };
                let endpoint = '';

                if (this.bulkAction === 'delete') {
                    if (!confirm('Delete selected users? This cannot be undone.')) return;
                    endpoint = '/admin/users/bulk-delete';
                } else if (this.bulkAction === 'role') {
                    if (!this.bulkRole) return;
                    payload.role_slug = this.bulkRole;
                    endpoint = '/admin/users/bulk-role';
                } else if (this.bulkAction === 'switch') {
                    payload.can_switch = this.bulkCanSwitch === '1' ? 1 : 0;
                    endpoint = '/admin/users/bulk-switch';
                } else if (this.bulkAction === 'enroll') {
                    if (!this.bulkCourseId) return;
                    payload.course_id = this.bulkCourseId;
                    endpoint = '/admin/users/bulk-enroll';
                } else if (this.bulkAction === 'unenroll') {
                    if (!this.bulkCourseId) return;
                    payload.course_id = this.bulkCourseId;
                    endpoint = '/admin/users/bulk-unenroll';
                } else if (this.bulkAction === 'reset') {
                    if (!this.bulkCourseId) return;
                    payload.course_id = this.bulkCourseId;
                    endpoint = '/admin/users/bulk-reset';
                }

                fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        alert(data.error || 'Bulk action failed.');
                    }
                });
            }
        }
    }
</script>

<div class="space-y-8" x-data="userManager()">
    
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-6 rounded-3xl bg-zinc-900 border border-zinc-800 flex items-center justify-between group hover:border-zinc-700 transition">
            <div>
                <p class="text-zinc-500 text-xs font-bold uppercase tracking-widest mb-1">Total Users</p>
                <h3 class="text-3xl font-bold text-white"><?= $totalUsers ?></h3>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-zinc-800 flex items-center justify-center text-zinc-400 group-hover:bg-indigo-500 group-hover:text-white transition">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
        <div class="p-6 rounded-3xl bg-zinc-900 border border-zinc-800 flex items-center justify-between group hover:border-zinc-700 transition">
            <div>
                <p class="text-zinc-500 text-xs font-bold uppercase tracking-widest mb-1">Instructors</p>
                <h3 class="text-3xl font-bold text-white"><?= $instructorCount ?></h3>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-zinc-800 flex items-center justify-center text-zinc-400 group-hover:bg-emerald-500 group-hover:text-white transition">
                <i class="fa-solid fa-chalkboard-user"></i>
            </div>
        </div>
        <div class="p-6 rounded-3xl bg-zinc-900 border border-zinc-800 flex items-center justify-between group hover:border-zinc-700 transition">
            <div>
                <p class="text-zinc-500 text-xs font-bold uppercase tracking-widest mb-1">Students</p>
                <h3 class="text-3xl font-bold text-white"><?= $studentCount ?></h3>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-zinc-800 flex items-center justify-center text-zinc-400 group-hover:bg-blue-500 group-hover:text-white transition">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-3xl overflow-hidden shadow-xl">
        
        <!-- Toolbar -->
        <div class="p-6 border-b border-zinc-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h2 class="text-xl font-bold text-white flex items-center">
                <span class="w-2 h-6 bg-indigo-500 rounded-full mr-3"></span>
                User Directory
            </h2>
            <div class="flex flex-col md:flex-row gap-3 md:items-center w-full md:w-auto">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-zinc-500 font-bold">Selected:</span>
                    <span class="text-xs text-white font-bold" x-text="selectedUsers.length"></span>
                </div>
                <div class="flex items-center gap-2">
                    <select x-model="bulkAction" class="bg-zinc-950 border border-zinc-700 text-zinc-300 text-xs rounded-xl px-3 py-2 focus:border-indigo-500 focus:outline-none">
                        <option value="">Bulk Action</option>
                        <option value="delete">Delete Users</option>
                        <option value="role">Set Role</option>
                        <option value="switch">Set Switch Permission</option>
                        <option value="enroll">Enroll In Course</option>
                        <option value="unenroll">Unenroll From Course</option>
                        <option value="reset">Reset Course Progress</option>
                    </select>
                    <select x-show="bulkAction === 'role'" x-model="bulkRole" class="bg-zinc-950 border border-zinc-700 text-zinc-300 text-xs rounded-xl px-3 py-2 focus:border-indigo-500 focus:outline-none">
                        <option value="">Select role</option>
                        <?php if (!empty($roles)): foreach ($roles as $role): ?>
                            <option value="<?= $role['slug'] ?>"><?= $role['name'] ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                    <select x-show="bulkAction === 'switch'" x-model="bulkCanSwitch" class="bg-zinc-950 border border-zinc-700 text-zinc-300 text-xs rounded-xl px-3 py-2 focus:border-indigo-500 focus:outline-none">
                        <option value="1">Allow Switch</option>
                        <option value="0">Disable Switch</option>
                    </select>
                    <select x-show="bulkAction === 'enroll' || bulkAction === 'unenroll' || bulkAction === 'reset'" x-model="bulkCourseId" class="bg-zinc-950 border border-zinc-700 text-zinc-300 text-xs rounded-xl px-3 py-2 focus:border-indigo-500 focus:outline-none">
                        <option value="">Select course</option>
                        <?php if (!empty($courses)): foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['title']) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                    <button @click="applyBulk()" :disabled="selectedUsers.length === 0 || !bulkAction"
                            class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white px-4 py-2 rounded-xl text-xs font-bold transition">
                        Apply
                    </button>
                </div>
                <div class="relative w-full md:w-72">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-500">
                    <i class="fa-solid fa-search"></i>
                </span>
                <input type="text" x-model="search" placeholder="Search by name or email..." 
                       class="w-full bg-zinc-950 border border-zinc-700 text-zinc-300 text-sm rounded-xl py-2.5 pl-10 pr-4 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-zinc-950/50 text-xs font-bold text-zinc-500 uppercase tracking-wider">
                        <th class="py-4 pl-6 w-16">
                            <input type="checkbox" x-model="selectAll" @change="toggleAllUsers()"
                                   class="h-4 w-4 rounded border-zinc-700 bg-zinc-900 text-indigo-500 focus:ring-indigo-500/50">
                        </th>
                        <th class="py-4">ID</th>
                        <th class="py-4">Profile</th>
                        <th class="py-4">Current Role</th>
                        <th class="py-4 text-center">Permissions</th>
                        <th class="py-4 pr-8 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800">
                    <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                    <tr class="group hover:bg-zinc-800/50 transition duration-150" x-show="matchesSearch('<?= htmlspecialchars(addslashes(strtolower($user['name'] ?? '')), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes(strtolower($user['email'] ?? '')), ENT_QUOTES) ?>')">
                        
                        <!-- ID -->
                        <td class="py-4 pl-6">
                            <input type="checkbox" value="<?= $user['id'] ?>" x-model="selectedUsers"
                                   class="h-4 w-4 rounded border-zinc-700 bg-zinc-900 text-indigo-500 focus:ring-indigo-500/50">
                        </td>
                        <td class="py-4 text-xs text-zinc-600 font-mono">#<?= $user['id'] ?></td>
                        
                        <!-- Profile -->
                        <td class="py-4">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-zinc-800 to-zinc-700 flex items-center justify-center text-sm font-bold text-white shadow-inner border border-zinc-700">
                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white group-hover:text-indigo-400 transition"><?= htmlspecialchars($user['name']) ?></p>
                                    <p class="text-xs text-zinc-500 font-mono"><?= htmlspecialchars($user['email']) ?></p>
                                    <p class="text-[10px] text-zinc-600 mt-0.5">Joined <?= date('M d, Y', strtotime($user['created_at'])) ?></p>
                                </div>
                            </div>
                        </td>

                        <!-- Role -->
                        <td class="py-4">
                            <?php 
                                $userRoles = explode(',', $user['role_slugs'] ?? '');
                                $mainRole = 'viewer';
                                if (in_array('admin', $userRoles)) $mainRole = 'admin';
                                elseif (in_array('master_instructor', $userRoles)) $mainRole = 'master';
                                elseif (in_array('instructor', $userRoles)) $mainRole = 'instructor';
                                elseif (in_array('student', $userRoles)) $mainRole = 'student';
                                
                                $roleColors = [
                                    'admin' => 'bg-red-500/10 text-red-400 border-red-500/20',
                                    'master' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
                                    'instructor' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
                                    'student' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                    'viewer' => 'bg-zinc-700/30 text-zinc-400 border-zinc-700/50'
                                ];
                                $colorClass = $roleColors[$mainRole] ?? $roleColors['viewer'];
                            ?>
                            <button @click="openRoleModal(<?= $user['id'] ?>, '<?= $mainRole ?>', '<?= htmlspecialchars($user['name']) ?>')" 
                                class="px-3 py-1.5 rounded-lg text-[10px] font-bold border uppercase tracking-wider transition hover:scale-105 active:scale-95 flex items-center gap-2 <?= $colorClass ?>">
                                <span><?= ucfirst(str_replace('_', ' ', $mainRole)) ?></span>
                                <i class="fa-solid fa-pen opacity-50"></i>
                            </button>
                        </td>

                        <!-- Permissions Toggle -->
                        <td class="py-4 text-center">
                            <label class="inline-flex items-center cursor-pointer group/toggle" title="Allow user to switch between Instructor/Student view">
                                <input type="checkbox" value="" class="sr-only peer" 
                                    <?= $user['can_switch_role'] ? 'checked' : '' ?>
                                    @change="toggleSwitch(<?= $user['id'] ?>, $el.checked)">
                                <div class="w-11 h-6 bg-zinc-800 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-zinc-400 after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600 peer-checked:after:bg-white transition-colors duration-300 border border-zinc-700"></div>
                                <span class="ml-3 text-xs font-medium text-zinc-500 group-hover/toggle:text-white transition">Can Switch View</span>
                            </label>
                        </td>

                        <!-- Actions -->
                        <td class="py-4 pr-8 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="openEnrollModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" 
                                    class="w-8 h-8 rounded-lg bg-zinc-800 border border-zinc-700 flex items-center justify-center text-zinc-400 hover:text-indigo-400 hover:border-indigo-500/50 transition"
                                    title="Manage Enrollments & Progress">
                                    <i class="fa-solid fa-graduation-cap"></i>
                                </button>
                                
                                <form action="/admin/users/delete/<?= $user['id'] ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <button type="submit" class="w-8 h-8 rounded-lg bg-zinc-800 border border-zinc-700 flex items-center justify-center text-zinc-400 hover:text-red-400 hover:border-red-500/50 transition" title="Delete User">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if (empty($users)): ?>
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-zinc-800 rounded-full flex items-center justify-center mx-auto mb-4 text-zinc-600">
                        <i class="fa-solid fa-users-slash text-2xl"></i>
                    </div>
                    <h3 class="text-zinc-300 font-bold">No users found</h3>
                    <p class="text-zinc-500 text-sm mt-1">There are no registered users in the system yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Role Selection Modal -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div @click.away="showModal = false" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="bg-zinc-900 border border-zinc-800 w-full max-w-md rounded-3xl shadow-2xl overflow-hidden relative">
            
            <!-- Decorative bg -->
            <div class="absolute top-0 inset-x-0 h-32 bg-gradient-to-b from-indigo-500/10 to-transparent pointer-events-none"></div>

            <div class="p-8 relative z-10">
                <div class="w-12 h-12 bg-zinc-800 rounded-2xl flex items-center justify-center mb-4 border border-zinc-700 shadow-lg">
                    <i class="fa-solid fa-user-tag text-indigo-400 text-xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-white">Change Role</h3>
                <p class="text-zinc-400 text-sm mt-2">Update permissions for <span x-text="targetName" class="text-white font-bold"></span>.</p>
            
                <div class="mt-6 space-y-2">
                    <?php if(!empty($roles)): foreach ($roles as $role): ?>
                        <button @click="setRole('<?= $role['slug'] ?>')" 
                            class="w-full text-left px-4 py-3 rounded-xl flex items-center justify-between group hover:bg-zinc-800 transition border border-zinc-800 hover:border-zinc-700"
                            :class="currentRole === '<?= $role['slug'] ?>' ? 'bg-indigo-600 border-indigo-500 shadow-lg shadow-indigo-500/20' : 'bg-zinc-950'">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full" :class="currentRole === '<?= $role['slug'] ?>' ? 'bg-white' : 'bg-zinc-700'"></div>
                                <span class="text-sm font-bold" :class="currentRole === '<?= $role['slug'] ?>' ? 'text-white' : 'text-zinc-400 group-hover:text-zinc-200'"><?= $role['name'] ?></span>
                            </div>
                            <i x-show="currentRole === '<?= $role['slug'] ?>'" class="fa-solid fa-check text-white text-sm"></i>
                        </button>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <div class="p-4 bg-zinc-950/50 border-t border-zinc-800 flex justify-end">
                <button @click="showModal = false" class="text-zinc-400 hover:text-white text-xs font-bold uppercase tracking-wider px-6 py-3 transition">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Enrollments Management Modal -->
    <div x-show="showEnrollModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div @click.away="showEnrollModal = false" 
             class="bg-zinc-900 border border-zinc-800 w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden relative flex flex-col max-h-[85vh]">
            
            <div class="p-6 border-b border-zinc-800 flex justify-between items-center bg-zinc-950/50">
                <div>
                    <h3 class="text-xl font-bold text-white">Manage Enrollments</h3>
                    <p class="text-zinc-500 text-xs mt-1">For <span x-text="targetName" class="text-white font-bold"></span></p>
                </div>
                <button @click="showEnrollModal = false" class="text-zinc-500 hover:text-white"><i class="fa-solid fa-times text-lg"></i></button>
            </div>

            <div class="p-6 overflow-y-auto flex-1 space-y-8">
                
                <!-- Add New Enrollment -->
                <div>
                    <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-3">Enroll in New Course</h4>
                    <div class="flex gap-3">
                        <select x-model="selectedCourseId" class="flex-1 bg-zinc-950 border border-zinc-800 text-zinc-300 text-sm rounded-xl px-4 py-2.5 focus:border-indigo-500 focus:outline-none">
                            <option value="">Select a course...</option>
                            <template x-for="course in availableCourses" :key="course.id">
                                <option :value="course.id" x-text="course.title"></option>
                            </template>
                        </select>
                        <button @click="enrollUser()" :disabled="!selectedCourseId" class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2.5 rounded-xl font-bold text-sm transition shadow-lg shadow-indigo-500/20">
                            Enroll
                        </button>
                    </div>
                </div>

                <!-- Current Enrollments List -->
                <div>
                    <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-3">Current Enrollments</h4>
                    
                    <div class="space-y-3">
                        <template x-for="course in enrolledCourses" :key="course.id">
                            <div class="bg-zinc-950 border border-zinc-800 p-4 rounded-xl flex items-center justify-between group hover:border-zinc-700 transition">
                                <div>
                                    <p class="text-sm font-bold text-white" x-text="course.title"></p>
                                    <div class="flex items-center gap-3 mt-1">
                                        <span class="text-xs text-zinc-500">Progress: <span class="text-indigo-400 font-bold" x-text="Math.round(course.progress_percent) + '%'"></span></span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="resetProgress(course.id)" class="p-2 text-zinc-500 hover:text-amber-400 transition" title="Reset Progress">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                    <button @click="unenrollUser(course.id)" class="p-2 text-zinc-500 hover:text-red-400 transition" title="Unenroll User">
                                        <i class="fa-solid fa-user-minus"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <div x-show="enrolledCourses.length === 0" class="text-center py-8 text-zinc-600 text-sm italic border border-dashed border-zinc-800 rounded-xl">
                            User is not enrolled in any courses.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<?php 
$content = ob_get_clean(); 
$title = 'Users';
// Ensure layout path is correct.
require __DIR__ . '/../layouts/admin.php'; 
?>
