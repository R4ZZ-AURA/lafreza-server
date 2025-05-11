<?php
require_once '../shared/connection.php';
require_once '../shared/cors.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $id = $_GET['id'];

        try {
            $stmt = $pdo->prepare("SELECT * FROM staff_accounts WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $staff = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($staff) {
                // Use $staff not $row
                $fullName = trim($staff['staff_name']);

                // Split name into first and last name
                $nameParts = explode(" ", $fullName, 2); // Split into 2 parts only
                $firstName = $nameParts[0];
                $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

                // Send the response
                echo json_encode([
                    'success' => true,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'username' => $staff['staff_username'],
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'staff not found']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'staff ID is required']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['id'], $data['firstName'], $data['lastName'], $data['username'])) {
        $id = $data['id'];
        $firstName = trim($data['firstName']);
        $lastName = trim($data['lastName']);
        $fullName = $firstName . " " . $lastName; // Combine to save as full name
        $username = trim($data['username']);

        try {
            $stmt = $pdo->prepare("UPDATE staff_accounts SET staff_name = :fullName, staff_username = :username WHERE id = :id");
            $stmt->bindParam(':fullName', $fullName);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'staff details updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update staff details']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
