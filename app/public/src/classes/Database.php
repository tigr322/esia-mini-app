<?php
namespace src\classes;

use PDO;

class Database
{
    private static ?self $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $host = '10.20.0.10';
        $dbname = 'cms';
        $user = 'root';
        $pass = '!QAZxdr5';

        try {
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $this->connection = new PDO($dsn, $user, $pass);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            throw new \PDOException("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    private function __clone() {}
    private function __wakeup() {}
}
?>