<?php
session_start();
require_once '../backend/config/db.php';

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request.");
}

$user_id = $_SESSION['user_id'];
$article_id = $_POST['article_id'] ?? '';
$rating = $_POST['rating'] ?? '';
$comment = $_POST['comment'] ?? '';

$article_id = trim($article_id);
$comment = trim($comment);

if ($article_id === '' || $rating === '') {
    die("Missing required fields.");
}

$rating = (int)$rating;

if ($rating < 1 || $rating > 5) {
    die("Invalid rating.");
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

    echo "Saved!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}