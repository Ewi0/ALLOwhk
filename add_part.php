<?php
// Database connection
$con = mysqli_connect("localhost", "root", "", "pbase");
if (!$con) {
    die("Cannot connect to server");
}

// Initialize variables to avoid undefined variable warnings
$article = $part_name = $quantity = $description = $price = $shelf = $alternative_barcode = '';
$message = ""; // To store feedback message
$existing_part_id = ""; // To store existing part's ID for updating
$reset_form = false; // Flag to reset form fields

if (isset($_POST['add_part'])) {
    // Get form input
    $article = $_POST["article"];
    $part_name = $_POST["part_name"];
    $quantity = $_POST["quantity"];
    $description = $_POST["description"];
    $price = $_POST["price"];
    $shelf = $_POST["shelf"];
    $alternative_barcode = $_POST["alternative_barcode"];

    // If the article is empty, generate a default article
    if (empty($article)) {
        $article = 'ALLO' . rand(0, 999999);
    }

    // Check if the article or barcode (including alternative) already exists
    $check_query = "SELECT * FROM parts WHERE article = '$article' OR barcode = '$alternative_barcode'";
    $result = mysqli_query($con, $check_query);

    if (mysqli_num_rows($result) > 0) {
        // Part already exists
        $row = mysqli_fetch_assoc($result);
        $existing_part_id = $row['id']; // Store the existing part's ID
        $message = "<div class='alert alert-warning' role='alert'>
                        Part with this article or barcode already exists in the database. 
                        <a href='edit.php?id=$existing_part_id' class='btn btn-primary btn-sm ml-3'>Update Existing Part</a>
                    </div>";
        // Keep the form data since part exists
        $reset_form = false;
    } else {
        // Part does not exist, reset the form data
        $reset_form = true;

        // Generate a random 9-digit number for the barcode, add the Latvian EAN prefix "475"
        $ean_base = '475' . str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT); 

        // Convert the barcode string to an array of digits
        $digits = str_split($ean_base);

        // Calculate the checksum according to the EAN-13 standard
        $sum_even = 0;
        $sum_odd = 0;

        for ($i = 0; $i < 12; $i++) {
            if ($i % 2 == 0) {
                $sum_odd += $digits[$i];
            } else {
                $sum_even += $digits[$i];
            }
        }

        // Final checksum calculation
        $checksum = (10 - (($sum_odd + ($sum_even * 3)) % 10)) % 10;

        // Append checksum to create the full EAN-13 barcode
        $ean13 = $ean_base . $checksum;

        // If the alternative barcode is provided, use it; otherwise, use the generated one
        $final_barcode = !empty($alternative_barcode) ? $alternative_barcode : $ean13;

        // Insert the new part into the database
        $q = "INSERT INTO parts (article, part_name, quantity, description, price, shelf, barcode)
              VALUES ('$article', '$part_name', '$quantity', '$description', '$price', '$shelf', '$final_barcode')";
        mysqli_query($con, $q);

        // Feedback message for successful addition
        $message = "<div class='alert alert-success' role='alert'>
                        New part added successfully!
                    </div>";

        // Clear the form fields after successful insertion
        $article = $part_name = $quantity = $description = $price = $shelf = $alternative_barcode = '';
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
                <input type="text" class="form-control" id="article" name="article" onkeyup="suggestArticle()" autocomplete="off" value="<?php echo !$reset_form ? htmlspecialchars($article) : ''; ?>" 
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
                       value="<?php echo !$reset_form ? htmlspecialchars($part_name) : ''; ?>" 
                       placeholder="Enter part name" required>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity:</label>
                <input type="number" class="form-control" id="quantity" name="quantity" 
                       value="<?php echo !$reset_form ? htmlspecialchars($quantity) : ''; ?>" 
                       placeholder="Enter quantity" step="0.01">
            </div>

            <div class="form-group">
                <label for="price">Price (€):</label>
                <input type="number" class="form-control" id="price" name="price" 
                       value="<?php echo !$reset_form ? htmlspecialchars($price) : ''; ?>" 
                       placeholder="Enter price in euros" step="0.001">
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea class="form-control" id="description" name="description" 
                          placeholder="Enter description"><?php echo !$reset_form ? htmlspecialchars($description) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="shelf">Shelf:</label>
                <input type="text" class="form-control" id="shelf" name="shelf" 
                       value="<?php echo !$reset_form ? htmlspecialchars($shelf) : ''; ?>" 
                       placeholder="Enter shelf location" required>
            </div>

            <div class="form-group">
                <label for="alternative_barcode">Alternative Barcode (optional):</label>
                <input type="text" class="form-control" id="alternative_barcode" name="alternative_barcode" 
                       value="<?php echo !$reset_form ? htmlspecialchars($alternative_barcode) : ''; ?>" 
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