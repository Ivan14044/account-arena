(function () {
    // Переменная для хранения предыдущего значения счетчика
    let previousUnreadCount = -1;

    // Функция для обновления счетчика непрочитанных сообщений в чате поддержки
    function updateSupportChatBadge() {
        // Only run on admin pages
        if (!location.pathname.startsWith("/admin")) {
            return;
        }

        if (typeof jQuery === 'undefined') {
            return;
        }

        let $badgeElement = document.querySelector('#support-chats-unread-count .badge');
        if (!$badgeElement) {
            return;
        }

        $.ajax({
            url: '/admin/support-chats/unread-count',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                const count = data.count || 0;

                // Воспроизводим звук при появлении нового непрочитанного сообщения
                if (count > previousUnreadCount && previousUnreadCount >= 0) {
                    const newMessagesCount = count - previousUnreadCount;
                    console.log('[Sound] Playing notification sound - Support chat: ' + newMessagesCount + ' new unread message(s)', {
                        event: 'support_chat_new_message',
                        previousCount: previousUnreadCount,
                        currentCount: count,
                        newMessages: newMessagesCount
                    });

                    try {
                        const audio = new Audio('/assets/admin/sounds/notification.mp3');
                        audio.volume = 0.3; // 30% громкости
                        audio.play().catch(function (error) {
                            // Игнорируем ошибки воспроизведения
                            console.debug('Could not play notification sound:', error);
                        });
                    } catch (error) {
                        console.debug('Failed to create audio element:', error);
                    }
                }

                previousUnreadCount = count;

                if (count > 0) {
                    $badgeElement.textContent = count;
                    $badgeElement.classList.remove('badge-secondary');
                    $badgeElement.classList.add('badge-danger');
                } else {
                    $badgeElement.textContent = '';
                    $badgeElement.classList.remove('badge-danger');
                }
            },
            error: function (xhr, status, error) {
                // Игнорируем ошибки
            }
        });
    }

    function initSupportChatBadge() {
        if (!location.pathname.startsWith("/admin")) {
            return;
        }

        if (typeof jQuery !== 'undefined') {
            $(document).ready(function () {
                setTimeout(function () {
                    updateSupportChatBadge();
                    setInterval(updateSupportChatBadge, 3000);
                }, 1000);
            });
        } else {
            setTimeout(initSupportChatBadge, 100);
        }
    }

    initSupportChatBadge();
})();

// Счетчик новых претензий на товары
(function () {
    // Переменная для хранения предыдущего значения счетчика
    let previousNewDisputesCount = -1;

    // Функция для обновления счетчика новых претензий
    function updateDisputesBadge() {
        // Only run on admin pages
        if (!location.pathname.startsWith("/admin")) {
            return;
        }

        if (typeof jQuery === 'undefined') {
            return;
        }

        let $badgeElement = document.querySelector('#disputes-unread-count .badge')
        if (!$badgeElement) {
            return;
        }

        $.ajax({
            url: '/admin/disputes/new-count',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                const count = data.count || 0;

                // Воспроизводим звук при появлении новой претензии
                if (count > previousNewDisputesCount && previousNewDisputesCount >= 0) {
                    const newDisputesCount = count - previousNewDisputesCount;
                    console.log('[Sound] Playing notification sound - Disputes: ' + newDisputesCount + ' new dispute(s)', {
                        event: 'disputes_new_dispute',
                        previousCount: previousNewDisputesCount,
                        currentCount: count,
                        newDisputes: newDisputesCount
                    });

                    try {
                        const audio = new Audio('/assets/admin/sounds/notification.mp3');
                        audio.volume = 0.3; // 30% громкости
                        audio.play().catch(function (error) {
                            // Игнорируем ошибки воспроизведения
                            console.debug('Could not play notification sound:', error);
                        });
                    } catch (error) {
                        console.debug('Failed to create audio element:', error);
                    }
                }

                previousNewDisputesCount = count;


                if (count > 0) {
                    $badgeElement.innerText = count;
                    $badgeElement.classList.remove('badge-secondary');
                    $badgeElement.classList.add('badge-warning');
                } else {
                    $badgeElement.innerText = '';
                    $badgeElement.classList.remove('badge-warning');
                }
            },
            error: function (xhr, status, error) {
                // Игнорируем ошибки
            }
        });
    }

    function initDisputesBadge() {
        // Only initialize on admin pages
        if (!location.pathname.startsWith("/admin")) {
            return;
        }

        if (typeof jQuery !== 'undefined') {
            $(document).ready(function () {
                setTimeout(function () {
                    updateDisputesBadge();
                    setInterval(updateDisputesBadge, 3000);
                }, 1000);
            });
        } else {
            setTimeout(initDisputesBadge, 100);
        }
    }

    initDisputesBadge();
})();

