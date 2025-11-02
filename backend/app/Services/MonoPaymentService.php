<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class MonoPaymentService
{
    // Константа для кода валюты (840 = USD)
    public const CURRENCY_CODE = 840;

    /**
     * Создание инвойса для оплаты
     */
    public static function createInvoice(
        float $amount,
        string $redirectUrl,
        string $webhookUrl,
        string $walletId
    ): array|false {
        $response = self::makeRequest('post', 'invoice/create', [
            'amount' => intval($amount * 100),
            'ccy' => self::CURRENCY_CODE,
            'redirectUrl' => $redirectUrl,
            'webHookUrl' => $webhookUrl,
            'reference' => uniqid('order_'),
            'validity' => 3600 * 12,
            'saveCardData' => [
                'saveCard' => true,
                'walletId' => md5($walletId),
            ],
        ]);

        return $response;
    }

    /**
     * Списание денег по токену карты
     */
    public static function chargeWithToken(string $cardToken, float $amount): array|false
    {
        return self::makeRequest('post', 'wallet/payment', [
            'amount' => intval($amount * 100),
            'ccy' => self::CURRENCY_CODE,
            'token' => $cardToken,
        ]);
    }

    /**
     * Получение списка сохранённых карт по walletId
     */
    public static function getWalletCard(string $walletId): array
    {
        $response = self::makeRequest('get', 'wallet', [
            'walletId' => md5($walletId),
        ]);

        return $response['accounts'] ?? [];
    }

    /**
     * Общий метод отправки запроса к Monobank API
     */
    private static function makeRequest(string $method, string $endpoint, array $data): array|false
    {
        $token = config('monobank.token');
        $url = "https://api.monobank.ua/api/merchant/{$endpoint}";

        $http = Http::withHeaders(['X-Token' => $token]);

        /** @var Response $response */
        $response = $method === 'get'
            ? $http->get($url . '?' . http_build_query($data))
            : $http->post($url, $data);

        if ($response->failed()) {
            return false;
        }

        return $response->json();
    }
}
