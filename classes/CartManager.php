<?php

class CartManager {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    public function addToCart($part_id) {
        $stmt = $this->db->prepare("SELECT * FROM parts WHERE id = ?");
        $stmt->bind_param("i", $part_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $part = $result->fetch_assoc();

        if (!$part) return ['status' => 'error', 'message' => 'Товар не найден'];

        if (isset($_SESSION['cart'][$part_id])) {
            $_SESSION['cart'][$part_id]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$part_id] = [
                'part_name' => $part['part_name'],
                'price' => $part['price'],
                'quantity' => 1
            ];
        }

        return ['status' => 'success', 'message' => 'Товар добавлен в корзину'];
    }

    public function updateQuantity($part_id, $quantity) {
        if (isset($_SESSION['cart'][$part_id])) {
            if ($quantity > 0) {
                $_SESSION['cart'][$part_id]['quantity'] = $quantity;
            } else {
                unset($_SESSION['cart'][$part_id]);
            }
            return ['status' => 'success', 'message' => 'Количество обновлено'];
        }
        return ['status' => 'error', 'message' => 'Товар не найден в корзине'];
    }

    public function removeFromCart($part_id) {
        if (isset($_SESSION['cart'][$part_id])) {
            unset($_SESSION['cart'][$part_id]);
            return ['status' => 'success', 'message' => 'Товар удалён из корзины'];
        }
        return ['status' => 'error', 'message' => 'Товар не найден'];
    }

    public function checkout() {
        $errors = [];

        foreach ($_SESSION['cart'] as $id => $item) {
            $stmt = $this->db->prepare("SELECT quantity FROM parts WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $part = $result->fetch_assoc();

            if ($item['quantity'] > $part['quantity']) {
                $errors[] = "Недостаточно товара для: " . $item['part_name'] . ". Доступно: " . $part['quantity'] . ", Запрошено: " . $item['quantity'];
            }
        }

        if (!empty($errors)) {
            return ['status' => 'error', 'message' => implode(', ', $errors)];
        }

        foreach ($_SESSION['cart'] as $id => $item) {
            $stmt = $this->db->prepare("SELECT quantity FROM parts WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $part = $result->fetch_assoc();

            $new_quantity = floatval($part['quantity']) - floatval($item['quantity']);
            $update_stmt = $this->db->prepare("UPDATE parts SET quantity = ? WHERE id = ?");
            $update_stmt->bind_param("di", $new_quantity, $id);
            $update_stmt->execute();

            $insert_stmt = $this->db->prepare("INSERT INTO sales (part_id, part_name, quantity_sold, price_sold, sale_date) VALUES (?, ?, ?, ?, ?)");
            $now = date('Y-m-d H:i:s');
            $insert_stmt->bind_param("isdss", $id, $item['part_name'], $item['quantity'], $item['price'], $now);
            $insert_stmt->execute();
        }

        unset($_SESSION['cart']);
        return ['status' => 'success', 'message' => 'Заказ успешно оформлен'];
    }

    public function getCartHtml() {
        $html = '';
        $total_price = 0;

        foreach ($_SESSION['cart'] as $id => $item) {
            $stmt = $this->db->prepare("SELECT quantity FROM parts WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $part = $result->fetch_assoc();

            $actual_quantity = $part['quantity'];
            $quantity = $item['quantity'];
            $price = $item['price'];
            $total = $quantity * $price;
            $total_price += $total;

            $html .= "
                <tr>
                    <td>{$item['part_name']}</td>
                    <td class='text-center'>
                        <input type='number' class='cart-quantity' data-id='{$id}' value='{$quantity}' />
                        <small class='text-muted'>На складе: {$actual_quantity}</small>
                    </td>
                    <td class='text-center'>€{$price}</td>
                    <td class='text-center'>€" . number_format($total, 2) . "</td>
                    <td class='text-center'><button class='remove-from-cart btn btn-danger' data-id='{$id}'>Удалить</button></td>
                </tr>
            ";
        }

        return [
            'html' => $html,
            'total' => number_format($total_price, 2)
        ];
    }
}
