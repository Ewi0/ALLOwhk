<?php

include "dbc.php";
session_start();  // Start session to manage the cart

// Шаг 1: Устанавливаем значения для пагинации
$rows_per_page = isset($_GET['rows_per_page']) ? (int)$_GET['rows_per_page'] : 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $rows_per_page;

// Шаг 2: Проверяем поисковый запрос
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';
$search_condition = '';

if (!empty($search_term)) {
    $terms = explode(' ', $search_term);
    $search_condition = " WHERE ";

    $conditions = array();
    foreach ($terms as $term) {
        $conditions[] = "(part_name LIKE '%$term%' OR description LIKE '%$term%' OR article LIKE '%$term%' OR barcode LIKE '%$term%')";
    }

    $search_condition .= implode(' AND ', $conditions);
}

// Получаем общее количество строк с учетом поискового запроса
$total_rows_query = "SELECT COUNT(*) AS total FROM parts" . $search_condition;
$total_rows_result = mysqli_query($con, $total_rows_query);
$total_rows = mysqli_fetch_assoc($total_rows_result)['total'];

// Пересчет общего количества страниц
$total_pages = ceil($total_rows / $rows_per_page);

// Если строк меньше, чем количество на одну страницу, установить total_pages в 1
if ($total_pages == 0) {
    $total_pages = 1;
}

// Проверка, что текущая страница валидна
if ($page > $total_pages) {
    $page = $total_pages;
} elseif ($page < 1) {
    $page = 1;
}

// Шаг 4: Получение данных для текущей страницы
$q = "SELECT * FROM parts" . $search_condition . " ORDER BY id DESC LIMIT $offset, $rows_per_page";

// Выполнение запроса
$stmt = $con->prepare($q);
$stmt->execute();
$parts_result = $stmt->get_result(); // Результат запрашивается в отдельной переменной
?>

<!DOCTYPE html>
<html>
<head>
    <title>Parts List</title>
    <link rel="apple-touch-icon" sizes="180x180" href="assets/ico/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/ico/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/ico/favicon-16x16.png">
    <link rel="manifest" href="ico/site.webmanifest">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="assets/styles/style.css">
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
            <!-- Здесь будут загружаться товары из корзины через AJAX -->
        </tbody>
    </table>
    <div class="text-right">
        <strong>Общая сумма: €<span id="cart-total">0.00</span></strong>
    </div>
    <div class="text-right mt-3">
        <button id="checkout-btn" class="btn btn-success">Оформить заказ</button>
    </div>
</div>

<!-- Контейнер для всплывающих сообщений -->
<div id="popup-message" class="alert alert-success"></div>

    <h1 class="mb-4">Parts List</h1>

<!-- Search Form -->
<div class="container-fluid" style="height: 100%;">
    <form method="GET" action="index.php" class="form-inline row mb-4">
        <div class="col-12">
            <input type="text" id="search" name="search" class="form-control w-100 mb-2" placeholder="Search by part name, description, or article" autofocus>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary w-100">Search</button>
        </div>
        <div class="col-12">
            <small class="d-block mt-2">Last search: <?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?></small>
        </div>
    </form>
</div>

<!-- Suggestions box for dynamic search results -->
<div id="suggestionBox" class="suggestion-box"></div>



    <div class="align-items-container">
        <a href="add_part.php" class="btn btn-success" style="margin: 0 10px;">Add New Part</a>
        <div class="form-group">
            <label for="rows_per_page" class="mr-2">Show rows per page:</label>
            <select id="rows_per_page" class="form-control" style="width: auto; display: inline-block; margin-top: 20px; margin-right: 10px;" onchange="window.location.href='?rows_per_page=' + this.value">
                <option value="5" <?php if($rows_per_page == 5) echo 'selected'; ?>>5</option>
                <option value="10" <?php if($rows_per_page == 10) echo 'selected'; ?>>10</option>
                <option value="20" <?php if($rows_per_page == 20) echo 'selected'; ?>>20</option>
                <option value="50" <?php if($rows_per_page == 50) echo 'selected'; ?>>50</option>
            </select>
        </div>
    </div>


    <!-- Список запчастей -->
    <h1 class="mb-4">Parts List</h1>
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
            <?php while ($row = $parts_result->fetch_assoc()): ?>
                <tr data-id="<?php echo $row['id']; ?>" data-barcode="<?php echo $row['barcode']; ?>">
                    <td class="copyData"><?php echo $row['article']; ?></td>
                    <td><?php echo $row['part_name']; ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td>€<?php echo number_format($row['price'], 2); ?></td>
                    <td><?php echo $row['shelf']; ?></td>
                    <td style="width: 260px;"><?php echo $row['description']; ?></td>
                    <td>
                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">Edit</a>
                        <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                        <button class="btn btn-secondary btn-sm print-button no-print" onclick="printLabel(<?php echo $row['id']; ?>)">Print Label</button>
                        <button class="btn btn-primary add-to-cart" data-id="<?php echo $row['id']; ?>">Добавить в корзину</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

<?php
// Пагинация с сокращённым отображением страниц
if ($total_pages > 1): ?>
    <div class="d-flex justify-content-center mt-4">
        <nav aria-label="Page navigation example">
            <ul class="pagination">
                <!-- Previous page link -->
                <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                    <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&rows_per_page=<?php echo $rows_per_page; ?>&search=<?php echo urlencode($search_term); ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>

                <!-- Показать первую страницу -->
                <?php if ($page > 2): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1&rows_per_page=<?php echo $rows_per_page; ?>&search=<?php echo urlencode($search_term); ?>">1</a>
                    </li>
                    <?php if ($page > 3): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Показать текущую и соседние страницы -->
                <?php for ($i = max(1, $page - 1); $i <= min($total_pages, $page + 1); $i++): ?>
                    <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&rows_per_page=<?php echo $rows_per_page; ?>&search=<?php echo urlencode($search_term); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <!-- Показать последнюю страницу -->
                <?php if ($page < $total_pages - 1): ?>
                    <?php if ($page < $total_pages - 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&rows_per_page=<?php echo $rows_per_page; ?>&search=<?php echo urlencode($search_term); ?>"><?php echo $total_pages; ?></a>
                    </li>
                <?php endif; ?>

                <!-- Next page link -->
                <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                    <a class="page-link" href="?page=<?php echo min($total_pages, $page + 1); ?>&rows_per_page=<?php echo $rows_per_page; ?>&search=<?php echo urlencode($search_term); ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>