<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to ScratchLMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Rubik', 'sans-serif'] },
                    colors: {
                        background: '#09090b',
                        surface: '#18181b',
                        primary: '#6366f1',
                    }
                }
            }
        }
    </script>
    <meta name="csrf-token" content="<?= $csrf_token ?? '' ?>">
    <script>
        window.CSRF_TOKEN = <?= json_encode($csrf_token ?? '') ?>;
        (function() {
            const originalFetch = window.fetch;
            if (!originalFetch) return;
            window.fetch = function(input, init) {
                const opts = init || {};
                const method = (opts.method || 'GET').toUpperCase();
                if (method !== 'GET' && method !== 'HEAD') {
                    if (!opts.headers) {
                        opts.headers = {};
                    }
                    if (opts.headers instanceof Headers) {
                        if (!opts.headers.has('X-CSRF-TOKEN')) {
                            opts.headers.set('X-CSRF-TOKEN', window.CSRF_TOKEN || '');
                        }
                    } else if (!('X-CSRF-TOKEN' in opts.headers)) {
                        opts.headers['X-CSRF-TOKEN'] = window.CSRF_TOKEN || '';
                    }
                }
                return originalFetch(input, opts);
            };
        })();
    </script>
    <style>
        body { background-color: #050505; color: #e4e4e7; font-family: 'Rubik', sans-serif; }
        .fade-enter-active, .fade-leave-active { transition: opacity 0.5s ease, transform 0.5s ease; }
        .fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(10px); }
        [x-cloak] { display: none !important; }
        
        @keyframes float {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(20px, -30px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        
        .blob {
            animation: float 10s infinite ease-in-out;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-[#050505] relative overflow-hidden text-zinc-200">

    <!-- Animated Background Blobs -->
    <div class="absolute inset-0 w-full h-full overflow-hidden z-0 pointer-events-none">
        <div class="blob absolute -top-[20%] -left-[10%] w-[70%] h-[70%] bg-indigo-500/30 rounded-full blur-[120px] mix-blend-screen"></div>
        <div class="blob absolute -bottom-[20%] -right-[10%] w-[70%] h-[70%] bg-violet-500/30 rounded-full blur-[120px] mix-blend-screen" style="animation-delay: -5s"></div>
        <div class="blob absolute top-[20%] right-[10%] w-[50%] h-[50%] bg-blue-500/20 rounded-full blur-[100px] mix-blend-screen" style="animation-delay: -10s"></div>
    </div>

    <div x-data="onboarding()" class="w-full max-w-2xl bg-[#0a0a0a]/90 backdrop-blur-3xl border border-white/10 rounded-[2.5rem] shadow-2xl overflow-hidden relative z-10 min-h-[650px] flex flex-col">
        
        <!-- Progress Bar -->
        <div class="h-1 bg-zinc-900 w-full">
            <div class="h-full bg-gradient-to-r from-indigo-500 to-violet-600 transition-all duration-700 ease-out" :style="'width: ' + ((step / 7) * 100) + '%'"></div>
        </div>

        <!-- Logo Area -->
        <div class="pt-10 px-10 flex justify-center">
            <div class="flex items-center space-x-2 opacity-80">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-base shadow-lg shadow-indigo-500/20">S</div>
                <span class="text-lg font-bold tracking-tight text-white">Scratch<span class="text-indigo-400">LMS</span></span>
            </div>
        </div>

        <!-- Content Area -->
        <div class="flex-1 p-10 relative z-10 overflow-hidden">
            <div class="relative min-h-[420px] flex flex-col justify-center">
            
            <!-- Step 1: Intro -->
            <div x-show="step === 1" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="absolute inset-0 text-center space-y-6 flex flex-col justify-center">
                <div class="w-24 h-24 bg-white/5 rounded-3xl mx-auto flex items-center justify-center text-indigo-400 text-5xl mb-4 border border-white/5 shadow-2xl">
                    <i class="fa-solid fa-rocket"></i>
                </div>
                <h1 class="text-4xl font-extrabold text-white tracking-tight">Your journey starts here.</h1>
                <p class="text-lg text-zinc-400 max-w-md mx-auto leading-relaxed">Let's craft a personalized learning experience tailored specifically to your goals and interests.</p>
                <button @click="next()" class="px-10 py-4 bg-white text-black rounded-full font-bold text-lg hover:bg-indigo-50 transition transform hover:-translate-y-1 shadow-xl mt-8">
                    Personalize Account &rarr;
                </button>
            </div>

            <!-- Step 2: Preferred Name -->
            <div x-show="step === 2" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="absolute inset-0 space-y-6 flex flex-col justify-center">
                <div class="flex items-center space-x-4 mb-8">
                    <div class="w-12 h-12 bg-amber-500/10 rounded-xl flex items-center justify-center text-amber-400 border border-amber-500/20">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">First things first.</h2>
                        <p class="text-zinc-400 text-sm">What should we call you?</p>
                    </div>
                </div>
                <input type="text" x-model="form.preferred_name" @input="clearError('preferred_name')" 
                       :class="errors.preferred_name ? 'border-red-500 focus:border-red-400' : ''"
                       class="w-full bg-zinc-900 border border-zinc-700 text-white text-xl rounded-xl px-6 py-4 focus:outline-none focus:border-indigo-500 transition placeholder-zinc-600" placeholder="e.g. Alex">
                <div x-show="errors.preferred_name" x-transition class="flex items-center gap-2 mt-2 text-red-400">
                    <i class="fa-solid fa-circle-exclamation text-xs"></i>
                    <p class="text-xs font-medium" x-text="errors.preferred_name"></p>
                </div>
            </div>

            <!-- Step 3: Gender & DOB -->
            <div x-show="step === 3" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="absolute inset-0 space-y-8 flex flex-col justify-center">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="w-12 h-12 bg-pink-500/10 rounded-xl flex items-center justify-center text-pink-400 border border-pink-500/20">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Tell us a bit about yourself.</h2>
                        <p class="text-zinc-400 text-sm">This helps us tailor content for you.</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <button @click="form.gender = 'male'; clearError('gender')" :class="form.gender === 'male' ? 'bg-indigo-600 border-indigo-400 text-white shadow-[0_0_20px_rgba(99,102,241,0.3)]' : 'bg-zinc-900 border-zinc-700 text-zinc-400 hover:border-zinc-500'" class="p-4 rounded-xl border flex flex-col items-center gap-2 hover:bg-zinc-800 transition">
                        <i class="fa-solid fa-mars text-2xl"></i>
                        <span>Male</span>
                    </button>
                    <button @click="form.gender = 'female'; clearError('gender')" :class="form.gender === 'female' ? 'bg-indigo-600 border-indigo-400 text-white shadow-[0_0_20px_rgba(99,102,241,0.3)]' : 'bg-zinc-900 border-zinc-700 text-zinc-400 hover:border-zinc-500'" class="p-4 rounded-xl border flex flex-col items-center gap-2 hover:bg-zinc-800 transition">
                        <i class="fa-solid fa-venus text-2xl"></i>
                        <span>Female</span>
                    </button>
                </div>
                <div x-show="errors.gender" x-transition class="flex items-center gap-2 mt-2 text-red-400">
                    <i class="fa-solid fa-circle-exclamation text-xs"></i>
                    <p class="text-xs font-medium" x-text="errors.gender"></p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Date of Birth</label>
                    <div class="grid grid-cols-3 gap-3">
                        <input type="number" min="1" max="31" placeholder="Day" x-model="form.dob_day" @input="clearError('dob')"
                               :class="errors.dob ? 'border-red-500 focus:border-red-400' : ''"
                               class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-500 transition placeholder-zinc-600">
                        <select x-model="form.dob_month" @change="clearError('dob')"
                                :class="errors.dob ? 'border-red-500 focus:border-red-400' : ''"
                                class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-500 transition">
                            <option value="" disabled>Month</option>
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                        <input type="number" min="1900" max="2100" placeholder="Year" x-model="form.dob_year" @input="clearError('dob')"
                               :class="errors.dob ? 'border-red-500 focus:border-red-400' : ''"
                               class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-500 transition placeholder-zinc-600">
                    </div>
                    <p class="text-xs text-zinc-500 mt-2">We use your birth date to personalize your learning journey.</p>
                    <div x-show="errors.dob" x-transition class="flex items-center gap-2 mt-2 text-red-400">
                        <i class="fa-solid fa-circle-exclamation text-xs"></i>
                        <p class="text-xs font-medium" x-text="errors.dob"></p>
                    </div>
                </div>
            </div>

            <!-- Step 4: Location -->
            <div x-show="step === 4" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="absolute inset-0 space-y-6 flex flex-col justify-center">
                <div class="flex items-center space-x-4 mb-8">
                    <div class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-400 border border-blue-500/20">
                        <i class="fa-solid fa-earth-americas"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Where are you based?</h2>
                        <p class="text-zinc-400 text-sm">We'll optimize servers near you.</p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Country</label>
                    <select x-model="form.country" @change="form.country_code = countryCodes[form.country] || ''; clearError('country')"
                            :class="errors.country ? 'border-red-500 focus:border-red-400' : ''"
                            class="w-full bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-500 transition mb-4">
                        <option value="" disabled>Select your country</option>
                        <template x-for="(code, name) in countryCodes" :key="name">
                            <option :value="name" x-text="name"></option>
                        </template>
                    </select>
                    <div x-show="errors.country" x-transition class="flex items-center gap-2 mt-2 text-red-400">
                        <i class="fa-solid fa-circle-exclamation text-xs"></i>
                        <p class="text-xs font-medium" x-text="errors.country"></p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Phone Number (Optional)</label>
                    <div class="grid grid-cols-3 gap-3">
                        <select x-model="form.country_code" @change="clearError('phone')"
                                :class="errors.phone ? 'border-red-500 focus:border-red-400' : ''"
                                class="bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-500 transition">
                            <option value="" disabled>Code</option>
                            <template x-for="(code, name) in countryCodes" :key="name">
                                <option :value="code" x-text="code + ' ' + name"></option>
                            </template>
                        </select>
                        <input type="tel" x-model="form.phone" @input="clearError('phone')"
                               :class="errors.phone ? 'border-red-500 focus:border-red-400' : ''"
                               class="col-span-2 bg-zinc-900 border border-zinc-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-500 transition placeholder-zinc-600"
                               placeholder="Phone number">
                    </div>
                    <div x-show="errors.phone" x-transition class="flex items-center gap-2 mt-2 text-red-400">
                        <i class="fa-solid fa-circle-exclamation text-xs"></i>
                        <p class="text-xs font-medium" x-text="errors.phone"></p>
                    </div>
                </div>
            </div>

            <!-- Step 5: Field of Work -->
            <div x-show="step === 5" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="absolute inset-0 space-y-6 flex flex-col justify-start pt-2">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="w-12 h-12 bg-purple-500/10 rounded-xl flex items-center justify-center text-purple-400 border border-purple-500/20">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Your Field?</h2>
                        <p class="text-zinc-400 text-sm">Select your industry.</p>
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto pr-2 grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[340px]">
                    <template x-for="field in fields" :key="field">
                        <button @click="form.field_of_work = field" 
                                :class="form.field_of_work === field ? 'bg-indigo-600 text-white border-indigo-400 shadow-lg' : 'bg-zinc-900 text-zinc-300 border-zinc-700 hover:border-zinc-500'"
                                class="px-4 py-3 rounded-xl text-sm font-medium border text-left transition leading-snug">
                            <span x-text="field"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Step 6: Usage Intent -->
            <div x-show="step === 6" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="absolute inset-0 space-y-6 flex flex-col justify-center">
                <div class="flex items-center space-x-4 mb-6">
                    <div class="w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center text-emerald-400 border border-emerald-500/20">
                        <i class="fa-solid fa-bullseye"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Why ScratchLMS?</h2>
                        <p class="text-zinc-400 text-sm">Help us understand your goal.</p>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <template x-for="intent in intents" :key="intent.value">
                        <button @click="form.usage_intent = intent.value"
                                :class="form.usage_intent === intent.value ? 'bg-indigo-600/20 border-indigo-500 ring-1 ring-indigo-500' : 'bg-zinc-900 border-zinc-700 hover:bg-zinc-800'"
                                class="w-full p-4 rounded-xl border flex items-center text-left transition group">
                            <div class="w-10 h-10 rounded-full bg-zinc-950 flex items-center justify-center mr-4 text-zinc-500 group-hover:text-white transition">
                                <i :class="intent.icon"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-bold text-sm" x-text="intent.label"></h4>
                            </div>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Step 7: Success -->
            <div x-show="step === 7" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="absolute inset-0 text-center flex flex-col items-center justify-center">
                <div class="mb-8 relative">
                    <div class="w-24 h-24 bg-green-500 rounded-full flex items-center justify-center text-white text-4xl shadow-[0_0_50px_#22c55e] animate-bounce">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div class="absolute top-0 left-0 w-full h-full animate-ping opacity-75 rounded-full border-2 border-green-400"></div>
                </div>
                <h2 class="text-4xl font-bold text-white mb-4">You're All Set!</h2>
                <p class="text-zinc-400 text-lg mb-8">Thank you for updating your profile. Redirecting you home...</p>
            </div>

            <!-- Footer Navigation -->
            </div>
            <div class="mt-8 flex justify-between items-center" x-show="step > 1 && step < 7" x-cloak>
                <button @click="step--" class="text-zinc-500 hover:text-white px-4 py-2 text-sm font-medium transition">Back</button>
                <button @click="next()" 
                        :disabled="!canProceed()"
                        :class="!canProceed() ? 'opacity-50 cursor-not-allowed' : 'hover:-translate-y-0.5 shadow-lg'"
                        class="bg-white text-black px-8 py-3 rounded-full font-bold text-sm transition">
                    Next Step &rarr;
                </button>
            </div>

        </div>
    </div>

    <script>
        function onboarding() {
            return {
                step: 1,
                form: {
                    preferred_name: '',
                    gender: '',
                    dob: '',
                    dob_day: '',
                    dob_month: '',
                    dob_year: '',
                    country: '',
                    country_code: '',
                    phone: '',
                    field_of_work: '',
                    usage_intent: ''
                },
                errors: {
                    preferred_name: '',
                    gender: '',
                    dob: '',
                    country: '',
                    phone: ''
                },
                countryCodes: {
                    'Egypt': '+20',
                    'United States': '+1',
                    'United Kingdom': '+44',
                    'Canada': '+1',
                    'Germany': '+49',
                    'France': '+33',
                    'Spain': '+34',
                    'Italy': '+39',
                    'Netherlands': '+31',
                    'Turkey': '+90',
                    'Saudi Arabia': '+966',
                    'United Arab Emirates': '+971',
                    'Jordan': '+962',
                    'Morocco': '+212',
                    'Algeria': '+213',
                    'Tunisia': '+216',
                    'Qatar': '+974',
                    'Kuwait': '+965',
                    'Bahrain': '+973',
                    'Oman': '+968',
                    'Iraq': '+964',
                    'Pakistan': '+92',
                    'India': '+91',
                    'Bangladesh': '+880',
                    'Nigeria': '+234',
                    'Kenya': '+254',
                    'South Africa': '+27',
                    'Australia': '+61'
                },
                fields: [
                    "Software Development", "Data Science", "Design & Creative", "Marketing", "Business Administration",
                    "Finance & Accounting", "Healthcare", "Education", "Engineering", "Law",
                    "Sales", "Human Resources", "Project Management", "Product Management", "Cybersecurity",
                    "Artificial Intelligence", "Cloud Computing", "Architecture", "Writing & Content", "Video Production",
                    "Music & Audio", "Photography", "Psychology", "Social Sciences", "Natural Sciences",
                    "Mathematics", "Languages", "Hospitality", "Real Estate", "Logistics", "Other"
                ],
                intents: [
                    { value: 'personal', label: 'For personal use & learning', icon: 'fa-solid fa-user' },
                    { value: 'organization', label: 'For the organization I work for', icon: 'fa-solid fa-building' },
                    { value: 'company', label: 'For my own company', icon: 'fa-solid fa-rocket' },
                    { value: 'collaboration', label: 'To collaborate with other users', icon: 'fa-solid fa-users' }
                ],
                clearError(field) {
                    if (this.errors[field]) this.errors[field] = '';
                },
                validateStep(step, setErrors = false) {
                    const nextErrors = {
                        preferred_name: '',
                        gender: '',
                        dob: '',
                        country: '',
                        phone: ''
                    };
                    let valid = true;

                    if (step === 2) {
                        if (!this.form.preferred_name.trim()) {
                            nextErrors.preferred_name = 'Please enter your preferred name.';
                            valid = false;
                        }
                    }

                    if (step === 3) {
                        if (!this.form.gender) {
                            nextErrors.gender = 'Please select a gender.';
                            valid = false;
                        }
                        const day = parseInt(this.form.dob_day, 10);
                        const month = parseInt(this.form.dob_month, 10);
                        const year = parseInt(this.form.dob_year, 10);
                        const now = new Date();
                        if (!day || !month || !year) {
                            nextErrors.dob = 'Please enter a valid date of birth.';
                            valid = false;
                        } else {
                            const dob = new Date(year, month - 1, day);
                            const matches = dob.getFullYear() === year && (dob.getMonth() + 1) === month && dob.getDate() === day;
                            if (!matches || year < 1900 || year > now.getFullYear()) {
                                nextErrors.dob = 'Please enter a valid date of birth.';
                                valid = false;
                            } else if (dob > now) {
                                nextErrors.dob = 'Date of birth cannot be in the future.';
                                valid = false;
                            }
                        }
                    }

                    if (step === 4) {
                        if (!this.form.country) {
                            nextErrors.country = 'Please select your country.';
                            valid = false;
                        }
                        if (this.form.phone && this.form.phone.trim()) {
                            const digits = this.form.phone.replace(/\\D/g, '');
                            if (!this.form.country_code) {
                                nextErrors.phone = 'Please select a country code.';
                                valid = false;
                            } else if (digits.length < 7) {
                                nextErrors.phone = 'Please enter a valid phone number.';
                                valid = false;
                            }
                        }
                    }

                    if (setErrors) {
                        this.errors = nextErrors;
                    }
                    return valid;
                },
                canProceed() {
                    if (this.step === 5) return this.form.field_of_work.length > 0;
                    if (this.step === 6) return this.form.usage_intent.length > 0;
                    return this.validateStep(this.step);
                },
                formatDob() {
                    if (!this.form.dob_day || !this.form.dob_month || !this.form.dob_year) return '';
                    const day = String(this.form.dob_day).padStart(2, '0');
                    const month = String(this.form.dob_month).padStart(2, '0');
                    return `${this.form.dob_year}-${month}-${day}`;
                },
                next() {
                    if (!this.validateStep(this.step, true)) return;
                    if (this.step < 6) {
                        this.step++;
                    } else if (this.step === 6) {
                        this.submit();
                    }
                },
                submit() {
                    this.step = 7;
                    const payload = {
                        ...this.form,
                        dob: this.formatDob()
                    };
                    fetch('/onboarding/save', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': window.CSRF_TOKEN
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Network response was not ok');
                        return res.json();
                    })
                    .then(data => {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 2000);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Optional: Handle error UI here (e.g. go back a step)
                    });
                }
            }
        }
    </script>
</body>
</html>
