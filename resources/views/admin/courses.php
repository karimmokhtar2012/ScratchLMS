<?php ob_start(); ?>

<script>
    function courseManager() {
        return {
            search: '',
            selectedCourses: [],
            selectAll: false,
            allCourseIds: <?= json_encode(array_column($courses, 'id')) ?>,
            bulkAction: '',
            bulkPrice: '',
            bulkInstructorId: '',

            init() {
                this.$watch('selectedCourses', () => {
                    this.selectAll = this.selectedCourses.length === this.allCourseIds.length;
                });
            },

            toggleAllCourses() {
                if (this.selectAll) {
                    this.selectedCourses = [...this.allCourseIds];
                } else {
                    this.selectedCourses = [];
                }
            },

            applyBulk() {
                if (this.selectedCourses.length === 0 || !this.bulkAction) return;
                const payload = { course_ids: this.selectedCourses };
                let endpoint = '';

                if (this.bulkAction === 'delete') {
                    if (!confirm('Delete selected courses? This cannot be undone.')) return;
                    endpoint = '/admin/courses/bulk-delete';
                } else if (this.bulkAction === 'publish') {
                    endpoint = '/admin/courses/bulk-publish';
                } else if (this.bulkAction === 'unpublish') {
                    endpoint = '/admin/courses/bulk-unpublish';
                } else if (this.bulkAction === 'price') {
                    if (this.bulkPrice === '') return;
                    payload.price = this.bulkPrice;
                    endpoint = '/admin/courses/bulk-price';
                } else if (this.bulkAction === 'instructor') {
                    if (!this.bulkInstructorId) return;
                    payload.instructor_id = this.bulkInstructorId;
                    endpoint = '/admin/courses/bulk-instructor';
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

<div class="space-y-6" x-data="courseManager()">
    
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Course Management</h2>
            <p class="text-zinc-400 text-sm mt-1">Oversee all content, instructors, and enrollments.</p>
        </div>
        <div class="flex flex-col md:flex-row gap-3 md:items-center">
            <div class="flex items-center gap-2">
                <span class="text-xs text-zinc-500 font-bold">Selected:</span>
                <span class="text-xs text-white font-bold" x-text="selectedCourses.length"></span>
            </div>
            <div class="flex items-center gap-2">
                <select x-model="bulkAction" class="bg-black/20 border border-zinc-800 rounded-lg py-2 px-3 text-xs text-zinc-300 focus:border-indigo-500 focus:outline-none">
                    <option value="">Bulk Action</option>
                    <option value="delete">Delete Courses</option>
                    <option value="publish">Publish</option>
                    <option value="unpublish">Unpublish</option>
                    <option value="price">Set Price</option>
                    <option value="instructor">Set Instructor</option>
                </select>
                <input x-show="bulkAction === 'price'" type="number" min="0" step="0.01" x-model="bulkPrice"
                       class="bg-black/20 border border-zinc-800 rounded-lg py-2 px-3 text-xs text-zinc-300 focus:border-indigo-500 focus:outline-none w-32" placeholder="Price">
                <select x-show="bulkAction === 'instructor'" x-model="bulkInstructorId"
                        class="bg-black/20 border border-zinc-800 rounded-lg py-2 px-3 text-xs text-zinc-300 focus:border-indigo-500 focus:outline-none">
                    <option value="">Select instructor</option>
                    <?php if (!empty($instructors)): foreach ($instructors as $ins): ?>
                        <option value="<?= $ins['id'] ?>"><?= htmlspecialchars($ins['name']) ?></option>
                    <?php endforeach; endif; ?>
                </select>
                <button @click="applyBulk()" :disabled="selectedCourses.length === 0 || !bulkAction"
                        class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg text-xs font-bold transition">
                    Apply
                </button>
            </div>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-500">
                    <i class="fa-solid fa-search"></i>
                </span>
                <input type="text" x-model="search" placeholder="Search courses..." class="bg-black/20 border border-zinc-800 rounded-lg py-2 pl-10 pr-4 text-sm text-zinc-300 focus:border-indigo-500 focus:outline-none w-64">
            </div>
        </div>
    </div>

    <div class="glass-panel rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-black/40 text-xs text-zinc-500 border-b border-zinc-800">
                        <th class="py-4 pl-6 font-bold uppercase tracking-wider w-12">
                            <input type="checkbox" x-model="selectAll" @change="toggleAllCourses()"
                                   class="h-4 w-4 rounded border-zinc-700 bg-zinc-900 text-indigo-500 focus:ring-indigo-500/50">
                        </th>
                        <th class="py-4 font-bold uppercase tracking-wider w-16">ID</th>
                        <th class="py-4 font-bold uppercase tracking-wider">Course</th>
                        <th class="py-4 font-bold uppercase tracking-wider">Instructor</th>
                        <th class="py-4 font-bold uppercase tracking-wider">Status</th>
                        <th class="py-4 font-bold uppercase tracking-wider text-right">Students</th>
                        <th class="py-4 font-bold uppercase tracking-wider text-right pr-6">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/50">
                    <?php foreach ($courses as $course): ?>
                    <tr class="group hover:bg-white/5 transition" x-show="search === '' || '<?= addslashes(strtolower($course['title'])) ?>'.includes(search.toLowerCase())">
                        <td class="py-4 pl-6">
                            <input type="checkbox" value="<?= $course['id'] ?>" x-model="selectedCourses"
                                   class="h-4 w-4 rounded border-zinc-700 bg-zinc-900 text-indigo-500 focus:ring-indigo-500/50">
                        </td>
                        <td class="py-4 text-sm text-zinc-600 font-mono">#<?= $course['id'] ?></td>
                        <td class="py-4">
                            <div class="flex items-center gap-3">
                                <img src="<?= $course['thumbnail'] ?: '/assets/images/course.png' ?>" class="w-10 h-10 rounded-lg object-cover bg-zinc-800">
                                <div>
                                    <p class="text-sm font-medium text-white line-clamp-1"><?= htmlspecialchars($course['title']) ?></p>
                                    <p class="text-xs text-zinc-500">$<?= number_format($course['price'], 2) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 text-sm text-zinc-400">
                            <?= htmlspecialchars($course['instructor_name'] ?? 'Unknown') ?>
                        </td>
                        <td class="py-4">
                            <?php 
                                $status = $course['status'] ?? 'draft';
                                $color = match($status) {
                                    'published' => 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
                                    'upcoming' => 'text-amber-400 bg-amber-500/10 border-amber-500/20',
                                    default => 'text-zinc-400 bg-zinc-500/10 border-zinc-500/20'
                                };
                            ?>
                            <span class="px-2 py-1 rounded text-[10px] font-bold border uppercase tracking-wider <?= $color ?>">
                                <?= $status ?>
                            </span>
                        </td>
                        <td class="py-4 text-right text-sm text-zinc-400">
                            <?= $course['students_count'] ?>
                        </td>
                        <td class="py-4 pr-6 text-right">
                            <form action="/admin/courses/delete/<?= $course['id'] ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this course and all its data?');" class="inline-block">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <button type="submit" class="text-zinc-600 hover:text-red-400 transition p-2" title="Delete Course">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            <a href="/course/<?= $course['id'] ?>" target="_blank" class="text-zinc-600 hover:text-indigo-400 transition p-2" title="View Course">
                                <i class="fa-solid fa-external-link"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
$title = 'Courses';
require __DIR__ . '/../layouts/admin.php'; 
?>
