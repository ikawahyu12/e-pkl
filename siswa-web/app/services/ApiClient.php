<?php

class ApiClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(API_BASE_URL, '/');
    }

    /** POST multipart (untuk upload file). $fields boleh berisi CURLFile. */
    public function postForm(string $endpoint, array $fields, ?string $token = null): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        $headers = ['Accept: application/json'];
        if ($token !== null && $token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 30,
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

    public function get(string $endpoint, ?string $token = null): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        $headers = ['Accept: application/json'];

        if ($token !== null && $token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        return $this->requestJson('GET', $url, $headers, null, 15);
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

        return $this->requestJson('POST', $url, $headers, $body, 15);
    }

    private function requestJson(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        int $timeout,
    ): array {
        $httpOptions = [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true,
            'follow_location' => 0,
            'timeout' => $timeout,
        ];

        if ($body !== null) {
            $httpOptions['content'] = $body;
        }

        $context = stream_context_create(['http' => $httpOptions]);
        $transportError = '';
        set_error_handler(static function (int $severity, string $message) use (&$transportError): bool {
            if ($severity === E_WARNING) {
                $transportError = $message;

                return true;
            }

            return false;
        }, E_WARNING);

        try {
            $responseBody = file_get_contents($url, false, $context);
        } finally {
            restore_error_handler();
        }

        $status = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
                $status = (int) $matches[1];
            }
        }

        if ($responseBody === false) {
            $message = stripos($transportError, 'timed out') !== false
                || stripos($transportError, 'timeout') !== false
                ? 'Waktu koneksi ke server API habis.'
                : 'Tidak dapat menghubungi server API.';

            return [
                'success' => false,
                'message' => $message,
                'status' => $status,
            ];
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