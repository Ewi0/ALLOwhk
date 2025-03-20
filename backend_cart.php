<?php
session_start();
require_once 'autoload.php';

$cart = new CartManager();
$response = ['status' => 'error', 'message' => 'Неверный запрос'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $part_id = isset($_POST['part_id']) ? intval($_POST['part_id']) : 0;
    $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 1;

    if ($quantity <= 0 && $action === 'update') {
        $action = 'remove';
    }

    switch ($action) {
        case 'add':
            $response = $cart->addToCart($part_id);
            break;
        case 'update':
            $response = $cart->updateQuantity($part_id, $quantity);
            break;
        case 'remove':
            $response = $cart->removeFromCart($part_id);
            break;
        case 'checkout':
            $response = $cart->checkout();
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $response = $cart->getCartHtml();
}

header('Content-Type: application/json');
echo json_encode($response);
