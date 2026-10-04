/**
 * Notifications and Toast System for ApexSMM
 */

window.ApexToast = (function () {
    let container = null;

    function getContainer() {
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed bottom-5 right-5 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none';
            document.body.appendChild(container);
        }
        return container;
    }

    const icons = {
        success: `<svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
        error: `<svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
        warning: `<svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>`,
        info: `<svg class="w-5 h-5 text-purple-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>`
    };

    return {
        show: function (type = 'info', title = '', message = '', duration = 4000) {
            const cont = getContainer();
            const toast = document.createElement('div');
            toast.className = 'pointer-events-auto bg-white border border-zinc-200/80 rounded-2xl shadow-xl shadow-purple-600/5 p-4 flex items-start gap-3 transition-all';
            
            const iconHtml = icons[type] || icons.info;
            toast.innerHTML = `
                ${iconHtml}
                <div class="flex-1 min-w-0">
                    ${title ? `<h4 class="text-sm font-semibold text-zinc-900 leading-snug">${title}</h4>` : ''}
                    <p class="text-xs text-zinc-600 leading-relaxed mt-0.5">${message}</p>
                </div>
                <button class="text-zinc-400 hover:text-zinc-600 p-1 -mr-1" onclick="this.closest('div').remove()">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            `;

            cont.appendChild(toast);
            if (window.ApexAnimations) {
                ApexAnimations.animateToastIn(toast);
            }

            if (duration > 0) {
                setTimeout(() => {
                    if (window.ApexAnimations) {
                        ApexAnimations.animateToastOut(toast, () => toast.remove());
                    } else {
                        toast.remove();
                    }
                }, duration);
            }
        },
        success: function (msg, title = 'Success') { this.show('success', title, msg); },
        error: function (msg, title = 'Error') { this.show('error', title, msg, 5000); },
        warning: function (msg, title = 'Warning') { this.show('warning', title, msg); },
        info: function (msg, title = 'Information') { this.show('info', title, msg); }
    };
})();

// Notifications Panel AJAX Helpers
window.ApexNotifications = {
    markAsRead: async function (notifId, btn) {
        try {
            const res = await fetch('/ajax/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    action: 'mark_read',
                    id: notifId,
                    csrf_token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                })
            });
            const data = await res.json();
            if (data.success) {
                const item = btn.closest('.notification-item');
                if (item) {
                    item.classList.remove('bg-purple-50/50');
                    btn.remove();
                }
                const badge = document.getElementById('notif-badge');
                if (badge) {
                    let count = parseInt(badge.innerText, 10) - 1;
                    if (count <= 0) {
                        badge.style.display = 'none';
                    } else {
                        badge.innerText = count;
                    }
                }
            }
        } catch (e) {
            console.error('Failed to mark notification as read:', e);
        }
    },

    markAllAsRead: async function () {
        try {
            const res = await fetch('/ajax/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    action: 'mark_all_read',
                    csrf_token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                })
            });
            const data = await res.json();
            if (data.success) {
                document.querySelectorAll('.notification-item').forEach(el => el.classList.remove('bg-purple-50/50'));
                document.querySelectorAll('.mark-read-btn').forEach(btn => btn.remove());
                const badge = document.getElementById('notif-badge');
                if (badge) badge.style.display = 'none';
                ApexToast.success('All notifications marked as read.');
            }
        } catch (e) {
            console.error('Failed to mark all as read:', e);
        }
    }
};
