<?php
require_once 'autoload.php'; // Автозагрузка классов (Database)

if (isset($_GET['query'])) {
    $search_query = $_GET['query'];

    // Получаем соединение через OOP-класс
    $db = new Database();
    $con = $db->getConnection();

    // Подготавливаем безопасный SQL-запрос
    $stmt = $con->prepare("
        SELECT part_name, article, description, barcode
        FROM parts
        WHERE part_name LIKE ? 
           OR description LIKE ? 
           OR article LIKE ? 
           OR barcode LIKE ?
    ");

    // Готовим поисковую строку для LIKE
    $like_query = "%$search_query%";
    $stmt->bind_param("ssss", $like_query, $like_query, $like_query, $like_query);
    $stmt->execute();
    $result = $stmt->get_result();

    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = [
            'part_name'   => $row['part_name'],
            'article'     => $row['article'],
            'description' => $row['description'],
            'barcode'     => $row['barcode']
        ];
    }

    // Возвращаем JSON
    header('Content-Type: application/json');
    echo json_encode($suggestions);
}
?>