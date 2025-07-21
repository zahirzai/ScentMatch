<?php
session_start();

// Include the Stripe PHP SDK
require __DIR__ . '/vendor/autoload.php';

// Check if the user is logged in
if (!isset($_SESSION['customerID'])) {
    die("User not logged in. Please <a href='login.php'>login</a> to proceed.");
}

// Set your Stripe API key
\Stripe\Stripe::setApiKey('sk_test_51R4BYx2LCJm9ERl4NEGhznar9sHTjj9yQQMxJBVKLrOXMvbRnVf6bTrXLTU2gbDw5wqLo8j1Me3il6d25Za6l3aY00SYqwuKcF');

// Database connection
$conn = new mysqli("localhost", "root", "", "scentmatch3");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get product IDs and quantities
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    die("Invalid product ID or quantity.");
}

if (!is_array($_POST['product_id']) && !is_array($_POST['quantity'])) {
    $product_ids = [$_POST['product_id']];
    $quantities = [$_POST['quantity']];
} elseif (is_array($_POST['product_id']) && is_array($_POST['quantity'])) {
    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];
} else {
    die("Invalid format for product ID or quantity.");
}

if (count($product_ids) !== count($quantities)) {
    die("Mismatch between product IDs and quantities.");
}

// Get user ID from session
$user_id = $_SESSION['customerID'];

// Fetch product details and calculate total price
$total_price = 0;
$products = [];

foreach ($product_ids as $index => $product_id) {
    $quantity = $quantities[$index];

    // Updated query to include promotion information
    $stmt = $conn->prepare("
        SELECT p.product_id, p.product_name, p.price, p.product_quantity, p.seller_id,
               pr.promotion_id, pr.discount_type, pr.discount_value,
               m.Subtotal as cart_subtotal
        FROM products p
        LEFT JOIN promotions pr ON p.product_id = pr.product_id 
            AND pr.start_date <= NOW() 
            AND pr.end_date >= NOW()
        LEFT JOIN manage_order m ON p.product_id = m.ProductID 
            AND m.CustomerID = ? 
            AND m.PaymentStatus = 'Unpaid'
            AND m.ShipmentStatus = 'Cart'
        WHERE p.product_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        die("Product not found.");
    }

    if ($quantity > $product['product_quantity']) {
        die("Insufficient stock for product: " . $product['product_name']);
    }

    // Calculate price with promotion if available
    $price = $product['price'];
    if (isset($_POST['from_cart']) && $product['cart_subtotal']) {
        // If coming from cart, use the cart subtotal to maintain the same price
        $price = $product['cart_subtotal'] / $quantity;
    } else if ($product['promotion_id']) {
        if ($product['discount_type'] == 'percentage') {
            $price = $product['price'] * (1 - ($product['discount_value'] / 100));
        } else {
            $price = $product['price'] - $product['discount_value'];
        }
    }

    $subtotal = $price * $quantity;
    $total_price += $subtotal;

    $products[] = [
        'product_id' => $product['product_id'],
        'product_name' => $product['product_name'],
        'price' => $price,
        'quantity' => $quantity,
        'subtotal' => $subtotal,
        'original_price' => $product['price'],
        'has_promotion' => !empty($product['promotion_id'])
    ];
}

// Add this after the products array calculation
$delivery_fee = 10.00; // Fixed delivery fee
$total_price_with_delivery = $total_price + $delivery_fee;

