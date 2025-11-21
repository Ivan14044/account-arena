<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use App\Models\SupportMessage;
use App\Models\SupportChat;
use App\Models\ProductDispute;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Обновляем label для меню
        $menu = Config::get('adminlte.menu', []);
        
        // Обновляем label для Чат поддержки
        foreach ($menu as $idx => $item) {
            if (is_array($item) && isset($item['id']) && $item['id'] === 'support-chats-unread-count') {
                try {
                    // Получаем количество непрочитанных сообщений
                    $unreadCount = Cache::remember('support_chats_unread_count', 30, function () {
                        try {
                            return SupportMessage::whereHas('chat', function($query) {
                                $query->where('status', '!=', SupportChat::STATUS_CLOSED);
                            })
                            ->fromUserOrGuest()
                            ->where('is_read', false)
                            ->count();
                        } catch (\Throwable $e) {
                            // Если ошибка при запросе к БД, возвращаем 0
                            return 0;
                        }
                    });
                    
                    if ($unreadCount > 0) {
                        $item['label'] = (string)$unreadCount;
                        $item['label_color'] = 'danger';
                    } else {
                        $item['label'] = '';
                        $item['label_color'] = 'secondary';
                    }
                } catch (\Throwable $e) {
                    // Если произошла ошибка, оставляем пункт меню без изменений
                    // Не удаляем его из меню
                }
                $menu[$idx] = $item;
                break;
            }
        }

        // Обновляем label для Претензий
        foreach ($menu as $idx => $item) {
            if (is_array($item) && isset($item['id']) && $item['id'] === 'disputes-unread-count') {
                try {
                    // Получаем количество новых претензий
                    $newCount = Cache::remember('disputes_new_count', 30, function () {
                        try {
                            return ProductDispute::where('status', ProductDispute::STATUS_NEW)->count();
                        } catch (\Throwable $e) {
                            // Если ошибка при запросе к БД, возвращаем 0
                            return 0;
                        }
                    });
                    
                    if ($newCount > 0) {
                        $item['label'] = (string)$newCount;
                        $item['label_color'] = 'warning';
                    } else {
                        $item['label'] = '';
                        $item['label_color'] = 'secondary';
                    }
                } catch (\Throwable $e) {
                    // Если произошла ошибка, оставляем пункт меню без изменений
                    // Не удаляем его из меню
                }
                $menu[$idx] = $item;
                break;
            }
        }

        Config::set('adminlte.menu', $menu);
    }
}
