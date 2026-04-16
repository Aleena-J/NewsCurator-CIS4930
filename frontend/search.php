<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../frontend/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>NewsCurator Search</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="./css/styles.css">
    </head>

    <body>

        <?php require_once './components/navbar.php'; ?>

        <div class="page-header">
            <h1>Search News</h1>
            <p>Enter keywords and optionally narrow by country, language, and category.</p>
        </div>

        <div class="search-page-wrap search-page-wide">
            <form id="search-form" class="search-page-form" action="#" method="get">
                <label for="search-keywords" class="form-label mb-1">Keywords</label>
                <input type="search" id="search-keywords" class="form-control mb-3" maxlength="500"
                    placeholder="e.g. climate summit, election results">

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="filter-country" class="form-label small mb-1">Country</label>
                        <select id="filter-country" class="form-select form-select-sm">
                            <option value="">Any</option>
                            <option value="US">United States</option>
                            <option value="GB">United Kingdom</option>
                            <option value="CA">Canada</option>
                            <option value="AU">Australia</option>
                            <option value="DE">Germany</option>
                            <option value="FR">France</option>
                            <option value="IN">India</option>
                            <option value="JP">Japan</option>
                            <option value="BR">Brazil</option>
                            <option value="MX">Mexico</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filter-lang" class="form-label small mb-1">Language</label>
                        <select id="filter-lang" class="form-select form-select-sm">
                            <option value="">Any</option>
                            <option value="english">English</option>
                            <option value="spanish">Spanish</option>
                            <option value="french">French</option>
                            <option value="german">German</option>
                            <option value="italian">Italian</option>
                            <option value="portuguese">Portuguese</option>
                            <option value="arabic">Arabic</option>
                            <option value="russian">Russian</option>
                            <option value="hindi">Hindi</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filter-category" class="form-label small mb-1">Category</label>
                        <select id="filter-category" class="form-select form-select-sm">
                            <option value="">Any</option>
                            <option value="sport">Sport</option>
                            <option value="politics">Politics</option>
                            <option value="environment">Environment</option>
                            <option value="health">Health</option>
                            <option value="education">Education</option>
                            <option value="science_tech">Science &amp; technology</option>
                            <option value="economy">Economy, business &amp; finance</option>
                            <option value="crime">Crime, law &amp; justice</option>
                            <option value="human_interest">Human interest</option>
                            <option value="war">War, conflict &amp; unrest</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <div class="results-info" id="search-results-info"></div>

        <div class="search-results-outer">
            <ul class="search-results-list list-unstyled mb-0" id="search-results-list"></ul>
        </div>

        <div id="load-more-wrap">
            <button type="button" id="search-load-more-btn">Load more</button>
        </div>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="search.js?v=<?php echo filemtime(__DIR__ . '/search.js'); ?>"></script>
    </body>
</html>
