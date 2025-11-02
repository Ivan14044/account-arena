<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\Api\ServiceController;
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
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Http\Controllers\ExtensionController;
use App\Http\Controllers\Api\PromocodeController;

// Public routes with rate limiting
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});
Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
Route::post('/user', [AuthController::class, 'update'])->middleware('auth:sanctum');
Route::get('/transactions', [TransactionController::class, 'index'])->middleware('auth:sanctum');

Route::get('/notifications', [NotificationController::class, 'index'])->middleware('auth:sanctum');
Route::post('/notifications/read', [NotificationController::class, 'markNotificationsAsRead'])
    ->middleware('auth:sanctum');

Route::get('/services', [ServiceController::class, 'index']);

Route::get('/accounts', [\App\Http\Controllers\Api\AccountController::class, 'index']);
Route::get('/accounts/{account}', [\App\Http\Controllers\Api\AccountController::class, 'show']);

Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{article}', [ArticleController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);

Route::get('/pages', [PageController::class, 'index']);
Route::get('/options', [OptionController::class, 'index']);
Route::post('/cart', [CartController::class, 'store'])->middleware('auth:sanctum');

Route::post('/toggle-auto-renew', [SubscriptionController::class, 'toggleAutoRenew'])->middleware('auth:sanctum');
Route::post('/cancel-subscription', [SubscriptionController::class, 'cancelSubscription'])->middleware('auth:sanctum');

Route::get('/cookie/check', [CookieConsentController::class, 'check']);

Route::post('/cryptomus/create-payment', [CryptomusController::class, 'createPayment'])->middleware('auth:sanctum');
Route::post('/cryptomus/webhook', [CryptomusController::class, 'webhook']); //->middleware('cryptomus');
Route::post('/mono/create-payment', [MonoController::class, 'createPayment'])->middleware('auth:sanctum');
Route::post('/mono/webhook', [MonoController::class, 'webhook']); //->middleware('cryptomus');

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

Route::post('/promocodes/validate', [PromocodeController::class, 'validateCode']);
