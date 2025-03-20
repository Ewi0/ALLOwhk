<?php
require_once 'autoload.php';
session_start();

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
?>



<?php
require_once 'autoload.php';

$part = new Part();

$rows_per_page = isset($_GET['rows_per_page']) ? (int)$_GET['rows_per_page'] : 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $rows_per_page;

// Получаем детали и количество
$parts = $part->searchParts($search_term, $offset, $rows_per_page);
$total_rows = $part->countParts($search_term);
$total_pages = ceil($total_rows / $rows_per_page);

// Защита от недопустимых значений
if ($total_pages < 1) $total_pages = 1;
if ($page > $total_pages) $page = $total_pages;
if ($page < 1) $page = 1;
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

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/styles/style.css">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>

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

    <h1 class="mb-4">Parts List</h1>

    <form method="GET" action="index.php" class="form-inline row mb-4">
        <div class="col-12">
            <input type="text" id="search" name="search" class="form-control w-100 mb-2" placeholder="Search by part name, description, or article" value="" autofocus>
            
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary w-100">Search</button>
        </div>
        <div class="col-12">
            <small class="d-block mt-2">Last search: <?= htmlspecialchars($search_term) ?></small>
        </div>
        <div class="col-12">
            <div id="suggestionBox" class="suggestion-box"></div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="add_part.php" class="btn btn-success">Add New Part</a>
        <form method="GET" class="form-inline">
            <label class="mr-2">Show rows per page:</label>
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
                <th>Article</th>
                <th>Part Name</th>
                <th>Quantity</th>
                <th>Price (€)</th>
                <th>Shelf</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($parts as $row): ?>
            <tr data-id="<?= $row['id'] ?>" data-barcode="<?= $row['barcode'] ?>">
                <td class="copyData"><?= htmlspecialchars($row['article']) ?></td>
                <td><?= htmlspecialchars($row['part_name']) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td>€<?= number_format($row['price'], 2) ?></td>
                <td><?= htmlspecialchars($row['shelf']) ?></td>
                <td style="width: 260px;"><?= htmlspecialchars($row['description']) ?></td>
                <td>
                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm">Edit</a>
                    <a href="?delete_id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                    <button class="btn btn-secondary btn-sm print-button no-print" onclick="printLabel(<?= $row['id'] ?>)">Print Label</button>
                    <button class="btn btn-primary btn-sm add-to-cart" data-id="<?= $row['id'] ?>">Добавить в корзину</button>
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
                    <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&rows_per_page=<?= $rows_per_page ?>&search=<?= urlencode($search_term) ?>">&laquo;</a>
                </li>

                <!-- Первая -->
                <?php if ($page > 2): ?>
                    <li class="page-item"><a class="page-link" href="?page=1&rows_per_page=<?= $rows_per_page ?>&search=<?= urlencode($search_term) ?>">1</a></li>
                    <?php if ($page > 3): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Текущая ±1 -->
                <?php for ($i = max(1, $page - 1); $i <= min($total_pages, $page + 1); $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&rows_per_page=<?= $rows_per_page ?>&search=<?= urlencode($search_term) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <!-- Последняя -->
                <?php if ($page < $total_pages - 1): ?>
                    <?php if ($page < $total_pages - 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item"><a class="page-link" href="?page=<?= $total_pages ?>&rows_per_page=<?= $rows_per_page ?>&search=<?= urlencode($search_term) ?>"><?= $total_pages ?></a></li>
                <?php endif; ?>

                <!-- Следующая -->
                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= min($total_pages, $page + 1) ?>&rows_per_page=<?= $rows_per_page ?>&search=<?= urlencode($search_term) ?>">&raquo;</a>
                </li>
            </ul>
        </nav>
    </div>
<?php endif; ?>

<!-- JS и завершение -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="assets/js/main.js"></script>
</div>
</body>
</html>