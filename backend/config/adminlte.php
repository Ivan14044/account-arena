<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | Here you can change the default title of your admin panel.
    |
    | For detailed instructions you can look the title section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'title' => 'Админ Панель',
    'title_prefix' => '',
    'title_postfix' => '',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    |
    | Here you can activate the favicon.
    |
    | For detailed instructions you can look the favicon section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_ico_only' => false,
    'use_full_favicon' => false,

    /*
    |--------------------------------------------------------------------------
    | Google Fonts
    |--------------------------------------------------------------------------
    |
    | Here you can allow or not the use of external google fonts. Disabling the
    | google fonts may be useful if your admin panel internet access is
    | restricted somehow.
    |
    | For detailed instructions you can look the google fonts section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'google_fonts' => [
        'allowed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Logo
    |--------------------------------------------------------------------------
    |
    | Here you can change the logo of your admin panel.
    |
    | For detailed instructions you can look the logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'logo' => '<b>Админ</b> Панель',
    'logo_img' => 'vendor/adminlte/dist/img/AdminLTELogo.png',
    'logo_img_class' => 'brand-image img-circle elevation-3',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs',
    'logo_img_alt' => 'Admin Logo',

    /*
    |--------------------------------------------------------------------------
    | Authentication Logo
    |--------------------------------------------------------------------------
    |
    | Here you can setup an alternative logo to use on your login and register
    | screens. When disabled, the admin panel logo will be used instead.
    |
    | For detailed instructions you can look the auth logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'auth_logo' => [
        'enabled' => false,
        'img' => [
            'path' => 'vendor/adminlte/dist/img/AdminLTELogo.png',
            'alt' => 'Auth Logo',
            'class' => '',
            'width' => 50,
            'height' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preloader Animation
    |--------------------------------------------------------------------------
    |
    | Here you can change the preloader animation configuration. Currently, two
    | modes are supported: 'fullscreen' for a fullscreen preloader animation
    | and 'cwrapper' to attach the preloader animation into the content-wrapper
    | element and avoid overlapping it with the sidebars and the top navbar.
    |
    | For detailed instructions you can look the preloader section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'preloader' => [
        'enabled' => false,
        'mode' => 'fullscreen',
        'img' => [
            'path' => 'vendor/adminlte/dist/img/AdminLTELogo.png',
            'alt' => 'AdminLTE Preloader Image',
            'effect' => 'animation__shake',
            'width' => 60,
            'height' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    |
    | Here you can activate and change the user menu.
    |
    | For detailed instructions you can look the user menu section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'usermenu_enabled' => false,
    'usermenu_header' => false,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => false,
    'usermenu_desc' => false,
    'usermenu_profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Here we change the layout of your admin panel.
    |
    | For detailed instructions you can look the layout section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => null,
    'layout_fixed_navbar' => null,
    'layout_fixed_footer' => null,
    'layout_dark_mode' => null,

    /*
    |--------------------------------------------------------------------------
    | Authentication Views Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the authentication views.
    |
    | For detailed instructions you can look the auth classes section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_auth_card' => 'card-outline card-primary',
    'classes_auth_header' => '',
    'classes_auth_body' => '',
    'classes_auth_footer' => '',
    'classes_auth_icon' => '',
    'classes_auth_btn' => 'btn-flat btn-primary',

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the admin panel.
    |
    | For detailed instructions you can look the admin panel classes here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_body' => '',
    'classes_brand' => '',
    'classes_brand_text' => '',
    'classes_content_wrapper' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_sidebar' => 'sidebar-dark-primary elevation-4',
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'navbar-white navbar-light',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container',

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar of the admin panel.
    |
    | For detailed instructions you can look the sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'sidebar_mini' => 'lg',
    'sidebar_collapse' => false,
    'sidebar_collapse_auto_size' => false,
    'sidebar_collapse_remember' => false,
    'sidebar_collapse_remember_no_transition' => true,
    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'l',
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    /*
    |--------------------------------------------------------------------------
    | Control Sidebar (Right Sidebar)
    |--------------------------------------------------------------------------
    |
    | Here we can modify the right sidebar aka control sidebar of the admin panel.
    |
    | For detailed instructions you can look the right sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'right_sidebar' => false,
    'right_sidebar_icon' => 'fas fa-cogs',
    'right_sidebar_theme' => 'dark',
    'right_sidebar_slide' => true,
    'right_sidebar_push' => true,
    'right_sidebar_scrollbar_theme' => 'os-theme-light',
    'right_sidebar_scrollbar_auto_hide' => 'l',

    /*
    |--------------------------------------------------------------------------
    | Custom Navbar
    |--------------------------------------------------------------------------
    */

    'navbar_custom' => [
        [
            'type' => 'notifications',
            'url' => '/supplier/notifications',
            'icon' => 'fas fa-bell',
            'badge_count_url' => '/supplier/notifications/unread-count',
            'can' => 'supplier-only',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | Here we can modify the url settings of the admin panel.
    |
    | For detailed instructions you can look the urls section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_route_url' => false,
    'dashboard_url' => 'admin',
    'logout_url' => 'logout',
    'login_url' => 'login',
    'register_url' => 'register',
    'password_reset_url' => false,
    'password_email_url' => false,
    'profile_url' => false,
    'disable_darkmode_routes' => false,

    /*
    |--------------------------------------------------------------------------
    | Laravel Asset Bundling
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Laravel Asset Bundling option for the admin panel.
    | Currently, the next modes are supported: 'mix', 'vite' and 'vite_js_only'.
    | When using 'vite_js_only', it's expected that your CSS is imported using
    | JavaScript. Typically, in your application's 'resources/js/app.js' file.
    | If you are not using any of these, leave it as 'false'.
    |
    | For detailed instructions you can look the asset bundling section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'laravel_asset_bundling' => false,
    'laravel_css_path' => 'css/app.css',
    'laravel_js_path' => 'js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar/top navigation of the admin panel.
    |
    | For detailed instructions you can look here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'menu' => [
        // Navbar items:
        [
            'type' => 'fullscreen-widget',
            'topnav_right' => true,
        ],
        [
            'type' => 'navbar-notification',
            'id' => 'my-notification',
            'icon' => 'far fa-bell',
            'label_color' => 'primary',
            'route' => 'admin.admin_notifications.index',
            'topnav_right' => true,
            'dropdown_mode' => true,
            'dropdown_flabel' => 'Все уведомления',
            'update_cfg' => [
                'route' => 'admin.admin_notifications.get',
                'period' => 30,
            ],
            'can' => 'admin-only', // Скрываем для поставщиков
        ],

        // Sidebar items (ADMIN ONLY):
        [
            'text' => 'Панель управления',
            'url' => 'admin',
            'icon' => 'fas fa-fw mr-1 fa-tachometer-alt',
            'can' => 'admin-only',
        ],
        ['header' => 'ОПЕРАЦИИ', 'can' => 'admin-only'],
        [
            'text' => 'Выдача товаров',
            'url' => 'admin/manual-delivery',
            'icon' => 'fas fa-fw mr-1 fa-hand-paper',
            'active' => ['admin/manual-delivery*'],
            'can' => 'main-admin',
            'id' => 'manual-delivery-count',
        ],
        [
            'text' => 'Претензии на товары',
            'active' => ['admin/disputes*'],
            'can' => 'admin-only',
            'url' => 'admin/disputes',
            'icon' => 'fas fa-fw mr-1 fa-exclamation-triangle',
            'id' => 'disputes-unread-count',
        ],
        [
            'text' => 'Чат поддержки',
            'active' => ['admin/support-chats*'],
            'can' => 'admin-only',
            'url' => 'admin/support-chats',
            'icon' => 'fas fa-fw mr-1 fa-comments',
            'id' => 'support-chats-unread-count',
        ],
        ['header' => 'УПРАВЛЕНИЕ', 'can' => 'admin-only'],
        [
            'text' => 'Пользователи',
            'url' => 'admin/users',
            'icon' => 'fas fa-fw mr-1 fa-users',
            'active' => ['admin/users*'],
            'can' => 'admin-only',
        ],
        [
            'text' => 'Покупки',
            'url' => 'admin/purchases',
            'icon' => 'fas fa-fw mr-1 fa-shopping-cart',
            'active' => ['admin/purchases*'],
            'can' => 'admin-only',
        ],
        [
            'text' => 'Товары',
            'icon' => 'fas fa-fw mr-1 fa-box',
            'can' => 'admin-only',
            'submenu' => [
                [
                    'text' => 'Все товары',
                    'url' => 'admin/service-accounts',
                    'icon' => 'fas fa-fw mr-1 fa-list',
                ],
                [
                    'text' => 'Категории',
                    'url' => 'admin/product-categories',
                    'icon' => 'fas fa-fw mr-1 fa-folder',
                ],
                [
                    'text' => 'Подкатегории',
                    'url' => 'admin/product-subcategories',
                    'icon' => 'fas fa-fw mr-1 fa-folder-open',
                ],
            ],
        ],
        [
            'text' => 'Контент',
            'icon' => 'far fa-fw mr-1 fa-folder-open',
            'can' => 'admin-only',
            'submenu' => [
                [
                    'text' => 'Контент сайта',
                    'url' => 'admin/site-content',
                    'icon' => 'far fa-fw mr-1 fa-file-alt',
                    'active' => ['admin/site-content*'],
                ],
                [
                    'text' => 'Статьи',
                    'url' => 'admin/articles',
                    'icon' => 'far fa-fw mr-1 fa-file-alt',
                    'active' => ['admin/articles*'],
                ],
                [
                    'text' => 'Категории статей',
                    'url' => 'admin/article-categories',
                    'icon' => 'fas fa-fw mr-1 fa-tags',
                    'active' => ['admin/article-categories*'],
                ],
                [
                    'text' => 'Страницы',
                    'url' => 'admin/pages',
                    'icon' => 'far fa-fw mr-1 fa-file',
                    'active' => ['admin/pages*'],
                ],
                [
                    'text' => 'Баннеры',
                    'url' => 'admin/banners',
                    'icon' => 'fas fa-fw mr-1 fa-image',
                    'active' => ['admin/banners*'],
                ],
            ],
        ],
        [
            'text' => 'Маркетинг',
            'icon' => 'fas fa-fw mr-1 fa-tags',
            'can' => 'admin-only',
            'submenu' => [
                [
                    'text' => 'Промокоды',
                    'url' => 'admin/promocodes',
                    'icon' => 'fas fa-fw mr-1 fa-ticket-alt',
                    'active' => ['admin/promocodes*', 'admin/promocode*'],
                ],
                [
                    'text' => 'Ваучеры',
                    'url' => 'admin/vouchers',
                    'icon' => 'fas fa-fw mr-1 fa-gift',
                    'active' => ['admin/vouchers*'],
                ]
            ]
        ],
        [
            'text' => 'Поставщики',
            'icon' => 'fas fa-fw mr-1 fa-user-tie',
            'can' => 'admin-only',
            'submenu' => [
                [
                    'text' => 'Список поставщиков',
                    'url' => 'admin/suppliers',
                    'icon' => 'fas fa-fw mr-1 fa-list',
                    'active' => ['admin/suppliers*'],
                ],
                [
                    'text' => 'Запросы на вывод',
                    'url' => 'admin/withdrawal-requests',
                    'icon' => 'fas fa-fw mr-1 fa-money-bill-wave',
                    'active' => ['admin/withdrawal-requests*'],
                ]
            ],
        ],
        [
            'text' => 'Уведомления',
            'icon' => 'fas fa-fw mr-1 fa-bell',
            'active' => ['admin/notifications*', 'admin/notification-templates*'],
            'can' => 'admin-only',
            'submenu' => [
                [
                    'text' => 'Список уведомлений',
                    'url' => 'admin/notifications',
                    'icon' => 'fas fa-fw mr-1 fa-list',
                    'active' => ['admin/notifications*'],
                ],
                [
                    'text' => 'Шаблоны уведомлений',
                    'url' => 'admin/notification-templates',
                    'icon' => 'fas fa-fw mr-1 fa-file-alt',
                    'active' => ['admin/notification-templates*'],
                ],
                [
                    'text' => 'Email шаблоны',
                    'url' => 'admin/email-templates',
                    'icon' => 'fas fa-fw mr-1 fa-envelope',
                    'active' => ['admin/notification-templates*'],
                ],
            ]
        ],
        ['header' => 'СИСТЕМА','can' => 'admin-only'],
        [
            'text' => 'Настройки',
            'url' => 'admin/settings',
            'can' => 'admin-only',
            'icon' => 'fas fa-fw mr-1 fa-cog',
        ],
        [
            'text' => 'Журнал действий',
            'url' => 'admin/activity-logs',
            'icon' => 'fas fa-fw mr-1 fa-history',
            'can' => 'main-admin',
        ],
        [
            'text' => 'Администраторы',
            'url' => 'admin/admins',
            'icon' => 'fas fa-fw mr-1 fa-user-shield',
            'can' => 'main-admin',
        ],
        [
            'text' => 'Правила покупки',
            'url' => 'admin/purchase-rules',
            'icon' => 'fas fa-fw mr-1 fa-book',
            'can' => 'admin-only',
        ],

        // Sidebar items (Supplier ONLY):
        [
            'text' => 'Панель поставщика',
            'url' => '/supplier',
            'icon' => 'fas fa-fw mr-1 fa-store',
            'can' => 'supplier-only',
        ],

        [
            'text' => 'Мои товары',
            'url' => '/supplier/products',
            'icon' => 'fas fa-fw mr-1 fa-box',
            'active' => ['supplier/products*'],
            'can' => 'supplier-only',
        ],
        [
            'text' => 'Мои заказы',
            'url' => '/supplier/orders',
            'icon' => 'fas fa-fw mr-1 fa-shopping-cart',
            'active' => ['supplier/orders*'],
            'can' => 'supplier-only',
        ],
        [
            'text' => 'Скидки',
            'url' => '/supplier/discounts',
            'icon' => 'fas fa-fw mr-1 fa-percent',
            'active' => ['supplier/discounts*'],
            'can' => 'supplier-only',
        ],
        [
            'text' => 'Вывод средств',
            'url' => '/supplier/withdrawals',
            'icon' => 'fas fa-fw mr-1 fa-wallet',
            'active' => ['supplier/withdrawals*'],
            'can' => 'supplier-only',
        ],
        [
            'text' => 'Претензии на товары',
            'url' => '/supplier/disputes',
            'icon' => 'fas fa-fw mr-1 fa-exclamation-triangle',
            'active' => ['supplier/disputes*'],
            'can' => 'supplier-only',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    |
    | Here we can modify the menu filters of the admin panel.
    |
    | For detailed instructions you can look the menu filters section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    |
    | Here we can modify the plugins used inside the admin panel.
    |
    | For detailed instructions you can look the plugins section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Plugins-Configuration
    |
    */

    'plugins' => [
        'Datatables' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css',
                ],
            ],
        ],
        'Select2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.css',
                ],
            ],
        ],
        'Chartjs' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.bundle.min.js',
                ],
            ],
        ],
        'CustomJs' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => '/assets/admin/js/custom.js',
                ],
            ],
        ],
        'Sweetalert2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/sweetalert2@8',
                ],
            ],
        ],
        'Pace' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/themes/blue/pace-theme-center-radar.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.min.js',
                ],
            ],
        ],
        'DateRangePicker' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/momentjs/latest/moment.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFrame
    |--------------------------------------------------------------------------
    |
    | Here we change the IFrame mode configuration. Note these changes will
    | only apply to the view that extends and enable the IFrame mode.
    |
    | For detailed instructions you can look the iframe mode section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/IFrame-Mode-Configuration
    |
    */

    'iframe' => [
        'default_tab' => [
            'url' => null,
            'title' => null,
        ],
        'buttons' => [
            'close' => true,
            'close_all' => true,
            'close_all_other' => true,
            'scroll_left' => true,
            'scroll_right' => true,
            'fullscreen' => true,
        ],
        'options' => [
            'loading_screen' => 1000,
            'auto_show_new_tab' => true,
            'use_navbar_items' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Livewire support.
    |
    | For detailed instructions you can look the livewire here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'livewire' => false,
];
