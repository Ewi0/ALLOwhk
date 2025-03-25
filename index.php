<?php
require_once 'autoload.php';

// Обработка удаления детали
if (isset($_GET['delete_id'])) {
    $part = new Part();
    $part->load((int)$_GET['delete_id']);
    
    if ($part->delete()) {
        $_SESSION['success_message'] = "Деталь успешно удалена.";
    } else {
        $_SESSION['error_message'] = "Ошибка при удалении детали.";
    }
    
    // Перенаправляем на главную страницу
    header("Location: index.php");
    exit;
}

session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$part = new Part();

// Получаем параметры запроса
$search = $_POST['search'] ?? '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$rows_per_page = isset($_GET['rows_per_page']) ? (int) $_GET['rows_per_page'] : 10;
$offset = ($page - 1) * $rows_per_page;

// Получаем результаты поиска и общее количество
$parts = $part->searchParts($search, $offset, $rows_per_page);
$total_rows = $part->countParts($search);
$total_pages = ceil($total_rows / $rows_per_page);

$rows_per_page = isset($_GET['rows_per_page']) ? (int) $_GET['rows_per_page'] : 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $rows_per_page;

// Получаем детали и количество
$parts = $part->searchParts($search_term, $offset, $rows_per_page);
$total_rows = $part->countParts($search_term);
$total_pages = ceil($total_rows / $rows_per_page);

// Защита от недопустимых значений
if ($total_pages < 1)
    $total_pages = 1;
if ($page > $total_pages)
    $page = $total_pages;
if ($page < 1)
    $page = 1;
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Parts List</title>
    <link rel="apple-touch-icon" sizes="180x180" href="assets/ico/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/ico/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/ico/favicon-16x16.png">
    <link rel="manifest" href="ico/site.webmanifest">

    <link rel="stylesheet" href="assets/styles/bootstrap.min.css">
    <link rel="stylesheet" href="assets/styles/style.css">

    <script src="assets/js/jquery-3.7.1.min.js"></script>
</head>

<body>
    <div class="text-right" style="padding: 5px 10px;">
        <span>👤 <?= $_SESSION['user'] ?></span>
        <a href="logout.php" class="btn btn-sm btn-outline-secondary ml-2">Выйти</a>
    </div>
    <div class="container-fluid">
        <h2>Корзина</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Название товара</th>
                    <th>Количество</th>
                    <th>Цена</th>
                    <th>Итого</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody id="cart-items">
                <!-- Подгружается через AJAX -->
            </tbody>
        </table>
        <div class="text-right">
            <strong>Общая сумма: €<span id="cart-total">0.00</span></strong>
        </div>
        <div class="text-right mt-3">
            <button id="checkout-btn" class="btn btn-success">Оформить заказ</button>
        </div>

        <div id="popup-message" class="alert alert-success" style="display:none;"></div>

        <h1 class="mb-4">Список деталей</h1>

        <form method="GET" action="index.php" class="form-inline row mb-4">
            <div class="col-12">
                <input type="text" id="search" name="search" class="form-control w-100 mb-2"
                    placeholder="Поиск по Артикулу, Названию, Описанию или штрих-коду" value="" autofocus>

            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary w-100">🔍 Search</button>
            </div>
            <div class="col-12">
                <small class="d-block mt-2">Last search: <?= htmlspecialchars($search_term) ?></small>
            </div>
            <div class="col-12">
                <div id="suggestionBox" class="suggestion-box"></div>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="add_part.php" class="btn btn-success">➕ Добавить деталь</a>
            <form method="GET" class="form-inline">
                <label class="mr-2">Показать строки на странице:</label>
                <select name="rows_per_page" class="form-control" onchange="this.form.submit()">
                    <?php foreach ([5, 10, 20, 50] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $rows_per_page == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_term) ?>">
            </form>
        </div>

        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Артикул</th>
                    <th>Название детали</th>
                    <th>Количество</th>
                    <th>Цена (€)</th>
                    <th>Полка</th>
                    <th>Описание</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($parts as $row): ?>
                    <tr data-id="<?= $row['id'] ?>" data-barcode="<?= $row['barcode'] ?>">
                        <td class="copyData"><?= htmlspecialchars($row['article']) ?></td>
                        <td>
                            <a href="part_history.php?id=<?= $row['id'] ?>">
                                <?= htmlspecialchars($row['part_name']) ?>
                            </a>
                        </td>
                        <td><?= $row['quantity'] ?></td>
                        <td>€<?= number_format($row['price'], 2) ?></td>
                        <td><?= htmlspecialchars($row['shelf']) ?></td>
                        <td style="width: 260px;"><?= htmlspecialchars($row['description']) ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm add-to-cart" data-id="<?= $row['id'] ?>">🛒 Добавить в
                                корзину</button>
                            <button class="btn btn-secondary btn-sm print-button no-print"
                                onclick="printLabel(<?= $row['id'] ?>)">🖨️ Печать</button>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm">✏️ Редактировать</a>
                            <a href="?delete_id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">🗑️ Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <!-- Предыдущая -->
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link"
                                href="?page=<?= max(1, $page - 1) ?>&rows_per_page=<?= $rows_per_page ?>&search=<?= urlencode($search_term) ?>">&laquo;</a>
                        </li>

                        <!-- Первая -->
                        <?php if ($page > 2): ?>
                            <li class="page-item"><a class="page-link"
                                    href="?page=1&rows_per_page=<?= $rows_per_page ?>&search=<?= urlencode($search_term) ?>">1</a>
                            </li>
                            <?php if ($page > 3): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Текущая ±1 -->
                        <?php for ($i = max(1, $page - 1); $i <= min($total_pages, $page + 1); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link"
                                    href="?page=<?= $i ?>&rows_per_page=<?= $rows_per_page ?>&search=<?= urlencode($search_term) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Последняя -->
                        <?php if ($page < $total_pages - 1): ?>
                            <?php if ($page < $total_pages - 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item"><a class="page-link"
                                    href="?page=<?= $total_pages ?>&rows_per_page=<?= $rows_per_page ?>&search=<?= urlencode($search_term) ?>"><?= $total_pages ?></a>
                            </li>
                        <?php endif; ?>

                        <!-- Следующая -->
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link"
                                href="?page=<?= min($total_pages, $page + 1) ?>&rows_per_page=<?= $rows_per_page ?>&search=<?= urlencode($search_term) ?>">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>

        <!-- JS и завершение -->
        <script src="assets/js/JsBarcode.all.min.js"></script>
        <script src="assets/js/main.js"></script>
    </div>
</body>

</html>