<?php ob_start(); ?>

<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex items-center justify-between" data-tour="analytics-header">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Global Analytics</h1>
            <p class="text-zinc-400 mt-2">Insights into your teaching performance.</p>
        </div>
        <div class="flex space-x-3" data-tour="analytics-export">
            <button class="bg-zinc-800 hover:bg-zinc-700 text-white px-4 py-2 rounded-lg text-sm font-bold border border-zinc-700 transition">
                <i class="fa-solid fa-download mr-2"></i> Export Report
            </button>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6" data-tour="analytics-overview">
        <!-- Revenue -->
        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 relative overflow-hidden group hover:border-zinc-700 transition">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                <i class="fa-solid fa-sack-dollar text-6xl text-emerald-500"></i>
            </div>
            <p class="text-zinc-500 text-sm font-medium uppercase tracking-wider mb-1">Total Revenue</p>
            <h3 class="text-3xl font-black text-white">$<?= number_format($overview['total_revenue'] ?? 0, 2) ?></h3>
            <div class="mt-4 flex items-center text-xs text-emerald-400 font-bold bg-emerald-500/10 inline-block px-2 py-1 rounded">
                <i class="fa-solid fa-arrow-trend-up mr-1"></i> +12% this month
            </div>
        </div>

        <!-- Enrollments -->
        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 relative overflow-hidden group hover:border-zinc-700 transition">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                <i class="fa-solid fa-users text-6xl text-blue-500"></i>
            </div>
            <p class="text-zinc-500 text-sm font-medium uppercase tracking-wider mb-1">Total Enrollments</p>
            <h3 class="text-3xl font-black text-white"><?= number_format($overview['total_enrollments'] ?? 0) ?></h3>
            <div class="mt-4 flex items-center text-xs text-blue-400 font-bold bg-blue-500/10 inline-block px-2 py-1 rounded">
                <i class="fa-solid fa-user-plus mr-1"></i> New students active
            </div>
        </div>

        <!-- Courses -->
        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 relative overflow-hidden group hover:border-zinc-700 transition">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                <i class="fa-solid fa-layer-group text-6xl text-purple-500"></i>
            </div>
            <p class="text-zinc-500 text-sm font-medium uppercase tracking-wider mb-1">Active Courses</p>
            <h3 class="text-3xl font-black text-white"><?= number_format($overview['total_courses'] ?? 0) ?></h3>
            <div class="mt-4 flex items-center text-xs text-purple-400 font-bold bg-purple-500/10 inline-block px-2 py-1 rounded">
                <i class="fa-solid fa-video mr-1"></i> Content published
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Course Performance Table -->
        <div class="lg:col-span-2 bg-zinc-900 border border-zinc-800 rounded-2xl p-6" data-tour="analytics-performance">
            <h3 class="text-lg font-bold text-white mb-6 flex items-center">
                <i class="fa-solid fa-chart-simple mr-2 text-indigo-500"></i> Course Performance
            </h3>
            
            <?php if (empty($performance)): ?>
                <div class="text-center py-12 text-zinc-500">No course data available yet.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-xs text-zinc-500 uppercase border-b border-zinc-800">
                                <th class="py-3 font-medium pl-2">Course Name</th>
                                <th class="py-3 font-medium">Price</th>
                                <th class="py-3 font-medium">Students</th>
                                <th class="py-3 font-medium text-right pr-2">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php foreach ($performance as $p): ?>
                                <tr class="border-b border-zinc-800/50 hover:bg-zinc-800/30 transition group">
                                    <td class="py-4 pl-2 font-medium text-white group-hover:text-indigo-400 transition">
                                        <?= htmlspecialchars($p['title']) ?>
                                    </td>
                                    <td class="py-4 text-zinc-400">$<?= number_format($p['price'], 2) ?></td>
                                    <td class="py-4 text-zinc-300">
                                        <span class="bg-zinc-800 px-2 py-1 rounded text-xs font-bold"><?= $p['enrollments_count'] ?></span>
                                    </td>
                                    <td class="py-4 text-right pr-2 font-bold text-emerald-400">
                                        $<?= number_format($p['revenue'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Enrollments List -->
        <div class="lg:col-span-1 bg-zinc-900 border border-zinc-800 rounded-2xl p-6" data-tour="analytics-recent">
            <h3 class="text-lg font-bold text-white mb-6 flex items-center">
                <i class="fa-regular fa-clock mr-2 text-blue-500"></i> Recent Activity
            </h3>

            <?php if (empty($recent)): ?>
                <div class="text-center py-12 text-zinc-500">No recent enrollments.</div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($recent as $r): ?>
                        <div class="flex items-start space-x-3 pb-4 border-b border-zinc-800/50 last:border-0 last:pb-0">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
                                <?= strtoupper(substr($r['student_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <p class="text-sm text-white font-medium">
                                    <span class="text-indigo-300"><?= htmlspecialchars($r['student_name']) ?></span> 
                                    enrolled in 
                                    <span class="text-zinc-300"><?= htmlspecialchars($r['course_title']) ?></span>
                                </p>
                                <p class="text-xs text-zinc-500 mt-1">
                                    <?= date('M j, g:i a', strtotime($r['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart Script (Simple Chart.js integration) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="mt-8 bg-zinc-900 border border-zinc-800 rounded-2xl p-6">
    <h3 class="text-lg font-bold text-white mb-6">Revenue Overview</h3>
    <canvas id="revenueChart" height="80"></canvas>
</div>

<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], // Placeholder data
            datasets: [{
                label: 'Revenue ($)',
                data: [1200, 1900, 3000, 5000, 2300, 4500], // Placeholder data
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#27272a' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../layouts/app.php'; 
?>

<?php if (!isset($_GET['tour']) || $_GET['tour'] !== 'off'): ?>
<style>
    [x-cloak] { display: none !important; }
</style>
<div x-data="analyticsTour()" x-init="init()" x-show="show" x-cloak class="fixed inset-0 z-[200] pointer-events-none">
    <div class="fixed inset-0 bg-black/60"></div>
    <div class="fixed rounded-2xl border border-indigo-500/60 shadow-[0_0_0_9999px_rgba(0,0,0,0.65)] transition-all duration-300"
         :style="spotlightStyle"></div>

    <div class="fixed w-[92%] max-w-xl bg-zinc-900/95 border border-zinc-800 rounded-2xl p-6 shadow-2xl transition-all duration-300 pointer-events-auto"
         :style="tourCardStyle">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-widest text-indigo-400 font-bold">Guided Tour</p>
                <h3 class="text-xl font-bold text-white mt-1" x-text="currentStep.title"></h3>
                <p class="text-zinc-400 text-sm mt-2" x-text="currentStep.body"></p>
            </div>
            <button @click="finish()" class="text-zinc-500 hover:text-white text-sm">Skip</button>
        </div>

        <div class="flex items-center justify-between mt-6">
            <div class="text-xs text-zinc-500 font-bold" x-text="(stepIndex + 1) + ' / ' + steps.length"></div>
            <div class="flex items-center gap-3">
                <button @click="prev()" :disabled="stepIndex === 0"
                        class="px-4 py-2 rounded-full text-sm font-bold border border-zinc-700 text-zinc-300 hover:text-white hover:border-zinc-500 transition disabled:opacity-40 disabled:cursor-not-allowed">
                    Back
                </button>
                <button @click="next()" class="px-5 py-2 rounded-full text-sm font-bold bg-white text-black hover:bg-zinc-200 transition">
                    <span x-show="!isLast">Next</span>
                    <span x-show="isLast">Finish</span>
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    function analyticsTour() {
        const storageKey = 'tour_analytics_seen';
        const steps = [
            { selector: '[data-tour="analytics-header"]', title: 'Global Analytics', body: 'Overview of your teaching performance, revenue, and enrollments.' },
            { selector: '[data-tour="analytics-export"]', title: 'Export Reports', body: 'Download a snapshot of your analytics for sharing or record keeping.' },
            { selector: '[data-tour="analytics-overview"]', title: 'Top Metrics', body: 'Track revenue, enrollments, and active courses at a glance.' },
            { selector: '[data-tour="analytics-performance"]', title: 'Course Performance', body: 'See how each course contributes to your revenue and student counts.' },
            { selector: '[data-tour="analytics-recent"]', title: 'Recent Activity', body: 'Recent enrollments and activity give you context on what is trending.' }
        ];

        return {
            show: !localStorage.getItem(storageKey),
            stepIndex: 0,
            steps,
            spotlightStyle: '',
            tourCardStyle: '',
            get currentStep() { return this.steps[this.stepIndex] || this.steps[0]; },
            get isLast() { return this.stepIndex === this.steps.length - 1; },
            init() {
                if (!this.show) return;
                this.updateSpotlight();
                window.addEventListener('resize', () => this.updateSpotlight());
                window.addEventListener('scroll', () => this.updateSpotlight(), { passive: true });
            },
            updateSpotlight() {
                if (!this.show) return;
                const step = this.steps[this.stepIndex];
                const el = document.querySelector(step.selector);
                if (!el) return;
                el.scrollIntoView({ behavior: 'auto', block: 'center' });
                requestAnimationFrame(() => {
                    const rect = el.getBoundingClientRect();
                    const pad = 6;
                    const top = Math.max(rect.top - pad, 12);
                    const left = Math.max(rect.left - pad, 12);
                    const width = Math.max(rect.width + pad * 2, 80);
                    const height = Math.max(rect.height + pad * 2, 40);
                    this.spotlightStyle = `top:${top}px; left:${left}px; width:${width}px; height:${height}px;`;

                    const cardWidth = Math.min(520, window.innerWidth - 24);
                    const preferBelow = rect.bottom + 16 + 220 < window.innerHeight;
                    const cardTop = preferBelow ? (rect.bottom + 16) : Math.max(rect.top - 16 - 200, 16);
                    let cardLeft = Math.min(Math.max(rect.left, 12), window.innerWidth - cardWidth - 12);
                    this.tourCardStyle = `top:${cardTop}px; left:${cardLeft}px; width:${cardWidth}px;`;
                });
            },
            next() {
                if (this.isLast) { this.finish(); return; }
                this.stepIndex++;
                this.updateSpotlight();
            },
            prev() {
                if (this.stepIndex === 0) return;
                this.stepIndex--;
                this.updateSpotlight();
            },
            finish() {
                this.show = false;
                localStorage.setItem(storageKey, '1');
            }
        }
    }
    document.addEventListener('alpine:init', () => {
        const root = document.querySelector('[x-data="analyticsTour()"]');
        if (root && root.__x) {
            root.__x.updateElements(root);
        }
    });
</script>
<?php endif; ?>
