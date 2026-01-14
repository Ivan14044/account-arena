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
                    $badgeElement.innerText = count;
                    $badgeElement.style.display = 'inline-block';
                } else {
                    $badgeElement.style.display = 'none';
                }
            },
            error: function (xhr, status, error) {
                console.debug('[Support Chat Badge] Error updating badge:', error);
            }
        });
    }

    // Обновляем каждые 30 секунд
    if (location.pathname.startsWith("/admin")) {
        setInterval(updateSupportChatBadge, 30000);
        // Первое обновление через 5 секунд
        setTimeout(updateSupportChatBadge, 5000);
    }
})();

(function () {
    // Переменная для хранения предыдущего значения счетчика претензий
    let previousDisputeCount = -1;

    // Функция для обновления счетчика новых претензий
    function updateDisputesBadge() {
        // Only run on admin pages
        if (!location.pathname.startsWith("/admin")) {
            return;
        }

        if (typeof jQuery === 'undefined') {
            return;
        }

        let $badgeElement = document.querySelector('#disputes-unread-count .badge');
        if (!$badgeElement) {
            return;
        }

        $.ajax({
            url: '/admin/disputes/new-count',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                const count = data.count || 0;

                // Воспроизводим звук при появлении новых претензий
                if (count > previousDisputeCount && previousDisputeCount >= 0) {
                    const newDisputesCount = count - previousDisputeCount;
                    console.log('[Sound] Playing notification sound - New disputes: ' + newDisputesCount + ' new dispute(s)', {
                        event: 'new_dispute',
                        previousCount: previousDisputeCount,
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

                previousDisputeCount = count;

                if (count > 0) {
                    $badgeElement.innerText = count;
                    $badgeElement.style.display = 'inline-block';
                    $badgeElement.classList.remove('badge-secondary');
                    $badgeElement.classList.add('badge-warning');
                } else {
                    $badgeElement.style.display = 'none';
                }
            },
            error: function (xhr, status, error) {
                console.debug('[Disputes Badge] Error updating badge:', error);
            }
        });
    }

    // Обновляем каждые 30 секунд
    if (location.pathname.startsWith("/admin")) {
        setInterval(updateDisputesBadge, 30000);
        // Первое обновление через 10 секунд
        setTimeout(updateDisputesBadge, 10000);
    }
})();

(function () {
    // Переменная для хранения предыдущего значения счетчика заказов на ручную обработку
    let previousManualDeliveryCount = -1;
    
    // Переменная для хранения настроек уведомлений
    let notificationSettings = {
        manual_delivery_enabled: true,
        sound_enabled: true
    };
    // Флаг для предотвращения множественных интервалов
    let updateInterval = null;
    
    // Функция для очистки интервала обновления счетчика
    function clearManualDeliveryInterval() {
        if (updateInterval !== null) {
            clearTimeout(updateInterval);
            updateInterval = null;
        }
    }

    // Функция для загрузки настроек уведомлений
    function loadNotificationSettings() {
        if (typeof jQuery === 'undefined') {
            return;
        }

        $.ajax({
            url: '/admin/settings/notification-check',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                notificationSettings = {
                    manual_delivery_enabled: data.manual_delivery_enabled !== false,
                    sound_enabled: data.sound_enabled !== false
                };
            },
            error: function () {
                // При ошибке используем значения по умолчанию (включено)
                notificationSettings = {
                    manual_delivery_enabled: true,
                    sound_enabled: true
                };
            }
        });
    }

    // Функция для обновления счетчика заказов на ручную обработку
    function updateManualDeliveryBadge() {
        // Only run on admin pages
        if (!location.pathname.startsWith("/admin")) {
            return;
        }

        if (typeof jQuery === 'undefined') {
            return;
        }

        $.ajax({
            url: '/admin/manual-delivery/count',
            method: 'GET',
            dataType: 'json',
            cache: false, // ВАЖНО: Отключаем кеширование для актуальности данных
            data: { _t: new Date().getTime() }, // Обход кеша через timestamp
            success: function (data) {
                const count = data.count || 0;
                
                // Воспроизводим звук при появлении новых заказов
                // Только если уведомления для ручной обработки включены И звук включен
                if (count > previousManualDeliveryCount && previousManualDeliveryCount >= 0) {
                    const newOrdersCount = count - previousManualDeliveryCount;
                    
                    // Проверяем настройки перед воспроизведением звука
                    if (notificationSettings.manual_delivery_enabled && notificationSettings.sound_enabled) {
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
                    } else {
                        console.log('[Sound] Skipped - Manual Delivery notifications disabled or sound disabled', {
                            event: 'manual_delivery_new_order',
                            manual_delivery_enabled: notificationSettings.manual_delivery_enabled,
                            sound_enabled: notificationSettings.sound_enabled,
                            newOrders: newOrdersCount
                        });
                    }
                }

                previousManualDeliveryCount = count;

                // ОБНОВЛЕННАЯ ЛОГИКА DOM: Ищем тег <p> внутри ссылки, чтобы badge отображался корректно в AdminLTE
                const $li = document.getElementById('manual-delivery-count');
                if (!$li) {
                    console.debug('[Manual Delivery Badge] Element #manual-delivery-count not found in DOM');
                    return;
                }

                // Ищем или создаем контейнер для бейджа
                // В AdminLTE 3 структура обычно: li.nav-item > a.nav-link > p
                const $p = $li.querySelector('a.nav-link p');
                if (!$p) {
                    console.debug('[Manual Delivery Badge] Could not find <p> inside #manual-delivery-count a.nav-link');
                    return;
                }

                let $badge = $li.querySelector('.badge');

                if (count > 0) {
                    if (!$badge) {
                        // Создаем badge, если его нет (с классами AdminLTE/Bootstrap)
                        $badge = document.createElement('span');
                        $badge.className = 'badge badge-warning right'; // 'right' прижимает к правому краю в AdminLTE
                        $p.appendChild($badge);
                        console.debug('[Manual Delivery Badge] Created new badge element');
                    }
                    
                    // Обновляем текст и отображение
                    const displayCount = count > 99 ? '99+' : count;
                    if ($badge.innerText !== String(displayCount)) {
                        $badge.innerText = displayCount;
                        console.debug('[Manual Delivery Badge] Updated count to:', displayCount);
                    }
                    
                    $badge.style.display = 'inline-block';
                    $badge.classList.remove('badge-secondary');
                    $badge.classList.add('badge-warning');
                } else if ($badge) {
                    // Если заказов нет, скрываем badge
                    $badge.style.display = 'none';
                    $badge.innerText = '';
                    console.debug('[Manual Delivery Badge] Hidden badge (count is 0)');
                }
            },
            error: function (xhr, status, error) {
                // Если 404 - маршрут не найден, прекращаем попытки
                if (xhr.status === 404) {
                    console.warn('[Manual Delivery Badge] Route not found (404). Stopping badge updates.');
                    if (updateInterval) {
                        clearTimeout(updateInterval);
                        updateInterval = null;
                    }
                    return;
                }
                
                // Для других ошибок просто логируем
                console.debug('[Manual Delivery Badge] Error updating badge:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    error: error
                });
                
                // При ошибке увеличиваем интервал до 5 секунд, чтобы не перегружать сервер
                updateInterval = setTimeout(updateManualDeliveryBadge, 5000);
            },
            complete: function() {
                // Планируем следующее обновление только если нет ошибки 404
                if (updateInterval !== null) {
                    // Планируем следующее обновление через 2 секунды (вместо жесткого setInterval)
                    // Это предотвращает наложение запросов если сервер тормозит
                    updateInterval = setTimeout(updateManualDeliveryBadge, 2000);
                }
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
                // Загружаем настройки синхронно перед началом обновлений
                loadNotificationSettings();
                
                // Обновляем настройки каждые 60 секунд (на случай, если администратор изменил их)
                setInterval(loadNotificationSettings, 60000);
                
                // Функция для попытки инициализации счетчика
                function tryInitBadge() {
                    // Проверяем, что элемент меню существует (необязательно с бейджем)
                    let $menuItem = document.getElementById('manual-delivery-count');
                    
                    if ($menuItem) {
                        console.log('[Manual Delivery Badge] Menu item found, starting polling...');
                        
                        // Очищаем существующий интервал/таймаут перед созданием нового
                        clearManualDeliveryInterval();
                        
                        // Запускаем первое обновление немедленно
                        updateManualDeliveryBadge();
                        return true;
                    }
                    return false;
                }
                
                // Пытаемся инициализировать немедленно
                if (!tryInitBadge()) {
                    console.debug('[Manual Delivery Badge] Element #manual-delivery-count not found on first try, retrying...');
                    let retries = 0;
                    const maxRetries = 10;
                    const retryInterval = setInterval(function() {
                        retries++;
                        if (tryInitBadge() || retries >= maxRetries) {
                            clearInterval(retryInterval);
                            if (retries >= maxRetries) {
                                console.debug('[Manual Delivery Badge] Max retries reached, element not found');
                            }
                        }
                    }, 1000);
                }
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
            notificationSound.volume = 0.3; // 30% громкости
            isInitialized = true;
            console.log('[Sound] Notification sound initialized');
        } catch (e) {
            console.error('[Sound] Failed to initialize notification sound:', e);
        }
    }

    // Функция воспроизведения звука
    function playSound() {
        if (!isInitialized) initSound();
        if (notificationSound) {
            notificationSound.play().catch(e => {
                console.debug('[Sound] Playback blocked by browser, waiting for user interaction');
            });
        }
    }

    // Функция получения количества уведомлений
    function checkNotifications() {
        if (typeof jQuery === 'undefined') return;

        $.ajax({
            url: '/admin/admin_notifications/get',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                // Если data.total существует (новое API)
                if (data && typeof data.total !== 'undefined') {
                    const currentCount = parseInt(data.total);
                    
                    // Если это не первая загрузка и количество увеличилось - играем звук
                    if (lastNotificationCount !== -1 && currentCount > lastNotificationCount) {
                        console.log('[Sound] Playing notification sound - New admin notification');
                        playSound();
                    }
                    
                    lastNotificationCount = currentCount;
                }
            },
            error: function (xhr) {
                console.debug('[Notifications] Error fetching count:', xhr.status);
            }
        });
    }

    // Инициализация при загрузке
    $(document).ready(function() {
        // Проверяем каждые 30 секунд
        setInterval(checkNotifications, 30000);
        // Первая проверка через 3 секунды
        setTimeout(checkNotifications, 3000);
        
        // Инициализируем звук при первом клике пользователя (обход ограничений браузера)
        $(document).one('click', function() {
            if (!isInitialized) initSound();
        });
    });
})();
