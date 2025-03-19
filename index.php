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
    <link rel="apple-touch-icon" sizes="180x180" href="ico/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="ico/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="ico/favicon-16x16.png">
    <link rel="manifest" href="ico/site.webmanifest">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <style>
    /* Custom styles */
    .table {
        margin-top: 20px;
        box-shadow: 0px 0px 15px rgba(0,0,0,0.1);
    }
    .table th, .table td {
        vertical-align: middle;
        text-align: center;
    }
    .container-fluid {
        padding-left: 15px;
        padding-right: 15px;
    }
    #popup-message {
            position: fixed;
            top: 20px;
            right: 20px;
            left: 20px;
            z-index: 9999;
            display: none;
        }
    .align-items-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .print-selected-button {
        margin-right: auto;
    }

    .suggestion-box {
    border: 1px solid #ccc;
    background-color: white;
    max-height: 200px;
    overflow-y: auto;
    position: absolute;
    z-index: 1000;
    width: 100%;  /* Make it match the width of the search input */
    max-width: calc(100vw - 40px);  /* Ensure it doesn't overflow the viewport (with some padding) */
    font-family: Arial, sans-serif;
    font-size: 14px;
    margin-bottom: 30px;
}

.suggestion-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
}

.suggestion-item strong {
    color: #333;
    font-weight: bold;
}

.suggestion-item small {
    color: #666;
}

.suggestion-item:hover {
    background-color: #f7f7f7;
}

.suggestion-item:last-child {
    border-bottom: none;  /* Remove bottom border for the last suggestion */
}



    @media print {
        @page {
            size: 56mm 38mm; /* Set the exact label size */
            margin: 0; /* Remove margins */
            padding: 5px;
        }

        body {
            visibility: hidden; /* Hide everything else */
            margin: 0;
            padding: 0;
        }

        .label {
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
            text-align: center;
            box-sizing: border-box;
            visibility: visible; /* Make the label content visible */
        }

        strong {
            font-size: 24px; /* Adjust size for strong text */
            margin: 0;
        }

        .label-content {
            margin: 1mm 0; /* Adjust spacing between items */
        }
    }

    .pagination {
        margin: 0;
        margin-bottom: 20px;
    }

    .pagination-container {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }
</style>


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

<script>
// Функция для отображения всплывающего сообщения
function showPopupMessage(message, type = 'success') {
    const popup = $('#popup-message');
    popup.removeClass('alert-success alert-danger').addClass('alert-' + type).text(message).fadeIn();

    // Исчезает через 3 секунды
    setTimeout(function() {
        popup.fadeOut();
    }, 3000);
}

// Функция для загрузки содержимого корзины
function loadCart() {
    $.ajax({
        url: 'backend_cart.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            $('#cart-items').html(response.html);
            $('#cart-total').text(response.total);
        },
        error: function(xhr, status, error) {
            console.error('Ошибка AJAX: ', error);
        }
    });
}

