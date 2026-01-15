<?php ob_start(); ?>

<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white tracking-tight">Create New Course</h1>
        <p class="text-zinc-400 mt-2">Share your knowledge with the world. Fill out the details below.</p>
    </div>

    <div class="bg-surface rounded-2xl border border-zinc-700/50 p-8 shadow-xl">
        <form action="/instructor/course/save" method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-zinc-300 mb-2">Course Title</label>
                <input type="text" name="title" id="title" required
                    class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition placeholder-zinc-600"
                    placeholder="e.g., Advanced PHP Architecture">
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-zinc-300 mb-2">Description</label>
                <textarea name="description" id="description" rows="5" required
                    class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition placeholder-zinc-600"
                    placeholder="What will students learn in this course?"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Price -->
                <div>
                    <label for="price" class="block text-sm font-medium text-zinc-300 mb-2">Price ($)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3 text-zinc-500">$</span>
                        <input type="number" name="price" id="price" min="0" step="0.01" required
                            class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl pl-8 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition"
                            placeholder="0.00">
                    </div>
                </div>

                <!-- Thumbnail -->
                <div>
                    <label for="thumbnail" class="block text-sm font-medium text-zinc-300 mb-2">Thumbnail Image</label>
                    <input type="file" name="thumbnail" id="thumbnail" accept="image/*"
                        class="w-full block w-full text-sm text-zinc-400
                        file:mr-4 file:py-3 file:px-4
                        file:rounded-xl file:border-0
                        file:text-sm file:font-semibold
                        file:bg-zinc-800 file:text-blue-400
                        hover:file:bg-zinc-700 transition">
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
                <button type="submit" 
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-bold py-4 px-6 rounded-xl shadow-lg shadow-blue-500/20 transform hover:-translate-y-0.5 transition duration-200">
                    Create Course & Continue 🚀
                </button>
            </div>

        </form>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../layouts/app.php'; 
?>
