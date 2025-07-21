<?php
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");
include 'top-navigation.php';

// Check for database connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get filter parameters
$gender_filter = isset($_GET['gender']) ? $_GET['gender'] : '';
$concentration_filter = isset($_GET['concentration']) ? $_GET['concentration'] : '';
$min_price = isset($_GET['min_price']) ? (float) $_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float) $_GET['max_price'] : 1000;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';
$scent_types = isset($_GET['scent_types']) ? $_GET['scent_types'] : [];

// Initialize an array to hold WHERE clause conditions
$where_conditions = ["p.price BETWEEN ? AND ?"];

// Initialize an array to hold SQL parameters
$params = [$min_price, $max_price];
$types = "dd"; // For min and max price

// Map URL parameter to actual gender value in database if needed
$gender_map = [
    'male' => 'Man',
    'female' => 'Woman', 
    'unisex' => 'UniSex',
    // Add case variations to ensure we catch all possible formats
    'men' => 'Man',
    'women' => 'Woman',
    'Male' => 'Man',
    'Female' => 'Woman',
    'Unisex' => 'UniSex',
    'MALE' => 'Man',
    'FEMALE' => 'Woman',
    'UNISEX' => 'UniSex'
];

// The gender value to use in database query
$gender_db_value = isset($gender_map[$gender_filter]) ? $gender_map[$gender_filter] : $gender_filter;

// Get all available concentrations for filter
$concentration_query = "SELECT ConcentrationID, ConcentrationName FROM concentration ORDER BY ConcentrationName";
$concentration_result = $conn->query($concentration_query);
$concentrations = [];
if ($concentration_result->num_rows > 0) {
    while ($row = $concentration_result->fetch_assoc()) {
        $concentrations[] = $row;
    }
}

// Get price range for slider
$price_query = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM products";
$price_result = $conn->query($price_query);
$price_range = $price_result->fetch_assoc();
$db_min_price = floor($price_range['min_price']);
$db_max_price = ceil($price_range['max_price']);

// If no min/max specified, use database values
if (!isset($_GET['min_price'])) $min_price = $db_min_price;
if (!isset($_GET['max_price'])) $max_price = $db_max_price;

// Add gender filter if selected
if (!empty($gender_filter)) {
    if ($gender_filter == 'unisex') {
        $where_conditions[] = "UPPER(p.gender) = 'UNISEX'";
    } else {
        $where_conditions[] = "UPPER(p.gender) = UPPER(?)";
        $params[] = $gender_db_value;
        $types .= "s";
    }
}

// Add concentration filter if selected
if (!empty($concentration_filter)) {
    $where_conditions[] = "p.ConcentrationID = ?";
    $params[] = $concentration_filter;
    $types .= "i";
}

// Add scent type filter if selected
if (!empty($scent_types)) {
    $placeholders = str_repeat('?,', count($scent_types) - 1) . '?';
    $where_conditions[] = "p.product_id IN (
        SELECT product_id 
        FROM product_scent 
        WHERE scent_id IN ($placeholders)
        GROUP BY product_id 
        HAVING COUNT(DISTINCT scent_id) = ?
    )";
    foreach ($scent_types as $scent_id) {
        $params[] = $scent_id;
        $types .= "i";
    }
    // Add count parameter for HAVING clause
    $params[] = count($scent_types);
    $types .= "i";
}

// Add promotion filter if 'promotion' sort is selected
// This ensures only promoted products are selected when sorting by promotion
if ($sort_by === 'promotion') {
    $where_conditions[] = "pr.promotion_id IS NOT NULL AND pr.start_date <= NOW() AND pr.end_date >= NOW()";
}

// Get active promotions
$promotion_query = "SELECT p.product_id, p.product_name, pr.promotion_id, pr.start_date, pr.end_date, pr.discount_type, pr.discount_value 
                   FROM products p 
                   LEFT JOIN promotions pr ON p.product_id = pr.product_id 
                   WHERE pr.start_date <= NOW() AND pr.end_date >= NOW()";
$promotion_result = $conn->query($promotion_query);

// Debug information
if (!$promotion_result) {
    echo "<!-- Promotion query error: " . $conn->error . " -->";
}

$active_promotions = [];
if ($promotion_result && $promotion_result->num_rows > 0) {
    while ($promo = $promotion_result->fetch_assoc()) {
        $active_promotions[$promo['product_id']] = $promo;
    }
    echo "<!-- Found " . count($active_promotions) . " active promotions -->";
} else {
    echo "<!-- No active promotions found -->";
}

