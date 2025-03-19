<?php
include "dbc.php"; // Подключение к базе данных

if (isset($_GET['query'])) {
    $search_query = $_GET['query']; // Получение поискового запроса
    
    // Подготовленный запрос для предотвращения SQL-инъекций
    $stmt = $con->prepare("SELECT part_name, article, description, barcode 
                           FROM parts 
                           WHERE part_name LIKE ? 
                           OR description LIKE ? 
                           OR article LIKE ? 
                           OR barcode LIKE ?");
                           
    $like_query = "%$search_query%";
    $stmt->bind_param("ssss", $like_query, $like_query, $like_query, $like_query); // Привязка параметров
    $stmt->execute(); // Выполнение запроса
    
    $result = $stmt->get_result();
    
    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = [
            'part_name' => $row['part_name'],
            'article' => $row['article'],
            'description' => $row['description'],
            'barcode' => $row['barcode']
        ];
    }
    
    // Возврат JSON-ответа
    header('Content-Type: application/json');
    echo json_encode($suggestions);
}
?>