<?php
session_start();
require_once '../backend/config/db.php';
date_default_timezone_set('America/New_York');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in."
    ]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$article_id = $_POST['article_id'] ?? '';
$rating = $_POST['rating'] ?? '';
$comment = $_POST['comment_text'] ?? '';

$article_id = trim($article_id);
$comment = trim($comment);

if ($article_id === '' || $rating === '') {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields."
    ]);
    exit();
}

$rating = (int)$rating;

if ($rating < 1 || $rating > 5) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid rating."
    ]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO comments (user_id, article_id, rating, comment_text)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            rating = VALUES(rating),
            comment_text = VALUES(comment_text),
            created_at = CURRENT_TIMESTAMP
    ");

    $stmt->execute([
        $user_id,
        $article_id,
        $rating,
        $comment !== '' ? $comment : null
    ]);

    $userStmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
    $userStmt->execute([$user_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    $avgStmt = $pdo->prepare("
        SELECT ROUND(AVG(rating), 1) AS avg_rating
        FROM comments
        WHERE article_id = ?
    ");
    $avgStmt->execute([$article_id]);
    $avgRow = $avgStmt->fetch(PDO::FETCH_ASSOC);
	
	$avg = (float)($avgRow['avg_rating'] ?? 0);

	if ($avg == 5) {
		$formatted_avg = "5";
	} else {
		$formatted_avg = number_format($avg, 1);
	}
	
	$commentStmt = $pdo->prepare("
		SELECT created_at
		FROM comments
		WHERE user_id = ? AND article_id = ?
		LIMIT 1
	");
	$commentStmt->execute([$user_id, $article_id]);
	$savedComment = $commentStmt->fetch(PDO::FETCH_ASSOC);

	$createdAtFormatted = "";
	if ($savedComment && !empty($savedComment['created_at'])) {
		$createdAtFormatted = date("M j, Y, g:i A", strtotime($savedComment['created_at']));
	}

	echo json_encode([
		"success" => true,
		"message" => "Saved!",
		"comment" => [
			"user_id" => $user_id,
			"username" => $user['username'] ?? 'User',
			"rating" => $rating,
			"comment_text" => $comment,
			"created_at" => $createdAtFormatted
		],
		"avg_rating" => $formatted_avg
	]);
    exit();

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
    exit();
}