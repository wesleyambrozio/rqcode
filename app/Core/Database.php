<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }

        $db = config('database');
        $driver = $db['connection'];
        $dsn = match ($driver) {
            'pgsql' => "pgsql:host={$db['host']};port={$db['port']};dbname={$db['database']}",
            'sqlite' => 'sqlite:' . dirname(__DIR__, 2) . '/' . $db['database'],
            default => "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset=utf8mb4",
        };

        try {
            self::$pdo = new PDO($dsn, $db['username'], $db['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $exception) {
            if (config('app.debug')) {
                throw $exception;
            }
            exit('Não foi possível conectar ao banco de dados.');
        }

        return self::$pdo;
    }
}
