<?php
// Start session and check login status before any output
session_start();

// Redirect logged-in users to logged_home.php
if (isset($_SESSION['customerID'])) {
    header("Location: logged_home.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "scentmatch3");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get popular products
function getPopularProducts($conn, $limit = 5) {
    $query = "
        SELECT 
            p.product_id,
            p.product_name,
            p.price,
            p.product_image,
            COALESCE(SUM(s.QuantitySold), 0) AS total_sales
        FROM products p
        LEFT JOIN sales s ON p.product_id = s.ProductID
        GROUP BY p.product_id
        ORDER BY total_sales DESC
        LIMIT ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Check for active promotions for each product
    foreach ($products as &$product) {
        $promo_query = "
            SELECT 
                p.promotion_id,
                p.promo_name,
                p.discount_type,
                p.discount_value,
                p.start_date,
                p.end_date
            FROM 
                promotions p
            WHERE 
                p.product_id = ? 
                AND p.start_date <= NOW() 
                AND p.end_date >= NOW()
            ORDER BY 
                p.created_at DESC
            LIMIT 1";
            
        $promo_stmt = $conn->prepare($promo_query);
        $promo_stmt->bind_param("i", $product['product_id']);
        $promo_stmt->execute();
        $promo = $promo_stmt->get_result()->fetch_assoc();
        
        if ($promo) {
            $product['promotion'] = $promo;
            
            // Calculate discounted price
            if ($promo['discount_type'] == 'percentage') {
                $product['discounted_price'] = $product['price'] * (1 - ($promo['discount_value'] / 100));
                $product['discount_text'] = $promo['discount_value'] . '% OFF';
            } else {
                $product['discounted_price'] = $product['price'] - $promo['discount_value'];
                $product['discount_text'] = 'RM' . number_format($promo['discount_value'], 2) . ' OFF';
            }
        }
    }
    
    return $products;
}

$popularProducts = getPopularProducts($conn);

// Include navigation after all logic is processed
include 'top-navigation.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScentMatch</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/top-navigation.css">
    <link rel="stylesheet" href="css/promotions.css">
    <style>
        /* Update product card styles for more compact display */
        .product {
            position: relative;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .product img {
            width: 100%;
            height: 220px; /* Reduced from 280px */
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product:hover img {
            transform: scale(1.05);
        }

        .product h3 {
            font-size: 1rem; /* Reduced from 1.1rem */
            margin: 10px 15px 5px; /* Reduced top margin from 15px */
            color: #333;
            font-weight: 600;
            line-height: 1.3; /* Reduced from 1.4 */
            height: 2.6em; /* Reduced from 2.8em */
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 5px 15px 10px; /* Reduced bottom margin from 15px */
            gap: 20px;
        }

        .price-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            flex: 1;
        }

        .product .price {
            font-size: 1.1rem; /* Reduced from 1.2rem */
            font-weight: 700;
            color: #e74c3c;
            margin: 0;
        }

        .product .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 0.85rem; /* Reduced from 0.9rem */
            margin: 0;
        }

        .product .discounted-price {
            color: #e74c3c;
            font-weight: 700;
            margin: 0;
        }

        .product .total-sales,
        .product .match-score {
            font-size: 0.8rem; /* Reduced from 0.85rem */
            color: #666;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .product-link {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1;
        }

        .promotion-badge {
            position: absolute;
            top: 10px; /* Reduced from 15px */
            right: 10px; /* Reduced from 15px */
            background: #e74c3c;
            color: white;
            padding: 6px 10px; /* Reduced padding */
            border-radius: 20px;
            font-size: 0.8rem; /* Reduced from 0.85rem */
            font-weight: 600;
            z-index: 2;
            box-shadow: 0 2px 10px rgba(231, 76, 60, 0.3);
        }

        .promotion-badge.limited {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Container Styles */
        .container-wrapper {
            padding: 30px;
            border-radius: 15px;
            margin: 30px auto;
            max-width: 1200px;
        }

        .top-sale-title {
            display: block;
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 25px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        .top-sale-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: #e74c3c;
            border-radius: 3px;
        }

        .container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            padding: 10px;
        }

        /* Hero Section Styles */
        .img-header {
            position: relative;
            min-height: 700px;
            background: linear-gradient(135deg, #1a1a1a, #333);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .img-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('img/hero-bg.jpg') center/cover;
            opacity: 0.3;
            z-index: 1;
        }

        .center {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .quote-section {
            text-align: center;
            margin-bottom: 50px;
        }

        .quote-section h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            line-height: 1.2;
            margin: 0;
            padding: 0 20px;
        }

        /* Updated Scent Info Section Styles */
        .scent-info {
            background: #1a1a1a;
            padding: 60px 20px;
            text-align: center;
            overflow-x: auto;
            margin: 0;
            width: 100%;
        }

        .scent-info h2 {
            color: #ffffff;
            font-size: 2.5rem;
            margin-bottom: 30px;
            position: relative;
            display: inline-block;
        }

        .scent-info h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: #e74c3c;
            border-radius: 3px;
        }

        .scent-info p {
            max-width: 800px;
            margin: 0 auto 40px;
            color: #ffffff;
            font-size: 1.1rem;
            line-height: 1.6;
            opacity: 0.9;
        }

        .scent-navigation {
            position: relative;
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 0 20px;
            margin-bottom: 10px;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            z-index: 3;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.1);
        }

        .nav-btn:active {
            transform: scale(0.95);
        }

        .nav-btn svg {
            width: 24px;
            height: 24px;
        }

        .scent-boxes-container {
            display: flex;
            gap: 20px;
            padding: 20px 0;
            overflow-x: auto;
            scroll-behavior: smooth;
            width: 100%;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
        }

        /* Custom scrollbar styles */
        .scent-boxes-container::-webkit-scrollbar {
            height: 6px;
        }

        .scent-boxes-container::-webkit-scrollbar-track {
            background: transparent;
            border-radius: 3px;
        }

        .scent-boxes-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
            transition: background 0.3s ease;
        }

        .scent-boxes-container::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Hide scrollbar for IE and Edge */
        .scent-boxes-container {
            -ms-overflow-style: none;
        }

        /* For Firefox */
        .scent-boxes-container {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
        }

        .scent-box {
            position: relative;
            width: 300px;
            height: 400px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: transform 0.3s ease;
            flex-shrink: 0;
            border: none;
        }

        .scent-box:hover {
            transform: translateY(-5px);
        }

        .scent-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .scent-box:hover img {
            transform: scale(1.05);
        }

        /* Add permanent dim overlay */
        .scent-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1;
            transition: background 0.3s ease;
        }

        .scent-box:hover::before {
            background: rgba(0, 0, 0, 0.2);
        }

        .scent-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.95), rgba(0,0,0,0.7), transparent);
            color: white;
            padding: 20px;
            transform: translateY(-100%);
            transition: all 0.3s ease;
            z-index: 2;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .scent-box:hover .scent-overlay {
            transform: translateY(0);
            background: linear-gradient(to bottom, rgba(0,0,0,0.95), rgba(0,0,0,0.8));
        }

        .scent-overlay h3 {
            color: white;
            font-size: 1.4rem;
            margin-bottom: 10px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }

        .scent-box:hover .scent-overlay h3 {
            transform: translateY(0);
        }

        .scent-overlay p {
            color: rgba(255,255,255,0.9);
            font-size: 0.9rem;
            line-height: 1.6;
            margin: 0;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            transform: translateY(-20px);
            transition: transform 0.3s ease 0.1s;
            opacity: 0;
        }

        .scent-box:hover .scent-overlay p {
            transform: translateY(0);
            opacity: 1;
        }

        @media (max-width: 768px) {
            .scent-box {
                width: 250px;
                height: 350px;
            }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .container {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quote-section h1 {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 600px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .quote-section h1 {
                font-size: 2rem;
            }
            
            .container-wrapper {
                padding: 20px;
            }
        }

        /* Update container-wrapper styles */
        .container-wrapper {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 15px;
        }

        /* Add new top-sale-header styles */
        .top-sale-header {
            margin-bottom: 20px;
        }

        .top-sale-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin: 0;
            padding-left: 10px;
            position: relative;
        }

        .top-sale-header h2::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 24px;
            background: #e74c3c;
            border-radius: 2px;
        }

        /* Update container styles */
        .container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
        }

        /* Update responsive styles */
        @media (max-width: 1200px) {
            .container {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 900px) {
            .container {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 700px) {
            .container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .container {
                grid-template-columns: 1fr;
            }
        }

        /* Update recommended-wrapper styles */
        .recommended-wrapper {
            margin-top: 40px;
        }

        .no-preferences {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 12px;
            margin: 20px 0;
        }

        .no-preferences p {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 20px;
        }

        .setup-preferences {
            display: inline-block;
            padding: 12px 24px;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .setup-preferences:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .match-score {
            color: #27ae60;
            font-size: 0.9rem;
            margin: 5px 15px 15px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .match-score::before {
            content: '🎯';
            font-size: 1rem;
        }

        /* About Us Section Styles */
        .about-section {
            background: #ffffff;
            padding: 80px 20px;
            position: relative;
        }

        .about-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            gap: 60px;
            align-items: center;
        }

        .about-text {
            flex: 1;
        }

        .about-text h2 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .about-text h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 3px;
            background: #e74c3c;
            border-radius: 3px;
        }

        .tagline {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 30px;
            font-style: italic;
        }

        .about-description {
            margin-bottom: 40px;
        }

        .about-description p {
            color: #555;
            line-height: 1.8;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        .about-features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 40px;
        }

        .feature {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            transition: transform 0.3s ease;
        }

        .feature:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .feature h3 {
            color: #333;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .feature p {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .about-image {
            flex: 1;
            position: relative;
        }

        .about-image img {
            width: 100%;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        @media (max-width: 992px) {
            .about-content {
                flex-direction: column;
            }

            .about-features {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .about-features {
                grid-template-columns: 1fr;
            }

            .about-text h2 {
                font-size: 2rem;
            }

            .about-description p {
                font-size: 1rem;
            }
        }

        /* Add smooth scroll behavior to the whole page */
        html {
            scroll-behavior: smooth;
        }

        /* Welcome Popup Styles */
        .welcome-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            animation: fadeIn 0.3s ease-in-out;
        }

        .welcome-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            max-width: 500px;
            width: 90%;
            text-align: center;
            animation: slideIn 0.5s ease-out;
        }

        .welcome-content h2 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 20px;
        }

        .welcome-content p {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .welcome-button {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .welcome-button:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }
    </style>
</head>

<body>
    <div class="img-header">
        <div class="center">
            <div class="quote-section">
                <h1 id="quote-text"></h1>
            </div>
            <div class="top-rated-section">
                <h5>Top Rated Products</h5>

                <div class="product-grid">
                    <?php
                    // Fetch 4 products for the img-header
                    $header_query = "SELECT product_id, product_name, price, product_image FROM products LIMIT 4";
                    $header_result = $conn->query($header_query);

                    if ($header_result->num_rows > 0) {
                        while ($row = $header_result->fetch_assoc()) {
                            echo '<a href="product_details.php?product_id=' . $row['product_id'] . '" class="product-item">';
                            echo '<img src="uploads/' . htmlspecialchars($row['product_image']) . '" alt="' . htmlspecialchars($row['product_name']) . '">';
                            echo '<p>' . htmlspecialchars($row['product_name']) . '</p>';
                            echo '</a>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="option-btn">
        <span>|</span>
        <a href="seller/seller_login.php" target="_blank">Seller Centre</a>
        <span>|</span>
        <a href="#about-section">About us</a>
        <span>|</span>
        <a href="all_promotions.php" style="color: #e74c3c;">Special Offers</a>
        <span>|</span>
    </div>

    <div class="container-wrapper">
        <div class="top-sale-header">
            <h2>Top Sale Products</h2>
        </div>
        <div class="container">
            <?php
            // Modify the query to get 5 products instead of 4
            $popularProducts = getPopularProducts($conn, 5);
            
            if (!empty($popularProducts)) {
                foreach ($popularProducts as $product) {
                    echo '<div class="product' . (isset($product['promotion']) ? ' promotion-product' : '') . '">';
                    
                    // Show promotion badge if there's an active promotion
                    if (isset($product['promotion'])) {
                        $is_limited = (strtotime($product['promotion']['end_date']) - time() < 86400);
                        $badge_class = $is_limited ? 'promotion-badge limited' : 'promotion-badge';
                        echo '<div class="' . $badge_class . '">' . htmlspecialchars($product['discount_text']) . '</div>';
                    }
                    
                    echo '<img src="uploads/' . htmlspecialchars($product['product_image']) . '" alt="' . htmlspecialchars($product['product_name']) . '">';
                    echo '<h3>' . htmlspecialchars($product['product_name']) . '</h3>';
                    
                    echo '<div class="product-info">';
                    echo '<div class="price-container">';
                    if (isset($product['promotion'])) {
                        echo '<p class="price original-price">RM' . number_format($product['price'], 2) . '</p>';
                        echo '<p class="price discounted-price">RM' . number_format($product['discounted_price'], 2) . '</p>';
                    } else {
                        echo '<p class="price">RM' . number_format($product['price'], 2) . '</p>';
                    }
                    echo '</div>';
                    echo '<p class="total-sales">Sold ' . htmlspecialchars($product['total_sales']) . '</p>';
                    echo '</div>';
                    
                    echo '<a href="product_details.php?product_id=' . $product['product_id'] . '" class="product-link"></a>';
                    echo '</div>';
                }
            } else {
                echo '<p>No products found.</p>';
            }
            ?>
        </div>
    </div>

    <div class="scent-info">
        <h2>Discover Your Signature Scent</h2>
        <p>
            Explore our wide range of fragrances and find the one that resonates with your unique personality. From
            fresh and floral to bold and musky, we have something for everyone.
        </p>
        <!-- Scent Boxes -->
        <div class="scent-navigation">
            <button class="nav-btn prev-btn" onclick="scrollScents('left')">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
            </button>
            <div class="scent-boxes-container">
                <div class="scent-box">
                    <img src="img/floral.jpg" alt="Floral Scents">
                    <div class="scent-overlay">
                        <h3>Floral Scents</h3>
                        <p>Floral fragrances are inspired by flowers and evoke feelings of elegance and romance. Common notes
                            include rose, jasmine, and lavender.</p>
                    </div>
                </div>
                <div class="scent-box">
                    <img src="img/woody.jpg" alt="Woody Scents">
                    <div class="scent-overlay">
                        <h3>Woody Scents</h3>
                        <p>Woody fragrances are warm, earthy, and rich, often adding depth and sophistication. Common notes include
                            sandalwood, cedarwood, and patchouli.</p>
                    </div>
                </div>
                <div class="scent-box">
                    <img src="img/citrus.jpg" alt="Citrus Scents">
                    <div class="scent-overlay">
                        <h3>Citrus Scents</h3>
                        <p>Citrus fragrances are fresh, zesty, and invigorating, perfect for everyday wear. Common notes include
                            bergamot, lemon, and grapefruit.</p>
                    </div>
                </div>
                <div class="scent-box">
                    <img src="img/oriental.jpg" alt="Oriental Scents">
                    <div class="scent-overlay">
                        <h3>Oriental Scents</h3>
                        <p>Oriental fragrances are exotic, spicy, and sensual, offering allure and mystery. Common notes include
                            amber, vanilla, and musk.</p>
                    </div>
                </div>
                <div class="scent-box">
                    <img src="img/amber.jpg" alt="Amber Scents">
                    <div class="scent-overlay">
                        <h3>Amber Scents</h3>
                        <p>Amber fragrances are warm, sweet, and resinous, creating a rich and luxurious scent profile. Common notes include
                            labdanum, benzoin, and vanilla.</p>
                    </div>
                </div>
                <div class="scent-box">
                    <img src="img/fruity.jpg" alt="Fruity Scents">
                    <div class="scent-overlay">
                        <h3>Fruity Scents</h3>
                        <p>The fragrance opens with a burst of juicy fruits that provide an immediate sense of liveliness and sweetness. 
                            Think of the crisp bite of green apple, the tartness of citrus, and the subtle juiciness of berries. 
                            These fruity elements create a vibrant, playful opening that captures attention.</p>
                    </div>
                </div>
                <div class="scent-box">
                    <img src="img/green.jpg" alt="Green Scents">
                    <div class="scent-overlay">
                        <h3>Green Scents</h3>
                        <p>Following the fruitiness, green accords evoke the scent of freshly cut grass, crushed green leaves, and dewy stems. 
                            These elements bring a natural, verdant freshness, grounding the sweetness of the fruits and adding a feeling of renewal and energy.</p>
                    </div>
                </div>
                <div class="scent-box">
                    <img src="img/herbal.jpg" alt="Herbal Scents">
                    <div class="scent-overlay">
                        <h3>Herbal Scents</h3>
                        <p>Interwoven with the green facets are herbal undertones—perhaps rosemary, basil, or sage. These lend a slightly earthy, aromatic sharpness, 
                            offering a crisp contrast to the juicy fruit and verdant greens. The herbal layer enhances complexity and sophistication.</p>
                    </div>
                </div>
                <div class="scent-box">
                    <img src="img/spicy.jpg" alt="Spicy Scents">
                    <div class="scent-overlay">
                        <h3>Spicy Scents</h3>
                        <p>As the fragrance settles, a spicy warmth begins to emerge. This could include hints of cardamom, cinnamon, pink pepper, or cloves—spices 
                            that provide a subtle heat and an exotic depth. The spices invigorate the composition, adding a sensual and energizing character.</p>
                    </div>
                </div>
                <div class="scent-box">
                    <img src="img/gourmand.jpg" alt="gourmand Scents">
                    <div class="scent-overlay">
                        <h3>Gourmand Scents</h3>
                        <p>At the heart of the fragrance, gourmand notes create an irresistible, mouth-watering quality. Imagine creamy vanilla, caramel, 
                            or praline-like facets that add a delicious, edible sweetness. This sweetness complements the fruity top and adds indulgence to the scent.</p>
                    </div>
                </div>
                <div class="scent-box">
                    <img src="img/aquatic.jpg" alt="Aquatic Scents">
                    <div class="scent-overlay">
                        <h3>Aquatic Scents</h3>
                        <p>A cool, breezy aquatic element balances the warmth of the spices and gourmand notes. Think of sea spray, mineral freshness, or the scent 
                            of clean water, which brings a crisp, marine-like clarity to the composition. It adds a refreshing, airy lift.</p>
                    </div>
                </div>
                <div class="scent-box">
                    <img src="img/musk.jpg" alt="Musk Scents">
                    <div class="scent-overlay">
                        <h3>Musk Scents</h3>
                        <p>As the scent evolves, a subtle musky base lends a soft, skin-like warmth. This musky element creates intimacy and depth, blending 
                            seamlessly with other notes while adding a velvety, sensual undertone that lingers on the skin.</p>
                    </div>
                </div>
                <div class="scent-box">
                    <img src="img/leather.jpg" alt="Leather Scents">
                    <div class="scent-overlay">
                        <h3>Leather Scents</h3>
                        <p>Interwoven into the base, leather notes bring a hint of rugged sophistication. Think of the scent of fine leather or suede, 
                            providing a smooth, refined, and slightly smoky depth that contrasts with the freshness above.</p>
                    </div>
                </div>
                <div class="scent-box">
                    <img src="img/aromatic.jpg" alt="Aromatic Scents">
                    <div class="scent-overlay">
                        <h3>Aromatic Scents</h3>
                        <p>The herbal, spicy, and green aspects coalesce into a broader aromatic character. This creates a vibrant, energizing effect, 
                            blending hints of fresh-cut herbs, spices, and florals, forming an uplifting and stimulating aura.</p>
                    </div>
                </div>
                <div class="scent-box">
                    <img src="img/fresh.jpg" alt="Fresh Scents">
                    <div class="scent-overlay">
                        <h3>Fresh Scents</h3>
                        <p>Throughout the fragrance, a continuous thread of freshness ensures balance. Whether from citrus, herbs, or cool aquatic notes, 
                            this keeps the scent lively, clean, and invigorating, preventing the gourmand and leather elements from feeling too heavy.</p>
                    </div>
                </div>
                <div class="scent-box">
                    <img src="img/sweet.jpg" alt="Sweet Scents">
                    <div class="scent-overlay">
                        <h3>Sweet Scents</h3>
                        <p>A delicate hint of sweetness ties everything together. This sweetness comes not only from the fruits and gourmand elements but also 
                            from subtle facets of vanilla or tonka bean in the base. It creates a harmonious, comforting finish that lingers pleasantly.</p>
                    </div>
                </div>
            </div>
            <button class="nav-btn next-btn" onclick="scrollScents('right')">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 18l6-6-6-6"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- About Us Section -->
    <div class="about-section" id="about-section">
        <div class="about-content">
            <div class="about-text">
                <h2>About ScentMatch</h2>
                <p class="tagline">Where Fragrance Meets Personality</p>
                <div class="about-description">
                    <p>Welcome to ScentMatch, your ultimate destination for discovering the perfect fragrance that resonates with your unique personality. We believe that every individual deserves to find their signature scent - one that not only smells amazing but also tells their story.</p>
                    <p>Our innovative scent-matching technology combines traditional perfumery knowledge with modern algorithms to create personalized fragrance recommendations that truly match your style, preferences, and personality.</p>
                </div>
                <div class="about-features">
                    <div class="feature">
                        <div class="feature-icon">🎯</div>
                        <h3>Personalized Matching</h3>
                        <p>Advanced algorithms that understand your unique preferences</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">🌿</div>
                        <h3>Quality Assured</h3>
                        <p>Curated collection of premium fragrances from renowned brands</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">💡</div>
                        <h3>Expert Guidance</h3>
                        <p>Professional advice to help you make informed choices</p>
                    </div>
                </div>
            </div>
            <div class="about-image">
                <img src="img/perfume.jpg" alt="ScentMatch Perfumes">
            </div>
        </div>
    </div>

    <!-- Add this before the closing body tag -->
    <script>
        // Quote rotation and typing animation
        const quotes = [
            "THE RIGHT SCENT\nCAN SPEAK\nLOUDER THAN\nWORDS",
            "DARE TO BE NOTICED.\nLET YOUR SCENT\nMAKE THE\nFIRST MOVE",
            "WHEN MEMORY FAILS,\nFRAGRANCE\nREMEMBERS"
        ];

        let currentQuoteIndex = 0;
        const quoteElement = document.getElementById('quote-text');

        function typeQuote(quote) {
            quoteElement.textContent = '';
            quoteElement.classList.remove('fade-out');
            quoteElement.classList.add('fade-in');
            
            const words = quote.split('\n');
            let currentWordIndex = 0;
            let currentCharIndex = 0;
            
            function typeNextChar() {
                if (currentWordIndex < words.length) {
                    if (currentCharIndex < words[currentWordIndex].length) {
                        quoteElement.textContent += words[currentWordIndex][currentCharIndex];
                        currentCharIndex++;
                        setTimeout(typeNextChar, 50);
                    } else {
                        quoteElement.textContent += '\n';
                        currentWordIndex++;
                        currentCharIndex = 0;
                        setTimeout(typeNextChar, 100);
                    }
                } else {
                    setTimeout(() => {
                        quoteElement.classList.remove('fade-in');
                        quoteElement.classList.add('fade-out');
                        setTimeout(() => {
                            currentQuoteIndex = (currentQuoteIndex + 1) % quotes.length;
                            typeQuote(quotes[currentQuoteIndex]);
                        }, 500);
                    }, 3000);
                }
            }
            
            typeNextChar();
        }

        // Start the quote rotation
        typeQuote(quotes[currentQuoteIndex]);

        function scrollScents(direction) {
            const container = document.querySelector('.scent-boxes-container');
            const scrollAmount = 320; // Width of box + gap
            
            if (direction === 'left') {
                container.scrollLeft -= scrollAmount;
            } else {
                container.scrollLeft += scrollAmount;
            }
        }

        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                scrollScents('left');
            } else if (e.key === 'ArrowRight') {
                scrollScents('right');
            }
        });
    </script>
</body>
<?php include"footer.php" ?>
</html>