// Prepare SQL query - Build the SELECT and FROM parts first
$sql = "
    SELECT DISTINCT
        p.product_id, 
        p.product_image, 
        p.product_name, 
        p.price, 
        p.gender, 
        c.ConcentrationName AS concentration,
        (SELECT COALESCE(SUM(QuantitySold), 0) FROM sales WHERE ProductID = p.product_id) AS total_sales,
        pr.promotion_id,
        pr.promo_name,
        pr.discount_type,
        pr.discount_value,
        pr.start_date,
        pr.end_date,
        s.LogoUrl AS seller_logo,
        s.CompanyName AS seller_name,
        (SELECT AVG(r.Rating) FROM reviews r WHERE r.ProductID = p.product_id) AS rating,
        (SELECT COUNT(r.Rating) FROM reviews r WHERE r.ProductID = p.product_id) AS review_count
    FROM products p
    LEFT JOIN concentration c ON p.ConcentrationID = c.ConcentrationID
    LEFT JOIN product_scent ps ON p.product_id = ps.product_id
    LEFT JOIN promotions pr ON p.product_id = pr.product_id 
        AND pr.start_date <= NOW() 
        AND pr.end_date >= NOW()
    LEFT JOIN seller s ON p.seller_id = s.SellerID";

// Append all collected WHERE conditions
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

// Group results by product_id
$sql .= " GROUP BY p.product_id";

// Add sorting
switch ($sort_by) {
    case 'price_low':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'promotion':
        // Order by the actual discounted price for promoted products
        $sql .= " ORDER BY (
            CASE 
                WHEN pr.discount_type = 'percentage' 
                THEN p.price * (1 - pr.discount_value/100)
                ELSE p.price - pr.discount_value
            END
        ) ASC";
        break;
    case 'newest':
        $sql .= " ORDER BY p.created_at DESC";
        break;
    case 'bestselling':
        $sql .= " ORDER BY total_sales DESC";
        break;
    case 'top_rated':
        // For all_product.php, we don't have average_rating in the initial SELECT, so we need to re-calculate or adjust
        // Assuming we can recalculate average rating for sorting here:
        $sql .= " ORDER BY (SELECT COALESCE(AVG(r.Rating), 0) FROM reviews r WHERE r.ProductID = p.product_id) DESC, total_sales DESC";
        break;
    case 'relevance':
    default:
        $sql .= " ORDER BY total_sales DESC";
        break;
}

// Prepare statement
$stmt = $conn->prepare($sql);

