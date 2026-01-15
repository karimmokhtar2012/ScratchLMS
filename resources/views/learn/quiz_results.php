<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        background: '#09090b',
                        surface: '#18181b', 
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-background text-zinc-200 h-screen flex items-center justify-center font-sans">

    <div class="bg-surface border border-zinc-800 rounded-3xl p-12 max-w-lg w-full text-center shadow-2xl">
        
        <?php if ($attempt['is_passed']): ?>
            <div class="w-24 h-24 bg-green-500/20 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 text-5xl border-4 border-green-500/30">
                <i class="fa-solid fa-trophy"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Congratulations!</h1>
            <p class="text-zinc-400 mb-8">You successfully passed the exam.</p>
        <?php else: ?>
            <div class="w-24 h-24 bg-red-500/20 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6 text-5xl border-4 border-red-500/30">
                <i class="fa-solid fa-xmark"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Keep Trying!</h1>
            <p class="text-zinc-400 mb-8">You didn't meet the passing score this time.</p>
        <?php endif; ?>

        <!-- Score Ring -->
        <div class="mb-8">
            <div class="text-6xl font-black text-white tracking-tighter">
                <?= $attempt['score'] ?>%
            </div>
            <div class="text-sm text-zinc-500 uppercase tracking-widest mt-1">Total Score</div>
            <div class="text-xs text-zinc-600 mt-1">Passing Score: <?= $attempt['passing_score'] ?>%</div>
        </div>

        <!-- Actions -->
        <div class="space-y-3">
            <?php if ($attempt['is_passed']): ?>
                <a href="/learn/<?= $attempt['course_id'] ?>" class="block w-full bg-white text-black font-bold py-3 rounded-xl hover:bg-zinc-200 transition">
                    Continue Learning
                </a>
            <?php else: ?>
                <a href="/learn/quiz/start/<?= $attempt['quiz_id'] ?>" class="block w-full bg-white text-black font-bold py-3 rounded-xl hover:bg-zinc-200 transition">
                    Retake Exam
                </a>
            <?php endif; ?>
            
            <a href="/dashboard" class="block w-full text-zinc-500 hover:text-white py-2 text-sm transition">
                Return to Dashboard
            </a>
        </div>

    </div>

</body>
</html>
