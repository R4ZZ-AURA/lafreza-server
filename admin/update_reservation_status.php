<?php
require_once '../shared/auth.php';
require_once '../shared/connection.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$data = json_decode(file_get_contents("php://input"), true);

$reservation_id = $data['reservation_id'] ?? null;
$new_status = $data['status'] ?? null;

if (!$reservation_id || !$new_status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE reservations SET status = :status, updated_at = NOW() WHERE reservation_id = :id");
    $stmt->bindParam(':status', $new_status);
    $stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($new_status === 'approved') {
        $fetch = $pdo->prepare("SELECT guest_id, total_price FROM reservations WHERE reservation_id = :id");
        $fetch->bindParam(':id', $reservation_id, PDO::PARAM_INT);
        $fetch->execute();
        $reservation = $fetch->fetch(PDO::FETCH_ASSOC);

        if ($reservation) {
            $guest_id = $reservation['guest_id'];
            $amount = $reservation['total_price'];
            $payment_method = 'gcash';
            $status = 'paid';
            $created_at = date("Y-m-d H:i:s");

            $insertTxn = $pdo->prepare("
                INSERT INTO transactions (guest_id, reservation_id, amount, payment_method, status, created_at)
                VALUES (:guest_id, :reservation_id, :amount, :payment_method, :status, :created_at)
            ");
            $insertTxn->execute([
                ':guest_id' => $guest_id,
                ':reservation_id' => $reservation_id,
                ':amount' => $amount,
                ':payment_method' => $payment_method,
                ':status' => $status,
                ':created_at' => $created_at
            ]);
        }
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
