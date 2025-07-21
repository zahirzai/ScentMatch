<?php
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");

// Check for database connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : null;
    $review = isset($_POST['review']) ? trim($_POST['review']) : null;

    if ($product_id && $user_id && $rating && $review) {
        $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $product_id, $user_id, $rating, $review);
        if ($stmt->execute()) {
            header("Location: product_details.php?product_id=$product_id&success=review_submitted");
        } else {
            header("Location: product_details.php?product_id=$product_id&error=review_failed");
        }
    } else {
        header("Location: product_details.php?product_id=$product_id&error=invalid_input");
    }
}
?>