// Fetch user details
$user_stmt = $conn->prepare("SELECT Name AS full_name, Email AS email, address FROM customer WHERE CustomerID = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// For showing a success pop-up later
$payment_success = false;
$tracking_code = "";
$delivery_date = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stripeToken'])) {
    // Check if user has all required information
    $missing_fields = [];
    if (empty($user['full_name'])) {
        $missing_fields[] = 'name';
    }
    if (empty($user['email'])) {
        $missing_fields[] = 'email';
    }
    if (empty($user['address'])) {
        $missing_fields[] = 'address';
    }

    if (!empty($missing_fields)) {
        $fields_str = implode(', ', $missing_fields);
        echo "<script>
            alert('Please complete your profile information ($fields_str) before proceeding with the purchase.');
            window.location.href = 'profile.php';
        </script>";
        exit();
    }

    $token = $_POST['stripeToken'];

    try {
        $charge = \Stripe\Charge::create([
            'amount' => round($total_price_with_delivery * 100), // Amount in cents
            'currency' => 'myr',
            'description' => 'Purchase of ' . count($products) . ' items',
            'source' => $token,
            'metadata' => ['customer_id' => $user_id]
        ]);

        if ($charge->status === 'succeeded') {
            // Generate tracking code
            $tracking_code = 'ORDER' . strtoupper(substr(uniqid(), -8));
            
            // Generate random delivery date (1-7 days from now)
            $delivery_days = rand(1, 7);
            $delivery_date = date('Y-m-d', strtotime("+$delivery_days days"));
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Check if this is a direct purchase (Buy Now) or from cart
                $is_direct_purchase = !isset($_POST['from_cart']) || (isset($_POST['from_cart']) && $_POST['from_cart'] === '0');
                error_log("Processing order - Direct purchase: " . ($is_direct_purchase ? 'Yes' : 'No'));

                // First, check if any orders already exist for these products
                $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
                $check_params = array_merge([$user_id], $product_ids);
                $check_types = 'i' . str_repeat('i', count($product_ids));
                
                $check_orders = $conn->prepare("
                    SELECT OrderID, ProductID, PaymentStatus, ShipmentStatus 
                    FROM manage_order 
                    WHERE CustomerID = ? 
                    AND ProductID IN ($placeholders)
                ");
                $check_orders->bind_param($check_types, ...$check_params);
                $check_orders->execute();
                $existing_orders = $check_orders->get_result()->fetch_all(MYSQLI_ASSOC);
                
                error_log("Found " . count($existing_orders) . " existing orders");

                if ($is_direct_purchase) {
                    // For direct purchases, only create orders for products that don't have existing orders
                    $existing_product_ids = array_column($existing_orders, 'ProductID');
                    foreach ($products as $product) {
                        if (!in_array($product['product_id'], $existing_product_ids)) {
                            error_log("Creating new order for product ID: " . $product['product_id']);
                            $insert_order = $conn->prepare("
                                INSERT INTO manage_order 
                                (CustomerID, ProductID, Quantity, Subtotal, TotalPrice, PaymentStatus, ShipmentStatus) 
                                VALUES (?, ?, ?, ?, ?, 'Unpaid', 'Cart')
                            ");
                            $insert_order->bind_param("iiidd", 
                                $user_id,
                                $product['product_id'],
                                $product['quantity'],
                                $product['subtotal'],
                                $product['subtotal']
                            );
                            if (!$insert_order->execute()) {
                                error_log("Failed to insert order: " . $insert_order->error);
                                throw new Exception("Failed to create order: " . $insert_order->error);
                            }
                        } else {
                            error_log("Order already exists for product ID: " . $product['product_id']);
                        }
                    }
                }

                // Update all orders to paid status
                error_log("Updating orders to paid status for products: " . implode(', ', $product_ids));
                $update_order = $conn->prepare("
                    UPDATE manage_order 
                    SET PaymentStatus = 'Paid',
                        ShipmentStatus = 'Preparing',
                        TrackingNumber = ?,
                        Delivery_date = ?,
                        Paid_time = NOW(),
                        TotalPrice = Subtotal,
                        DeliveryType = ?
                    WHERE CustomerID = ? 
                    AND ProductID IN ($placeholders)
                    AND PaymentStatus = 'Unpaid'
                ");
                
                // Get the selected delivery type from the form
                $selected_delivery_type = isset($_POST['delivery_type']) ? $_POST['delivery_type'] : 'Pos Laju';
                
                // Create the parameter array with the correct number of elements
                $update_params = array_merge(
                    [$tracking_code, $delivery_date, $selected_delivery_type, $user_id],
                    $product_ids
                );
                
                // Create the type string with the correct number of types
                $update_types = 'sssi' . str_repeat('i', count($product_ids));
                
                $update_order->bind_param($update_types, ...$update_params);
                if (!$update_order->execute()) {
                    error_log("Failed to update orders: " . $update_order->error);
                    throw new Exception("Failed to update orders: " . $update_order->error);
                }
                
                // Verify update was successful
                if ($update_order->affected_rows === 0) {
                    error_log("No orders were updated. Check if orders exist with correct status.");
                    throw new Exception("No orders were found to update. Please try again.");
                }

                // Update product stock and sales records
                foreach ($products as $product) {
                    error_log("Updating stock and sales for product ID: " . $product['product_id']);
                    // Insert into sales table
                    $sales_stmt = $conn->prepare("
                        INSERT INTO sales 
                        (ProductID, QuantitySold) 
                        VALUES (?, ?)
                    ");
                    $sales_stmt->bind_param("ii", 
                        $product['product_id'], 
                        $product['quantity']
                    );
                    if (!$sales_stmt->execute()) {
                        error_log("Failed to insert sales record: " . $sales_stmt->error);
                        throw new Exception("Failed to update sales: " . $sales_stmt->error);
                    }

                    // Update product stock
                    $update_stmt = $conn->prepare("
                        UPDATE products 
                        SET product_quantity = product_quantity - ? 
                        WHERE product_id = ?
                    ");
                    $update_stmt->bind_param("ii", 
                        $product['quantity'], 
                        $product['product_id']
                    );
                    if (!$update_stmt->execute()) {
                        error_log("Failed to update product stock: " . $update_stmt->error);
                        throw new Exception("Failed to update stock: " . $update_stmt->error);
                    }
                }
                
                $conn->commit();
                $payment_success = true;
                
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Order processing error: " . $e->getMessage());
                $payment_error = "Error processing your order: " . $e->getMessage();
            }
        } else {
            $payment_error = "Payment failed. Please try again.";
        }
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $payment_error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <link rel="stylesheet" href="./css/top-navigation.css">
    <link rel="stylesheet" href="./css/checkout.css">
    <script src="https://js.stripe.com/v3/"></script>

    <style>
    /* Main Layout */
    .checkout-container {
        display: flex;
        max-width: 1400px;
        margin: 40px auto;
        gap: 40px;
        padding: 0 20px;
    }

    .left-section, .right-section {
        flex: 1;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 30px;
    }

    /* Left Section Styles */
    .left-section {
        min-width: 500px;
    }

    .section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }

    .section:last-child {
        border-bottom: none;
    }

    .section h2 {
        color: #333;
        font-size: 1.5em;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }

    .customer-info p {
        margin: 10px 0;
        font-size: 1.1em;
    }

    .customer-info strong {
        color: #555;
        min-width: 100px;
        display: inline-block;
    }

    .address-warning {
        background-color: #fff3cd;
        color: #856404;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .address-warning i {
        font-size: 1.2em;
    }

    .address-warning a {
        color: #0056b3;
        text-decoration: none;
        font-weight: 500;
    }

    .address-warning a:hover {
        text-decoration: underline;
    }

    .product-details {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }

    .product-details th {
        background: #f8f9fa;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #333;
    }

    .product-details td {
        padding: 12px;
        border-bottom: 1px solid #eee;
    }

    .total-price {
        text-align: right;
        font-size: 1.2em;
        margin-top: 20px;
        padding: 15px;
        background:rgb(255, 255, 255);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border-radius: 8px;
    }

    /* Right Section Styles */
    .right-section {
        min-width: 400px;
        background: #f8f9fa;
    }

    .payment-section {
        background: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        margin-bottom: 10px;
        color: #333;
        font-weight: 500;
    }

    /* Card Element Styles */
    .card-element-container {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    #card-element {
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #fff;
    }

    .card-details {
        display: flex;
        gap: 15px;
    }

    #card-expiry {
        flex: 1;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #fff;
    }

    #card-cvc {
        flex: 1;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #fff;
    }

    #card-errors {
        color: #dc3545;
        margin-top: 10px;
        font-size: 0.9em;
    }

    .pay-button {
        width: 100%;
        padding: 15px;
        background: #000;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 1.1em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .pay-button:hover {
        background: #333;
        transform: translateY(-2px);
    }

    /* Popup Styles */
    #popup-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 9999;
    }

    #popup-message {
        background: #fff;
        padding: 30px;
        border-radius: 15px;
        width: 400px;
        text-align: center;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    .delivery-info {
        margin-top: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
    }

    #popup-message button {
        margin-top: 20px;
        padding: 10px 20px;
        background: #000;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    #popup-message button:hover {
        background: #333;
    }

    @media (max-width: 1024px) {
        .checkout-container {
            flex-direction: column;
        }
        
        .left-section, .right-section {
            min-width: 100%;
        }
    }

    /* Add these styles to your existing style section */
    .delivery-select {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #fff;
        font-size: 1em;
        color: #333;
        cursor: pointer;
        transition: border-color 0.3s ease;
    }

    .delivery-select:hover {
        border-color: #999;
    }

    .delivery-select:focus {
        outline: none;
        border-color: #000;
        box-shadow: 0 0 0 2px rgba(0,0,0,0.1);
    }
    </style>
