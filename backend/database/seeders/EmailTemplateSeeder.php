<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Template 1: Payment Confirmation
        $paymentConfirmationTemplate = EmailTemplate::firstOrCreate(
            ['code' => 'payment_confirmation'],
            ['name' => 'Payment Confirmation']
        );
        $paymentConfirmationTemplate->update(['name' => 'Payment Confirmation']);

        $this->saveTranslations($paymentConfirmationTemplate, [
            'title' => [
                'ru' => 'Подтверждение оплаты',
                'en' => 'Payment Confirmation',
                'uk' => 'Підтвердження оплати',
            ],
            'message' => [
                'ru' => 'Ваш платеж на сумму {{amount}} успешно обработан. Спасибо за покупку!',
                'en' => 'Your payment of {{amount}} has been successfully processed. Thank you for your purchase!',
                'uk' => 'Ваш платіж на суму {{amount}} успішно оброблено. Дякуємо за покупку!',
            ],
        ]);

        // Template 2: Product Purchase Confirmation
        $productPurchaseTemplate = EmailTemplate::firstOrCreate(
            ['code' => 'product_purchase_confirmation'],
            ['name' => 'Product Purchase Confirmation']
        );
        $productPurchaseTemplate->update(['name' => 'Product Purchase Confirmation']);

        $this->saveTranslations($productPurchaseTemplate, [
            'title' => [
                'ru' => 'Подтверждение покупки',
                'en' => 'Purchase Confirmation',
                'uk' => 'Підтвердження покупки',
            ],
            'message' => [
                'ru' => 'Вы успешно приобрели {{products_count}} товар(ов) на общую сумму {{total_amount}}. Данные для доступа к товарам доступны в разделе "Мои покупки" в вашем профиле.',
                'en' => 'You have successfully purchased {{products_count}} product(s) for a total of {{total_amount}}. Access data for products is available in the "My Purchases" section in your profile.',
                'uk' => 'Ви успішно придбали {{products_count}} товар(ів) на загальну суму {{total_amount}}. Дані для доступу до товарів доступні в розділі "Мої покупки" у вашому профілі.',
            ],
        ]);

        // Template 3: Guest Purchase Confirmation
        $guestPurchaseTemplate = EmailTemplate::firstOrCreate(
            ['code' => 'guest_purchase_confirmation'],
            ['name' => 'Guest Purchase Confirmation']
        );
        $guestPurchaseTemplate->update(['name' => 'Guest Purchase Confirmation']);

        $this->saveTranslations($guestPurchaseTemplate, [
            'title' => [
                'ru' => 'Подтверждение покупки',
                'en' => 'Purchase Confirmation',
                'uk' => 'Підтвердження покупки',
            ],
            'message' => [
                'ru' => 'Здравствуйте! Вы успешно приобрели {{products_count}} товар(ов) на общую сумму {{total_amount}}. Данные для доступа к товарам отправлены на ваш email: {{guest_email}}. Если у вас есть аккаунт, войдите в систему для просмотра всех покупок.',
                'en' => 'Hello! You have successfully purchased {{products_count}} product(s) for a total of {{total_amount}}. Access data for products has been sent to your email: {{guest_email}}. If you have an account, please log in to view all purchases.',
                'uk' => 'Вітаємо! Ви успішно придбали {{products_count}} товар(ів) на загальну суму {{total_amount}}. Дані для доступу до товарів відправлено на ваш email: {{guest_email}}. Якщо у вас є акаунт, увійдіть до системи для перегляду всіх покупок.',
            ],
        ]);

        // Template 4: Reset Password
        $resetPasswordTemplate = EmailTemplate::firstOrCreate(
            ['code' => 'reset_password'],
            ['name' => 'Reset Password']
        );
        $resetPasswordTemplate->update(['name' => 'Reset Password']);

        $this->saveTranslations($resetPasswordTemplate, [
            'title' => [
                'ru' => 'Сброс пароля',
                'en' => 'Password Reset',
                'uk' => 'Скидання пароля',
            ],
            'message' => [
                'ru' => 'Вы запросили сброс пароля для вашего аккаунта ({{email}}). Для сброса пароля перейдите по ссылке: {{button}} Если вы не запрашивали сброс пароля, просто проигнорируйте это письмо.',
                'en' => 'You have requested a password reset for your account ({{email}}). To reset your password, click the link: {{button}} If you did not request a password reset, please ignore this email.',
                'uk' => 'Ви запросили скидання пароля для вашого акаунта ({{email}}). Для скидання пароля перейдіть за посиланням: {{button}} Якщо ви не запитували скидання пароля, просто проігноруйте цей лист.',
            ],
        ]);

        // Template 5: Manual Delivery Order Created
        $manualDeliveryOrderCreatedTemplate = EmailTemplate::firstOrCreate(
            ['code' => 'manual_delivery_order_created'],
            ['name' => 'Manual Delivery Order Created']
        );
        $manualDeliveryOrderCreatedTemplate->update(['name' => 'Manual Delivery Order Created']);

        $this->saveTranslations($manualDeliveryOrderCreatedTemplate, [
            'title' => [
                'ru' => 'Ваш заказ принят в обработку',
                'en' => 'Your order is being processed',
                'uk' => 'Ваше замовлення прийнято в обробку',
            ],
            'message' => [
                'ru' => 'Здравствуйте! Ваш заказ #{{order_number}} на товар "{{product_title}}" принят в обработку. Товар будет выдан менеджером вручную в течение рабочего времени (Пн-Пт, 9:00-18:00 по МСК). Вы получите уведомление, когда товар будет готов. Следить за статусом заказа вы можете в разделе "Мои покупки" в вашем профиле.',
                'en' => 'Hello! Your order #{{order_number}} for "{{product_title}}" has been accepted for processing. The product will be delivered manually by a manager during business hours (Mon-Fri, 9:00-18:00 MSK). You will receive a notification when the product is ready. You can track the order status in the "My Purchases" section in your profile.',
                'uk' => 'Вітаємо! Ваше замовлення #{{order_number}} на товар "{{product_title}}" прийнято в обробку. Товар буде видано менеджером вручну протягом робочого часу (Пн-Пт, 9:00-18:00 за МСК). Ви отримаєте сповіщення, коли товар буде готовий. Відстежувати статус замовлення ви можете в розділі "Мої покупки" у вашому профілі.',
            ],
        ]);

        // Template 6: Manual Delivery Completed
        $manualDeliveryCompletedTemplate = EmailTemplate::firstOrCreate(
            ['code' => 'manual_delivery_completed'],
            ['name' => 'Manual Delivery Completed']
        );
        $manualDeliveryCompletedTemplate->update(['name' => 'Manual Delivery Completed']);

        $this->saveTranslations($manualDeliveryCompletedTemplate, [
            'title' => [
                'ru' => 'Ваш заказ готов!',
                'en' => 'Your order is ready!',
                'uk' => 'Ваше замовлення готове!',
            ],
            'message' => [
                'ru' => 'Здравствуйте! Ваш заказ #{{order_number}} на товар "{{product_title}}" успешно обработан. Товар готов к использованию! Данные для доступа доступны в разделе "Мои покупки" в вашем профиле.',
                'en' => 'Hello! Your order #{{order_number}} for "{{product_title}}" has been successfully processed. The product is ready to use! Access data is available in the "My Purchases" section in your profile.',
                'uk' => 'Вітаємо! Ваше замовлення #{{order_number}} на товар "{{product_title}}" успішно оброблено. Товар готовий до використання! Дані для доступу доступні в розділі "Мої покупки" у вашому профілі.',
            ],
        ]);

        // Template 7: Manual Delivery Out of Stock
        $manualDeliveryOutOfStockTemplate = EmailTemplate::firstOrCreate(
            ['code' => 'manual_delivery_out_of_stock'],
            ['name' => 'Manual Delivery Out of Stock']
        );
        $manualDeliveryOutOfStockTemplate->update(['name' => 'Manual Delivery Out of Stock']);

        $this->saveTranslations($manualDeliveryOutOfStockTemplate, [
            'title' => [
                'ru' => 'Товар временно отсутствует - заказ в ожидании',
                'en' => 'Product temporarily out of stock - order on hold',
                'uk' => 'Товар тимчасово відсутній - замовлення в очікуванні',
            ],
            'message' => [
                'ru' => 'Здравствуйте! При обработке вашего заказа #{{order_number}} на товар "{{product_title}}" обнаружена временная нехватка товара на складе. Ваш заказ переведен в статус ожидания товара. Менеджер уведомлен и работает над решением вопроса. Мы автоматически уведомим вас, как только товар появится в наличии, и менеджер обработает ваш заказ. Если у вас есть вопросы, вы можете связаться с менеджером через раздел "Мои покупки" в личном кабинете. Приносим извинения за неудобства и благодарим за понимание.',
                'en' => 'Hello! During processing of your order #{{order_number}} for "{{product_title}}", a temporary shortage of the product in stock was detected. Your order has been placed on hold waiting for stock. The manager has been notified and is working on resolving the issue. We will automatically notify you as soon as the product becomes available, and the manager will process your order. If you have any questions, you can contact the manager through the "My Purchases" section in your personal account. We apologize for the inconvenience and thank you for your understanding.',
                'uk' => 'Вітаємо! Під час обробки вашого замовлення #{{order_number}} на товар "{{product_title}}" виявлено тимчасову нестачу товару на складі. Ваше замовлення переведено в статус очікування товару. Менеджер сповіщений і працює над вирішенням питання. Ми автоматично повідомимо вас, як тільки товар з\'явиться в наявності, і менеджер обробить ваше замовлення. Якщо у вас є питання, ви можете зв\'язатися з менеджером через розділ "Мої покупки" в особистому кабінеті. Вибачте за незручності та дякуємо за розуміння.',
            ],
        ]);

        // Template: Manual Delivery Processing Error
        $processingErrorTemplate = EmailTemplate::firstOrCreate(
            ['code' => 'manual_delivery_processing_error'],
            ['name' => 'Manual Delivery Processing Error']
        );
        $processingErrorTemplate->update(['name' => 'Manual Delivery Processing Error']);

        $this->saveTranslations($processingErrorTemplate, [
            'title' => [
                'ru' => 'Ошибка при обработке заказа',
                'en' => 'Order processing error',
                'uk' => 'Помилка при обробці замовлення',
            ],
            'message' => [
                'ru' => 'Здравствуйте! При обработке вашего заказа #{{order_number}} на товар "{{product_title}}" возникла ошибка: {{error_message}}. Менеджер уведомлен и работает над решением проблемы. Мы свяжемся с вами в ближайшее время. Приносим извинения за неудобства.',
                'en' => 'Hello! An error occurred while processing your order #{{order_number}} for "{{product_title}}": {{error_message}}. The manager has been notified and is working on resolving the issue. We will contact you shortly. We apologize for the inconvenience.',
                'uk' => 'Вітаємо! Під час обробки вашого замовлення #{{order_number}} на товар "{{product_title}}" виникла помилка: {{error_message}}. Менеджер сповіщений і працює над вирішенням проблеми. Ми зв\'яжемося з вами найближчим часом. Вибачте за незручності.',
            ],
        ]);
    }

    private function saveTranslations(EmailTemplate $template, array $translations): void
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

