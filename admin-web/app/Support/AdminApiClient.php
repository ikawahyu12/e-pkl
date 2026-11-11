<?php

final class AdminApiClient
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $configuredUrl = $baseUrl
            ?? getenv('EPKL_API_URL')
            ?: 'http://127.0.0.1:8000';

        $this->baseUrl = rtrim($configuredUrl, '/');
    }

    public function login(string $login, string $password): array
    {
        return $this->request('POST', '/api/v1/admin/auth/login', [
            'login' => $login,
            'password' => $password,
        ]);
    }

    public function me(string $token): array
    {
        return $this->request('GET', '/api/v1/admin/auth/me', null, $token);
    }

    public function logout(string $token): array
    {
        return $this->request('POST', '/api/v1/admin/auth/logout', null, $token);
    }

    private function request(
        string $method,
        string $path,
        ?array $payload = null,
        ?string $token = null
    ): array {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Ekstensi cURL PHP belum tersedia.');
        }

        $headers = ['Accept: application/json'];

        $curl = curl_init($this->baseUrl . $path);

        if ($curl === false) {
            throw new RuntimeException('Tidak dapat memulai koneksi API.');
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($payload !== null) {
            $jsonPayload = json_encode($payload);

            if ($jsonPayload === false) {
                curl_close($curl);
                throw new RuntimeException('Gagal menyiapkan data permintaan API.');
            }

            $options[CURLOPT_POSTFIELDS] = $jsonPayload;
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        }

        if ($token !== null && $token !== '') {
            $options[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt_array($curl, $options);

        $responseBody = curl_exec($curl);
        $curlError = curl_error($curl);
        $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($responseBody === false) {
            throw new RuntimeException(
                $curlError !== '' ? $curlError : 'Koneksi ke API gagal.'
            );
        }

        $decoded = json_decode($responseBody, true);

        return [
            'status' => $httpStatus,
            'body' => is_array($decoded) ? $decoded : [],
        ];
    }
}