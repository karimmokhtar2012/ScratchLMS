<?php 
ob_start(); 
$totalPoints = array_sum(array_column($questions, 'points'));
?>

<div class="max-w-7xl mx-auto" x-data="{ showImportModal: false }">
    
    <!-- Simple Header -->
    <div class="mb-8">
        <a href="/instructor/course/<?= $course_id ?>/edit" class="text-zinc-500 hover:text-white text-sm flex items-center mb-3 transition w-fit">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back to Course Builder
        </a>
        <h1 class="text-3xl font-bold text-white tracking-tight">Quiz Builder</h1>
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
        
        <!-- LEFT COLUMN: Questions Timeline (Span 8) -->
        <div class="lg:col-span-8 order-2 lg:order-1 relative">
            
            <!-- Vertical Timeline Line -->
            <div class="absolute left-6 top-6 bottom-0 w-0.5 bg-zinc-800/50 hidden md:block"></div>

            <div class="space-y-8">
                <?php if (empty($questions)): ?>
                    <div class="ml-0 md:ml-16 bg-zinc-900/30 border border-zinc-800/50 rounded-2xl p-8 text-center border-dashed">
                        <div class="w-16 h-16 bg-zinc-800 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-clipboard-question text-zinc-600 text-2xl"></i>
                        </div>
                        <h3 class="text-white font-bold mb-1">Start Building</h3>
                        <p class="text-zinc-500 text-sm">Add your first question to get started.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($questions as $index => $q): ?>
                        <div class="relative pl-0 md:pl-16 group">
                            <!-- Timeline Dot -->
                            <div class="absolute left-3 top-6 w-6 h-6 rounded-full bg-zinc-900 border-4 border-zinc-800 hidden md:flex items-center justify-center z-10 group-hover:border-indigo-500/50 group-hover:scale-110 transition duration-300">
                                <div class="w-1.5 h-1.5 rounded-full bg-zinc-600 group-hover:bg-indigo-400"></div>
                            </div>

                            <!-- Question Card -->
                            <div class="bg-zinc-950 border border-zinc-800 rounded-2xl p-6 hover:border-zinc-700 transition duration-300 shadow-sm relative overflow-hidden">
                                <!-- Type Color Strip -->
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-zinc-700 to-transparent group-hover:from-indigo-500 transition-colors"></div>

                                <div class="flex justify-between items-start">
                                    <div class="flex-1 pr-4">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="text-xs font-black text-zinc-500 uppercase tracking-widest">Question <?= $index + 1 ?></span>
                                            <span class="bg-zinc-900 text-zinc-400 px-2 py-0.5 rounded text-[10px] font-bold border border-zinc-800 uppercase">
                                                <?= str_replace('_', ' ', $q['type']) ?>
                                            </span>
                                            <span class="text-emerald-500 text-xs font-bold bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20"><?= $q['points'] ?> pts</span>
                                        </div>
                                        <h3 class="text-white font-medium text-lg leading-snug mb-3"><?= htmlspecialchars_decode($q['question_text']) ?></h3>
                                        
                                        <!-- Mini Preview (Collapsed) -->
                                        <div class="text-xs text-zinc-500 flex items-center gap-2">
                                            <?php if(in_array($q['type'], ['single_choice', 'multi_choice'])): ?>
                                                <i class="fa-solid fa-list-ul"></i> <?= count($q['options']) ?> Options
                                            <?php elseif($q['type'] === 'true_false'): ?>
                                                <i class="fa-solid fa-toggle-on"></i> True/False
                                            <?php else: ?>
                                                <i class="fa-solid fa-keyboard"></i> Text Input
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <button type="button" @click="editQuestion(<?= htmlspecialchars(json_encode($q)) ?>)" class="w-8 h-8 rounded-lg flex items-center justify-center text-zinc-400 hover:text-white hover:bg-zinc-800 transition">
                                            <i class="fa-solid fa-pencil text-sm"></i>
                                        </button>
                                        <form action="/instructor/question/delete/<?= $q['id'] ?>" method="POST" onsubmit="return confirm('Delete this question?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <button type="submit" class="w-8 h-8 rounded-lg flex items-center justify-center text-zinc-400 hover:text-red-400 hover:bg-red-400/10 transition">
                                                <i class="fa-solid fa-trash text-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Add Button (End of Timeline) -->
                <div class="relative pl-0 md:pl-16">
                    <!-- Timeline Dot (Add) -->
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-blue-500/20 border-4 border-black hidden md:flex items-center justify-center z-10 animate-pulse">
                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500"></div>
                    </div>

                    <div class="bg-gradient-to-r from-blue-900/10 to-transparent border border-blue-500/20 border-dashed rounded-2xl p-8 flex flex-col items-center justify-center text-center group hover:border-blue-500/40 transition-all duration-300">
                        <div class="w-14 h-14 bg-blue-500/10 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition duration-300">
                            <i class="fa-solid fa-plus text-2xl text-blue-400"></i>
                        </div>
                        <h3 class="text-white font-bold mb-2">Grow Your Quiz</h3>
                        <p class="text-zinc-500 text-sm mb-6 max-w-sm">Add another question to test your students' knowledge.</p>
                        <button @click="openAddModal()" 
                                class="px-8 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold shadow-lg shadow-blue-600/20 transition transform hover:-translate-y-0.5 flex items-center">
                            <span>Add Question</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Question Modal (Expanded & Fixed) -->
            <div x-show="showQuestionModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/90 backdrop-blur-md" x-transition.opacity style="display: none;">
                <div @click.away="showQuestionModal = false" class="w-full max-w-[98%] h-[95vh] overflow-hidden rounded-3xl shadow-2xl bg-zinc-950 border border-zinc-800 relative flex flex-col">
                    
                    <!-- Header -->
                    <div class="flex items-center justify-between px-8 py-6 border-b border-zinc-800 bg-zinc-900/50 z-50 backdrop-blur-md shrink-0">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-5 shadow-lg"
                                 :class="mode === 'edit' ? 'bg-indigo-500 text-white shadow-indigo-500/20' : 'bg-blue-500 text-white shadow-blue-500/20'">
                                <i class="fa-solid text-xl" :class="mode === 'edit' ? 'fa-pen-to-square' : 'fa-plus'"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-white leading-tight" x-text="mode === 'edit' ? 'Edit Question' : 'Add New Question'"></h2>
                                <p class="text-sm text-zinc-500 uppercase tracking-widest font-medium" x-text="mode === 'edit' ? 'Updating assessment' : 'Creating new item'"></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <!-- Layout Toggles (Visible only when focused) -->
                            <div x-show="isFocused" x-transition.opacity class="flex items-center bg-zinc-900 rounded-xl p-1.5 border border-zinc-800">
                                <button type="button" @click="isFocused = false" class="px-4 py-2 rounded-lg text-xs font-bold text-zinc-400 hover:text-white hover:bg-zinc-800 transition flex items-center uppercase tracking-wide">
                                    <i class="fa-solid fa-columns mr-2"></i> Show Sidebars
                                </button>
                            </div>
                            <button @click="showQuestionModal = false" class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 text-zinc-400 hover:text-white hover:bg-zinc-700 transition">
                                <i class="fa-solid fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body (3 Columns) -->
                    <form :action="actionUrl" method="POST" @submit="submitting = true" class="flex-1 overflow-hidden grid grid-cols-12">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                        <input type="hidden" name="lesson_id" value="<?= $lesson_id ?>">
                        <input type="hidden" name="type" x-model="type">
                        
                        <!-- Hidden input to store rich text content -->
                        <input type="hidden" name="question_text" :value="question_text">

                        <!-- LEFT COLUMN: Guide & Settings (Span 3) -->
                        <div class="col-span-3 border-r border-zinc-800 bg-zinc-900/20 flex flex-col p-8 gap-8 overflow-y-auto transition-all duration-500 ease-in-out"
                             :class="isFocused ? 'w-0 opacity-0 px-0 py-0' : 'w-[25%] opacity-100'">
                            
                            <!-- 1. Type Info -->
                            <div class="p-6 rounded-3xl bg-zinc-900/50 border border-zinc-800 flex flex-col gap-4 shrink-0 shadow-sm min-w-[240px]">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl shrink-0 flex items-center justify-center transition-colors duration-300" :class="`bg-${questionTypes[type].color}-500/10 text-${questionTypes[type].color}-400`">
                                        <i class="fa-solid text-xl" :class="`fa-${questionTypes[type].icon}`"></i>
                                    </div>
                                    <h3 class="text-base font-bold text-white uppercase tracking-wider" x-text="questionTypes[type].label"></h3>
                                </div>
                                <p class="text-sm text-zinc-400 leading-relaxed" x-text="questionTypes[type].description"></p>
                                <div class="flex items-start gap-3 text-xs text-zinc-500 bg-black/20 p-4 rounded-xl border border-zinc-800/50">
                                    <i class="fa-solid fa-lightbulb text-amber-500/70 mt-0.5 text-sm"></i>
                                    <span x-text="questionTypes[type].guide" class="leading-relaxed"></span>
                                </div>
                            </div>

                            <div class="h-px bg-zinc-800/50 w-full shrink-0"></div>

                            <!-- 2. Settings -->
                            <div class="flex-1 flex flex-col gap-6 min-w-[240px]">
                                <label class="text-xs font-black text-zinc-500 uppercase tracking-widest">Configuration</label>
                                
                                <!-- Points (Fixed Width Logic) -->
                                <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6 shrink-0 shadow-inner">
                                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-widest mb-4 flex justify-between">
                                        Points Value <i class="fa-solid fa-star text-amber-500/50"></i>
                                    </label>
                                    <div class="flex items-center justify-between gap-3">
                                        <button type="button" @click="points = Math.max(1, parseInt(points) - 1)" class="w-12 h-12 rounded-xl bg-zinc-800 text-zinc-400 hover:text-white hover:bg-zinc-700 transition flex items-center justify-center border border-zinc-700/50 shrink-0"><i class="fa-solid fa-minus"></i></button>
                                        <div class="flex-1 text-center border-b-2 border-zinc-800 pb-1 mx-2 min-w-0">
                                            <input type="number" name="points" x-model="points" min="1" class="w-full bg-transparent text-center text-white font-black text-4xl border-none focus:ring-0 p-0 leading-none">
                                        </div>
                                        <button type="button" @click="points = parseInt(points) + 1" class="w-12 h-12 rounded-xl bg-zinc-800 text-zinc-400 hover:text-white hover:bg-zinc-700 transition flex items-center justify-center border border-zinc-700/50 shrink-0"><i class="fa-solid fa-plus"></i></button>
                                    </div>
                                </div>

                                <!-- Explanation -->
                                <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6 flex-1 flex flex-col min-h-[200px] shadow-inner">
                                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                                        Instructor Note <span class="text-[10px] opacity-40 lowercase font-normal">(Optional)</span>
                                    </label>
                                    <textarea name="explanation" x-model="explanation" class="flex-1 w-full bg-black/20 border border-zinc-800 text-white text-sm rounded-2xl p-4 focus:border-blue-500/50 transition resize-none placeholder-zinc-700 leading-relaxed" placeholder="Write an explanation shown after the student answers..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- MIDDLE COLUMN: Content Editor -->
                        <div class="flex-1 flex flex-col h-full bg-black/40 relative border-r border-zinc-800 transition-all duration-500 ease-in-out">
                            
                            <!-- Prompt Section with Toolbar -->
                            <div class="p-10 border-b border-zinc-800 bg-zinc-900/30 shrink-0 relative group">
                                <div class="flex items-center justify-between mb-6">
                                    <label class="block text-sm font-black text-zinc-500 uppercase tracking-widest flex items-center">
                                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 mr-3 animate-pulse"></span> Question
                                    </label>
                                    
                                    <!-- Editor Toolbar (Visible on Focus) -->
                                    <div x-show="isFocused" x-transition.opacity class="flex items-center gap-2 bg-zinc-900/90 border border-zinc-700/50 rounded-xl p-1.5 shadow-lg backdrop-blur-md">
                                        <button type="button" @click="formatText('bold')" class="w-9 h-9 rounded-lg flex items-center justify-center text-zinc-400 hover:text-white hover:bg-zinc-800 transition" title="Bold"><i class="fa-solid fa-bold text-sm"></i></button>
                                        <button type="button" @click="formatText('italic')" class="w-9 h-9 rounded-lg flex items-center justify-center text-zinc-400 hover:text-white hover:bg-zinc-800 transition" title="Italic"><i class="fa-solid fa-italic text-sm"></i></button>
                                        <button type="button" @click="formatText('underline')" class="w-9 h-9 rounded-lg flex items-center justify-center text-zinc-400 hover:text-white hover:bg-zinc-800 transition" title="Underline"><i class="fa-solid fa-underline text-sm"></i></button>
                                        <div class="w-px h-5 bg-zinc-700 mx-1"></div>
                                        <button type="button" @click="formatText('insertImage', 'https://placehold.co/600x400')" class="w-9 h-9 rounded-lg flex items-center justify-center text-zinc-400 hover:text-white hover:bg-zinc-800 transition" title="Insert Image"><i class="fa-regular fa-image text-sm"></i></button>
                                    </div>
                                </div>

                                <!-- Rich Text Content Editable Div -->
                                <div 
                                    contenteditable="true" 
                                    x-ref="editor"
                                    @input="question_text = $el.innerHTML"
                                    @focus="isFocused = true"
                                    @blur="/* Optional: sync on blur */"
                                    class="w-full bg-transparent text-3xl font-bold text-white placeholder-zinc-800 border-none focus:outline-none focus:ring-0 p-0 resize-none leading-snug min-h-[4rem] empty:before:content-[attr(placeholder)] empty:before:text-zinc-700"
                                    placeholder="Type your assessment question here..."
                                ></div>
                            </div>

                            <!-- Interactive Answer Area -->
                            <div class="flex-1 overflow-y-auto p-10 scrollbar-thin scrollbar-thumb-zinc-800">
                                <!-- True/False -->
                                <div x-show="type === 'true_false'" class="grid grid-cols-2 gap-8">
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="tf_correct" value="1" x-model="tf_correct" class="peer sr-only">
                                        <div class="p-10 rounded-[2rem] border-2 border-zinc-800 bg-zinc-900/50 text-center transition-all duration-300 peer-checked:border-emerald-500 peer-checked:bg-emerald-500/10 group-hover:border-zinc-700 shadow-xl">
                                            <i class="fa-solid fa-check text-4xl mb-4 text-zinc-600 peer-checked:text-emerald-400 transition-colors"></i>
                                            <div class="font-bold text-zinc-500 text-xl peer-checked:text-white transition-colors">True</div>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="tf_correct" value="0" x-model="tf_correct" class="peer sr-only">
                                        <div class="p-10 rounded-[2rem] border-2 border-zinc-800 bg-zinc-900/50 text-center transition-all duration-300 peer-checked:border-red-500 peer-checked:bg-red-500/10 group-hover:border-zinc-700 shadow-xl">
                                            <i class="fa-solid fa-xmark text-4xl mb-4 text-zinc-600 peer-checked:text-red-400 transition-colors"></i>
                                            <div class="font-bold text-zinc-500 text-xl peer-checked:text-white transition-colors">False</div>
                                        </div>
                                    </label>
                                </div>

                                <!-- Choice Logic -->
                                <div x-show="type === 'single_choice' || type === 'multi_choice'" class="space-y-4">
                                    <template x-for="(opt, index) in options" :key="index">
                                        <div class="flex items-center group gap-5">
                                            <label class="cursor-pointer shrink-0">
                                                <input :type="type === 'single_choice' ? 'radio' : 'checkbox'" :name="type === 'single_choice' ? 'correct_option' : 'is_correct[' + index + ']'" :value="index" :checked="opt.is_correct" x-model="opt.is_correct" class="peer sr-only">
                                                <div class="w-14 h-14 rounded-2xl border-2 border-zinc-800 bg-zinc-900/50 flex items-center justify-center transition-all peer-checked:bg-indigo-600 peer-checked:border-indigo-500 peer-checked:shadow-lg peer-checked:shadow-indigo-500/20 group-hover:border-zinc-600">
                                                    <i class="fa-solid fa-check text-white text-lg opacity-0 peer-checked:opacity-100 scale-50 peer-checked:scale-100 transition-all duration-300"></i>
                                                </div>
                                            </label>
                                            <div class="flex-1 relative">
                                                <input type="text" name="option_text[]" x-model="opt.text" class="w-full bg-zinc-900/50 border border-zinc-800 text-white rounded-2xl px-6 py-4 text-lg focus:border-indigo-500/50 transition shadow-sm group-hover:border-zinc-700 focus:bg-black/40" placeholder="Option text...">
                                                <button type="button" @click="removeOption(index)" x-show="options.length > 2" class="absolute right-5 top-1/2 -translate-y-1/2 text-zinc-600 hover:text-red-400 transition p-2"><i class="fa-solid fa-trash-can text-lg"></i></button>
                                            </div>
                                        </div>
                                    </template>
                                    <button type="button" @click="addOption()" class="w-full py-4 rounded-2xl border border-dashed border-zinc-800 text-sm font-bold text-zinc-500 uppercase tracking-widest hover:border-zinc-600 hover:text-zinc-300 transition-all mt-6 flex items-center justify-center gap-3 hover:bg-zinc-900/50"><i class="fa-solid fa-plus"></i> Add Option</button>
                                </div>

                                <!-- Other types (Fill, Matching, AI) -->
                                <div x-show="type === 'fill_blank'" class="space-y-8">
                                    <div class="bg-amber-500/5 border border-amber-500/10 p-8 rounded-3xl">
                                        <label class="block text-sm font-bold text-amber-500 uppercase mb-4 tracking-wide">Accepted Phrases</label>
                                        <input type="text" name="fill_blank_answers" x-model="fill_blank_answers" class="w-full bg-zinc-900/80 border border-zinc-800 text-white rounded-2xl px-6 py-5 text-lg focus:border-amber-500/50 font-mono" placeholder="Comma separated (e.g. color, colour)...">
                                    </div>
                                    <div class="grid grid-cols-3 gap-4">
                                        <template x-for="p in ['both', 'strict', 'fuzzy']">
                                            <label class="cursor-pointer text-center py-4 rounded-2xl border border-zinc-800 bg-zinc-900 text-sm font-bold transition" :class="grading_policy === p ? 'bg-amber-500/10 text-amber-400 border-amber-500/30' : 'text-zinc-500 hover:bg-zinc-800 hover:border-zinc-700'">
                                                <input type="radio" name="grading_policy" :value="p" x-model="grading_policy" class="sr-only">
                                                <span x-text="p === 'both' ? 'Hybrid Match' : p.charAt(0).toUpperCase() + p.slice(1) + ' Only'"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>

                                <div x-show="type === 'matching'" class="space-y-4">
                                    <template x-for="(pair, idx) in matchingPairs" :key="idx">
                                        <div class="flex items-center gap-6 group">
                                            <input type="text" :name="`match_left[${idx}]`" x-model="pair.left" class="flex-1 bg-zinc-900/50 border border-zinc-800 text-white rounded-2xl px-6 py-4 text-base focus:border-pink-500/50 transition text-right group-hover:border-zinc-700" placeholder="Term">
                                            <i class="fa-solid fa-arrow-right-long text-zinc-600 text-xl"></i>
                                            <input type="text" :name="`match_right[${idx}]`" x-model="pair.right" class="flex-1 bg-zinc-900/50 border border-zinc-800 text-white rounded-2xl px-6 py-4 text-base focus:border-pink-500/50 transition group-hover:border-zinc-700" placeholder="Match">
                                        </div>
                                    </template>
                                    <button type="button" @click="addMatchPair()" class="w-full py-4 rounded-2xl border border-dashed border-zinc-800 text-sm font-bold text-zinc-500 hover:text-pink-400 transition mt-6 flex items-center justify-center gap-3 hover:bg-pink-500/5 hover:border-pink-500/20"><i class="fa-solid fa-plus"></i> Add Pair</button>
                                </div>

                                <div x-show="['short_text', 'essay'].includes(type)" class="bg-indigo-500/5 border border-indigo-500/10 p-8 rounded-3xl">
                                    <label class="block text-sm font-bold text-indigo-400 uppercase mb-4 flex items-center gap-3"><i class="fa-solid fa-robot"></i> AI Grading Rubric</label>
                                    <textarea name="ai_rubric" x-model="ai_rubric" rows="5" class="w-full bg-zinc-900/80 border border-zinc-800 text-white text-base rounded-2xl p-6 focus:outline-none focus:border-indigo-500/50 transition leading-relaxed" placeholder="Tell the AI exactly what to look for..."></textarea>
                                </div>
                            </div>

                            <!-- Single Save Button (Sticky Bottom) -->
                            <div class="px-10 py-6 border-t border-zinc-800 bg-zinc-900/50 shrink-0 flex justify-end">
                                <button type="submit" :disabled="submitting" class="px-14 py-5 rounded-2xl font-black text-white shadow-xl transform hover:-translate-y-0.5 transition duration-200 disabled:opacity-50 flex items-center justify-center uppercase tracking-widest text-base" :class="mode === 'edit' ? 'bg-gradient-to-r from-indigo-600 to-violet-600 shadow-indigo-500/20 hover:shadow-indigo-500/40' : 'bg-gradient-to-r from-blue-600 to-cyan-500 shadow-blue-500/20 hover:shadow-blue-500/40'">
                                    <span x-show="!submitting" class="flex items-center"><i class="fa-solid mr-3" :class="mode === 'edit' ? 'fa-floppy-disk' : 'fa-plus'"></i> <span x-text="mode === 'edit' ? 'Update Assessment' : 'Add Question'"></span></span>
                                    <span x-show="submitting"><i class="fa-solid fa-circle-notch fa-spin text-xl"></i></span>
                                </button>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: Type Selector (Span 2) -->
                        <div class="bg-zinc-900/40 flex flex-col p-6 gap-4 overflow-y-auto border-l border-zinc-800 transition-all duration-500 ease-in-out"
                             :class="isFocused ? 'w-0 opacity-0 px-0' : 'w-[20%] opacity-100'">
                            <label class="text-[10px] font-black text-zinc-600 uppercase tracking-widest text-center py-2 min-w-[120px]">Format</label>
                            <div class="flex-1 flex flex-col gap-3 min-w-[120px]">
                                <template x-for="(t, key) in questionTypes">
                                    <button @click="type = key" type="button"
                                            class="w-full relative overflow-hidden group rounded-2xl px-4 py-4 transition-all duration-300 border flex flex-col items-center gap-2 shrink-0 justify-center"
                                            :class="type === key 
                                                ? `bg-gradient-to-b from-${t.color}-900/80 to-${t.color}-600/20 border-${t.color}-500 shadow-lg shadow-${t.color}-500/20` 
                                                : `bg-zinc-950/50 border-zinc-800 hover:bg-zinc-800 hover:border-zinc-700`">
                                        
                                        <div x-show="type === key" class="absolute inset-0 -translate-x-full animate-[shimmer_2s_infinite] bg-gradient-to-r from-transparent via-white/10 to-transparent z-0"></div>

                                        <i class="fa-solid text-2xl transition-transform duration-300 group-hover:scale-110 relative z-10" :class="[`fa-${t.icon}`, type === key ? 'text-white' : `text-${t.color}-500/70`]"></i>
                                        <span class="text-xs font-bold uppercase tracking-tight text-center relative z-10 leading-none" :class="type === key ? 'text-white' : 'text-zinc-600 group-hover:text-zinc-400'" x-text="t.label"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Settings Sidebar (Span 4) -->
        <div class="lg:col-span-4 space-y-6 order-1 lg:order-2">
            
            <!-- Quiz Summary Card -->
            <div class="bg-zinc-950 border border-zinc-800 rounded-2xl p-6 shadow-xl relative overflow-hidden group">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl group-hover:bg-indigo-500/20 transition duration-700"></div>
                <h3 class="text-xs font-black text-zinc-500 uppercase tracking-widest mb-4 flex items-center">
                    <i class="fa-solid fa-chart-pie mr-2"></i> Quiz Overview
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-zinc-900/50 rounded-xl p-4 border border-zinc-800 text-center">
                        <div class="text-2xl font-bold text-white mb-1"><?= count($questions) ?></div>
                        <div class="text-[10px] text-zinc-500 uppercase tracking-wide">Questions</div>
                    </div>
                    <div class="bg-zinc-900/50 rounded-xl p-4 border border-zinc-800 text-center">
                        <div class="text-2xl font-bold text-white mb-1"><?= $totalPoints ?></div>
                        <div class="text-[10px] text-zinc-500 uppercase tracking-wide">Total Points</div>
                    </div>
                </div>
                <button @click="showImportModal = true" class="w-full mt-4 bg-zinc-900 hover:bg-zinc-800 text-zinc-400 hover:text-white px-4 py-3 rounded-xl text-xs font-bold border border-zinc-800 transition flex items-center justify-center">
                    <i class="fa-solid fa-file-import mr-2 text-indigo-500"></i> Import Questions
                </button>
            </div>

            <!-- Configuration Panel -->
            <div class="bg-zinc-950 border border-zinc-800 rounded-2xl shadow-xl sticky top-8 overflow-hidden">
                <div class="bg-gradient-to-r from-zinc-900 to-zinc-900/50 p-6 border-b border-zinc-800">
                    <h2 class="text-lg font-bold text-white flex items-center">
                        <span class="w-8 h-8 rounded-lg bg-zinc-800 flex items-center justify-center mr-3 border border-zinc-700 text-indigo-400 shadow-inner">
                            <i class="fa-solid fa-sliders"></i>
                        </span>
                        Settings
                    </h2>
                </div>

                <div class="p-6 space-y-6">
                    <form action="/instructor/quiz/settings" method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                        <input type="hidden" name="lesson_id" value="<?= $lesson_id ?>">

                        <!-- Status Group -->
                        <div class="space-y-3">
                            <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest flex items-center">
                                <i class="fa-solid fa-eye mr-1.5"></i> Visibility Status
                            </label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-zinc-500 group-hover:text-indigo-400 transition">
                                    <i class="fa-solid fa-globe"></i>
                                </div>
                                <select name="status" class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl pl-11 pr-10 py-3 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/50 transition appearance-none cursor-pointer hover:bg-zinc-800/50">
                                    <option value="draft" <?= ($quiz['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                                    <option value="published" <?= ($quiz['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published (Live)</option>
                                    <option value="upcoming" <?= ($quiz['status'] ?? '') === 'upcoming' ? 'selected' : '' ?>>Upcoming (Scheduled)</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-zinc-500">
                                    <i class="fa-solid fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-zinc-800/50 my-4"></div>

                        <!-- Constraints Group -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest flex items-center">
                                    <i class="fa-regular fa-clock mr-1.5"></i> Time Limit
                                </label>
                                <div class="relative group">
                                    <input type="number" name="time_limit" value="<?= $quiz['time_limit'] ?>" min="0" 
                                        class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl pl-3 pr-10 py-3 text-center font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/50 transition hover:bg-zinc-800/50">
                                    <span class="absolute right-3 top-3.5 text-zinc-500 text-xs font-bold pointer-events-none">min</span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest flex items-center">
                                    <i class="fa-solid fa-trophy mr-1.5"></i> Pass Mark
                                </label>
                                <div class="relative group">
                                    <input type="number" name="passing_score" value="<?= $quiz['passing_score'] ?>" min="0" max="100" 
                                        class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl pl-3 pr-8 py-3 text-center font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/50 transition hover:bg-zinc-800/50">
                                    <span class="absolute right-3 top-3.5 text-zinc-500 text-xs font-bold pointer-events-none">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest flex items-center">
                                <i class="fa-solid fa-rotate-right mr-1.5"></i> Attempts
                            </label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-zinc-500 group-hover:text-indigo-400 transition">
                                    <i class="fa-solid fa-layer-group"></i>
                                </div>
                                <input type="number" name="allowed_attempts" value="<?= $quiz['allowed_attempts'] ?>" min="0" 
                                    class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl pl-11 pr-4 py-3 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/50 transition hover:bg-zinc-800/50" placeholder="0">
                            </div>
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
    @keyframes shimmer { 100% { transform: translateX(100%); } }
    .toggle-checkbox:checked { right: 0; border-color: #6366f1; }
    .toggle-checkbox:checked + .toggle-label { background-color: #6366f1; }
    .toggle-checkbox { right: 50%; transition: all 0.3s; }
    
    /* Custom Thin Scrollbar */
    .scrollbar-thin::-webkit-scrollbar { width: 6px; }
    .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
    .scrollbar-thin::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 10px; }
    .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: #52525b; }

    /* Hide Number Input Arrows */
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    input[type=number] { -moz-appearance: textfield; }
</style>

<script>
    function questionBuilder() {
        return {
            mode: 'add',
            showQuestionModal: false,
            isFocused: false,
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

            questionTypes: {
                single_choice: { 
                    label: 'Single Choice', 
                    icon: 'circle-dot', 
                    color: 'blue',
                    description: 'Select one correct answer.',
                    guide: 'Add options and select the radio button next to the correct answer.'
                },
                multi_choice: { 
                    label: 'Multi Choice', 
                    icon: 'list-check', 
                    color: 'purple',
                    description: 'Select one or more correct answers.',
                    guide: 'Check the box next to every correct answer. All must be found.'
                },
                true_false: { 
                    label: 'True / False', 
                    icon: 'check-double', 
                    color: 'emerald',
                    description: 'Binary choice validation.',
                    guide: 'Define the statement and select whether True or False is correct.'
                },
                fill_blank: { 
                    label: 'Fill in the Blank', 
                    icon: 'keyboard', 
                    color: 'amber',
                    description: 'Type missing words.',
                    guide: 'Provide accepted variations of the answer separated by commas.'
                },
                matching: { 
                    label: 'Matching', 
                    icon: 'right-left', 
                    color: 'pink',
                    description: 'Pair related items.',
                    guide: 'Create pairs (e.g., Term and Definition). Students will reorder them.'
                },
                short_text: { 
                    label: 'Short Answer', 
                    icon: 'comment', 
                    color: 'cyan',
                    description: 'Brief written response.',
                    guide: 'Specify the question. Optionally provide an AI rubric for grading.'
                },
                essay: { 
                    label: 'Essay', 
                    icon: 'align-left', 
                    color: 'indigo',
                    description: 'Long-form detailed response.',
                    guide: 'Requires manual grading. AI can provide a suggested score via rubric.'
                },
                video_submission: { 
                    label: 'Video Response', 
                    icon: 'film', 
                    color: 'red',
                    description: 'Upload or record video answer.',
                    guide: 'Practical demonstration. Requires manual review by an instructor.'
                }
            },

            formatText(command, value = null) {
                document.execCommand(command, false, value);
                this.question_text = this.$refs.editor.innerHTML;
                this.$refs.editor.focus();
            },

            addOption() { this.options.push({ text: '', is_correct: false }); },
            removeOption(index) { this.options.splice(index, 1); },
            addMatchPair() { this.matchingPairs.push({ left: '', right: '' }); },

            resetForm() {
                this.mode = 'add';
                this.actionUrl = '/instructor/question/add';
                this.type = 'single_choice';
                this.question_text = '';
                if(this.$refs.editor) this.$refs.editor.innerHTML = '';
                this.points = 1;
                this.explanation = '';
                this.tf_correct = 1;
                this.fill_blank_answers = '';
                this.grading_policy = 'auto';
                this.ai_rubric = '';
                this.options = [ { text: '', is_correct: false }, { text: '', is_correct: false } ];
                this.matchingPairs = [ { left: '', right: '' }, { left: '', right: '' } ];
                this.isFocused = false;
            },

            openAddModal() {
                this.resetForm();
                this.showQuestionModal = true;
            },

            editQuestion(q) {
                this.resetForm();
                this.mode = 'edit';
                this.actionUrl = '/instructor/question/update/' + q.id;
                this.type = q.type;
                this.question_text = q.question_text; // Logic here might need to strip html if we were using textarea, but now we assume html
                
                // Decode entities for the editor if necessary
                // In a real app, you might check if it contains HTML tags or is plain text
                
                this.points = q.points;
                this.explanation = q.explanation || '';
                this.grading_policy = q.grading_policy || 'auto';
                this.ai_rubric = q.ai_rubric || '';

                if (['single_choice', 'multi_choice'].includes(q.type)) {
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

                this.showQuestionModal = true;
                
                // Initialize editor content
                this.$nextTick(() => {
                    if(this.$refs.editor) {
                        this.$refs.editor.innerHTML = this.question_text;
                    }
                });
            },

            cancelEdit() {
                this.showQuestionModal = false;
                this.resetForm();
            }
        }
    }
</script>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/../layouts/app.php'; 
?>