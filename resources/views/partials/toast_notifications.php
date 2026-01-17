<!-- Toast Notification Container -->
<div x-data="toastNotificationSystem()" 
     class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-3 pointer-events-none"
     @keydown.window.escape="clearAllToasts()">
    
    <template x-for="toast in toasts" :key="toast.tempId">
        <div x-show="toast.visible" 
             x-init="$nextTick(() => { toast.progress = 0 })"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="translate-x-12 opacity-0 scale-90"
             x-transition:enter-end="translate-x-0 opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="translate-x-0 opacity-100 scale-100"
             x-transition:leave-end="translate-x-12 opacity-0 scale-90"
             class="pointer-events-auto w-85 bg-zinc-900/95 backdrop-blur-xl border border-zinc-800 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.5)] overflow-hidden flex flex-col relative group ring-1 ring-white/5">
            
            <!-- Progress Bar (Draining) -->
            <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-indigo-500 transition-all ease-linear"
                 :style="'width: ' + toast.progress + '%; transition-duration: 5000ms;'"></div>

            <div class="p-5 flex items-start gap-4">
                <div class="flex-shrink-0 mt-0.5">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shadow-inner">
                        <i class="fa-solid fa-bell-concierge"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-black text-white tracking-tight" x-text="toast.title"></h4>
                    <p class="text-xs text-zinc-400 mt-1.5 leading-relaxed line-clamp-2" x-text="toast.message"></p>
                </div>
                <button @click="removeToast(toast.tempId)" class="text-zinc-600 hover:text-white transition-colors p-1 -mr-1">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
            
            <a :href="toast.link" @click="markReadAndRedirect(toast.id, toast.link)" x-show="toast.link" 
               class="block bg-white/5 px-5 py-3 text-[11px] font-black uppercase tracking-widest text-indigo-400 hover:text-white hover:bg-indigo-600 transition-all text-center border-t border-zinc-800/50">
                Action Required <i class="fa-solid fa-chevron-right ml-2 text-[9px]"></i>
            </a>
        </div>
    </template>
</div>

<script>
    function toastNotificationSystem() {
        return {
            notifications: [],
            unreadCount: 0,
            toasts: [],
            seenIds: new Set(),
            init() {
                // Pre-populate seenIds with existing unread notifications so we don't toast them on page load
                fetch('/api/notifications')
                    .then(res => res.json())
                    .then(data => {
                        const now = new Date();
                        data.notifications.forEach(n => {
                            // Attempt to parse UTC date (SQLite CURRENT_TIMESTAMP is UTC 'YYYY-MM-DD HH:MM:SS')
                            // We replace space with T and add Z to ensure JS treats it as UTC
                            const dateStr = n.created_at.replace(' ', 'T') + 'Z';
                            const created = new Date(dateStr);
                            const age = (now - created) / 1000; // seconds

                            // If it's unread and very fresh (< 10s), show it even on initial load
                            // This handles "Redirect-after-action" scenarios where notification generated 
                            // just before page load.
                            if (n.is_read == 0 && age < 10) {
                                this.addToast(n);
                            }

                            this.seenIds.add(n.id);
                        });
                        this.notifications = data.notifications;
                        this.unreadCount = data.unread_count;
                        
                        // Sync with header
                        window.dispatchEvent(new CustomEvent('notifications-updated', { 
                            detail: { count: this.unreadCount, list: this.notifications } 
                        }));
                    });

                // Poll for NEW notifications
                setInterval(() => this.checkForNewNotifications(), 5000); // Check every 5 seconds for snappier feeling
            },
            checkForNewNotifications() {
                fetch('/api/notifications')
                    .then(res => res.json())
                    .then(data => {
                        let hasNew = false;
                        data.notifications.forEach(n => {
                            if (n.is_read == 0 && !this.seenIds.has(n.id)) {
                                this.addToast(n);
                                this.seenIds.add(n.id);
                                hasNew = true;
                            }
                        });

                        if (hasNew || data.unread_count !== this.unreadCount) {
                            this.notifications = data.notifications;
                            this.unreadCount = data.unread_count;
                            window.dispatchEvent(new CustomEvent('notifications-updated', { 
                                detail: { count: this.unreadCount, list: this.notifications } 
                            }));
                        }
                    });
            },
            addToast(notification) {
                const tempId = Date.now() + Math.random();
                // We use 'progress: 100' initially, then set to 0 in x-init to trigger transition
                const toast = { ...notification, tempId: tempId, visible: true, progress: 100 };
                this.toasts.push(toast);
                
                // Auto dismiss after 5 seconds
                setTimeout(() => {
                    this.removeToast(tempId);
                }, 5000);
            },
            removeToast(tempId) {
                const index = this.toasts.findIndex(t => t.tempId === tempId);
                if (index > -1) {
                    this.toasts[index].visible = false;
                    // Fully remove from array after animation completes
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.tempId !== tempId);
                    }, 500);
                }
            },
            clearAllToasts() {
                this.toasts.forEach(t => t.visible = false);
                setTimeout(() => { this.toasts = []; }, 500);
            },
            markReadAndRedirect(id, link) {
                fetch(`/api/notifications/read/${id}`, { method: 'POST' });
                // We don't need to manually redirect if it's an <a> tag, 
                // but we might want to refresh state before navigation
            }
        }
    }
</script>

<style>
    /* Ensure the toast container doesn't block clicks to elements behind it */
    .pointer-events-none { pointer-events: none; }
    .pointer-events-auto { pointer-events: auto; }
</style>