// Загрузка корзины при загрузке страницы
$(document).ready(function() {
    loadCart();

    // Добавление товара в корзину
    $(document).on('click', '.add-to-cart', function() {
        var partId = $(this).data('id');
        $.ajax({
            url: 'backend_cart.php',
            method: 'POST',
            data: { action: 'add', part_id: partId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    loadCart();
                    showPopupMessage('Товар добавлен в корзину!');
                } else {
                    showPopupMessage(response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ошибка AJAX: ', error);
                showPopupMessage('Ошибка при добавлении товара', 'danger');
            }
        });
    });

    // Обновление количества товара в корзине
$(document).on('change', '.cart-quantity', function() {
    var partId = $(this).data('id');
    var quantity = $(this).val();
    $.ajax({
        url: 'backend_cart.php',
        method: 'POST',
        data: { action: 'update', part_id: partId, quantity: quantity },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                loadCart();
                showPopupMessage(response.message);
            } else {
                showPopupMessage(response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            console.error('Ошибка AJAX: ', error);
            showPopupMessage('Ошибка при обновлении товара', 'danger');
        }
    });
});


    // Удаление товара из корзины
    $(document).on('click', '.remove-from-cart', function() {
        var partId = $(this).data('id');
        $.ajax({
            url: 'backend_cart.php',
            method: 'POST',
            data: { action: 'remove', part_id: partId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    loadCart();
                    showPopupMessage('Товар удален из корзины!');
                } else {
                    showPopupMessage(response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ошибка AJAX: ', error);
                showPopupMessage('Ошибка при удалении товара', 'danger');
            }
        });
    });

    // Оформление заказа (checkout)
    $('#checkout-btn').on('click', function() {
        $.ajax({
            url: 'backend_cart.php',
            method: 'POST',
            data: { action: 'checkout' },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('Заказ успешно оформлен!');
                    loadCart();  // Очищаем корзину
                } else {
                    alert('Ошибка: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ошибка AJAX: ', error);
            }
        });
    });
});
</script>


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

<script>
    $(document).ready(function() {
        // Фокусируемся на поле поиска при загрузке страницы
        $("#search").focus();

        // Обработка клика на "Добавить в корзину"
        $(this).on("click", function() {
            // Используем setTimeout, чтобы не сразу возвращать фокус
            setTimeout(function() {
                // Проверяем, не фокусируется ли пользователь на другом элементе
                if (!$(":focus").is("input, textarea, select")) {
                    $("#search").focus();
                }
            }, 200);
        });
    });
</script>



<!-- Suggestions box for dynamic search results -->
<div id="suggestionBox" class="suggestion-box"></div>



    <div class="align-items-container">
        <a href="add_part.php" class="btn btn-success" style="margin: 0 10px;">Add New Part</a>
        <button class="btn btn-secondary btn-sm print-selected-button no-print" onclick="printSelectedLabels()">Print Selected Labels</button>
        <div class="form-group">
            <label for="rows_per_page" class="mr-2">Show rows per page:</label>
            <select id="rows_per_page" class="form-control" style="width: auto; display: inline-block; margin-top: 20px;" onchange="window.location.href='?rows_per_page=' + this.value">
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
                <th>Select</th>
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
                    <td style="width: 50px;"><input type="checkbox" name="select_part" value="<?php echo $row['id']; ?>"></td>
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

<script>
  $(document).ready(function(){
    $('.copyData').click(function(){
      var $this = $(this);
      var originalText = $this.text().trim();
      var textToCopy = originalText;

      // Copy the text to the clipboard using the fallback method
      var $tempInput = $('<input>');
      $('body').append($tempInput);
      $tempInput.val(textToCopy).select();
      document.execCommand('copy');
      $tempInput.remove();

      // Change the text to indicate copying
      $this.text('Copied!');
      
      // Change the text back to the original after 1 seconds
      setTimeout(function(){
        $this.text(originalText);
      }, 1000); // 1000 milliseconds = 1 seconds
    });
  });
</script>


<script>
document.getElementById('search').addEventListener('input', function() {
    let query = this.value;

    if (query.length >= 2) {  // Trigger suggestions after 2 characters
        fetch('search_suggestions.php?query=' + query)
            .then(response => response.json())
            .then(data => {
                let suggestionBox = document.getElementById('suggestionBox');
                suggestionBox.innerHTML = '';  // Clear previous suggestions
                
                data.forEach(item => {
                    let suggestionDiv = document.createElement('div');
                    suggestionDiv.classList.add('suggestion-item');  // Add class for styling
                    
                    // Structure the suggestion box to show part name, article, description, and barcode
                    suggestionDiv.innerHTML = `
                        <strong>${item.part_name}</strong> <br>
                        <small>Article: ${item.article}</small> <br>
                        <small>Description: ${item.description}</small> <br>
                        <small>Barcode: ${item.barcode}</small>
                    `;
                    suggestionDiv.addEventListener('click', function() {
                        document.getElementById('search').value = item.part_name;  // Set part name in the input field
                        suggestionBox.innerHTML = '';  // Clear suggestions after selection
                    });
                    suggestionBox.appendChild(suggestionDiv);
                });
            })
            .catch(error => {
                console.error('Error fetching suggestions:', error);
            });
    } else {
        document.getElementById('suggestionBox').innerHTML = '';  // Clear suggestions if fewer than 2 characters are typed
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
// Function to detect barcode type based on the barcode input
function detectBarcodeType(barcode) {
    if (/^\\d{12}$/.test(barcode)) {
        return 'UPC';  // UPC-A (12 digits)
    } else if (/^\\d{13}$/.test(barcode)) {
        return 'EAN13';  // EAN-13 (13 digits)
    } else {
        return 'CODE128';  // Default to Code128 for alphanumeric or unknown formats
    }
}

// Function to generate the barcode and fit it to a 56mm width label
function generateBarcodeSVG(barcode) {
    const barcodeType = detectBarcodeType(barcode);

    // Generate the barcode directly into the SVG element using JsBarcode
    let svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    JsBarcode(svg, barcode, {
        format: barcodeType,  // Detected barcode type
        displayValue: true,   // Display the text below the barcode
        fontSize: 16,         // Font size for the text
        width: 1.1,  // Width of each bar, calculated to fit the 56mm label
        height: 10           // Height of the bars (can be adjusted if needed)
    });

    return svg.outerHTML;
}

// Function to print selected labels
function printSelectedLabels() {
    let selectedParts = [];
    let checkboxes = document.querySelectorAll('input[name="select_part"]:checked');

    checkboxes.forEach(function(checkbox) {
        selectedParts.push(checkbox.value); // Get selected part IDs
    });

    if (selectedParts.length === 0) {
        alert("Please select at least one part to print.");
        return;
    }

    let labelWindow = window.open('', '_blank', 'width=1280,height=720'); // Open a new window for the labels

    // Loop through each selected part to generate labels
    let allLabelsContent = '<html><head><title>Labels</title></head><body style="font-family: Arial, sans-serif; text-align: center;" onload="window.print(); window.onafterprint = window.close;">';

    selectedParts.forEach(function(partId) {
        let row = document.querySelector(`tr[data-id='${partId}']`);
        if (!row) return;

        let article = row.cells[1].textContent;
        let partName = row.cells[2].textContent;
        let price = row.cells[4].textContent;
        let shelf = row.cells[5].textContent;
        let barcode = row.getAttribute('data-barcode'); // Get the barcode from the row's data attribute

        // Generate the SVG barcode for each part using the updated JsBarcode-based logic
        let barcodeSVG = generateBarcodeSVG(barcode);

        // Append each label content to the variable with centered content and padding above the article
        allLabelsContent += `
            <div class="label" style="margin-bottom: 20px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                <div style="padding-top: 2px;"> <!-- Padding only above the article -->
                    <strong style="font-size: 18px;">${article}</strong>
                </div>
                <div style="font-size: 14px;">${partName}</div>
                <strong style="font-size: 20px; margin: 1px 1px 0 0">${price}</strong>
                <div>${barcodeSVG}</div>
            </div>`;
    });

    // Close the document after all labels are added
    allLabelsContent += '</body></html>';
    labelWindow.document.write(allLabelsContent);
    labelWindow.document.close();
}

// Function to print individual labels with padding above the article
function printLabel(partId) {
    let row = document.querySelector(`tr[data-id='${partId}']`);
    if (!row) return;

    let article = row.cells[1].textContent;
    let partName = row.cells[2].textContent;
    let price = row.cells[4].textContent;
    let shelf = row.cells[5].textContent;
    let barcode = row.getAttribute('data-barcode'); // Get the barcode from the row's data attribute

    let labelWindow = window.open('', '_blank', 'width=1280,height=720');

    // Inject the SVG barcode using the JsBarcode-based function
    let barcodeSVG = generateBarcodeSVG(barcode);

    labelWindow.document.write(`
        <html>
        <head><title>Label</title></head>
        <body onload="window.print(); window.onafterprint = window.close();" style="font-family: Arial, sans-serif; text-align: center;">
            <div class="label" style="margin-bottom: 20px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                <div style="padding-top: 2px;"> <!-- Padding only above the article -->
                    <strong style="font-size: 16px;">${article}</strong>
                </div>
                <div style="font-size: 14px;">${partName}</div>
                <strong style="font-size: 20px; margin: 1px 1px 0 0">${price}</strong>
                <div>${barcodeSVG}</div>
            </div>
        </body>
        </html>
    `);
    labelWindow.document.close();
}
</script>

<script>
setInterval(() => {
    fetch('backend_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=check_idle_auto_checkout'
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'auto_checked_out') {
            alert("Корзина была автоматически оформлена.");
            location.reload();
        }
    });
}, 30000);
</script>


</body>
</html>