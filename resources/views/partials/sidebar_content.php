<nav class="p-4 space-y-6">
    <?php
    $mode = $_SESSION['dashboard_mode'] ?? 'student';
    $roles = $_SESSION['user_roles'] ?? [];
    $isInstructor = in_array('instructor', $roles) || in_array('admin', $roles);

    // Calculate counts if not already set (in case of double include or moved logic)
    if (!isset($pendingAttemptCount) && $isInstructor && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $db = \App\Core\Database::getInstance();
        $sqlAttempts = "SELECT COUNT(*) as count
                            FROM attempt_requests ar
                            JOIN quizzes q ON ar.quiz_id = q.id
                            JOIN lessons l ON q.lesson_id = l.id
                            JOIN modules m ON l.module_id = m.id
                            JOIN courses c ON m.course_id = c.id
                            WHERE c.instructor_id = :uid AND ar.status = 'pending'";
        $resAttempts = $db->fetchAll($sqlAttempts, [':uid' => $userId]);
        $pendingAttemptCount = (int) ($resAttempts[0]['count'] ?? 0);

        $sqlCerts = "SELECT COUNT(*) as count
                            FROM enrollments e
                            JOIN courses c ON e.course_id = c.id
                            WHERE c.instructor_id = :uid
                            AND c.has_certificate = true
                            AND c.certificate_auto_issue = false
                            AND e.is_completed = true
                            AND e.certificate_id IS NULL";
        $resCerts = $db->fetchAll($sqlCerts, [':uid' => $userId]);
        $pendingCertificateCount = (int) ($resCerts[0]['count'] ?? 0);
    }
    // Ensure defaults
    $pendingAttemptCount = $pendingAttemptCount ?? 0;
    $pendingCertificateCount = $pendingCertificateCount ?? 0;
    ?>

    <!-- STUDENT MENU -->
    <?php if ($mode === 'student'): ?>
        <div>
            <p class="px-4 text-[10px] font-bold text-zinc-600 uppercase tracking-widest mb-2" x-show="!sidebarCollapsed">Learning</p>
            <div class="space-y-1">
                <a href="/dashboard" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                    :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i class="fa-solid fa-house w-5 text-center group-hover:text-indigo-400 transition"></i>
                    <span x-show="!sidebarCollapsed">Home</span>
                    <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Home</span>
                </a>
                <a href="/my-courses" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                    :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i class="fa-solid fa-book-open w-5 text-center group-hover:text-indigo-400 transition"></i>
                    <span x-show="!sidebarCollapsed">My Library</span>
                    <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">My Library</span>
                </a>
                <a href="/cart" class="relative flex items-center px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                    :class="sidebarCollapsed ? 'justify-center px-0' : 'justify-between'">
                    <div class="flex items-center space-x-3" :class="sidebarCollapsed ? 'space-x-0' : ''">
                        <i class="fa-solid fa-cart-shopping w-5 text-center group-hover:text-indigo-400 transition"></i>
                        <span x-show="!sidebarCollapsed">Cart</span>
                    </div>
                    <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Cart</span>
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <span class="bg-indigo-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-lg shadow-indigo-500/50"
                            :class="sidebarCollapsed ? 'absolute -top-1 right-2' : ''"><?= count($_SESSION['cart']) ?></span>
                    <?php endif; ?>
                </a>
                <a href="/certificates" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                    :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i class="fa-solid fa-award w-5 text-center group-hover:text-indigo-400 transition"></i>
                    <span x-show="!sidebarCollapsed">Certificates</span>
                    <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Certificates</span>
                </a>
                <a href="/notes" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                    :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i class="fa-solid fa-book-bookmark w-5 text-center group-hover:text-indigo-400 transition"></i>
                    <span x-show="!sidebarCollapsed">Notebook</span>
                    <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Notebook</span>
                </a>
            </div>
        </div>

        <!-- INSTRUCTOR MENU -->
    <?php else: ?>
        <div>
            <p class="px-4 text-[10px] font-bold text-zinc-600 uppercase tracking-widest mb-2" x-show="!sidebarCollapsed">Teaching</p>
            <div class="space-y-1">
                <a href="/dashboard" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                    :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i class="fa-solid fa-chart-pie w-5 text-center group-hover:text-indigo-400 transition"></i>
                    <span x-show="!sidebarCollapsed">Overview</span>
                    <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Overview</span>
                </a>
                <a href="/instructor/course/new" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                    :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i class="fa-solid fa-plus-circle w-5 text-center group-hover:text-indigo-400 transition"></i>
                    <span x-show="!sidebarCollapsed">Create Course</span>
                    <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Create Course</span>
                </a>
                <a href="/instructor/lessons" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                    :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i class="fa-solid fa-film w-5 text-center group-hover:text-indigo-400 transition"></i>
                    <span x-show="!sidebarCollapsed">Lesson Library</span>
                    <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Lesson Library</span>
                </a>
                <a href="/instructor/analytics" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                    :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i class="fa-solid fa-chart-line w-5 text-center group-hover:text-indigo-400 transition"></i>
                    <span x-show="!sidebarCollapsed">Global Analytics</span>
                    <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Global Analytics</span>
                </a>
                <a href="/instructor/question-banks" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                    :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i class="fa-solid fa-database w-5 text-center group-hover:text-indigo-400 transition"></i>
                    <span x-show="!sidebarCollapsed">Question Bank</span>
                    <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Question Bank</span>
                </a>
            </div>
            <p class="px-4 mt-4 text-[10px] font-bold text-zinc-600 uppercase tracking-widest mb-2" x-show="!sidebarCollapsed">Actions</p>
            <div class="space-y-1">
                <a href="/instructor/requests" class="relative flex items-center px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                    :class="sidebarCollapsed ? 'justify-center px-0' : 'justify-between'">
                    <div class="flex items-center space-x-3" :class="sidebarCollapsed ? 'space-x-0' : ''">
                        <i class="fa-solid fa-rotate-right w-5 text-center group-hover:text-amber-400 transition"></i>
                        <span x-show="!sidebarCollapsed">Retake Requests</span>
                    </div>
                    <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Retake Requests</span>
                    <?php if ($pendingAttemptCount > 0): ?>
                        <span class="bg-amber-500/10 text-amber-300 text-[10px] font-bold px-2 py-0.5 rounded-full border border-amber-500/30"
                            :class="sidebarCollapsed ? 'absolute -top-1 right-2' : ''"><?= $pendingAttemptCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="/instructor/certificates/approvals" class="relative flex items-center px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                    :class="sidebarCollapsed ? 'justify-center px-0' : 'justify-between'">
                    <div class="flex items-center space-x-3" :class="sidebarCollapsed ? 'space-x-0' : ''">
                        <i class="fa-solid fa-award w-5 text-center group-hover:text-emerald-400 transition"></i>
                        <span x-show="!sidebarCollapsed">Certificates</span>
                    </div>
                    <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Certificates</span>
                    <?php if ($pendingCertificateCount > 0): ?>
                        <span class="bg-emerald-500/10 text-emerald-300 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-500/30"
                            :class="sidebarCollapsed ? 'absolute -top-1 right-2' : ''"><?= $pendingCertificateCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="/instructor/grading" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                    :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i class="fa-solid fa-pen-to-square w-5 text-center group-hover:text-indigo-400 transition"></i>
                    <span x-show="!sidebarCollapsed">Grading Queue</span>
                    <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Grading Queue</span>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- ACCOUNT MENU (Global) -->
    <div>
        <p class="px-4 text-[10px] font-bold text-zinc-600 uppercase tracking-widest mb-2" x-show="!sidebarCollapsed">Account</p>
        <div class="space-y-1">
            <a href="/profile" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white transition group"
                :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                <i class="fa-solid fa-user-circle w-5 text-center group-hover:text-indigo-400 transition"></i>
                <span x-show="!sidebarCollapsed">Profile Settings</span>
                <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Profile Settings</span>
            </a>
        </div>
    </div>
</nav>

<div class="p-4 border-t border-zinc-800">
    <a href="/logout" class="relative flex items-center space-x-3 px-4 py-3 rounded-xl text-zinc-500 hover:bg-red-500/10 hover:text-red-400 transition w-full"
        :class="sidebarCollapsed ? 'justify-center px-0' : ''">
        <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i>
        <span x-show="!sidebarCollapsed">Logout</span>
        <span x-show="sidebarCollapsed" class="pointer-events-none absolute left-full ml-3 top-1/2 -translate-y-1/2 whitespace-nowrap bg-zinc-900 text-zinc-200 text-xs font-bold px-3 py-1.5 rounded-lg border border-zinc-700 shadow-xl opacity-0 group-hover:opacity-100 transition">Logout</span>
    </a>
</div>