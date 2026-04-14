<?php
// includes/db.php
define('DB_PATH', __DIR__ . '/../database/finsight.db');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA foreign_keys = ON;');
            // Auto-init schema
            $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
            $pdo->exec($schema);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'DB: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
