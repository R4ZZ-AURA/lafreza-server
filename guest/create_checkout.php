<?php

require_once '../shared/connection.php';
require_once '../shared/cors.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = json_decode(file_get_contents('php://input'), true);
  $guest_id = $data['guest_id'] ?? null;
  $amount = $data['amount'] ?? null;

  if (!$guest_id || !$amount) {
    echo json_encode(['success' => false, 'message' => 'Guest ID and amount are required']);
    exit;
  }

  try {
    $cart_query = "SELECT * FROM cart WHERE guest_id = :guest_id";
    $stmt = $pdo->prepare($cart_query);
    $stmt->bindParam(':guest_id', $guest_id, PDO::PARAM_INT);
    $stmt->execute();

    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
      echo json_encode(['success' => false, 'message' => 'No items in the cart to process.']);
      exit;
    }

    $pdo->beginTransaction();

    $insert_query = "INSERT INTO reservations (guest_id, accommodation_id, date_from, date_to, total_price, status, created_at)
                         VALUES (:guest_id, :accommodation_id, :date_from, :date_to, :total_price, 'pending', NOW())";
    $insert_stmt = $pdo->prepare($insert_query);

    foreach ($cart_items as $item) {
      $insert_stmt->bindParam(':guest_id', $guest_id, PDO::PARAM_INT);
      $insert_stmt->bindParam(':accommodation_id', $item['accommodation_id'], PDO::PARAM_INT);
      $insert_stmt->bindParam(':date_from', $item['date_from'], PDO::PARAM_STR);
      $insert_stmt->bindParam(':date_to', $item['date_to'], PDO::PARAM_STR);
      $insert_stmt->bindParam(':total_price', $item['total_price'], PDO::PARAM_STR);

      $insert_stmt->execute();
    }

    $delete_cart_query = "DELETE FROM cart WHERE guest_id = :guest_id";
    $delete_stmt = $pdo->prepare($delete_cart_query);
    $delete_stmt->bindParam(':guest_id', $guest_id, PDO::PARAM_INT);
    $delete_stmt->execute();

    $pdo->commit();

    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "https://api.paymongo.com/v1/links",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode([
        'data' => [
          'attributes' => [
            'amount' => $amount,
            'description' => 'Downpayment for reservation',
            'remarks' => 'Checkout link for gcash payment',
            'redirect' => [
              'success' => 'http://localhost:3000/redirect-success.html',
            ]
          ]
        ]
      ]),
      CURLOPT_HTTPHEADER => [
        "accept: application/json",
        "authorization: Basic " . base64_encode("sk_test_R8ftDrMbfgQWsjnHHJv51tNV"),
        "content-type: application/json"
      ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
      echo json_encode(['success' => false, 'message' => "cURL Error: " . $err]);
      exit;
    }

    $data = json_decode($response, true);

    if (isset($data['data']['attributes']['checkout_url'])) {
      $checkoutUrl = $data['data']['attributes']['checkout_url'];
      echo json_encode(['success' => true, 'checkoutUrl' => $checkoutUrl]);
    } else {
      echo json_encode(['success' => false, 'message' => 'Failed to generate payment link']);
    }
  } catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
  }
}
