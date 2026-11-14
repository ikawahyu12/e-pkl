<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/** Panggil API Laravel. Return: status, body, error */
function admin_api(string $method, string $path, ?array $payload = null, array $query = []): array
{
    $url = 'http://127.0.0.1:8000/api/v1' . $path . ($query ? '?' . http_build_query($query) : '');

    $headers = [
        'Accept: application/json',
        'Authorization: Bearer ' . ($_SESSION['admin_token'] ?? ''),
    ];

    $opt = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CUSTOMREQUEST  => $method,
    ];

    if ($payload !== null) {
        $opt[CURLOPT_POSTFIELDS] = json_encode($payload);
        $headers[] = 'Content-Type: application/json';
    }

    $opt[CURLOPT_HTTPHEADER] = $headers;

    $ch = curl_init($url);
    curl_setopt_array($ch, $opt);
    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error  = curl_error($ch);
    curl_close($ch);

    $json = is_string($body) ? json_decode($body, true) : null;

    return ['status' => $status, 'body' => is_array($json) ? $json : [], 'error' => $error];
}

/** Ambil satu pesan error yang enak dibaca dari respons API */
function admin_errors(array $res): string
{
    $b = $res['body'] ?? [];

    if (!empty($b['errors']) && is_array($b['errors'])) {
        $first = reset($b['errors']);
        return is_array($first) ? ($first[0] ?? 'Data tidak valid.') : (string) $first;
    }

    if (($res['status'] ?? 0) === 0) {
        return 'Tidak dapat menghubungi server API. ' . ($res['error'] ?? '');
    }

    return $b['message'] ?? ('Terjadi kesalahan (kode ' . $res['status'] . ').');
}

function admin_flash_set(string $type, string $message): void
{
    $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
}

function admin_flash_get(): ?array
{
    $f = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);
    return $f;
}

function guru_csrf(): string
{
    if (empty($_SESSION['guru_csrf'])) {
        $_SESSION['guru_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['guru_csrf'];
}

function guru_csrf_ok(): bool
{
    $t = $_POST['csrf'] ?? '';
    return is_string($t) && $t !== '' && hash_equals($_SESSION['guru_csrf'] ?? '', $t);
}