(function(){
    // Переменная для хранения предыдущего значения счетчика
    let previousUnreadCount = -1;
    
    // Функция для обновления счетчика непрочитанных сообщений в чате поддержки
    function updateSupportChatBadge() {
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
            success: function(data) {
                const count = data.count || 0;
                
                // Воспроизводим звук при появлении нового непрочитанного сообщения
                if (count > previousUnreadCount && previousUnreadCount >= 0) {
                    try {
                        const audio = new Audio('/assets/admin/sounds/notification.mp3');
                        audio.volume = 0.3; // 30% громкости
                        audio.play().catch(function(error) {
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
            error: function(xhr, status, error) {
                // Игнорируем ошибки
            }
        });
    }

    window.updateSupportChatBadge = updateSupportChatBadge;

    function initSupportChatBadge() {
        if (typeof jQuery !== 'undefined') {
            $(document).ready(function() {
                setTimeout(function() {
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
(function(){
    // Переменная для хранения предыдущего значения счетчика
    let previousNewDisputesCount = -1;
    
    // Функция для обновления счетчика новых претензий
    function updateDisputesBadge() {
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
            success: function(data) {
                const count = data.count || 0;
                
                // Воспроизводим звук при появлении новой претензии
                if (count > previousNewDisputesCount && previousNewDisputesCount >= 0) {
                    try {
                        const audio = new Audio('/assets/admin/sounds/notification.mp3');
                        audio.volume = 0.3; // 30% громкости
                        audio.play().catch(function(error) {
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
            error: function(xhr, status, error) {
                // Игнорируем ошибки
            }
        });
    }

    window.updateDisputesBadge = updateDisputesBadge;

    function initDisputesBadge() {
        if (typeof jQuery !== 'undefined') {
            $(document).ready(function() {
                setTimeout(function() {
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

// Звуковое оповещение для уведомлений администратора
(function() {
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
    function playNotificationSound() {
        if (notificationSound) {
            notificationSound.play().catch(function(error) {
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
            if (data.sound_enabled && data.has_new) {
                playNotificationSound();
            }
        }
        
        lastNotificationCount = currentCount;
    }
    
    // Проверка новых уведомлений через API
    function checkNotificationsViaAPI() {
        if (typeof jQuery === 'undefined') {
            return;
        }
        
        $.ajax({
            url: '/admin/admin_notifications/get',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                handleNotificationUpdate(data);
            },
            error: function() {
                // Игнорируем ошибки
            }
        });
    }
    
    // Инициализация при загрузке страницы
    if (typeof jQuery !== 'undefined') {
        $(document).ready(function() {
            initSound();
            
            // Получаем начальное количество уведомлений через API
            checkNotificationsViaAPI();
            
            // Проверяем уведомления каждые 5 секунд (чаще, чем AdminLTE обновляет)
            setInterval(checkNotificationsViaAPI, 5000);
        });
    }
    
    // Перехватываем обновления виджета уведомлений AdminLTE через jQuery
    if (typeof jQuery !== 'undefined') {
        $(document).ready(function() {
            // Перехватываем AJAX запросы к уведомлениям
            $(document).ajaxSuccess(function(event, xhr, settings) {
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
