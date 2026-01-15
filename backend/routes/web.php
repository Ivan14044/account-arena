<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\PromocodeController;
use App\Http\Controllers\Admin\PromocodeUsageController;
use App\Http\Controllers\Admin\ServiceAccountController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SiteContentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductSubcategoryController;
use App\Http\Controllers\Admin\ArticleCategoryController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Supplier\DashboardController as SupplierDashboardController;
use App\Http\Controllers\Supplier\ProductController as SupplierProductController;
use App\Http\Controllers\Admin\WithdrawalRequestController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\ProductDisputeController;
use App\Http\Controllers\Admin\PurchaseRulesController;
use App\Http\Controllers\Admin\ManualDeliveryController;
use App\Http\Controllers\Supplier\WithdrawalController;
use App\Http\Controllers\Supplier\DisputeController as SupplierDisputeController;
use Illuminate\Support\Facades\Route;

Route::prefix('/admin')
    ->name('admin.')
    ->group(function () {
        // Login routes (no guest middleware to avoid redirect loops)
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login']);

        // Logout БЕЗ audit.admin middleware (только admin.auth)
        // Это предотвращает ошибку 405 Not Allowed при выходе
        Route::middleware(['admin.auth'])->group(function () {
            Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');
        });

        // Все остальные роуты с audit.admin middleware
        Route::middleware(['admin.auth', 'audit.admin'])->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

            Route::resource('users', UserController::class)->except(['show']);
            Route::post('users/{user}/block', [UserController::class, 'block'])->name('users.block');
            Route::post('users/{user}/update-balance', [UserController::class, 'updateBalance'])->name('users.update-balance');

            Route::resource('promocodes', PromocodeController::class)->except(['show']);
            Route::get('promocode-usages', [PromocodeUsageController::class, 'index'])->name('promocode-usages.index');
            Route::delete('promocodes-bulk', [PromocodeController::class, 'bulkDestroy'])->name('promocodes.bulk-destroy');
            Route::resource('pages', PageController::class)->except(['show']);
            Route::resource('notification-templates', NotificationTemplateController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
            Route::resource('notifications', NotificationController::class)->only(['index', 'create', 'store', 'destroy']);
            Route::resource('contents', ContentController::class)->except(['show']);
            Route::get('site-content', [SiteContentController::class, 'index'])->name('site-content.index');
            Route::post('site-content', [SiteContentController::class, 'store'])->name('site-content.store');
            Route::resource('articles', ArticleController::class)->except(['show']);
            Route::resource('product-categories', ProductCategoryController::class)->except(['show']);
            Route::resource('product-subcategories', ProductSubcategoryController::class)->except(['show']);
            Route::resource('article-categories', ArticleCategoryController::class)->except(['show']);
            Route::resource('banners', BannerController::class)->except(['show']);
            Route::resource('email-templates', EmailTemplateController::class);
            Route::post('email-templates/{email_template}/send-test', [EmailTemplateController::class, 'sendTest'])->name('email-templates.send-test');
            Route::resource('settings', SettingController::class)->only(['index', 'store']);
            Route::post('settings/test-smtp', [SettingController::class, 'testSmtp'])->name('settings.test-smtp');
            Route::get('settings/notification-check', [SettingController::class, 'getNotificationSettings'])->name('settings.notification-check');
            Route::resource('service-accounts', ServiceAccountController::class)->except(['show']);
            Route::get('service-accounts/{serviceAccount}/export', [ServiceAccountController::class, 'export'])->name('service-accounts.export');
            Route::post('service-accounts/{serviceAccount}/import', [ServiceAccountController::class, 'import'])->name('service-accounts.import');
            Route::post('service-accounts/bulk-action', [ServiceAccountController::class, 'bulkAction'])->name('service-accounts.bulk-action');
            
            // Модерация товаров поставщика
            Route::get('product-moderation', [\App\Http\Controllers\Admin\ProductModerationController::class, 'index'])->name('product-moderation.index');
            Route::get('product-moderation/{product}', [\App\Http\Controllers\Admin\ProductModerationController::class, 'show'])->name('product-moderation.show');
            Route::post('product-moderation/{product}/approve', [\App\Http\Controllers\Admin\ProductModerationController::class, 'approve'])->name('product-moderation.approve');
            Route::post('product-moderation/{product}/reject', [\App\Http\Controllers\Admin\ProductModerationController::class, 'reject'])->name('product-moderation.reject');
            Route::post('service-accounts/upload-image', [ServiceAccountController::class, 'uploadImage'])->name('service-accounts.upload-image');
            Route::post('service-accounts/update-sort-order', [ServiceAccountController::class, 'updateSortOrder'])->name('service-accounts.update-sort-order');
            Route::post('service-accounts/apply-sort-order', [ServiceAccountController::class, 'applySortOrder'])->name('service-accounts.apply-sort-order');
            Route::resource('vouchers', VoucherController::class);

            // Purchases (покупки товаров)
            Route::resource('purchases', PurchaseController::class)->only(['index', 'show', 'destroy']);

            // Manual Delivery - счетчик для badge и статистика (доступны всем админам)
            Route::get('manual-delivery/count', [ManualDeliveryController::class, 'getPendingCount'])->name('manual-delivery.count');
            Route::get('manual-delivery/statistics', [ManualDeliveryController::class, 'statistics'])->name('manual-delivery.statistics');
            
            // Manual Delivery (ручная обработка заказов) - только для главного администратора
            Route::middleware(['admin.main'])->group(function () {
                Route::get('manual-delivery', [ManualDeliveryController::class, 'index'])->name('manual-delivery.index');
                Route::get('manual-delivery/{purchase}', [ManualDeliveryController::class, 'show'])->name('manual-delivery.show');
                Route::post('manual-delivery/{purchase}/process', [ManualDeliveryController::class, 'process'])->name('manual-delivery.process');
            });

            // Purchase Rules (правила покупки)
            Route::get('purchase-rules', [PurchaseRulesController::class, 'index'])->name('purchase-rules.index');
            Route::post('purchase-rules', [PurchaseRulesController::class, 'store'])->name('purchase-rules.store');

            // Admin notifications
            Route::get('admin_notifications/get', [AdminNotificationController::class, 'get'])->name('admin_notifications.get');
            Route::get('admin_notifications/read/{id}', [AdminNotificationController::class, 'read'])->name('admin_notifications.read');
            Route::post('admin_notifications/read-all', [AdminNotificationController::class, 'readAll'])->name('admin_notifications.read-all');
            Route::resource('admin_notifications', AdminNotificationController::class)
                ->only(['index', 'destroy'])
                ->parameters(['admin_notifications' => 'id']);
            

            Route::middleware(['admin.main'])->group(function () {
                Route::resource('admins', AdminController::class)->except(['show']);
                Route::post('admins/{admin}/block', [AdminController::class, 'block'])->name('admins.block');
            });

            // Supplier management
            Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
            Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
            Route::get('suppliers-settings', [SupplierController::class, 'settings'])->name('suppliers.settings');
            Route::post('suppliers-settings', [SupplierController::class, 'updateSettings'])->name('suppliers.settings.update');

            // Withdrawal requests management
            Route::get('withdrawal-requests', [WithdrawalRequestController::class, 'index'])->name('withdrawal-requests.index');
            Route::get('withdrawal-requests/{withdrawalRequest}', [WithdrawalRequestController::class, 'show'])->name('withdrawal-requests.show');
            Route::post('withdrawal-requests/{withdrawalRequest}/approve', [WithdrawalRequestController::class, 'approve'])->name('withdrawal-requests.approve');
            Route::post('withdrawal-requests/{withdrawalRequest}/reject', [WithdrawalRequestController::class, 'reject'])->name('withdrawal-requests.reject');
            Route::post('withdrawal-requests/{withdrawalRequest}/mark-paid', [WithdrawalRequestController::class, 'markAsPaid'])->name('withdrawal-requests.mark-paid');

            // Product disputes management
            Route::get('disputes', [ProductDisputeController::class, 'index'])->name('disputes.index');
            Route::get('disputes/new-count', [ProductDisputeController::class, 'getNewCount'])->name('disputes.new-count');
            Route::get('disputes/{dispute}', [ProductDisputeController::class, 'show'])->name('disputes.show');
            Route::patch('disputes/{dispute}/mark-in-review', [ProductDisputeController::class, 'markInReview'])->name('disputes.mark-in-review');
            Route::post('disputes/{dispute}/resolve-refund', [ProductDisputeController::class, 'resolveRefund'])->name('disputes.resolve-refund');
            Route::post('disputes/{dispute}/resolve-replacement', [ProductDisputeController::class, 'resolveReplacement'])->name('disputes.resolve-replacement');
            Route::post('disputes/{dispute}/reject', [ProductDisputeController::class, 'reject'])->name('disputes.reject');
            Route::get('disputes/{dispute}/replacement-products', [ProductDisputeController::class, 'getReplacementProducts'])->name('disputes.replacement-products');

            // Support chats management
            Route::get('support-chats', [\App\Http\Controllers\Admin\SupportChatController::class, 'index'])->name('support-chats.index');
            Route::get('support-chats/unread-count', [\App\Http\Controllers\Admin\SupportChatController::class, 'getUnreadCount'])->name('support-chats.unread-count');
            Route::get('support-chats/{id}/messages', [\App\Http\Controllers\Admin\SupportChatController::class, 'getMessages'])->name('support-chats.messages');
            Route::get('support-chats/{id}', [\App\Http\Controllers\Admin\SupportChatController::class, 'show'])->name('support-chats.show');
            Route::post('support-chats/{id}/message', [\App\Http\Controllers\Admin\SupportChatController::class, 'sendMessage'])->name('support-chats.send-message');
            Route::post('support-chats/{id}/assign', [\App\Http\Controllers\Admin\SupportChatController::class, 'assign'])->name('support-chats.assign');
            Route::post('support-chats/{id}/status', [\App\Http\Controllers\Admin\SupportChatController::class, 'updateStatus'])->name('support-chats.update-status');
            Route::post('support-chats/{id}/typing', [\App\Http\Controllers\Admin\SupportChatController::class, 'sendTyping'])->name('support-chats.send-typing')->middleware('throttle:60,1');
            Route::post('support-chats/{id}/typing/stop', [\App\Http\Controllers\Admin\SupportChatController::class, 'stopTyping'])->name('support-chats.stop-typing')->middleware('throttle:60,1');
            Route::get('support-chats/{id}/typing/user-status', [\App\Http\Controllers\Admin\SupportChatController::class, 'getUserTypingStatus'])->name('support-chats.user-typing-status');
            Route::post('support-chats/{id}/notes', [\App\Http\Controllers\Admin\SupportChatController::class, 'addNote'])->name('support-chats.add-note');
            Route::delete('support-chats/{id}/notes/{noteId}', [\App\Http\Controllers\Admin\SupportChatController::class, 'deleteNote'])->name('support-chats.delete-note');


            Route::resource('profile', ProfileController::class)->only(['index', 'store']);
        });
    });

