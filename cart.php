<?php
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");

// Check for database connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['customerID'])) {
    header("Location: ../customer/login.php");
    exit();
}

$customer_id = $_SESSION['customerID']; // Use CustomerID from the session

// Update existing cart items to have ShipmentStatus = 'Cart'
$update_existing = $conn->prepare("
    UPDATE manage_order 
    SET ShipmentStatus = 'Cart' 
    WHERE CustomerID = ? 
    AND PaymentStatus = 'Unpaid' 
    AND (ShipmentStatus IS NULL OR ShipmentStatus = '')
");
$update_existing->bind_param("i", $customer_id);
$update_existing->execute();

// Handle Add, Update, and Remove actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity'] ?? 1);
    $response = ['success' => false, 'message' => '', 'cartCount' => 0];

    try {
        if ($_POST['action'] === 'add') {
            // Check if the product exists and has enough quantity
            $check_product = $conn->prepare("
                SELECT p.product_quantity, p.price, 
                       pr.promotion_id, pr.discount_type, pr.discount_value
                FROM products p
                LEFT JOIN promotions pr ON p.product_id = pr.product_id 
                    AND pr.start_date <= NOW() 
                    AND pr.end_date >= NOW()
                WHERE p.product_id = ?
            ");
            $check_product->bind_param("i", $product_id);
            $check_product->execute();
            $product_result = $check_product->get_result();
            
            if ($product_result->num_rows === 0) {
                throw new Exception('Product not found');
            }
            
            $product_data = $product_result->fetch_assoc();
            if ($product_data['product_quantity'] < $quantity) {
                throw new Exception('Not enough stock available');
            }

            // Calculate the actual price considering promotion
            $actual_price = $product_data['price'];
            if ($product_data['promotion_id']) {
                if ($product_data['discount_type'] == 'percentage') {
                    $actual_price = $product_data['price'] * (1 - ($product_data['discount_value'] / 100));
                } else {
                    $actual_price = $product_data['price'] - $product_data['discount_value'];
                }
            }

            // Check if the product already exists in the cart
            $check_query = $conn->prepare("SELECT Quantity FROM manage_order WHERE CustomerID = ? AND ProductID = ? AND PaymentStatus = 'Unpaid'");
            $check_query->bind_param("ii", $customer_id, $product_id);
            $check_query->execute();
            $result = $check_query->get_result();

            if ($result->num_rows > 0) {
                // Update existing cart item
                $update_query = $conn->prepare("UPDATE manage_order SET Quantity = Quantity + ?, Subtotal = Subtotal + (? * ?) WHERE CustomerID = ? AND ProductID = ? AND PaymentStatus = 'Unpaid'");
                $update_query->bind_param("iiddi", $quantity, $quantity, $actual_price, $customer_id, $product_id);
                if (!$update_query->execute()) {
                    throw new Exception('Error updating cart');
                }
                $response['success'] = true;
                $response['message'] = 'Cart updated successfully';
            } else {
                // Add new item to the cart
                $subtotal = $quantity * $actual_price;
                $insert_query = $conn->prepare("INSERT INTO manage_order (CustomerID, ProductID, Quantity, Subtotal, TotalPrice, PaymentStatus, ShipmentStatus) VALUES (?, ?, ?, ?, ?, 'Unpaid', 'Cart')");
                $insert_query->bind_param("iiidd", $customer_id, $product_id, $quantity, $subtotal, $subtotal);
                if (!$insert_query->execute()) {
                    throw new Exception('Error adding item to cart');
                }
                $response['success'] = true;
                $response['message'] = 'Item added to cart successfully';
            }

            // Get updated cart count
            $count_query = $conn->prepare("SELECT SUM(Quantity) as item_count FROM manage_order WHERE CustomerID = ? AND PaymentStatus = 'Unpaid'");
            $count_query->bind_param("i", $customer_id);
            $count_query->execute();
            $count_result = $count_query->get_result();
            $count_data = $count_result->fetch_assoc();
            $response['cartCount'] = $count_data['item_count'] ?? 0;
        } elseif ($_POST['action'] === 'update') {
            // Get current product price with promotion
            $price_query = $conn->prepare("
                SELECT p.price, 
                       pr.promotion_id, pr.discount_type, pr.discount_value
                FROM products p
                LEFT JOIN promotions pr ON p.product_id = pr.product_id 
                    AND pr.start_date <= NOW() 
                    AND pr.end_date >= NOW()
                WHERE p.product_id = ?
            ");
            $price_query->bind_param("i", $product_id);
            $price_query->execute();
            $price_result = $price_query->get_result();
            $price_data = $price_result->fetch_assoc();

            // Calculate the actual price considering promotion
            $actual_price = $price_data['price'];
            if ($price_data['promotion_id']) {
                if ($price_data['discount_type'] == 'percentage') {
                    $actual_price = $price_data['price'] * (1 - ($price_data['discount_value'] / 100));
                } else {
                    $actual_price = $price_data['price'] - $price_data['discount_value'];
                }
            }

            // Update item quantity with promotional price
            $update_query = $conn->prepare("UPDATE manage_order SET Quantity = ?, Subtotal = ? * ? WHERE CustomerID = ? AND ProductID = ? AND PaymentStatus = 'Unpaid'");
            $update_query->bind_param("iiddi", $quantity, $quantity, $actual_price, $customer_id, $product_id);
            $update_query->execute();

            // Get updated cart count
            $count_query = $conn->prepare("SELECT SUM(Quantity) as item_count FROM manage_order WHERE CustomerID = ? AND PaymentStatus = 'Unpaid'");
            $count_query->bind_param("i", $customer_id);
            $count_query->execute();
            $count_result = $count_query->get_result();
            $count_data = $count_result->fetch_assoc();
            $response['cartCount'] = $count_data['item_count'] ?? 0;
            $response['success'] = true;
            $response['message'] = 'Cart updated successfully';
        } elseif ($_POST['action'] === 'remove') {
            // Remove item from cart
            $remove_query = $conn->prepare("DELETE FROM manage_order WHERE CustomerID = ? AND ProductID = ? AND PaymentStatus = 'Unpaid' AND (ShipmentStatus = 'Cart' OR ShipmentStatus IS NULL OR ShipmentStatus = '')");
            $remove_query->bind_param("ii", $customer_id, $product_id);
            $remove_query->execute();

            // Get updated cart count
            $count_query = $conn->prepare("SELECT SUM(Quantity) as item_count FROM manage_order WHERE CustomerID = ? AND PaymentStatus = 'Unpaid' AND (ShipmentStatus = 'Cart' OR ShipmentStatus IS NULL OR ShipmentStatus = '')");
            $count_query->bind_param("i", $customer_id);
            $count_query->execute();
            $count_result = $count_query->get_result();
            $count_data = $count_result->fetch_assoc();
            $response['cartCount'] = $count_data['item_count'] ?? 0;
            $response['success'] = true;
            $response['message'] = 'Item removed from cart';
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    // If this is an AJAX request, return JSON response
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Fetch cart items with promotional prices
$cart_query = $conn->prepare("
    SELECT m.ProductID, p.product_name, p.price, p.product_image, m.Quantity, m.Subtotal,
           pr.promotion_id, pr.discount_type, pr.discount_value
    FROM manage_order m 
    JOIN products p ON m.ProductID = p.product_id 
    LEFT JOIN promotions pr ON p.product_id = pr.product_id 
        AND pr.start_date <= NOW() 
        AND pr.end_date >= NOW()
    WHERE m.CustomerID = ? 
    AND m.PaymentStatus = 'Unpaid' 
    AND (m.ShipmentStatus = 'Cart' OR m.ShipmentStatus IS NULL OR m.ShipmentStatus = '')
");
$cart_query->bind_param("i", $customer_id);
$cart_query->execute();
$cart_result = $cart_query->get_result();

$total = 0;
$cartItems = [];
while ($row = $cart_result->fetch_assoc()) {
    // Calculate the actual price considering promotion
    $actual_price = $row['price'];
    if ($row['promotion_id']) {
        if ($row['discount_type'] == 'percentage') {
            $actual_price = $row['price'] * (1 - ($row['discount_value'] / 100));
        } else {
            $actual_price = $row['price'] - $row['discount_value'];
        }
    }
    $row['price'] = $actual_price; // Update the price to show promotional price
    $cartItems[] = $row;
    $total += $row['Subtotal'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/top-navigation.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #b3b3b356;
            margin: 0;
            padding: 0;
        }
        .cart-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .cart-container h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .cart-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
        }

        .cart-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .product-name {
            font-weight: 500;
            color: #333;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .quantity-input {
            width: 50px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            font-size: 14px;
        }

        .quantity-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .quantity-btn:hover {
            background: #f8f9fa;
        }

        .quantity-btn:active {
            background: #e9ecef;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .update-btn, .remove-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .update-btn {
            background: #000;
            color: white;
        }

        .update-btn:hover {
            background: #333;
        }

        .remove-btn {
            background: #dc3545;
            color: white;
        }

        .remove-btn:hover {
            background: #c82333;
        }

        .cart-total {
            text-align: right;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 1.2em;
        }

        .checkout-button, .back-button {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
            text-align: center;
            text-decoration: none;
        }

        .checkout-button {
            background: #000;
            color: white;
            margin-right: 10px;
        }

        .checkout-button:hover {
            background: #333;
            transform: translateY(-2px);
        }

        .back-button {
            background: #6c757d;
            color: white;
        }

        .back-button:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .empty-cart {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }

        .empty-cart p {
            font-size: 1.2em;
            color: #666;
            margin-bottom: 20px;
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        /* Confirmation Dialog Styles */
        .confirmation-dialog {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            text-align: center;
        }

        .confirmation-dialog p {
            margin-bottom: 20px;
            font-size: 16px;
        }

        .dialog-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .dialog-btn {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .confirm-btn {
            background: #dc3545;
            color: white;
        }

        .confirm-btn:hover {
            background: #c82333;
        }

        .cancel-btn {
            background: #6c757d;
            color: white;
        }

        .cancel-btn:hover {
            background: #5a6268;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        /* --- Mobile Responsive Styles for Cart --- */
        @media (max-width: 768px) {
            .cart-container {
                padding: 10px;
                margin: 40px auto;
            }

            .cart-container h1 {
                font-size: 1.8em;
                margin-bottom: 20px;
            }

            /* Table transformations */
            .cart-table,
            .cart-table thead,
            .cart-table tbody,
            .cart-table th,
            .cart-table td,
            .cart-table tr {
                display: block;
            }

            /* Hide table headers (but not display: none, so they can be used for labels) */
            .cart-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            .cart-table tr {
                border: 1px solid #ccc;
                margin-bottom: 15px;
                border-radius: 8px;
                overflow: hidden;
                background: white;
                display: flex; /* Use flexbox for the row content */
                flex-direction: column; /* Stack cells vertically */
                padding: 10px;
            }

            .cart-table td {
                border: none;
                border-bottom: 1px solid #eee; /* Add separator between items */
                position: relative;
                padding-left: 50%; /* Make space for the label */
                text-align: right; /* Align content to the right */
                word-break: break-word; /* Prevent long text from overflowing */
                padding-top: 10px; /* Adjust padding */
                padding-bottom: 10px; /* Adjust padding */
            }

            .cart-table td:last-child {
                border-bottom: 0;
            }

            /* Add labels using data attributes or :nth-child */
            .cart-table td::before {
                /* Absolute position the pseudo element */
                position: absolute;
                top: 50%;
                left: 10px; /* Position label to the left */
                width: 45%; /* Width of the label area */
                padding-right: 10px;
                white-space: nowrap; /* Prevent label text wrapping */
                transform: translateY(-50%); /* Vertically center label */
                font-weight: bold;
                content: attr(data-label); /* Use data-label attribute for content */
                text-align: left; /* Align label text to the left */
                color: #555;
                font-size: 0.9em;
            }

            /* Specific labels for each column */
            .cart-table td:nth-of-type(1)::before { content: "Product:"; }
            .cart-table td:nth-of-type(2)::before { content: "Quantity:"; }
            .cart-table td:nth-of-type(3)::before { content: "Price:"; }
            .cart-table td:nth-of-type(4)::before { content: "Subtotal:"; }
            .cart-table td:nth-of-type(5)::before { content: "Actions:"; }

            /* Adjust product info layout */
            .product-info {
                flex-direction: column; /* Stack image and name */
                align-items: flex-end; /* Align items to the right */
                text-align: right; /* Align text to the right */
                gap: 5px; /* Reduce gap */
            }

             .product-image {
                width: 60px; /* Adjust image size */
                height: 60px; /* Adjust image size */
                margin-bottom: 5px; /* Add space below image */
            }

            .product-name {
                font-size: 1em; /* Adjust font size */
            }

            /* Adjust quantity control layout */
            .quantity-control {
                 justify-content: flex-end; /* Align controls to the right */
                 margin-top: 5px; /* Add space above controls */
            }

            /* Adjust action buttons layout */
            .action-buttons {
                justify-content: flex-end; /* Align buttons to the right */
                 margin-top: 10px; /* Add space above buttons */
            }

            .update-btn, .remove-btn {
                padding: 6px 10px; /* Adjust button padding */
                font-size: 0.9em; /* Adjust button font size */
            }

            .cart-total {
                 text-align: center; /* Center total on mobile */
                 padding: 15px;
                 font-size: 1.1em;
            }

            .button-container {
                 flex-direction: column; /* Stack buttons vertically */
                 gap: 10px; /* Adjust gap between buttons */
            }

            .checkout-button, .back-button {
                 width: 100%; /* Make buttons full width */
                 box-sizing: border-box; /* Include padding and border in width */
                 padding: 10px; /* Adjust padding */
                 font-size: 1em; /* Adjust font size */
            }

            .checkout-button {
                 margin-right: 0; /* Remove margin */
            }

            .empty-cart {
                 padding: 20px;
            }

            .empty-cart p {
                 font-size: 1em;
            }
        }
    </style>
</head>

<body>
    <!-- Top navigation -->
    <?php include 'top-navigation.php'; ?>

    <div class="cart-container">
        <h1>Shopping Cart</h1>
        <?php if (count($cartItems) > 0): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td>
                                <div class="product-info">
                                    <img src="uploads/<?php echo htmlspecialchars($item['product_image']); ?>"
                                        alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image">
                                    <span class="product-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                </div>
                            </td>
                            <td>
                                <form action="cart.php" method="POST" class="quantity-control">
                                    <input type="hidden" name="product_id" value="<?php echo $item['ProductID']; ?>">
                                    <input type="hidden" name="action" value="update">
                                    <button type="button" class="quantity-btn minus-btn" onclick="updateQuantity(this, -1)">-</button>
                                    <input type="number" name="quantity" value="<?php echo $item['Quantity']; ?>" min="1" max="99" class="quantity-input" readonly>
                                    <button type="button" class="quantity-btn plus-btn" onclick="updateQuantity(this, 1)">+</button>
                                </form>
                            </td>
                            <td>RM<?php echo number_format($item['price'], 2); ?></td>
                            <td>RM<?php echo number_format($item['Subtotal'], 2); ?></td>
                            <td>
                                <form action="cart.php" method="POST" class="action-buttons">
                                    <input type="hidden" name="product_id" value="<?php echo $item['ProductID']; ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="remove-btn">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="cart-total">
                <strong>Total: RM<?php echo number_format($total, 2); ?></strong>
            </div>
            <div class="button-container">
                <button class="back-button" onclick="window.location.href='home.php';">Continue Shopping</button>
                <form action="purchaseItem.php" method="POST" style="display: inline;">
                    <?php foreach ($cartItems as $item): ?>
                        <input type="hidden" name="product_id[]" value="<?php echo $item['ProductID']; ?>">
                        <input type="hidden" name="quantity[]" value="<?php echo $item['Quantity']; ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="from_cart" value="1">
                    <button type="submit" class="checkout-button">Proceed to Checkout</button>
                </form>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <p>Your cart is empty.</p>
                <button class="back-button" onclick="window.location.href='home.php';">Continue Shopping</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Confirmation Dialog -->
    <div class="overlay" id="overlay"></div>
    <div class="confirmation-dialog" id="confirmationDialog">
        <p>Are you sure you want to remove this item?</p>
        <div class="dialog-buttons">
            <button class="dialog-btn confirm-btn" onclick="confirmRemove()">Yes</button>
            <button class="dialog-btn cancel-btn" onclick="cancelRemove()">No</button>
        </div>
    </div>

    <script>
        let currentForm = null;
        let currentProductId = null;

        function updateQuantity(button, change) {
            const form = button.closest('form');
            const input = form.querySelector('.quantity-input');
            const currentValue = parseInt(input.value);
            const newValue = currentValue + change;
            
            if (newValue >= 1 && newValue <= 99) {
                input.value = newValue;
                // Automatically submit the form
                form.submit();
            } else if (newValue < 1) {
                // Show confirmation dialog for removal
                currentForm = form;
                currentProductId = form.querySelector('input[name="product_id"]').value;
                showConfirmationDialog();
            }
        }

        function showConfirmationDialog() {
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('confirmationDialog').style.display = 'block';
        }

        function hideConfirmationDialog() {
            document.getElementById('overlay').style.display = 'none';
            document.getElementById('confirmationDialog').style.display = 'none';
        }

        function confirmRemove() {
            if (currentForm) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'cart.php';
                
                const productIdInput = document.createElement('input');
                productIdInput.type = 'hidden';
                productIdInput.name = 'product_id';
                productIdInput.value = currentProductId;
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'remove';
                
                form.appendChild(productIdInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
            hideConfirmationDialog();
        }

        function cancelRemove() {
            hideConfirmationDialog();
        }
    </script>
</body>

</html>