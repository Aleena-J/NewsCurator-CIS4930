<?php
// include database connection
require_once '../backend/config/db.php';

// running json
header('Content-Type: application/json');

// get article id from url, default to empty elsewise.
$article_id = $_GET['article_id'] ?? '';

// if no article id is provided, return default average of 0.0
if ($article_id === '') {
    echo json_encode(["avg" => "0.0"]);
    exit();
}

// Prepare SQL quesry to calculate averag rating for article
$stmt = $pdo->prepare("
    SELECT ROUND(AVG(rating), 1) AS avg_rating
    FROM comments
    WHERE article_id = ?
");

// Execute query 
$stmt->execute([$article_id]);

// Fetch resutls in associative array
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// Convert result to float, default to 0 if no rating exist
$avg = (float)($row['avg_rating'] ?? 0);

// formatting the average rating (if 5.0 just use 5)
if ($avg == 5) {
    $formatted = "5";
} else {
    $formatted = number_format($avg, 1);
}

// Return formatted average as json
echo json_encode(["avg" => $formatted]);