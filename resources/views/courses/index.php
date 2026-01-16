<?php ob_start(); ?>

<style>
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-up {
        opacity: 0; /* Start hidden */
        animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
</style>

<div class="min-h-screen bg-zinc-950 relative overflow-hidden">
    
    <!-- Atmosphere Background -->
    <div class="absolute inset-0 z-0 pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-900/20 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-emerald-900/10 rounded-full blur-[120px]"></div>
        <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20 mix-blend-overlay"></div>
    </div>

    <!-- Hero Section -->
    <section class="relative z-10 pt-12 pb-16 lg:pt-20 lg:pb-24 px-6 border-b border-white/5 bg-gradient-to-b from-transparent to-zinc-900/50">
        <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
            
            <!-- Left: Content & Search -->
            <div class="space-y-8 animate-fade-up">
                <div class="space-y-4">
                    <span class="inline-block py-1 px-3 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-mono font-bold tracking-wider">
                        EXPLORE THE CATALOG
                    </span>
                    <h1 class="text-4xl lg:text-6xl font-black text-white leading-tight">
                        Find Your Next <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-violet-400 to-fuchsia-400">Mastery Level</span>
                    </h1>
                    <p class="text-lg text-zinc-400 max-w-xl leading-relaxed">
                        Join thousands of developers and creators building the future. Search our library of premium courses.
                    </p>
                </div>

                <!-- Search & Filter Form -->
                <form action="/courses" method="GET" class="bg-zinc-900/80 backdrop-blur-xl border border-zinc-800 p-2 rounded-2xl shadow-2xl flex flex-col md:flex-row gap-2" id="filterForm">
                    <!-- Search -->
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-zinc-500">
                            <i class="fa-solid fa-search"></i>
                        </span>
                        <input type="text" name="q" value="<?= htmlspecialchars($filters['q']) ?>" 
                               placeholder="What do you want to learn?" 
                               class="w-full bg-transparent border-none text-white placeholder-zinc-500 focus:ring-0 pl-12 h-12">
                    </div>

                    <!-- Filters (Divider on Desktop) -->
                    <div class="hidden md:block w-px bg-zinc-800 my-2"></div>

                    <!-- Filter Group -->
                    <div class="flex gap-2 p-1">
                        <select name="sort" onchange="document.getElementById('filterForm').submit()" 
                                class="bg-zinc-800 border-none text-zinc-300 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500/20 py-2.5 pl-3 pr-8 cursor-pointer hover:bg-zinc-700 transition">
                            <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Newest</option>
                            <option value="popular" <?= $filters['sort'] === 'popular' ? 'selected' : '' ?>>Popular</option>
                            <option value="price_asc" <?= $filters['sort'] === 'price_asc' ? 'selected' : '' ?>>Price: Low</option>
                            <option value="price_desc" <?= $filters['sort'] === 'price_desc' ? 'selected' : '' ?>>Price: High</option>
                        </select>

                        <select name="price" onchange="document.getElementById('filterForm').submit()" 
                                class="bg-zinc-800 border-none text-zinc-300 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500/20 py-2.5 pl-3 pr-8 cursor-pointer hover:bg-zinc-700 transition">
                            <option value="all" <?= $filters['price'] === 'all' ? 'selected' : '' ?>>All Prices</option>
                            <option value="free" <?= $filters['price'] === 'free' ? 'selected' : '' ?>>Free</option>
                            <option value="paid" <?= $filters['price'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                        </select>
                        
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white p-3 rounded-xl transition shadow-lg shadow-indigo-500/20">
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right: Featured Spotlight -->
            <?php if (!empty($featuredCourse)): ?>
            <div class="hidden lg:block relative group animate-fade-up" style="animation-delay: 0.2s">
                <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-fuchsia-500 rounded-3xl blur opacity-20 group-hover:opacity-40 transition duration-1000"></div>
                <div class="relative bg-zinc-900 border border-zinc-800 rounded-3xl overflow-hidden shadow-2xl">
                    <!-- Image -->
                    <div class="aspect-video relative overflow-hidden">
                        <img src="<?= htmlspecialchars($featuredCourse['thumbnail'] ?? '/assets/images/course-placeholder.jpg') ?>" 
                             class="w-full h-full object-cover transform group-hover:scale-105 transition duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-zinc-900 via-zinc-900/20 to-transparent"></div>
                        <div class="absolute top-4 left-4">
                            <span class="bg-white/10 backdrop-blur-md border border-white/20 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">
                                <i class="fa-solid fa-star text-yellow-400 mr-1"></i> Featured
                            </span>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-white mb-2 leading-snug"><?= htmlspecialchars($featuredCourse['title']) ?></h3>
                        <p class="text-zinc-400 text-sm line-clamp-2 mb-6"><?= htmlspecialchars($featuredCourse['description']) ?></p>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center text-zinc-500 font-bold border border-zinc-700">
                                    <?= strtoupper(substr($featuredCourse['instructor_name'] ?? 'U', 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="text-white text-sm font-bold"><?= htmlspecialchars($featuredCourse['instructor_name']) ?></div>
                                    <div class="text-zinc-500 text-xs"><?= $featuredCourse['students_count'] ?> Students</div>
                                </div>
                            </div>
                            <a href="/course/<?= $featuredCourse['id'] ?>" class="bg-white text-black px-6 py-3 rounded-xl font-bold hover:bg-zinc-200 transition">
                                View Course
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </section>

    <!-- Main Content Area -->
    <div class="max-w-7xl mx-auto px-6 py-12 relative z-10">
        
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-white flex items-center">
                <i class="fa-solid fa-layer-group text-zinc-500 mr-3"></i> 
                All Courses 
                <span class="ml-3 text-sm font-medium text-zinc-600 bg-zinc-900 px-2 py-1 rounded-md border border-zinc-800"><?= $filters['total_courses'] ?></span>
            </h2>
        </div>

        <!-- Course Grid -->
        <?php if (!empty($courses)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($courses as $index => $course): ?>
                <?php 
                    $isEnrolled = $course['is_enrolled'] ?? false;
                    $isFree = $course['price'] == 0;
                    // Stagger animation delay max 10 items
                    $delay = min($index * 0.1, 1.0) . 's';
                ?>
                <div class="group bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden hover:border-zinc-700 hover:shadow-2xl hover:shadow-indigo-500/10 transition-all duration-300 flex flex-col h-full relative animate-fade-up"
                     style="animation-delay: <?= $delay ?>">
                    
                    <!-- Thumbnail -->
                    <div class="relative aspect-video overflow-hidden bg-zinc-800">
                        <img src="<?= htmlspecialchars($course['thumbnail'] ?? '/assets/images/course-placeholder.jpg') ?>" 
                             alt="<?= htmlspecialchars($course['title']) ?>" 
                             loading="lazy"
                             class="w-full h-full object-cover transform group-hover:scale-105 transition duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent opacity-60"></div>
                        
                        <!-- Badges -->
                        <div class="absolute bottom-4 left-4 flex gap-2">
                            <?php if ($isFree): ?>
                                <span class="bg-emerald-600/90 backdrop-blur-md text-white text-xs font-bold px-2.5 py-1 rounded-lg shadow-lg border border-emerald-500/50">
                                    FREE
                                </span>
                            <?php else: ?>
                                <span class="bg-zinc-900/90 backdrop-blur-md text-white text-xs font-bold px-2.5 py-1 rounded-lg shadow-lg border border-zinc-700">
                                    $<?= number_format($course['price'], 2) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (($course['status'] ?? '') === 'upcoming'): ?>
                            <div class="absolute top-4 right-4">
                                <span class="bg-amber-500 text-black text-xs font-bold px-2.5 py-1 rounded-md shadow-lg border border-amber-400">
                                    UPCOMING
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if ($isEnrolled): ?>
                            <div class="absolute top-4 left-4">
                                <span class="bg-indigo-600 text-white text-[10px] font-bold px-2 py-1 rounded-full shadow-lg border border-indigo-400 flex items-center">
                                    <i class="fa-solid fa-check mr-1"></i> ENROLLED
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Content -->
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex-1">
                            <h2 class="text-xl font-bold text-white mb-2 line-clamp-2 group-hover:text-indigo-400 transition">
                                <?= htmlspecialchars($course['title']) ?>
                            </h2>
                            <p class="text-zinc-400 text-sm line-clamp-2 mb-4 h-10">
                                <?= htmlspecialchars($course['description']) ?>
                            </p>
                        </div>

                        <!-- Meta -->
                        <div class="flex items-center justify-between pt-4 border-t border-zinc-800 mt-auto mb-4">
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 rounded-full bg-zinc-800 flex items-center justify-center text-[10px] text-zinc-500 font-bold border border-zinc-700">
                                    <?= strtoupper(substr($course['instructor_name'] ?? 'U', 0, 1)) ?>
                                </div>
                                <span class="text-xs text-zinc-500 truncate max-w-[100px]"><?= htmlspecialchars($course['instructor_name'] ?? 'Instructor') ?></span>
                            </div>
                            
                            <div class="flex items-center text-xs text-zinc-500 space-x-3">
                                <span title="Students"><i class="fa-solid fa-users mr-1"></i> <?= $course['students_count'] ?? 0 ?></span>
                                <span title="Lessons"><i class="fa-solid fa-play-circle mr-1"></i> <?= $course['lessons_count'] ?? 0 ?></span>
                            </div>
                        </div>

                        <!-- Action -->
                        <div>
                            <?php if ($isEnrolled): ?>
                                <a href="/course/<?= $course['id'] ?>" class="block w-full bg-zinc-800 text-white text-center font-bold py-3 rounded-xl hover:bg-zinc-700 transition border border-zinc-700">
                                    Continue Learning
                                </a>
                            <?php else: ?>
                                <div class="flex gap-2">
                                    <a href="/course/<?= $course['id'] ?>" class="flex-1 bg-zinc-800 text-zinc-300 text-center font-bold py-3 rounded-xl hover:bg-zinc-700 transition text-sm flex items-center justify-center border border-zinc-700">
                                        Details
                                    </a>
                                    <?php if ($isFree): ?>
                                        <form action="/course/<?= $course['id'] ?>/enroll" method="post" class="flex-1">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <button type="submit" class="w-full bg-white text-black text-center font-bold py-3 rounded-xl hover:bg-zinc-200 transition text-sm flex items-center justify-center shadow-lg shadow-white/5">
                                                Enroll Now
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="/course/<?= $course['id'] ?>/enroll" method="post" class="flex-1">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <button type="submit" class="w-full bg-indigo-600 text-white text-center font-bold py-3 rounded-xl hover:bg-indigo-500 transition text-sm flex items-center justify-center shadow-lg shadow-indigo-500/20">
                                                Buy Now
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($filters['total_pages'] > 1): ?>
        <div class="flex justify-center mt-16 animate-fade-up" style="animation-delay: 0.5s">
            <nav class="flex items-center space-x-2 bg-zinc-900 border border-zinc-800 p-2 rounded-xl shadow-xl">
                <?php 
                    // Helper to build URL
                    $buildUrl = function($p) use ($filters) {
                        $query = $filters;
                        $query['page'] = $p;
                        unset($query['total_pages'], $query['total_courses']);
                        return '/courses?' . http_build_query($query);
                    };
                    $currentPage = $filters['page'];
                    $totalPages = $filters['total_pages'];
                ?>

                <?php if ($currentPage > 1): ?>
                    <a href="<?= $buildUrl($currentPage - 1) ?>" class="px-4 py-2 hover:bg-zinc-800 rounded-lg text-zinc-400 hover:text-white transition">
                        &larr;
                    </a>
                <?php endif; ?>

                <span class="px-4 py-2 text-zinc-500 text-sm font-mono border-x border-zinc-800 mx-2">
                    <?= $currentPage ?> / <?= $totalPages ?>
                </span>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= $buildUrl($currentPage + 1) ?>" class="px-4 py-2 hover:bg-zinc-800 rounded-lg text-zinc-400 hover:text-white transition">
                        &rarr;
                    </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- Empty State -->
        <div class="text-center py-24 bg-zinc-900/30 rounded-3xl border border-zinc-800 border-dashed animate-fade-up">
            <div class="w-20 h-20 bg-zinc-900 rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl border border-zinc-800">
                <i class="fa-solid fa-filter-circle-xmark text-3xl text-zinc-600"></i>
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">No courses found</h3>
            <p class="text-zinc-500 mb-8 max-w-sm mx-auto">We couldn't find any courses matching your current filters.</p>
            <a href="/courses" class="inline-block bg-white text-black px-6 py-2.5 rounded-lg font-bold hover:bg-zinc-200 transition">
                Clear All Filters
            </a>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../layouts/public.php'; 
?>
