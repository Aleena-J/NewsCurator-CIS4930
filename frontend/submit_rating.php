<?php
// Start the session
session_start(); 

// Include database connection
require_once '../backend/config/db.php';

// East timezone
date_default_timezone_set('America/New_York');

//Return file json
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in."
    ]);
    exit();
}

//Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);
    exit();
}

// Get the logged-in user's ID from the session
$user_id = $_SESSION['user_id'];

// Get submitted form values or default to empty if Missing
$article_id = $_POST['article_id'] ?? '';
$rating = $_POST['rating'] ?? '';
$comment = $_POST['comment_text'] ?? '';

// Remove whitespace from comment and articleID.
$article_id = trim($article_id);
$comment = trim($comment);

// Make sure required fields are present
if ($article_id === '' || $rating === '') {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields."
    ]);
    exit();
}

// convert rating to int
$rating = (int)$rating;

// Make sure rating is between 1 and 5
if ($rating < 1 || $rating > 5) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid rating."
    ]);
    exit();
}


try {
	// Inserts a new comment and rating into the comments table
	// If the user already rated the article, update the existing article instead
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
	
	// look yp username of logged-in user
    $userStmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
    $userStmt->execute([$user_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

	// calculates the average rating of the article
    $avgStmt = $pdo->prepare("
        SELECT ROUND(AVG(rating), 1) AS avg_rating
        FROM comments
        WHERE article_id = ?
    ");
    $avgStmt->execute([$article_id]);
    $avgRow = $avgStmt->fetch(PDO::FETCH_ASSOC);
	
	//Convert the average rating to a float
	$avg = (float)($avgRow['avg_rating'] ?? 0);

	// Formatting: if it is 5, show 5 stars instead of 5.0.
	if ($avg == 5) {
		$formatted_avg = "5";
	} else {
		$formatted_avg = number_format($avg, 1);
	}
	
	// Get the saved comment's created_at timestamp for this user and article.
	$commentStmt = $pdo->prepare("
		SELECT created_at
		FROM comments
		WHERE user_id = ? AND article_id = ?
		LIMIT 1
	");
	$commentStmt->execute([$user_id, $article_id]);
	$savedComment = $commentStmt->fetch(PDO::FETCH_ASSOC);

	// Format timestamp to be readable
	$createdAtFormatted = "";
	if ($savedComment && !empty($savedComment['created_at'])) {
		$createdAtFormatted = date("M j, Y, g:i A", strtotime($savedComment['created_at']));
	}

	// Returns a successs response with the saved comment and updated average rating
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