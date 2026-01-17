<?php ob_start(); ?>

<div class="max-w-7xl mx-auto" x-data="{ showImportModal: false }">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="/instructor/course/<?= $course_id ?>/edit" class="text-zinc-500 hover:text-white text-sm flex items-center mb-2 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Course Builder
            </a>
            <h1 class="text-3xl font-bold text-white flex items-center">
                Quiz Builder
                <div class="relative inline-block ml-3 group" x-data="{ tooltip: false }">
                    <button @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="text-zinc-500 hover:text-indigo-400 transition focus:outline-none">
                        <i class="fa-solid fa-circle-info text-sm"></i>
                    </button>
                    <div x-show="tooltip" x-transition class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-zinc-800 border border-zinc-700 text-xs text-zinc-300 rounded-lg shadow-xl z-50 text-center pointer-events-none">
                        Create and manage quiz questions and settings.
                        <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-700"></div>
                    </div>
                </div>
            </h1>
            <p class="text-zinc-400">Configure settings and add questions.</p>
        </div>
        <div class="flex items-center space-x-4">
            <button @click="showImportModal = true" class="bg-zinc-800 hover:bg-zinc-700 text-white px-4 py-2 rounded-xl text-xs font-bold border border-zinc-700 transition flex items-center">
                <i class="fa-solid fa-file-import mr-2 text-indigo-400"></i> Import from Bank
            </button>
            <span class="bg-purple-500/10 text-purple-400 px-3 py-1 rounded-full text-xs font-bold border border-purple-500/20 uppercase tracking-wide">
                <?= count($questions) ?> Questions
            </span>
        </div>
    </div>

    <!-- Import Modal -->
    <div x-show="showImportModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" x-transition.opacity style="display: none;">
        <div @click.away="showImportModal = false" class="bg-zinc-900 border border-zinc-800 w-full max-w-4xl max-h-[80vh] rounded-3xl p-8 shadow-2xl flex flex-col" x-data="{ activeBank: null }">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-white">Import Questions</h2>
                <button @click="showImportModal = false" class="text-zinc-500 hover:text-white"><i class="fa-solid fa-times text-xl"></i></button>
            </div>
            <div class="flex flex-1 overflow-hidden gap-6">
                <div class="w-1/3 overflow-y-auto pr-2 space-y-2 border-r border-zinc-800">
                    <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-4">Your Banks</p>
                    <?php foreach ($banks as $bank): ?>
                        <button @click="activeBank = <?= htmlspecialchars(json_encode($bank)) ?>" class="w-full text-left p-4 rounded-2xl transition border" :class="activeBank && activeBank.id == <?= $bank['id'] ?> ? 'bg-indigo-600 border-indigo-500 text-white' : 'bg-zinc-950 border-zinc-800 text-zinc-400 hover:border-zinc-700'">
                            <div class="font-bold text-sm truncate"><?= htmlspecialchars($bank['title']) ?></div>
                            <div class="text-[10px] mt-1 opacity-60"><?= count($bank['questions']) ?> questions</div>
                        </button>
                    <?php endforeach; ?>
                    <?php if (empty($banks)): ?>
                        <p class="text-zinc-600 text-xs text-center py-8">No banks found.</p>
                    <?php endif; ?>
                </div>
                <div class="flex-1 flex flex-col overflow-hidden">
                    <template x-if="activeBank">
                        <form action="/instructor/quiz/import" method="POST" class="flex flex-col h-full">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                            <input type="hidden" name="lesson_id" value="<?= $lesson_id ?>">
                            <div class="flex-1 overflow-y-auto space-y-3 pr-2">
                                <template x-for="q in activeBank.questions" :key="q.id">
                                    <label class="block bg-zinc-950 border border-zinc-800 p-4 rounded-2xl cursor-pointer hover:border-indigo-500/50 transition">
                                        <div class="flex items-start space-x-3">
                                            <input type="checkbox" name="bank_question_ids[]" :value="q.id" class="mt-1 w-4 h-4 text-indigo-600 rounded bg-zinc-900 border-zinc-700">
                                            <div class="flex-1">
                                                <div class="text-xs font-bold text-zinc-500 uppercase mb-1" x-text="q.type.replace('_', ' ')"></div>
                                                <div class="text-sm text-white font-medium" x-text="q.question_text"></div>
                                            </div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                            <div class="pt-6 mt-4 border-t border-zinc-800 flex justify-end">
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-3 rounded-xl font-bold transition shadow-lg shadow-indigo-500/20">Import Selected</button>
                            </div>
                        </form>
                    </template>
                    <template x-if="!activeBank">
                        <div class="flex-1 flex flex-col items-center justify-center text-zinc-600">
                            <i class="fa-solid fa-arrow-left mb-4 text-4xl opacity-20"></i>
                            <p>Select a bank from the left to browse questions.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8" x-data="questionBuilder()">
        
        <!-- LEFT COLUMN: Questions List & Builder (Span 8) -->
        <div class="lg:col-span-8 space-y-8 order-2 lg:order-1">
            
            <!-- Existing Questions -->
            <div class="space-y-4">
                <?php if (empty($questions)): ?>
                    <div class="text-center py-12 bg-zinc-800/30 rounded-2xl border border-dashed border-zinc-700">
                        <i class="fa-solid fa-clipboard-question text-4xl text-zinc-600 mb-3"></i>
                        <p class="text-zinc-500">No questions yet. Add one below!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($questions as $index => $q): ?>
                        <div class="bg-surface rounded-xl border border-zinc-700/50 p-5 hover:border-zinc-600 transition group relative">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <span class="bg-zinc-800 text-zinc-400 px-2 py-0.5 rounded text-xs font-bold border border-zinc-700 uppercase">
                                            <?= str_replace('_', ' ', $q['type']) ?>
                                        </span>
                                        <span class="text-zinc-500 text-xs font-medium"><?= $q['points'] ?> pts</span>
                                    </div>
                                    <h3 class="text-white font-medium text-lg mb-2"><?= htmlspecialchars($q['question_text']) ?></h3>
                                    
                                    <!-- Options Preview -->
                                    <div class="space-y-1 pl-4 border-l-2 border-zinc-800">
                                        <?php if ($q['type'] === 'true_false'): ?>
                                            <?php $isTrue = in_array(1, $q['correct_answers']); ?>
                                            <div class="text-sm <?= $isTrue ? 'text-green-400' : 'text-zinc-500' ?>"><i class="fa-solid fa-check mr-2 opacity-<?= $isTrue ? '100' : '0' ?>"></i> True</div>
                                            <div class="text-sm <?= !$isTrue ? 'text-green-400' : 'text-zinc-500' ?>"><i class="fa-solid fa-check mr-2 opacity-<?= !$isTrue ? '100' : '0' ?>"></i> False</div>
                                        <?php elseif (in_array($q['type'], ['single_choice', 'multi_choice'])): ?>
                                            <?php foreach ($q['options'] as $opt): ?>
                                                <?php $isCorrect = in_array($opt['id'], $q['correct_answers']); ?>
                                                <div class="text-sm flex items-center <?= $isCorrect ? 'text-green-400 font-medium' : 'text-zinc-500' ?>">
                                                    <i class="fa-solid <?= $isCorrect ? 'fa-circle-check' : 'fa-circle' ?> text-[10px] mr-2"></i>
                                                    <?= htmlspecialchars($opt['text']) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php elseif ($q['type'] === 'fill_blank'): ?>
                                            <div class="text-xs text-zinc-500 uppercase tracking-wider mb-2">Accepted Answers</div>
                                            <div class="flex flex-wrap gap-2">
                                                <?php foreach ($q['correct_answers'] as $ans): ?>
                                                    <span class="px-2 py-1 rounded-full bg-zinc-800 text-zinc-300 text-xs"><?= htmlspecialchars((string)$ans) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php elseif ($q['type'] === 'matching'): ?>
                                            <div class="text-xs text-zinc-500 uppercase tracking-wider mb-2">Matching Pairs</div>
                                            <?php foreach ($q['options'] as $pair): ?>
                                                <div class="text-xs text-zinc-400"><?= htmlspecialchars($pair['left_text']) ?> → <?= htmlspecialchars($pair['right_text']) ?></div>
                                            <?php endforeach; ?>
                                        <?php elseif (in_array($q['type'], ['essay', 'video_submission'], true)): ?>
                                            <div class="text-xs text-amber-400">Manual review required.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <button type="button" @click="editQuestion(<?= htmlspecialchars(json_encode($q)) ?>)" class="text-zinc-600 hover:text-indigo-400 transition p-2">
                                        <i class="fa-solid fa-pencil"></i>
                                    </button>
                                    <form action="/instructor/question/delete/<?= $q['id'] ?>" method="POST" onsubmit="return confirm('Delete this question?');">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <button type="submit" class="text-zinc-600 hover:text-red-400 transition p-2">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Builder Form -->
            <div class="bg-surface rounded-2xl border border-zinc-700/50 p-6 shadow-xl relative" x-ref="builderForm">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <i class="fa-solid" :class="mode === 'edit' ? 'fa-pen-to-square text-indigo-500' : 'fa-plus-circle text-blue-500'"></i> 
                        <span class="ml-3" x-text="mode === 'edit' ? 'Edit Question' : 'Add Question'"></span>
                    </h2>
                    <button x-show="mode === 'edit'" @click="cancelEdit()" class="text-xs font-bold text-zinc-500 hover:text-white uppercase tracking-wider transition">
                        Cancel Edit
                    </button>
                </div>

                <form :action="actionUrl" method="POST" class="space-y-6" @submit="submitting = true">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                    <input type="hidden" name="lesson_id" value="<?= $lesson_id ?>">

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <!-- Type -->
                        <div class="md:col-span-1">
                            <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Type</label>
                            <select name="type" x-model="type" class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-blue-500 transition">
                                <option value="single_choice">Single Choice</option>
                                <option value="multi_choice">Multiple Choice</option>
                                <option value="true_false">True / False</option>
                                <option value="fill_blank">Fill in the Blank</option>
                                <option value="matching">Matching</option>
                                <option value="short_text">Short Text</option>
                                <option value="essay">Essay</option>
                                <option value="video_submission">Video Submission</option>
                            </select>
                        </div>
                        
                        <!-- Question Text -->
                        <div class="md:col-span-3">
                            <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Question Text</label>
                            <input type="text" name="question_text" x-model="question_text" required class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-blue-500 transition" placeholder="e.g., What does MVC stand for?">
                        </div>
                    </div>

                    <!-- Dynamic Options Area -->
                    <div class="bg-zinc-900/50 rounded-xl p-6 border border-zinc-800">
                        <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-4">Answer Options</label>

                        <!-- True/False -->
                        <div x-show="type === 'true_false'" class="space-y-3">
                            <label class="flex items-center p-3 bg-zinc-800 border border-zinc-700 rounded-lg cursor-pointer hover:border-blue-500 transition">
                                <input type="radio" name="tf_correct" value="1" x-model="tf_correct" class="form-radio text-blue-600 h-5 w-5 bg-zinc-900 border-zinc-600">
                                <span class="ml-3 text-white font-medium">True</span>
                            </label>
                            <label class="flex items-center p-3 bg-zinc-800 border border-zinc-700 rounded-lg cursor-pointer hover:border-blue-500 transition">
                                <input type="radio" name="tf_correct" value="0" x-model="tf_correct" class="form-radio text-blue-600 h-5 w-5 bg-zinc-900 border-zinc-600">
                                <span class="ml-3 text-white font-medium">False</span>
                            </label>
                        </div>

                        <!-- Single/Multi Choice -->
                        <div x-show="type === 'single_choice' || type === 'multi_choice'" class="space-y-3">
                            <template x-for="(opt, index) in options" :key="index">
                                <div class="flex items-center gap-3">
                                    <!-- Correct Toggle -->
                                    <div class="flex-shrink-0">
                                        <!-- Radio for Single -->
                                        <input x-show="type === 'single_choice'" type="radio" name="correct_option" :value="index" :checked="opt.is_correct" class="w-5 h-5 text-green-500 bg-zinc-900 border-zinc-600 focus:ring-green-500">
                                        
                                        <!-- Checkbox for Multi -->
                                        <input x-show="type === 'multi_choice'" type="checkbox" :name="'is_correct[' + index + ']'" value="1" x-model="opt.is_correct" class="w-5 h-5 text-green-500 bg-zinc-900 border-zinc-600 focus:ring-green-500 rounded">
                                    </div>
                                    <input type="text" name="option_text[]" x-model="opt.text" class="flex-1 bg-zinc-800 border border-zinc-700 text-white rounded-lg px-3 py-2 text-sm focus:border-blue-500" placeholder="Option text...">
                                    <button type="button" @click="removeOption(index)" class="text-zinc-600 hover:text-red-400 p-2" x-show="options.length > 2">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="addOption()" class="mt-2 text-sm text-blue-400 hover:text-blue-300 font-medium flex items-center">
                                <i class="fa-solid fa-plus mr-2"></i> Add Another Option
                            </button>
                        </div>

                        <!-- Fill Blank -->
                        <div x-show="type === 'fill_blank'" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Accepted Answers (comma separated)</label>
                                <input type="text" name="fill_blank_answers" x-model="fill_blank_answers" class="w-full bg-zinc-800 border border-zinc-700 text-white rounded-lg px-3 py-2 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Grading Policy</label>
                                <select name="grading_policy" x-model="grading_policy" class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3">
                                    <option value="both">Strict + Fuzzy</option>
                                    <option value="strict">Strict Only</option>
                                    <option value="fuzzy">Fuzzy Only</option>
                                </select>
                            </div>
                        </div>

                        <!-- Matching -->
                        <div x-show="type === 'matching'" class="space-y-3">
                            <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Pairs (Left → Right)</label>
                            <template x-for="(pair, idx) in matchingPairs" :key="idx">
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="text" :name="`match_left[${idx}]`" x-model="pair.left" class="bg-zinc-800 border border-zinc-700 text-white rounded-lg px-3 py-2 focus:border-blue-500" placeholder="Left item">
                                    <input type="text" :name="`match_right[${idx}]`" x-model="pair.right" class="bg-zinc-800 border border-zinc-700 text-white rounded-lg px-3 py-2 focus:border-blue-500" placeholder="Right item">
                                </div>
                            </template>
                            <button type="button" @click="addMatchPair()" class="text-sm text-blue-400 hover:text-blue-300 font-medium flex items-center">
                                <i class="fa-solid fa-plus mr-2"></i> Add Pair
                            </button>
                        </div>

                        <!-- Short Text / Essay / Video -->
                        <div x-show="['short_text', 'essay', 'video_submission'].includes(type)" class="space-y-4">
                            <div x-show="type !== 'short_text'">
                                <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Grading Policy</label>
                                <select name="grading_policy" x-model="grading_policy" class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3">
                                    <option value="ai">AI Pre-Score + Manual Review</option>
                                    <option value="manual">Manual Review Only</option>
                                </select>
                            </div>
                            <div x-show="type !== 'video_submission'">
                                <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">AI Rubric (Optional)</label>
                                <textarea name="ai_rubric" x-model="ai_rubric" rows="3" class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-blue-500" placeholder="Instructions for AI grading..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                         <div>
                            <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Points</label>
                            <input type="number" name="points" x-model="points" min="1" class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Explanation (Optional)</label>
                            <input type="text" name="explanation" x-model="explanation" class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-blue-500" placeholder="Shown after answering...">
                        </div>
                    </div>

                    <button type="submit" :disabled="submitting"
                        class="w-full font-bold py-4 rounded-xl shadow-lg transform hover:-translate-y-0.5 transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="mode === 'edit' ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white shadow-indigo-500/20' : 'bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white shadow-blue-500/20'">
                        <span x-text="mode === 'edit' ? 'Update Question' : 'Add Question'"></span>
                    </button>
                </form>
            </div>
        </div>

        <!-- RIGHT COLUMN: Settings Sidebar (Span 4) -->
        <div class="lg:col-span-4 space-y-6 order-1 lg:order-2">
            <div class="bg-zinc-950 border border-zinc-800 rounded-2xl shadow-2xl sticky top-8 overflow-hidden">
                <!-- Stylish Header -->
                <div class="bg-gradient-to-r from-zinc-900 to-zinc-900/50 p-6 border-b border-zinc-800">
                    <h2 class="text-lg font-bold text-white flex items-center">
                        <span class="w-8 h-8 rounded-lg bg-zinc-800 flex items-center justify-center mr-3 border border-zinc-700 text-indigo-400 shadow-inner">
                            <i class="fa-solid fa-sliders"></i>
                        </span>
                        Quiz Configuration
                    </h2>
                </div>

                <div class="p-6 space-y-6">
                    <form action="/instructor/quiz/settings" method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                        <input type="hidden" name="lesson_id" value="<?= $lesson_id ?>">

                        <!-- Status Group -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest">Visibility</label>
                            <div class="relative">
                                <select name="status" class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-500 transition appearance-none cursor-pointer">
                                    <option value="draft" <?= ($quiz['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                                    <option value="published" <?= ($quiz['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published (Live)</option>
                                    <option value="upcoming" <?= ($quiz['status'] ?? '') === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-zinc-500">
                                    <i class="fa-solid fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Constraints Group -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest">Time (Min)</label>
                                <input type="number" name="time_limit" value="<?= $quiz['time_limit'] ?>" min="0" 
                                    class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-3 py-3 text-center font-mono focus:border-indigo-500 transition">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest">Pass (%)</label>
                                <input type="number" name="passing_score" value="<?= $quiz['passing_score'] ?>" min="0" max="100" 
                                    class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-3 py-3 text-center font-mono focus:border-indigo-500 transition">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest">Attempts Allowed</label>
                            <input type="number" name="allowed_attempts" value="<?= $quiz['allowed_attempts'] ?>" min="0" 
                                class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:border-indigo-500 transition" placeholder="0 for unlimited">
                        </div>

                        <hr class="border-zinc-800">

                        <!-- Toggles -->
                        <div class="space-y-4">
                            <label class="flex items-center justify-between cursor-pointer group">
                                <span class="text-sm font-medium text-zinc-300 group-hover:text-white transition">Shuffle Questions</span>
                                <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                                    <input type="checkbox" name="is_randomized" value="1" <?= $quiz['is_randomized'] ? 'checked' : '' ?> class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-zinc-400 border-4 appearance-none cursor-pointer"/>
                                    <label class="toggle-label block overflow-hidden h-5 rounded-full bg-zinc-800 cursor-pointer"></label>
                                </div>
                            </label>
                            <label class="flex items-center justify-between cursor-pointer group">
                                <span class="text-sm font-medium text-zinc-300 group-hover:text-white transition">Show Answers</span>
                                <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                                    <input type="checkbox" name="show_correct_answers" value="1" <?= $quiz['show_correct_answers'] ? 'checked' : '' ?> class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-zinc-400 border-4 appearance-none cursor-pointer"/>
                                    <label class="toggle-label block overflow-hidden h-5 rounded-full bg-zinc-800 cursor-pointer"></label>
                                </div>
                            </label>
                        </div>

                        <button type="submit" class="w-full bg-zinc-800 hover:bg-zinc-700 text-white font-bold py-3 rounded-xl transition border border-zinc-700 shadow-lg">
                            Save Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .toggle-checkbox:checked { right: 0; border-color: #6366f1; }
    .toggle-checkbox:checked + .toggle-label { background-color: #6366f1; }
    .toggle-checkbox { right: 50%; transition: all 0.3s; }
</style>

<script>
    function questionBuilder() {
        return {
            mode: 'add',
            actionUrl: '/instructor/question/add',
            type: 'single_choice',
            submitting: false,
            question_text: '',
            points: 1,
            explanation: '',
            tf_correct: 1,
            fill_blank_answers: '',
            grading_policy: 'auto',
            ai_rubric: '',
            options: [ { text: '', is_correct: false }, { text: '', is_correct: false } ],
            matchingPairs: [ { left: '', right: '' }, { left: '', right: '' } ],

            addOption() { this.options.push({ text: '', is_correct: false }); },
            removeOption(index) { this.options.splice(index, 1); },
            addMatchPair() { this.matchingPairs.push({ left: '', right: '' }); },

            resetForm() {
                this.mode = 'add';
                this.actionUrl = '/instructor/question/add';
                this.type = 'single_choice';
                this.question_text = '';
                this.points = 1;
                this.explanation = '';
                this.tf_correct = 1;
                this.fill_blank_answers = '';
                this.grading_policy = 'auto';
                this.ai_rubric = '';
                this.options = [ { text: '', is_correct: false }, { text: '', is_correct: false } ];
                this.matchingPairs = [ { left: '', right: '' }, { left: '', right: '' } ];
            },

            editQuestion(q) {
                this.resetForm();
                this.mode = 'edit';
                this.actionUrl = '/instructor/question/update/' + q.id;
                this.type = q.type;
                this.question_text = q.question_text;
                this.points = q.points;
                this.explanation = q.explanation || '';
                this.grading_policy = q.grading_policy || 'auto';
                this.ai_rubric = q.ai_rubric || '';

                if (['single_choice', 'multi_choice'].includes(q.type)) {
                    // Map options and set is_correct based on correct_answers ID
                    this.options = q.options.map(opt => ({
                        text: opt.text,
                        is_correct: q.correct_answers.includes(opt.id)
                    }));
                } else if (q.type === 'true_false') {
                    this.tf_correct = q.correct_answers[0];
                } else if (q.type === 'fill_blank') {
                    this.fill_blank_answers = q.correct_answers.join(', ');
                } else if (q.type === 'matching') {
                    this.matchingPairs = q.options.map(opt => ({
                        left: opt.left_text,
                        right: opt.right_text
                    }));
                }

                this.$refs.builderForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
            },

            cancelEdit() {
                this.resetForm();
            }
        }
    }
</script>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../layouts/app.php'; 
?>