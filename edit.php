<?php
  require_once 'autoload.php';
  
  $part = new Part();
  $message = "";
  
  if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
      $part->load($_GET['id']);
  }
  
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $part->id = $_POST['id'];
      $part->name = $_POST['part_name'];
      $part->quantity = $_POST['quantity'];
      $part->description = $_POST['description'];
      $part->article = $_POST['article'];
      $part->price = $_POST['price'];
      $part->shelf = $_POST['shelf'];
  
      if ($part->update()) {
          header("Location: index.php");
          exit();
      } else {
          $message = "Ошибка при обновлении записи.";
      }
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
      <?php if (!empty($message)) : ?>
        <div class="alert alert-danger"><?= $message ?></div>
      <?php endif; ?>
      <form action="edit.php" method="POST">
        <input type="hidden" name="id" value="<?php echo $part->id ?>">
        
        <div class="form-group">
          <label>Part Name</label>
          <input type="text" class="form-control" placeholder="Part Name" name="part_name" value="<?php echo htmlspecialchars($part->name, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
          <label>Quantity</label>
          <input type="number" class="form-control" placeholder="Quantity" name="quantity" value="<?php echo $part->quantity; ?>" step="0.01">
        </div>

        <div class="form-group">
          <label>Article</label>
          <input type="text" class="form-control" placeholder="Article" name="article" value="<?php echo $part->article; ?>">
        </div>

        <div class="form-group">
          <label>Price</label>
          <input type="number" step="0.001" class="form-control" placeholder="Price" name="price" value="<?php echo $part->price; ?>">
        </div>

        <div class="form-group">
          <label>Shelf</label>
          <input type="text" class="form-control" placeholder="Shelf" name="shelf" value="<?php echo $part->shelf; ?>">
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea class="form-control" placeholder="Description" name="description"><?php echo $part->description; ?></textarea>
        </div>

        <div class="form-group">
          <input type="submit" value="Update" class="btn btn-primary">
        </div>
      </form>
    </div>
  </body>
</html>
