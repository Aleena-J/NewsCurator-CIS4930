<?php
session_start();

//Redirect unauthenticated users to the login pages by checking if user_id is set in session data
if (!isset($_SESSION["user_id"])) {
    header("Location: ../frontend/login.php");
    exit();
}

require_once "../backend/config/db.php";

//Normalize language, country, and category to match Webz.IO API's expected values
function normalizeCountry(string $country): string
{
    return strtolower(trim($country));
}

function normalizeLanguage(string $language): string
{
    $map = [
        'english' => 'english',
        'spanish' => 'spanish',
        'french' => 'french',
        'german' => 'german',
        'italian' => 'italian',
        'portuguese' => 'portuguese',
        'arabic' => 'arabic',
        'russian' => 'russian',
        'hindi' => 'hindi',
    ];

    $key = strtolower(trim($language));
    return $map[$key] ?? $key;
}

function topicToCategoryFilter(string $topicName): ?string
{
    $map = [
        'sports' => 'sport',
        'politics' => 'politics',
        'environment' => 'environment',
        'health' => 'health',
        'education' => 'education',
        'science' => 'Science and Technology',
        'technology' => 'Science and Technology',
        'business' => 'Economy, Business and Finance',
        'economy' => 'Economy, Business and Finance',
        'crime' => 'Crime, Law and Justice',
    ];

    $key = strtolower(trim($topicName));
    return $map[$key] ?? null;
}

//Function to build all the parameters connected to each dashboard topic tab to be read by dashboard.js
function buildDashboardTabParams(?string $topicName, array $countries, array $languages, bool $popular = false): array {
    //Popular queries use performance_score for virality
    //Domain rank is used to filter to only the top 5000 news sites
    $q = $popular ? 'domain_rank:<5000 performance_score:>=3' : 'domain_rank:<5000';


    $language = [];
    foreach ($languages as $lang) {
        $l = normalizeLanguage((string)$lang);
        if ($l !== '') { $language[] = $l;}
    }

    $country = [];
    foreach ($countries as $c) {
        $c = normalizeCountry((string)$c);
        if ($c !== '') { $country[] = $c;}
    }

    $category = '';
    //If the topic matches with something in the category map, use category parameter
    //Otherwise, add the topic name to the 'q'/query part of the API call
    if ($topicName !== null && trim($topicName) !== '') {
        $mappedCategory = topicToCategoryFilter($topicName);
        if ($mappedCategory !== null) {
            $category = $mappedCategory;
        } else {
            $q .= ' ' . trim($topicName);
        }
    }

    return [
        'q'                => $q,
        'language'         => $language,
        'country'          => $country,
        'category'         => $category,
    ];
}

//Check for user preferred topics, otherwise set defaults
$userTopics    = [];
$defaultTopics = ['Politics', 'Science', 'Sports'];
//Load user preferences from the database
$userSources   = [];
$userCountries = [];
$userLanguages = [];

try {
    $userId = (int)$_SESSION["user_id"];

    //SQL call to the database to load users preferred topics using user_id
    $stmt = $pdo->prepare("
        SELECT DISTINCT t.name
        FROM user_pref_topic upt
        JOIN topic t ON upt.topic_id = t.topic_id
        WHERE upt.user_id = ?
        ORDER BY t.name
    ");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $topicName = trim($row['name']);
        if ($topicName !== '') {
            $userTopics[] = $topicName;
        }
    }
} catch (Throwable $e) {
    //Fallback - use default topics if call fails
    $userTopics = [];
}

try {
    //Load users preferred news sources from database using user_id
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.name
        FROM user_pref_source ups
        JOIN source s ON ups.source_id = s.source_id
        WHERE ups.user_id = ?
        ORDER BY s.name
    ");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $sourceName = trim($row['name']);
        if ($sourceName !== '') {
            $userSources[] = $sourceName;
        }
    }
} catch (Throwable $e) {
    $userSources = [];
}

