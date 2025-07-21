<?php
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");
include 'top-navigation.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get filters from URL parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$discount = isset($_GET['discount']) ? $_GET['discount'] : '';
$seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Function to get all active promotions with filters
function getFilteredPromotions($conn, $category = '', $discount = '', $seller_id = '', $sort = 'newest')
{
    // Start building query
    $query = "
        SELECT 
            p.promotion_id,
            p.promo_name,
            p.discount_type,
            p.discount_value,
            p.start_date,
            p.end_date,
            pr.product_id,
            pr.product_name,
            pr.product_image,
            pr.price,
            pr.ConcentrationID,
            pr.gender,
            c.ConcentrationName,
            s.Name as seller_name,
            s.CompanyName as company_name,
            s.SellerID,
            GROUP_CONCAT(DISTINCT sc.ScentName SEPARATOR ', ') as scent_categories
        FROM 
            promotions p
        JOIN 
            products pr ON p.product_id = pr.product_id
        JOIN 
            seller s ON pr.seller_id = s.SellerID
        LEFT JOIN
            concentration c ON pr.ConcentrationID = c.ConcentrationID
        LEFT JOIN
            product_scent ps ON pr.product_id = ps.product_id
        LEFT JOIN
            scents sc ON ps.scent_id = sc.ScentID
        WHERE 
            p.start_date <= NOW() 
            AND p.end_date >= NOW()";
    
    $params = [];
    $types = "";
    
    // Add filters
    if (!empty($category)) {
        // Filter by scent category, gender, or concentration
        if ($category == 'male' || $category == 'female' || $category == 'unisex') {
            $query .= " AND (pr.gender = ? OR pr.gender = 'UniSex')";
            $params[] = $category;
            $types .= "s";
        } elseif (is_numeric($category)) {
            // Concentration ID
            $query .= " AND pr.ConcentrationID = ?";
            $params[] = $category;
            $types .= "i";
        } else {
            // Scent category
            $query .= " AND sc.ScentName LIKE ?";
            $params[] = "%$category%";
            $types .= "s";
        }
    }
    
    if (!empty($discount)) {
        if ($discount == 'percent') {
            $query .= " AND p.discount_type = 'percentage'";
        } elseif ($discount == 'fixed') {
            $query .= " AND p.discount_type = 'fixed'";
        } elseif ($discount == 'high') {
            // High discount - either high percentage or high fixed value
            $query .= " AND ((p.discount_type = 'percentage' AND p.discount_value >= 20) OR 
                        (p.discount_type = 'fixed' AND p.discount_value >= 50))";
        }
    }
    
    if (!empty($seller_id)) {
        $query .= " AND s.SellerID = ?";
        $params[] = $seller_id;
        $types .= "i";
    }
    
    // Group by to avoid duplicates due to multiple scents
    $query .= " GROUP BY p.promotion_id";
    
    // Add sorting
    switch ($sort) {
        case 'discount_high':
            $query .= " ORDER BY 
                CASE 
                    WHEN p.discount_type = 'percentage' THEN p.discount_value 
                    ELSE (p.discount_value / pr.price) * 100 
                END DESC";
            break;
        case 'price_low':
            $query .= " ORDER BY 
                CASE 
                    WHEN p.discount_type = 'percentage' THEN pr.price * (1 - (p.discount_value/100))
                    ELSE pr.price - p.discount_value
                END ASC";
            break;
        case 'price_high':
            $query .= " ORDER BY 
                CASE 
                    WHEN p.discount_type = 'percentage' THEN pr.price * (1 - (p.discount_value/100))
                    ELSE pr.price - p.discount_value
                END DESC";
            break;
        case 'end_soon':
            $query .= " ORDER BY p.end_date ASC";
            break;
        case 'newest':
        default:
            $query .= " ORDER BY p.created_at DESC";
            break;
    }

    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $promotions = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Calculate discounted price
            $original_price = $row['price'];
            if ($row['discount_type'] == 'percentage') {
                $discounted_price = $original_price * (1 - ($row['discount_value'] / 100));
                $discount_text = $row['discount_value'] . '% OFF';
                $discount_amount = ($row['discount_value'] / 100) * $original_price;
            } else {
                $discounted_price = $original_price - $row['discount_value'];
                $discount_text = 'RM' . number_format($row['discount_value'], 2) . ' OFF';
                $discount_amount = $row['discount_value'];
            }
            
            $row['discounted_price'] = $discounted_price;
            $row['discount_text'] = $discount_text;
            $row['discount_amount'] = $discount_amount;
            $row['savings_percent'] = round(($discount_amount / $original_price) * 100);
            
            // Calculate time remaining
            $end_date = new DateTime($row['end_date']);
            $now = new DateTime();
            $interval = $now->diff($end_date);
            $row['days_remaining'] = $interval->days;
            $row['hours_remaining'] = $interval->h;
            $row['total_hours_remaining'] = ($interval->days * 24) + $interval->h;
            
            $promotions[] = $row;
        }
    }
    
    $stmt->close();
    return $promotions;
}

