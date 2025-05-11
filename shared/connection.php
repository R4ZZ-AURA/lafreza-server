<?php


$dbHost = getenv("DB_HOST") ?: "localhost";
$dbUser = getenv("DB_USER") ?: "root";
$dbPass = getenv("DB_PASSWORD") ?: "";
$dbName   = getenv("DB_NAME") ?: "lafrezagold_db";

// data source name
$dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName;

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Conection failed: ' . $e->getMessage());
}
