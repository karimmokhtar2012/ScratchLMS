<?php ob_start(); ?>

<div class="max-w-4xl mx-auto py-12 px-6">
    <div class="mb-12 text-center">
        <h1 class="text-4xl font-extrabold text-white tracking-tight mb-2">Secure Checkout</h1>
        <p class="text-zinc-400">Complete your purchase to gain instant access.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
        <!-- Order Items -->
        <div class="lg:col-span-7 space-y-6">
            <h2 class="text-xl font-bold text-white mb-4 flex items-center">
                <i class="fa-solid fa-list-ul mr-3 text-zinc-500"></i> Review Items
            </h2>
            <div class="bg-surface rounded-2xl border border-zinc-800 divide-y divide-zinc-800">
                <?php foreach ($courses as $course): ?>
                    <div class="p-4 flex items-center gap-4">
                        <div class="w-20 aspect-video rounded-lg bg-zinc-800 overflow-hidden flex-shrink-0">
                            <img src="<?= $course['thumbnail'] ?: '/assets/images/course.png' ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-grow">
                            <h3 class="text-sm font-bold text-white"><?= htmlspecialchars($course['title']) ?></h3>
                            <p class="text-[10px] text-zinc-500">Lifetime Access</p>
                        </div>
                        <div class="text-sm font-bold text-white">$<?= number_format($course['price'], 2) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Payment Method Simulation -->
            <div class="mt-12 space-y-6">
                <h2 class="text-xl font-bold text-white mb-4 flex items-center">
                    <i class="fa-solid fa-credit-card mr-3 text-zinc-500"></i> Payment Method
                </h2>
                <div class="bg-surface rounded-2xl border border-blue-500/50 p-6 bg-blue-500/5 ring-1 ring-blue-500/20">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-8 bg-zinc-800 rounded flex items-center justify-center border border-zinc-700">
                                <i class="fa-brands fa-cc-visa text-xl text-white"></i>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-white">Mock Card Payment</div>
                                <div class="text-[10px] text-zinc-500">Fast, secure, and encrypted.</div>
                            </div>
                        </div>
                        <i class="fa-solid fa-circle-check text-blue-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Checkout Sidebar -->
        <div class="lg:col-span-5">
            <div class="bg-surface rounded-3xl border border-zinc-700/50 p-8 shadow-2xl sticky top-8 space-y-8">
                <h2 class="text-2xl font-bold text-white border-b border-zinc-800 pb-6">Final Total</h2>
                
                <div class="space-y-4">
                    <div class="flex justify-between text-zinc-400">
                        <span>Items Subtotal</span>
                        <span>$<?= number_format($total, 2) ?></span>
                    </div>
                    <div class="flex justify-between text-zinc-400">
                        <span>Tax / Fees</span>
                        <span>$0.00</span>
                    </div>
                    <div class="pt-4 border-t border-zinc-800 flex justify-between items-center">
                        <span class="text-xl font-bold text-white">Total to Pay</span>
                        <span class="text-3xl font-black text-indigo-400">$<?= number_format($total, 2) ?></span>
                    </div>
                </div>

                <form action="/checkout/process" method="POST">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-black py-5 rounded-2xl transition shadow-xl shadow-indigo-500/20 transform hover:-translate-y-1 active:scale-95 flex items-center justify-center space-x-3">
                        <i class="fa-solid fa-shield-lock"></i>
                        <span>Complete Purchase</span>
                    </button>
                </form>

                <div class="flex items-center justify-center space-x-4 pt-4 grayscale opacity-50">
                    <i class="fa-brands fa-cc-mastercard text-2xl text-white"></i>
                    <i class="fa-brands fa-cc-stripe text-2xl text-white"></i>
                    <i class="fa-brands fa-cc-apple-pay text-2xl text-white"></i>
                </div>
                
                <p class="text-[10px] text-zinc-500 text-center leading-relaxed">By clicking complete, you agree to our Terms of Service and Privacy Policy.</p>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../layouts/public.php'; 
?>
