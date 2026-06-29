<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\Category;
use App\Models\CategoryTranslation;
use Illuminate\Database\Seeder;

class ArticlesSeeder extends Seeder
{
    /**
     * Демо-статьи для блока «Полезные статьи».
     * Идемпотентен: повторный запуск не создаёт дубли.
     */
    public function run(): void
    {
        if (Article::query()->exists()) {
            $this->command?->info('Статьи уже существуют — пропускаю ArticlesSeeder.');
            return;
        }

        // Карта родительских товарных категорий по русскому названию -> id
        $catByName = CategoryTranslation::query()
            ->where('code', 'name')
            ->where('locale', 'ru')
            ->whereIn('category_id', Category::query()->where('type', 'product')->whereNull('parent_id')->pluck('id'))
            ->pluck('category_id', 'value');

        $pick = function (array $names) use ($catByName): array {
            $ids = [];
            foreach ($names as $n) {
                if (isset($catByName[$n])) {
                    $ids[] = $catByName[$n];
                }
            }
            return $ids;
        };

        $articles = [
            [
                'img' => '/img/articles/guide.svg',
                'days_ago' => 2,
                'categories' => $pick(['Игровые аккаунты', 'Стриминговые сервисы']),
                'tr' => [
                    'ru' => [
                        'title' => 'Как безопасно купить цифровой аккаунт: пошаговое руководство',
                        'short' => 'Разбираем весь путь покупки — от выбора товара до получения данных, и на что обратить внимание, чтобы сделка была безопасной.',
                        'content' => "Покупка цифрового аккаунта занимает несколько минут, но пара простых правил сэкономит вам нервы и деньги.\n\n1. Выбирайте товары с понятным описанием и актуальным наличием. Обращайте внимание на способ выдачи: автоматическая — данные приходят моментально, ручная — в течение 1–24 часов.\n\n2. Используйте только встроенные способы оплаты. Все платежи проходят через защищённые шлюзы, а история заказа остаётся в вашем профиле.\n\n3. Сразу после получения проверьте доступ и при необходимости смените пароль и привязки. Если что-то не так — обращайтесь в поддержку, действует гарантия замены.",
                    ],
                    'uk' => [
                        'title' => 'Як безпечно купити цифровий акаунт: покрокова інструкція',
                        'short' => 'Розбираємо весь шлях покупки — від вибору товару до отримання даних, і на що звернути увагу для безпечної угоди.',
                        'content' => "Купівля цифрового акаунту займає кілька хвилин, але кілька простих правил збережуть вам нерви та гроші.\n\n1. Обирайте товари зі зрозумілим описом та актуальною наявністю. Зважайте на спосіб видачі: автоматичний — дані надходять миттєво, ручний — протягом 1–24 годин.\n\n2. Використовуйте лише вбудовані способи оплати. Усі платежі проходять через захищені шлюзи, а історія замовлення залишається у вашому профілі.\n\n3. Одразу після отримання перевірте доступ і за потреби змініть пароль та прив'язки. Якщо щось не так — звертайтеся в підтримку, діє гарантія заміни.",
                    ],
                    'en' => [
                        'title' => 'How to Safely Buy a Digital Account: A Step-by-Step Guide',
                        'short' => 'We walk through the full purchase journey — from picking a product to receiving the details — and what to check for a safe deal.',
                        'content' => "Buying a digital account takes a few minutes, but a couple of simple rules will save you time and money.\n\n1. Choose products with a clear description and up-to-date stock. Note the delivery type: automatic means details arrive instantly, manual within 1–24 hours.\n\n2. Use only the built-in payment methods. Every payment goes through secured gateways, and the order history stays in your profile.\n\n3. Right after delivery, verify access and change the password and linked data if needed. If anything is off, contact support — the replacement warranty applies.",
                    ],
                ],
            ],
            [
                'img' => '/img/articles/warranty.svg',
                'days_ago' => 5,
                'categories' => $pick(['Игровые аккаунты']),
                'tr' => [
                    'ru' => [
                        'title' => 'Гарантия и замена: как это работает на Account Arena',
                        'short' => 'Что покрывает гарантия, в какие сроки можно запросить замену и как ускорить решение вопроса.',
                        'content' => "Мы гарантируем валидность аккаунта на момент покупки. Это значит, что выданные данные рабочие и соответствуют описанию товара.\n\nЕсли доступ не работает сразу после покупки, оформите запрос на замену через поддержку и приложите детали заказа. В большинстве случаев замена выдаётся в течение нескольких минут.\n\nГарантия не распространяется на случаи, когда покупатель сам изменил данные восстановления и не сохранил их. Поэтому сразу после получения фиксируйте все изменения.",
                    ],
                    'uk' => [
                        'title' => 'Гарантія та заміна: як це працює на Account Arena',
                        'short' => 'Що покриває гарантія, у які терміни можна запросити заміну та як прискорити вирішення питання.',
                        'content' => "Ми гарантуємо валідність акаунту на момент покупки. Це означає, що видані дані робочі та відповідають опису товару.\n\nЯкщо доступ не працює одразу після покупки, оформіть запит на заміну через підтримку та додайте деталі замовлення. У більшості випадків заміна видається протягом кількох хвилин.\n\nГарантія не поширюється на випадки, коли покупець сам змінив дані відновлення та не зберіг їх. Тому одразу після отримання фіксуйте всі зміни.",
                    ],
                    'en' => [
                        'title' => 'Warranty and Replacement: How It Works on Account Arena',
                        'short' => 'What the warranty covers, the window to request a replacement, and how to speed up resolution.',
                        'content' => "We guarantee the account's validity at the moment of purchase. That means the delivered details work and match the product description.\n\nIf access fails right after the purchase, submit a replacement request through support with your order details. In most cases a replacement is issued within minutes.\n\nThe warranty does not cover cases where the buyer changed the recovery data and didn't save it. So record every change right after delivery.",
                    ],
                ],
            ],
            [
                'img' => '/img/articles/payments.svg',
                'days_ago' => 8,
                'categories' => $pick([]),
                'tr' => [
                    'ru' => [
                        'title' => 'Способы оплаты: криптовалюта, карты и электронные кошельки',
                        'short' => 'Сравниваем доступные методы оплаты по скорости, комиссиям и анонимности — чтобы вы выбрали удобный.',
                        'content' => "Account Arena поддерживает несколько способов оплаты, и у каждого свои плюсы.\n\nКриптовалюта — самый быстрый и приватный вариант. Платёж подтверждается в сети, после чего товар с автоматической выдачей приходит мгновенно.\n\nБанковские карты удобны привычностью: оплата в пару кликов без установки кошельков.\n\nЭлектронные кошельки — золотая середина между скоростью и удобством. Все методы защищены и проходят через проверенные платёжные шлюзы.",
                    ],
                    'uk' => [
                        'title' => 'Способи оплати: криптовалюта, картки та електронні гаманці',
                        'short' => 'Порівнюємо доступні методи оплати за швидкістю, комісіями та анонімністю — щоб ви обрали зручний.',
                        'content' => "Account Arena підтримує кілька способів оплати, і кожен має свої переваги.\n\nКриптовалюта — найшвидший і найприватніший варіант. Платіж підтверджується в мережі, після чого товар з автоматичною видачею надходить миттєво.\n\nБанківські картки зручні звичністю: оплата в пару кліків без встановлення гаманців.\n\nЕлектронні гаманці — золота середина між швидкістю та зручністю. Усі методи захищені та проходять через перевірені платіжні шлюзи.",
                    ],
                    'en' => [
                        'title' => 'Payment Methods: Crypto, Cards and E-Wallets',
                        'short' => 'We compare the available payment methods by speed, fees and privacy so you can pick the most convenient one.',
                        'content' => "Account Arena supports several payment methods, each with its own advantages.\n\nCryptocurrency is the fastest and most private option. The payment is confirmed on-chain, after which auto-delivered products arrive instantly.\n\nBank cards are convenient through familiarity: pay in a couple of clicks without installing wallets.\n\nE-wallets are the sweet spot between speed and convenience. All methods are secured and processed through trusted payment gateways.",
                    ],
                ],
            ],
            [
                'img' => '/img/articles/streaming.svg',
                'days_ago' => 12,
                'categories' => $pick(['Стриминговые сервисы']),
                'tr' => [
                    'ru' => [
                        'title' => 'Netflix, Spotify, YouTube: как выбрать подписку под себя',
                        'short' => 'Чем отличаются тарифы стриминговых сервисов и как не переплатить за функции, которыми не пользуетесь.',
                        'content' => "Стриминговые подписки экономят, если выбрать тариф под свои привычки.\n\nNetflix различается по качеству и числу устройств: Standard хватит для одного-двух экранов в Full HD, Premium нужен для 4K и большой семьи.\n\nSpotify Premium убирает рекламу и открывает офлайн-режим; Family выгоден, если слушают несколько человек.\n\nYouTube Premium — это отсутствие рекламы, фоновое воспроизведение и YouTube Music в комплекте. Берите более длинные сроки — они почти всегда дешевле в пересчёте на месяц.",
                    ],
                    'uk' => [
                        'title' => 'Netflix, Spotify, YouTube: як обрати підписку під себе',
                        'short' => 'Чим відрізняються тарифи стрімінгових сервісів і як не переплатити за функції, якими не користуєтесь.',
                        'content' => "Стрімінгові підписки заощаджують, якщо обрати тариф під свої звички.\n\nNetflix різниться за якістю та кількістю пристроїв: Standard вистачить для одного-двох екранів у Full HD, Premium потрібен для 4K і великої родини.\n\nSpotify Premium прибирає рекламу та відкриває офлайн-режим; Family вигідний, якщо слухають кілька людей.\n\nYouTube Premium — це відсутність реклами, фонове відтворення та YouTube Music у комплекті. Беріть довші терміни — вони майже завжди дешевші в перерахунку на місяць.",
                    ],
                    'en' => [
                        'title' => 'Netflix, Spotify, YouTube: How to Choose the Right Plan',
                        'short' => 'How streaming tiers differ and how to avoid paying for features you never use.',
                        'content' => "Streaming subscriptions save money when the plan fits your habits.\n\nNetflix varies by quality and device count: Standard is enough for one or two Full HD screens, Premium is for 4K and a large family.\n\nSpotify Premium removes ads and unlocks offline mode; Family is worth it when several people listen.\n\nYouTube Premium means no ads, background playback and YouTube Music included. Pick longer terms — they're almost always cheaper per month.",
                    ],
                ],
            ],
            [
                'img' => '/img/articles/gaming.svg',
                'days_ago' => 17,
                'categories' => $pick(['Игровые аккаунты']),
                'tr' => [
                    'ru' => [
                        'title' => 'Steam и игровые аккаунты: на что смотреть перед покупкой',
                        'short' => 'Библиотека, регион, статус Prime и привязки — короткий чек-лист, который убережёт от неприятных сюрпризов.',
                        'content' => "Игровой аккаунт — это не только список игр, но и его «здоровье».\n\nПроверьте библиотеку и регион: некоторые игры и цены привязаны к стране аккаунта.\n\nДля CS обращайте внимание на статус Prime и отсутствие банов — это влияет на матчмейкинг.\n\nПосле покупки сразу привяжите свою почту и включите двухфакторную защиту. Так аккаунт станет полностью вашим, а доступ — безопасным.",
                    ],
                    'uk' => [
                        'title' => 'Steam та ігрові акаунти: на що дивитися перед покупкою',
                        'short' => 'Бібліотека, регіон, статус Prime та прив\'язки — короткий чек-лист, що вбереже від неприємних сюрпризів.',
                        'content' => "Ігровий акаунт — це не лише список ігор, а і його «здоров'я».\n\nПеревірте бібліотеку та регіон: деякі ігри та ціни прив'язані до країни акаунту.\n\nДля CS звертайте увагу на статус Prime та відсутність банів — це впливає на матчмейкінг.\n\nПісля покупки одразу прив'яжіть свою пошту та увімкніть двофакторний захист. Так акаунт стане повністю вашим, а доступ — безпечним.",
                    ],
                    'en' => [
                        'title' => 'Steam and Gaming Accounts: What to Check Before Buying',
                        'short' => 'Library, region, Prime status and links — a short checklist that saves you from unpleasant surprises.',
                        'content' => "A gaming account isn't just a list of games — its health matters too.\n\nCheck the library and region: some games and prices are tied to the account's country.\n\nFor CS, watch the Prime status and the absence of bans — it affects matchmaking.\n\nRight after the purchase, link your own email and enable two-factor protection. That makes the account fully yours and the access secure.",
                    ],
                ],
            ],
            [
                'img' => '/img/articles/telegram.svg',
                'days_ago' => 23,
                'categories' => $pick(['Социальные сети']),
                'tr' => [
                    'ru' => [
                        'title' => 'Telegram Premium: все возможности и стоит ли он того',
                        'short' => 'Большие лимиты, эксклюзивные стикеры и скорость — разбираем, что даёт Premium и кому он действительно нужен.',
                        'content' => "Telegram Premium расширяет привычный мессенджер сразу по нескольким направлениям.\n\nЛимит загрузки файлов вырастает до 4 ГБ, скорость скачивания снимается с ограничений, а места для каналов и папок становится больше.\n\nДобавляются эксклюзивные стикеры и реакции, анимированные аватары и значок Premium.\n\nЕсли вы активно пользуетесь Telegram для работы и контента — подписка окупается удобством. Для редких переписок хватит и бесплатной версии.",
                    ],
                    'uk' => [
                        'title' => 'Telegram Premium: усі можливості та чи вартий він того',
                        'short' => 'Великі ліміти, ексклюзивні стікери та швидкість — розбираємо, що дає Premium і кому він справді потрібен.',
                        'content' => "Telegram Premium розширює звичний месенджер одразу за кількома напрямами.\n\nЛіміт завантаження файлів зростає до 4 ГБ, швидкість завантаження знімається з обмежень, а місця для каналів і папок стає більше.\n\nДодаються ексклюзивні стікери та реакції, анімовані аватари і значок Premium.\n\nЯкщо ви активно користуєтесь Telegram для роботи та контенту — підписка окупається зручністю. Для рідкісного листування вистачить і безкоштовної версії.",
                    ],
                    'en' => [
                        'title' => 'Telegram Premium: Every Feature and Whether It\'s Worth It',
                        'short' => 'Bigger limits, exclusive stickers and speed — we break down what Premium offers and who actually needs it.',
                        'content' => "Telegram Premium expands the familiar messenger across several fronts at once.\n\nThe file upload limit grows to 4 GB, download speed is uncapped, and you get more room for channels and folders.\n\nYou also get exclusive stickers and reactions, animated avatars and the Premium badge.\n\nIf you use Telegram heavily for work and content, the subscription pays off in convenience. For occasional chats, the free version is plenty.",
                    ],
                ],
            ],
        ];

        foreach ($articles as $data) {
            /** @var Article $article */
            $article = Article::create([
                'img' => $data['img'],
                'status' => 'published',
                'created_at' => now()->subDays($data['days_ago']),
            ]);

            foreach ($data['tr'] as $locale => $fields) {
                foreach (['title', 'short', 'content'] as $code) {
                    ArticleTranslation::create([
                        'article_id' => $article->id,
                        'locale' => $locale,
                        'code' => $code,
                        'value' => $fields[$code],
                    ]);
                }
                // Мета-поля для SEO на основе заголовка/описания
                ArticleTranslation::create([
                    'article_id' => $article->id,
                    'locale' => $locale,
                    'code' => 'meta_title',
                    'value' => $fields['title'],
                ]);
                ArticleTranslation::create([
                    'article_id' => $article->id,
                    'locale' => $locale,
                    'code' => 'meta_description',
                    'value' => $fields['short'],
                ]);
            }

            if (!empty($data['categories'])) {
                $article->categories()->syncWithoutDetaching($data['categories']);
            }
        }

        $this->command?->info('Создано демо-статей: ' . count($articles));
    }
}
