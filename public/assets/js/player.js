document.addEventListener('DOMContentLoaded', () => {
    const video = document.getElementById('course-video');
    const markCompleteBtn = document.getElementById('mark-complete-btn');
    const youtubeContainer = document.getElementById('youtube-player');

    // 1. Toast Notification Helper
    function showToast(message, type = 'success', nextUrl = null) {
        const toast = document.createElement('div');
        const colorClass = type === 'success' ? 'bg-emerald-600' : 'bg-blue-600';
        toast.className = `fixed bottom-4 inset-x-4 md:inset-x-auto md:bottom-8 md:right-8 ${colorClass} text-white px-6 py-4 rounded-xl shadow-2xl transform transition-all duration-500 translate-y-20 opacity-0 z-50 font-bold flex items-center gap-4 border border-white/10`;

        let html = `<i class="fa-solid fa-circle-check text-xl"></i> <div>${message}</div>`;

        const resolvedNextUrl = nextUrl
            || (typeof NEXT_ITEM_URL !== 'undefined' ? NEXT_ITEM_URL : null)
            || (typeof ITEM_STATE !== 'undefined' && ITEM_STATE && ITEM_STATE.nextItemUrl ? ITEM_STATE.nextItemUrl : null);
        if (resolvedNextUrl) {
            html += `<a href="${resolvedNextUrl}" class="ml-4 bg-white/20 hover:bg-white/30 text-white px-3 py-1.5 rounded-lg text-xs transition border border-white/20">Next Item &rarr;</a>`;
        } else if (typeof NEXT_LESSON_ID !== 'undefined' && NEXT_LESSON_ID) {
            html += `<a href="/learn/${COURSE_ID}/lesson/${NEXT_LESSON_ID}" class="ml-4 bg-white/20 hover:bg-white/30 text-white px-3 py-1.5 rounded-lg text-xs transition border border-white/20">Next Lesson &rarr;</a>`;
        }

        toast.innerHTML = html;
        document.body.appendChild(toast);

        // Animate In
        requestAnimationFrame(() => {
            toast.classList.remove('translate-y-20', 'opacity-0');
        });

        // Remove
        setTimeout(() => {
            toast.classList.add('translate-y-20', 'opacity-0');
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }

    // 2. Sidebar Update Helper
    function markSidebarComplete() {
        const currentPath = window.location.pathname;
        const activeLink = document.querySelector(`a[href="${currentPath}"]`);

        if (activeLink) {
            const iconContainer = activeLink.querySelector('div');
            if (iconContainer) {
                iconContainer.className = "mt-0.5 flex-shrink-0";
                iconContainer.innerHTML = `<div class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-500 flex items-center justify-center text-xs animate-bounce"><i class="fa-solid fa-check"></i></div>`;

                const title = activeLink.querySelector('.font-medium');
                if (title) title.classList.add('line-through', 'decoration-zinc-700', 'text-zinc-500');
            }
        }
    }

    // 3. Tracking Logic
    function trackProgress(verb, meta = {}) {
        fetch('/learn/track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.CSRF_TOKEN || ''
            },
            body: JSON.stringify({
                lesson_id: LESSON_ID,
                item_id: typeof ITEM_ID !== 'undefined' ? ITEM_ID : null,
                verb: verb,
                meta: meta
            })
        })
            .then(response => response.json())
            .then(data => {
                console.log('Tracked:', verb, data);

                if (data.status === 'success' && verb === 'completed') {
                    markSidebarComplete();
                    showToast('Lesson Completed!', 'success', data.next_item_url || null);

                    if (markCompleteBtn) {
                        markCompleteBtn.disabled = true;
                        markCompleteBtn.innerHTML = '<i class="fa-solid fa-check mr-2"></i> Completed';
                        markCompleteBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                } else if (data.error) {
                    console.error('Server Error:', data.error);
                }
            })
            .catch(err => console.error('Tracking Error:', err));
    }

    // 4. Standard Video Events
    if (video) {
        let hasStarted = false;
        let hasHalfway = false;

        video.addEventListener('play', () => {
            if (!hasStarted) {
                trackProgress('started');
                hasStarted = true;
            }
        });

        video.addEventListener('timeupdate', () => {
            if (!hasHalfway && video.duration > 0) {
                const percent = video.currentTime / video.duration;
                if (percent >= 0.5) {
                    trackProgress('watched_50');
                    hasHalfway = true;
                }
            }
        });

        video.addEventListener('ended', () => {
            trackProgress('completed');
        });
    }

    // 5. YouTube IFrame API Logic
    if (youtubeContainer) {
        // Global for Notes access
        window.ytPlayer = null;
        let hasStarted = false;
        let hasHalfway = false;
        const videoId = youtubeContainer.dataset.videoId;

        window.onYouTubeIframeAPIReady = function () {
            window.ytPlayer = new YT.Player('youtube-player', {
                height: '100%',
                width: '100%',
                videoId: videoId,
                playerVars: {
                    'autoplay': 1,
                    'modestbranding': 1,
                    'rel': 0
                },
                events: {
                    'onStateChange': onPlayerStateChange
                }
            });
        };

        // If API is already loaded but not ready
        if (window.YT && window.YT.Player) {
            onYouTubeIframeAPIReady();
        }

        function onPlayerStateChange(event) {
            if (event.data == YT.PlayerState.PLAYING) {
                if (!hasStarted) {
                    trackProgress('started');
                    hasStarted = true;
                }
                startTimer();
            }
            if (event.data == YT.PlayerState.ENDED) {
                trackProgress('completed');
                stopTimer();
            }
        }

        let timer;
        function startTimer() {
            timer = setInterval(() => {
                if (!hasHalfway && window.ytPlayer && typeof window.ytPlayer.getDuration === 'function') {
                    const duration = window.ytPlayer.getDuration();
                    const currentTime = window.ytPlayer.getCurrentTime();
                    if (duration > 0 && (currentTime / duration) >= 0.5) {
                        trackProgress('watched_50');
                        hasHalfway = true;
                    }
                }
            }, 1000);
        }

        function stopTimer() {
            clearInterval(timer);
        }
    }

    // 6. Manual Button Events
    if (markCompleteBtn) {
        markCompleteBtn.addEventListener('click', () => {
            trackProgress('completed');
        });
    }

    // --- Notes Feature Logic ---
    const noteForms = [document.getElementById('note-form'), document.getElementById('mobile-note-form')];
    const noteTimeDisplays = [document.getElementById('current-time-display'), document.getElementById('mobile-current-time-display')];
    const timestampBtns = [document.getElementById('timestamp-btn'), document.getElementById('mobile-timestamp-btn')];
    const notesLists = [document.getElementById('notes-list'), document.getElementById('mobile-notes-list')];

    let currentTimestamp = 0;

    function formatTime(seconds) {
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
        return `${m}:${s.toString().padStart(2, '0')}`;
    }

    function getPlayerTime() {
        // Check standard video
        if (video && !video.paused) {
            return video.currentTime;
        }
        // Check YouTube
        if (window.ytPlayer && typeof window.ytPlayer.getCurrentTime === 'function') {
            return window.ytPlayer.getCurrentTime();
        }
        // Fallback for paused standard video
        if (video) return video.currentTime;

        return 0;
    }

    // Update timestamp display loop
    setInterval(() => {
        currentTimestamp = getPlayerTime();
        const formatted = formatTime(currentTimestamp);
        noteTimeDisplays.forEach(el => { if (el) el.textContent = formatted; });
    }, 1000);

    // Insert Timestamp into text
    timestampBtns.forEach((btn, index) => {
        if (btn) {
            btn.addEventListener('click', () => {
                const timeStr = `[${formatTime(currentTimestamp)}] `;
                // Find corresponding input (desktop -> desktop, mobile -> mobile)
                // We know index 0 is desktop, 1 is mobile based on array definition above.
                // Or simplified: find the relative textarea.
                const form = btn.closest('form');
                const textarea = form.querySelector('textarea');
                if (textarea) {
                    textarea.value += timeStr;
                    textarea.focus();
                }
            });
        }
    });

    // Load Notes
    function fetchNotes() {
        fetch(`/notes/lesson/${LESSON_ID}`)
            .then(res => res.json())
            .then(notes => {
                const html = notes.length === 0 ?
                    `<div class="text-center py-8 text-zinc-600 text-xs">
                        <i class="fa-solid fa-pencil mb-2 block text-lg"></i>
                        No notes yet. Start writing!
                    </div>` :
                    notes.map(note => `
                    <div class="bg-zinc-950 border border-zinc-800 p-3 rounded-lg group animate-fade-in">
                        <div class="flex justify-between items-start mb-1">
                            <span class="text-xs font-bold text-indigo-400 font-mono cursor-pointer hover:underline" onclick="seekTo(${note.timestamp})">${note.timestamp ? formatTime(note.timestamp) : '0:00'}</span>
                            <button onclick="deleteNote(${note.id})" class="text-zinc-600 hover:text-red-400 opacity-0 group-hover:opacity-100 transition"><i class="fa-solid fa-times"></i></button>
                        </div>
                        <p class="text-sm text-zinc-300 whitespace-pre-wrap leading-relaxed">${escapeHtml(note.content)}</p>
                    </div>
                `).join('');

                notesLists.forEach(list => {
                    if (list) list.innerHTML = html;
                });
            });
    }

    // Seek to timestamp helper
    window.seekTo = function (seconds) {
        if (video) {
            video.currentTime = seconds;
            video.play();
        } else if (window.ytPlayer) {
            window.ytPlayer.seekTo(seconds, true);
            window.ytPlayer.playVideo();
        }
    };

    // Save Note
    noteForms.forEach(form => {
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const textarea = form.querySelector('textarea');
                const content = textarea.value.trim();
                if (!content) return;

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerText;
                submitBtn.disabled = true;
                submitBtn.innerText = 'Saving...';

                fetch('/notes/store', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.CSRF_TOKEN
                    },
                    body: JSON.stringify({
                        lesson_id: LESSON_ID,
                        content: content,
                        timestamp: Math.floor(currentTimestamp)
                    })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Clear all forms
                            noteForms.forEach(f => { if (f) f.querySelector('textarea').value = ''; });
                            fetchNotes();
                            showToast('Note saved', 'success');
                        } else {
                            console.error('Save Note Error:', data);
                            showToast(data.message || 'Failed to save note', 'error');
                        }
                    })
                    .catch(err => {
                        console.error('Network Error:', err);
                        showToast('Network error saving note', 'error');
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerText = originalText;
                    });
            });
        }
    });

    // Initial Load
    fetchNotes();

    // Helper for HTML escaping
    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Global delete function
    window.deleteNote = function (id) {
        if (!confirm('Delete note?')) return;
        fetch(`/notes/delete/${id}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest', // Important for Controller detection
                'X-CSRF-TOKEN': window.CSRF_TOKEN || ''
            }
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    fetchNotes();
                    showToast('Note deleted', 'success');
                } else {
                    showToast('Failed to delete note', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error deleting note', 'error');
            });
    };
});
