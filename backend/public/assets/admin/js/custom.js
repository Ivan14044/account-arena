/**
 * ACCOUNT ARENA - Admin Panel Custom Logic
 * Unified Badge & Notification System
 */

(function ($) {
    'use strict';

    // --- CONFIGURATION & STATE ---
    const CONFIG = {
        polling: {
            manual: 5000,    // 5 seconds (was 2s, but 5s is plenty and less server load)
            disputes: 30000, // 30 seconds
            support: 30000,  // 30 seconds
            notifications: 30000, // 30 seconds
            settings: 60000  // 60 seconds
        },
        selectors: {
            manual: '#manual-delivery-count',
            disputes: '#disputes-unread-count',
            support: '#support-chats-unread-count'
        },
        colors: {
            manual: 'badge-warning',
            disputes: 'badge-warning',
            support: 'badge-info'
        },
        routes: {
            manual: '/admin/manual-delivery/count',
            disputes: '/admin/disputes/new-count',
            support: '/admin/support-chats/unread-count',
            settings: '/admin/settings/notification-check',
            notifications: '/admin/admin_notifications/get'
        }
    };

    let state = {
        manual: { count: -1 },
        disputes: { count: -1 },
        support: { count: -1 },
        notifications: { count: -1 },
        settings: {
            manual_delivery_enabled: true,
            sound_enabled: true
        }
    };

    let timeouts = {
        manual: null,
        disputes: null,
        support: null,
        notifications: null
    };

    // --- HELPERS ---

    /**
     * Unified function to update a sidebar badge
     */
    function updateBadge(key, count) {
        const selector = CONFIG.selectors[key];
        const colorClass = CONFIG.colors[key];

        const $li = document.querySelector(selector);
        if (!$li) return;

        // AdminLTE structure: li.nav-item > a.nav-link > p
        const $p = $li.querySelector('a.nav-link p');
        if (!$p) return;

        let $badge = $li.querySelector('.badge');

        if (count > 0) {
            if (!$badge) {
                $badge = document.createElement('span');
                $badge.className = `badge ${colorClass} right`;
                $p.appendChild($badge);
            }

            const displayCount = count > 99 ? '99+' : count;
            if ($badge.innerText !== String(displayCount)) {
                $badge.innerText = displayCount;
            }
            $badge.style.display = 'inline-block';
        } else if ($badge) {
            $badge.style.display = 'none';
        }

        // Handle sound if count increased
        if (state[key].count !== -1 && count > state[key].count) {
            let shouldPlay = state.settings.sound_enabled;

            // Special check for manual delivery setting
            if (key === 'manual' && !state.settings.manual_delivery_enabled) {
                shouldPlay = false;
            }

            if (shouldPlay) {
                playNotificationSound();
            }
        }

        state[key].count = count;
    }

    /**
     * Play notification sound
     */
    function playNotificationSound() {
        try {
            const audio = new Audio('/assets/admin/sounds/notification.mp3');
            audio.volume = 0.3;
            audio.play().catch(e => console.debug('[Sound] Playback blocked or failed:', e));
        } catch (e) {
            console.debug('[Sound] Error playing sound:', e);
        }
    }

    // --- AJAX ACTIONS ---

    function fetchSettings() {
        $.ajax({
            url: CONFIG.routes.settings,
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                state.settings = {
                    manual_delivery_enabled: data.manual_delivery_enabled !== false,
                    sound_enabled: data.sound_enabled !== false
                };
            }
        });
    }

    function fetchManualCount() {
        if (timeouts.manual) clearTimeout(timeouts.manual);

        $.ajax({
            url: CONFIG.routes.manual,
            method: 'GET',
            dataType: 'json',
            cache: false,
            data: { _t: Date.now() },
            success: function (data) {
                updateBadge('manual', data.count || 0);
            },
            complete: function () {
                timeouts.manual = setTimeout(fetchManualCount, CONFIG.polling.manual);
            }
        });
    }

    function fetchDisputesCount() {
        $.ajax({
            url: CONFIG.routes.disputes,
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                updateBadge('disputes', data.count || 0);
            }
        });
    }

    function fetchSupportCount() {
        $.ajax({
            url: CONFIG.routes.support,
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                updateBadge('support', data.count || 0);
            }
        });
    }

    function fetchAdminNotifications() {
        $.ajax({
            url: CONFIG.routes.notifications,
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                if (data && typeof data.total !== 'undefined') {
                    const currentCount = parseInt(data.total);

                    if (state.notifications.count !== -1 && currentCount > state.notifications.count) {
                        if (state.settings.sound_enabled) {
                            playNotificationSound();
                        }
                    }
                    state.notifications.count = currentCount;
                }
            }
        });
    }

    // --- INITIALIZATION ---

    function init() {
        // Only run on admin pages
        if (!location.pathname.startsWith("/admin")) return;

        $(document).ready(function () {
            // 1. Initial data fetch
            fetchSettings();

            // Wait 1s for the DOM and other scripts to settle before first badge updates
            setTimeout(() => {
                fetchManualCount();    // This one starts its own chain
                fetchDisputesCount();
                fetchSupportCount();
                fetchAdminNotifications();
            }, 1000);

            // 2. Setup regular intervals
            setInterval(fetchSettings, CONFIG.polling.settings);
            setInterval(fetchDisputesCount, CONFIG.polling.disputes);
            setInterval(fetchSupportCount, CONFIG.polling.support);
            setInterval(fetchAdminNotifications, CONFIG.polling.notifications);

            // 3. Audio unlock (browser restriction bypass)
            $(document).one('click', function () {
                // Just create and play an empty/silent sound or just log
                console.debug('[Audio] First interaction recorded, audio unlocked');
            });
        });
    }

    // Start the machine
    if (typeof jQuery !== 'undefined') {
        init();
    } else {
        // Retry if jQuery is not loaded yet
        let retries = 0;
        const jqInterval = setInterval(() => {
            retries++;
            if (typeof jQuery !== 'undefined' || retries > 50) {
                clearInterval(jqInterval);
                if (typeof jQuery !== 'undefined') init();
            }
        }, 100);
    }

})(window.jQuery);
