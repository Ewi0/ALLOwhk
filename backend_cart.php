<?php
session_start();
include 'dbc.php';  // Подключение к базе данных

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $part_id = isset($_POST['part_id']) ? intval($_POST['part_id']) : 0;
    $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 1;

    // Проверка на случай ввода 0 или отрицательного значения: удаляем товар из корзины
    if ($quantity <= 0) {
        $action = 'update';  // Меняем действие на удаление
    }

    switch ($action) {
        // Добавление товара в корзину
        case 'add':
            $query = "SELECT * FROM parts WHERE id = $part_id";
            $result = mysqli_query($con, $query);
            $part = mysqli_fetch_assoc($result);

            if ($part) {
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }

                $cart = $_SESSION['cart'];

                if (isset($cart[$part_id])) {
                    $cart[$part_id]['quantity'] += 1;  // Добавляем товар к текущему количеству
                } else {
                    $cart[$part_id] = [
                        'part_name' => $part['part_name'],
                        'price' => $part['price'],
                        'quantity' => 1
                    ];
                }

                $_SESSION['cart'] = $cart;

                echo json_encode(['status' => 'success', 'message' => 'Товар добавлен в корзину']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Товар не найден']);
            }
            break;

        // Обновление количества товара в корзине
        case 'update':
            if (isset($_SESSION['cart'][$part_id])) {
                if ($quantity >= 0) {
                    // Положительное количество перезаписывает текущее количество
                    $_SESSION['cart'][$part_id]['quantity'] = $quantity;
                } else {
                    // Отрицательное количество уменьшает текущее количество
                    $_SESSION['cart'][$part_id]['quantity'] += $quantity;
                }

                // Если количество товара становится меньше или равно нулю, удаляем его из корзины
                if ($_SESSION['cart'][$part_id]['quantity'] <= 0) {
                    unset($_SESSION['cart'][$part_id]);
                }

                echo json_encode(['status' => 'success', 'message' => 'Количество товара обновлено']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Товар не найден в корзине']);
            }
            break;

        // Удаление товара из корзины
        case 'remove':
            if (isset($_SESSION['cart'][$part_id])) {
                unset($_SESSION['cart'][$part_id]); // Удаляем товар из корзины
                echo json_encode(['status' => 'success', 'message' => 'Товар удален из корзины']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Товар не найден в корзине']);
            }
            break;

        // Оформление заказа (checkout)
        case 'checkout':
            $errors = [];

            // Проверка наличия товара на складе
            foreach ($_SESSION['cart'] as $id => $item) {
                $query = "SELECT quantity FROM parts WHERE id = $id";
                $result = mysqli_query($con, $query);
                $part = mysqli_fetch_assoc($result);

                // Проверяем, достаточно ли товара на складе
                if ($item['quantity'] > $part['quantity']) {
                    $errors[] = "Недостаточно товара для: " . $item['part_name'] . ". Доступно: " . $part['quantity'] . ", Запрошено: " . $item['quantity'];
                }
            }

            if (empty($errors)) {
                // Если ошибок нет, обновляем количество на складе и записываем информацию о продаже
                foreach ($_SESSION['cart'] as $id => $item) {
                    // Обновляем количество в базе данных
                    $query = "SELECT quantity FROM parts WHERE id = $id";
                    $result = mysqli_query($con, $query);
                    $part = mysqli_fetch_assoc($result);

                    $new_quantity = floatval($part['quantity']) - floatval($item['quantity']);
                    $update_query = "UPDATE parts SET quantity = $new_quantity WHERE id = $id";
                    mysqli_query($con, $update_query);

                    // Записываем информацию о продаже
                    $part_id = $id;
                    $part_name = mysqli_real_escape_string($con, $item['part_name']);
                    $quantity = floatval($item['quantity']);
                    $price = $item['price'];
                    $sale_date = date('Y-m-d H:i:s');

                    $insert_query = "INSERT INTO sales (part_id, part_name, quantity_sold, price_sold, sale_date) 
                                     VALUES ($part_id, '$part_name', $quantity, $price, '$sale_date')";
                    mysqli_query($con, $insert_query);
                }

                // Очищаем корзину после завершения покупки
                unset($_SESSION['cart']);
                echo json_encode(['status' => 'success', 'message' => 'Заказ успешно оформлен']);
            } else {
                echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Неизвестное действие']);
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Загрузка содержимого корзины с отображением актуального количества на складе
    $response = ['html' => '', 'total' => 0];
    $total_price = 0;

    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $id => $item) {
            // Получаем актуальное количество товара на складе
            $query = "SELECT quantity FROM parts WHERE id = $id";
            $result = mysqli_query($con, $query);
            $part = mysqli_fetch_assoc($result);
            $actual_quantity = $part['quantity'];

            $part_name = $item['part_name'];
            $quantity = $item['quantity'];
            $price = $item['price'];
            $total = $price * $quantity;
            $total_price += $total;

            // Добавляем отображение актуального количества на складе под полем количества
            $response['html'] .= "
                <tr>
                    <td>{$part_name}</td>
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
    }

    $response['total'] = number_format($total_price, 2);
    echo json_encode($response);
}
?>