// Счетчик заказов на ручную обработку
(function () {
    // Переменная для хранения предыдущего значения счетчика
    let previousManualDeliveryCount = -1;

    // Функция для обновления счетчика заказов на ручную обработку
    function updateManualDeliveryBadge() {
        // Only run on admin pages
        if (!location.pathname.startsWith("/admin")) {
            return;
        }

        if (typeof jQuery === 'undefined') {
            return;
        }

        let $badgeElement = document.querySelector('#manual-delivery-count .badge');
        if (!$badgeElement) {
            return;
        }

        $.ajax({
            url: '/admin/manual-delivery/count',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                const count = data.count || 0;

                // Воспроизводим звук при появлении новых заказов
                if (count > previousManualDeliveryCount && previousManualDeliveryCount >= 0) {
                    const newOrdersCount = count - previousManualDeliveryCount;
                    console.log('[Sound] Playing notification sound - Manual Delivery: ' + newOrdersCount + ' new order(s)', {
                        event: 'manual_delivery_new_order',
                        previousCount: previousManualDeliveryCount,
                        currentCount: count,
                        newOrders: newOrdersCount
                    });

                    try {
                        const audio = new Audio('/assets/admin/sounds/notification.mp3');
                        audio.volume = 0.3; // 30% громкости
                        audio.play().catch(function (error) {
                            // Игнорируем ошибки воспроизведения
                            console.debug('Could not play notification sound:', error);
                        });
                    } catch (error) {
                        console.debug('Failed to create audio element:', error);
                    }
                }

                previousManualDeliveryCount = count;

                if (count > 0) {
                    $badgeElement.innerText = count;
                    $badgeElement.classList.remove('badge-secondary');
                    $badgeElement.classList.add('badge-warning');
                } else {
                    $badgeElement.innerText = '';
                    $badgeElement.classList.remove('badge-warning');
                    $badgeElement.classList.add('badge-secondary');
                }
            },
            error: function (xhr, status, error) {
                // Игнорируем ошибки
            }
        });
    }

    function initManualDeliveryBadge() {
        // Only initialize on admin pages
        if (!location.pathname.startsWith("/admin")) {
            return;
        }

        if (typeof jQuery !== 'undefined') {
            $(document).ready(function () {
                setTimeout(function () {
                    updateManualDeliveryBadge();
                    setInterval(updateManualDeliveryBadge, 3000);
                }, 1000);
            });
        } else {
            setTimeout(initManualDeliveryBadge, 100);
        }
    }

    initManualDeliveryBadge();
})();

// Звуковое оповещение для уведомлений администратора
(function () {
    let lastNotificationCount = -1; // -1 означает, что еще не инициализировано
    let notificationSound = null;
    let isInitialized = false;

    // Инициализация звука
    function initSound() {
        try {
            notificationSound = new Audio('/assets/admin/sounds/notification.mp3');
            notificationSound.volume = 0.5; // Устанавливаем громкость 50%
        } catch (e) {
            console.warn('Не удалось загрузить звук уведомления:', e);
        }
    }

    // Воспроизведение звука
    function playNotificationSound(reason) {
        if (notificationSound) {
            console.log('[Sound] Playing notification sound - Admin notifications', {
                event: 'admin_notification_new',
                reason: reason || 'new_notification',
                timestamp: new Date().toISOString()
            });

            notificationSound.play().catch(function (error) {
                // Игнорируем ошибки автовоспроизведения (браузеры блокируют автовоспроизведение)
                console.debug('Автовоспроизведение звука заблокировано браузером');
            });
        }
    }

    // Обработка обновления уведомлений из API
    function handleNotificationUpdate(data) {
        if (!data || typeof data.label === 'undefined') {
            return;
        }

        const currentCount = parseInt(data.label) || 0;

        // Если это первая инициализация, просто сохраняем счетчик
        if (lastNotificationCount === -1) {
            lastNotificationCount = currentCount;
            isInitialized = true;
            return;
        }

        // Если счетчик увеличился и звук включен, воспроизводим звук
        if (currentCount > lastNotificationCount && isInitialized) {
            const newNotificationsCount = currentCount - lastNotificationCount;
            if (data.sound_enabled && data.has_new) {
                playNotificationSound('count_increased: ' + newNotificationsCount + ' new notification(s)');
            } else if (!data.sound_enabled) {
                console.log('[Sound] Sound disabled in settings, skipping playback', {
                    event: 'admin_notification_new',
                    reason: 'sound_disabled',
                    newNotifications: newNotificationsCount
                });
            } else if (!data.has_new) {
                console.log('[Sound] No new notifications flag, skipping playback', {
                    event: 'admin_notification_new',
                    reason: 'no_new_flag',
                    newNotifications: newNotificationsCount
                });
            }
        }

        lastNotificationCount = currentCount;
    }

    // Проверка новых уведомлений через API
    function checkAdminNotificationsViaAPI() {
        if (!location.pathname.startsWith("/admin")) {
            return;
        }

        $.ajax({
            url: '/admin/admin_notifications/get',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                handleNotificationUpdate(data);
            },
            error: function () {
                // Игнорируем ошибки
            }
        });
    }

    // Инициализация при загрузке страницы
    if (typeof jQuery !== 'undefined' && location.pathname.startsWith("/admin")) {
        $(document).ready(function () {
            initSound();

            // Получаем начальное количество уведомлений через API
            checkAdminNotificationsViaAPI();

            // Проверяем уведомления каждые 5 секунд (чаще, чем AdminLTE обновляет)
            setInterval(checkAdminNotificationsViaAPI, 5000);
        });
    }

    // Перехватываем обновления виджета уведомлений AdminLTE через jQuery
    if (typeof jQuery !== 'undefined') {
        $(document).ready(function () {
            // Перехватываем AJAX запросы к уведомлениям
            $(document).ajaxSuccess(function (event, xhr, settings) {
                if (settings.url && settings.url.includes('admin_notifications/get')) {
                    try {
                        const data = typeof xhr.responseJSON !== 'undefined' ? xhr.responseJSON : JSON.parse(xhr.responseText);
                        handleNotificationUpdate(data);
                    } catch (e) {
                        // Игнорируем ошибки парсинга
                    }
                }
            });
        });
    }
})();

