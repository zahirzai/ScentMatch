<?php
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");
include 'top-navigation.php';
// Check for database connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get product ID
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;

if (!$product_id) {
    die("Invalid product ID.");
}

// Check for active promotions for this product
$promotion_stmt = $conn->prepare("
    SELECT 
        promotion_id,
        promo_name,
        discount_type,
        discount_value,
        start_date,
        end_date
    FROM 
        promotions
    WHERE 
        product_id = ? 
        AND start_date <= NOW() 
        AND end_date >= NOW()
    ORDER BY 
        created_at DESC
    LIMIT 1
");
$promotion_stmt->bind_param("i", $product_id);
$promotion_stmt->execute();
$promotion = $promotion_stmt->get_result()->fetch_assoc();

// In the product query section, add this to calculate average rating:
$product_query = "
    SELECT 
        p.*,
        c.ConcentrationName as concentration,
        c.ConcentrationPercentage,
        c.Longevity,
        GROUP_CONCAT(DISTINCT s.ScentName SEPARATOR ', ') as scents,
        (SELECT COALESCE(AVG(Rating), 0) FROM reviews WHERE ProductID = p.product_id) AS rating,
        (SELECT COUNT(*) FROM reviews WHERE ProductID = p.product_id) AS review_count,
        (SELECT COALESCE(SUM(QuantitySold), 0) FROM sales WHERE ProductID = p.product_id) AS total_sold
    FROM products p
    LEFT JOIN product_scent ps ON p.product_id = ps.product_id
    LEFT JOIN scents s ON ps.scent_id = s.ScentID
    LEFT JOIN concentration c ON p.ConcentrationID = c.ConcentrationID
    WHERE p.product_id = ?
    GROUP BY p.product_id
";
$product_stmt = $conn->prepare($product_query);
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product = $product_stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

// Ensure seller_id is retrieved correctly
$seller_id = $product['seller_id'] ?? null;

if (!$seller_id) {
    die("Error: No Seller ID found for this product.");
}

// Fetch seller information
$seller_stmt = $conn->prepare("    
    SELECT 
        se.SellerID, 
        se.CompanyName AS company_name, 
        COUNT(p.product_id) AS total_products, 
        se.OpenHours AS open_hours, 
        se.CreatedAt AS join_date,
        se.LogoUrl AS logo_url
    FROM 
        seller se
    LEFT JOIN 
        products p ON se.SellerID = p.seller_id
    WHERE 
        se.SellerID = ?
    GROUP BY 
        se.SellerID
");
$seller_stmt->bind_param("i", $seller_id);
$seller_stmt->execute();
$seller = $seller_stmt->get_result()->fetch_assoc();

if (!$seller) {
    die("Seller not found.");
}

// Track product view
require_once 'seller/track_product_view.php';
trackProductView($product_id, isset($_SESSION['customerID']));

// Add this after getting the seller information
$seller_status = '';
$suspend_reason = '';

// Get seller status
$status_query = $conn->prepare("SELECT status FROM seller WHERE SellerID = ?");
$status_query->bind_param("i", $seller_id);
$status_query->execute();
$status_result = $status_query->get_result();
if ($status_row = $status_result->fetch_assoc()) {
    $seller_status = $status_row['status'];
}
$status_query->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?></title>
    <link rel="stylesheet" href="css/product_details.css">
    <link rel="stylesheet" href="css/top-navigation.css">
    <style>
        .suspended-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .suspended-message {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            max-width: 80%;
        }

        .suspended-message h3 {
            color: #e74c3c;
            margin-bottom: 10px;
        }

        .suspended-message p {
            color: #666;
            margin: 0;
        }

        /* Image Modal Styles */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            overflow: auto;
        }

        .modal-content {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding-top: 80px;
        }

        .modal-image {
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
        }

        .close-modal {
            position: absolute;
            top: 85px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 10000;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: #bbb;
        }

        /* Review Image Styles */
        .review-image {
            cursor: pointer;
            transition: transform 0.2s;
        }

        .review-image:hover {
            transform: scale(1.05);
        }

        @keyframes fadeInOverlay {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes fadeOutOverlay {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        .login-alert {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            z-index: 99999;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: none;
        }
        .login-alert.show {
            display: flex !important;
        }
        .login-alert.animating-in {
            animation: fadeInOverlay 0.3s cubic-bezier(0.4,0,0.2,1) forwards;
        }
        .login-alert.animating-out {
            animation: fadeOutOverlay 0.3s cubic-bezier(0.4,0,0.2,1) forwards;
        }
        .login-alert-content {
            background: #fff;
            padding: 40px 30px 30px 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 24px rgba(0,0,0,0.15);
            max-width: 400px;
            width: 90vw;
        }
        .login-alert-icon {
            margin-bottom: 15px;
        }
        .login-alert-buttons {
            margin-top: 25px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        .login-btn {
            background: #000;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 10px 22px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .login-btn:hover {
            background: #333;
        }
        .cancel-btn {
            background: #e0e0e0;
            color: #333;
            border: none;
            border-radius: 5px;
            padding: 10px 22px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .cancel-btn:hover {
            background: #ccc;
        }
    </style>
</head>
<?php ?>

<body>
    
    <div class="big-container">
        <div class="main-content">
            <!-- Existing Product Container -->
            <div class="product-container">
                <!-- Product Image -->
                <div class="product-image">
                    <img src="uploads/<?php echo htmlspecialchars($product['product_image']); ?>"
                        alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                    <?php if ($product['product_quantity'] <= 0): ?>
                        <div class="out-of-stock-overlay">Out of Stock</div>
                    <?php endif; ?>
                </div>

                <!-- Product Details -->
                <div class="product-details">
                    <h1><?php echo htmlspecialchars($product['product_name']); ?></h1>

                    <!-- Rating and Total Sold -->
                    <!-- Replace the dummy stars with dynamic stars based on actual rating -->
                    <div class="rating">
                        <?php
                        $avg_rating = round($product['rating'] ?? 0, 1);
                        $full_stars = floor($avg_rating);
                        $has_half_star = ($avg_rating - $full_stars) >= 0.5;
                        $empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0);

                        // Display full stars
                        for ($i = 0; $i < $full_stars; $i++) {
                            echo '★';
                        }

                        // Display half star if needed
                        if ($has_half_star) {
                            echo '½';
                        }

                        // Display empty stars
                        for ($i = 0; $i < $empty_stars; $i++) {
                            echo '☆';
                        }
                        ?>
                        <p><?php echo number_format($avg_rating, 1); ?> (<?php echo $product['review_count'] ?? 0; ?>
                            reviews)</p>
                        <p class="total-sold">| Total Sold: <?php echo htmlspecialchars($product['total_sold']); ?></p>
                    </div>

                    <!-- Price -->
                    <?php if ($promotion):
                        // Calculate discounted price
                        $original_price = $product['price'];
                        if ($promotion['discount_type'] == 'percentage') {
                            $discounted_price = $original_price * (1 - ($promotion['discount_value'] / 100));
                            $discount_text = $promotion['discount_value'] . '% OFF';
                        } else {
                            $discounted_price = $original_price - $promotion['discount_value'];
                            $discount_text = 'RM' . number_format($promotion['discount_value'], 2) . ' OFF';
                        }
                        ?>
                        <div class="promotion-info"
                            style="background-color: #fff3cd; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                            <p style="color: #e74c3c; font-weight: bold; margin: 0;">
                                <?php echo htmlspecialchars($promotion['promo_name']); ?>
                            </p>
                            <p style="margin: 5px 0 0; font-size: 0.9em;">
                                <?php
                                $start_date = new DateTime($promotion['start_date']);
                                $end_date = new DateTime($promotion['end_date']);
                                $now = new DateTime();
                                $interval = $now->diff($end_date);

                                echo $start_date->format('M d, Y') . " - " . $end_date->format('M d, Y');
                                echo "<br>";
                                if ($interval->days > 0) {
                                    echo "Ends in " . $interval->days . " day" . ($interval->days > 1 ? "s" : "");
                                } else {
                                    $hours = $interval->h;
                                    $minutes = $interval->i;
                                    echo "Ends in " . $hours . "h " . $minutes . "m";
                                }
                                ?>
                            </p>
                        </div>
                        <p class="price"
                            style="text-decoration: line-through; color: #999; margin-bottom: 5px; font-size: 0.9em;">
                            RM<?php echo number_format($original_price, 2); ?>
                        </p>
                        <p class="price" style="color: #e74c3c; font-size: 1.5em;">
                            RM<?php echo number_format($discounted_price, 2); ?>
                            <span
                                style="background-color: #e74c3c; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.6em; font-weight: bold; margin-left: 10px;">
                                <?php echo $discount_text; ?>
                            </span>
                        </p>
                    <?php else: ?>
                        <p class="price">RM<?php echo number_format($product['price'], 2); ?></p>
                    <?php endif; ?>

                    <!-- Quantity Selector -->
                    <div class="quantity-selector">
                        <label for="quantity">Quantity:</label>
                        <button type="button" onclick="decreaseQuantity()">-</button>
                        <input type="number" id="quantity" name="quantity" value="1" min="1"
                            max="<?php echo $product['product_quantity']; ?>">
                        <button type="button" onclick="increaseQuantity()">+</button>
                        <span style="opacity: 0.6; font-size: 0.9em; margin-left: 10px;">Stock:
                            <?php echo $product['product_quantity']; ?></span>
                    </div>

                    <!-- Product Actions -->
                    <div class="product-actions">
                        <?php if ($seller_status === 'suspended'): ?>
                            <div class="suspended-overlay">
                                <div class="suspended-message">
                                    <h3>Seller Account Suspended</h3>
                                    <p>This seller's account has been suspended. Products are temporarily unavailable for purchase.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['username'])): ?>
                            <?php if ($product['product_quantity'] > 0 && $seller_status !== 'Suspended'): ?>
                                <!-- Add to Cart Form -->
                                <form action="cart.php" method="POST" class="product-action-form" id="addToCartForm">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <input type="hidden" name="quantity" id="cart-quantity" value="1">
                                    <?php if ($promotion): ?>
                                        <input type="hidden" name="promotion_id" value="<?php echo $promotion['promotion_id']; ?>">
                                        <input type="hidden" name="discount_price" value="<?php echo $discounted_price; ?>">
                                    <?php endif; ?>
                                    <button type="submit" class="add-to-cart unified-button">Add to Cart</button>
                                </form>

                                <!-- Buy Now Form -->
                                <form action="purchaseItem.php" method="POST" class="product-action-form">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <input type="hidden" name="quantity" id="buy-now-quantity" value="1">
                                    <input type="hidden" name="from_cart" value="0">
                                    <?php if ($promotion): ?>
                                        <input type="hidden" name="promotion_id" value="<?php echo $promotion['promotion_id']; ?>">
                                        <input type="hidden" name="discount_price" value="<?php echo $discounted_price; ?>">
                                    <?php endif; ?>
                                    <button type="submit" class="buy-now unified-button">Buy Now</button>
                                </form>
                            <?php else: ?>
                                <button type="button" class="add-to-cart unified-button" disabled>Add to Cart</button>
                                <button type="button" class="buy-now unified-button" disabled>Buy Now</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- Modified buttons for non-logged in users -->
                            <button type="button" class="add-to-cart unified-button" onclick="showLoginAlert()">Add to Cart</button>
                            <button type="button" class="buy-now unified-button" onclick="showLoginAlert()">Buy Now</button>
                        <?php endif; ?>
                    </div>

                    <!-- Add Cart Notification -->
                    <div id="cartNotification" class="cart-notification" style="display: none;">
                        <div class="notification-content">
                            <i class="fas fa-check-circle"></i>
                            <span>Item added to cart successfully!</span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Seller Container -->
            <div class="seller-container">
                <div class="seller-logo">
                    <img src="uploads/<?php echo htmlspecialchars($seller['logo_url'] ?? 'default-logo.png'); ?>"
                        alt="Seller Logo">
                </div>

                <!-- Seller Info -->
                <div class="seller-info">
                    <div class="seller-header">
                        <h2><?php echo htmlspecialchars($seller['company_name'] ?? 'Unknown Seller'); ?></h2>
                        <div class="seller-actions">
                            <?php
                            // Fixed WhatsApp number
                            $whatsapp_number = '01110365650';
                            // Format WhatsApp number (remove any non-digit characters)
                            $clean_number = preg_replace('/[^0-9]/', '', $whatsapp_number);
                            // Create WhatsApp link with product name
                            $whatsapp_link = "https://wa.me/$clean_number?text=" .
                                urlencode("Hi, I'm interested in your product: " .
                                    htmlspecialchars($product['product_name']));
                            ?>
                            <a href="<?php echo $whatsapp_link; ?>" class="chat-now" target="_blank">Chat Now</a>
                            <a href="viewShop.php?seller_id=<?php echo $seller_id ? urlencode($seller_id) : '0'; ?>"
                                class="view-shop">
                                View Shop
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Vertical Separator -->
                <div class="seller-separator"></div>

                <div class="seller-details">
                    <p><strong>Total Products:</strong> <?php echo htmlspecialchars($seller['total_products']); ?></p>
                    <p><strong>Open Hours:</strong> <?php echo htmlspecialchars($seller['open_hours']); ?></p>
                    <p><strong>Joined:</strong>
                        <?php echo htmlspecialchars(date('Y-m-d', strtotime($seller['join_date']))); ?>
                    </p>
                </div>
            </div>

            <!-- Product Specification -->
            <div class="specifications-container">
                <h2>Product Specifications</h2>
                <div class="specifications-table">
                    <div class="specification-row">
                        <span class="spec-label">Product Name</span>
                        <span class="spec-value"><?php echo htmlspecialchars($product['product_name']); ?></span>
                    </div>
                    <div class="specification-row">
                        <span class="spec-label">Gender</span>
                        <span class="spec-value"><?php echo htmlspecialchars($product['gender']); ?></span>
                    </div>
                    <div class="specification-row">
                        <span class="spec-label">Concentration</span>
                        <span class="spec-value"><?php echo htmlspecialchars($product['concentration']); ?></span>
                    </div>
                    <div class="specification-row">
                        <span class="spec-label">Percentage</span>
                        <span class="spec-value"><?php echo htmlspecialchars($product['ConcentrationPercentage']); ?></span>
                    </div>
                    <div class="specification-row">
                        <span class="spec-label">Longevity</span>
                        <span class="spec-value"><?php echo htmlspecialchars($product['Longevity']); ?></span>
                    </div>
                    <div class="specification-row">
                        <span class="spec-label">Scent Type</span>
                        <span
                            class="spec-value"><?php echo htmlspecialchars($product['scents'] ?? 'No scent specified'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Product Description -->
            <div class="description-container">
                <h2>Product Description</h2>
                <div class="description-table">
                    <div class="description-row">
                        <span class="desc-label">
                            <?php echo nl2br(htmlspecialchars($product['product_description'])); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Product Review Section -->
            <div class="review-container">
                <h2>Customer Reviews</h2>

                <?php
                // Fetch existing reviews for this product including seller responses
                $reviewQuery = $conn->prepare("
                    SELECT 
                        r.*, 
                        c.Username, 
                        c.Name AS customer_name,
                        p.seller_id,
                        se.CompanyName AS seller_name,
                        se.LogoUrl AS seller_logo
                    FROM reviews r
                    JOIN customer c ON r.CustomerID = c.CustomerID
                    JOIN products p ON r.ProductID = p.product_id
                    JOIN seller se ON p.seller_id = se.SellerID
                    WHERE r.ProductID = ?
                    ORDER BY r.CreatedAt DESC
                ");
                $reviewQuery->bind_param("i", $product_id);
                $reviewQuery->execute();
                $reviews = $reviewQuery->get_result();

                if ($reviews->num_rows > 0): ?>
                    <div class="reviews-list">
                        <?php while ($review = $reviews->fetch_assoc()): ?>
                            <div class="review-item">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar">
                                        <?= strtoupper(substr($review['customer_name'] ?? $review['Username'], 0, 1)) ?>
                                    </div>
                                    <span class="reviewer-name">
                                        <?= htmlspecialchars($review['customer_name'] ?? $review['Username']) ?>
                                    </span>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?= $i <= $review['Rating'] ? 'filled' : '' ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="review-date">
                                        <?= date('M d, Y', strtotime($review['CreatedAt'])) ?>
                                    </span>
                                </div>
                                <div class="review-content">
                                    <p><?= nl2br(htmlspecialchars($review['ReviewText'])) ?></p>
                                    <?php if (!empty($review['ReviewImages'])): ?>
                                        <div class="review-images">
                                            <?php
                                            // Debug information
                                            error_log("Review images from database: " . $review['ReviewImages']);
                                            
                                            $images = explode(',', $review['ReviewImages']);
                                            error_log("Exploded images array: " . print_r($images, true));
                                            
                                            foreach ($images as $image):
                                                $image = trim($image);
                                                if (!empty($image)):
                                                    $imagePath = 'uploads/reviews/' . htmlspecialchars($image);
                                                    error_log("Checking image path: " . $imagePath);
                                                    if (file_exists($imagePath)): ?>
                                                        <div class="review-image-container">
                                                            <img src="<?= $imagePath ?>" alt="Review image" class="review-image" onclick="openImageModal('<?= $imagePath ?>')">
                                                        </div>
                                                    <?php else:
                                                        error_log("Image file not found: " . $imagePath);
                                                    endif;
                                                endif;
                                            endforeach; ?>
                                        </div>
                                    <?php else:
                                        error_log("No review images found for review ID: " . $review['ReviewID']);
                                    endif; ?>

                                    <!-- Seller Response Section -->
                                    <?php if (!empty($review['SellerResponse'])): ?>
                                        <div class="seller-response">
                                            <div class="seller-response-header">
                                                <?php if (!empty($review['seller_logo'])): ?>
                                                    <img src="uploads/<?= htmlspecialchars($review['seller_logo']) ?>"
                                                        alt="<?= htmlspecialchars($review['seller_name']) ?>" class="seller-avt-logo">
                                                <?php else: ?>
                                                    <div class="seller-avatar">
                                                        <?= strtoupper(substr($review['seller_name'], 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <span class="seller-name"><?= htmlspecialchars($review['seller_name']) ?></span>
                                                <span class="response-date">
                                                    Responded on <?= date('M d, Y', strtotime($review['ResponseDate'])) ?>
                                                </span>
                                            </div>
                                            <div class="seller-response-text">
                                                <?= nl2br(htmlspecialchars($review['SellerResponse'])) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="no-reviews">No reviews yet for this product.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="similar-products">
            <h2>Similar Products</h2>
            <div class="similar-product-list">
                <?php
                // Get the current product's scents
                $current_scents = explode(', ', $product['scents']);
                
                // Query to find similar products based on scents
                $similar_query = $conn->prepare("
                    SELECT DISTINCT p.product_id, p.product_name, p.price, p.product_image,
                           pr.promotion_id, pr.discount_type, pr.discount_value
                    FROM products p
                    LEFT JOIN product_scent ps ON p.product_id = ps.product_id
                    LEFT JOIN scents s ON ps.scent_id = s.ScentID
                    LEFT JOIN promotions pr ON p.product_id = pr.product_id 
                        AND pr.start_date <= NOW() 
                        AND pr.end_date >= NOW()
                    WHERE p.product_id != ? 
                    AND s.ScentName IN (" . str_repeat('?,', count($current_scents) - 1) . "?)
                    AND p.product_quantity > 0
                    GROUP BY p.product_id
                    HAVING COUNT(DISTINCT s.ScentName) >= 1
                    LIMIT 5
                ");
                
                $params = array_merge([$product_id], $current_scents);
                $types = str_repeat('s', count($params));
                $similar_query->bind_param($types, ...$params);
                $similar_query->execute();
                $similar_products = $similar_query->get_result();

                while ($similar = $similar_products->fetch_assoc()):
                    // Calculate discounted price if there's a promotion
                    $actual_price = $similar['price'];
                    $discount_text = '';
                    if ($similar['promotion_id']) {
                        if ($similar['discount_type'] == 'percentage') {
                            $actual_price = $similar['price'] * (1 - ($similar['discount_value'] / 100));
                            $discount_text = $similar['discount_value'] . '% OFF';
                        } else {
                            $actual_price = $similar['price'] - $similar['discount_value'];
                            $discount_text = 'RM' . number_format($similar['discount_value'], 2) . ' OFF';
                        }
                    }
                ?>
                    <a href="product_details.php?product_id=<?php echo $similar['product_id']; ?>" class="similar-product-item">
                        <img src="uploads/<?php echo htmlspecialchars($similar['product_image']); ?>" 
                             alt="<?php echo htmlspecialchars($similar['product_name']); ?>" 
                             class="similar-product-image">
                        <div class="similar-product-info">
                            <div class="similar-product-name"><?php echo htmlspecialchars($similar['product_name']); ?></div>
                            <div class="similar-product-price">
                                <?php if ($similar['promotion_id']): ?>
                                    <span class="original">RM<?php echo number_format($similar['price'], 2); ?></span>
                                <?php endif; ?>
                                RM<?php echo number_format($actual_price, 2); ?>
                                <?php if ($discount_text): ?>
                                    <span class="similar-product-badge"><?php echo $discount_text; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <!-- Image Modal -->
    <div id="imageModal" class="image-modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeImageModal()">&times;</span>
            <img class="modal-image" id="modalImage">
        </div>
    </div>

    <!-- Re-add the login alert HTML -->
    <div id="loginAlert" class="login-alert" style="display: none;">
        <div class="login-alert-content">
            <div class="login-alert-icon">
                <svg viewBox="0 0 24 24" width="48" height="48">
                    <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
                </svg>
            </div>
            <h3>Login Required</h3>
            <p>Please login to add items to cart or make a purchase.</p>
            <div class="login-alert-buttons">
                <button onclick="redirectToLogin()" class="login-btn">
                    <span>Login Now</span>
                    <svg viewBox="0 0 24 24" width="20" height="20" style="vertical-align: middle; margin-left: 5px;">
                        <path fill="currentColor" d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
                    </svg>
                </button>
                <button onclick="closeLoginAlert()" class="cancel-btn">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Add this to your existing JavaScript
        function showOutOfStockAlert() {
            alert('Sorry, this product is currently out of stock.');
        }

        // Add to Cart AJAX
        document.getElementById('addToCartForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('cart.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show notification
                    const notification = document.getElementById('cartNotification');
                    notification.style.display = 'block';
                    
                    // Update cart count in top navigation
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        const currentCount = parseInt(cartCount.textContent || '0');
                        cartCount.textContent = currentCount + parseInt(formData.get('quantity'));
                    }
                    
                    // Hide notification after 3 seconds
                    setTimeout(() => {
                        notification.style.animation = 'fadeOut 0.3s ease-out';
                        setTimeout(() => {
                            notification.style.display = 'none';
                            notification.style.animation = 'fadeIn 0.3s ease-out';
                        }, 300);
                    }, 3000);
                } else {
                    alert(data.message || 'Error adding item to cart. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding item to cart. Please try again.');
            });
        });

        // Update your existing quantity functions
        function increaseQuantity() {
            const qtyInput = document.getElementById('quantity');
            const maxQuantity = <?php echo $product['product_quantity']; ?>;
            if (qtyInput.value < maxQuantity) {
                qtyInput.value = parseInt(qtyInput.value) + 1;
                updateAllQuantityInputs();
            }
        }

        function decreaseQuantity() {
            const qtyInput = document.getElementById('quantity');
            if (qtyInput.value > 1) {
                qtyInput.value = parseInt(qtyInput.value) - 1;
                updateAllQuantityInputs();
            }
        }

        function updateAllQuantityInputs() {
            const qtyInput = document.getElementById('quantity');
            const buyNowQtyInput = document.getElementById('buy-now-quantity');
            const cartQtyInput = document.getElementById('cart-quantity');
            const quantity = parseInt(qtyInput.value);
            
            if (buyNowQtyInput) buyNowQtyInput.value = quantity;
            if (cartQtyInput) cartQtyInput.value = quantity;
        }

        // Add event listener for manual quantity input
        document.getElementById('quantity').addEventListener('change', function() {
            const maxQuantity = <?php echo $product['product_quantity']; ?>;
            if (this.value > maxQuantity) {
                this.value = maxQuantity;
            } else if (this.value < 1) {
                this.value = 1;
            }
            updateAllQuantityInputs();
        });

        function addToCart(productId, quantity) {
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('action', 'add');

            fetch('cart.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert(data.message);
                    
                    // Update cart count in top navigation
                    if (window.parent) {
                        window.parent.postMessage({
                            type: 'cartUpdate',
                            count: data.cartCount
                        }, '*');
                    }
                } else {
                    alert(data.message || 'Error adding item to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding item to cart');
            });
        }

        // Re-add the showLoginAlert, redirectToLogin, and closeLoginAlert functions
        function showLoginAlert() {
            const currentUrl = window.location.href;
            sessionStorage.setItem('returnUrl', currentUrl);
            const loginAlert = document.getElementById('loginAlert');
            loginAlert.style.display = 'flex';
            loginAlert.classList.remove('animating-out');
            setTimeout(() => {
                loginAlert.classList.add('show', 'animating-in');
                loginAlert.style.opacity = '1';
            }, 10);
            // Remove animating-in after animation
            setTimeout(() => {
                loginAlert.classList.remove('animating-in');
            }, 310);
        }

        function redirectToLogin() {
            // Get the current URL and encode it
            const currentUrl = window.location.href;
            // Redirect to login page with return URL as a query parameter
            window.location.href = 'auth.php?return_url=' + encodeURIComponent(currentUrl);
        }

        function closeLoginAlert() {
            const loginAlert = document.getElementById('loginAlert');
            loginAlert.classList.remove('animating-in');
            loginAlert.classList.add('animating-out');
            loginAlert.style.opacity = '0';
            setTimeout(() => {
                loginAlert.classList.remove('show', 'animating-out');
                loginAlert.style.display = 'none';
            }, 310);
        }

        // Close alert when clicking outside
        document.getElementById('loginAlert').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLoginAlert();
            }
        });

        // Image Modal Functions
        function openImageModal(src) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'block';
            modalImg.src = src;
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
        }

        // Close modal when clicking outside the image
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>
</body>

</html>