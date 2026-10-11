<?php
declare(strict_types=1);

/** Limitación sencilla de fuerza bruta: 5 fallos por usuario+IP o 15 por IP en 15 minutos. */
final class LoginAttempt
{
    public static function tooMany(string $ip, string $username): bool
    {
        $byUser = (int) Database::value(
            'SELECT COUNT(*) FROM login_attempts
             WHERE ip = :ip AND username = :u AND created_at > (NOW() - INTERVAL 15 MINUTE)',
            ['ip' => $ip, 'u' => $username]
        );
        $byIp = (int) Database::value(
            'SELECT COUNT(*) FROM login_attempts
             WHERE ip = :ip2 AND created_at > (NOW() - INTERVAL 15 MINUTE)',
            ['ip2' => $ip]
        );
        return $byUser >= 5 || $byIp >= 15;
    }

    public static function record(string $ip, string $username): void
    {
        Database::query(
            'INSERT INTO login_attempts (ip, username) VALUES (:ip, :u)',
            ['ip' => $ip, 'u' => mb_substr($username, 0, 50)]
        );
        Database::query('DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)');
    }

    public static function clear(string $ip, string $username): void
    {
        Database::query('DELETE FROM login_attempts WHERE ip = :ip AND username = :u', ['ip' => $ip, 'u' => $username]);
    }
}
