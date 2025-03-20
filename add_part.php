<?php
require_once 'autoload.php';

$part = new Part();
$message = "";
$reset_form = false;
$data = [
    'article' => '',
    'part_name' => '',
    'quantity' => '',
    'description' => '',
    'price' => '',
    'shelf' => '',
    'barcode' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'article' => $_POST['article'] ?: 'ALLO' . rand(0, 999999),
        'part_name' => $_POST['part_name'],
        'quantity' => $_POST['quantity'],
        'description' => $_POST['description'],
        'price' => $_POST['price'],
        'shelf' => $_POST['shelf'],
        'barcode' => $_POST['alternative_barcode'] ?: $part->generateEAN13()
    ];

    $existing = $part->exists($data['article'], $data['barcode']);

    if ($existing) {
        $id = $existing['id'];
        $message = "<div class='alert alert-warning'>Такая деталь уже есть. <a href='edit.php?id=$id'>Редактировать</a></div>";
    } else {
        if ($part->insert($data)) {
            $message = "<div class='alert alert-success'>Деталь добавлена!</div>";
            $data = []; // очистим форму
            $reset_form = true;
        } else {
            $message = "<div class='alert alert-danger'>Ошибка при добавлении детали.</div>";
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Part</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Add New Part</h1>

        <!-- Feedback message -->
        <?php if (!empty($message)) { echo $message; } ?>

        <form action="add_part.php" method="post">
            <div class="form-group">
                <label for="article">Article:</label>
                <input type="text" class="form-control" id="article" name="article" onkeyup="suggestArticle()" autocomplete="off" value="<?= $reset_form ? '' : htmlspecialchars($data['article'] ?? '') ?>" 
                placeholder="Enter article">
                <small class="form-text text-muted">Please enter the article number without special characters (only digits).</small>
                <div id="suggestions" style="border: 1px solid #ccc;"></div> <!-- Место для предложений -->
            </div>

            <script>
                document.getElementById('article').addEventListener('input', function (e) {
                    let value = e.target.value;
                    value = value.replace(/[\s\-\/_(){}\[\]\"\'\.\:\;\,]/g, '');
                    e.target.value = value;
                });
            </script>

            <div class="form-group">
                <label for="part_name">Part Name:</label>
                <input type="text" class="form-control" id="part_name" name="part_name" 
                       value="<?= $reset_form ? '' : htmlspecialchars($data['part_name'] ?? '') ?>" 
                       placeholder="Enter part name" required>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity:</label>
                <input type="number" class="form-control" id="quantity" name="quantity" 
                       value="<?= $reset_form ? '' : htmlspecialchars($data['quatity'] ?? '') ?>" 
                       placeholder="Enter quantity" step="0.01">
            </div>

            <div class="form-group">
                <label for="price">Price (€):</label>
                <input type="number" class="form-control" id="price" name="price" 
                       value="<?= $reset_form ? '' : htmlspecialchars($data['price'] ?? '') ?>" 
                       placeholder="Enter price in euros" step="0.001">
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea class="form-control" id="description" name="description" 
                          placeholder="Enter description"><?= $reset_form ? '' : htmlspecialchars($data['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="shelf">Shelf:</label>
                <input type="text" class="form-control" id="shelf" name="shelf" 
                       value="<?= $reset_form ? '' : htmlspecialchars($data['shelf'] ?? '') ?>" 
                       placeholder="Enter shelf location" required>
            </div>

            <div class="form-group">
                <label for="alternative_barcode">Alternative Barcode (optional):</label>
                <input type="text" class="form-control" id="alternative_barcode" name="alternative_barcode" 
                       value="<?= $reset_form ? '' : htmlspecialchars($data['barcode'] ?? '') ?>" 
                       placeholder="Enter alternative barcode">
            </div>

            <button type="submit" class="btn btn-success" name="add_part">Add Part</button>
            <a href="index.php" class="btn btn-secondary">Back to List</a>
        </form>
    </div>
    <script>
function suggestArticle() {
    const article = document.getElementById('article').value;
    if (article.length > 2) { // Делаем запрос только если введено более 2 символов
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "suggest_article.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                document.getElementById('suggestions').innerHTML = xhr.responseText;
            }
        };
        xhr.send("article=" + article);
    } else {
        document.getElementById('suggestions').innerHTML = '';
    }
}

function fillForm(part) {
    // Заполняем поля на основе данных из базы
    document.getElementById('article').value = part.article;   // Заполняем article
    document.getElementById('alternative_barcode').value = part.barcode;   // Заполняем barcode
    document.getElementById('part_name').value = part.part_name; // Заполняем part_name
    document.getElementById('quantity').value = part.quantity; // Заполняем quantity
    document.getElementById('price').value = part.price;       // Заполняем price
    document.getElementById('shelf').value = part.shelf;       // Заполняем shelf

    document.getElementById('suggestions').innerHTML = ''; // Убираем подсказки после выбора
}
</script>
</body>
</html>