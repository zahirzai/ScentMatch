<?php
// Start session and check login status before any output
session_start();

// Redirect non-logged-in users to home.php
if (!isset($_SESSION['customerID'])) {
    header("Location: home.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "scentmatch3");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Feedback Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $errors = [];
    $success = '';
    $customerID = $_SESSION['customerID'] ?? null;
    
    // Get and sanitize form data
    $feedback_type = htmlspecialchars(trim($_POST['feedback_type'] ?? ''));
    $subject_input = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $rating = (int)($_POST['rating'] ?? 0);
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    // Combine feedback type and subject
    $subject = !empty($feedback_type) ? $feedback_type . ' - ' . $subject_input : $subject_input;

    // Validation
    if (empty($message)) {
        $errors[] = "Feedback message is required.";
    }

    if ($rating < 1 || $rating > 5) {
        $errors[] = "Please select a valid rating (1-5).";
    }

    if (empty($errors)) {
        // Prepare SQL statement with all fields
        $stmt = $conn->prepare("
            INSERT INTO feedback (customer_id, subject, message, rating, status, feedback_source)
            VALUES (?, ?, ?, ?, 'new', 'customer')
        ");
        
        // Bind parameters
        $stmt->bind_param("issi", $customerID, $subject, $message, $rating);

        // Execute statement
        if ($stmt->execute()) {
            $_SESSION['feedback_status'] = 'success';
            $_SESSION['feedback_message'] = 'Thank you for your feedback!';
        } else {
            $_SESSION['feedback_status'] = 'error';
            $_SESSION['feedback_message'] = 'Error submitting feedback. Please try again later.';
        }

        $stmt->close();
    } else {
        $_SESSION['feedback_status'] = 'error';
        $_SESSION['feedback_message'] = implode('<br>', $errors);
    }

    // Redirect back to the page to prevent form resubmission on refresh
    header("Location: logged_home.php#feedback-section");
    exit();
}

// Check if user has preferences and fetch them
$hasPreferences = false;
$userPreferences = [];
$preferences_query = "
    SELECT 
        q.gender,
        q.ConcentrationID,
        q.Personality,
        q.LifestyleID,
        GROUP_CONCAT(DISTINCT s.ScentID SEPARATOR ',') AS scentIDs
    FROM 
        preference p
    JOIN 
        question q ON p.questionID = q.questionID
    LEFT JOIN 
        question_scent qs ON q.questionID = qs.questionID
    LEFT JOIN 
        scents s ON qs.ScentID = s.ScentID
    WHERE 
        p.customerID = ?
    GROUP BY 
        p.preferenceID
    ORDER BY
        p.preferenceID DESC
    LIMIT 1
";
$preferences_stmt = $conn->prepare($preferences_query);
$preferences_stmt->bind_param("i", $_SESSION['customerID']);
$preferences_stmt->execute();
$preferences_result = $preferences_stmt->get_result();

if ($preferences_result->num_rows > 0) {
    $hasPreferences = true;
    $userPreferences = $preferences_result->fetch_assoc();
    $userPreferences['scentIDs'] = !empty($userPreferences['scentIDs']) ? explode(',', $userPreferences['scentIDs']) : [];
     // Split personality into an array if multiple are selected
    $userPreferences['Personality'] = !empty($userPreferences['Personality']) ? explode(',', $userPreferences['Personality']) : [];

} else {
     $userPreferences['scentIDs'] = []; // Ensure scentIDs is an array even if no preferences
     $userPreferences['Personality'] = [];
     $userPreferences['LifestyleID'] = null;
     $userPreferences['gender'] = null;
     $userPreferences['ConcentrationID'] = null;
}

// Fetch user's preferred scents
$preferredScents = [];
$preferredScentsQuery = $conn->prepare("
    SELECT 
        GROUP_CONCAT(DISTINCT s.ScentName SEPARATOR ',') AS scents
    FROM 
        preference p
    JOIN 
        question q ON p.questionID = q.questionID
    LEFT JOIN 
        question_scent qs ON q.questionID = qs.questionID
    LEFT JOIN 
        scents s ON qs.ScentID = s.ScentID
    WHERE 
        p.customerID = ?
    GROUP BY 
        p.preferenceID
    ORDER BY
        p.preferenceID DESC
    LIMIT 1
");
$preferredScentsQuery->bind_param("i", $_SESSION['customerID']);
$preferredScentsQuery->execute();
$preferredScentsResult = $preferredScentsQuery->get_result();

$userPreferredScentNames = [];
if ($preferredScentsResult->num_rows > 0) {
    $row = $preferredScentsResult->fetch_assoc();
    if (!empty($row['scents'])) {
        $userPreferredScentNames = array_map('trim', explode(',', $row['scents']));
    }
}

// Fetch Scent IDs for the user's preferred scent names
$userPreferredScentIDs = [];
if (!empty($userPreferredScentNames)) {
    $placeholders = str_repeat('?,', count($userPreferredScentNames) - 1) . '?';
    $scentIDQuery = $conn->prepare("SELECT ScentID, ScentName FROM scents WHERE ScentName IN ($placeholders)");
    $types = str_repeat('s', count($userPreferredScentNames));
    $scentIDQuery->bind_param($types, ...$userPreferredScentNames);
    $scentIDQuery->execute();
    $scentIDResult = $scentIDQuery->get_result();
    while ($row = $scentIDResult->fetch_assoc()) {
        $userPreferredScentIDs[$row['ScentName']] = $row['ScentID'];
    }
}

// Function to get products by scent name
function getProductsByScent($conn, $scentName, $limit = 5) {
    $query = "
        SELECT 
            p.product_id,
            p.product_name,
            p.price,
            p.product_image,
            COALESCE(SUM(sales_alias.QuantitySold), 0) AS total_sales
        FROM products p
        JOIN product_scent ps ON p.product_id = ps.product_id
        JOIN scents s ON ps.scent_id = s.ScentID
        LEFT JOIN sales sales_alias ON p.product_id = sales_alias.ProductID
        WHERE s.ScentName = ?
        GROUP BY p.product_id
        ORDER BY total_sales DESC
        LIMIT ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $scentName, $limit);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Check for active promotions for each product
    foreach ($products as &$product) {
        $promo_query = "
            SELECT 
                pr.promotion_id,
                pr.promo_name,
                pr.discount_type,
                pr.discount_value,
                pr.start_date,
                pr.end_date
            FROM 
                promotions pr
            WHERE 
                pr.product_id = ? 
                AND pr.start_date <= NOW() 
                AND pr.end_date >= NOW()
            ORDER BY 
                pr.created_at DESC
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

// Function to get promotional products
function getPromotionalProducts($conn, $limit = 5) {
    $query = "
        SELECT 
            p.product_id,
            p.product_name,
            p.price,
            p.product_image,
            COALESCE(SUM(s.QuantitySold), 0) AS total_sales,
            pr.promotion_id,
            pr.promo_name,
            pr.discount_type,
            pr.discount_value,
            pr.start_date,
            pr.end_date
        FROM products p
        JOIN promotions pr ON p.product_id = pr.product_id 
        LEFT JOIN sales s ON p.product_id = s.ProductID
        WHERE pr.start_date <= NOW() 
          AND pr.end_date >= NOW()
        GROUP BY p.product_id
        ORDER BY p.product_id DESC
        LIMIT ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate discounted price for each product
    foreach ($products as &$product) {
         if ($product['promotion_id']) {
             // Calculate discounted price
             if ($product['discount_type'] == 'percentage') {
                 $product['discounted_price'] = $product['price'] * (1 - ($product['discount_value'] / 100));
                 $product['discount_text'] = $product['discount_value'] . '% OFF';
             } else {
                 $product['discounted_price'] = $product['price'] - $product['discount_value'];
                 $product['discount_text'] = 'RM' . number_format($product['discount_value'], 2) . ' OFF';
             }
             $product['promotion'] = [ // Create a structure similar to other functions
                 'promotion_id' => $product['promotion_id'],
                 'promo_name' => $product['promo_name'],
                 'discount_type' => $product['discount_type'],
                 'discount_value' => $product['discount_value'],
                 'start_date' => $product['start_date'],
                 'end_date' => $product['end_date']
             ];
         }
    }
    
    return $products;
}

$popularProducts = getPopularProducts($conn);

// Function to get recommended products based on preferences
function getRecommendedProducts($conn, $userPreferences, $limit = 5) {
    // Define weights for preference matching (adjust as needed)
    $genderWeight = 20;
    $concentrationWeight = 20;
    $scentWeight = 30; // Base weight for individual scent matches
    $combinationScentBoost = 30; // Boost for matching combination scents

    // Define combination recommendations (Personality + Lifestyle -> Recommended Scents)
    $combinationRecommendations = [
        ['personality' => 'calm', 'lifestyle' => 4, 'scents' => ['Green', 'Herbal', 'Woody']], // Lifestyle ID 4 for Hiking in nature (assuming from question.php)
        ['personality' => 'bold', 'lifestyle' => 1, 'scents' => ['Oriental', 'Spicy', 'Amber']], // Lifestyle ID 1 for Partying with friends (assuming from question.php)
        ['personality' => 'elegant', 'lifestyle' => 2, 'scents' => ['Floral', 'Amber', 'Musk']], // Lifestyle ID 2 for Enjoying a luxury dinner (assuming from question.php)
        ['personality' => 'mysterious', 'lifestyle' => 3, 'scents' => ['Leather', 'Oriental', 'Woody']], // Lifestyle ID 3 for Cozying up with a book (assuming from question.php)
        ['personality' => 'playful', 'lifestyle' => 5, 'scents' => ['Citrus', 'Aquatic', 'Fruity']], // Lifestyle ID 5 for Relaxing at a beach (assuming from question.php)
    ];

    // Fetch Scent IDs for the recommended scent names
    $recommendedScentIDs = [];
    $scentNames = [];
    foreach ($combinationRecommendations as $rec) {
        $scentNames = array_merge($scentNames, $rec['scents']);
    }
    $scentNames = array_unique($scentNames);

    if (!empty($scentNames)) {
        $placeholders = str_repeat('?,', count($scentNames) - 1) . '?';
        $scentIDQuery = $conn->prepare("SELECT ScentID, ScentName FROM scents WHERE ScentName IN ($placeholders)");
        $types = str_repeat('s', count($scentNames));
        $scentIDQuery->bind_param($types, ...$scentNames);
        $scentIDQuery->execute();
        $scentIDResult = $scentIDQuery->get_result();
        while ($row = $scentIDResult->fetch_assoc()) {
            $recommendedScentIDs[$row['ScentName']] = $row['ScentID'];
        }
    }


    // Base query to get products and related info
    $query = "
        SELECT 
            p.product_id,
            p.product_name,
            p.price,
            p.product_image,
            p.gender,
            p.ConcentrationID,
            GROUP_CONCAT(DISTINCT ps.scent_id SEPARATOR ',') AS productScentIDs
        FROM products p
        LEFT JOIN product_scent ps ON p.product_id = ps.product_id
        GROUP BY p.product_id
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $recommendedProducts = [];

    // Calculate match score for each product
    foreach ($products as $product) {
        $matchScore = 0;

        // Gender match
        if (!empty($userPreferences['gender']) && strtolower($product['gender']) === strtolower($userPreferences['gender'])) {
            $matchScore += $genderWeight;
        }

        // Concentration match
         if (!empty($userPreferences['ConcentrationID']) && $product['ConcentrationID'] == $userPreferences['ConcentrationID']) {
            $matchScore += $concentrationWeight;
        }

        // Scent matches (Individual Preferences)
        $productScentIDs = !empty($product['productScentIDs']) ? explode(',', $product['productScentIDs']) : [];
        $scentMatches = count(array_intersect($userPreferences['scentIDs'], $productScentIDs));
        $matchScore += $scentMatches * ($scentWeight / max(1, count($userPreferences['scentIDs']))); // Distribute base scent weight

        // Combination Match Boost
        if (!empty($userPreferences['Personality']) && $userPreferences['LifestyleID'] !== null) {
            foreach ($combinationRecommendations as $rec) {
                // Check if user's personality and lifestyle match the combination
                if (in_array($rec['personality'], $userPreferences['Personality']) && $rec['lifestyle'] == $userPreferences['LifestyleID']) {
                    // Check if product contains any of the recommended scents for this combination
                    $combinationScentMatches = 0;
                    foreach ($rec['scents'] as $recScentName) {
                         if (isset($recommendedScentIDs[$recScentName]) && in_array($recommendedScentIDs[$recScentName], $productScentIDs)) {
                              $combinationScentMatches++;
                         }
                    }
                    // Add boost based on how many combination scents match
                    $matchScore += $combinationScentMatches * ($combinationScentBoost / max(1, count($rec['scents'])));
                    break; // Apply only the first matching combination boost
                }
            }
        }

        // Cap score at 100
        $matchScore = min(100, $matchScore);

        // Add product and score if score is above a certain threshold or if few products are found
        // Adjust threshold or logic based on desired strictness of recommendations
        if ($matchScore > 0) { // Only include products with at least some match
             $product['match_score'] = $matchScore;
             $recommendedProducts[] = $product;
        }
    }

    // Sort by match score descending
    usort($recommendedProducts, function($a, $b) {
        return $b['match_score'] <=> $a['match_score'];
    });

    // Apply promotion details and limit
    $finalRecommendedProducts = [];
    $count = 0;
    foreach ($recommendedProducts as $product) {
         if ($count >= $limit) break;

         // Fetch promotion details (similar to other product functions)
         $promo_query = "
             SELECT 
                 pr.promotion_id,
                 pr.promo_name,
                 pr.discount_type,
                 pr.discount_value,
                 pr.start_date,
                 pr.end_date
             FROM 
                 promotions pr
             WHERE 
                 pr.product_id = ? 
                 AND pr.start_date <= NOW() 
                 AND pr.end_date >= NOW()
             ORDER BY 
                 pr.created_at DESC
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

         $finalRecommendedProducts[] = $product;
         $count++;
    }

    return $finalRecommendedProducts;
}

// Get recommended products if user has preferences
$recommendedProducts = [];
if ($hasPreferences) {
    $recommendedProducts = getRecommendedProducts($conn, $userPreferences, 5);
}

// Get top rated products
function getTopRatedProducts($conn, $limit = 5) {
    $query = "
        SELECT 
            p.product_id,
            p.product_name,
            p.price,
            p.product_image,
            (SELECT COALESCE(AVG(Rating), 0) FROM reviews WHERE ProductID = p.product_id) as average_rating,
            (SELECT COUNT(*) FROM reviews WHERE ProductID = p.product_id) as total_ratings,
            (SELECT COALESCE(SUM(QuantitySold), 0) FROM sales WHERE ProductID = p.product_id) as total_sales
        FROM 
            products p
        HAVING 
            average_rating > 0
        ORDER BY 
            average_rating DESC, total_ratings DESC
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

$topRatedProducts = getTopRatedProducts($conn);

// Get promotional products
$promotionalProducts = getPromotionalProducts($conn, 5);

// Include navigation after all logic is processed
include 'top-navigation.php';
?>

<!-- Welcome Header Section -->
<div class="welcome-header">
    <div class="welcome-content">
        <h1 class="welcome-title">Welcome to ScentMatch</h1>
        <p>Discover your perfect fragrance match. We're here to help you find scents that resonate with your personality and preferences. Explore our curated collection of premium perfumes and let your signature scent tell your story.</p>
    </div>
</div>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScentMatch</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/logged_home.css">
    <link rel="stylesheet" href="css/top-navigation.css">
    <link rel="stylesheet" href="css/promotions.css">
    <style>
        .welcome-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            transition: opacity 0.3s ease;
        }

        .welcome-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1001;
            transition: opacity 0.3s ease;
        }

        .welcome-popup h2 {
            color: black;
            margin-bottom: 1rem;
        }

        .welcome-popup p {
            color: #333;
            margin-bottom: 1rem;
        }

        .welcome-button {
            background-color: #000;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 1rem;
            transition: background-color 0.3s ease;
        }

        .welcome-button:hover {
            background-color: #333;
        }

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

        .product {
            position: relative;
            cursor: pointer;
        }

        .product-link {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 30px;
            z-index: 1;
        }

        .product:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
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
            background:rgb(0, 0, 0);
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 13px;
        }

        .compare-btn:hover {
            background:rgb(115, 115, 115);
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
            z-index: 2000;
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
            background-color:rgb(0, 0, 0);
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
            background-color:rgb(255, 102, 102);
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

        /* Ensure top navigation is behind the overlay when comparison is active */
        .comparison-overlay.active ~ .top-nav {
            z-index: 998; /* Lower than comparison-overlay's z-index (999) */
        }

        /* Hide sticky compare when comparison is active */
        .comparison-view.active ~ .sticky-compare {
            opacity: 0;
            visibility: hidden;
            transform: translateX(-50%) translateY(20px);
        }

        .product .rating {
            color: #ffc107;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .product .rating .stars {
            display: inline-flex;
            align-items: center;
            font-size: 1rem;
            letter-spacing: 1px;
        }

        .product .rating .stars span {
            display: inline-block;
            width: 16px;
            height: 16px;
            line-height: 16px;
            text-align: center;
        }

        /* Add styles for the view more link */
        .top-sale-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .view-more-link {
            font-size: 0.85rem; /* Slightly smaller font */
            color: #ffffff; /* White text */
            background-color: #000000; /* Black background */
            padding: 5px 12px; /* Adjust padding */
            border-radius: 20px; /* Pill shape */
            text-decoration: none; /* Remove underline */
            transition: background-color 0.3s ease, opacity 0.3s ease;
            display: inline-flex; /* Align text and icon */
            align-items: center;
            gap: 5px; /* Space between text and icon */
            font-weight: 500; /* Semi-bold */
        }

        .view-more-link:hover {
            background-color: #333333; /* Darker gray on hover */
            text-decoration: none; /* Ensure no underline on hover */
        }

        .view-more-link i {
            font-size: 0.7rem; /* Size of the icon */
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

        /* Feedback Section Mobile Responsive Styles */
        @media (max-width: 768px) {
            .about-section {
                padding: 20px;
            }

            .about-content {
                padding: 15px;
            }

            .feedback-header {
                text-align: center;
            }

            .feedback-title h2 {
                font-size: 1.5rem;
                margin-bottom: 10px;
            }

            .feedback-title .tagline {
                font-size: 1rem;
                margin-bottom: 15px;
            }

            .about-description p {
                font-size: 0.9rem;
                line-height: 1.4;
                margin-bottom: 20px;
            }

            .feedback-form {
                padding: 7px;
                padding-left: 1px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .form-group label {
                font-size: 0.9rem;
                margin-bottom: 5px;
            }

            .form-group input[type="text"],
            .form-group select,
            .form-group textarea {
                width: 100%;
                padding: 10px;
                font-size: 0.9rem;
            }

            .rating-stars {
                justify-content: center;
                gap: 5px;
            }

            .rating-stars label {
                font-size: 1.5rem;
                padding: 5px;
            }

            .submit-btn {
                width: 100%;
                padding: 12px;
                font-size: 1rem;
            }

            .login-prompt {
                text-align: center;
                padding: 20px;
            }

            .login-prompt p {
                font-size: 0.9rem;
                margin-bottom: 15px;
            }

            .setup-preferences {
                display: inline-block;
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }

        /* Additional styles for very small screens */
        @media (max-width: 480px) {
            .about-section {
                padding: 15px;
            }

            .feedback-title h2 {
                font-size: 1.3rem;
            }

            .feedback-title .tagline {
                font-size: 0.9rem;
            }

            .form-group input[type="text"],
            .form-group select,
            .form-group textarea {
                padding: 8px;
                font-size: 0.85rem;
            }

            .rating-stars label {
                font-size: 1.3rem;
            }

            .submit-btn {
                padding: 10px;
                font-size: 0.9rem;
            }
        }

        /* Add these styles in the existing style section */
        .compare-limit-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: flex-start;
            padding-top: 50px;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .compare-limit-popup.show {
            opacity: 1;
        }

        .compare-limit-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            position: relative;
            transform: translateY(-100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .compare-limit-popup.show .compare-limit-content {
            transform: translateY(0);
            opacity: 1;
        }

        .compare-limit-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #333;
        }

        .compare-limit-message {
            margin-bottom: 25px;
            color: #666;
        }

        .compare-limit-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .compare-limit-btn {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .compare-limit-ok-btn {
            background: #000;
            color: white;
        }

        .compare-limit-ok-btn:hover {
            background: #333;
            transform: translateY(-2px);
        }

        .notification-content i {
            color: #ffffff;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translate(-50%, -40%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
            to {
                opacity: 0;
                transform: translate(-50%, -60%);
            }
        }

        /* Overlay for notification */
        .notification-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 9998;
            animation: fadeIn 0.3s ease-in-out forwards;
            pointer-events: none;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>

<body>
    <?php if (isset($_SESSION['show_welcome'])): ?>
    <div class="welcome-overlay" id="welcomeOverlay"></div>
    <div class="welcome-popup" id="welcomePopup">
        <div class="welcome-content">
            <h2>Welcome to ScentMatch! 🎉</h2>
            <p>Thank you for joining ScentMatch! We're excited to help you discover your perfect fragrance match.</p>
            <p>Feel free to explore our collection and find scents that resonate with you.</p>
            <button onclick="closeWelcomePopup()" class="welcome-button">Start Exploring</button>
        </div>
    </div>
    <?php 
    // Remove the flag so the popup doesn't show again
    unset($_SESSION['show_welcome']);
    endif; 
    ?>
    <!-- New Recommended Section -->
    <?php if (isset($_SESSION['customerID'])): ?>
        <div class="container-wrapper recommended-wrapper">
            <div class="top-sale-header">
                <h2>Recommended For You</h2>
            </div>
            <div class="container">
                <?php if ($hasPreferences && !empty($recommendedProducts)): ?>
                    <?php foreach ($recommendedProducts as $product): ?>
                        <div class="product <?php echo isset($product['promotion']) ? 'promotion-product' : ''; ?>">
                            <?php if (isset($product['promotion'])): ?>
                                <div class="promotion-badge <?php echo (strtotime($product['promotion']['end_date']) - time() < 86400) ? 'limited' : ''; ?>">
                                    <?php echo htmlspecialchars($product['discount_text']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <img src="uploads/<?= htmlspecialchars($product['product_image']) ?>"
                                alt="<?= htmlspecialchars($product['product_name']) ?>">
                            <h3><?= htmlspecialchars($product['product_name']) ?></h3>
                            
                            <div class="product-info">
                                <div class="price-container">
                                    <?php if (isset($product['promotion'])): ?>
                                        <p class="price original-price">RM<?= number_format($product['price'], 2) ?></p>
                                        <p class="price discounted-price">RM<?= number_format($product['discounted_price'], 2) ?></p>
                                    <?php else: ?>
                                        <p class="price">RM<?= number_format($product['price'], 2) ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (isset($product['match_score'])): ?>
                                    <p class="match-score">🎯<?= $product['match_score'] ?>%</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="compare-container">
                                <input type="checkbox" id="compare_recommended_<?= $product['product_id'] ?>" class="compare-checkbox" data-product-id="<?= $product['product_id'] ?>">
                                <label for="compare_recommended_<?= $product['product_id'] ?>" class="compare-label">Compare</label>
                            </div>
                            
                            <a href="product_details.php?product_id=<?= $product['product_id'] ?>" class="product-link"></a>
                        </div>
                    <?php endforeach; ?>
                <?php elseif (!$hasPreferences): ?>
                    <div class="no-preferences">
                        <p>You haven't set up your scent preferences yet.</p>
                        <a href="question.php?fromHome=true" class="setup-preferences">Set Up Preferences</a>
                    </div>
                <?php else: ?>
                    <p>We're working on finding the perfect recommendations for you.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- New Sections for Products by Preferred Scent -->
        <?php if (!empty($userPreferredScentNames)): ?>
            <?php foreach ($userPreferredScentNames as $scentName): ?>
                <?php
                    $productsByScent = getProductsByScent($conn, $scentName, 5);
                    // Get the ScentID for the current scent name
                    $currentScentID = $userPreferredScentIDs[$scentName] ?? null;
                ?>
                <?php if (!empty($productsByScent) && $currentScentID !== null): ?>
                    <div class="container-wrapper">
                        <div class="top-sale-header">
                            <h2>Scent of <?= htmlspecialchars($scentName) ?></h2>
                            <a href="all_product.php?scent_types[]=<?= $currentScentID ?>" class="view-more-link">View More <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <div class="container">
                            <?php foreach ($productsByScent as $product): ?>
                                <div class="product <?php echo isset($product['promotion']) ? 'promotion-product' : ''; ?>">
                                    <?php if (isset($product['promotion'])): ?>
                                        <div class="promotion-badge <?php echo (strtotime($product['promotion']['end_date']) - time() < 86400) ? 'limited' : ''; ?>">
                                            <?php echo htmlspecialchars($product['discount_text']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <img src="uploads/<?= htmlspecialchars($product['product_image']) ?>"
                                        alt="<?= htmlspecialchars($product['product_name']) ?>">
                                    <h3><?= htmlspecialchars($product['product_name']) ?></h3>
                                    
                                    <div class="product-info">
                                        <div class="price-container">
                                            <?php if (isset($product['promotion'])): ?>
                                                <p class="price original-price">RM<?= number_format($product['price'], 2) ?></p>
                                                <p class="price discounted-price">RM<?= number_format($product['discounted_price'], 2) ?></p>
                                            <?php else: ?>
                                                <p class="price">RM<?= number_format($product['price'], 2) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <p class="total-sales">Sold <?= $product['total_sales'] ?></p>
                                    </div>
                                    
                                    <div class="compare-container">
                                        <input type="checkbox" id="compare_scent_<?= $scentName ?>_<?= $product['product_id'] ?>" class="compare-checkbox" data-product-id="<?= $product['product_id'] ?>">
                                        <label for="compare_scent_<?= $scentName ?>_<?= $product['product_id'] ?>" class="compare-label">Compare</label>
                                    </div>
                                    
                                    <a href="product_details.php?product_id=<?= $product['product_id'] ?>" class="product-link"></a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php endif; ?>

    <!-- New Section for Promotional Products -->
    <div class="container-wrapper promotions-wrapper">
        <div class="top-sale-header">
            <h2>On Sale Products</h2>
            <a href="all_product.php?sort=promotion" class="view-more-link">View More <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="container">
            <?php if (!empty($promotionalProducts)): ?>
                <?php foreach ($promotionalProducts as $product): ?>
                    <div class="product <?php echo isset($product['promotion']) ? 'promotion-product' : ''; ?>">
                        <?php if (isset($product['promotion'])): ?>
                            <div class="promotion-badge <?php echo (strtotime($product['promotion']['end_date']) - time() < 86400) ? 'limited' : ''; ?>">
                                <?php echo htmlspecialchars($product['discount_text']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <img src="uploads/<?php echo htmlspecialchars($product['product_image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        
                        <div class="product-info">
                            <div class="price-container">
                                <?php if (isset($product['promotion'])): ?>
                                    <p class="price original-price">RM<?php echo number_format($product['price'], 2); ?></p>
                                    <p class="price discounted-price">RM<?php echo number_format($product['discounted_price'], 2); ?></p>
                                <?php else: ?>
                                    <p class="price">RM<?php echo number_format($product['price'], 2); ?></p>
                                <?php endif; ?>
                            </div>
                             <?php if (isset($product['total_sales'])): // Display total sales if available ?>
                                <p class="total-sales">Sold <?php echo htmlspecialchars($product['total_sales']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="compare-container">
                            <input type="checkbox" id="compare_promo_<?php echo $product['product_id']; ?>" class="compare-checkbox" data-product-id="<?php echo $product['product_id']; ?>">
                            <label for="compare_promo_<?php echo $product['product_id']; ?>" class="compare-label">Compare</label>
                        </div>
                        
                        <a href="product_details.php?product_id=<?php echo $product['product_id']; ?>" class="product-link"></a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products currently on sale.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="container-wrapper">
        <div class="top-sale-header">
            <h2>Top Sale Products</h2>
            <a href="all_product.php?sort=bestselling" class="view-more-link">View More <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="container">
            <?php if (!empty($popularProducts)): ?>
                <?php foreach ($popularProducts as $product): ?>
                    <div class="product <?php echo isset($product['promotion']) ? 'promotion-product' : ''; ?>">
                        <?php if (isset($product['promotion'])): ?>
                            <div class="promotion-badge <?php echo (strtotime($product['promotion']['end_date']) - time() < 86400) ? 'limited' : ''; ?>">
                                <?php echo htmlspecialchars($product['discount_text']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <img src="uploads/<?php echo htmlspecialchars($product['product_image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        
                        <div class="product-info">
                            <div class="price-container">
                                <?php if (isset($product['promotion'])): ?>
                                    <p class="price original-price">RM<?php echo number_format($product['price'], 2); ?></p>
                                    <p class="price discounted-price">RM<?php echo number_format($product['discounted_price'], 2); ?></p>
                                <?php else: ?>
                                    <p class="price">RM<?php echo number_format($product['price'], 2); ?></p>
                                <?php endif; ?>
                            </div>
                            <p class="total-sales">Sold <?php echo htmlspecialchars($product['total_sales']); ?></p>
                        </div>
                        
                        <div class="compare-container">
                            <input type="checkbox" id="compare_topsale_<?php echo $product['product_id']; ?>" class="compare-checkbox" data-product-id="<?php echo $product['product_id']; ?>">
                            <label for="compare_topsale_<?php echo $product['product_id']; ?>" class="compare-label">Compare</label>
                        </div>
                        
                        <a href="product_details.php?product_id=<?php echo $product['product_id']; ?>" class="product-link"></a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products found.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="container-wrapper">
        <div class="top-sale-header">
            <h2>Top Rated Products</h2>
            <a href="all_product.php?sort=top_rated" class="view-more-link">View More <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="container">
            <?php if (!empty($topRatedProducts)): ?>
                <?php foreach ($topRatedProducts as $product): ?>
                    <div class="product <?php echo isset($product['promotion']) ? 'promotion-product' : ''; ?>">
                        <?php if (isset($product['promotion'])): ?>
                            <div class="promotion-badge <?php echo (strtotime($product['promotion']['end_date']) - time() < 86400) ? 'limited' : ''; ?>">
                                <?php echo htmlspecialchars($product['discount_text']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <img src="uploads/<?php echo htmlspecialchars($product['product_image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        
                        <div class="product-info">
                            <div class="price-container">
                                <?php if (isset($product['promotion'])): ?>
                                    <p class="price original-price">RM<?php echo number_format($product['price'], 2); ?></p>
                                    <p class="price discounted-price">RM<?php echo number_format($product['discounted_price'], 2); ?></p>
                                <?php else: ?>
                                    <p class="price">RM<?php echo number_format($product['price'], 2); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="rating">
                                <span class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span><?php echo ($i <= round($product['average_rating'])) ? '★' : '☆'; ?></span>
                                    <?php endfor; ?>
                                </span>
                                <span class="review-count">
                                    (<?php echo isset($product['total_ratings']) ? $product['total_ratings'] : 0; ?> reviews)
                                </span>
                            </div>
                        </div>
                        
                        <div class="compare-container">
                            <input type="checkbox" id="compare_toprated_<?php echo $product['product_id']; ?>" class="compare-checkbox" data-product-id="<?php echo $product['product_id']; ?>">
                            <label for="compare_toprated_<?php echo $product['product_id']; ?>" class="compare-label">Compare</label>
                        </div>
                        
                        <a href="product_details.php?product_id=<?php echo $product['product_id']; ?>" class="product-link"></a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No rated products found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Feedback Form Section -->
    <div class="about-section" id="feedback-section">
        <div class="about-content">
            <div class="about-text">
                <div class="feedback-header">
                    <div class="feedback-title">
                        <h2>We Value Your Feedback</h2>
                        <p class="tagline">Help Us Improve Your Experience</p>
                        <div class="about-description">
                            <p>Your feedback is essential in helping us enhance our services and provide you with the best possible shopping experience. We appreciate your time and insights.</p>
                        </div>
                    </div>
                    <?php if (isset($_SESSION['customerID'])): ?>
                        <form class="feedback-form" method="POST" action="logged_home.php">
                            <div class="form-group">
                                <label for="feedback_type">Type of Feedback</label>
                                <select name="feedback_type" id="feedback_type" required>
                                    <option value="">Select type</option>
                                    <option value="suggestion">Suggestion</option>
                                    <option value="complaint">Complaint</option>
                                    <option value="praise">Praise</option>
                                    <option value="question">Question</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" required placeholder="Brief description of your feedback">
                            </div>

                            <div class="form-group">
                                <label>Rating (Optional)</label>
                                <div class="rating-stars">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>">
                                        <label for="star<?php echo $i; ?>">★</label>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="message">Your Message</label>
                                <textarea id="message" name="message" rows="5" required placeholder="Please provide details about your feedback..."></textarea>
                            </div>

                            <button type="submit" name="submit_feedback" class="submit-btn">Submit Feedback</button>
                        </form>
                    <?php else: ?>
                        <div class="login-prompt">
                            <p>Please log in to submit your feedback.</p>
                            <a href="login.php" class="setup-preferences">Login Now</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>  

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

    <!-- Add this before the closing body tag -->
    <div class="compare-limit-popup" id="compareLimitPopup">
        <div class="compare-limit-content">
            <h3 class="compare-limit-title">Compare Products</h3>
            <p class="compare-limit-message">You can only compare 2 products at a time. Please deselect a product before selecting another one.</p>
            <div class="compare-limit-buttons">
                <button class="compare-limit-btn compare-limit-ok-btn" onclick="closeCompareLimitPopup()">OK</button>
            </div>
        </div>
    </div>

    <!-- Update the script section -->
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
                            showCompareLimitPopup();
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

    <script>
        // Remove the AJAX form submission code
        document.addEventListener('DOMContentLoaded', function() {
            // Check for feedback submission status in session
            const feedbackStatus = '<?php echo isset($_SESSION['feedback_status']) ? $_SESSION['feedback_status'] : ''; ?>';
            const feedbackMessage = '<?php echo isset($_SESSION['feedback_message']) ? $_SESSION['feedback_message'] : ''; unset($_SESSION['feedback_status']); unset($_SESSION['feedback_message']); ?>';

            if (feedbackStatus === 'success') {
                showNotification(feedbackMessage || 'Feedback Submitted Successfully!');
            } else if (feedbackStatus === 'error') {
                showNotification(feedbackMessage || 'Error submitting feedback.', true);
            }

            function showNotification(message, isError = false) {
                const notification = document.getElementById('successNotification');
                const notificationMessage = document.getElementById('notificationMessage');
                
                if (notification && notificationMessage) {
                    notificationMessage.innerHTML = message;
                    notification.style.backgroundColor = isError ? '#e74c3c' : '#000000';
                    
                    // Show notification with fade-in animation
                    notification.style.display = 'flex';
                    setTimeout(() => {
                        notification.style.opacity = '1';
                    }, 10);
                    
                    // Automatically hide after 5 seconds
                    setTimeout(() => {
                        hideNotification();
                    }, 5000);
                }
            }

            function hideNotification() {
                const notification = document.getElementById('successNotification');
                if (notification) {
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        notification.style.display = 'none';
                    }, 300);
                }
            }
        });
    </script>

    <!-- Success Notification -->
    <div id="successNotification" style="
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: #000000;
        color: white;
        padding: 20px 30px;
        border-radius: 8px;
        z-index: 1002;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        opacity: 0;
        transition: opacity 0.3s ease;
        max-width: 80%;
        text-align: center;
    ">
        <i class="fas fa-check-circle" style="color: #4CAF50; font-size: 1.2rem;"></i>
        <span id="notificationMessage"></span>
    </div>

    <script>
        function closeWelcomePopup() {
            const popup = document.getElementById('welcomePopup');
            const overlay = document.getElementById('welcomeOverlay');
            
            // Add fade-out animation
            popup.style.opacity = '0';
            overlay.style.opacity = '0';
            
            // Remove elements after animation completes
            setTimeout(() => {
                popup.style.display = 'none';
                overlay.style.display = 'none';
            }, 300);
        }
    </script>

    <script>
        // Update the popup functions
        function showCompareLimitPopup() {
            const popup = document.getElementById('compareLimitPopup');
            popup.style.display = 'flex';
            // Trigger reflow
            popup.offsetHeight;
            popup.classList.add('show');
        }

        function closeCompareLimitPopup() {
            const popup = document.getElementById('compareLimitPopup');
            popup.classList.remove('show');
            // Wait for animation to complete before hiding
            setTimeout(() => {
                popup.style.display = 'none';
            }, 300);
        }
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>
 