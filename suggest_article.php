<?php
require 'dbc.php'; // Подключаем ваш файл с подключением

if (isset($_POST['article'])) {
    $article = $_POST['article'];

    // Подготавливаем запрос
    $stmt = $con->prepare("SELECT * FROM parts WHERE article LIKE ?");
    $like_article = "%" . $article . "%";
    $stmt->bind_param("s", $like_article);

    // Выполняем запрос
    $stmt->execute();

    // Получаем результат
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Выводим каждую деталь в виде кнопки, по нажатию вызываем fillForm
            echo '<div onclick="fillForm(' . htmlspecialchars(json_encode($row)) . ')">' . $row['article'] . '</div>';
        }
    } else {
        echo 'Нет совпадений';
    }

    // Закрываем запрос
    $stmt->close();
}
?>