// Get sellers with active promotions for the filter dropdown
function getSellersWithPromotions($conn) {
    $query = "
        SELECT DISTINCT
            s.SellerID,
            s.Name,
            s.CompanyName,
            COUNT(p.promotion_id) as promotion_count
        FROM 
            seller s
        JOIN 
            products pr ON s.SellerID = pr.seller_id
        JOIN 
            promotions p ON pr.product_id = p.product_id
        WHERE 
            p.start_date <= NOW() 
            AND p.end_date >= NOW()
        GROUP BY 
            s.SellerID
        ORDER BY 
            s.Name ASC";
            
    $result = $conn->query($query);
    $sellers = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sellers[] = $row;
        }
    }
    
    return $sellers;
}

// Get scent categories for the filter dropdown
function getScentCategories($conn) {
    $query = "
        SELECT DISTINCT
            s.ScentName
        FROM 
            scents s
        JOIN 
            product_scent ps ON s.ScentID = ps.scent_id
        JOIN 
            products p ON ps.product_id = p.product_id
        JOIN 
            promotions pr ON p.product_id = pr.product_id
        WHERE 
            pr.start_date <= NOW() 
            AND pr.end_date >= NOW()
        ORDER BY 
            s.ScentName ASC";
            
    $result = $conn->query($query);
    $scents = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $scents[] = $row['ScentName'];
        }
    }
    
    return $scents;
}

// Get concentration types for the filter dropdown
function getConcentrationTypes($conn) {
    $query = "
        SELECT DISTINCT
            c.ConcentrationID,
            c.ConcentrationName
        FROM 
            concentration c
        JOIN 
            products p ON c.ConcentrationID = p.ConcentrationID
        JOIN 
            promotions pr ON p.product_id = pr.product_id
        WHERE 
            pr.start_date <= NOW() 
            AND pr.end_date >= NOW()
        ORDER BY 
            c.ConcentrationName ASC";
            
    $result = $conn->query($query);
    $concentrations = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $concentrations[] = $row;
        }
    }
    
    return $concentrations;
}

// Get the filtered promotions
$promotions = getFilteredPromotions($conn, $category, $discount, $seller_id, $sort);

// Get data for filter dropdowns
$sellers = getSellersWithPromotions($conn);
$scents = getScentCategories($conn);
$concentrations = getConcentrationTypes($conn);

