<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return self::$pdo;
        } catch (PDOException $e) {
            Logger::critical("Database connection error: " . $e->getMessage());
            renderErrorPage(503, 'Database Connection Error', 'The system is temporarily unable to connect to the database. Please try again in a few moments.');
            exit;
        }
    }

    public static function beginTransaction(): bool
    {
        $db = self::getConnection();
        return $db->inTransaction() ? true : $db->beginTransaction();
    }

    public static function commit(): bool
    {
        $db = self::getConnection();
        return $db->inTransaction() ? $db->commit() : true;
    }

    public static function rollBack(): bool
    {
        $db = self::getConnection();
        return $db->inTransaction() ? $db->rollBack() : true;
    }
}

function getDB(): PDO
{
    return Database::getConnection();
}