// Виправлення logout URL для admin та supplier панелей
(function () {
    function fixLogoutForm() {
        const logoutForm = document.getElementById('logout-form');
        if (!logoutForm)  return;
        const currentPath = window.location.pathname;
        let logoutUrl = '';

        if (currentPath.startsWith('/admin')) {
            logoutUrl = '/admin/logout';
        } else if (currentPath.startsWith('/supplier')) {
            logoutUrl = '/supplier/logout';
        } else {
            logoutUrl = '/admin/logout';
        }

        if (logoutForm.getAttribute('action') !== logoutUrl) {
            logoutForm.setAttribute('action', logoutUrl);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixLogoutForm);
    } else {
        fixLogoutForm();
    }
})();

// Глобальное обновление badge "Ручная обработка" на всех страницах админ-панели
(function () {
    // Переменная для хранения предыдущего значения счетчика
    let previousManualDeliveryCount = -1;

    // Функция для обновления счетчика заказов на ручную обработку
    function updateManualDeliveryBadge() {
        // Only run on admin pages
        if (!location.pathname.startsWith("/admin")) {
            return;
        }

        if (typeof jQuery === 'undefined') {
            return;
        }

        let $badgeElement = document.querySelector('#manual-delivery-count');
        if (!$badgeElement) {
            return;
        }

        $.ajax({
            url: '/admin/manual-delivery/count',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                const count = data.count || 0;

                // Воспроизводим звук при появлении нового заказа на обработку
                if (count > previousManualDeliveryCount && previousManualDeliveryCount >= 0) {
                    const newOrdersCount = count - previousManualDeliveryCount;
                    console.log('[Sound] Playing notification sound - Manual delivery: ' + newOrdersCount + ' new order(s)', {
                        event: 'manual_delivery_new_order',
                        previousCount: previousManualDeliveryCount,
                        currentCount: count,
                        newOrders: newOrdersCount
                    });

                    try {
                        const audio = new Audio('/assets/admin/sounds/notification.mp3');
                        audio.volume = 0.3; // 30% громкости
                        audio.play().catch(function (error) {
                            // Игнорируем ошибки воспроизведения
                            console.debug('Could not play notification sound:', error);
                        });
                    } catch (error) {
                        console.debug('Failed to create audio element:', error);
                    }
                }

                previousManualDeliveryCount = count;

                if (count > 0) {
                    $badgeElement.textContent = count > 99 ? '99+' : count;
                    $badgeElement.className = 'badge badge-warning navbar-badge';
                    $badgeElement.style.display = 'inline-block';
                } else {
                    $badgeElement.textContent = '';
                    $badgeElement.style.display = 'none';
                }
            },
            error: function (xhr, status, error) {
                // Игнорируем ошибки
            }
        });
    }

    function initManualDeliveryBadge() {
        if (!location.pathname.startsWith("/admin")) {
            return;
        }

        if (typeof jQuery !== 'undefined') {
            $(document).ready(function () {
                setTimeout(function () {
                    updateManualDeliveryBadge();
                    setInterval(updateManualDeliveryBadge, 30000); // Обновляем каждые 30 секунд
                }, 1000);
            });
        } else {
            setTimeout(initManualDeliveryBadge, 100);
        }
    }

    initManualDeliveryBadge();
})();
