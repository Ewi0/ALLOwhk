<?php
// Database connection
$con = mysqli_connect("localhost", "root", "", "pbase");
if (!$con) {
    die(json_encode(["error" => "Cannot connect to server"]));
}

// Check if the ID is provided
if (isset($_GET['id'])) {
    $part_id = (int) $_GET['id'];
    $query = "SELECT part_name, price, description, shelf FROM parts WHERE id = $part_id";
    $result = mysqli_query($con, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $part = mysqli_fetch_assoc($result);
        echo json_encode($part);
    } else {
        echo json_encode(["error" => "Part not found"]);
    }
} else {
    echo json_encode(["error" => "ID not provided"]);
}
?>
