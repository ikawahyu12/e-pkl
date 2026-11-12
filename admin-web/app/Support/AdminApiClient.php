<?php

final class AdminApiClient
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $configuredUrl = $baseUrl;

        if ($configuredUrl === null) {
            foreach ([$_ENV['EPKL_API_URL'] ?? null, $_SERVER['EPKL_API_URL'] ?? null, getenv('EPKL_API_URL')] as $candidate) {
                if (is_string($candidate) && trim($candidate) !== '') {
                    $configuredUrl = trim($candidate);
                    break;
                }
            }
        }

        $this->baseUrl = rtrim($configuredUrl ?? $this->detectApiUrl(), '/');
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

    public function adminUsers(string $token, array $filters = []): array
    {
        $query = http_build_query(array_filter(
            $filters,
            static fn ($value): bool => $value !== null && $value !== ''
        ));
        $path = '/api/v1/admin/users' . ($query !== '' ? '?' . $query : '');

        return $this->request('GET', $path, null, $token);
    }

    public function adminUser(string $token, int $id): array
    {
        return $this->request('GET', '/api/v1/admin/users/' . $id, null, $token);
    }

    public function createAdminUser(string $token, array $data): array
    {
        return $this->request('POST', '/api/v1/admin/users', $data, $token);
    }

    public function updateAdminUser(string $token, int $id, array $data): array
    {
        return $this->request('PATCH', '/api/v1/admin/users/' . $id, $data, $token);
    }

    public function deleteAdminUser(string $token, int $id): array
    {
        return $this->request('DELETE', '/api/v1/admin/users/' . $id, null, $token);
    }

    public function adminStudents(string $token, array $filters = []): array
    {
        return $this->request('GET', '/api/v1/admin/students?' . http_build_query($filters), null, $token);
    }

    public function adminStudentClasses(string $token): array
    {
        return $this->request('GET', '/api/v1/admin/students/classes', null, $token);
    }

    public function adminStudent(string $token, int $id): array
    {
        return $this->request('GET', '/api/v1/admin/students/' . $id, null, $token);
    }

    public function createAdminStudent(string $token, array $data): array
    {
        return $this->request('POST', '/api/v1/admin/students', $data, $token);
    }

    public function updateAdminStudent(string $token, int $id, array $data): array
    {
        return $this->request('PATCH', '/api/v1/admin/students/' . $id, $data, $token);
    }

    public function deleteAdminStudent(string $token, int $id): array
    {
        return $this->request('DELETE', '/api/v1/admin/students/' . $id, null, $token);
    }

    public function adminTeachers(string $token, array $filters = []): array
    {
        return $this->request('GET', '/api/v1/admin/teachers?' . http_build_query($filters), null, $token);
    }

    public function adminTeacher(string $token, int $id): array
    {
        return $this->request('GET', '/api/v1/admin/teachers/' . $id, null, $token);
    }

    public function createAdminTeacher(string $token, array $data): array
    {
        return $this->request('POST', '/api/v1/admin/teachers', $data, $token);
    }

    public function updateAdminTeacher(string $token, int $id, array $data): array
    {
        return $this->request('PATCH', '/api/v1/admin/teachers/' . $id, $data, $token);
    }

    public function deleteAdminTeacher(string $token, int $id): array
    {
        return $this->request('DELETE', '/api/v1/admin/teachers/' . $id, null, $token);
    }

    private function detectApiUrl(): string
    {
        $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $adminPath = '/admin-web/public/';
        $adminPathPosition = strpos($scriptPath, $adminPath);
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            ? 'https'
            : 'http';

        if ($adminPathPosition !== false) {
            $projectPath = rtrim(substr($scriptPath, 0, $adminPathPosition), '/');

            return $scheme . '://' . $host . $projectPath . '/api/public';
        }

        return 'http://127.0.0.1:8000';
    }

    private function request(
        string $method,
        string $path,
        ?array $payload = null,
        ?string $token = null
    ): array {
        $headers = ['Accept: application/json'];
        $jsonPayload = null;

        if ($payload !== null) {
            $jsonPayload = json_encode($payload);

            if ($jsonPayload === false) {
                throw new RuntimeException('Gagal menyiapkan data permintaan API.');
            }
            $headers[] = 'Content-Type: application/json';
        }

        if ($token !== null && $token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $url = $this->baseUrl . $path;

        if (function_exists('curl_init')) {
            $curl = curl_init($url);

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

            if ($jsonPayload !== null) {
                $options[CURLOPT_POSTFIELDS] = $jsonPayload;
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
        } else {
            $streamOptions = [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => 15,
            ];

            if ($jsonPayload !== null) {
                $streamOptions['content'] = $jsonPayload;
            }

            $context = stream_context_create(['http' => $streamOptions]);
            $streamError = null;

            set_error_handler(static function (int $severity, string $message) use (&$streamError): bool {
                $streamError = $message;
                return true;
            });

            try {
                $responseBody = file_get_contents($url, false, $context);
            } finally {
                restore_error_handler();
            }

            if ($responseBody === false) {
                throw new RuntimeException(
                    $streamError ?? 'Koneksi ke API gagal.'
                );
            }

            $httpStatus = 0;
            foreach ($http_response_header ?? [] as $responseHeader) {
                if (preg_match('/^HTTP\/\S+\s+(\d+)/', $responseHeader, $matches)) {
                    $httpStatus = (int) $matches[1];
                }
            }
        }

        $decoded = json_decode($responseBody, true);

        return [
            'status' => $httpStatus,
            'body' => is_array($decoded) ? $decoded : [],
        ];
    }
}