// Supplier Panel
Route::prefix('supplier')
    ->name('supplier.')
    ->group(function () {
        // Login routes
        Route::get('/login', [\App\Http\Controllers\Supplier\AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [\App\Http\Controllers\Supplier\AuthController::class, 'login'])->name('login.post');

        // Protected routes
        Route::middleware(['auth', 'supplier.auth'])->group(function () {
            Route::get('/', [SupplierDashboardController::class, 'index'])->name('dashboard');
            Route::match(['get', 'post'], '/logout', [\App\Http\Controllers\Supplier\AuthController::class, 'logout'])->name('logout');
            Route::resource('products', SupplierProductController::class)->except(['show']);
            Route::get('products/{product}/export', [SupplierProductController::class, 'export'])->name('products.export');
            Route::post('/products/upload-image', [SupplierProductController::class, 'uploadImage'])->name('products.upload-image');
            Route::get('/orders', [\App\Http\Controllers\Supplier\OrderController::class, 'index'])->name('orders.index');
            Route::resource('discounts', \App\Http\Controllers\Supplier\DiscountController::class)->except(['show']);

            // Withdrawals
            Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
            Route::get('/withdrawals/create', [WithdrawalController::class, 'create'])->name('withdrawals.create');
            Route::post('/withdrawals', [WithdrawalController::class, 'store'])->name('withdrawals.store');
            Route::post('/withdrawals/{withdrawal}/cancel', [WithdrawalController::class, 'cancel'])->name('withdrawals.cancel');
            Route::get('/withdrawals/payment-details', [WithdrawalController::class, 'editPaymentDetails'])->name('withdrawals.payment-details');
            Route::post('/withdrawals/payment-details', [WithdrawalController::class, 'updatePaymentDetails'])->name('withdrawals.payment-details.update');

            // Notifications
            Route::get('/notifications', [\App\Http\Controllers\Supplier\NotificationController::class, 'index'])->name('notifications.index');
            Route::post('/notifications/{id}/mark-read', [\App\Http\Controllers\Supplier\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
            Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Supplier\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
            Route::get('/notifications/unread-count', [\App\Http\Controllers\Supplier\NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');

            // Disputes
            Route::get('/disputes', [SupplierDisputeController::class, 'index'])->name('disputes.index');
            Route::get('/disputes/{dispute}', [SupplierDisputeController::class, 'show'])->name('disputes.show');
        });
    });

// SEO-важные страницы (SSR для поисковых систем)
Route::prefix('seo')->name('seo.')->group(function () {
    Route::get('/categories/{id}', [\App\Http\Controllers\Seo\CategoryController::class, 'show'])->name('category');
    Route::get('/articles', [\App\Http\Controllers\Seo\ArticleController::class, 'index'])->name('articles');
    Route::get('/articles/{id}', [\App\Http\Controllers\Seo\ArticleController::class, 'show'])->name('article');
    Route::get('/products/{id}', [\App\Http\Controllers\Seo\ProductController::class, 'show'])->name('product');
    
    // Сервисные страницы
    Route::get('/suppliers', [\App\Http\Controllers\Seo\ServicePageController::class, 'suppliers'])->name('suppliers');
    Route::get('/replace-conditions', [\App\Http\Controllers\Seo\ServicePageController::class, 'replaceConditions'])->name('replace-conditions');
    Route::get('/payment-refund', [\App\Http\Controllers\Seo\ServicePageController::class, 'paymentRefund'])->name('payment-refund');
});

// Sitemap (нужно ДО catch-all, чтобы не попадал в SPA)
Route::get('/sitemap.xml', [\App\Http\Controllers\Seo\SitemapController::class, 'index'])->name('sitemap');

// SPA-роуты с инжекцией мета-тегов (обрабатываются через nginx -> Laravel)
Route::get('/account/{id}', [\App\Http\Controllers\Seo\SpaController::class, 'index'])->where('id', '.*')->name('spa.account');
Route::get('/products/{id}', [\App\Http\Controllers\Seo\SpaController::class, 'index'])->where('id', '.*')->name('spa.products'); // Alias
Route::get('/articles/{id}', [\App\Http\Controllers\Seo\SpaController::class, 'index'])->where('id', '\d+')->name('spa.article');
Route::get('/articles', [\App\Http\Controllers\Seo\SpaController::class, 'index'])->name('spa.articles');
Route::get('/categories/{id}', [\App\Http\Controllers\Seo\SpaController::class, 'index'])->where('id', '\d+')->name('spa.category');
Route::get('/categories', [\App\Http\Controllers\Seo\SpaController::class, 'index'])->name('spa.categories');
Route::get('/become-supplier', [\App\Http\Controllers\Seo\SpaController::class, 'index'])->name('spa.become-supplier');
Route::get('/suppliers', [\App\Http\Controllers\Seo\SpaController::class, 'index'])->name('spa.suppliers'); // Alias
Route::get('/conditions', [\App\Http\Controllers\Seo\SpaController::class, 'index'])->name('spa.conditions');
Route::get('/replace-conditions', [\App\Http\Controllers\Seo\SpaController::class, 'index'])->name('spa.replace-conditions'); // Alias
Route::get('/payment-refund', [\App\Http\Controllers\Seo\SpaController::class, 'index'])->name('spa.payment-refund');
Route::get('/contacts', [\App\Http\Controllers\Seo\SpaController::class, 'index'])->name('spa.contacts');
Route::get('/', [\App\Http\Controllers\Seo\SpaController::class, 'index'])->name('spa.home');

// Поддержка любых других динамических страниц (slug)
Route::get('/{slug}', [\App\Http\Controllers\Seo\SpaController::class, 'index'])
    ->where('slug', '^(?!api|admin|auth|supplier|storage|img|js|css|seo|sitemap\.xml|robots\.txt).*$');

// Redirect /login to /admin/login for convenience
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Auth routes
Route::prefix('auth')->group(function () {
    // Google OAuth
    Route::get('/google', [SocialAuthController::class, 'redirectToGoogle']);
    Route::get('/google/reauth', [SocialAuthController::class, 'redirectToGoogleWithPrompt']);
    Route::get('/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

    // Telegram авторизация
    Route::match(['get', 'post'], '/telegram/callback', [SocialAuthController::class, 'handleTelegramCallback']);
});
