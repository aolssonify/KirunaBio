<?php
header('Content-type: text/html; charset=utf-8');

try {
    // Använd tjänstnamnet 'db' som host eftersom det är vad MySQL-tjänsten heter i Docker Compose
    $dsn = 'mysql:host=db;dbname=kirunabio;charset=utf8mb4';
    $pdo = new PDO($dsn, 'user', 'password', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log("Databasanslutningsfel: " . $e->getMessage(), 3, 'error_log.txt');
    die("Anslutningsfel: " . $e->getMessage());
}
?>
