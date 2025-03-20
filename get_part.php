<?php
require_once 'autoload.php'; // Подключаем автозагрузку классов

header('Content-Type: application/json'); // Отправляем ответ в формате JSON

$db = new Database();                    // Создаём подключение к базе
$con = $db->getConnection();            // Получаем объект mysqli

if (isset($_GET['id'])) {
    $part_id = (int) $_GET['id'];       // Приводим id к числу для безопасности

    $stmt = $con->prepare("SELECT part_name, price, description, shelf FROM parts WHERE id = ?");
    $stmt->bind_param("i", $part_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        echo json_encode($result->fetch_assoc()); // Возвращаем найденную деталь
    } else {
        echo json_encode(["error" => "Part not found"]);
    }
} else {
    echo json_encode(["error" => "ID not provided"]);
}