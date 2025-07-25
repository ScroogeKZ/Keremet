<?php

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $dsn = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
        if (!$dsn) {
            throw new Exception('DATABASE_URL environment variable is required');
        }
        
        // Parse Neon database URL
        $parsed = parse_url($dsn);
        $host = $parsed['host'];
        $port = $parsed['port'] ?? 5432;
        $dbname = ltrim($parsed['path'], '/');
        $username = $parsed['user'];
        $password = $parsed['pass'];
        
        try {
            $this->connection = new PDO(
                "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}