//Load users country and language preferences from the database using user_id
try {
    $stmt = $pdo->prepare("
        SELECT country 
        FROM user_pref_country upc
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $userCountries = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $userCountries = [];
}

try {
    $stmt = $pdo->prepare("
        SELECT language 
        FROM user_pref_language upl
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $userLanguages = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $userLanguages = [];
}

//Set user topics to display if they exist, otherwise use defaults
$topicsToDisplay = !empty($userTopics) ? $userTopics : $defaultTopics;
$placeholderSources = $userSources;

//Map user-facing source names to domains used in the API calls, frontend reads map to make calls
$sourceDomainMap = [
    "cnn" => "cnn.com",
    "associated press" => "apnews.com",
    "al jazeera" => "aljazeera.com",
    "npr" => "npr.org",
    "pbs" => "pbs.org",
    "nbc" => "nbcnews.com",
    "business insider" => "businessinsider.com"
];
// Add custom-added user sources (domains) if not in map
foreach ($userSources as $src) {
    $key = strtolower($src);
    if (!isset($sourceDomainMap[$key])) {
        $sourceDomainMap[$key] = $src;
    }
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

    <!-- Keyword search outside of user preferences/tabs -->
    <div class="search-bar-wrap">
        <div class="search-bar">
            <input type="text" id="search-input" placeholder="Search for news...">
            <button id="search-btn">Search</button>
        </div>
    </div>

    <!-- Topic tabs -->
    <div class="topic-tabs-wrap">
        <ul class="topic-tabs">
            <li
                class="topic-tab active"
                data-topic=""
                data-is-popular="1"
                data-params="<?php echo htmlspecialchars(json_encode(buildDashboardTabParams(null, $userCountries, $userLanguages, true))); ?>">
                Popular
            </li>

            <?php foreach ($topicsToDisplay as $topicName): ?>
                <li
                    class="topic-tab"
                    data-topic="<?php echo htmlspecialchars($topicName); ?>"
                    data-is-popular="0"
                    data-params="<?php echo htmlspecialchars(json_encode(buildDashboardTabParams($topicName, $userCountries, $userLanguages))); ?>">
                    <?php echo htmlspecialchars($topicName); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Source tabs, hidden until a topic is selected -->
    <div id="source-tabs-wrap" style="display:none;">
        <ul class="source-tabs" id="source-tabs-list">
            <?php foreach ($placeholderSources as $src): ?>
            <li
                class="source-tab"
                data-source="<?php echo htmlspecialchars(strtolower($src)); ?>"
                data-label="<?php echo htmlspecialchars($src); ?>">
                <?php echo htmlspecialchars($src); ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Num results display -->
    <div class="results-info" id="results-info"></div>

    <!-- News cards display -->
    <div class="news-grid-wrap">
        <div id="news-grid"></div>
    </div>

    <!-- Load more button for pagination -->
    <div id="load-more-wrap">
        <button id="load-more-btn">Load More</button>
    </div>

    <!-- Pop up for rating and comment -->
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
    <script>
        //Debugging statements - show user preferences
        var userCountries = <?php echo json_encode($userCountries); ?>;
        var userLanguages = <?php echo json_encode($userLanguages); ?>;
        var userSources   = <?php echo json_encode($userSources); ?>;
        var sourceDomainMap = <?php echo json_encode($sourceDomainMap); ?>;
    </script>
    <script src="dashboard.js?v=<?php echo filemtime(__DIR__ . '/dashboard.js'); ?>"></script>
    <script>
        //debug statements, show the query parameters sent
        console.group("Dashboard pre-built queries (PHP)");
        console.log("Popular :", <?php echo json_encode(
            buildDashboardTabParams(null, $userCountries, $userLanguages, true)
        ); ?>);
        <?php foreach ($topicsToDisplay as $topicName): ?>
        console.log(
            <?php echo json_encode($topicName); ?> + ":",
            <?php echo json_encode(
                buildDashboardTabParams($topicName, $userCountries, $userLanguages)
            ); ?>
        );
        <?php endforeach; ?>
        console.groupEnd();
    </script>
</body>
</html>