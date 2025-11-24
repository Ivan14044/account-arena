<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\CryptomusController;
use App\Http\Controllers\MonoController;
use App\Http\Controllers\Api\BrowserController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\BannerController as ApiBannerController;
use App\Http\Controllers\Api\SiteContentController;
use App\Http\Controllers\Api\ProductDisputeController;
use App\Http\Controllers\Api\PurchaseController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Http\Controllers\ExtensionController;
use App\Http\Controllers\Api\PromocodeController;
use App\Http\Controllers\Api\SupportChatController;

// Auth routes with rate limiting (увеличен лимит для разработки: 60 запросов в минуту)
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Public routes with moderate rate limiting (увеличено для разработки: 300 requests per minute)
// Health check endpoints (без auth и rate limiting)
Route::get('/health', [\App\Http\Controllers\HealthController::class, 'check']);
Route::get('/ping', [\App\Http\Controllers\HealthController::class, 'ping']);

Route::middleware('throttle:300,1')->group(function () {
    Route::get('/accounts', [\App\Http\Controllers\Api\AccountController::class, 'index']);
    Route::get('/accounts/{account}', [\App\Http\Controllers\Api\AccountController::class, 'show']);
    Route::get('/accounts/{account}/similar', [\App\Http\Controllers\Api\AccountController::class, 'similar']);
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::get('/articles/{article}', [ArticleController::class, 'show']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{categoryId}/subcategories', [CategoryController::class, 'getSubcategories']);
    Route::get('/pages', [PageController::class, 'index']);
    Route::get('/options', [OptionController::class, 'index']);
    Route::get('/cookie/check', [CookieConsentController::class, 'check']);
    Route::post('/promocodes/validate', [PromocodeController::class, 'validateCode']);
    Route::get('/banners', [ApiBannerController::class, 'index']);
    Route::get('/banners/all', [ApiBannerController::class, 'all']);
    Route::get('/site-content', [SiteContentController::class, 'index']);
    Route::get('/purchase-rules', [OptionController::class, 'getPurchaseRules']);
    Route::get('/support-chat-settings', [OptionController::class, 'getSupportChatSettings']);
    
    // Чат поддержки (публичные endpoints)
    Route::post('/support-chat/create', [SupportChatController::class, 'getOrCreateChat']);
    Route::get('/support-chat/{chatId}/messages', [SupportChatController::class, 'getMessages']);
    Route::post('/support-chat/{chatId}/messages', [SupportChatController::class, 'sendMessage']);
    Route::post('/support-chat/{chatId}/typing', [SupportChatController::class, 'sendTyping']);
    Route::post('/support-chat/{chatId}/typing/stop', [SupportChatController::class, 'stopTyping']);
    Route::get('/support-chat/{chatId}/typing/status', [SupportChatController::class, 'getTypingStatus']);
    Route::post('/support-chat/{chatId}/rating', [SupportChatController::class, 'addRating']);
    
    // Гостевые покупки (без авторизации) - только товары
    Route::post('/guest/cart', [\App\Http\Controllers\GuestCartController::class, 'store']);
    Route::post('/guest/mono/create-payment', [MonoController::class, 'createGuestPayment']);
    Route::post('/guest/cryptomus/create-payment', [CryptomusController::class, 'createGuestPayment']);
});

// Authenticated routes with rate limiting (120 requests per minute)
Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/user', [AuthController::class, 'update']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read', [NotificationController::class, 'markNotificationsAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('/cart', [CartController::class, 'store']);
    
    // Vouchers
    Route::post('/vouchers/activate', [\App\Http\Controllers\VoucherController::class, 'activate']);
    
    // Product disputes
    Route::get('/disputes', [ProductDisputeController::class, 'index']);
    Route::post('/disputes', [ProductDisputeController::class, 'store']);
    Route::get('/disputes/{id}', [ProductDisputeController::class, 'show']);
    Route::get('/transactions/{transactionId}/can-dispute', [ProductDisputeController::class, 'canDispute']);
    
    // Purchases (купленные товары)
    Route::get('/purchases', [PurchaseController::class, 'index']);
    Route::get('/purchases/{id}', [PurchaseController::class, 'show']);
    Route::get('/purchases/{id}/download', [PurchaseController::class, 'download']);
    
    // Чат поддержки (для авторизованных пользователей)
    Route::get('/support-chats', [\App\Http\Controllers\Api\SupportChatController::class, 'getChats']);
    
    // Balance API (новая система управления балансом)
    Route::prefix('balance')->group(function () {
        Route::get('/', [\App\Http\Controllers\BalanceController::class, 'getBalance']);
        Route::get('/history', [\App\Http\Controllers\BalanceController::class, 'getHistory']);
        Route::post('/check-funds', [\App\Http\Controllers\BalanceController::class, 'checkSufficientFunds']);
        Route::get('/statistics', [\App\Http\Controllers\BalanceController::class, 'getStatistics']);
    });
});

// Payment creation with stricter rate limiting (10 requests per minute)
Route::middleware(['auth:sanctum', 'throttle:10,1'])->group(function () {
    Route::post('/cryptomus/create-payment', [CryptomusController::class, 'createPayment']);
    Route::post('/mono/create-payment', [MonoController::class, 'createPayment']);
    
    // Пополнение баланса
    Route::post('/mono/topup', [MonoController::class, 'createTopUpPayment']);
    Route::post('/cryptomus/topup', [CryptomusController::class, 'createTopUpPayment']);
});

// Webhooks с проверкой подписи и rate limiting
Route::middleware(['verify.webhook:cryptomus', 'throttle:100,1'])->group(function () {
    Route::post('/cryptomus/webhook', [CryptomusController::class, 'webhook']);
});

Route::middleware(['verify.webhook:monobank', 'throttle:100,1'])->group(function () {
    Route::post('/mono/webhook', [MonoController::class, 'webhook']);
});

Route::get('/contents/{code}', [ContentController::class, 'show']);

Route::get('/browser/new', [BrowserController::class, 'new']);

Route::post('/browser/stop', function (Request $request) {
    $base = rtrim(config('services.browser_api.url'), '/');
    $resp = Http::timeout(60)->asJson()->post($base . '/stop', $request->all());

    return response($resp->body(), $resp->status())
        ->withHeaders(['Content-Type' => 'application/json']);
});

Route::post('/browser/stop_all', function (Request $request) {
    $base = rtrim(config('services.browser_api.url'), '/');
    $resp = Http::timeout(60)->asJson()->post($base . '/stop_all', $request->all());

    return response($resp->body(), $resp->status())
        ->withHeaders(['Content-Type' => 'application/json']);
});

Route::get('/browser/list', function () {
    $base = rtrim(config('services.browser_api.url'), '/');
    $resp = Http::timeout(60)->get($base . '/list');

    return response($resp->body(), $resp->status())
        ->withHeaders(['Content-Type' => 'application/json']);
});

Route::middleware('ext.auth')->group(function () {
    Route::post('/extension/settings', [ExtensionController::class, 'saveSettings']);
    Route::get('/extension/auth', [ExtensionController::class, 'authStatus']);
});
