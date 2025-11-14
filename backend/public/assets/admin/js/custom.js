(function(){
    // Переменная для хранения предыдущего значения счетчика
    let previousUnreadCount = -1;
    
    // Функция для обновления счетчика непрочитанных сообщений в чате поддержки
    function updateSupportChatBadge() {
        if (typeof jQuery === 'undefined') {
            return;
        }

        let $badgeElement = null;

        $('.nav-sidebar .nav-item, .main-sidebar .nav-item').each(function() {
            const $link = $(this).find('a.nav-link, a');
            if ($link.length) {
                const linkText = $link.text().trim();
                if (linkText.includes('Чат поддержки') || linkText.includes('Чат')) {
                    $badgeElement = $(this).find('.badge, .label, span.badge, span.label');
                    
                    if (!$badgeElement.length && $link.length) {
                        $badgeElement = $('<span>')
                            .addClass('badge badge-danger float-right')
                            .attr('id', 'support-chats-unread-count');
                        $link.append($badgeElement);
                    }
                    return false;
                }
            }
        });

        if (!$badgeElement || !$badgeElement.length) {
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
                        const audio = new Audio('/sounds/notification.mp3');
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
                    $badgeElement.text(count)
                        .removeClass('badge-secondary')
                        .addClass('badge-danger')
                        .show();
                } else {
                    $badgeElement.hide();
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