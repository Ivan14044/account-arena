<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Шаблон 1: Уведомление после регистрации
        $registrationTemplate = NotificationTemplate::firstOrCreate(
            ['code' => 'registration'],
            [
                'name' => 'Уведомление о регистрации',
                'is_mass' => 0,
            ]
        );

        // Обновляем name и is_mass на случай, если шаблон уже существовал
        $registrationTemplate->update([
            'name' => 'Уведомление о регистрации',
            'is_mass' => 0,
        ]);

        $this->saveTranslations($registrationTemplate, [
            'title' => [
                'ru' => 'Добро пожаловать в Account Arena!',
                'en' => 'Welcome to Account Arena!',
                'uk' => 'Ласкаво просимо до Account Arena!',
            ],
            'message' => [
                'ru' => 'Спасибо за регистрацию! Мы рады видеть вас в нашей системе. Теперь вы можете покупать цифровые товары, премиум аккаунты и многое другое по выгодным ценам. Приятных покупок!',
                'en' => 'Thank you for registering! We are glad to see you in our system. Now you can buy digital goods, premium accounts and much more at competitive prices. Happy shopping!',
                'uk' => 'Дякуємо за реєстрацію! Ми раді бачити вас у нашій системі. Тепер ви можете купувати цифрові товари, преміум акаунти та багато іншого за вигідними цінами. Приємних покупок!',
            ],
        ]);

        // Шаблон 2: Уведомление после покупки
        $purchaseTemplate = NotificationTemplate::firstOrCreate(
            ['code' => 'purchase'],
            [
                'name' => 'Уведомление о покупке',
                'is_mass' => 0,
            ]
        );
        
        // Обновляем name и is_mass на случай, если шаблон уже существовал
        $purchaseTemplate->update([
            'name' => 'Уведомление о покупке',
            'is_mass' => 0,
        ]);

        $this->saveTranslations($purchaseTemplate, [
            'title' => [
                'ru' => 'Покупка успешно завершена',
                'en' => 'Purchase completed successfully',
                'uk' => 'Покупку успішно завершено',
            ],
            'message' => [
                'ru' => 'Ваш заказ :order_number успешно оплачен! Данные для доступа к товару доступны в разделе "Мои покупки" в вашем профиле. Спасибо за покупку!',
                'en' => 'Your order :order_number has been successfully paid! Access data for the product is available in the "My Purchases" section in your profile. Thank you for your purchase!',
                'uk' => 'Ваше замовлення :order_number успішно оплачено! Дані для доступу до товару доступні в розділі "Мої покупки" у вашому профілі. Дякуємо за покупку!',
            ],
        ]);

        // Шаблон 3: Уведомление о решении претензии
        $disputeTemplate = NotificationTemplate::firstOrCreate(
            ['code' => 'dispute_resolved'],
            [
                'name' => 'Уведомление о решении претензии',
                'is_mass' => 0,
            ]
        );
        
        // Обновляем name и is_mass на случай, если шаблон уже существовал
        $disputeTemplate->update([
            'name' => 'Уведомление о решении претензии',
            'is_mass' => 0,
        ]);

        $this->saveTranslations($disputeTemplate, [
            'title' => [
                'ru' => 'Претензия рассмотрена',
                'en' => 'Dispute resolved',
                'uk' => 'Претензію розглянуто',
            ],
            'message' => [
                'ru' => 'Ваша претензия #:dispute_id рассмотрена администратором. Решение: :decision. :comment Подробности доступны в разделе "Мои покупки" → "Претензии".',
                'en' => 'Your dispute #:dispute_id has been reviewed by the administrator. Decision: :decision. :comment Details are available in the "My Purchases" → "Disputes" section.',
                'uk' => 'Вашу претензію #:dispute_id розглянуто адміністратором. Рішення: :decision. :comment Деталі доступні в розділі "Мої покупки" → "Претензії".',
            ],
        ]);

        // Шаблон 4: Админ-уведомление о новой покупке
        $adminPurchaseTemplate = NotificationTemplate::firstOrCreate(
            ['code' => 'admin_product_purchase'],
            [
                'name' => 'Админ: Уведомление о новой покупке',
                'is_mass' => 0,
            ]
        );
        
        // Обновляем name и is_mass на случай, если шаблон уже существовал
        $adminPurchaseTemplate->update([
            'name' => 'Админ: Уведомление о новой покупке',
            'is_mass' => 0,
        ]);

        $this->saveTranslations($adminPurchaseTemplate, [
            'title' => [
                'ru' => 'Новая покупка (:method)',
                'en' => 'New purchase (:method)',
                'uk' => 'Нова покупка (:method)',
            ],
            'message' => [
                'ru' => 'Новая покупка (:method), email: :email, имя: :name, товаров: :products, сумма: :amount',
                'en' => 'New purchase (:method), email: :email, name: :name, products: :products, amount: :amount',
                'uk' => 'Нова покупка (:method), email: :email, ім\'я: :name, товарів: :products, сума: :amount',
            ],
        ]);

        // Шаблон 5: Уведомление о создании заказа на ручную обработку
        $manualOrderCreatedTemplate = NotificationTemplate::firstOrCreate(
            ['code' => 'manual_delivery_order_created'],
            [
                'name' => 'Заказ принят в обработку',
                'is_mass' => 0,
            ]
        );
        
        $manualOrderCreatedTemplate->update([
            'name' => 'Заказ принят в обработку',
            'is_mass' => 0,
        ]);

        $this->saveTranslations($manualOrderCreatedTemplate, [
            'title' => [
                'ru' => 'Ваш заказ принят в обработку',
                'en' => 'Your order is being processed',
                'uk' => 'Ваше замовлення прийнято в обробку',
            ],
            'message' => [
                'ru' => 'Ваш заказ :order_number на товар ":product_title" принят в обработку. Товар будет выдан менеджером вручную в течение рабочего времени (Пн-Пт, 9:00-18:00 по Киеву). Вы получите уведомление, когда товар будет готов.',
                'en' => 'Your order :order_number for ":product_title" has been accepted for processing. The product will be delivered manually by a manager during business hours (Mon-Fri, 9:00-18:00 Kyiv). You will receive a notification when the product is ready.',
                'uk' => 'Ваше замовлення :order_number на товар ":product_title" прийнято в обробку. Товар буде видано менеджером вручну протягом робочого часу (Пн-Пт, 9:00-18:00 за Києвом). Ви отримаєте сповіщення, коли товар буде готовий.',
            ],
        ]);

        // Шаблон 6: Уведомление о завершении ручной обработки
        $manualDeliveryCompletedTemplate = NotificationTemplate::firstOrCreate(
            ['code' => 'manual_delivery_completed'],
            [
                'name' => 'Заказ готов',
                'is_mass' => 0,
            ]
        );
        
        $manualDeliveryCompletedTemplate->update([
            'name' => 'Заказ готов',
            'is_mass' => 0,
        ]);

        $this->saveTranslations($manualDeliveryCompletedTemplate, [
            'title' => [
                'ru' => 'Ваш заказ готов!',
                'en' => 'Your order is ready!',
                'uk' => 'Ваше замовлення готове!',
            ],
            'message' => [
                'ru' => 'Ваш заказ :order_number на товар ":product_title" успешно обработан. Товар готов к использованию! Данные для доступа доступны в разделе "Мои покупки" в вашем профиле.',
                'en' => 'Your order :order_number for ":product_title" has been successfully processed. The product is ready to use! Access data is available in the "My Purchases" section in your profile.',
                'uk' => 'Ваше замовлення :order_number на товар ":product_title" успішно оброблено. Товар готовий до використання! Дані для доступу доступні в розділі "Мої покупки" у вашому профілі.',
            ],
        ]);
    }

    private function saveTranslations(NotificationTemplate $template, array $translations): void
    {
        foreach ($translations as $code => $localeValues) {
            foreach ($localeValues as $locale => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $template->translations()->updateOrCreate(
                    ['locale' => $locale, 'code' => $code],
                    ['value' => $value]
                );
            }
        }
    }
}

