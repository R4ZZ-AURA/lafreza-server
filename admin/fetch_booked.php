<?php

require_once '../shared/connection.php';
require_once '../shared/cors.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {  // Use GET if you're not sending a body
    try {
        // Fetch today's bookings
        $query = "SELECT COUNT(*) as total_bookings 
                  FROM reservations 
                  WHERE DATE(created_at) = CURDATE()";  // This query counts bookings made today

        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'total_bookings' => $result['total_bookings']]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
