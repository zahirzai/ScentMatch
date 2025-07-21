<?php
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get seller ID from URL and validate
$seller_id = !empty($_GET['seller_id']) ? intval($_GET['seller_id']) : null;

if (!$seller_id || $seller_id <= 0) {
    die("Error: Invalid Seller ID provided.");
}

// Get active tab from URL parameter
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';

// Map URL parameter to actual gender value in database if needed
$gender_map = [
    'men' => 'Man',
    'women' => 'Woman', 
    'unisex' => 'UniSex',
    'male' => 'Man',
    'female' => 'Woman',
    'Men' => 'Man',
    'Women' => 'Woman',
    'Unisex' => 'UniSex',
    'MALE' => 'Man',
    'FEMALE' => 'Woman',
    'UNISEX' => 'UniSex'
];

// The gender value to use in database query
$gender_filter = isset($gender_map[strtolower($active_tab)]) ? $gender_map[strtolower($active_tab)] : $active_tab;

// Fetch seller details
$seller_stmt = $conn->prepare("
    SELECT 
        SellerID, CompanyName, Email, Phone, Address, OpenHours, CreatedAt, LogoUrl, status
    FROM seller 
    WHERE SellerID = ?
");
$seller_stmt->bind_param("i", $seller_id);
$seller_stmt->execute();
$seller = $seller_stmt->get_result()->fetch_assoc();

if (!$seller) {
    echo "<h2 style='text-align:center; color:red;'>Seller not found.</h2>";
    exit;
}

// Prepare the base product query
$product_query = "
    SELECT 
        p.product_id, 
        p.product_name, 
        p.product_image, 
        p.price, 
        p.product_quantity, 
        COALESCE(AVG(r.Rating), 0) as rating,
        p.gender, 
        p.ConcentrationID, 
        p.created_at,
        DATEDIFF(NOW(), p.created_at) <= 14 AS is_new,
        COUNT(r.ReviewID) as review_count
    FROM products p
    LEFT JOIN reviews r ON p.product_id = r.ProductID
    WHERE p.seller_id = ?
";

// Add gender filter based on active tab
if ($active_tab !== 'all') {
    $product_query .= " AND LOWER(p.gender) = LOWER(?)";
}

$product_query .= " GROUP BY p.product_id, p.product_name, p.product_image, p.price, p.product_quantity, 
             p.gender, p.ConcentrationID, p.created_at
    ORDER BY p.created_at DESC";

// Prepare and execute the query
$product_stmt = $conn->prepare($product_query);

// Bind parameters based on active tab
if ($active_tab !== 'all') {
    $product_stmt->bind_param("is", $seller_id, $gender_filter);
} else {
    $product_stmt->bind_param("i", $seller_id);
}

$product_stmt->execute();
$products = $product_stmt->get_result();

// Count products
$total_products = $products->num_rows;

// Get active promotions for this seller
$promo_stmt = $conn->prepare("
    SELECT 
        promo.promotion_id,
        promo.promo_name,
        promo.discount_type,
        promo.discount_value,
        promo.end_date,
        p.product_id,
        p.product_name,
        p.product_image,
        p.price
    FROM 
        promotions promo
    JOIN 
        products p ON promo.product_id = p.product_id
    WHERE 
        p.seller_id = ?
        AND promo.start_date <= NOW() 
        AND promo.end_date >= NOW()
    ORDER BY 
        promo.created_at DESC
    LIMIT 4
");
$promo_stmt->bind_param("i", $seller_id);
$promo_stmt->execute();
$promotions = $promo_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($seller['CompanyName']); ?> - Shop | ScentMatch</title>
    <link rel="stylesheet" href="css/top-navigation.css">
    <link rel="stylesheet" href="css/viewShop.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .filter-tabs {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
            padding: 0 20px;
        }

        .filter-tab {
            padding: 12px 30px;
            border: 2px solid #222;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #222;
            background: transparent;
        }

        .filter-tab:hover {
            background: #222;
            color: white;
        }

        .filter-tab.active {
            background: #222;
            color: white;
        }

        /* Add styles for seller logo and suspended overlay */
        .seller-logo {
            position: relative;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
        }

        .seller-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .seller-logo .out-of-stock-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            text-align: center;
            padding: 10px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .filter-tabs {
                flex-wrap: wrap;
                gap: 10px;
            }

            .filter-tab {
                padding: 8px 20px;
                font-size: 14px;
            }
        }

        .no-items {
            text-align: center;
            padding: 40px 0;
            margin-bottom: 40px;
        }

        .no-items i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <?php include 'top-navigation.php'; ?>

    <!-- Shop Banner -->
    <div class="shop-banner">
        <div class="container">
            <div class="shop-header">
                <div class="seller-logo">
                    <img src="uploads/<?php echo htmlspecialchars($seller['LogoUrl'] ?? 'default-logo.png'); ?>"
                        alt="<?php echo htmlspecialchars($seller['CompanyName']); ?>">
                    <?php if ($seller['status'] === 'suspended'): ?>
                        <div class="out-of-stock-overlay">Seller Suspended</div>
                    <?php endif; ?>
                </div>
                <div class="seller-details">
                    <h1 class="seller-name"><?php echo htmlspecialchars($seller['CompanyName']); ?></h1>
                    <div class="seller-meta">
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span><?php echo htmlspecialchars($seller['OpenHours']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Joined: <?php echo date('M Y', strtotime($seller['CreatedAt'])); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($seller['Email']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($seller['Phone']); ?></span>
                        </div>
                    </div>

                    <div class="seller-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $total_products; ?></div>
                            <div class="stat-label">Products</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $promotions->num_rows; ?></div>
                            <div class="stat-label">Active Offers</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Promotions Section (if any available) -->
        <?php if ($promotions && $promotions->num_rows > 0): ?>
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-tags"></i> Special Offers</h2>
                <?php if ($promotions->num_rows > 4): ?>
                    <a href="special_offers.php?seller_id=<?php echo $seller_id; ?>" class="view-all">
                        View All Offers <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>

            <div class="promotions-wrapper">
                <?php
                while ($promo = $promotions->fetch_assoc()):
                    // Calculate discounted price
                    $original_price = $promo['price'];
                    if ($promo['discount_type'] == 'percentage') {
                        $discounted_price = $original_price * (1 - ($promo['discount_value'] / 100));
                        $discount_text = $promo['discount_value'] . '% OFF';
                    } else {
                        $discounted_price = $original_price - $promo['discount_value'];
                        $discount_text = 'RM' . number_format($promo['discount_value'], 2) . ' OFF';
                    }

                    // Calculate days remaining
                    $end_date = new DateTime($promo['end_date']);
                    $now = new DateTime();
                    $interval = $now->diff($end_date);
                    ?>
                    <div class="promo-card">
                        <div class="promo-badge"><?php echo htmlspecialchars($discount_text); ?></div>
                        <div class="promo-image">
                            <img src="uploads/<?php echo htmlspecialchars($promo['product_image']); ?>"
                                alt="<?php echo htmlspecialchars($promo['product_name']); ?>">
                        </div>
                        <div class="promo-content">
                            <h3 class="promo-title"><?php echo htmlspecialchars($promo['product_name']); ?></h3>

                            <div class="promo-pricing">
                                <div class="price-info">
                                    <span class="original-price">RM<?php echo number_format($original_price, 2); ?></span>
                                    <span class="discounted-price">RM<?php echo number_format($discounted_price, 2); ?></span>
                                </div>
                                <div class="time-remaining">
                                    <?php if ($interval->days > 0): ?>
                                        <span><?php echo $interval->days; ?> days left</span>
                                    <?php else: ?>
                                        <span>Ends today!</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <a href="product_details.php?product_id=<?php echo $promo['product_id']; ?>" class="promo-btn">
                                View Deal
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?seller_id=<?php echo $seller_id; ?>&tab=all" 
               class="filter-tab <?php echo $active_tab === 'all' ? 'active' : ''; ?>">
                All Products
            </a>
            <a href="?seller_id=<?php echo $seller_id; ?>&tab=men" 
               class="filter-tab <?php echo $active_tab === 'men' ? 'active' : ''; ?>">
                Men
            </a>
            <a href="?seller_id=<?php echo $seller_id; ?>&tab=women" 
               class="filter-tab <?php echo $active_tab === 'women' ? 'active' : ''; ?>">
                Women
            </a>
            <a href="?seller_id=<?php echo $seller_id; ?>&tab=unisex" 
               class="filter-tab <?php echo $active_tab === 'unisex' ? 'active' : ''; ?>">
                Unisex
            </a>
        </div>

        <!-- Products Section -->
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-shopping-bag"></i> 
                <?php 
                    echo $active_tab === 'all' ? 'All Products' : 
                         ucfirst($active_tab) . "'s Products";
                ?>
            </h2>
            <span><?php echo $total_products; ?> products available</span>
        </div>

        <?php if ($total_products > 0): ?>
            <div class="product-grid-view">
                <?php while ($product = $products->fetch_assoc()): ?>
                    <div class="product-card-view">
                        <?php if ($product['is_new']): ?>
                            <div class="new-badge">NEW</div>
                        <?php endif; ?>

                        <div class="product-image">
                            <img src="uploads/<?php echo htmlspecialchars($product['product_image']); ?>"
                                alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        </div>

                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h3>

                            <div class="product-meta">
                                <div class="product-price">RM<?php echo number_format($product['price'], 2); ?></div>
                                <div class="stock-info">Stock: <?php echo $product['product_quantity']; ?></div>
                            </div>

                            <div class="product-rating">
                                <div class="stars">
                                    <?php
                                    $rating = round($product['rating'], 1);
                                    $fullStars = floor($rating);
                                    $halfStar = ($rating - $fullStars) >= 0.5;
                                    
                                    // Display full stars
                                    for ($i = 0; $i < $fullStars; $i++) {
                                        echo '<i class="fas fa-star"></i>';
                                    }
                                    
                                    // Display half star if needed
                                    if ($halfStar) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    }
                                    
                                    // Display empty stars
                                    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                                    for ($i = 0; $i < $emptyStars; $i++) {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </div>
                                <span class="review-count">(<?php echo $product['review_count']; ?> reviews)</span>
                            </div>

                            <a href="product_details.php?product_id=<?php echo $product['product_id']; ?>"
                                class="product-action">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-items">
                <i class="fas fa-box-open"></i>
                <h3>No products available</h3>
                <p>No products found in this category. Please try another category!</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // Restore scroll position on page load if available
        document.addEventListener('DOMContentLoaded', function() {
            if (sessionStorage.getItem('scrollPosition')) {
                window.scrollTo(0, parseInt(sessionStorage.getItem('scrollPosition')));
                sessionStorage.removeItem('scrollPosition');
            }
        });

        // Save scroll position when filter tab is clicked
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function(e) {
                sessionStorage.setItem('scrollPosition', window.scrollY);
            });
        });
    </script>
</body>

</html> 