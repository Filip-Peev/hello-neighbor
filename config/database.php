<?php
date_default_timezone_set('Europe/Sofia');
class Database
{
    private static $instance = null;

    public static function getConnection()
    {
        if (self::$instance === null) {
            $envPath = __DIR__ . '/../.env';

            if (!file_exists($envPath)) {
                die("Error: Configuration file (.env) is missing. Please run the installer.");
            }

            $env = parse_ini_file($envPath);

            $host = $env['DB_HOST']      ?? '';
            $port = $env['DB_PORT']      ?? '';
            $db   = $env['DB_NAME']      ?? '';
            $user = $env['DB_USER']      ?? '';
            $pass = $env['MARIADB_PASS'] ?? '';

            try {
                $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                die("Database connection failed. Check your .env settings or run the installer again.");
            }
        }
        return self::$instance;
    }
}
