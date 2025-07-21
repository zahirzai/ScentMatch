<?php
header('Content-Type: application/json');

// Get product IDs from query string
$ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];

if (empty($ids)) {
    echo json_encode([]);
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "scentmatch3");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Prepare the query
$placeholders = str_repeat('?,', count($ids) - 1) . '?';
$query = "
    SELECT 
        p.*,
        c.ConcentrationName as concentration,
        c.ConcentrationPercentage,
        c.Longevity,
        GROUP_CONCAT(DISTINCT s.ScentName SEPARATOR ', ') as scents,
        (SELECT COALESCE(AVG(Rating), 0) FROM reviews WHERE ProductID = p.product_id) AS average_rating,
        (SELECT COUNT(*) FROM reviews WHERE ProductID = p.product_id) AS total_ratings,
        (SELECT COALESCE(SUM(QuantitySold), 0) FROM sales WHERE ProductID = p.product_id) AS total_sales,
        se.CompanyName as seller_name,
        se.LogoUrl as seller_logo
    FROM products p
    LEFT JOIN product_scent ps ON p.product_id = ps.product_id
    LEFT JOIN scents s ON ps.scent_id = s.ScentID
    LEFT JOIN concentration c ON p.ConcentrationID = c.ConcentrationID
    LEFT JOIN seller se ON p.seller_id = se.SellerID
    WHERE p.product_id IN ($placeholders)
    GROUP BY p.product_id
";

$stmt = $conn->prepare($query);
$types = str_repeat('i', count($ids));
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);
$conn->close();
?> 