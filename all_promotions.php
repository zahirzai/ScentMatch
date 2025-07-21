<?php
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");
include 'top-navigation.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get all active promotions without limit
function getAllActivePromotions($conn)
{
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
            s.companyName as seller_name,
            s.SellerID
        FROM 
            promotions p
        JOIN 
            products pr ON p.product_id = pr.product_id
        JOIN 
            seller s ON pr.seller_id = s.SellerID
        WHERE 
            p.start_date <= NOW() 
            AND p.end_date >= NOW()
        ORDER BY 
            p.created_at DESC";

    $result = $conn->query($query);
    $promotions = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Calculate discounted price
            $original_price = $row['price'];
            if ($row['discount_type'] == 'percentage') {
                $discounted_price = $original_price * (1 - ($row['discount_value'] / 100));
                $discount_text = $row['discount_value'] . '% OFF';
            } else {
                $discounted_price = $original_price - $row['discount_value'];
                $discount_text = 'RM' . number_format($row['discount_value'], 2) . ' OFF';
            }
            
            $row['discounted_price'] = $discounted_price;
            $row['discount_text'] = $discount_text;
            $promotions[] = $row;
        }
    }
    
    return $promotions;
}

$allPromotions = getAllActivePromotions($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Promotions - ScentMatch</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/top-navigation.css">
    <link rel="stylesheet" href="css/promotions.css">
    <style>
        .promotion-header {
            text-align: center;
            margin: 30px 0;
            padding: 30px 0;
            background: linear-gradient(to right, #000000, #333333);
            border-radius: 8px;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
        }
        
        .promotion-header h1 {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 10px;
            text-align: center;
            width: 100%;
        }
        
        .promotion-header p {
            color: #ffffff;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .no-promotions {
            text-align: center;
            padding: 50px 0;
            color: #666;
        }
        
        .seller-section {
            margin-bottom: 40px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .seller-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .seller-header h2 {
            font-size: 1.5rem;
            color: #333;
            margin: 0;
        }
        
        .view-shop {
            display: inline-block;
            padding: 5px 15px;
            background-color: #000000;
            color: white;
            text-decoration: none;
            border-radius: 20px;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        
        .view-shop:hover {
            background-color: #333333;
        }

        /* Add style to remove potential gap */
        body > *:last-child:not(.footer) {
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .footer {
            margin-top: 0 !important;
        }

        /* Remove bottom margin from the last seller section */
        .seller-section:last-of-type {
            margin-bottom: 0;
        }
    </style>
</head>

<body>
    <div class="promotion-header">
        <h1>Special Offers</h1>
        <p>Discover amazing deals and discounts from our sellers. Limited time offers on selected products.</p>
    </div>

    <?php if (empty($allPromotions)): ?>
        <div class="no-promotions">
            <h2>No active promotions at the moment</h2>
            <p>Check back later for new deals and discounts!</p>
        </div>
    <?php else: ?>
        <?php
        // Group promotions by seller
        $promotionsBySeller = [];
        foreach ($allPromotions as $promo) {
            $sellerID = $promo['SellerID'];
            if (!isset($promotionsBySeller[$sellerID])) {
                $promotionsBySeller[$sellerID] = [
                    'companyName' => $promo['seller_name'],
                    'promotions' => []
                ];
            }
            $promotionsBySeller[$sellerID]['promotions'][] = $promo;
        }
        
        // Display promotions by seller
        foreach ($promotionsBySeller as $sellerID => $sellerData): 
        ?>
            <div class="seller-section">
                <div class="seller-header">
                    <h2><?php echo htmlspecialchars($sellerData['companyName']); ?>'s Promotions</h2>
                    <a href="viewShop.php?seller_id=<?php echo $sellerID; ?>" class="view-shop">
                        View Shop <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                
                <div class="container">
                    <?php foreach ($sellerData['promotions'] as $promo): ?>
                        <div class="product promotion-product"
                            onclick="window.location.href='product_details.php?product_id=<?php echo $promo['product_id']; ?>'">
                            <!-- Promotion badge -->
                            <div class="promotion-badge <?php echo (strtotime($promo['end_date']) - time() < 86400) ? 'limited' : ''; ?>">
                                <?php echo htmlspecialchars($promo['discount_text']); ?>
                            </div>
                            
                            <img src="uploads/<?php echo htmlspecialchars($promo['product_image']); ?>" 
                                alt="<?php echo htmlspecialchars($promo['product_name']); ?>">
                            <h3><?php echo htmlspecialchars($promo['product_name']); ?></h3>
                            
                            <p class="price original-price">
                                RM<?php echo number_format($promo['price'], 2); ?>
                            </p>
                            <p class="price discounted-price">
                                RM<?php echo number_format($promo['discounted_price'], 2); ?>
                            </p>
                            
                            <p class="promo-details">
                                <?php echo htmlspecialchars($promo['promo_name']); ?>
                            </p>
                            
                            <div class="promo-timer">
                                <?php 
                                    $end_date = new DateTime($promo['end_date']);
                                    $now = new DateTime();
                                    $interval = $now->diff($end_date);
                                    
                                    if ($interval->days > 0) {
                                        echo "Ends in " . $interval->days . " day" . ($interval->days > 1 ? "s" : "");
                                    } else {
                                        $hours = $interval->h;
                                        $minutes = $interval->i;
                                        echo "Ends in " . $hours . "h " . $minutes . "m";
                                    }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php include 'footer.php'; ?>
</body>
</html> 