</head>
<body>

<?php include 'top-navigation.php'; ?>

<div class="checkout-container">
    <!-- Left Section -->
    <div class="left-section">
        <div class="section">
            <h2>Customer Information</h2>
            <div class="customer-info">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
                <?php if (empty($user['address'])): ?>
                <div class="address-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Please add your delivery address in your <a href="profile.php">profile</a> before proceeding with the purchase.</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h2>Order Summary</h2>
            <table class="product-details">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo $product['quantity']; ?></td>
                            <td>
                                <?php if ($product['has_promotion']): ?>
                                    <span style="text-decoration: line-through; color: #999;">RM<?php echo number_format($product['original_price'], 2); ?></span>
                                    <br>
                                    <span style="color: #e74c3c; font-weight: bold;">RM<?php echo number_format($product['price'], 2); ?></span>
                                <?php else: ?>
                                    RM<?php echo number_format($product['price'], 2); ?>
                                <?php endif; ?>
                            </td>
                            <td>RM<?php echo number_format($product['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="alert-section">
                <div class="alert-box" style="background-color: #fff3cd; padding: 15px; border-radius: 8px; margin-top: 20px;">
                    <h3 style="color: #856404; margin-bottom: 10px; font-size: 1.1em;">Important Notice</h3>
                    <p style="color: #666; margin: 0; font-size: 0.95em;">
                        <strong>No Return Policy:</strong> Please note that all purchases are final and non-refundable. 
                        We do not accept returns or exchanges. Please ensure you are satisfied with your selection before completing your purchase.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Section -->
    <div class="right-section">
        <div class="payment-section">
            <h2>Payment Details</h2>
            <form action="purchaseItem.php" method="POST" id="payment-form">
                <?php foreach ($products as $product): ?>
                    <input type="hidden" name="product_id[]" value="<?php echo $product['product_id']; ?>">
                    <input type="hidden" name="quantity[]" value="<?php echo $product['quantity']; ?>">
                <?php endforeach; ?>

                <div class="form-group">
                    <label for="delivery-type">Delivery Type</label>
                    <select name="delivery_type" id="delivery-type" class="delivery-select">
                        <option value="Pos Laju">Pos Laju</option>
                        <option value="J&T Express">J&T Express</option>
                        <option value="DHL">DHL</option>
                        <option value="Ninja Van">Ninja Van</option>
                        <option value="Shopee Xpress">Shopee Xpress</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="card-element">Card Information</label>
                    <div class="card-element-container">
                        <div id="card-element"></div>
                        <div class="card-details">
                            <div id="card-expiry"></div>
                            <div id="card-cvc"></div>
                        </div>
                    </div>
                    <div id="card-errors" role="alert"></div>
                </div>

                <input type="hidden" name="stripeToken" id="stripeToken">
                <button type="submit" class="pay-button">PAY RM<?php echo number_format($total_price_with_delivery, 2); ?></button>
            </form>
        </div>
        <div class="total-price" style="text-align: right; font-size: 1.2em; margin-bottom: 20px; padding: 15px; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-radius: 8px;">
            <div style="margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                <span style="float: left;">Delivery Fee:</span>
                <span style="float: right;">RM<?php echo number_format($delivery_fee, 2); ?></span>
                <div style="clear: both;"></div>
            </div>
            <div style="font-weight: bold;">
                <span style="float: left;">Total:</span>
                <span style="float: right;">RM<?php echo number_format($total_price_with_delivery, 2); ?></span>
                <div style="clear: both;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Centered Popup -->
<div id="popup-overlay">
    <div id="popup-message">
        <h2>Thank you for your purchase!</h2>
        <p>Your tracking code:<br><strong id="tracking-code"><?php echo $tracking_code; ?></strong></p>
        <div class="delivery-info">
            <p>Estimated delivery date:<br><strong id="delivery-date"><?php 
                echo date('F j, Y', strtotime($delivery_date)); 
            ?></strong></p>
        </div>
        <button onclick="window.location.href='order.php'">Check your Order</button>
    </div>
</div>

<?php if ($payment_success): ?>
<script>
window.onload = function() {
    document.getElementById('popup-overlay').style.display = 'block';
}
</script>
<?php elseif (!empty($payment_error)): ?>
<script>
window.onload = function() {
    alert("❌ Payment failed: <?php echo addslashes($payment_error); ?>");
}
</script>
<?php endif; ?>

<script>
    var stripe = Stripe('pk_test_51R4BYx2LCJm9ERl4nDyir85Y0LTAbMAPevhfKBP4uFaswhea3WIPhHvjzeRlp2cSCjShooYZc5cxXcHZtTqDlMyW00xjNZeLgh');
    var elements = stripe.elements();

    // Create card number element
    var card = elements.create('cardNumber', {
        style: {
            base: {
                color: '#32325d',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            }
        }
    });
    card.mount('#card-element');

    // Create expiry element
    var expiry = elements.create('cardExpiry', {
        style: {
            base: {
                color: '#32325d',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            }
        }
    });
    expiry.mount('#card-expiry');

    // Create CVC element
    var cvc = elements.create('cardCvc', {
        style: {
            base: {
                color: '#32325d',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            }
        }
    });
    cvc.mount('#card-cvc');

    // Handle validation errors
    function handleError(event) {
        var displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    }

    card.on('change', handleError);
    expiry.on('change', handleError);
    cvc.on('change', handleError);

    // Handle form submission
    var form = document.getElementById('payment-form');
    form.addEventListener('submit', function(event) {
        event.preventDefault();

        <?php 
        $missing_fields = [];
        if (empty($user['full_name'])) {
            $missing_fields[] = 'name';
        }
        if (empty($user['email'])) {
            $missing_fields[] = 'email';
        }
        if (empty($user['address'])) {
            $missing_fields[] = 'address';
        }
        
        if (!empty($missing_fields)): 
        ?>
        // Show profile completion warning popup
        var popup = document.createElement('div');
        popup.style.position = 'fixed';
        popup.style.top = '50%';
        popup.style.left = '50%';
        popup.style.transform = 'translate(-50%, -50%)';
        popup.style.backgroundColor = '#fff';
        popup.style.padding = '20px';
        popup.style.borderRadius = '8px';
        popup.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
        popup.style.zIndex = '1000';
        popup.style.textAlign = 'center';
        popup.style.maxWidth = '400px';

        var overlay = document.createElement('div');
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.right = '0';
        overlay.style.bottom = '0';
        overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
        overlay.style.zIndex = '999';

        var missingFields = <?php echo json_encode($missing_fields); ?>;
        var message = 'Please complete your profile information before proceeding with the purchase:<br><br>';
        missingFields.forEach(function(field) {
            message += '• ' + field.charAt(0).toUpperCase() + field.slice(1) + '<br>';
        });

        popup.innerHTML = `
            <h3 style="margin-bottom: 15px; color: #333;">Profile Information Required</h3>
            <p style="margin-bottom: 20px; color: #666;">${message}</p>
            <button onclick="window.location.href='profile.php'" style="
                padding: 10px 20px;
                background: #000;
                color: #fff;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 500;
                transition: all 0.3s ease;
            ">Update Profile</button>
        `;

        document.body.appendChild(overlay);
        document.body.appendChild(popup);
        return;
        <?php endif; ?>

        stripe.createToken(card).then(function(result) {
            if (result.error) {
                document.getElementById('card-errors').textContent = result.error.message;
            } else {
                document.getElementById('stripeToken').value = result.token.id;
                form.submit();
            }
        });
    });

    // Add click handlers for customer information fields
    document.addEventListener('DOMContentLoaded', function() {
        const customerInfo = document.querySelector('.customer-info');
        if (customerInfo) {
            const fields = customerInfo.querySelectorAll('p');
            fields.forEach(field => {
                field.style.cursor = 'pointer';
                field.addEventListener('click', function() {
                    var popup = document.createElement('div');
                    popup.style.position = 'fixed';
                    popup.style.top = '50%';
                    popup.style.left = '50%';
                    popup.style.transform = 'translate(-50%, -50%)';
                    popup.style.backgroundColor = '#fff';
                    popup.style.padding = '20px';
                    popup.style.borderRadius = '8px';
                    popup.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
                    popup.style.zIndex = '1000';
                    popup.style.textAlign = 'center';
                    popup.style.maxWidth = '400px';

                    var overlay = document.createElement('div');
                    overlay.style.position = 'fixed';
                    overlay.style.top = '0';
                    overlay.style.left = '0';
                    overlay.style.right = '0';
                    overlay.style.bottom = '0';
                    overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
                    overlay.style.zIndex = '999';

                    const fieldName = this.querySelector('strong').textContent.replace(':', '').toLowerCase();
                    popup.innerHTML = `
                        <h3 style="margin-bottom: 15px; color: #333;">Update ${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)}</h3>
                        <p style="margin-bottom: 20px; color: #666;">Please update your ${fieldName} in your profile.</p>
                        <button onclick="window.location.href='profile.php'" style="
                            padding: 10px 20px;
                            background: #000;
                            color: #fff;
                            border: none;
                            border-radius: 4px;
                            cursor: pointer;
                            font-weight: 500;
                            transition: all 0.3s ease;
                        ">Go to Profile</button>
                    `;

                    document.body.appendChild(overlay);
                    document.body.appendChild(popup);
                });
            });
        }
    });
</script>

</body>
</html>