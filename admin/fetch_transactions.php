<?php
require_once '../shared/auth.php';
require_once '../shared/connection.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM transactions ORDER BY created_at DESC");
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $transactions]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
