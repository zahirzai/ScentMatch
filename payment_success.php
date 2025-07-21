<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['customerID'])) {
    die("User not logged in. Please <a href='login.php'>login</a> to proceed.");
}

// Database connection
$conn = new mysqli("localhost", "root", "", "scentmatch3");

// Check for database connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the customer ID from the session
$customerID = $_SESSION['customerID'];

// Fetch the items in the customer's cart
$cart_items_stmt = $conn->prepare("
    SELECT 
        OrderID, ProductID, Quantity, Subtotal 
    FROM 
        manage_order 
    WHERE 
        CustomerID = ? AND PaymentStatus = 'Unpaid'
");
$cart_items_stmt->bind_param("i", $customerID);
$cart_items_stmt->execute();
$cart_items_result = $cart_items_stmt->get_result();

// Initialize variables for prepared statements
$insert_sale_stmt = null;
$update_stock_stmt = null;
$delete_cart_item_stmt = null;

// Save the sales and clear the cart
if ($cart_items_result->num_rows > 0) {
    while ($cart_item = $cart_items_result->fetch_assoc()) {
        $productID = $cart_item['ProductID'];
        $quantity = $cart_item['Quantity'];
        $subtotal = $cart_item['Subtotal'];

        // Insert the sale into the `sales` table
        $insert_sale_stmt = $conn->prepare("
            INSERT INTO sales (ProductID, QuantitySold, SaleDate) 
            VALUES (?, ?, NOW())
        ");
        $insert_sale_stmt->bind_param("ii", $productID, $quantity);
        $insert_sale_stmt->execute();

        // Update the product stock
        $update_stock_stmt = $conn->prepare("
            UPDATE products 
            SET product_quantity = product_quantity - ? 
            WHERE product_id = ?
        ");
        $update_stock_stmt->bind_param("ii", $quantity, $productID);
        $update_stock_stmt->execute();

        // Delete the item from the cart
        $delete_cart_item_stmt = $conn->prepare("
            DELETE FROM manage_order 
            WHERE OrderID = ?
        ");
        $delete_cart_item_stmt->bind_param("i", $cart_item['OrderID']);
        $delete_cart_item_stmt->execute();
    }
}

// Close statements if they were initialized
if ($cart_items_stmt) {
    $cart_items_stmt->close();
}
if ($insert_sale_stmt) {
    $insert_sale_stmt->close();
}
if ($update_stock_stmt) {
    $update_stock_stmt->close();
}
if ($delete_cart_item_stmt) {
    $delete_cart_item_stmt->close();
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .success-container {
            text-align: center;
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }

        .success-container h1 {
            color: #4CAF50;
            font-size: 2rem;
            margin-bottom: 20px;
        }

        .success-container p {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 30px;
        }

        .success-container .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .success-container .btn:hover {
            background-color: #45a049;
        }
    </style>
</head>

<body>
    <div class="success-container">
        <h1>Payment Successful!</h1>
        <p>Thank you for your purchase. Your order has been successfully processed.</p>
        <a href="order.php" class="btn">Check your Order</a>
    </div>
</body>

</html>