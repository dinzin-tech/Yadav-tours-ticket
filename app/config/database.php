<?php
// app/config/database.php

function getDB() {
    static $db = null;
    
    if ($db === null) {
        try {
            $db = new PDO(
                "mysql:host=localhost;dbname=ticket_system;charset=utf8mb4",
                "root",
                "",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    }
    
    return $db;
}



class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            // Use constants or fallback to defaults
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $dbname = defined('DB_NAME') ? DB_NAME : 'ticket_system';
            $user = defined('DB_USER') ? DB_USER : 'root';
            $pass = defined('DB_PASSWORD') ? DB_PASSWORD : '';
            
            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }
    
    public static function execute($sql, $params = []) {
        $db = self::getInstance();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public static function fetch($sql, $params = []) {
        $stmt = self::execute($sql, $params);
        return $stmt->fetch();
    }
    
    public static function fetchAll($sql, $params = []) {
        $stmt = self::execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    public static function lastInsertId() {
        return self::getInstance()->lastInsertId();
    }
}
?>