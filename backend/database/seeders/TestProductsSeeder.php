<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ServiceAccount;
use App\Models\CategoryTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем категории товаров
        $categories = [
            [
                'name' => 'Игровые аккаунты',
                'name_uk' => 'Ігрові акаунти',
                'name_en' => 'Gaming Accounts',
                'subcategories' => [
                    ['name' => 'Steam', 'name_uk' => 'Steam', 'name_en' => 'Steam'],
                    ['name' => 'Epic Games', 'name_uk' => 'Epic Games', 'name_en' => 'Epic Games'],
                    ['name' => 'Origin', 'name_uk' => 'Origin', 'name_en' => 'Origin'],
                ]
            ],
            [
                'name' => 'Стриминговые сервисы',
                'name_uk' => 'Стрімінгові сервіси',
                'name_en' => 'Streaming Services',
                'subcategories' => [
                    ['name' => 'Netflix', 'name_uk' => 'Netflix', 'name_en' => 'Netflix'],
                    ['name' => 'Spotify', 'name_uk' => 'Spotify', 'name_en' => 'Spotify'],
                    ['name' => 'YouTube Premium', 'name_uk' => 'YouTube Premium', 'name_en' => 'YouTube Premium'],
                ]
            ],
            [
                'name' => 'Социальные сети',
                'name_uk' => 'Соціальні мережі',
                'name_en' => 'Social Networks',
                'subcategories' => [
                    ['name' => 'Instagram', 'name_uk' => 'Instagram', 'name_en' => 'Instagram'],
                    ['name' => 'Telegram', 'name_uk' => 'Telegram', 'name_en' => 'Telegram'],
                ]
            ],
        ];

        $createdCategories = [];

        foreach ($categories as $catData) {
            // Создаем родительскую категорию
            $category = Category::create([
                'type' => 'product',
                'parent_id' => null,
                'image_url' => null,
            ]);

            // Добавляем переводы для категории
            foreach (['ru', 'uk', 'en'] as $locale) {
                $nameField = $locale === 'ru' ? 'name' : "name_{$locale}";
                CategoryTranslation::create([
                    'category_id' => $category->id,
                    'locale' => $locale,
                    'code' => 'name',
                    'value' => $catData[$nameField] ?? $catData['name'],
                ]);
            }

            $createdCategories[$category->id] = [
                'category' => $category,
                'subcategories' => [],
            ];

            // Создаем подкатегории
            foreach ($catData['subcategories'] as $subCatData) {
                $subCategory = Category::create([
                    'type' => 'product',
                    'parent_id' => $category->id,
                    'image_url' => null,
                ]);

                // Добавляем переводы для подкатегории
                foreach (['ru', 'uk', 'en'] as $locale) {
                    $nameField = $locale === 'ru' ? 'name' : "name_{$locale}";
                    CategoryTranslation::create([
                        'category_id' => $subCategory->id,
                        'locale' => $locale,
                        'code' => 'name',
                        'value' => $subCatData[$nameField] ?? $subCatData['name'],
                    ]);
                }

                $createdCategories[$category->id]['subcategories'][] = $subCategory;
            }
        }

        // Получаем ID подкатегории для удобства
        $categoriesArray = array_values($createdCategories);
        $steamCategoryId = $categoriesArray[0]['subcategories'][0]->id ?? null;
        $epicGamesCategoryId = $categoriesArray[0]['subcategories'][1]->id ?? null;
        $originCategoryId = $categoriesArray[0]['subcategories'][2]->id ?? null;
        $netflixCategoryId = $categoriesArray[1]['subcategories'][0]->id ?? null;
        $spotifyCategoryId = $categoriesArray[1]['subcategories'][1]->id ?? null;
        $youtubeCategoryId = $categoriesArray[1]['subcategories'][2]->id ?? null;
        $instagramCategoryId = $categoriesArray[2]['subcategories'][0]->id ?? null;
        $telegramCategoryId = $categoriesArray[2]['subcategories'][1]->id ?? null;

        // Создаем тестовые товары
        $products = [
            // Steam аккаунты - 15 товаров
            [
                'title' => 'Steam аккаунт с CS:GO Prime и Dota 2',
                'title_uk' => 'Steam акаунт з CS:GO Prime та Dota 2',
                'title_en' => 'Steam account with CS:GO Prime and Dota 2',
                'description' => 'Премиум Steam аккаунт с популярными играми CS:GO Prime и Dota 2. Полный доступ ко всем функциям, включая ранговые матчи и торговую площадку. Аккаунт проверен и готов к использованию.',
                'description_uk' => 'Преміум Steam акаунт з популярними іграми CS:GO Prime та Dota 2. Повний доступ до всіх функцій, включаючи рангові матчі та торгову майданчик. Акаунт перевірений і готовий до використання.',
                'description_en' => 'Premium Steam account with popular games CS:GO Prime and Dota 2. Full access to all features, including ranked matches and marketplace. Account verified and ready to use.',
                'price' => 25.99,
                'image_url' => '/img/products/steam-csgo-dota.png',
                'category_id' => $steamCategoryId,
                'accounts_data' => $this->generateAccounts(15),
            ],
            [
                'title' => 'Steam аккаунт с GTA V и Red Dead Redemption 2',
                'title_uk' => 'Steam акаунт з GTA V та Red Dead Redemption 2',
                'title_en' => 'Steam account with GTA V and Red Dead Redemption 2',
                'description' => 'Steam аккаунт с культовыми играми GTA V и Red Dead Redemption 2. Обе игры полностью пройдены, сохранения на месте. Отличное предложение для любителей открытых миров и приключений.',
                'description_uk' => 'Steam акаунт з культовими іграми GTA V та Red Dead Redemption 2. Обидві ігри повністю пройдені, збереження на місці. Чудова пропозиція для любителів відкритих світів та пригод.',
                'description_en' => 'Steam account with iconic games GTA V and Red Dead Redemption 2. Both games fully completed, saves in place. Great offer for open world and adventure lovers.',
                'price' => 29.99,
                'image_url' => '/img/products/steam-gta-rdr2.png',
                'category_id' => $steamCategoryId,
                'accounts_data' => $this->generateAccounts(12),
            ],
            [
                'title' => 'Steam аккаунт с большой библиотекой игр',
                'title_uk' => 'Steam акаунт з великою бібліотекою ігор',
                'title_en' => 'Steam account with large game library',
                'description' => 'Steam аккаунт с огромной библиотекой игр. Более 50 игр в коллекции, включая популярные AAA-тайтлы и инди-хиты. Идеально для тех, кто хочет сразу начать играть без покупок.',
                'description_uk' => 'Steam акаунт з величезною бібліотекою ігор. Понад 50 ігор у колекції, включаючи популярні AAA-тайтли та інди-хіти. Ідеально для тих, хто хоче одразу почати грати без покупок.',
                'description_en' => 'Steam account with huge game library. Over 50 games in collection, including popular AAA titles and indie hits. Perfect for those who want to start playing immediately without purchases.',
                'price' => 45.50,
                'image_url' => '/img/products/steam-library.png',
                'category_id' => $steamCategoryId,
                'accounts_data' => $this->generateAccounts(8),
            ],
            [
                'title' => 'Steam аккаунт с Counter-Strike 2 и инвентарем',
                'title_uk' => 'Steam акаунт з Counter-Strike 2 та інвентарем',
                'title_en' => 'Steam account with Counter-Strike 2 and inventory',
                'description' => 'Steam аккаунт с Counter-Strike 2 и ценным инвентарем. Включает скины оружия, перчатки и другие предметы. Высокий ранг, готов к соревновательной игре.',
                'description_uk' => 'Steam акаунт з Counter-Strike 2 та цінним інвентарем. Включає скіни зброї, рукавички та інші предмети. Високий ранг, готовий до змагальної гри.',
                'description_en' => 'Steam account with Counter-Strike 2 and valuable inventory. Includes weapon skins, gloves and other items. High rank, ready for competitive play.',
                'price' => 34.99,
                'image_url' => '/img/products/steam-cs2.png',
                'category_id' => $steamCategoryId,
                'accounts_data' => $this->generateAccounts(18),
            ],
            [
                'title' => 'Steam аккаунт с Elden Ring и Dark Souls',
                'title_uk' => 'Steam акаунт з Elden Ring та Dark Souls',
                'title_en' => 'Steam account with Elden Ring and Dark Souls',
                'description' => 'Steam аккаунт с легендарными играми серии Souls: Elden Ring, Dark Souls III и Sekiro. Для настоящих ценителей сложных игр и эпических сражений.',
                'description_uk' => 'Steam акаунт з легендарними іграми серії Souls: Elden Ring, Dark Souls III та Sekiro. Для справжніх цінителів складних ігор та епічних битв.',
                'description_en' => 'Steam account with legendary Souls series games: Elden Ring, Dark Souls III and Sekiro. For true connoisseurs of challenging games and epic battles.',
                'price' => 39.99,
                'image_url' => '/img/products/steam-souls.png',
                'category_id' => $steamCategoryId,
                'accounts_data' => $this->generateAccounts(10),
            ],
            [
                'title' => 'Steam аккаунт с Call of Duty и Battlefield',
                'title_uk' => 'Steam акаунт з Call of Duty та Battlefield',
                'title_en' => 'Steam account with Call of Duty and Battlefield',
                'description' => 'Steam аккаунт с популярными шутерами Call of Duty: Modern Warfare и Battlefield 2042. Множество разблокированного контента, оружия и кастомизаций.',
                'description_uk' => 'Steam акаунт з популярними шутерами Call of Duty: Modern Warfare та Battlefield 2042. Безліч розблокованого контенту, зброї та кастомізацій.',
                'description_en' => 'Steam account with popular shooters Call of Duty: Modern Warfare and Battlefield 2042. Lots of unlocked content, weapons and customizations.',
                'price' => 32.50,
                'image_url' => '/img/products/steam-shooters.png',
                'category_id' => $steamCategoryId,
                'accounts_data' => $this->generateAccounts(14),
            ],
            [
                'title' => 'Steam аккаунт с The Witcher 3 и Cyberpunk 2077',
                'title_uk' => 'Steam акаунт з The Witcher 3 та Cyberpunk 2077',
                'title_en' => 'Steam account with The Witcher 3 and Cyberpunk 2077',
                'description' => 'Steam аккаунт с лучшими RPG от CD Projekt RED: The Witcher 3: Wild Hunt (полное издание) и Cyberpunk 2077. Все DLC включены, сохранения на месте.',
                'description_uk' => 'Steam акаунт з найкращими RPG від CD Projekt RED: The Witcher 3: Wild Hunt (повне видання) та Cyberpunk 2077. Всі DLC включені, збереження на місці.',
                'description_en' => 'Steam account with best RPGs from CD Projekt RED: The Witcher 3: Wild Hunt (complete edition) and Cyberpunk 2077. All DLCs included, saves in place.',
                'price' => 36.99,
                'image_url' => '/img/products/steam-witcher-cyberpunk.png',
                'category_id' => $steamCategoryId,
                'accounts_data' => $this->generateAccounts(11),
            ],
            [
                'title' => 'Steam аккаунт с Assassin\'s Creed серией',
                'title_uk' => 'Steam акаунт з серією Assassin\'s Creed',
                'title_en' => 'Steam account with Assassin\'s Creed series',
                'description' => 'Steam аккаунт с коллекцией игр Assassin\'s Creed: Valhalla, Odyssey и Origins. Все игры полностью пройдены, открыты все локации и достижения.',
                'description_uk' => 'Steam акаунт з колекцією ігор Assassin\'s Creed: Valhalla, Odyssey та Origins. Всі ігри повністю пройдені, відкриті всі локації та досягнення.',
                'description_en' => 'Steam account with Assassin\'s Creed collection: Valhalla, Odyssey and Origins. All games fully completed, all locations and achievements unlocked.',
                'price' => 42.00,
                'image_url' => '/img/products/steam-assassins.png',
                'category_id' => $steamCategoryId,
                'accounts_data' => $this->generateAccounts(9),
            ],
            [
                'title' => 'Steam аккаунт с Resident Evil коллекцией',
                'title_uk' => 'Steam акаунт з колекцією Resident Evil',
                'title_en' => 'Steam account with Resident Evil collection',
                'description' => 'Steam аккаунт с полной коллекцией Resident Evil: RE2 Remake, RE3 Remake, RE7, RE8 Village. Для любителей хорроров и выживания. Все игры на максимальных настройках.',
                'description_uk' => 'Steam акаунт з повною колекцією Resident Evil: RE2 Remake, RE3 Remake, RE7, RE8 Village. Для любителів хоррорів та виживання. Всі ігри на максимальних налаштуваннях.',
                'description_en' => 'Steam account with complete Resident Evil collection: RE2 Remake, RE3 Remake, RE7, RE8 Village. For horror and survival lovers. All games on maximum settings.',
                'price' => 38.50,
                'image_url' => '/img/products/steam-resident-evil.png',
                'category_id' => $steamCategoryId,
                'accounts_data' => $this->generateAccounts(13),
            ],
            [
                'title' => 'Steam аккаунт с FIFA и NBA 2K',
                'title_uk' => 'Steam акаунт з FIFA та NBA 2K',
                'title_en' => 'Steam account with FIFA and NBA 2K',
                'description' => 'Steam аккаунт с популярными спортивными симуляторами FIFA 24 и NBA 2K24. Идеально для любителей футбола и баскетбола. Множество разблокированных команд и игроков.',
                'description_uk' => 'Steam акаунт з популярними спортивними симуляторами FIFA 24 та NBA 2K24. Ідеально для любителів футболу та баскетболу. Безліч розблокованих команд та гравців.',
                'description_en' => 'Steam account with popular sports simulators FIFA 24 and NBA 2K24. Perfect for football and basketball lovers. Many unlocked teams and players.',
                'price' => 28.99,
                'image_url' => '/img/products/steam-sports.png',
                'category_id' => $steamCategoryId,
                'accounts_data' => $this->generateAccounts(16),
            ],
            [
                'title' => 'Steam аккаунт с Fallout и Skyrim',
                'title_uk' => 'Steam акаунт з Fallout та Skyrim',
                'title_en' => 'Steam account with Fallout and Skyrim',
                'description' => 'Steam аккаунт с легендарными RPG от Bethesda: The Elder Scrolls V: Skyrim (Special Edition) и Fallout 4. Обе игры с модами и всеми DLC. Сотни часов геймплея.',
                'description_uk' => 'Steam акаунт з легендарними RPG від Bethesda: The Elder Scrolls V: Skyrim (Special Edition) та Fallout 4. Обидві ігри з модами та всіма DLC. Сотні годин геймплею.',
                'description_en' => 'Steam account with legendary RPGs from Bethesda: The Elder Scrolls V: Skyrim (Special Edition) and Fallout 4. Both games with mods and all DLCs. Hundreds of hours of gameplay.',
                'price' => 31.99,
                'image_url' => '/img/products/steam-bethesda.png',
                'category_id' => $steamCategoryId,
                'accounts_data' => $this->generateAccounts(12),
            ],
            [
                'title' => 'Steam аккаунт с Total War и Civilization',
                'title_uk' => 'Steam акаунт з Total War та Civilization',
                'title_en' => 'Steam account with Total War and Civilization',
                'description' => 'Steam аккаунт с стратегиями Total War: Warhammer III и Sid Meier\'s Civilization VI. Для любителей тактики и глобальной стратегии. Все дополнения включены.',
                'description_uk' => 'Steam акаунт з стратегіями Total War: Warhammer III та Sid Meier\'s Civilization VI. Для любителів тактики та глобальної стратегії. Всі доповнення включені.',
                'description_en' => 'Steam account with strategies Total War: Warhammer III and Sid Meier\'s Civilization VI. For tactics and global strategy lovers. All expansions included.',
                'price' => 33.50,
                'image_url' => '/img/products/steam-strategy.png',
                'category_id' => $steamCategoryId,
                'accounts_data' => $this->generateAccounts(10),
            ],
            [
                'title' => 'Steam аккаунт с инди-хитами',
                'title_uk' => 'Steam акаунт з інди-хітами',
                'title_en' => 'Steam account with indie hits',
                'description' => 'Steam аккаунт с коллекцией лучших инди-игр: Hollow Knight, Celeste, Hades, Dead Cells, Stardew Valley. Более 20 качественных инди-тайтлов для разнообразия.',
                'description_uk' => 'Steam акаунт з колекцією найкращих інди-ігор: Hollow Knight, Celeste, Hades, Dead Cells, Stardew Valley. Понад 20 якісних інди-тайтлів для різноманітності.',
                'description_en' => 'Steam account with collection of best indie games: Hollow Knight, Celeste, Hades, Dead Cells, Stardew Valley. Over 20 quality indie titles for variety.',
                'price' => 27.99,
                'image_url' => '/img/products/steam-indie.png',
                'category_id' => $steamCategoryId,
                'accounts_data' => $this->generateAccounts(15),
            ],
            [
                'title' => 'Steam аккаунт с симуляторами',
                'title_uk' => 'Steam акаунт з симуляторами',
                'title_en' => 'Steam account with simulators',
                'description' => 'Steam аккаунт с популярными симуляторами: Euro Truck Simulator 2, Microsoft Flight Simulator, Farming Simulator 22. Реалистичная физика и детализированная графика.',
                'description_uk' => 'Steam акаунт з популярними симуляторами: Euro Truck Simulator 2, Microsoft Flight Simulator, Farming Simulator 22. Реалістична фізика та деталізована графіка.',
                'description_en' => 'Steam account with popular simulators: Euro Truck Simulator 2, Microsoft Flight Simulator, Farming Simulator 22. Realistic physics and detailed graphics.',
                'price' => 29.50,
                'image_url' => '/img/products/steam-simulators.png',
                'category_id' => $steamCategoryId,
                'accounts_data' => $this->generateAccounts(11),
            ],
            [
                'title' => 'Steam аккаунт с кооперативными играми',
                'title_uk' => 'Steam акаунт з кооперативними іграми',
                'title_en' => 'Steam account with co-op games',
                'description' => 'Steam аккаунт с лучшими кооперативными играми: It Takes Two, Overcooked 2, Left 4 Dead 2, Deep Rock Galactic. Идеально для игры с друзьями и семьей.',
                'description_uk' => 'Steam акаунт з найкращими кооперативними іграми: It Takes Two, Overcooked 2, Left 4 Dead 2, Deep Rock Galactic. Ідеально для гри з друзями та родиною.',
                'description_en' => 'Steam account with best co-op games: It Takes Two, Overcooked 2, Left 4 Dead 2, Deep Rock Galactic. Perfect for playing with friends and family.',
                'price' => 26.99,
                'image_url' => '/img/products/steam-coop.png',
                'category_id' => $steamCategoryId,
                'accounts_data' => $this->generateAccounts(17),
            ],
            // Epic Games
            [
                'title' => 'Epic Games аккаунт с Fortnite',
                'title_uk' => 'Epic Games акаунт з Fortnite',
                'title_en' => 'Epic Games account with Fortnite',
                'description' => 'Epic Games аккаунт с игрой Fortnite и боевым пропуском. Множество редких скинов, эмодзи и предметов. Высокий уровень и разблокированный контент.',
                'description_uk' => 'Epic Games акаунт з грою Fortnite та бойовим пропуском. Безліч рідкісних скінів, емодзі та предметів. Високий рівень та розблокований контент.',
                'description_en' => 'Epic Games account with Fortnite game and battle pass. Many rare skins, emotes and items. High level and unlocked content.',
                'price' => 19.99,
                'image_url' => '/img/products/epic-fortnite.png',
                'category_id' => $epicGamesCategoryId,
                'accounts_data' => $this->generateAccounts(20),
            ],
            [
                'title' => 'Epic Games аккаунт с коллекцией игр',
                'title_uk' => 'Epic Games акаунт з колекцією ігор',
                'title_en' => 'Epic Games account with game collection',
                'description' => 'Epic Games аккаунт с большой коллекцией бесплатных игр, полученных через еженедельные раздачи. Более 30 игр в библиотеке, включая популярные тайтлы.',
                'description_uk' => 'Epic Games акаунт з великою колекцією безкоштовних ігор, отриманих через щотижневі роздачі. Понад 30 ігор у бібліотеці, включаючи популярні тайтли.',
                'description_en' => 'Epic Games account with large collection of free games received through weekly giveaways. Over 30 games in library, including popular titles.',
                'price' => 15.00,
                'image_url' => '/img/products/epic-games.png',
                'category_id' => $epicGamesCategoryId,
                'accounts_data' => $this->generateAccounts(25),
            ],
            // Netflix
            [
                'title' => 'Netflix Premium подписка на 1 месяц',
                'title_uk' => 'Netflix Premium підписка на 1 місяць',
                'title_en' => 'Netflix Premium subscription for 1 month',
                'description' => 'Netflix Premium подписка с доступом к 4K контенту и HDR. Без рекламы, все сериалы и фильмы, включая эксклюзивы Netflix. Одновременный просмотр на 4 устройствах.',
                'description_uk' => 'Netflix Premium підписка з доступом до 4K контенту та HDR. Без реклами, всі серіали та фільми, включаючи ексклюзиви Netflix. Одночасний перегляд на 4 пристроях.',
                'description_en' => 'Netflix Premium subscription with 4K content and HDR access. No ads, all series and movies, including Netflix exclusives. Simultaneous viewing on 4 devices.',
                'price' => 12.99,
                'image_url' => '/img/products/netflix-premium.png',
                'category_id' => $netflixCategoryId,
                'accounts_data' => $this->generateAccounts(30),
            ],
            [
                'title' => 'Netflix Standard подписка',
                'title_uk' => 'Netflix Standard підписка',
                'title_en' => 'Netflix Standard subscription',
                'description' => 'Netflix Standard подписка на 1 месяц. HD качество (1080p), доступ ко всему контенту без рекламы. Одновременный просмотр на 2 устройствах. Идеально для небольших семей.',
                'description_uk' => 'Netflix Standard підписка на 1 місяць. HD якість (1080p), доступ до всього контенту без реклами. Одночасний перегляд на 2 пристроях. Ідеально для невеликих родин.',
                'description_en' => 'Netflix Standard subscription for 1 month. HD quality (1080p), access to all content without ads. Simultaneous viewing on 2 devices. Perfect for small families.',
                'price' => 9.99,
                'image_url' => '/img/products/netflix-standard.png',
                'category_id' => $netflixCategoryId,
                'accounts_data' => $this->generateAccounts(35),
            ],
            [
                'title' => 'Netflix Premium на 3 месяца',
                'title_uk' => 'Netflix Premium на 3 місяці',
                'title_en' => 'Netflix Premium for 3 months',
                'description' => 'Netflix Premium подписка на 3 месяца с выгодной экономией. Все преимущества Premium тарифа: 4K, HDR, без рекламы, просмотр на 4 устройствах одновременно.',
                'description_uk' => 'Netflix Premium підписка на 3 місяці з вигідною економією. Всі переваги Premium тарифу: 4K, HDR, без реклами, перегляд на 4 пристроях одночасно.',
                'description_en' => 'Netflix Premium subscription for 3 months with great savings. All Premium benefits: 4K, HDR, no ads, viewing on 4 devices simultaneously.',
                'price' => 35.00,
                'image_url' => '/img/products/netflix-3months.png',
                'category_id' => $netflixCategoryId,
                'accounts_data' => $this->generateAccounts(18),
            ],
            // Spotify
            [
                'title' => 'Spotify Premium на 1 месяц',
                'title_uk' => 'Spotify Premium на 1 місяць',
                'title_en' => 'Spotify Premium for 1 month',
                'description' => 'Spotify Premium подписка на 1 месяц. Без рекламы, высокое качество звука (320 kbps), офлайн прослушивание, неограниченные пропуски треков. Доступ к миллионам песен и подкастов.',
                'description_uk' => 'Spotify Premium підписка на 1 місяць. Без реклами, висока якість звуку (320 kbps), офлайн прослуховування, необмежені пропуски треків. Доступ до мільйонів пісень та подкастів.',
                'description_en' => 'Spotify Premium subscription for 1 month. No ads, high sound quality (320 kbps), offline listening, unlimited track skips. Access to millions of songs and podcasts.',
                'price' => 8.99,
                'image_url' => '/img/products/spotify-premium.png',
                'category_id' => $spotifyCategoryId,
                'accounts_data' => $this->generateAccounts(40),
            ],
            [
                'title' => 'Spotify Family Premium',
                'title_uk' => 'Spotify Family Premium',
                'title_en' => 'Spotify Family Premium',
                'description' => 'Spotify Family Premium подписка для всей семьи на 1 месяц. До 6 отдельных аккаунтов, все премиум функции для каждого. Экономия до 50% по сравнению с индивидуальными подписками.',
                'description_uk' => 'Spotify Family Premium підписка для всієї родини на 1 місяць. До 6 окремих акаунтів, всі преміум функції для кожного. Економія до 50% порівняно з індивідуальними підписками.',
                'description_en' => 'Spotify Family Premium subscription for the whole family for 1 month. Up to 6 separate accounts, all premium features for each. Save up to 50% compared to individual subscriptions.',
                'price' => 14.99,
                'image_url' => '/img/products/spotify-family.png',
                'category_id' => $spotifyCategoryId,
                'accounts_data' => $this->generateAccounts(15),
            ],
            // YouTube Premium
            [
                'title' => 'YouTube Premium подписка',
                'title_uk' => 'YouTube Premium підписка',
                'title_en' => 'YouTube Premium subscription',
                'description' => 'YouTube Premium подписка на 1 месяц. Без рекламы, фоновое воспроизведение видео, доступ к YouTube Music, возможность скачивать видео для офлайн просмотра. Работает на всех устройствах.',
                'description_uk' => 'YouTube Premium підписка на 1 місяць. Без реклами, фонове відтворення відео, доступ до YouTube Music, можливість завантажувати відео для офлайн перегляду. Працює на всіх пристроях.',
                'description_en' => 'YouTube Premium subscription for 1 month. No ads, background video playback, YouTube Music access, ability to download videos for offline viewing. Works on all devices.',
                'price' => 11.99,
                'image_url' => '/img/products/youtube-premium.png',
                'category_id' => $youtubeCategoryId,
                'accounts_data' => $this->generateAccounts(22),
            ],
            // Instagram
            [
                'title' => 'Instagram аккаунт с подписчиками',
                'title_uk' => 'Instagram акаунт з підписниками',
                'title_en' => 'Instagram account with followers',
                'description' => 'Instagram аккаунт с большим количеством активных подписчиков (1000+). Готовый к использованию профиль с качественным контентом. Идеально для бизнеса или личного бренда.',
                'description_uk' => 'Instagram акаунт з великою кількістю активних підписників (1000+). Готовий до використання профіль з якісним контентом. Ідеально для бізнесу або особистого бренду.',
                'description_en' => 'Instagram account with large number of active followers (1000+). Ready-to-use profile with quality content. Perfect for business or personal brand.',
                'price' => 49.99,
                'image_url' => '/img/products/instagram-account.png',
                'category_id' => $instagramCategoryId,
                'accounts_data' => $this->generateAccounts(5),
            ],
            [
                'title' => 'Instagram бизнес аккаунт',
                'title_uk' => 'Instagram бізнес акаунт',
                'title_en' => 'Instagram business account',
                'description' => 'Instagram бизнес аккаунт с расширенной аналитикой, рекламными инструментами и возможностью добавления контактной кнопки. Идеально для маркетинга, продаж и продвижения бренда.',
                'description_uk' => 'Instagram бізнес акаунт з розширеною аналітикою, рекламними інструментами та можливістю додавання контактної кнопки. Ідеально для маркетингу, продажів та просування бренду.',
                'description_en' => 'Instagram business account with advanced analytics, advertising tools and ability to add contact button. Perfect for marketing, sales and brand promotion.',
                'price' => 39.99,
                'image_url' => '/img/products/instagram-business.png',
                'category_id' => $instagramCategoryId,
                'accounts_data' => $this->generateAccounts(8),
            ],
            // Telegram
            [
                'title' => 'Telegram Premium аккаунт',
                'title_uk' => 'Telegram Premium акаунт',
                'title_en' => 'Telegram Premium account',
                'description' => 'Telegram Premium подписка на 1 год. Все премиум функции: увеличенные лимиты загрузки (4GB), эксклюзивные стикеры, анимированные аватары, приоритетная поддержка, отсутствие рекламы в каналах.',
                'description_uk' => 'Telegram Premium підписка на 1 рік. Всі преміум функції: збільшені ліміти завантаження (4GB), ексклюзивні стікери, анімовані аватари, пріоритетна підтримка, відсутність реклами в каналах.',
                'description_en' => 'Telegram Premium subscription for 1 year. All premium features: increased upload limits (4GB), exclusive stickers, animated avatars, priority support, no ads in channels.',
                'price' => 4.99,
                'image_url' => '/img/products/telegram-premium.png',
                'category_id' => $telegramCategoryId,
                'accounts_data' => $this->generateAccounts(50),
            ],
            // Товары без категории для тестирования
            [
                'title' => 'Универсальный цифровой товар',
                'title_uk' => 'Універсальний цифровий товар',
                'title_en' => 'Universal digital product',
                'description' => 'Универсальный цифровой товар для различных целей. Гибкое использование в зависимости от ваших потребностей. Подходит для тестирования и экспериментов.',
                'description_uk' => 'Універсальний цифровий товар для різних цілей. Гнучке використання залежно від ваших потреб. Підходить для тестування та експериментів.',
                'description_en' => 'Universal digital product for various purposes. Flexible use depending on your needs. Suitable for testing and experiments.',
                'price' => 7.50,
                'image_url' => '/img/products/universal.png',
                'category_id' => null,
                'accounts_data' => $this->generateAccounts(10),
            ],
        ];

        foreach ($products as $productData) {
            ServiceAccount::create([
                'sku' => ServiceAccount::generateSku(),
                'title' => $productData['title'],
                'title_uk' => $productData['title_uk'] ?? $productData['title'],
                'title_en' => $productData['title_en'] ?? $productData['title'],
                'description' => $productData['description'],
                'description_uk' => $productData['description_uk'] ?? $productData['description'],
                'description_en' => $productData['description_en'] ?? $productData['description'],
                'price' => $productData['price'],
                'image_url' => $productData['image_url'] ?? null,
                'category_id' => $productData['category_id'],
                'is_active' => true,
                'accounts_data' => $productData['accounts_data'],
                'used' => 0,
                'sort_order' => ServiceAccount::max('sort_order') + 1,
            ]);
        }

        // Добавляем несколько товаров со скидками
        $discountProducts = [
            [
                'title' => 'Steam аккаунт со скидкой 20%',
                'title_uk' => 'Steam акаунт зі знижкою 20%',
                'title_en' => 'Steam account with 20% discount',
                'description' => 'Steam аккаунт с играми. Специальная скидка 20% на ограниченное время. Включает популярные игры и разблокированный контент. Успейте приобрести по выгодной цене!',
                'description_uk' => 'Steam акаунт з іграми. Спеціальна знижка 20% на обмежений час. Включає популярні ігри та розблокований контент. Встигніть придбати за вигідною ціною!',
                'description_en' => 'Steam account with games. Special 20% discount for limited time. Includes popular games and unlocked content. Hurry to buy at a great price!',
                'price' => 30.00,
                'image_url' => '/img/products/steam-discount.png',
                'discount_percent' => 20,
                'discount_start_date' => now(),
                'discount_end_date' => now()->addDays(30),
                'category_id' => $steamCategoryId,
                'accounts_data' => $this->generateAccounts(10),
            ],
            [
                'title' => 'Netflix Premium со скидкой 15%',
                'title_uk' => 'Netflix Premium зі знижкою 15%',
                'title_en' => 'Netflix Premium with 15% discount',
                'description' => 'Netflix Premium подписка со скидкой 15%. Акция действует ограниченное время. Все преимущества Premium тарифа по сниженной цене. Не упустите возможность!',
                'description_uk' => 'Netflix Premium підписка зі знижкою 15%. Акція діє обмежений час. Всі переваги Premium тарифу за зниженою ціною. Не втратьте можливість!',
                'description_en' => 'Netflix Premium subscription with 15% discount. Promotion valid for limited time. All Premium benefits at reduced price. Don\'t miss the opportunity!',
                'price' => 12.99,
                'image_url' => '/img/products/netflix-discount.png',
                'discount_percent' => 15,
                'discount_start_date' => now(),
                'discount_end_date' => now()->addDays(15),
                'category_id' => $netflixCategoryId,
                'accounts_data' => $this->generateAccounts(20),
            ],
        ];

        foreach ($discountProducts as $productData) {
            ServiceAccount::create([
                'sku' => ServiceAccount::generateSku(),
                'title' => $productData['title'],
                'title_uk' => $productData['title_uk'] ?? $productData['title'],
                'title_en' => $productData['title_en'] ?? $productData['title'],
                'description' => $productData['description'],
                'description_uk' => $productData['description_uk'] ?? $productData['description'],
                'description_en' => $productData['description_en'] ?? $productData['description'],
                'price' => $productData['price'],
                'image_url' => $productData['image_url'] ?? null,
                'discount_percent' => $productData['discount_percent'],
                'discount_start_date' => $productData['discount_start_date'],
                'discount_end_date' => $productData['discount_end_date'],
                'category_id' => $productData['category_id'],
                'is_active' => true,
                'accounts_data' => $productData['accounts_data'],
                'used' => 0,
                'sort_order' => ServiceAccount::max('sort_order') + 1,
            ]);
        }

        $this->command->info('Создано тестовых товаров: ' . count($products) + count($discountProducts));
    }

    /**
     * Генерация тестовых аккаунтов
     */
    private function generateAccounts(int $count): array
    {
        $accounts = [];
        for ($i = 0; $i < $count; $i++) {
            $accounts[] = 'user' . Str::random(8) . ':pass' . Str::random(8);
        }
        return $accounts;
    }
}

