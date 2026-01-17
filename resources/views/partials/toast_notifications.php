<!-- Toast Notification Container -->
<div x-data="toastNotificationSystem()" 
     class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"
     @keydown.window.escape="clearAllToasts()">
    
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.visible" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-2 opacity-0 scale-95"
             x-transition:enter-end="translate-y-0 opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0 opacity-100 scale-100"
             x-transition:leave-end="translate-y-2 opacity-0 scale-95"
             class="pointer-events-auto w-80 bg-zinc-900 border border-zinc-800 rounded-xl shadow-2xl overflow-hidden flex flex-col relative group">
            
            <!-- Progress Bar -->
            <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-[5000ms] ease-linear w-full"
                 :style="'width: ' + (toast.visible ? '0%' : '100%')"></div>

            <div class="p-4 flex items-start gap-3">
                <div class="flex-shrink-0 mt-0.5">
                    <div class="w-8 h-8 rounded-lg bg-zinc-800 flex items-center justify-center text-indigo-400">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-bold text-white leading-tight" x-text="toast.title"></h4>
                    <p class="text-xs text-zinc-400 mt-1 leading-snug line-clamp-2" x-text="toast.message"></p>
                </div>
                <button @click="removeToast(toast.id)" class="text-zinc-500 hover:text-white transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <a :href="toast.link" @click="markRead(toast.id)" x-show="toast.link" class="block bg-zinc-950/50 px-4 py-2 text-xs font-bold text-indigo-400 hover:text-indigo-300 hover:bg-zinc-950 transition text-center border-t border-zinc-800">
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
                // Initialize seenIds from initial fetch if needed, 
                // but strictly we only want *new* ones to toast.
                // So first fetch populates state, subsequent fetches check for diff.
                this.fetchNotifications(true);
                
                // Poll every 30 seconds (faster than 60s for responsiveness)
                setInterval(() => this.fetchNotifications(false), 30000);
            },
            fetchNotifications(isInitial = false) {
                fetch('/api/notifications')
                    .then(res => res.json())
                    .then(data => {
                        this.notifications = data.notifications;
                        this.unreadCount = data.unread_count;

                        // Update global/header unread count if it exists elsewhere
                        // Dispatch event for other components (like header bell)
                        window.dispatchEvent(new CustomEvent('notifications-updated', { 
                            detail: { count: this.unreadCount, list: this.notifications } 
                        }));

                        // Process for toasts
                        if (!isInitial) {
                            data.notifications.forEach(n => {
                                if (n.is_read == 0 && !this.seenIds.has(n.id)) {
                                    this.addToast(n);
                                }
                            });
                        }

                        // Update seen set
                        data.notifications.forEach(n => this.seenIds.add(n.id));
                    });
            },
            addToast(notification) {
                const id = Date.now() + Math.random();
                const toast = { ...notification, id: id, visible: true };
                this.toasts.push(toast);
                
                // Auto dismiss
                setTimeout(() => {
                    this.removeToast(id);
                }, 5000);
            },
            removeToast(id) {
                const index = this.toasts.findIndex(t => t.id === id);
                if (index > -1) {
                    this.toasts[index].visible = false;
                    // Remove from DOM after animation
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 300);
                }
            },
            clearAllToasts() {
                this.toasts = [];
            },
            markRead(id) {
                fetch(`/api/notifications/read/${id}`, { method: 'POST' });
                // Optimistic UI update
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                window.dispatchEvent(new CustomEvent('notifications-updated', { 
                    detail: { count: this.unreadCount, list: this.notifications } 
                }));
            }
        }
    }
</script>