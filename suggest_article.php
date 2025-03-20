<?php
require_once 'autoload.php'; // Загружаем автозагрузку классов

if (isset($_POST['article'])) {
    $article = $_POST['article'];

    $db = new Database();                      // Создаем объект подключения к БД
    $con = $db->getConnection();               // Получаем mysqli-соединение

    $stmt = $con->prepare("SELECT * FROM parts WHERE article LIKE ?");
    $like_article = "%" . $article . "%";
    $stmt->bind_param("s", $like_article);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Выводим каждую деталь как <div> с onclick-обработчиком
            echo '<div onclick="fillForm(' . htmlspecialchars(json_encode($row)) . ')">' . $row['article'] . '</div>';
        }
    } else {
        echo 'Нет совпадений';
    }

    $stmt->close();
}
?>