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
             class="pointer-events-auto w-72 bg-zinc-950 border border-indigo-500/30 rounded-xl shadow-2xl shadow-indigo-500/10 overflow-hidden flex flex-col relative group ring-1 ring-white/5 backdrop-blur-md">
            
            <!-- Progress Bar (Draining) -->
            <div class="absolute bottom-0 left-0 h-0.5 bg-gradient-to-r from-indigo-500 via-violet-500 to-indigo-500 transition-all ease-linear"
                 :style="'width: ' + toast.progress + '%; transition-duration: 10000ms;'"></div>

            <div class="p-4 flex items-start gap-3">
                <div class="flex-shrink-0 mt-0.5">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                        <i class="fa-solid fa-bell text-xs"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-bold text-white tracking-tight leading-tight mb-1" x-text="toast.title"></h4>
                    <p class="text-[11px] text-zinc-400 leading-snug line-clamp-3" x-text="toast.message"></p>
                </div>
                <button @click="removeToast(toast.tempId)" class="text-zinc-500 hover:text-white transition-colors -mt-1 -mr-1 p-1">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>
            
            <a :href="toast.link" @click="markReadAndRedirect(toast.id, toast.link)" x-show="toast.link" 
               class="block bg-white/5 px-4 py-2.5 text-[10px] font-bold uppercase tracking-widest text-indigo-300 hover:text-white hover:bg-indigo-600 transition-all text-center border-t border-white/5">
                View Details <i class="fa-solid fa-arrow-right ml-1"></i>
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
                
                // Auto dismiss after 10 seconds
                setTimeout(() => {
                    this.removeToast(tempId);
                }, 10000);
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