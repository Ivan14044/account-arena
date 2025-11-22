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

