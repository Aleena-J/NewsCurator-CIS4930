<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "../backend/config/db.php";

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

function topicToCategoryFilter(string $topicName): string
{
    $map = [
        'sports' => 'sport',
        'politics' => 'politics',
        'environment' => 'environment',
        'health' => 'health',
        'education' => 'education',
        'science' => '"Science and Technology"',
        'technology' => '"Science and Technology"',
        'business' => '"Economy, Business and Finance"',
        'economy' => '"Economy, Business and Finance"',
        'crime' => '"Crime, Law and Justice"',
    ];

    $key = strtolower(trim($topicName));
    return isset($map[$key]) ? 'category:' . $map[$key] : 'category:"' . addslashes($topicName) . '"';
}

function sourceToSiteFilter(string $sourceName): ?string
{
    $map = [
        'bbc' => 'bbc.com',
        'cnn' => 'cnn.com',
        'reuters' => 'reuters.com',
        'associated press' => 'apnews.com',
        'al jazeera' => 'aljazeera.com',
        'the guardian' => 'theguardian.com',
        'npr' => 'npr.org',
        'bloomberg' => 'bloomberg.com',
    ];

    $key = strtolower(trim($sourceName));
    if (!isset($map[$key])) {
        return null;
    }

    return 'site:' . $map[$key];
}

function buildCountryLanguageFilters(array $countries, array $languages): array
{
    $parts = [];

    if (!empty($countries)) {
        $countryParts = [];
        foreach ($countries as $country) {
            $country = normalizeCountry((string)$country);
            if ($country !== '') {
                $countryParts[] = 'thread.country:' . $country;
            }
        }
        if (!empty($countryParts)) {
            $parts[] = '(' . implode(' OR ', $countryParts) . ')';
        }
    }

    if (!empty($languages)) {
        $languageParts = [];
        foreach ($languages as $language) {
            $language = normalizeLanguage((string)$language);
            if ($language !== '') {
                $languageParts[] = 'language:' . $language;
            }
        }
        if (!empty($languageParts)) {
            $parts[] = '(' . implode(' OR ', $languageParts) . ')';
        }
    }

    return $parts;
}

function buildSourceFilters(array $sources): array
{
    $siteParts = [];

    foreach ($sources as $source) {
        $filter = sourceToSiteFilter((string)$source);
        if ($filter !== null) {
            $siteParts[] = $filter;
        }
    }

    $siteParts = array_values(array_unique($siteParts));
    $siteParts = array_slice($siteParts, 0, 2);

    if (empty($siteParts)) {
        return [];
    }

    return ['(' . implode(' OR ', $siteParts) . ')'];
}

function buildDashboardTabQuery(?string $topicName, array $countries, array $languages, array $sources, bool $popular = false): string
{
    $queryParts = [];

    if ($popular) {
        $queryParts[] = 'performance_score:5';
    }

    $queryParts = array_merge($queryParts, buildCountryLanguageFilters($countries, $languages));
    $queryParts = array_merge($queryParts, buildSourceFilters($sources));

    if ($topicName !== null && trim($topicName) !== '') {
        $queryParts[] = topicToCategoryFilter($topicName);
    }

    return implode(' AND ', $queryParts);
}

$userTopics = [];
$defaultTopics = ['Politics', 'Science', 'Sports'];

$userSources = [];

$userCountries = [];
$userLanguages = [];

try {
    $userId = (int)$_SESSION["user_id"];

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
    $userTopics = [];
}

try {
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

try {
    $stmt = $pdo->prepare("SELECT countries, languages FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $userCountries = json_decode($row['countries'] ?? '[]', true) ?: [];
        $userLanguages = json_decode($row['languages'] ?? '[]', true) ?: [];
    }
} catch (Throwable $e) {
    $userCountries = [];
    $userLanguages = [];
}

$topicsToDisplay = !empty($userTopics) ? $userTopics : $defaultTopics;
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

    <div class="topic-tabs-wrap">
        <ul class="topic-tabs">
            <li
                class="topic-tab active"
                data-topic=""
                data-query="<?php echo htmlspecialchars(buildDashboardTabQuery(null, $userCountries, $userLanguages, $userSources, true)); ?>">
                Popular
            </li>

            <?php foreach ($topicsToDisplay as $topicName): ?>
                <li
                    class="topic-tab"
                    data-topic="<?php echo htmlspecialchars($topicName); ?>"
                    data-query="<?php echo htmlspecialchars(buildDashboardTabQuery($topicName, $userCountries, $userLanguages, $userSources)); ?>">
                    <?php echo htmlspecialchars($topicName); ?>
                </li>
            <?php endforeach; ?>
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
        var userCountries = <?php echo json_encode($userCountries); ?>;
        var userLanguages = <?php echo json_encode($userLanguages); ?>;
    </script>
   <script src="dashboard.js?v=<?php echo filemtime(__DIR__ . '/dashboard.js'); ?>"></script>
    <script>
        console.log("Popular query:", <?php echo json_encode(buildDashboardTabQuery(null, $userCountries, $userLanguages, $userSources, true)); ?>);
        <?php foreach ($topicsToDisplay as $topicName): ?>
        console.log(
            <?php echo json_encode($topicName); ?> + " query:",
            <?php echo json_encode(buildDashboardTabQuery($topicName, $userCountries, $userLanguages, $userSources)); ?>
        );
        <?php endforeach; ?>
    </script>
</body>
</html>