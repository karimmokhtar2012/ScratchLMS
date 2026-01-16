<?php ob_start(); 

// Helpers
function formatDuration($seconds) {
    if ($seconds < 60) return $seconds . 's';
    if ($seconds < 3600) return floor($seconds / 60) . 'm';
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    return $h . 'h ' . ($m > 0 ? $m . 'm' : '');
}

$isFree = $course['price'] == 0;
$isUpcoming = $course['status'] === 'upcoming';
$releaseDate = $course['published_at'] ? date('M d, Y', strtotime($course['published_at'])) : 'Coming Soon';
?>

<style>
    @keyframes subtle-glow {
        0%, 100% { transform: scale(1); opacity: 0.2; }
        50% { transform: scale(1.2); opacity: 0.5; }
    }
    .hero-glow {
        opacity: 1;
        pointer-events: none;
        z-index: 0; /* Ensure it is above the section background but below content */
        filter: blur(80px); /* Enhance blur via CSS */
        animation: subtle-glow 4s infinite ease-in-out;
    }
</style>

<div class="bg-zinc-950 min-h-screen pb-20">
    
    <!-- Hero Section -->
    <section class="hero-section group relative pt-12 pb-16 lg:pt-20 lg:pb-24 border-b border-zinc-800 bg-zinc-900/30 overflow-hidden">
        <!-- Atmosphere Background -->
        <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-10 z-0"></div>
        
        <!-- Animated Glows (Visible on Hover) -->
        <div class="hero-glow absolute top-[-20%] left-[-10%] w-[600px] h-[600px] bg-indigo-600 rounded-full"></div>
        <div class="hero-glow absolute bottom-[-20%] right-[0%] w-[500px] h-[500px] bg-violet-600 rounded-full" style="animation-delay: -2s;"></div>

        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-3 gap-12 relative z-10">
            
            <!-- Left: Course Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Badges -->
                <div class="flex flex-wrap gap-3">
                    <?php if ($isUpcoming): ?>
                        <span class="bg-amber-500/10 border border-amber-500/20 text-amber-500 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                            <i class="fa-solid fa-clock mr-1"></i> Pre-Order / Upcoming
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($isFree): ?>
                        <span class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                            Free Course
                        </span>
                    <?php else: ?>
                        <span class="bg-zinc-800 border border-zinc-700 text-zinc-300 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                            Premium
                        </span>
                    <?php endif; ?>

                    <span class="bg-zinc-800 border border-zinc-700 text-zinc-400 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                        Last Updated: <?= date('M Y', strtotime($course['created_at'])) ?>
                    </span>
                </div>

                <h1 class="text-4xl md:text-5xl font-black text-white leading-tight">
                    <?= htmlspecialchars($course['title']) ?>
                </h1>
                
                <p class="text-lg text-zinc-400 leading-relaxed max-w-2xl">
                    <?= htmlspecialchars($course['description']) ?>
                </p>

                <!-- Instructor Mini-Block (Mobile Only) -->
                <div class="flex items-center space-x-3 lg:hidden pt-4 border-t border-zinc-800">
                    <div class="w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center text-zinc-500 font-bold border border-zinc-700">
                        <?= strtoupper(substr($instructor['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="text-white font-bold text-sm"><?= htmlspecialchars($instructor['name']) ?></div>
                        <div class="text-zinc-500 text-xs"><?= htmlspecialchars($instructor['field_of_work'] ?? 'Instructor') ?></div>
                    </div>
                </div>
            </div>

            <!-- Right: Course Highlights (Quick Facts) -->
            <div class="lg:col-span-1 relative">
                <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-6 shadow-2xl relative overflow-hidden group hover:border-white/20 transition duration-500">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/20 rounded-full blur-2xl -mr-16 -mt-16 pointer-events-none"></div>
                    
                    <h3 class="text-white font-bold mb-6 flex items-center">
                        <i class="fa-solid fa-bolt text-amber-400 mr-2"></i> Course Highlights
                    </h3>

                    <div class="grid grid-cols-2 gap-y-6 gap-x-4">
                        <!-- Duration -->
                        <div class="space-y-1">
                            <div class="text-zinc-500 text-xs font-bold uppercase tracking-wider">Duration</div>
                            <div class="text-white font-medium flex items-center">
                                <i class="fa-regular fa-clock text-indigo-400 mr-2"></i> <?= formatDuration($totalDuration) ?>
                            </div>
                        </div>

                        <!-- Lessons -->
                        <div class="space-y-1">
                            <div class="text-zinc-500 text-xs font-bold uppercase tracking-wider">Lessons</div>
                            <div class="text-white font-medium flex items-center">
                                <i class="fa-solid fa-layer-group text-indigo-400 mr-2"></i> <?= $totalLessons ?>
                            </div>
                        </div>

                        <!-- Level -->
                        <div class="space-y-1">
                            <div class="text-zinc-500 text-xs font-bold uppercase tracking-wider">Level</div>
                            <div class="text-white font-medium flex items-center">
                                <i class="fa-solid fa-chart-simple text-indigo-400 mr-2"></i> All Levels
                            </div>
                        </div>

                        <!-- Language -->
                        <div class="space-y-1">
                            <div class="text-zinc-500 text-xs font-bold uppercase tracking-wider">Language</div>
                            <div class="text-white font-medium flex items-center">
                                <i class="fa-solid fa-globe text-indigo-400 mr-2"></i> English
                            </div>
                        </div>

                        <!-- Access -->
                        <div class="space-y-1">
                            <div class="text-zinc-500 text-xs font-bold uppercase tracking-wider">Access</div>
                            <div class="text-white font-medium flex items-center">
                                <i class="fa-solid fa-mobile-screen text-indigo-400 mr-2"></i> Mobile & Web
                            </div>
                        </div>

                        <!-- Certificate -->
                        <div class="space-y-1">
                            <div class="text-zinc-500 text-xs font-bold uppercase tracking-wider">Certificate</div>
                            <div class="text-white font-medium flex items-center">
                                <?php if ($course['has_certificate']): ?>
                                    <i class="fa-solid fa-certificate text-emerald-400 mr-2"></i> Included
                                <?php else: ?>
                                    <i class="fa-solid fa-xmark text-zinc-600 mr-2"></i> None
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Content & Sticky Sidebar -->
    <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 lg:grid-cols-3 gap-12 relative">
        
        <!-- Left Column: Curriculum & Instructor -->
        <div class="lg:col-span-2 space-y-16">
            
            <!-- Curriculum -->
            <section>
                <h3 class="text-2xl font-bold text-white mb-6">Course Curriculum</h3>
                
                <?php if (empty($modules)): ?>
                    <div class="bg-zinc-900/50 border border-zinc-800 rounded-2xl p-8 text-center">
                        <i class="fa-solid fa-box-open text-4xl text-zinc-700 mb-4"></i>
                        <p class="text-zinc-400">Content is being prepared. Stay tuned!</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($modules as $index => $module): ?>
                            <div class="bg-zinc-900/50 border border-zinc-800 rounded-xl overflow-hidden" x-data="{ open: <?= $index === 0 ? 'true' : 'false' ?> }">
                                <!-- Module Header -->
                                <button @click="open = !open" class="w-full flex items-center justify-between p-5 text-left hover:bg-zinc-800/50 transition">
                                    <div>
                                        <h4 class="text-white font-bold text-lg"><?= htmlspecialchars($module['title']) ?></h4>
                                        <div class="text-xs text-zinc-500 mt-1"><?= count($module['lessons']) ?> Lessons</div>
                                    </div>
                                    <i class="fa-solid fa-chevron-down text-zinc-500 transition-transform duration-300" :class="open ? 'rotate-180' : ''"></i>
                                </button>

                                <!-- Lessons List -->
                                <div x-show="open" x-collapse class="border-t border-zinc-800/50 bg-black/20">
                                    <?php foreach ($module['lessons'] as $lesson): ?>
                                        <?php 
                                            // Lock Logic:
                                            // If enrolled: All unlocked (unless linear enforcement, but show accessible).
                                            // If NOT enrolled: Only 'preview' allowed? Currently we don't have explicit 'is_preview' column.
                                            // We'll assume first lesson of first module is preview for guests, or based on future logic.
                                            // For now, if not enrolled, all locked except maybe implied preview.
                                            // Let's just show icons.
                                            $icon = match($lesson['type']) {
                                                'video' => 'fa-circle-play',
                                                'quiz' => 'fa-clipboard-question',
                                                'article' => 'fa-file-lines',
                                                default => 'fa-circle'
                                            };
                                            $isLocked = !$isEnrolled; // Strict for now
                                        ?>
                                        <div class="flex items-center justify-between p-4 border-b border-zinc-800/30 last:border-0 hover:bg-white/5 transition group">
                                            <div class="flex items-center space-x-4">
                                                <i class="fa-solid <?= $icon ?> text-zinc-600 group-hover:text-indigo-400 transition"></i>
                                                <span class="text-zinc-300 text-sm group-hover:text-white transition"><?= htmlspecialchars($lesson['title']) ?></span>
                                            </div>
                                            <div class="flex items-center space-x-4 text-xs text-zinc-500">
                                                <?php if ($lesson['duration']): ?>
                                                    <span><?= formatDuration($lesson['duration']) ?></span>
                                                <?php endif; ?>
                                                
                                                <?php if ($isLocked): ?>
                                                    <i class="fa-solid fa-lock text-zinc-700"></i>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Instructor -->
            <section class="border-t border-zinc-800 pt-12">
                <h3 class="text-2xl font-bold text-white mb-8">Meet Your Instructor</h3>
                <div class="flex flex-col md:flex-row gap-8">
                    <div class="shrink-0">
                        <div class="w-24 h-24 rounded-2xl bg-zinc-800 flex items-center justify-center text-2xl text-zinc-500 font-bold border border-zinc-700 shadow-xl">
                            <?= strtoupper(substr($instructor['name'], 0, 1)) ?>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-xl font-bold text-white mb-1"><?= htmlspecialchars($instructor['name']) ?></h4>
                        <p class="text-indigo-400 text-sm font-medium mb-4"><?= htmlspecialchars($instructor['field_of_work'] ?? 'Expert Instructor') ?></p>
                        <p class="text-zinc-400 text-sm leading-relaxed mb-6">
                            Passionate educator with years of industry experience. Dedicated to helping students master complex topics through practical, hands-on learning.
                        </p>
                        
                        <?php if (!empty($moreCourses)): ?>
                            <div class="space-y-3">
                                <h5 class="text-xs font-bold text-zinc-500 uppercase tracking-widest">More from <?= explode(' ', $instructor['name'])[0] ?></h5>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <?php foreach ($moreCourses as $mc): ?>
                                        <a href="/course/<?= $mc['id'] ?>" class="flex items-center space-x-3 bg-zinc-900 border border-zinc-800 p-2 rounded-lg hover:border-zinc-700 transition group">
                                            <div class="w-12 h-12 rounded bg-zinc-800 overflow-hidden shrink-0">
                                                <img src="<?= htmlspecialchars($mc['thumbnail'] ?? '/assets/images/course-placeholder.jpg') ?>" class="w-full h-full object-cover">
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-white text-xs font-bold truncate group-hover:text-indigo-400 transition"><?= htmlspecialchars($mc['title']) ?></div>
                                                <div class="text-zinc-500 text-[10px]"><?= $mc['price'] > 0 ? '$' . $mc['price'] : 'Free' ?></div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

        </div>

        <!-- Right Column: Sticky Enrollment Card -->
        <div class="lg:col-span-1 relative">
            <div class="sticky top-8 space-y-6">
                
                <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden shadow-2xl relative">
                    <!-- Thumbnail (Hidden on mobile as it's in header, or keep?) Layout implies separate card. -->
                    <div class="aspect-video relative">
                        <img src="<?= htmlspecialchars($course['thumbnail'] ?? '/assets/images/course-placeholder.jpg') ?>" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-zinc-900 to-transparent opacity-50"></div>
                        
                        <!-- Play Button Overlay -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-16 h-16 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center border border-white/20 text-white shadow-xl">
                                <i class="fa-solid fa-play ml-1 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <!-- Pricing / Status -->
                        <div class="mb-6">
                            <?php if ($isEnrolled): ?>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-bold text-zinc-400">Your Progress</span>
                                    <span class="text-xl font-bold text-emerald-500"><?= $progressPercent ?>%</span>
                                </div>
                                <div class="h-2 w-full bg-zinc-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 transition-all duration-1000" style="width: <?= $progressPercent ?>%"></div>
                                </div>
                            <?php else: ?>
                                <div class="flex items-end space-x-2">
                                    <?php if ($isFree): ?>
                                        <span class="text-3xl font-black text-white">Free</span>
                                    <?php else: ?>
                                        <span class="text-3xl font-black text-white">$<?= number_format($course['price'], 2) ?></span>
                                        <span class="text-zinc-500 text-sm line-through mb-1.5">$<?= number_format($course['price'] * 1.5, 2) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- CTA -->
                        <div class="space-y-3">
                            <?php if ($isUpcoming): ?>
                                <button disabled class="w-full bg-zinc-800 text-zinc-500 font-bold py-4 rounded-xl cursor-not-allowed border border-zinc-700">
                                    Coming <?= $releaseDate ?>
                                </button>
                                <button class="w-full bg-transparent border border-zinc-700 text-white font-bold py-3 rounded-xl hover:bg-zinc-800 transition">
                                    <i class="fa-regular fa-bell mr-2"></i> Notify Me
                                </button>
                            <?php elseif ($isEnrolled): ?>
                                <a href="/learn/<?= $course['id'] ?><?= $firstLessonId ? '/lesson/' . $firstLessonId : '' ?>" class="block w-full bg-emerald-600 text-white text-center font-bold py-4 rounded-xl hover:bg-emerald-500 transition shadow-lg shadow-emerald-500/20">
                                    <?= $progressPercent > 0 ? 'Continue Learning' : 'Start Course' ?>
                                </a>
                            <?php else: ?>
                                <?php if ($isFree): ?>
                                    <form action="/course/<?= $course['id'] ?>/enroll" method="post" class="block">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <button type="submit" class="w-full bg-indigo-600 text-white text-center font-bold py-4 rounded-xl hover:bg-indigo-500 transition shadow-lg shadow-indigo-500/20">
                                            Enroll Now
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form action="/course/<?= $course['id'] ?>/enroll" method="post" class="block">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <button type="submit" class="w-full bg-indigo-600 text-white text-center font-bold py-4 rounded-xl hover:bg-indigo-500 transition shadow-lg shadow-indigo-500/20">
                                            Add to Cart
                                        </button>
                                    </form>
                                    <button class="w-full bg-transparent border border-zinc-700 text-white font-bold py-3 rounded-xl hover:bg-zinc-800 transition">
                                        Buy Now
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Includes -->
                        <div class="mt-8 space-y-3 pt-6 border-t border-zinc-800">
                            <h5 class="text-xs font-bold text-zinc-500 uppercase tracking-widest mb-2">This course includes:</h5>
                            <div class="flex items-center text-sm text-zinc-300">
                                <i class="fa-solid fa-video w-6 text-zinc-500"></i> <?= formatDuration($totalDuration) ?> on-demand video
                            </div>
                            <div class="flex items-center text-sm text-zinc-300">
                                <i class="fa-solid fa-file w-6 text-zinc-500"></i> <?= $totalLessons ?> lessons
                            </div>
                            <div class="flex items-center text-sm text-zinc-300">
                                <i class="fa-solid fa-mobile-screen w-6 text-zinc-500"></i> Access on mobile
                            </div>
                            <div class="flex items-center text-sm text-zinc-300">
                                <i class="fa-solid fa-infinity w-6 text-zinc-500"></i> Full lifetime access
                            </div>
                            <?php if ($course['has_certificate']): ?>
                                <div class="flex items-center text-sm text-amber-400">
                                    <i class="fa-solid fa-trophy w-6 text-amber-500"></i> Certificate of completion
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Guarantee (Marketing) -->
                <?php if (!$isFree && !$isEnrolled): ?>
                    <div class="text-center">
                        <p class="text-xs text-zinc-500">30-Day Money-Back Guarantee</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../layouts/public.php'; 
?>
