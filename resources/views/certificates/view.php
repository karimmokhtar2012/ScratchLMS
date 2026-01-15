<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - <?= htmlspecialchars($cert['student_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { background: #1a1a1a; font-family: 'Montserrat', sans-serif; }
        .serif { font-family: 'Cinzel', serif; }
        .cert-border {
            background-image: 
                linear-gradient(45deg, #d4af37 25%, transparent 25%, transparent 75%, #d4af37 75%, #d4af37), 
                linear-gradient(45deg, #d4af37 25%, transparent 25%, transparent 75%, #d4af37 75%, #d4af37);
            background-position: 0 0, 10px 10px;
            background-size: 20px 20px;
            opacity: 0.1;
        }
        @media print {
            body { background: white; -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-8 bg-zinc-950">

    <div class="relative bg-white text-black w-[1100px] h-[750px] p-2 shadow-2xl overflow-hidden mx-auto">
        <!-- Brand Border Frame -->
        <div class="absolute inset-4 border-[12px] border-double border-indigo-600/20 z-10"></div>
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 via-transparent to-violet-500/5 z-0"></div>

        <!-- Content -->
        <div class="relative z-20 h-full flex flex-col items-center justify-center text-center p-16">
            
            <!-- Brand Logo -->
            <div class="mb-10">
                <div class="flex items-center justify-center space-x-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-2xl shadow-lg">S</div>
                    <span class="text-3xl font-black tracking-tighter text-zinc-900">Scratch<span class="text-indigo-600">LMS</span></span>
                </div>
            </div>

            <h1 class="serif text-5xl font-bold text-gray-900 mb-4 tracking-widest uppercase">Certificate of Completion</h1>
            <p class="text-xl text-gray-500 italic mb-12 font-medium">This official credential is proudly presented to</p>

            <h2 class="serif text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600 mb-8 border-b-4 border-indigo-100 pb-4 px-12 inline-block">
                <?= htmlspecialchars($cert['student_name']) ?>
            </h2>

            <p class="text-xl text-gray-600 mb-4">For successfully mastering all requirements of the course</p>
            <h3 class="text-3xl font-bold text-gray-800 mb-12 max-w-3xl leading-tight">
                <?= htmlspecialchars($cert['course_title']) ?>
            </h3>

            <div class="flex justify-between w-full max-w-4xl px-12 mt-12 items-end">
                <div class="text-center">
                    <p class="text-lg font-bold text-gray-800"><?= date('F j, Y', strtotime($cert['certified_at'])) ?></p>
                    <div class="h-0.5 w-48 bg-gray-200 mt-2 mb-2"></div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Date of Achievement</p>
                </div>

                <div class="text-center">
                    <!-- Signature -->
                    <div class="text-3xl text-zinc-900 mb-1" style="font-family: 'Cinzel', serif; font-weight: 700;">Karim Ahmed</div>
                    <div class="h-0.5 w-48 bg-indigo-600 mt-2 mb-2"></div>
                    <p class="text-[10px] text-indigo-400 font-bold uppercase tracking-widest">Lead Instructor</p>
                </div>
            </div>

            <div class="absolute bottom-10 left-16 flex items-center space-x-2 opacity-40 grayscale">
                <div class="w-8 h-8 rounded bg-zinc-200 flex items-center justify-center font-bold text-xs">QR</div>
                <div class="text-[8px] text-left leading-tight font-mono">
                    VERIFICATION ID<br>
                    <?= htmlspecialchars($cert['certificate_id']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Button (Screen Only) -->
    <div class="fixed bottom-8 right-8 no-print">
        <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-4 rounded-full font-bold shadow-xl transition flex items-center group">
            <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Download Official Certificate
        </button>
    </div>

</body>
</html>
