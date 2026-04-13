<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
date_default_timezone_set('America/New_York');
require_once "../backend/config/db.php";

$url = isset($_GET["url"]) ? (string) $_GET["url"] : "";
$title = isset($_GET["title"]) ? (string) $_GET["title"] : "";
$publisher = isset($_GET["publisher"]) ? (string) $_GET["publisher"] : "";
$country = isset($_GET["country"]) ? (string) $_GET["country"] : "";
$date = isset($_GET["date"]) ? (string) $_GET["date"] : "";
$language = isset($_GET["language"]) ? (string) $_GET["language"] : "";
$image = isset($_GET["image"]) ? (string) $_GET["image"] : "";
$description = isset($_GET["description"]) ? (string) $_GET["description"] : "";



function formatDate($originalDate)
{
    if ($originalDate === "") {
        return "";
    }
    $ts = strtotime($originalDate);
    if ($ts === false) {
        return $originalDate;
    }
    return date("M j, Y, g:i A", $ts);
}

function line($label, $value)
{
    if ($value === "") $value = '<span class="text-muted">N/A</span>';
    else $value = htmlspecialchars($value);
    return '<div class="article-meta-row"><dt class="article-meta-label">' . htmlspecialchars($label) . '</dt><dd class="article-meta-value">' . $value . "</dd></div>";
}

$comments = [];
$averageRating = "0.0";

if ($url !== "") {
    try {
		$stmt = $pdo->prepare("
			SELECT c.user_id, c.rating, c.comment_text, c.created_at, u.username
			FROM comments c
			JOIN users u ON c.user_id = u.user_id
			WHERE c.article_id = ?
			ORDER BY c.created_at DESC
		");
		$stmt->execute([$url]);
		$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$avgStmt = $pdo->prepare("
			SELECT ROUND(AVG(rating), 1) AS avg_rating
			FROM comments
			WHERE article_id = ?
		");
		$avgStmt->execute([$url]);
		$avgRow = $avgStmt->fetch(PDO::FETCH_ASSOC);

		$avg = (float)($avgRow["avg_rating"] ?? 0);

		if ($avg == 5 || $avg == 0) {
			$averageRating = (string)$avg;
		} else {
			$averageRating = number_format($avg, 1);
		}

	} catch (PDOException $e) {
		$comments = [];
		$averageRating = "0.0";
	}
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Article - <?php echo $title?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="./css/styles.css?v=<?php echo filemtime(__DIR__ . '/css/styles.css'); ?>">
    </head>
    <body>
        <?php require_once './components/navbar.php'; ?>

        <div class="page-header">
            <p class="mb-2"><a href="search.php" class="article-back-link">← Back to search</a></p>
            <div class="article-title-row">
                <h1 class="article-page-title"><?php echo $title !== "" ? htmlspecialchars($title) : "Article"; ?></h1>
                <div class="card-rating-badge" id="article-total-score">&#9733; <?php echo $averageRating; ?>/5</div>
            </div>
				<?php if ($publisher !== "") { ?>
					<p class="article-page-site"><?php echo htmlspecialchars($publisher); ?></p>
				<?php } ?>
				<?php if ($description !== "") { ?>
				<p class="article-description">
					<?php echo htmlspecialchars($description); ?>
				</p>
				<?php } ?>
			</div>

        <div class="article-page-wrap">
            <?php if ($image !== "") { ?>
                <div>
                    <img
                        class="article-img"
                        src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>"
                        alt=""
                        onerror="this.style.display='none'"
                    >
                </div>
            <?php } ?>
            <div class="article-detail-card">
                <dl>
                    <?php
                    echo line("Country", $country);
                    echo line("Date", formatDate($date));
                    echo line("Language", $language);
                    ?>
                    <div class="article-meta-row">
                        <dt class="article-meta-label">Original article</dt>
                        <dd class="article-meta-value">
                            <?php if ($url !== "") { ?>
                                <a href="<?php echo htmlspecialchars($url); ?>" target="_blank">Open in new tab</a>
                            <?php } else { ?>
                                <span class="text-muted">N/A</span>
                            <?php } ?>
                        </dd>
                    </div>
                </dl>
            </div>
            <div class="article-rate-actions">
               <button 
					type="button" 
					class="article-rate-btn" 
					data-title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
					data-id="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
				>
					Rate this article
				</button>
            </div>
        </div>

        <div id="rating-popup" class="rating-popup-overlay" style="display:none;">
            <div class="rating-popup-box">
                <h3>Rate this article</h3>
                <p id="rating-popup-title" class="rating-article-title"></p>
                <div class="star-row">
                    <span class="star" data-value="1">&#9733;</span>
                    <span class="star" data-value="2">&#9733;</span>
                    <span class="star" data-value="3">&#9733;</span>
                    <span class="star" data-value="4">&#9733;</span>
                    <span class="star" data-value="5">&#9733;</span>
                </div>
                <p class="star-label" id="star-label">Select a rating</p>
				<textarea 
					id="rating-comment" 
					class="rating-comment-box" 
					placeholder="Optional: Add a comment..."
				></textarea>
                <div class="rating-popup-actions">
                    <button id="rating-submit-btn" class="btn-rating-submit" disabled>Submit</button>
                    <button id="rating-cancel-btn" class="btn-rating-cancel">Cancel</button>
                </div>
				<p id="rating-message" class="rating-message"></p>
            </div>
        </div>
		
		<div class="article-comments-section">
			<h2 class="comments-heading">Reviews</h2>

			<?php if (empty($comments)) { ?>
				<p class="no-comments-msg" id="no-comments-msg">No reviews yet. Be the first to rate this article.</p>
				<div class="comments-list" id="comments-list" style="display:none;"></div>
			<?php } else { ?>
				<div class="comments-list" id="comments-list">
					<?php foreach ($comments as $comment) { ?>
						<div class="comment-card" data-user-id="<?php echo (int)$comment['user_id']; ?>">
							<div class="comment-header">
								<span class="comment-username"><?php echo htmlspecialchars($comment["username"]); ?></span>
								<span class="comment-time">
									<?php echo date("M j, Y, g:i A", strtotime($comment["created_at"])); ?>
								</span>
							</div>

							<div class="comment-rating">
								<?php echo str_repeat("★", (int)$comment["rating"]); ?>
								<?php echo str_repeat("☆", 5 - (int)$comment["rating"]); ?>
								<span class="comment-rating-number">
									<?php echo (int)$comment["rating"]; ?>/5
								</span>
							</div>

							<?php if (!empty($comment["comment_text"])) { ?>
								<p class="comment-text"><?php echo nl2br(htmlspecialchars($comment["comment_text"])); ?></p>
							<?php } else { ?>
								<p class="comment-text text-muted"><em>No written comment provided.</em></p>
							<?php } ?>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
		</div>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="article.js?v=<?php echo filemtime(__DIR__ . '/article.js'); ?>"></script>
    </body>
</html>
