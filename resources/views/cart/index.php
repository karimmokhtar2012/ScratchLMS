<?php ob_start(); ?>

<div class="max-w-5xl mx-auto space-y-8 px-6 py-12">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Shopping Cart</h1>
            <p class="text-zinc-400 mt-2">Review your courses and proceed to secure checkout.</p>
        </div>
        <a href="/courses" class="text-sm text-indigo-400 hover:text-indigo-300 font-medium">
            <i class="fa-solid fa-arrow-left mr-2"></i> Continue Browsing
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Cart Items -->
        <div class="lg:col-span-8 space-y-4">
            <?php if (empty($courses)): ?>
                <div class="bg-surface rounded-2xl border border-dashed border-zinc-700 p-12 text-center">
                    <div class="bg-zinc-800 p-4 rounded-full inline-block mb-4">
                        <i class="fa-solid fa-cart-shopping text-4xl text-zinc-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Your cart is empty</h3>
                    <p class="text-zinc-400 mb-6">Looks like you haven't added any courses yet.</p>
                    <a href="/courses" class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-3 rounded-xl font-bold transition">
                        Browse Catalog
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <div class="bg-surface rounded-2xl border border-zinc-700/50 p-4 flex items-center gap-6 hover:border-zinc-500 transition group">
                        <!-- Thumbnail -->
                        <div class="w-32 aspect-video rounded-xl bg-zinc-800 overflow-hidden flex-shrink-0">
                            <img src="<?= $course['thumbnail'] ?: '/assets/images/course.png' ?>" class="w-full h-full object-cover">
                        </div>

                        <!-- Content -->
                        <div class="flex-grow">
                            <h3 class="text-white font-bold group-hover:text-indigo-400 transition"><?= htmlspecialchars($course['title']) ?></h3>
                            <p class="text-xs text-zinc-500 mt-1">Instructor: <?= htmlspecialchars($course['instructor_name'] ?? 'Instructor') ?></p>
                        </div>

                        <!-- Price & Action -->
                        <div class="text-right space-y-2">
                            <div class="text-lg font-bold text-white"><?= $course['price'] > 0 ? '$' . number_format($course['price'], 2) : 'Free' ?></div>
                            <form action="/cart/remove/<?= $course['id'] ?>" method="POST">
                                <button type="submit" class="text-xs text-zinc-500 hover:text-red-400 transition">
                                    <i class="fa-solid fa-trash-can mr-1"></i> Remove
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Order Summary -->
        <?php if (!empty($courses)): ?>
        <div class="lg:col-span-4">
            <div class="bg-surface rounded-2xl border border-zinc-700/50 p-6 shadow-xl sticky top-8 space-y-6">
                <h2 class="text-xl font-bold text-white border-b border-zinc-800 pb-4">Order Summary</h2>
                
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-400">Subtotal (<?= count($courses) ?> items)</span>
                        <span class="text-white font-medium">$<?= number_format($total, 2) ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-400">Discount</span>
                        <span class="text-green-500">-$0.00</span>
                    </div>
                    <div class="pt-3 border-t border-zinc-800 flex justify-between">
                        <span class="text-lg font-bold text-white">Total</span>
                        <span class="text-2xl font-bold text-indigo-400">$<?= number_format($total, 2) ?></span>
                    </div>
                </div>

                <div class="space-y-4">
                    <a href="/checkout" class="block w-full text-center bg-white text-black font-bold py-4 rounded-xl hover:bg-zinc-200 transition shadow-lg shadow-white/5">
                        Checkout Now
                    </a>
                    <p class="text-[10px] text-zinc-500 text-center uppercase tracking-widest font-bold">Secure Checkout Powered by LMS</p>
                </div>

                <!-- Coupon Box -->
                <div class="pt-6 border-t border-zinc-800">
                    <form action="/cart/coupon" method="POST" class="flex gap-2">
                        <input type="text" name="coupon" placeholder="Coupon Code" class="flex-grow bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                        <button type="submit" class="bg-zinc-800 text-zinc-300 px-4 py-2 rounded-lg text-sm font-bold border border-zinc-700 hover:text-white transition">Apply</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../layouts/public.php'; 
?>
