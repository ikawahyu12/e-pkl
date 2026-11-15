<?php

return [
    'expire_minutes' => (int) env('PASSWORD_RESET_EXPIRE_MINUTES', 60),
    'request_cooldown_seconds' => (int) env('PASSWORD_RESET_COOLDOWN_SECONDS', 60),
    'frontends' => [
        'admin' => env('ADMIN_PASSWORD_RESET_URL'),
        'siswa' => env('SISWA_PASSWORD_RESET_URL'),
        'guru' => env('GURU_PASSWORD_RESET_URL', 'epkl://guru/reset-password'),
        'guru_fallback' => env(
            'GURU_PASSWORD_RESET_FALLBACK_URL',
            rtrim((string) env('APP_URL', 'http://localhost'), '/').'/password-reset/guru'
        ),
    ],
];
