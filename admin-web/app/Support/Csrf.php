<?php

final class AdminCsrf
{
    private const SESSION_KEY = 'admin_csrf_token';

    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function verify(mixed $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!is_string($token)) {
            return false;
        }

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;

        return is_string($sessionToken) && hash_equals($sessionToken, $token);
    }
}
