<?php

class ApiClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(API_BASE_URL, '/');
    }

    public function post(string $endpoint, array $payload = [], ?string $token = null): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        if ($token !== null && $token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $body = json_encode($payload);

        if ($body === false) {
            return [
                'success' => false,
                'message' => 'Gagal menyiapkan data permintaan.',
                'status' => 0,
            ];
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 15,
            ]);

            $responseBody = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            curl_close($ch);

            if ($responseBody === false) {
                return [
                    'success' => false,
                    'message' => 'Tidak dapat menghubungi server API: ' . $curlError,
                    'status' => 0,
                ];
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", $headers),
                    'content' => $body,
                    'ignore_errors' => true,
                    'timeout' => 15,
                ],
            ]);

            $responseBody = @file_get_contents($url, false, $context);

            $status = 0;
            foreach ($http_response_header ?? [] as $header) {
                if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
                    $status = (int) $matches[1];
                }
            }

            if ($responseBody === false) {
                return [
                    'success' => false,
                    'message' => 'Tidak dapat menghubungi server API.',
                    'status' => $status,
                ];
            }
        }

        $result = json_decode($responseBody, true);

        if (!is_array($result)) {
            return [
                'success' => false,
                'message' => 'Server API mengirim respons yang tidak valid.',
                'status' => $status,
            ];
        }

        $result['status'] = $status;

        return $result;
    }
}