// Count total active promotions
$totalPromotions = count($promotions);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Offers - ScentMatch</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/top-navigation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f9f9f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .page-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .promotion-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 60px 20px;
            background: linear-gradient(135deg, #333 0%, #111 100%);
            border-radius: 12px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            color: white;
        }
        
        .promo-badge {
            position: absolute;
            top: -10px;
            right: -30px;
            background-color: #e74c3c;
            color: white;
            padding: 30px 60px;
            transform: rotate(45deg) translateX(30px);
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            letter-spacing: 2px;
            font-size: 1.2rem;
        }
        
        .promotion-header h1 {
            font-size: 3.5rem;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            background: linear-gradient(to right, #fff, #ccc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .promotion-header p {
            max-width: 700px;
            margin: 0 auto;
            font-size: 1.2rem;
            line-height: 1.6;
            opacity: 0.8;
        }
        
        .highlight-count {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 25px;
            background-color: rgba(255,255,255,0.1);
            border-radius: 50px;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .highlight-count span {
            color: #e74c3c;
            font-size: 1.3rem;
            font-weight: 700;
        }
        
        /* Content layout with sidebar */
        .main-content {
            display: flex;
            gap: 30px;
            margin-bottom: 50px;
        }
        
        .filter-sidebar {
            width: 260px;
            flex-shrink: 0;
        }
        
        .offers-content {
            flex-grow: 1;
        }
        
        /* Filter styles */
        .filter-section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .filter-title {
            font-size: 1.1rem;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            font-weight: 600;
        }
        
        .filter-title i {
            margin-right: 10px;
            color: #e74c3c;
        }
        
        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .filter-group {
            width: 100%;
        }
        
        .filter-label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: #555;
            font-weight: 500;
        }
        
        .filter-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background-color: #f8f9fa;
            font-size: 0.95rem;
            transition: all 0.3s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23555' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: calc(100% - 15px) center;
            padding-right: 35px;
        }
        
        .filter-select:focus {
            border-color: #aaa;
            outline: none;
            box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.1);
        }
        
        .filter-button {
            width: 100%;
            padding: 10px 0;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            margin-top: 10px;
        }
        
        .filter-button:hover {
            background-color: #222;
        }
        
        .reset-button {
            width: 100%;
            padding: 10px 0;
            background-color: #f1f1f1;
            color: #555;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 0.95rem;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .reset-button:hover {
            background-color: #e1e1e1;
        }
        
        .promotion-count {
            margin: 0 0 25px;
            color: #555;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .promotion-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .promotion-item {
            position: relative;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .promotion-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .save-tag {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: #e74c3c;
            color: white;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 1;
        }
        
        .time-tag {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 1;
        }
        
        .limited-tag {
            background-color: #e67e22;
        }
        
        .promo-image-container {
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        
        .promo-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .promotion-item:hover .promo-image {
            transform: scale(1.05);
        }
        
        .promo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,0.4) 100%);
        }
        
        .promo-content {
            padding: 15px;
        }
        
        .promo-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 5px;
            color: #333;
        }
        
        .promo-seller {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .promo-name {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .promo-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .promo-meta-item {
            font-size: 0.8rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .promo-pricing {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .price-column {
            display: flex;
            flex-direction: column;
        }
        
        .original-price {
            font-size: 0.85rem;
            color: #999;
            text-decoration: line-through;
        }
        
        .discounted-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: #e74c3c;
        }
        
        .discount-percent {
            font-size: 0.85rem;
            font-weight: 600;
            color: #2ecc71;
            background-color: rgba(46, 204, 113, 0.1);
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .promo-button {
            display: block;
            width: 100%;
            padding: 10px 0;
            background-color: #333;
            color: white;
            text-align: center;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        
        .promo-button:hover {
            background-color: #222;
        }
        
        .no-promotions {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .no-promotions i {
            font-size: 3rem;
            color: #e74c3c;
            margin-bottom: 15px;
            opacity: 0.8;
        }
        
        .no-promotions h2 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .no-promotions p {
            color: #666;
            margin-bottom: 20px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 900px) {
            .main-content {
                flex-direction: column;
            }
            
            .filter-sidebar {
                width: 100%;
                margin-bottom: 30px;
            }
            
            .filter-options {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .filter-group {
                flex: 1 0 calc(50% - 10px);
                min-width: 200px;
            }
        }
        
        @media (max-width: 768px) {
            .promotion-header {
                margin-top: 0;
                padding: 40px 15px;
            }
            
            .promotion-header h1 {
                font-size: 2.5rem;
            }
            
            .filter-options {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .promotion-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
            
            .promo-badge {
                padding: 15px 30px;
                font-size: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .promotion-header h1 {
                font-size: 2rem;
            }
            
            .promotion-header p {
                font-size: 1rem;
            }
            
            .promotion-grid {
                grid-template-columns: 1fr;
            }
            
            .save-tag, .time-tag {
                padding: 5px 10px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'top-navigation.php'; ?>
    
    <div class="page-container">
        <!-- Hero header section -->
        <div class="promotion-header">
            <div class="promo-badge">SALE</div>
            <h1>Special Offers</h1>
            <p>Discover amazing deals and save on premium fragrances from our top sellers. Limited time offers updated regularly.</p>
            
            <div class="highlight-count">
                <span><?php echo $totalPromotions; ?></span> active promotions available
            </div>
        </div>

        <!-- Main content with sidebar -->
        <div class="main-content">
            <!-- Filter Sidebar -->
            <div class="filter-sidebar">
                <div class="filter-section">
                    <h2 class="filter-title"><i class="fas fa-filter"></i>Find Your Perfect Deal</h2>
                    <form method="get" action="special_offers.php">
                        <div class="filter-options">
                            <div class="filter-group">
                                <label class="filter-label">Category</label>
                                <select name="category" class="filter-select">
                                    <option value="">All Categories</option>
                                    <option value="male" <?php echo $category === 'male' ? 'selected' : ''; ?>>Men's Fragrances</option>
                                    <option value="female" <?php echo $category === 'female' ? 'selected' : ''; ?>>Women's Fragrances</option>
                                    <option value="unisex" <?php echo $category === 'unisex' ? 'selected' : ''; ?>>Unisex Fragrances</option>
                                    
                                    <!-- Concentration types -->
                                    <optgroup label="Concentration">
                                        <?php foreach ($concentrations as $concentration): ?>
                                            <option value="<?php echo $concentration['ConcentrationID']; ?>" <?php echo $category == $concentration['ConcentrationID'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($concentration['ConcentrationName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    
                                    <!-- Scent types -->
                                    <optgroup label="Scent Types">
                                        <?php foreach ($scents as $scent): ?>
                                            <option value="<?php echo $scent; ?>" <?php echo $category === $scent ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($scent); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">Discount Type</label>
                                <select name="discount" class="filter-select">
                                    <option value="">All Discounts</option>
                                    <option value="percent" <?php echo $discount === 'percent' ? 'selected' : ''; ?>>Percentage Discount</option>
                                    <option value="fixed" <?php echo $discount === 'fixed' ? 'selected' : ''; ?>>Fixed Amount Off</option>
                                    <option value="high" <?php echo $discount === 'high' ? 'selected' : ''; ?>>High Value Deals</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">Seller</label>
                                <select name="seller_id" class="filter-select">
                                    <option value="">All Sellers</option>
                                    <?php foreach ($sellers as $seller): ?>
                                        <option value="<?php echo $seller['SellerID']; ?>" <?php echo $seller_id == $seller['SellerID'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($seller['Name']); ?> 
                                            (<?php echo $seller['promotion_count']; ?> offers)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">Sort By</label>
                                <select name="sort" class="filter-select">
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                    <option value="discount_high" <?php echo $sort === 'discount_high' ? 'selected' : ''; ?>>Biggest Discount</option>
                                    <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                    <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                    <option value="end_soon" <?php echo $sort === 'end_soon' ? 'selected' : ''; ?>>Ending Soon</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="filter-button">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                            
                            <?php if (!empty($category) || !empty($discount) || !empty($seller_id) || $sort !== 'newest'): ?>
                                <a href="special_offers.php" class="reset-button">
                                    <i class="fas fa-undo"></i> Reset Filters
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Products Content -->
            <div class="offers-content">
                <!-- Results count -->
                <div class="promotion-count">
                    <strong><?php echo $totalPromotions; ?></strong> active promotions found
                </div>
                
                <!-- Promotions display -->
                <?php if (!empty($promotions)): ?>
                    <div class="promotion-grid">
                        <?php foreach ($promotions as $promo): ?>
                            <div class="promotion-item">
                                <div class="save-tag">SAVE <?php echo $promo['savings_percent']; ?>%</div>
                                
                                <?php if ($promo['days_remaining'] < 1): ?>
                                    <div class="time-tag limited-tag">
                                        <i class="fas fa-bolt"></i> 
                                        Ends in <?php echo $promo['hours_remaining']; ?>h
                                    </div>
                                <?php elseif ($promo['days_remaining'] < 3): ?>
                                    <div class="time-tag limited-tag">
                                        <i class="fas fa-clock"></i> 
                                        Ends in <?php echo $promo['days_remaining']; ?> days
                                    </div>
                                <?php else: ?>
                                    <div class="time-tag">
                                        <i class="fas fa-calendar-alt"></i> 
                                        <?php echo $promo['days_remaining']; ?> days left
                                    </div>
                                <?php endif; ?>
                                
                                <div class="promo-image-container">
                                    <img src="uploads/<?php echo htmlspecialchars($promo['product_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($promo['product_name']); ?>"
                                         class="promo-image">
                                    <div class="promo-overlay"></div>
                                </div>
                                     
                                <div class="promo-content">
                                    <h3 class="promo-title"><?php echo htmlspecialchars($promo['product_name']); ?></h3>
                                    <div class="promo-seller">
                                        <i class="fas fa-store"></i> 
                                        <?php echo htmlspecialchars($promo['company_name'] ?: $promo['seller_name']); ?>
                                    </div>
                                    
                                    <p class="promo-name">
                                        <i class="fas fa-tag"></i> 
                                        <?php echo htmlspecialchars($promo['promo_name']); ?>
                                    </p>
                                    
                                    <div class="promo-meta">
                                        <?php if (!empty($promo['gender'])): ?>
                                        <span class="promo-meta-item">
                                            <i class="fas fa-<?php echo strtolower($promo['gender']) === 'female' ? 'venus' : (strtolower($promo['gender']) === 'male' ? 'mars' : 'venus-mars'); ?>"></i>
                                            <?php echo ucfirst(htmlspecialchars($promo['gender'])); ?>
                                        </span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($promo['ConcentrationName'])): ?>
                                        <span class="promo-meta-item">
                                            <i class="fas fa-tint"></i>
                                            <?php echo htmlspecialchars($promo['ConcentrationName']); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="promo-pricing">
                                        <div class="price-column">
                                            <span class="original-price">RM<?php echo number_format($promo['price'], 2); ?></span>
                                            <span class="discounted-price">RM<?php echo number_format($promo['discounted_price'], 2); ?></span>
                                        </div>
                                        <span class="discount-percent">
                                            <?php echo htmlspecialchars($promo['discount_text']); ?>
                                        </span>
                                    </div>
                                    
                                    <a href="product_details.php?product_id=<?php echo $promo['product_id']; ?>" class="promo-button">
                                        <i class="fas fa-shopping-cart"></i> Shop Now
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-promotions">
                        <i class="fas fa-search"></i>
                        <h2>No promotions found</h2>
                        <p>We couldn't find any active promotions matching your criteria. Try adjusting your filters or check back later for new offers.</p>
                        <?php if (!empty($category) || !empty($discount) || !empty($seller_id)): ?>
                            <a href="special_offers.php" class="filter-button" style="display: inline-block; margin-top: 20px; max-width: 200px;">
                                <i class="fas fa-sync"></i> View All Offers
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2024 ScentMatch. All rights reserved.</p>
            <ul class="footer-links">
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms of Service</a></li>
                <li><a href="#">Contact Us</a></li>
            </ul>
        </div>
    </footer>
</body>
</html> 