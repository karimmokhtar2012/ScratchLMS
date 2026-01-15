<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Exam</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        background: '#09090b', // zinc-950
                        surface: '#18181b', // zinc-900
                        primary: '#3b82f6', // blue-500
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-background text-zinc-200 h-screen flex flex-col font-sans" 
      x-data="quizRunner()">

    <!-- Header / Progress Bar -->
    <header class="h-16 bg-surface border-b border-zinc-800 flex items-center justify-between px-4 md:px-6 z-20 flex-shrink-0">
        <div class="flex items-center space-x-3 min-w-0">
            <h1 class="text-white font-bold tracking-tight hidden sm:block">Exam</h1>
            <span class="px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-400 text-[10px] md:text-xs font-mono border border-indigo-500/20 whitespace-nowrap">
                Q <span x-text="currentIndex + 1"></span> / <?= count($questions) ?>
            </span>
        </div>

        <!-- Timer -->
        <div class="font-mono text-sm md:text-xl font-bold text-zinc-400 flex items-center bg-zinc-900 px-3 py-1 rounded-full border border-zinc-800">
            <i class="fa-regular fa-clock mr-2 text-indigo-500"></i>
            <span x-text="formatTime(timeRemaining)">--:--</span>
        </div>

        <form action="/learn/quiz/submit/<?= $attempt['id'] ?>" method="POST" x-ref="submitForm" @submit="submitting = true">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <button type="button" @click="submitQuiz()" :disabled="submitting"
                    class="bg-red-600 hover:bg-red-500 text-white px-3 md:px-4 py-1.5 md:py-2 rounded-lg text-xs md:text-sm font-bold transition flex items-center space-x-2 disabled:opacity-50">
                <span x-show="!submitting">Finish</span>
                <span x-show="submitting">...</span>
            </button>
        </form>
    </header>

    <div class="flex-1 flex overflow-hidden">
        
        <!-- Main Question Area -->
        <main class="flex-1 overflow-y-auto p-4 md:p-8 flex justify-center">
            <div class="w-full max-w-3xl space-y-6 md:space-y-8 mt-2">
                
                <!-- Question Card -->
                <div class="bg-surface border border-zinc-700/50 rounded-2xl p-5 md:p-8 shadow-2xl relative min-h-[350px] flex flex-col">
                    
                    <!-- Question Text -->
                    <div class="mb-6 md:mb-8">
                        <div class="flex items-center space-x-2 mb-2">
                            <span class="text-indigo-500 text-[10px] font-bold uppercase tracking-widest" x-text="'Question ' + (currentIndex + 1)"></span>
                            <template x-if="currentQuestion.type === 'multi_choice'">
                                <span class="bg-zinc-800 text-zinc-500 text-[9px] px-1.5 py-0.5 rounded uppercase font-bold">Multiple Select</span>
                            </template>
                        </div>
                        <h2 class="text-xl md:text-2xl font-medium text-white leading-relaxed" x-text="currentQuestion.question_text"></h2>
                    </div>

                    <!-- Options -->
                    <div class="space-y-3 flex-grow">
                        <template x-for="(option, idx) in currentQuestion.options" :key="option.id">
                            <div @click="selectOption(option.id)"
                                 class="flex items-center p-4 rounded-xl border border-zinc-700 bg-zinc-800/50 cursor-pointer transition select-none hover:border-indigo-500/50"
                                 :class="{ 'border-indigo-500 bg-indigo-500/10 ring-1 ring-indigo-500': isSelected(option.id) }">
                                
                                <!-- Visual Indicator -->
                                <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center mr-4 transition flex-shrink-0"
                                     :class="isSelected(option.id) ? 'border-indigo-500 bg-indigo-500 text-white' : 'border-zinc-600 text-transparent'">
                                    <i class="fa-solid fa-check text-[8px]"></i>
                                </div>

                                <span class="text-md md:text-lg text-zinc-200 leading-tight" x-text="option.text"></span>
                            </div>
                        </template>
                        
                        <!-- True/False Override -->
                        <template x-if="currentQuestion.type === 'true_false'">
                             <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div @click="selectOption(1)" 
                                     class="p-6 rounded-xl border border-zinc-700 bg-zinc-800 text-center cursor-pointer hover:border-indigo-500 transition select-none"
                                     :class="{ 'border-indigo-500 bg-indigo-500/10 ring-1 ring-indigo-500': isSelected(1) }">
                                    <span class="text-xl font-bold text-white uppercase tracking-wider">True</span>
                                </div>
                                <div @click="selectOption(0)"
                                     class="p-6 rounded-xl border border-zinc-700 bg-zinc-800 text-center cursor-pointer hover:border-indigo-500 transition select-none"
                                     :class="{ 'border-indigo-500 bg-indigo-500/10 ring-1 ring-indigo-500': isSelected(0) }">
                                    <span class="text-xl font-bold text-white uppercase tracking-wider">False</span>
                                </div>
                             </div>
                        </template>
                    </div>

                    <!-- Navigation Footer -->
                    <div class="mt-8 flex flex-row items-center gap-3 border-t border-zinc-700/50 pt-6">
                        <button @click="prev()" :disabled="currentIndex === 0 || submitting" 
                                class="flex-1 md:flex-none px-4 py-3 bg-zinc-800 text-zinc-400 rounded-xl hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition font-bold text-sm">
                            <i class="fa-solid fa-arrow-left"></i> <span class="ml-2 hidden sm:inline">Back</span>
                        </button>
                        
                        <button @click="next()" x-show="currentIndex < questions.length - 1" :disabled="submitting"
                                class="flex-[2] md:flex-none bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-indigo-500 transition shadow-lg shadow-indigo-500/20 text-sm disabled:opacity-50">
                            <span class="hidden sm:inline">Next Question</span><span class="sm:hidden">Next</span> <i class="fa-solid fa-arrow-right ml-2"></i>
                        </button>
                        
                        <button @click="submitQuiz()" x-show="currentIndex === questions.length - 1" :disabled="submitting"
                                class="flex-[2] md:flex-none bg-red-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-red-500 transition shadow-lg shadow-red-600/20 text-sm disabled:opacity-50">
                            <span x-show="!submitting">Finish</span>
                            <span x-show="submitting">...</span>
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <!-- Sidebar Question Navigator -->
        <aside class="w-72 bg-surface border-l border-zinc-800 p-6 hidden lg:block overflow-y-auto">
            <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-4">Question Navigator</h3>
            <div class="grid grid-cols-4 gap-3">
                <template x-for="(q, idx) in questions" :key="q.id">
                    <button @click="currentIndex = idx" 
                            class="aspect-square rounded-lg flex items-center justify-center text-sm font-bold transition border"
                            :class="{
                                'bg-blue-600 border-blue-500 text-white': currentIndex === idx,
                                'bg-zinc-800 border-zinc-700 text-zinc-400 hover:bg-zinc-700': currentIndex !== idx && !hasAnswered(q.id),
                                'bg-blue-900/30 border-blue-800 text-blue-400': currentIndex !== idx && hasAnswered(q.id)
                            }">
                        <span x-text="idx + 1"></span>
                    </button>
                </template>
            </div>
        </aside>
    </div>

    <!-- Timeout Modal -->
    <div x-show="showTimeoutModal" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm"
         x-transition.opacity>
        <div class="bg-zinc-900 border border-red-500/30 p-8 rounded-2xl shadow-2xl text-center max-w-sm mx-4 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-red-500 animate-[pulse_1s_infinite]"></div>
            <div class="w-16 h-16 bg-red-500/10 rounded-full flex items-center justify-center mx-auto mb-4 text-red-500 text-2xl">
                <i class="fa-solid fa-hourglass-end"></i>
            </div>
            <h2 class="text-2xl font-bold text-white mb-2">Time's Up!</h2>
            <p class="text-zinc-400 mb-6">Your exam time has expired. Submitting your answers now...</p>
            <div class="w-full bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                <div class="bg-red-500 h-full rounded-full animate-[width_3s_linear_forwards]" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <!-- Data Injection -->
    <script>
        const QUESTIONS = <?= json_encode($questions) ?>;
        const ATTEMPT_ID = <?= $attempt['id'] ?>;
        const SAVED_ANSWERS = <?= json_encode($user_answers ?? []) ?>;
        // Use server-calculated remaining time. If 0 (unlimited), use a default or handle logic.
        // Logic: If time_limit > 0, use remainingSeconds. Else use 0 (unlimited).
        const TIME_LIMIT = <?= ($attempt['time_limit'] > 0) ? $remainingSeconds : 0 ?>; 
    </script>

    <script>
        function quizRunner() {
            return {
                questions: QUESTIONS,
                currentIndex: 0,
                answers: SAVED_ANSWERS || {},
                timeRemaining: TIME_LIMIT > 0 ? TIME_LIMIT : 3600, // Show 1h if unlimited just for visuals or hide it
                showTimeoutModal: false,
                submitting: false,

                init() {
                    // Start Timer
                    if (TIME_LIMIT > 0) {
                        const timer = setInterval(() => {
                            if (this.timeRemaining > 0) {
                                this.timeRemaining--;
                            } else {
                                clearInterval(timer);
                                this.handleTimeout();
                            }
                        }, 1000);
                    }
                },

                handleTimeout() {
                    this.showTimeoutModal = true;
                    // Auto submit after 3 seconds visual delay
                    setTimeout(() => {
                        this.$refs.submitForm.submit();
                    }, 3000);
                },

                get currentQuestion() {
                    return this.questions[this.currentIndex];
                },

                get inputType() {
                    return this.currentQuestion.type === 'multi_choice' ? 'checkbox' : 'radio';
                },

                isSelected(optionId) {
                    const ans = this.answers[this.currentQuestion.id];
                    if (Array.isArray(ans)) {
                        return ans.includes(optionId); // Multi choice
                    }
                    return ans == optionId; // Single/TrueFalse
                },

                hasAnswered(questionId) {
                    const ans = this.answers[questionId];
                    return ans !== undefined && ans !== null && (Array.isArray(ans) ? ans.length > 0 : true);
                },

                selectOption(optionId) {
                    const qId = this.currentQuestion.id;
                    const type = this.currentQuestion.type;

                    if (type === 'multi_choice') {
                        // Toggle logic for array
                        let current = this.answers[qId] || [];
                        if (!Array.isArray(current)) current = [];
                        
                        if (current.includes(optionId)) {
                            current = current.filter(id => id !== optionId);
                        } else {
                            current.push(optionId);
                        }
                        this.answers[qId] = current;
                    } else {
                        // Single value
                        this.answers[qId] = optionId;
                    }

                    // Async Save
                    this.saveToServer(qId, this.answers[qId]);
                },

                saveToServer(questionId, answer) {
                    fetch('/learn/quiz/save', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '<?= $csrf_token ?>'
                        },
                        body: JSON.stringify({
                            attempt_id: ATTEMPT_ID,
                            question_id: questionId,
                            answer: answer
                        })
                    }).then(res => console.log('Saved'));
                },

                next() {
                    if (this.currentIndex < this.questions.length - 1) this.currentIndex++;
                },

                prev() {
                    if (this.currentIndex > 0) this.currentIndex--;
                },

                submitQuiz() {
                    if(confirm('Are you sure you want to finish the exam?')) {
                        this.submitting = true;
                        this.$refs.submitForm.submit();
                    }
                },

                formatTime(seconds) {
                    const m = Math.floor(seconds / 60);
                    const s = seconds % 60;
                    return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
                }
            }
        }
    </script>
</body>
</html>