<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Intro</title>
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
<body class="bg-background text-zinc-200 min-h-screen flex items-center justify-center font-sans p-6 py-12">

    <div class="bg-surface border border-zinc-800 rounded-3xl p-8 md:p-10 max-w-4xl w-full shadow-2xl relative overflow-hidden">
        
        <!-- Background Glow -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-600/10 rounded-full blur-[100px] -mr-20 -mt-20"></div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative z-10">
            
            <!-- Left Column: Overview & Action -->
            <div class="flex flex-col justify-center">
                <div class="mb-6">
                    <div class="w-14 h-14 bg-indigo-500/10 rounded-2xl flex items-center justify-center mb-4 text-indigo-500 text-2xl border border-indigo-500/20 shadow-lg shadow-indigo-500/10">
                        <i class="fa-solid fa-file-signature"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-2 leading-tight"><?= htmlspecialchars($lessonTitle) ?></h1>
                    
                    <div class="flex items-center gap-4 mt-4 text-sm font-medium">
                        <?php if ($quiz['show_correct_answers']): ?>
                            <div class="flex items-center text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                                <i class="fa-solid fa-eye mr-2"></i> Review Enabled
                            </div>
                        <?php else: ?>
                            <div class="flex items-center text-zinc-500 bg-zinc-800 px-3 py-1 rounded-full border border-zinc-700">
                                <i class="fa-solid fa-eye-slash mr-2"></i> Review Disabled
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-indigo-900/10 border border-indigo-500/20 p-4 rounded-xl mb-6">
                    <h3 class="text-indigo-300 font-bold mb-2 flex items-center text-sm uppercase tracking-wide"><i class="fa-solid fa-circle-info mr-2"></i> Instructions</h3>
                    <ul class="text-sm text-indigo-200/80 list-disc list-inside space-y-1">
                        <li>Ensure you have a stable internet connection.</li>
                        <li>Do not refresh the page during the exam.</li>
                        <li>You can flag questions for review inside.</li>
                    </ul>
                </div>

                <?php 
                    $attemptsUsed = count($attempts);
                    $maxAttempts = $quiz['allowed_attempts'];
                    
                    // Check for an incomplete attempt
                    $incompleteAttempt = null;
                    foreach($attempts as $a) {
                        if (!$a['completed_at']) {
                            $incompleteAttempt = $a;
                            break;
                        }
                    }

                    $canStart = ($incompleteAttempt !== null) || ($maxAttempts == 0) || ($attemptsUsed < $maxAttempts);
                ?>

                <?php if ($canStart): ?>
                    <a href="/learn/quiz/start/<?= $quiz['id'] ?>" class="w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold py-4 rounded-xl transition shadow-xl shadow-indigo-500/20 text-center text-lg transform hover:-translate-y-0.5 flex items-center justify-center gap-3">
                        <?= $incompleteAttempt ? 'Resume Attempt' : 'Start Attempt' ?> 
                        <i class="fa-solid <?= $incompleteAttempt ? 'fa-play' : 'fa-arrow-right' ?>"></i>
                    </a>
