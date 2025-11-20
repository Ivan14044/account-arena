<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class MonoPaymentService
{
    // Default currency code (980 = UAH)
    public const DEFAULT_CURRENCY_CODE = 980; // UAH
    public const CURRENCY_CODE_USD = 840;
    
    // Default invoice validity period: 24 hours (86400 seconds)
    public const DEFAULT_VALIDITY = 86400;

    /**
     * Convert currency from string format (USD, UAH) to ISO 4217 code
     * Uses Option::get('currency') to get currency from settings
     * 
     * @param string|null $currency String currency code (USD, UAH, etc.), if null - taken from Option
     * @return int ISO 4217 currency code
     */
    public static function getCurrencyCode(?string $currency = null): int
    {
        if ($currency === null) {
            // Use currency from Option::get('currency')
            $currency = \App\Models\Option::get('currency', 'USD');
        }
        
        $currency = strtoupper(trim($currency));
        
        return match ($currency) {
            'UAH' => 980,
            'USD' => 840,
            'EUR' => 978,
            'GBP' => 826,
            'PLN' => 985,
            default => self::DEFAULT_CURRENCY_CODE, // Fallback to UAH if currency is not recognized
        };
    }

    /**
     * Create invoice for payment according to MonoBank API documentation
     * https://monobank.ua/api-docs/acquiring/methods/ia/post--api--merchant--invoice--create
     *
     * @param float $amount Payment amount
     * @param string $webhookUrl CallBack address (POST) - payment status data will be sent to this address
     * @param array $options Additional options:
     *   - 'ccy' => int (ISO 4217 currency code, default 980 UAH)
     *   - 'redirectUrl' => string (return address (GET) - user will be redirected after payment completion, optional)
     *   - 'successUrl' => string (return address in case of successful payment)
     *   - 'failUrl' => string (return address in case of failed payment)
     *   - 'validity' => int (validity period in seconds, default 86400 = 24 hours)
     *   - 'paymentType' => string ('debit' or 'hold', default 'debit')
     *   - 'merchantPaymInfo' => array (order information data, required when PPRO is active)
     *   - 'reference' => string (order reference)
     * @return array|false
     */
    public static function createInvoice(
        float $amount,
        string $webhookUrl,
        array $options = []
    ): array|false {
        // Determine currency
        // If not explicitly specified in options, use currency from Option::get('currency')
        if (isset($options['ccy'])) {
            $ccy = (int)$options['ccy'];
        } else {
            $ccy = self::getCurrencyCode(); // Map currency from Option to ISO 4217 code
        }
        
        // Prepare request data according to documentation
        $requestData = [
            // Required parameters
            'amount' => (int)round($amount * 100), // Amount in minimum units (kopecks for hryvnia)
            'ccy' => (int)$ccy, // ISO 4217 currency code
            
            // URL parameters
            'webHookUrl' => $webhookUrl, // According to documentation: webHookUrl (with capital W)
        ];
        
        // Additional URLs (optional)
        if (!empty($options['redirectUrl'])) {
            $requestData['redirectUrl'] = $options['redirectUrl'];
        }
        if (!empty($options['successUrl'])) {
            $requestData['successUrl'] = $options['successUrl'];
            // If redirectUrl is not explicitly set, use successUrl as redirectUrl
            if (empty($options['redirectUrl'])) {
                $requestData['redirectUrl'] = $options['successUrl'];
            }
        }
        if (!empty($options['failUrl'])) {
            $requestData['failUrl'] = $options['failUrl'];
        }
        
        // Validity period (default 24 hours according to documentation)
        $requestData['validity'] = (int)($options['validity'] ?? self::DEFAULT_VALIDITY);
        
        // Payment type (default 'debit' according to documentation)
        $paymentType = $options['paymentType'] ?? 'debit';
        if (in_array($paymentType, ['debit', 'hold'])) {
            $requestData['paymentType'] = $paymentType;
        }
        
        // Order reference
        if (!empty($options['reference'])) {
            $requestData['reference'] = $options['reference'];
        } else {
            $requestData['reference'] = uniqid('order_', true);
        }
        
        // Order information data (required when PPRO connection is active)
        if (!empty($options['merchantPaymInfo'])) {
            $requestData['merchantPaymInfo'] = $options['merchantPaymInfo'];
        }
        
        // Additional options according to documentation
        if (!empty($options['qrId'])) {
            $requestData['qrId'] = $options['qrId'];
        }
        if (!empty($options['code'])) {
            $requestData['code'] = $options['code'];
        }
        if (!empty($options['agentFeePercent'])) {
            $requestData['agentFeePercent'] = (float)$options['agentFeePercent'];
        }
        if (!empty($options['tipsEmployeeId'])) {
            $requestData['tipsEmployeeId'] = $options['tipsEmployeeId'];
        }
        if (!empty($options['displayType']) && $options['displayType'] === 'iframe') {
            $requestData['displayType'] = 'iframe';
        }
        
        $response = self::makeRequest('post', 'invoice/create', $requestData);
        
        // Logging for debugging
        if ($response === false) {
            Log::error('MonoPaymentService: Failed to create invoice', [
                'amount' => $amount,
                'ccy' => $ccy,
                'request_data' => $requestData,
            ]);
        } else {
            Log::info('MonoPaymentService: Invoice created successfully', [
                'invoice_id' => $response['invoiceId'] ?? null,
                'page_url' => $response['pageUrl'] ?? null,
            ]);
        }

        return $response;
    }

    /**
     * General method for sending request to Monobank API
     * According to documentation: https://monobank.ua/api-docs/acquiring/methods/ia/post--api--merchant--invoice--create
     * 
     * HEADER PARAMETERS:
     * - X-Token (required, string) - Token from personal cabinet or test token
     */
    private static function makeRequest(string $method, string $endpoint, array $data): array|false
    {
        $token = config('monobank.token');
        if (!$token) {
            Log::error('MonoPaymentService: MONOBANK_TOKEN not configured');
            return false;
        }
        
        $url = "https://api.monobank.ua/api/merchant/{$endpoint}";

        // Prepare headers according to documentation
        $headers = [
            'X-Token' => $token,
        ];

        $http = Http::withHeaders($headers);

        Log::info('MonoPaymentService: API request', [
            'method' => $method,
            'endpoint' => $endpoint,
            'data' => $data,
            'url' => $url,
            'headers' => $headers,
        ]);

        /** @var Response $response */
        $response = $method === 'get'
            ? $http->get($url, $data)
            : $http->post($url, $data);

        if ($response->failed()) {
            Log::error('MonoPaymentService: API request failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return false;
        }

        return $response->json();
    }
}
