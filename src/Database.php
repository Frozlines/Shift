<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        global $config;

        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $db = $config['db'];
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset']
        );

        self::$pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }
}