<?php else: ?>
                    <div class="space-y-4">
                        <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm flex items-start gap-3">
                            <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                            <p>You have reached the maximum number of attempts allowed for this quiz. You can no longer start a new session.</p>
                        </div>
                        
                        <?php if (isset($pendingRequest)): ?>
                            <div class="w-full bg-yellow-500/10 border border-yellow-500/20 text-yellow-500 font-bold py-4 rounded-xl text-center flex items-center justify-center gap-2">
                                <i class="fa-solid fa-clock-rotate-left"></i> Request Pending Approval
                            </div>
                <?php else: ?>
                    <form action="/learn/quiz/request-reset" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                        <button type="submit" class="w-full bg-zinc-800 hover:bg-zinc-700 text-white font-bold py-4 rounded-xl transition flex items-center justify-center space-x-2 border border-zinc-700">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>Request Retake</span>
                        </button>
                    </form>
                <?php endif; ?>

                        <div class="flex flex-col gap-2 pt-2">
                            <a href="/course/<?= $courseId ?>" class="block w-full text-center bg-zinc-800 hover:bg-zinc-700 text-white py-3 rounded-xl text-sm font-bold transition border border-zinc-700">
                                <i class="fa-solid fa-chevron-left mr-2"></i> Back to Course
                            </a>
                            <a href="/dashboard" class="block w-full text-center text-zinc-500 hover:text-white py-2 text-xs transition underline underline-offset-4 decoration-zinc-800">
                                Go to Dashboard
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Stats & History -->
            <div class="flex flex-col gap-6">
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="bg-gradient-to-r from-purple-500/20 via-fuchsia-500/20 to-indigo-500/20 border border-purple-400/60 text-purple-100 px-6 py-4 rounded-xl mb-4 text-center font-bold text-sm relative overflow-hidden">
                <div class="absolute inset-0 pointer-events-none opacity-20"
                     style="background-image: linear-gradient(transparent 0, transparent 23px, rgba(255,255,255,0.08) 24px), linear-gradient(90deg, transparent 0, transparent 23px, rgba(255,255,255,0.08) 24px); background-size: 24px 24px;"></div>
                <i class="fa-solid fa-circle-exclamation mr-2"></i> <?= $_SESSION['flash_error'] ?>
                <?php unset($_SESSION['flash_error']); ?>
            </div>
        <?php endif; ?>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-zinc-900/50 border border-zinc-800 p-5 rounded-xl">
                        <div class="text-xs text-zinc-500 uppercase font-bold tracking-wider mb-1">Time Limit</div>
                        <div class="text-2xl font-bold text-white"><?= $quiz['time_limit'] > 0 ? $quiz['time_limit'] . '<span class="text-sm font-normal text-zinc-500 ml-1">mins</span>' : 'None' ?></div>
                    </div>
                    <div class="bg-zinc-900/50 border border-zinc-800 p-5 rounded-xl">
                        <div class="text-xs text-zinc-500 uppercase font-bold tracking-wider mb-1">Attempts</div>
                        <div class="text-2xl font-bold text-white">
                            <?= count($attempts) ?> <span class="text-zinc-600 text-lg">/ <?= $quiz['allowed_attempts'] > 0 ? $quiz['allowed_attempts'] : '∞' ?></span>
                        </div>
                    </div>
                    <div class="bg-zinc-900/50 border border-zinc-800 p-5 rounded-xl">
                        <div class="text-xs text-zinc-500 uppercase font-bold tracking-wider mb-1">Questions</div>
                        <div class="text-2xl font-bold text-white"><?= $questionCount ?></div>
                    </div>
                    <div class="bg-zinc-900/50 border border-zinc-800 p-5 rounded-xl">
                        <div class="text-xs text-zinc-500 uppercase font-bold tracking-wider mb-1">Pass Score</div>
                        <div class="text-2xl font-bold text-emerald-400"><?= $quiz['passing_score'] ?>%</div>
                    </div>
                </div>

                <!-- History -->
                <div class="flex-1 bg-zinc-900/30 border border-zinc-800 rounded-xl p-6 overflow-hidden flex flex-col">
                    <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-widest mb-4">Attempt History</h4>
                    
                    <?php if (empty($attempts)): ?>
                        <div class="flex-1 flex flex-col items-center justify-center text-zinc-600">
                            <i class="fa-regular fa-folder-open text-2xl mb-2"></i>
                            <span class="text-sm">No attempts yet.</span>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2 overflow-y-auto pr-2 custom-scrollbar">
                            <?php foreach ($attempts as $attempt): ?>
                                <div class="flex items-center justify-between text-sm p-3 rounded-lg bg-zinc-900 border border-zinc-800 hover:border-zinc-700 transition">
                                    <div class="flex items-center gap-3">
                                        <span class="text-zinc-400"><?= date('M j, H:i', strtotime($attempt['started_at'])) ?></span>
                                        <?php if ($attempt['completed_at']): ?>
                                            <span class="font-bold px-2 py-0.5 rounded text-[10px] uppercase tracking-wide <?= $attempt['is_passed'] ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20' ?>">
                                                <?= $attempt['score'] ?>% <?= $attempt['is_passed'] ? 'Pass' : 'Fail' ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-yellow-500 text-[10px] font-bold bg-yellow-500/10 px-2 py-0.5 rounded border border-yellow-500/20 uppercase tracking-wide">In Progress</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($attempt['completed_at'] && !empty($quiz['show_correct_answers'])): ?>
                                        <a href="/learn/quiz/review/<?= $attempt['id'] ?>" class="text-xs bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white px-3 py-1.5 rounded-md font-bold transition border border-zinc-700">
                                            <i class="fa-solid fa-eye mr-1"></i> Review
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
