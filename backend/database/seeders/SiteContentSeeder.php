<?php

namespace Database\Seeders;

use App\Models\Option;
use Illuminate\Database\Seeder;

class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        // Hero Section
        Option::set('hero_title_ru', 'Магазин <span class="gradient-text">цифровых товаров</span> и <span class="gradient-text bg-gradient-1">премиум аккаунтов</span>');
        Option::set('hero_title_en', 'Digital Goods Store & <span class="gradient-text">Premium Accounts</span>');
        Option::set('hero_title_uk', 'Магазин <span class="gradient-text">цифрових товарів</span> та <span class="gradient-text bg-gradient-1">преміум акаунтів</span>');
        
        Option::set('hero_description_ru', 'Купите готовые аккаунты, ключи, лицензии и цифровые товары по лучшим ценам. Мгновенная доставка, гарантия качества и безопасные платежи. Начните покупки прямо сейчас!');
        Option::set('hero_description_en', 'Buy ready-made accounts, keys, licenses, and digital products at the best prices. Instant delivery, quality guarantee, and secure payments. Start shopping right now!');
        Option::set('hero_description_uk', 'Купіть готові акаунти, ключі, ліцензії та цифрові товари за найкращими цінами. Миттєва доставка, гарантія якості та безпечні платежі. Почніть покупки прямо зараз!');
        
        Option::set('hero_button_ru', 'Перейти к каталогу');
        Option::set('hero_button_en', 'Go to Catalog');
        Option::set('hero_button_uk', 'Перейти до каталогу');

        // About Section
        Option::set('about_title_ru', 'О нас');
        Option::set('about_title_en', 'About Us');
        Option::set('about_title_uk', 'Про нас');
        
        Option::set('about_description_ru', 'Мы специализируемся на продаже цифровых товаров: премиум аккаунтов, лицензий, ключей активации и другой цифровой продукции. Что мы предлагаем: ✅ Огромный ассортимент ✅ Быстрая доставка ✅ Гарантия качества ✅ Безопасные платежи Наша цель: Сделать цифровые товары доступными каждому по выгодным ценам.');
        Option::set('about_description_en', 'We specialize in selling digital goods: premium accounts, licenses, activation keys, and other digital products. What we offer: ✅ Huge selection ✅ Fast delivery ✅ Quality guarantee ✅ Secure payments Our goal: Make digital goods accessible to everyone at great prices.');
        Option::set('about_description_uk', 'Ми спеціалізуємося на продажу цифрових товарів: преміум акаунтів, ліцензій, ключів активації та іншої цифрової продукції. Що ми пропонуємо: ✅ Величезний асортимент ✅ Швидка доставка ✅ Гарантія якості ✅ Безпечні платежі Наша мета: Зробити цифрові товари доступними кожному за вигідними цінами.');

        // Promote Section - Title
        Option::set('promote_title_ru', 'Почему выбирают <span class="gradient-text">нашу платформу</span>');
        Option::set('promote_title_en', 'Why choose <span class="gradient-text">our platform</span>');
        Option::set('promote_title_uk', 'Чому обирають <span class="gradient-text">нашу платформу</span>');

        // Promote - Access (Мгновенная доставка)
        Option::set('promote_access_title_ru', 'Мгновенная доставка');
        Option::set('promote_access_title_en', 'Instant Delivery');
        Option::set('promote_access_title_uk', 'Миттєва доставка');
        Option::set('promote_access_description_ru', 'Все товары доставляются моментально после оплаты. Не нужно ждать — получите доступ сразу.');
        Option::set('promote_access_description_en', 'All products are delivered instantly after payment. No waiting — get access immediately.');
        Option::set('promote_access_description_uk', 'Усі товари доставляються миттєво після оплати. Не потрібно чекати — отримайте доступ одразу.');

        // Promote - Pricing (Лучшие цены)
        Option::set('promote_pricing_title_ru', 'Лучшие цены');
        Option::set('promote_pricing_title_en', 'Best Prices');
        Option::set('promote_pricing_title_uk', 'Найкращі ціни');
        Option::set('promote_pricing_description_ru', 'Предлагаем конкурентоспособные цены на все категории цифровых товаров без скрытых комиссий.');
        Option::set('promote_pricing_description_en', 'We offer competitive prices on all categories of digital goods without hidden fees.');
        Option::set('promote_pricing_description_uk', 'Пропонуємо конкурентоспроможні ціни на всі категорії цифрових товарів без прихованих комісій.');

        // Promote - Refund (Гарантия качества)
        Option::set('promote_refund_title_ru', 'Гарантия качества');
        Option::set('promote_refund_title_en', 'Quality Guarantee');
        Option::set('promote_refund_title_uk', 'Гарантія якості');
        Option::set('promote_refund_description_ru', 'Мы гарантируем качество каждого товара. Если что-то не так — вернем деньги.');
        Option::set('promote_refund_description_en', 'We guarantee the quality of every product. If something goes wrong — we will refund your money.');
        Option::set('promote_refund_description_uk', 'Ми гарантуємо якість кожного товару. Якщо щось не так — повернемо гроші.');

        // Promote - Activation (Проверенные товары)
        Option::set('promote_activation_title_ru', 'Проверенные товары');
        Option::set('promote_activation_title_en', 'Verified Products');
        Option::set('promote_activation_title_uk', 'Перевірені товари');
        Option::set('promote_activation_description_ru', 'Весь товар проверяется перед продажей, чтобы вы получили только рабочую продукцию.');
        Option::set('promote_activation_description_en', 'All products are verified before sale to ensure you receive only working items.');
        Option::set('promote_activation_description_uk', 'Усі товари перевіряються перед продажем, щоб ви отримали тільки робочу продукцію.');

        // Promote - Support (Поддержка 24/7)
        Option::set('promote_support_title_ru', 'Поддержка 24/7');
        Option::set('promote_support_title_en', '24/7 Support');
        Option::set('promote_support_title_uk', 'Підтримка 24/7');
        Option::set('promote_support_description_ru', 'Наша команда работает круглосуточно — готова помочь на русском, украинском и английском.');
        Option::set('promote_support_description_en', 'Our team works around the clock — ready to help in Russian, Ukrainian, and English.');
        Option::set('promote_support_description_uk', 'Наша команда працює цілодобово — готова допомогти українською, російською та англійською.');

        // Promote - Payment (Безопасные платежи)
        Option::set('promote_payment_title_ru', 'Безопасные платежи');
        Option::set('promote_payment_title_en', 'Secure Payments');
        Option::set('promote_payment_title_uk', 'Безпечні платежі');
        Option::set('promote_payment_description_ru', 'Множество способов оплаты, безопасные транзакции и защита ваших данных.');
        Option::set('promote_payment_description_en', 'Multiple payment methods, secure transactions, and protection of your data.');
        Option::set('promote_payment_description_uk', 'Безліч способів оплати, безпечні транзакції та захист ваших даних.');

        // Steps Section
        Option::set('steps_title_ru', 'Как <br><span class="gradient-text">купить</span> товар в 3 шага');
        Option::set('steps_title_en', 'How to <br><span class="gradient-text">buy</span> in 3 steps');
        Option::set('steps_title_uk', 'Як <br><span class="gradient-text">купити</span> товар у 3 кроки');
        
        Option::set('steps_description_ru', 'Процесс покупки простой и понятный!');
        Option::set('steps_description_en', 'The purchase process is simple and clear!');
        Option::set('steps_description_uk', 'Процес покупки простий і зрозумілий!');
    }
}
