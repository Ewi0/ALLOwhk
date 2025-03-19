<?php
  // Database connection
  $con = mysqli_connect("localhost", "root", "", "pbase");
  if (!$con) {
    die("Cannot connect to server");
  }

  // Fetch the part data to edit
  if (isset($_GET["id"])) {
    $id = $_GET["id"];
    $q = "SELECT * FROM parts WHERE id = $id";
    $result = mysqli_query($con, $q);
    $row = mysqli_fetch_array($result);

    // Initialize dealer variable safely
    // $dealer = isset($row["dealer"]) ? $row["dealer"] : "";
  }

  // Update the part data
  if (isset($_POST["part_name"])) {
    $id = $_POST["id"];
    $part_name = mysqli_real_escape_string($con, $_POST["part_name"]);
    $quantity = $_POST["quantity"];
    $description = mysqli_real_escape_string($con, $_POST["description"]);
    $article = mysqli_real_escape_string($con, $_POST["article"]);
    $price = $_POST["price"];
    $shelf = mysqli_real_escape_string($con, $_POST["shelf"]);
    // $dealer = isset($_POST["dealer"]) ? $_POST["dealer"] : ""; // Ensure dealer is set

    /*
    // Dealer-specific markup multipliers
    $dealer_markups = [
      "husqvarna" => 0.70,
      "stihl" => 0.76,
      "sils" => 0.75,
      // Add more dealers as needed
    ];
  // Calculate the new price with dealer markup and VAT, if applicable
  if (isset($dealer_markups[$dealer])) {
    $markup = $dealer_markups[$dealer];
    $vat = 1.21; // 21% VAT
    $final_price = $price * $vat / $markup;
  } else {
  // No markup and no VAT applied
  $final_price = $price;
  }
*/
    // Update query with the final price
    $q = "UPDATE parts SET 
            part_name = '$part_name', 
            quantity = $quantity, 
            description = '$description', 
            article = '$article', 
            price = $price, 
            shelf = '$shelf'
          WHERE id = $id";

    mysqli_query($con, $q);
    header("location: index.php");
    exit();
  }
?>

<html>
  <head>
    <title>Edit Part</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
  </head>
  <body>
    <div class="container mt-5">
      <h1>Edit Part</h1>
      <form action="edit.php" method="POST">
        <input type="hidden" name="id" value="<?php echo $row["id"]; ?>">
        
        <div class="form-group">
          <label>Part Name</label>
          <input type="text" class="form-control" placeholder="Part Name" name="part_name" value="<?php echo htmlspecialchars($row['part_name'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
          <label>Quantity</label>
          <input type="number" class="form-control" placeholder="Quantity" name="quantity" value="<?php echo $row["quantity"]; ?>" step="0.01">
        </div>

        <div class="form-group">
          <label>Article</label>
          <input type="text" class="form-control" placeholder="Article" name="article" value="<?php echo $row["article"]; ?>">
        </div>

        <div class="form-group">
          <label>Price</label>
          <input type="number" step="0.001" class="form-control" placeholder="Price" name="price" value="<?php echo $row["price"]; ?>">
        </div>

        <!-- <div class="form-group">
          <label>Dealer</label>
          <select class="form-control" name="dealer">
            <option select="selected" value="none" <?php echo $dealer == "none" ? "selected" : ""; ?>>No Dealer (No Markup or VAT)</option>
            <option value="husqvarna" <?php echo $dealer == "husqvarna" ? "selected" : ""; ?>>Husqvarna - 0.7</option>
            <option value="stihl" <?php echo $dealer == "stihl" ? "selected" : ""; ?>>Stihl - 0.76</option>
            <option value="sils" <?php echo $dealer == "sils" ? "selected" : ""; ?>>Sils - 0.75</option>
             Add more options for other dealers
          </select>
        </div>
        -->

        <div class="form-group">
          <label>Shelf</label>
          <input type="text" class="form-control" placeholder="Shelf" name="shelf" value="<?php echo $row["shelf"]; ?>">
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea class="form-control" placeholder="Description" name="description"><?php echo $row["description"]; ?></textarea>
        </div>

        <div class="form-group">
          <input type="submit" value="Update" class="btn btn-primary">
        </div>
      </form>
    </div>
  </body>
</html>
