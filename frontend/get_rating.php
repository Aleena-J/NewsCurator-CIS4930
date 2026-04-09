<?php
require_once '../backend/config/db.php';

header('Content-Type: application/json');

$article_id = $_GET['article_id'] ?? '';

if ($article_id === '') {
    echo json_encode(["avg" => "0.0"]);
    exit();
}

$stmt = $pdo->prepare("
    SELECT ROUND(AVG(rating), 1) AS avg_rating
    FROM comments
    WHERE article_id = ?
");
$stmt->execute([$article_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$avg = (float)($row['avg_rating'] ?? 0);

if ($avg == 5) {
    $formatted = "5";
} else {
    $formatted = number_format($avg, 1);
}

echo json_encode(["avg" => $formatted]);