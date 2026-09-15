/* =====================================================================
   ISU IAT Tracer Study - Frontend utilities
   Vanilla JS + Fetch API
   ===================================================================== */

(function () {
    'use strict';

    // ------------------------------------------------------------------
    // CSRF helper
    // ------------------------------------------------------------------
    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    async function apiFetch(url, options = {}) {
        options.headers = options.headers || {};
        if (options.method && options.method !== 'GET') {
            options.headers['X-CSRF-TOKEN'] = csrfToken();
        }
        if (options.body && !(options.body instanceof FormData) && typeof options.body === 'object') {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }
        const res = await fetch(url, {
            credentials: 'same-origin',
            ...options,
        });
        const contentType = res.headers.get('content-type') || '';
        const data = contentType.includes('application/json') ? await res.json() : await res.text();
        if (!res.ok) {
            throw new Error(data?.message || 'Request failed');
        }
        return data;
    }

    // ------------------------------------------------------------------
    // Toast notifications
    // ------------------------------------------------------------------
    window.toast = function (message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const icons = { success: 'check-circle', error: 'alert-circle', warning: 'alert-triangle', info: 'info' };
        const colors = {
            success: 'bg-emerald-600',
            error: 'bg-red-600',
            warning: 'bg-amber-500',
            info: 'bg-sky-600',
        };

        const toast = document.createElement('div');
        toast.className = `flex items-start gap-3 rounded-xl ${colors[type] || colors.info} text-white px-4 py-3 shadow-lg text-sm animate-[slideIn_.25s_ease]`;
        toast.style.animation = 'none';
        toast.innerHTML = `
            <i data-lucide="${icons[type] || icons.info}" class="w-5 h-5 shrink-0"></i>
            <div class="flex-1">${escapeHtml(message)}</div>
            <button type="button" class="text-white/70 hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
        `;
        toast.querySelector('button').addEventListener('click', () => toast.remove());
        if (window.lucide) lucide.createIcons({ attrs: { class: [''] } });
        container.appendChild(toast);
        if (window.lucide) lucide.createIcons({});
        setTimeout(() => {
            toast.style.transition = 'opacity .3s, transform .3s';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-8px)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    };

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = String(str ?? '');
        return div.innerHTML;
    }
    window.escapeHtml = escapeHtml;

    // ------------------------------------------------------------------
    // Clipboard helper (async Clipboard API with legacy fallback)
    // ------------------------------------------------------------------
    window.copyText = function (text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            const ta = document.createElement('textarea');
            ta.value = String(text);
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            ta.style.top = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            try {
                document.execCommand('copy');
                document.body.removeChild(ta);
                resolve();
            } catch (err) {
                document.body.removeChild(ta);
                reject(err);
            }
        });
    };

    // ------------------------------------------------------------------
    // Confirmation dialog (native confirm with optional styling)
    // ------------------------------------------------------------------
    window.confirmAction = function (message, form) {
        if (window.confirm(message || 'Are you sure?')) {
            form.submit();
        }
    };

    // ------------------------------------------------------------------
    // Sidebar toggle (mobile)
    // ------------------------------------------------------------------
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const toggleBtn = document.getElementById('sidebar-toggle');

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('translate-x-0');
        if (overlay) overlay.classList.add('hidden');
    }
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const open = sidebar.classList.toggle('translate-x-0');
            if (overlay) overlay.classList.toggle('hidden', !open);
        });
    }
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // ------------------------------------------------------------------
    // User manual slide-over
    // ------------------------------------------------------------------
    const manualOpen = document.getElementById('manual-open');
    const manualClose = document.getElementById('manual-close');
    const manualSlider = document.getElementById('manual-slider');
    const manualBackdrop = document.getElementById('manual-backdrop');
    let manualBackdropTimer = null;

    function openManual() {
        if (!manualSlider || !manualBackdrop || !manualOpen) return;
        if (manualBackdropTimer) window.clearTimeout(manualBackdropTimer);
        manualBackdrop.classList.remove('hidden');
        manualSlider.classList.remove('hidden');
        manualSlider.classList.add('flex');
        window.requestAnimationFrame(() => {
            manualSlider.style.transform = 'translateX(0)';
        });
        manualSlider.setAttribute('aria-hidden', 'false');
        manualOpen.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
        if (manualClose) manualClose.focus();
    }

    function closeManual(instant = false) {
        if (!manualSlider || !manualBackdrop || !manualOpen) return;
        if (manualBackdropTimer) window.clearTimeout(manualBackdropTimer);
        manualSlider.style.transform = 'translateX(100%)';
        manualSlider.setAttribute('aria-hidden', 'true');
        manualOpen.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        if (instant) {
            manualSlider.classList.add('hidden');
            manualSlider.classList.remove('flex');
            manualBackdrop.classList.add('hidden');
            return;
        }
        manualBackdropTimer = window.setTimeout(() => {
            manualSlider.classList.add('hidden');
            manualSlider.classList.remove('flex');
            manualBackdrop.classList.add('hidden');
        }, 200);
    }

    closeManual(true);
    if (manualOpen) manualOpen.addEventListener('click', openManual);
    if (manualClose) manualClose.addEventListener('click', closeManual);
    if (manualBackdrop) manualBackdrop.addEventListener('click', closeManual);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && manualSlider && manualSlider.getAttribute('aria-hidden') === 'false') {
            closeManual();
        }
    });

    // ------------------------------------------------------------------
    // Alert dismiss
    // ------------------------------------------------------------------
    document.querySelectorAll('.alert-dismiss').forEach((btn) => {
        btn.addEventListener('click', () => {
            const box = btn.closest('.alert-box');
            if (box) {
                box.style.transition = 'opacity .3s';
                box.style.opacity = '0';
                setTimeout(() => box.remove(), 300);
            }
        });
    });

    // ------------------------------------------------------------------
    // Password visibility toggle
    // ------------------------------------------------------------------
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.querySelector(btn.dataset.togglePassword);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.innerHTML = `<i data-lucide="${show ? 'eye-off' : 'eye'}" class="w-4 h-4"></i>`;
            if (window.lucide) lucide.createIcons({});
        });
    });

    // ------------------------------------------------------------------
    // Notification Center
    // ------------------------------------------------------------------
    window.initNotificationCenter = function (opts) {
        const bell = document.getElementById('notification-bell');
        const panel = document.getElementById('notification-panel');
        const list = document.getElementById('notification-list');
        const countEl = document.getElementById('notification-count');
        const markAllBtn = document.getElementById('mark-all-read');
        if (!bell || !panel || !list) return;

        let open = false;

        function loadNotifications() {
            apiFetch(`${opts.base}`)
                .then((data) => {
                    if (!data.success) return;
                    const unread = data.unread_count || 0;
                    countEl.textContent = unread;
                    countEl.classList.toggle('hidden', unread === 0);
                    renderList(data.notifications || []);
                })
                .catch(() => {});
        }

        function renderList(items) {
            if (!items.length) {
                list.innerHTML = '<div class="px-4 py-10 text-center"><p class="text-sm text-ink-500">You have no notifications.</p></div>';
                return;
            }
            list.innerHTML = items.map((n) => `
                <a href="${n.link ? opts.base.replace(/api\/notifications$/, '') + n.link : '#'}" data-id="${n.id}" data-read-link="${n.link ? '1' : '0'}"
                   class="notif-item block px-4 py-3 hover:bg-ink-50 ${n.is_read ? '' : 'bg-brand-50/50'}">
                    <div class="flex items-start gap-2">
                        <span class="mt-1 w-2 h-2 rounded-full shrink-0 ${n.is_read ? 'bg-ink-300' : 'bg-brand-600'}"></span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-ink-800 truncate">${escapeHtml(n.title)}</p>
                            <p class="text-xs text-ink-500 line-clamp-2 mt-0.5">${escapeHtml(n.message)}</p>
                            <p class="text-[11px] text-ink-400 mt-1">${escapeHtml(n.created_at || '')}</p>
                        </div>
                    </div>
                </a>
            `).join('');

            list.querySelectorAll('.notif-item').forEach((item) => {
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    const id = this.dataset.id;
                    if (!this.dataset.readLink) {
                        apiFetch(`${opts.base}/${id}/read`, { method: 'POST' }).then(loadNotifications).catch(() => {});
                    } else {
                        apiFetch(`${opts.base}/${id}/read`, { method: 'POST' })
                            .then(() => { window.location = this.getAttribute('href'); })
                            .catch(() => {});
                    }
                });
            });
        }

        bell.addEventListener('click', (e) => {
            e.stopPropagation();
            open = !open;
            panel.classList.toggle('hidden', !open);
            if (open) loadNotifications();
        });

        document.addEventListener('click', (e) => {
            if (open && !panel.contains(e.target)) {
                open = false;
                panel.classList.add('hidden');
            }
        });

        if (markAllBtn) {
            markAllBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                apiFetch(`${opts.base}/read-all`, { method: 'POST' })
                    .then(() => { loadNotifications(); window.toast('All notifications marked as read.'); })
                    .catch(() => {});
            });
        }

        // Initial unread count.
        apiFetch(`${opts.base}?count=1`).then((data) => {
            if (data.success) {
                countEl.textContent = data.unread_count || 0;
                countEl.classList.toggle('hidden', (data.unread_count || 0) === 0);
            }
        }).catch(() => {});
    };

    // ------------------------------------------------------------------
    // Lucide icons
    // ------------------------------------------------------------------
    if (window.lucide) {
        document.addEventListener('DOMContentLoaded', () => lucide.createIcons({}));
    }

    window.apiFetch = apiFetch;
})();
