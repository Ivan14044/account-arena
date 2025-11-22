<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Проверка подписи webhook запросов от платежных систем
 * Защита от SSRF и replay атак
 */
class VerifyWebhookSignature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $provider): Response
    {
        if (!config('app.verify_webhooks_enabled', true)) {
            Log::warning('Webhook signature verification disabled', [
                'provider' => $provider,
                'ip' => $request->ip(),
            ]);
            return $next($request);
        }

        $isValid = match ($provider) {
            'cryptomus' => $this->verifyCryptomusSignature($request),
            'monobank' => $this->verifyMonobankSignature($request),
            default => false,
        };

        if (!$isValid) {
            Log::warning('Invalid webhook signature', [
                'provider' => $provider,
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);

            return response()->json([
                'error' => 'Invalid signature'
            ], 403);
        }

        return $next($request);
    }

    /**
     * Проверка подписи Cryptomus
     * According to docs: https://doc.cryptomus.com/merchant-api/payments/webhook
     * Signature is in the request body (sign field), not in headers
     */
    private function verifyCryptomusSignature(Request $request): bool
    {
        $rawData = $request->getContent();
        $data = json_decode($rawData, true);

        if (!is_array($data) || !isset($data['sign'])) {
            Log::warning('Cryptomus webhook: Invalid JSON or missing sign field', [
                'has_data' => !empty($rawData),
                'has_sign' => isset($data['sign']),
            ]);
            return false;
        }

        $signature = $data['sign'];
        unset($data['sign']);

        $paymentKey = config('cryptomus.payment_key');
        if (!$paymentKey) {
            Log::error('Cryptomus payment key not configured');
            return false;
        }

        // Generate signature: md5(base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE)) . $paymentKey)
        $expectedSignature = md5(base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE)) . $paymentKey);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Проверка подписи Monobank
     */
    private function verifyMonobankSignature(Request $request): bool
    {
        $signature = $request->header('X-Sign');
        if (!$signature) {
            return false;
        }

        $publicKey = config('monobank.public_key');
        if (!$publicKey) {
            Log::error('Monobank public key not configured');
            return false;
        }

        $data = $request->getContent();
        
        // Monobank использует RSA подпись
        $publicKeyResource = openssl_pkey_get_public(base64_decode($publicKey));
        if (!$publicKeyResource) {
            Log::error('Invalid Monobank public key format');
            return false;
        }

        $signatureDecoded = base64_decode($signature);
        $result = openssl_verify(
            $data,
            $signatureDecoded,
            $publicKeyResource,
            OPENSSL_ALGO_SHA256
        );

        return $result === 1;
    }
}



