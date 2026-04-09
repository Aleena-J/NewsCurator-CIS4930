<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$url = isset($_GET["url"]) ? (string) $_GET["url"] : "";
$title = isset($_GET["title"]) ? (string) $_GET["title"] : "";
$publisher = isset($_GET["publisher"]) ? (string) $_GET["publisher"] : "";
$country = isset($_GET["country"]) ? (string) $_GET["country"] : "";
$date = isset($_GET["date"]) ? (string) $_GET["date"] : "";
$language = isset($_GET["language"]) ? (string) $_GET["language"] : "";

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
                <div class="card-rating-badge" id="article-total-score">&#9733; 0/5</div>
            </div>
            <?php if ($publisher !== "") { ?>
                <p class="article-page-site"><?php echo htmlspecialchars($publisher); ?></p>
            <?php } ?>
        </div>

        <div class="article-page-wrap">
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
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="article.js?v=<?php echo filemtime(__DIR__ . '/article.js'); ?>"></script>
    </body>
</html>
