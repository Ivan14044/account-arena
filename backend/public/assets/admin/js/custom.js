(function(){
    // Переменная для хранения предыдущего значения счетчика
    let previousUnreadCount = -1;
    
    // Функция для обновления счетчика непрочитанных сообщений в чате поддержки
    function updateSupportChatBadge() {
        if (typeof jQuery === 'undefined') {
            return;
        }

        let $badgeElement = null;
        let $menuLink = null;

        // Ищем пункт меню "Чат поддержки" по тексту
        $('.nav-sidebar .nav-item, .main-sidebar .nav-item, .sidebar .nav-item').each(function() {
            const $item = $(this);
            const $link = $item.find('a.nav-link, a').first();
            if ($link.length) {
                // Получаем текст ссылки, убираем числа и пробелы для сравнения
                let linkText = $link.clone().children().remove().end().text().trim();
                linkText = linkText.replace(/\d+/g, '').trim();
                
                if (linkText.includes('Чат поддержки') || linkText === 'Чат поддержки') {
                    $menuLink = $link;
                    
                    // Ищем существующий badge внутри ссылки
                    $badgeElement = $link.find('.badge, .label, span.badge, span.label').first();
                    
                    // Если badge не найден, создаем его
                    if (!$badgeElement.length) {
                        $badgeElement = $('<span>')
                            .addClass('badge badge-danger float-right')
                            .attr('id', 'support-chats-unread-count');
                        $link.append($badgeElement);
                    }
                    
                    return false; // Прерываем цикл
                }
            }
        });

        // Если не нашли по тексту, пытаемся найти по ID (fallback)
        if (!$badgeElement || !$badgeElement.length) {
            const $elementById = $('#support-chats-unread-count');
            if ($elementById.length) {
                // Если это badge или label, используем его
                if ($elementById.hasClass('badge') || $elementById.hasClass('label')) {
                    $badgeElement = $elementById;
                } else {
                    // Если ID на родительском элементе, ищем badge внутри
                    $badgeElement = $elementById.find('.badge, .label').first();
                    if (!$badgeElement.length) {
                        // Ищем ссылку и создаем badge там
                        $menuLink = $elementById.find('a.nav-link, a').first();
                        if ($menuLink.length) {
                            $badgeElement = $('<span>')
                                .addClass('badge badge-danger float-right')
                                .attr('id', 'support-chats-unread-count');
                            $menuLink.append($badgeElement);
                        }
                    }
                }
            }
        }

        // Если все еще не нашли badge, выходим (не скрываем пункт меню!)
        if (!$badgeElement || !$badgeElement.length) {
            return;
        }
        
        // Дополнительная проверка: убеждаемся, что это действительно badge
        if (!$badgeElement.hasClass('badge') && !$badgeElement.hasClass('label')) {
            // Если это не badge, ищем badge внутри
            const $actualBadge = $badgeElement.find('.badge, .label').first();
            if ($actualBadge.length) {
                $badgeElement = $actualBadge;
            } else {
                return; // Не нашли badge, выходим
            }
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
                
                // Обновляем только badge, никогда не трогаем пункт меню
                // Дополнительная проверка: убеждаемся, что мы не работаем с пунктом меню
                const $parentNavItem = $badgeElement.closest('.nav-item');
                if ($parentNavItem.length && $badgeElement[0] === $parentNavItem[0]) {
                    // Если badgeElement это сам nav-item, выходим
                    return;
                }
                
                if (count > 0) {
                    $badgeElement.text(count)
                        .removeClass('badge-secondary')
                        .addClass('badge-danger')
                        .show();
                } else {
                    // Скрываем только badge, не пункт меню
                    // Убеждаемся, что мы скрываем именно badge, а не родительский элемент
                    if ($badgeElement.hasClass('badge') || $badgeElement.hasClass('label')) {
                        $badgeElement.hide();
                    }
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