// Bind parameters
// Check if number of types matches number of parameters
if (strlen($types) !== count($params)) {
    die("Parameter count mismatch: Expected " . strlen($types) . ", got " . count($params) . ". Types: " . $types . ", Params: " . print_r($params, true));
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Count total results
$total_results = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Products</title>
    <link rel="stylesheet" href="css/top-navigation.css">
    <link rel="stylesheet" href="css/all_prodct.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        /* Add these styles to your existing CSS */
        .scent-checkboxes {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            max-height: 200px;
            overflow-y: auto;
            padding: 5px;
        }

        .scent-checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .scent-checkbox-item input[type="checkbox"] {
            margin: 0;
        }

        .scent-checkbox-item label {
            font-size: 0.9rem;
            color: #333;
            cursor: pointer;
        }

        .scent-checkboxes::-webkit-scrollbar {
            width: 4px;
        }

        .scent-checkboxes::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .scent-checkboxes::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .scent-checkboxes::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Add compare container styles */
        .compare-container {
            display: flex;
            align-items: center;
            padding: 5px;
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(248, 249, 250, 0.9);
            border-top: 1px solid #eee;
            z-index: 2;
        }

        .compare-checkbox {
            margin-right: 8px;
            cursor: pointer;
            width: 16px;
            height: 16px;
        }

        .compare-label {
            font-size: 14px;
            color: #333;
            cursor: pointer;
            user-select: none;
        }

        .compare-checkbox:checked + .compare-label {
            color: #3498db;
            font-weight: 500;
        }

        /* Sticky Compare Container */
        .sticky-compare {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 12px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
            justify-content: space-between;
            align-items: center;
            border-radius: 30px;
            min-width: 300px;
            max-width: 400px;
            transition: all 0.3s ease;
        }

        .sticky-compare.visible {
            display: flex;
        }

        .compare-status {
            font-size: 14px;
            color: #333;
            margin-right: 15px;
        }

        .compare-actions {
            display: flex;
            gap: 8px;
        }

        .compare-btn {
            padding: 6px 15px;
            background: rgb(0, 0, 0);
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 13px;
        }

        .compare-btn:hover {
            background: rgb(115, 115, 115);
        }

        .compare-btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }

        .clear-compare {
            padding: 6px 15px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 13px;
        }

        .clear-compare:hover {
            background: #c0392b;
        }

        /* Comparison View */
        .comparison-view {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) scale(0.8);
            background: white;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1001;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            max-height: 65vh;
            overflow-y: auto;
            width: 75%;
            max-width: 1200px;
        }

        .comparison-view.active {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) scale(1);
            bottom: 15%;
            margin-bottom: 20px;
        }

        .comparison-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(350px, 1fr));
            gap: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0.8rem;
        }

        .product-comparison {
            padding: 1rem;
            border: 1px solid #eee;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            min-width: 350px;
            display: flex;
            flex-direction: column;
        }

        .product-comparison img {
            width: 100%;
            max-width: 250px;
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .product-comparison h3 {
            font-size: 1.1rem;
            margin-bottom: 0.8rem;
            color: #333;
            line-height: 1.3;
        }

        .product-comparison .price {
            font-size: 1.1rem;
            color: #e44d26;
            margin-bottom: 0.8rem;
            font-weight: 600;
        }

        .product-comparison .rating {
            color: #ffc107;
            margin-bottom: 0.8rem;
            font-size: 1rem;
        }

        .product-comparison .specs {
            margin: 1rem 0;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 12px;
        }

        .product-comparison .specs p {
            margin: 0.4rem 0;
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .product-comparison .description {
            margin-top: 1rem;
            line-height: 1.5;
            color: #666;
            font-size: 0.9rem;
            padding: 0.8rem;
            background: #f9f9f9;
            border-radius: 12px;
            flex-grow: 1;
        }

        .product-comparison .view-details-btn {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.8rem 1.5rem;
            background-color: rgb(0, 0, 0);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: background-color 0.3s ease;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }

        .product-comparison .view-details-btn:hover {
            background-color: rgb(255, 102, 102);
        }

        .close-comparison {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: #666;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.3s;
        }

        .close-comparison:hover {
            background-color: #f0f0f0;
        }

        .comparison-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(3px);
        }

        .comparison-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Hide sticky compare when comparison is active */
        .comparison-view.active ~ .sticky-compare {
            opacity: 0;
            visibility: hidden;
            transform: translateX(-50%) translateY(20px);
        }

        .product {
            position: relative;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            padding-bottom: 40px;
        }

        .product:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .product img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product:hover img {
            transform: scale(1.05);
        }

        .product h3 {
            font-size: 1rem;
            margin: 10px 15px 5px;
            color: #333;
            font-weight: 600;
            line-height: 1.3;
            height: 2.6em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 5px 15px 10px;
            gap: 20px;
        }

        .price-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            flex: 1;
            position: relative;
            z-index: 3;
        }

        .product-link {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 40px;
            z-index: 1;
        }

        .product .price {
            font-size: 1.1rem;
            font-weight: 700;
            color: #e74c3c;
            margin: 0;
        }

        .product .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 0.85rem;
            margin: 0;
        }

        .product .discounted-price {
            color: #e74c3c;
            font-weight: 700;
            margin: 0;
        }

        .product .total-sales {
            font-size: 0.8rem;
            color: #666;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .promotion-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e74c3c;
            color: white;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
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

        .seller-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .seller-logo {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .seller-name {
            font-size: 0.9rem;
            color: #333;
            font-weight: 500;
        }

        .product-comparison .seller-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .product-comparison .seller-logo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            border: 1px solid #eee;
        }

        .product-comparison .seller-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-comparison .seller-name {
            font-size: 0.9rem;
            color: #333;
            margin: 0;
            font-weight: 500;
        }

        /* Mobile Responsive Styles */
        @media screen and (max-width: 1024px) {
            .all-products-container {
                flex-direction: column;
            }

            .filter-sidebar {
                width: 100%;
                margin-bottom: 20px;
                margin-top: 40px;
            }

            .product-grid {
                width: 100%;
            }

            .comparison-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .comparison-view {
                width: 90%;
                max-height: 80vh;
            }

            .product-comparison {
                min-width: unset;
            }
        }

        @media screen and (max-width: 768px) {
            .container {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                padding: 10px;
            }

            .product {
                padding-bottom: 35px;
            }

            .product img {
                height: 180px;
            }

            .product h3 {
                font-size: 0.9rem;
                margin: 8px 12px 4px;
            }

            .product-info {
                margin: 4px 12px 8px;
            }

            .product .price {
                font-size: 1rem;
            }

            .product .total-sales {
                font-size: 0.75rem;
            }

            .sticky-compare {
                width: 90%;
                padding: 10px 15px;
            }

            .compare-status {
                font-size: 12px;
            }

            .compare-btn, .clear-compare {
                padding: 5px 12px;
                font-size: 12px;
            }
        }

        @media screen and (max-width: 480px) {
            .container {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .product {
                padding-bottom: 30px;
            }

            .product img {
                height: 200px;
            }

            .filter-card {
                padding: 15px;
            }

            .filter-group {
                margin-bottom: 15px;
            }

            .scent-checkboxes {
                grid-template-columns: 1fr;
            }

            .price-inputs {
                flex-direction: column;
                gap: 10px;
            }

            .price-input {
                width: 100%;
            }

            .comparison-view {
                width: 95%;
                padding: 1rem;
            }

            .product-comparison {
                padding: 0.8rem;
            }

            .product-comparison img {
                height: 200px;
            }

            .product-comparison h3 {
                font-size: 1rem;
            }

            .product-comparison .specs {
                padding: 0.8rem;
            }

            .product-comparison .description {
                padding: 0.8rem;
            }

            .product-comparison .view-details-btn {
                padding: 0.7rem 1.2rem;
                font-size: 0.9rem;
            }
        }

        /* Search Results Grid */
        .search-results {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        /* No Results Message */
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #555;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            width: 100%; /* Ensure it takes full width of its container */
        }

        .no-results i.fas.fa-search {
            font-size: 60px;
            color: #e74c3c;
            margin-bottom: 20px;
        }

        .no-results h3 {
            font-size: 1.5em;
            color: #333;
            margin: 0 0 10px;
        }

        .no-results p {
            font-size: 1em;
            color: #777;
            margin: 0 0 20px;
        }

        .no-results .search-again {
            display: inline-block;
            padding: 10px 20px;
            background-color: #000;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .no-results .search-again:hover {
            background-color: #333;
        }
    </style>
</head>

<body>
    <main class="main-content">
        <div class="all-products-container">
            <!-- Filter Sidebar -->
            <div class="filter-sidebar">
                <form method="get" action="all_product.php">
                    <div class="filter-card">
                        <div class="filter-title">
                            <span>Filter Results</span>
                            <i class="fas fa-sliders-h"></i>
                        </div>

                        <!-- Gender Filter -->
                        <div class="filter-group">
                            <label class="filter-label">Gender</label>
                            <div class="radio-group">
                                <div class="radio-item">
                                    <input type="radio" name="gender" id="gender-all" value="" class="radio-input" <?php echo empty($gender_filter) ? 'checked' : ''; ?>>
                                    <label for="gender-all" class="radio-label">All</label>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="gender" id="gender-male" value="male" class="radio-input" <?php echo $gender_filter === 'male' ? 'checked' : ''; ?>>
                                    <label for="gender-male" class="radio-label">Men's</label>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="gender" id="gender-female" value="female" class="radio-input" <?php echo $gender_filter === 'female' ? 'checked' : ''; ?>>
                                    <label for="gender-female" class="radio-label">Women's</label>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="gender" id="gender-unisex" value="unisex" class="radio-input" <?php echo $gender_filter === 'unisex' ? 'checked' : ''; ?>>
                                    <label for="gender-unisex" class="radio-label">Unisex</label>
                                </div>
                            </div>
                        </div>

                        <!-- Concentration Filter -->
                        <div class="filter-group">
                            <label class="filter-label">Concentration</label>
                            <select name="concentration" class="filter-input">
                                <option value="">All Concentrations</option>
                                <?php foreach ($concentrations as $concentration): ?>
                                    <option value="<?php echo $concentration['ConcentrationID']; ?>" <?php echo $concentration_filter == $concentration['ConcentrationID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($concentration['ConcentrationName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Scent Type Filter -->
                        <div class="filter-group">
                            <label class="filter-label">Scent Type (Select up to 3)</label>
                            <div class="scent-checkboxes">
                                <?php
                                // Fetch all scents
                                $scent_query = "SELECT ScentID, ScentName FROM scents ORDER BY ScentName";
                                $scent_result = $conn->query($scent_query);
                                if ($scent_result->num_rows > 0) {
                                    while ($scent = $scent_result->fetch_assoc()) {
                                        $is_checked = in_array($scent['ScentID'], $scent_types);
                                        echo '<div class="scent-checkbox-item">';
                                        echo '<input type="checkbox" name="scent_types[]" id="scent-' . $scent['ScentID'] . '" value="' . $scent['ScentID'] . '" class="scent-checkbox"' . ($is_checked ? ' checked' : '') . '>';
                                        echo '<label for="scent-' . $scent['ScentID'] . '">' . htmlspecialchars($scent['ScentName']) . '</label>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Price Range Filter -->
                        <div class="filter-group">
                            <label class="filter-label">Price Range (RM)</label>
                            <div class="price-inputs">
                                <input type="number" name="min_price" min="<?php echo $db_min_price; ?>" max="<?php echo $db_max_price; ?>" value="<?php echo $min_price; ?>" class="price-input" placeholder="Min">
                                <span>to</span>
                                <input type="number" name="max_price" min="<?php echo $db_min_price; ?>" max="<?php echo $db_max_price; ?>" value="<?php echo $max_price; ?>" class="price-input" placeholder="Max">
                            </div>
                        </div>

                        <!-- Sort By Option -->
                        <div class="filter-group">
                            <label class="filter-label">Sort By</label>
                            <select name="sort" class="filter-input">
                                <option value="relevance" <?php echo $sort_by === 'relevance' ? 'selected' : ''; ?>>Relevance</option>
                                <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="promotion" <?php echo $sort_by === 'promotion' ? 'selected' : ''; ?>>Promotion Products</option>
                                <option value="bestselling" <?php echo $sort_by === 'bestselling' ? 'selected' : ''; ?>>Best Selling</option>
                                <option value="top_rated" <?php echo $sort_by === 'top_rated' ? 'selected' : ''; ?>>Top Rated</option>
                                <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest</option>
                            </select>
                        </div>

                        <!-- Filter Actions -->
                        <div class="filter-actions">
                            <button type="submit" class="filter-button apply-filter">Apply Filters</button>
                            <a href="all_product.php" class="filter-button clear-filter">Clear</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Product Grid -->
            <div class="product-grid">
                <div class="search-header">
                    <div class="search-result-count">
                        <?php if (!empty($gender_filter)): ?>
                            <?php
                                $gender_display = '';
                                if ($gender_filter === 'male') $gender_display = "Men's";
                                elseif ($gender_filter === 'female') $gender_display = "Women's";
                                elseif ($gender_filter === 'unisex') $gender_display = "Unisex";
                            ?>
                            <h2 style="margin-top: 0; margin-bottom: 5px;"><?php echo htmlspecialchars($gender_display); ?> Products</h2>
                        <?php else: ?>
                            <h2 style="margin-top: 0; margin-bottom: 5px;">All Products</h2>
                        <?php endif; ?>
                        <p style="margin-top: 0; color: #666;"><?php echo $total_results; ?> products found</p>
                    </div>
                </div>

                <?php
                if ($result->num_rows > 0): ?>
                    <div class="container">
                        <?php
                        while ($row = $result->fetch_assoc()) {
                            echo '<div class="product">';
                            // Add product link
                            echo '<a href="product_details.php?product_id=' . $row['product_id'] . '" class="product-link"></a>';
                            
                            // Show promotion badge if there's an active promotion
                            if (isset($active_promotions[$row['product_id']])) {
                                $promo = $active_promotions[$row['product_id']];
                                $is_limited = (strtotime($promo['end_date']) - time() < 86400);
                                $badge_class = $is_limited ? 'promotion-badge limited' : 'promotion-badge';
                                
                                if ($promo['discount_type'] == 'percentage') {
                                    $discount_text = $promo['discount_value'] . '% OFF';
                                } else {
                                    $discount_text = 'RM' . number_format($promo['discount_value'], 2) . ' OFF';
                                }
                                
                                echo '<div class="' . $badge_class . '">' . htmlspecialchars($discount_text) . '</div>';
                            }
                            
                            echo '<img src="uploads/' . htmlspecialchars($row['product_image']) . '" alt="' . htmlspecialchars($row['product_name']) . '">';
                            echo '<h3>' . htmlspecialchars($row['product_name']) . '</h3>';
                            
                            echo '<div class="product-info">';
                            echo '<div class="price-container">';
                            
                            if (isset($active_promotions[$row['product_id']])) {
                                $promo = $active_promotions[$row['product_id']];
                                $original_price = $row['price'];
                                
                                // Calculate discounted price
                                if ($promo['discount_type'] == 'percentage') {
                                    $discounted_price = $original_price * (1 - ($promo['discount_value'] / 100));
                                } else {
                                    $discounted_price = $original_price - $promo['discount_value'];
                                }
                                
                                // Display original and discounted prices
                                echo '<p class="price original-price">RM' . number_format($original_price, 2) . '</p>';
                                echo '<p class="price discounted-price">RM' . number_format($discounted_price, 2) . '</p>';
                            } else {
                                echo '<p class="price">RM' . number_format($row['price'], 2) . '</p>';
                            }
                            
                            echo '</div>';
                            echo '<p class="total-sales">Sold ' . htmlspecialchars($row['total_sales']) . '</p>';
                            echo '</div>';
                            
                            // Add compare container
                            echo '<div class="compare-container" onclick="event.stopPropagation();">';
                            echo '<input type="checkbox" id="compare_all_' . $row['product_id'] . '" class="compare-checkbox" data-product-id="' . $row['product_id'] . '">';
                            echo '<label for="compare_all_' . $row['product_id'] . '" class="compare-label">Compare</label>';
                            echo '</div>';
                            
                            echo '</div>';
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>No products found</h3>
                        <p>Try adjusting your filters or browse our categories.</p>
                        <a href="all_product.php" class="search-again">Browse All Products</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <style>
        /* Add these styles to your existing CSS */
        .scent-checkboxes {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            max-height: 200px;
            overflow-y: auto;
            padding: 5px;
        }

        .scent-checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .scent-checkbox-item input[type="checkbox"] {
            margin: 0;
        }

        .scent-checkbox-item label {
            font-size: 0.9rem;
            color: #333;
            cursor: pointer;
        }

        .scent-checkboxes::-webkit-scrollbar {
            width: 4px;
        }

        .scent-checkboxes::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .scent-checkboxes::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .scent-checkboxes::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Add compare container styles */
        .compare-container {
            display: flex;
            align-items: center;
            padding: 5px;
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(248, 249, 250, 0.9);
            border-top: 1px solid #eee;
            z-index: 2;
        }

        .compare-checkbox {
            margin-right: 8px;
            cursor: pointer;
            width: 16px;
            height: 16px;
        }

        .compare-label {
            font-size: 14px;
            color: #333;
            cursor: pointer;
            user-select: none;
        }

        .compare-checkbox:checked + .compare-label {
            color: #3498db;
            font-weight: 500;
        }

        /* Sticky Compare Container */
        .sticky-compare {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 12px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
            justify-content: space-between;
            align-items: center;
            border-radius: 30px;
            min-width: 300px;
            max-width: 400px;
            transition: all 0.3s ease;
        }

        .sticky-compare.visible {
            display: flex;
        }

        .compare-status {
            font-size: 14px;
            color: #333;
            margin-right: 15px;
        }

        .compare-actions {
            display: flex;
            gap: 8px;
        }

        .compare-btn {
            padding: 6px 15px;
            background: rgb(0, 0, 0);
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 13px;
        }

        .compare-btn:hover {
            background: rgb(115, 115, 115);
        }

        .compare-btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }

        .clear-compare {
            padding: 6px 15px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 13px;
        }

        .clear-compare:hover {
            background: #c0392b;
        }

        /* Comparison View */
        .comparison-view {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) scale(0.8);
            background: white;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1001;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            max-height: 65vh;
            overflow-y: auto;
            width: 75%;
            max-width: 1200px;
        }

        .comparison-view.active {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) scale(1);
            bottom: 15%;
            margin-bottom: 20px;
        }

        .comparison-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(350px, 1fr));
            gap: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0.8rem;
        }

        .product-comparison {
            padding: 1rem;
            border: 1px solid #eee;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            min-width: 350px;
            display: flex;
            flex-direction: column;
        }

        .product-comparison img {
            width: 100%;
            max-width: 250px;
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .product-comparison h3 {
            font-size: 1.1rem;
            margin-bottom: 0.8rem;
            color: #333;
            line-height: 1.3;
        }

        .product-comparison .price {
            font-size: 1.1rem;
            color: #e44d26;
            margin-bottom: 0.8rem;
            font-weight: 600;
        }

        .product-comparison .rating {
            color: #ffc107;
            margin-bottom: 0.8rem;
            font-size: 1rem;
        }

        .product-comparison .specs {
            margin: 1rem 0;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 12px;
        }

        .product-comparison .specs p {
            margin: 0.4rem 0;
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .product-comparison .description {
            margin-top: 1rem;
            line-height: 1.5;
            color: #666;
            font-size: 0.9rem;
            padding: 0.8rem;
            background: #f9f9f9;
            border-radius: 12px;
            flex-grow: 1;
        }

        .product-comparison .view-details-btn {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.8rem 1.5rem;
            background-color: rgb(0, 0, 0);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: background-color 0.3s ease;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }

        .product-comparison .view-details-btn:hover {
            background-color: rgb(255, 102, 102);
        }

        .close-comparison {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: #666;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.3s;
        }

        .close-comparison:hover {
            background-color: #f0f0f0;
        }

        .comparison-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(3px);
        }

        .comparison-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Hide sticky compare when comparison is active */
        .comparison-view.active ~ .sticky-compare {
            opacity: 0;
            visibility: hidden;
            transform: translateX(-50%) translateY(20px);
        }

        .product {
            position: relative;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            padding-bottom: 40px;
        }

        .product:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .product img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product:hover img {
            transform: scale(1.05);
        }

        .product h3 {
            font-size: 1rem;
            margin: 10px 15px 5px;
            color: #333;
            font-weight: 600;
            line-height: 1.3;
            height: 2.6em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 5px 15px 10px;
            gap: 20px;
        }

        .price-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            flex: 1;
            position: relative;
            z-index: 3;
        }

        .product-link {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 40px;
            z-index: 1;
        }

        .product .price {
            font-size: 1.1rem;
            font-weight: 700;
            color: #e74c3c;
            margin: 0;
        }

        .product .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 0.85rem;
            margin: 0;
        }

        .product .discounted-price {
            color: #e74c3c;
            font-weight: 700;
            margin: 0;
        }

        .product .total-sales {
            font-size: 0.8rem;
            color: #666;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .promotion-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e74c3c;
            color: white;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
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

        .seller-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .seller-logo {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .seller-name {
            font-size: 0.9rem;
            color: #333;
            font-weight: 500;
        }

        .product-comparison .seller-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .product-comparison .seller-logo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            border: 1px solid #eee;
        }

        .product-comparison .seller-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-comparison .seller-name {
            font-size: 0.9rem;
            color: #333;
            margin: 0;
            font-weight: 500;
        }

        /* Mobile Responsive Styles */
        @media screen and (max-width: 1024px) {
            .all-products-container {
                flex-direction: column;
            }

            .filter-sidebar {
                width: 100%;
                margin-bottom: 20px;
            }

            .product-grid {
                width: 100%;
            }

            .comparison-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .comparison-view {
                width: 90%;
                max-height: 80vh;
            }

            .product-comparison {
                min-width: unset;
            }
        }

        @media screen and (max-width: 768px) {
            .container {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                padding: 10px;
            }

            .product {
                padding-bottom: 35px;
            }

            .product img {
                height: 180px;
            }

            .product h3 {
                font-size: 0.9rem;
                margin: 8px 12px 4px;
            }

            .product-info {
                margin: 4px 12px 8px;
            }

            .product .price {
                font-size: 1rem;
            }

            .product .total-sales {
                font-size: 0.75rem;
            }

            .sticky-compare {
                width: 90%;
                padding: 10px 15px;
            }

            .compare-status {
                font-size: 12px;
            }

            .compare-btn, .clear-compare {
                padding: 5px 12px;
                font-size: 12px;
            }
        }

        @media screen and (max-width: 480px) {
            .container {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .product {
                padding-bottom: 30px;
            }

            .product img {
                height: 200px;
            }

            .filter-card {
                padding: 15px;
            }

            .filter-group {
                margin-bottom: 15px;
            }

            .scent-checkboxes {
                grid-template-columns: 1fr;
            }

            .price-inputs {
                flex-direction: column;
                gap: 10px;
            }

            .price-input {
                width: 100%;
            }

            .comparison-view {
                width: 95%;
                padding: 1rem;
            }

            .product-comparison {
                padding: 0.8rem;
            }

            .product-comparison img {
                height: 200px;
            }

            .product-comparison h3 {
                font-size: 1rem;
            }

            .product-comparison .specs {
                padding: 0.8rem;
            }

            .product-comparison .description {
                padding: 0.8rem;
            }

            .product-comparison .view-details-btn {
                padding: 0.7rem 1.2rem;
                font-size: 0.9rem;
            }
        }
    </style>

    <!-- Add this right after the body tag -->
    <div class="sticky-compare" id="stickyCompare">
        <div class="compare-status">
            <span id="compareCount">0/2</span> products selected for comparison
        </div>
        <div class="compare-actions">
            <button class="compare-btn" id="compareBtn" disabled>Compare Products</button>
            <button class="clear-compare" id="clearCompare">Clear Selection</button>
        </div>
    </div>

    <!-- Add this after the sticky compare container -->
    <div class="comparison-overlay" id="comparisonOverlay"></div>
    <div class="comparison-view" id="comparisonView">
        <div class="comparison-header">
            <h2 class="comparison-title">Product Comparison</h2>
            <button class="close-comparison" id="closeComparison">&times;</button>
        </div>
        <div class="comparison-grid" id="comparisonGrid">
            <!-- Products will be inserted here -->
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const compareCheckboxes = document.querySelectorAll('.compare-checkbox');
        const stickyCompare = document.getElementById('stickyCompare');
        const compareCount = document.getElementById('compareCount');
        const compareBtn = document.getElementById('compareBtn');
        const clearCompare = document.getElementById('clearCompare');
        const comparisonView = document.getElementById('comparisonView');
        const comparisonOverlay = document.getElementById('comparisonOverlay');
        const closeComparison = document.getElementById('closeComparison');
        const comparisonGrid = document.getElementById('comparisonGrid');
        const topNav = document.querySelector('.top-nav'); // Get the top navigation element
        const topBar = document.querySelector('.top-bar'); // Get the top bar element
        let selectedProducts = new Set();

        // --- Scent Selection Logic ---
        const scentCheckboxes = document.querySelectorAll('.scent-checkbox');
        const maxScentSelections = 3;
        let selectedScentIds = []; // Use an array to maintain order

        // Initialize selectedScentIds based on currently checked boxes (e.g., from previous form submission)
        scentCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                selectedScentIds.push(checkbox.value);
            }
        });

        // Function to update the disabled state of checkboxes
        function updateScentCheckboxState() {
            scentCheckboxes.forEach(checkbox => {
                // Disable unchecked checkboxes if the limit is reached
                if (!checkbox.checked && selectedScentIds.length >= maxScentSelections) {
                    checkbox.disabled = true;
                } else {
                    // Otherwise, enable the checkbox
                    checkbox.disabled = false;
                }
            });
        }

        // Initial update of checkbox state
        updateScentCheckboxState();

        scentCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const scentId = this.value;

                if (this.checked) {
                    // Check if we are over the limit AFTER checking this box
                    if (selectedScentIds.length >= maxScentSelections) {
                        // Identify the ID of the oldest selected scent
                        const oldestScentId = selectedScentIds.shift(); // Remove the first element (oldest)

                        // Find the corresponding checkbox and uncheck it
                        const oldestCheckbox = document.querySelector(`.scent-checkbox[value="${oldestScentId}"]`);
                        if (oldestCheckbox) {
                            oldestCheckbox.checked = false;
                        }
                    }
                    // Add the newly selected scent ID
                    selectedScentIds.push(scentId);

                } else {
                    // Remove the unchecked scent ID from the array
                    selectedScentIds = selectedScentIds.filter(id => id !== scentId);
                }

                // Update the disabled state of other checkboxes
                updateScentCheckboxState();

                // Note: The form is submitted by the 'Apply Filters' button,
                // so no need to explicitly submit here.
            });
        });
        // --- End Scent Selection Logic ---

        function updateCompareStatus() {
            const count = selectedProducts.size;
            compareCount.textContent = `${count}/2`;
            
            if (count > 0) {
                stickyCompare.classList.add('visible');
            } else {
                stickyCompare.classList.remove('visible');
            }

            compareBtn.disabled = count !== 2;
        }

        async function showComparison(products) {
            try {
                comparisonGrid.innerHTML = '';
                
                products.forEach(product => {
                    // Calculate average rating and format it
                    const avgRating = parseFloat(product.average_rating) || 0;
                    const roundedRating = Math.round(avgRating);
                    const totalReviews = parseInt(product.total_ratings) || 0;
                    
                    const productHtml = `
                        <div class="product-comparison">
                            <img src="uploads/${product.product_image}" alt="${product.product_name}">
                            <h3>${product.product_name}</h3>
                            <div class="price">RM${parseFloat(product.price).toFixed(2)}</div>
                            <div class="rating">
                                <span class="stars">
                                    ${'★'.repeat(roundedRating)}${'☆'.repeat(5-roundedRating)}
                                </span>
                                <span class="review-count">
                                    (${totalReviews} ${totalReviews === 1 ? 'review' : 'reviews'})
                                </span>
                            </div>
                            <div class="seller-info">
                                <div class="seller-logo">
                                    <img src="uploads/${product.seller_logo || 'default-logo.png'}" alt="${product.seller_name}">
                                </div>
                                <p class="seller-name">${product.seller_name}</p>
                            </div>
                            <div class="specs">
                                <p><strong>Gender:</strong> ${product.gender}</p>
                                <p><strong>Concentration:</strong> ${product.concentration} (${product.ConcentrationPercentage}%)</p>
                                <p><strong>Longevity:</strong> ${product.Longevity}</p>
                                <p><strong>Scents:</strong> ${product.scents}</p>
                                <p><strong>Total Sales:</strong> ${product.total_sold}</p>
                            </div>
                            <div class="description">
                                <h4>Description</h4>
                                <p>${product.product_description || 'No description available.'}</p>
                            </div>
                            <a href="product_details.php?product_id=${product.product_id}" class="view-details-btn">View Product</a>
                        </div>
                    `;
                    comparisonGrid.innerHTML += productHtml;
                });
                
                // Show the comparison view with animation
                comparisonView.style.display = 'block';
                comparisonOverlay.style.display = 'block';

                // Lower the z-index of the top navigation and top bar
                if (topNav) {
                    topNav.style.zIndex = '998';
                }
                if (topBar) {
                    topBar.style.zIndex = '998';
                }
                
                // Force a reflow
                comparisonView.offsetHeight;
                
                // Add active class for animation
                comparisonView.classList.add('active');
                comparisonOverlay.classList.add('active');
                
                // Prevent body scrolling
                document.body.style.overflow = 'hidden';
            } catch (error) {
                console.error('Error showing comparison:', error);
                alert('Error displaying product comparison. Please try again.');
            }
        }

        compareCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const productId = this.dataset.productId;
                
                if (this.checked) {
                    if (selectedProducts.size < 2) {
                        selectedProducts.add(productId);
                         // Uncheck other checkboxes for the same product
                        document.querySelectorAll(`.compare-checkbox[data-product-id="${productId}"]`).forEach(otherCheckbox => {
                            if (otherCheckbox !== this) {
                                otherCheckbox.checked = false;
                            }
                        });
                    } else {
                        this.checked = false;
                        alert('You can only compare 2 products at a time');
                        return;
                    }
                } else {
                    selectedProducts.delete(productId);
                }
                
                updateCompareStatus();
            });
        });

        clearCompare.addEventListener('click', function() {
            selectedProducts.clear();
            compareCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateCompareStatus();
            
            // Close comparison view if it's open
            if (comparisonView.classList.contains('active')) {
                comparisonView.classList.remove('active');
                comparisonOverlay.classList.remove('active');
                
                // Wait for animation to complete before hiding
                setTimeout(() => {
                    comparisonView.style.display = 'none';
                    comparisonOverlay.style.display = 'none';
                    document.body.style.overflow = '';
                     // Reset the z-index of the top navigation and top bar
                    if (topNav) {
                        topNav.style.zIndex = ''; // Remove inline style to reset
                    }
                    if (topBar) {
                        topBar.style.zIndex = ''; // Remove inline style to reset
                    }
                }, 300);
            }
        });

        compareBtn.addEventListener('click', async function() {
            if (selectedProducts.size === 2) {
                const productIds = Array.from(selectedProducts);
                try {
                    const response = await fetch(`get_products.php?ids=${productIds.join(',')}`);
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    const products = await response.json();
                    if (products && products.length === 2) {
                        await showComparison(products);
                    } else {
                        throw new Error('Invalid product data received');
                    }
                } catch (error) {
                    console.error('Error fetching products:', error);
                    alert('Error loading product details. Please try again.');
                }
            }
        });

        closeComparison.addEventListener('click', function() {
            comparisonView.classList.remove('active');
            comparisonOverlay.classList.remove('active');
            
            // Wait for animation to complete before hiding
            setTimeout(() => {
                comparisonView.style.display = 'none';
                comparisonOverlay.style.display = 'none';
                document.body.style.overflow = '';
                 // Reset the z-index of the top navigation and top bar
                if (topNav) {
                    topNav.style.zIndex = ''; // Remove inline style to reset
                }
                if (topBar) {
                    topBar.style.zIndex = ''; // Remove inline style to reset
                }
            }, 300);
        });

        comparisonOverlay.addEventListener('click', function() {
            comparisonView.classList.remove('active');
            comparisonOverlay.classList.remove('active');
            
            // Wait for animation to complete before hiding
            setTimeout(() => {
                comparisonView.style.display = 'none';
                comparisonOverlay.style.display = 'none';
                document.body.style.overflow = '';
                 // Reset the z-index of the top navigation and top bar
                if (topNav) {
                    topNav.style.zIndex = ''; // Remove inline style to reset
                }
                if (topBar) {
                    topBar.style.zIndex = ''; // Remove inline style to reset
                }
            }, 300);
        });
    });
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>
