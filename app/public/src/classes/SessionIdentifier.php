<?php
namespace src\classes;

class SessionIdentifier
{
    public static function generate(): string
    {
        if (!empty($_COOKIE['session_uid'])) {
            return $_COOKIE['session_uid'];
        }

        $sessionId = self::createUniqueId();

        setcookie('session_uid', $sessionId, time() + 7200, '/', '', false, true);

        return $sessionId;
    }

    private static function createUniqueId(): string
    {
        $components = [
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            microtime(true),
            random_int(1000, 9999)
        ];

        return md5(implode('|', $components));
    }

    public static function getClientInfo(): array
    {
        return [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 100),
            'forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            'session_id' => session_id()
        ];
    }
}