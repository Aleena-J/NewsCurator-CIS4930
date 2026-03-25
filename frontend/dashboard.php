<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="./css/styles.css">
    </head>

    <body>

        <?php require_once './components/navbar.php'; ?>

        <div class="page-header">
            <h1>Your News</h1>
            <p id="today-date"></p>
        </div>

        <div class="search-bar-wrap">
            <div class="search-bar">
                <input type="text" id="search-input" placeholder="Search for news...">
                <button id="search-btn">Search</button>
            </div>
        </div>

        <!-- TODO: Implement getting user preferences - these are placeholders  -->
        <div class="topic-tabs-wrap">
            <ul class="topic-tabs">
                <li class="topic-tab active" data-query="performance_score:5">Popular</li>
                <li class="topic-tab" data-query="category:Politics">Politics</li>
                <li class="topic-tab" data-query='category:"Science and Technology"'>Science</li>
                <li class="topic-tab" data-query="category:Sport">Sports</li>
            </ul>
        </div>

        <div class="results-info" id="results-info"></div>

        <div class="news-grid-wrap">
            <div id="news-grid"></div>
        </div>

        <div id="load-more-wrap">
            <button id="load-more-btn">Load More</button>
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
                <div class="rating-popup-actions">
                    <button id="rating-submit-btn" class="btn-rating-submit" disabled>Submit</button>
                    <button id="rating-cancel-btn" class="btn-rating-cancel">Cancel</button>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="dashboard.js"></script>
    </body>
</html>