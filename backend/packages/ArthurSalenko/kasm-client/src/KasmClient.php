<?php

namespace ArthurSalenko\KasmClient;

use Illuminate\Support\Facades\Http;

class KasmClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $apiSecret;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('kasm.base_url'), '/');
        $this->apiKey = config('kasm.api_key');
        $this->apiSecret = config('kasm.api_secret');
    }

    /**
     * Sends a POST request to the Kasm API with authentication credentials.
     *
     * @param string $endpoint
     * @param array $payload
     * @return array|null
     */
    protected function post(string $endpoint, array $payload): array|null
    {
        $data = array_merge($payload, [
            'api_key' => $this->apiKey,
            'api_key_secret' => $this->apiSecret,
        ]);

        $response = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->post($endpoint, $data);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Create a new Kasm user.
     *
     * @param array $payload
     * @return array|null
     */
    public function createUser(array $payload): array|null
    {
        return $this->post('/api/public/create_user', $payload);
    }

    /**
     * Requests a new Kasm session.
     *
     * @param array $payload
     * @return array|null
     */
    public function requestKasm(array $payload): array|null
    {
        return $this->post('/api/public/request_kasm', $payload);
    }

    /**
     * Requests a new Kasm session.
     *
     * @param array $payload
     * @return array|null
     */
    public function getKasmStatus(array $payload): array|null
    {
        return $this->post('/api/public/get_kasm_status', $payload);
    }

    /**
     * Destroy a Kasm session.
     *
     * @param array $payload
     * @return array|null
     */
    public function destroyKasm(array $payload): array|null
    {
        return $this->post('/api/public/destroy_kasm', $payload);
    }

    /**
     * Retrieves the list of available Kasm images.
     *
     * @return array|null
     */
    public function getImages(): array|null
    {
        return $this->post('/api/public/get_images', []);
    }
}
