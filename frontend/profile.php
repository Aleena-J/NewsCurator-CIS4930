<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "../backend/config/db.php";

$userId = (int) $_SESSION["user_id"];
$username = $_SESSION["username"] ?? "User";
$feedback = "";
$feedbackType = "";

// Add two lists of all possible countries to their ISO codes and languages for validation when processing form submissions.
$countriesOptions = [
    "AF", "AL", "DZ", "AS", "AD", "AO", "AI", "AQ", "AG", "AR",
    "AM", "AW", "AU", "AT", "AZ", "BS", "BH", "BD", "BB", "BY", "BE", "BZ", "BJ", "BM", "BT", "BO",
    "BQ", "BA", "BW", "BV", "BR", "IO", "BN", "BG", "BF", "BI", "CV", "KH", "CM", "CA", "KY", "CF",
    "TD", "CL", "CN", "CX", "CC", "CO", "KM", "CG", "CD", "CK", "CR", "CI", "HR", "CU", "CW", "CY", "CZ", "DK", "DJ", "DM", "DO",
    "EC", "EG", "SV", "GQ", "ER", "EE", "ET", "FK", "FO", "FJ", "FI", "FR", "GF", "PF", "TF", "GA", "GM", "GE", "DE", "GH",
    "GI", "GR", "GL", "GD", "GP", "GU", "GT", "GG", "GN", "GW", "GY", "HT", "HM", "VA", "HN", "HK", "HU", "IS", "IN", "ID", "IR", "IQ",
    "IE", "IM", "IL", "IT", "JM", "JP", "JE", "JO", "KZ", "KE", "KI", "KP", "KR", "KW", "KG", "LA", "LV", "LB", "LS", "LR", "LY",
    "LI", "LT", "LU", "MO", "MK", "MG", "MW", "MY", "MV", "ML", "MT", "MH", "MQ", "MR", "MU", "YT", "MX", "FM", "MD", "MC", "MN", "ME",
    "MS", "MA", "MZ", "MM", "NA", "NR", "NP", "NL", "NC", "NZ", "NI", "NE", "NG", "NU", "NF", "MP", "NO", "OM", "PK", "PW", "PS", "PA",
    "PG", "PY", "PE", "PH", "PN", "PL", "PT", "PR", "QA", "RE", "RO", "RU", "RW", "BL", "SH", "KN", "LC", "MF", "PM", "VC", "WS", "SM",
    "ST", "SA", "SN", "RS", "SC", "SL", "SG", "SX", "SK", "SI", "SB", "SO", "ZA", "GS", "SS", "ES", "LK", "SD", "SR", "SJ", "SE", "CH", "SY",
    "TW", "TJ", "TZ", "TH", "TL", "TG", "TK", "TO", "TT", "TN", "TR", "TM", "TC", "TV", "UG", "UA", "AE", "GB", "US", "UM", "UY", "UZ",
    "VU", "VE", "VN", "VG", "VI", "WF", "EH", "YE", "ZM", "ZW"
];

$languageOptions = ["English", "Mandarin", "Chinese", "Hindi", "Spanish", "French", "Arabic", "Bengali", "Portuguese", "Russian", 
    "Urdu", "Indonesian", "German", "Japanese", "Nigerian", "Egyptian Arabic", "Marathi", "Telugu", "Turkish",
    "Tamil", "Cantonese", "Vietnamese", "Tagalog", "Korean", "Italian", "Hausa", "Thai", "Gujarati", "Javanese", 
    "Persian", "Farsi", "Polish", "Ukrainian", "Malayalam", "Kannada", "Odia", "Maithili",
    "Burmese", "Sundanese", "Yoruba", "Amharic", "Romanian", "Pashto", "Serbo-Croatian", "Malay", "Zulu",
    "Dutch", "Igbo", "Sinhalese"
];

function parseCustomList(string $raw): array
{
    $parts = preg_split('/,/', $raw);
    if (!is_array($parts)) {
        return [];
    }

    $clean = [];
    foreach ($parts as $part) {
        $value = trim($part);
        if ($value !== "") {
            $clean[] = substr($value, 0, 60);
        }
    }

    return array_values(array_unique($clean));
}

function parsePostedList(string $field): array
{
    $items = $_POST[$field] ?? [];
    if (!is_array($items)) {
        return [];
    }

    $clean = [];
    foreach ($items as $item) {
        $value = trim((string) $item);
        if ($value !== "") {
            $clean[] = substr($value, 0, 60);
        }
    }

    return array_values(array_unique($clean));
}

function splitTopicsByCustom(array $savedTopics, array $defaultTopics): array
{
    $normalizedDefaults = [];
    foreach ($defaultTopics as $topic) {
        $normalizedDefaults[strtolower(trim((string) $topic))] = true;
    }

    $customTopics = [];
    foreach ($savedTopics as $topic) {
        $value = trim((string) $topic);
        if ($value === "") {
            continue;
        }

        if (!isset($normalizedDefaults[strtolower($value)])) {
            $customTopics[] = $value;
        }
    }

    return array_values(array_unique($customTopics));
}

function getCurrentAccountData(PDO $pdo, int $userId): array
{
    $countryItems = [];
    try {
        $countryStmt = $pdo->prepare("SELECT country FROM user_pref_country WHERE user_id = ? ORDER BY country");
        $countryStmt->execute([$userId]);
        $countryItems = $countryStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } catch (Throwable $e) {
        $countryItems = [];
    }

    $languageItems = [];
    try {
        $languageStmt = $pdo->prepare("SELECT language FROM user_pref_language WHERE user_id = ? ORDER BY language");
        $languageStmt->execute([$userId]);
        $languageItems = $languageStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } catch (Throwable $e) {
        $languageItems = [];
    }

    $topicItems = [];
    try {
        $topicStmt = $pdo->prepare("SELECT t.name FROM user_pref_topic upt JOIN topic t ON upt.topic_id = t.topic_id WHERE upt.user_id = ? ORDER BY t.name");
        $topicStmt->execute([$userId]);
        $topicItems = $topicStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } catch (Throwable $e) {
        $topicItems = [];
    }

    $sourceItems = [];
    try {
        $sourceStmt = $pdo->prepare("SELECT s.name FROM user_pref_source ups JOIN source s ON ups.source_id = s.source_id WHERE ups.user_id = ? ORDER BY s.name");
        $sourceStmt->execute([$userId]);
        $sourceItems = $sourceStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } catch (Throwable $e) {
        $sourceItems = [];
    }

    return [
        "preferences" => [
            "countries" => array_values(array_unique(array_map('trim', $countryItems))),
            "languages" => array_values(array_unique(array_map('trim', $languageItems))),
            "sources" => array_values(array_unique(array_map('trim', $sourceItems))),
            "topics" => array_values(array_unique(array_map('trim', $topicItems))),
        ],
    ];
}

function ensureCountriesExist(PDO $pdo, array $countries): array
{
    $countries = array_values(array_unique(array_filter(array_map('trim', $countries))));
    if (count($countries) === 0) {
        return [];
    }

    // Validate that countries are valid ISO codes
    $validCountryCodes = [
        "AF", "AL", "DZ", "AS", "AD", "AO", "AI", "AQ", "AG", "AR",
        "AM", "AW", "AU", "AT", "AZ", "BS", "BH", "BD", "BB", "BY", "BE", "BZ", "BJ", "BM", "BT", "BO",
        "BQ", "BA", "BW", "BV", "BR", "IO", "BN", "BG", "BF", "BI", "CV", "KH", "CM", "CA", "KY", "CF",
        "TD", "CL", "CN", "CX", "CC", "CO", "KM", "CG", "CD", "CK", "CR", "CI", "HR", "CU", "CW", "CY", "CZ", "DK", "DJ", "DM", "DO",
        "EC", "EG", "SV", "GQ", "ER", "EE", "ET", "FK", "FO", "FJ", "FI", "FR", "GF", "PF", "TF", "GA", "GM", "GE", "DE", "GH",
        "GI", "GR", "GL", "GD", "GP", "GU", "GT", "GG", "GN", "GW", "GY", "HT", "HM", "VA", "HN", "HK", "HU", "IS", "IN", "ID", "IR", "IQ",
        "IE", "IM", "IL", "IT", "JM", "JP", "JE", "JO", "KZ", "KE", "KI", "KP", "KR", "KW", "KG", "LA", "LV", "LB", "LS", "LR", "LY",
        "LI", "LT", "LU", "MO", "MK", "MG", "MW", "MY", "MV", "ML", "MT", "MH", "MQ", "MR", "MU", "YT", "MX", "FM", "MD", "MC", "MN", "ME",
        "MS", "MA", "MZ", "MM", "NA", "NR", "NP", "NL", "NC", "NZ", "NI", "NE", "NG", "NU", "NF", "MP", "NO", "OM", "PK", "PW", "PS", "PA",
        "PG", "PY", "PE", "PH", "PN", "PL", "PT", "PR", "QA", "RE", "RO", "RU", "RW", "BL", "SH", "KN", "LC", "MF", "PM", "VC", "WS", "SM",
        "ST", "SA", "SN", "RS", "SC", "SL", "SG", "SX", "SK", "SI", "SB", "SO", "ZA", "GS", "SS", "ES", "LK", "SD", "SR", "SJ", "SE", "CH", "SY",
        "TW", "TJ", "TZ", "TH", "TL", "TG", "TK", "TO", "TT", "TN", "TR", "TM", "TC", "TV", "UG", "UA", "AE", "GB", "US", "UM", "UY", "UZ",
        "VU", "VE", "VN", "VG", "VI", "WF", "EH", "YE", "ZM", "ZW"
    ];

    $countryMap = [];
    foreach ($countries as $country) {
        $code = strtoupper(trim($country));
        if (in_array($code, $validCountryCodes, true)) {
            $countryMap[$country] = $code;
        }
    }

    return $countryMap;
}

function ensureLanguagesExist(PDO $pdo, array $languages): array
{
    $languages = array_values(array_unique(array_filter(array_map('trim', $languages))));
    if (count($languages) === 0) {
        return [];
    }

    $validLanguages = ["English", "Mandarin", "Chinese", "Hindi", "Spanish", "French", "Arabic", "Bengali", "Portuguese", "Russian",
        "Urdu", "Indonesian", "German", "Japanese", "Nigerian", "Egyptian Arabic", "Marathi", "Telugu", "Turkish",
        "Tamil", "Cantonese", "Vietnamese", "Tagalog", "Korean", "Italian", "Hausa", "Thai", "Gujarati", "Javanese",
        "Persian", "Farsi", "Polish", "Ukrainian", "Malayalam", "Kannada", "Odia", "Maithili",
        "Burmese", "Sundanese", "Yoruba", "Amharic", "Romanian", "Pashto", "Serbo-Croatian", "Malay", "Zulu",
        "Dutch", "Igbo", "Sinhalese"
    ];

    $languageMap = [];
    foreach ($languages as $language) {
        $normalized = ucfirst(strtolower(trim($language)));
        if (in_array($normalized, $validLanguages, true)) {
            $languageMap[$language] = $normalized;
        }
    }

    return $languageMap;
}

function setUserPrefCountries(PDO $pdo, int $userId, array $countries): void
{
    $validCountriesMap = ensureCountriesExist($pdo, $countries);
    $validCountries = array_values($validCountriesMap);

    $deleteStmt = $pdo->prepare("DELETE FROM user_pref_country WHERE user_id = ?");
    $deleteStmt->execute([$userId]);

    if (count($validCountries) === 0) {
        return;
    }

    $insertStmt = $pdo->prepare("
        INSERT INTO user_pref_country (user_id, country)
        VALUES (?, ?)
    ");

    foreach ($validCountries as $country) {
        $insertStmt->execute([$userId, $country]);
    }
}

function setUserPrefLanguages(PDO $pdo, int $userId, array $languages): void
{
    $validLanguagesMap = ensureLanguagesExist($pdo, $languages);
    $validLanguages = array_values($validLanguagesMap);

    $deleteStmt = $pdo->prepare("DELETE FROM user_pref_language WHERE user_id = ?");
    $deleteStmt->execute([$userId]);

    if (count($validLanguages) === 0) {
        return;
    }

    $insertStmt = $pdo->prepare("
        INSERT INTO user_pref_language (user_id, language)
        VALUES (?, ?)
    ");

    foreach ($validLanguages as $language) {
        $insertStmt->execute([$userId, $language]);
    }
}

function ensureTopicsExist(PDO $pdo, array $topics): array
{
    $topics = array_values(array_unique(array_filter(array_map('trim', $topics))));
    if (count($topics) === 0) {
        return [];
    }

    // Insert all topics (will be ignored if they already exist)
    $insertStmt = $pdo->prepare("INSERT IGNORE INTO topic (name) VALUES (?)");
    foreach ($topics as $name) {
        if ($name !== '') {
            try {
                $insertStmt->execute([$name]);
            } catch (Throwable $e) {
                // log or ignore insert errors, continue with others
            }
        }
    }

    // Select all matching topics to get their IDs
    $placeholders = implode(',', array_fill(0, count($topics), '?'));
    $selectStmt = $pdo->prepare("SELECT topic_id, name FROM topic WHERE name IN ($placeholders)");
    $selectStmt->execute($topics);
    $results = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

    $topicIds = [];
    foreach ($results as $row) {
        $topicIds[$row['name']] = (int) $row['topic_id'];
    }

    return $topicIds;
}

function setUserPrefTopics(PDO $pdo, int $userId, array $topics): void
{
    $topicIdsByName = ensureTopicsExist($pdo, $topics);
    $topicIds = array_map(function($id) { return (int) $id; }, array_values($topicIdsByName));

    // delete old links and insert new ones
    $deleteStmt = $pdo->prepare("DELETE FROM user_pref_topic WHERE user_id = ?");
    $deleteStmt->execute([$userId]);

    if (count($topicIds) === 0) {
        return;
    }

    $insertStmt = $pdo->prepare("INSERT INTO user_pref_topic (user_id, topic_id) VALUES (?, ?)");
    foreach ($topicIds as $topicId) {
        $insertStmt->execute([$userId, $topicId]);
    }
}

function ensureSourcesExist(PDO $pdo, array $sources): array
{
    $sources = array_values(array_unique(array_filter(array_map('trim', $sources))));
    if (count($sources) === 0) {
        return [];
    }

    $insertStmt = $pdo->prepare("INSERT IGNORE INTO source (name) VALUES (?)");
    foreach ($sources as $name) {
        if ($name !== '') {
            try {
                $insertStmt->execute([$name]);
            } catch (Throwable $e) {
            }
        }
    }

    $placeholders = implode(',', array_fill(0, count($sources), '?'));
    $selectStmt = $pdo->prepare("SELECT source_id, name FROM source WHERE name IN ($placeholders)");
    $selectStmt->execute($sources);
    $results = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

    $sourceIds = [];
    foreach ($results as $row) {
        $sourceIds[$row['name']] = (int) $row['source_id'];
    }

    return $sourceIds;
}

function setUserPrefSources(PDO $pdo, int $userId, array $sources): void
{
    $sourceIdsByName = ensureSourcesExist($pdo, $sources);
    $sourceIds = array_map(function ($id) {
        return (int) $id;
    }, array_values($sourceIdsByName));

    $deleteStmt = $pdo->prepare("DELETE FROM user_pref_source WHERE user_id = ?");
    $deleteStmt->execute([$userId]);

    if (count($sourceIds) === 0) {
        return;
    }

    $insertStmt = $pdo->prepare("
        INSERT INTO user_pref_source (user_id, source_id)
        VALUES (?, ?)
    ");

    foreach ($sourceIds as $sourceId) {
        $insertStmt->execute([$userId, $sourceId]);
    }
}

$topicsOptions = ["Politics", "Science", "Sports", "Technology", "Health", "Business", "Environment", "Education", "Economy", "Crime"];
$accountData = getCurrentAccountData($pdo, $userId);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "update_preferences") {
        $section = $_POST["section"] ?? "";
        $preferences = $accountData["preferences"];

        if ($section === "countries") {
            $customCountriesInput = parseCustomList(trim($_POST["countries_custom"] ?? ""));
            $selectedCountries = parsePostedList("countries");
            $allCountries = array_values(array_unique(array_merge($selectedCountries, $customCountriesInput)));

            try {
                setUserPrefCountries($pdo, $userId, $allCountries);
                $preferences["countries"] = $allCountries;
                $feedback = "Preferences updated.";
                $feedbackType = "success";
            } catch (Throwable $e) {
                $feedback = "Could not save country preferences. " . $e->getMessage();
                $feedbackType = "danger";
            }
        } elseif ($section === "languages") {
            $customLanguagesInput = parseCustomList(trim($_POST["languages_custom"] ?? ""));
            $selectedLanguages = parsePostedList("languages");
            $allLanguages = array_values(array_unique(array_merge($selectedLanguages, $customLanguagesInput)));

            try {
                setUserPrefLanguages($pdo, $userId, $allLanguages);
                $preferences["languages"] = $allLanguages;
                $feedback = "Preferences updated.";
                $feedbackType = "success";
            } catch (Throwable $e) {
                $feedback = "Could not save language preferences. " . $e->getMessage();
                $feedbackType = "danger";
            }
        } elseif ($section === "sources") {
            $preferences["sources"] = array_values(array_unique(array_merge(
                parsePostedList("sources"),
                parseCustomList(trim($_POST["sources_custom"] ?? ""))
            )));

            try {
                setUserPrefSources($pdo, $userId, $preferences["sources"]);
                $feedback = "Preferences updated.";
                $feedbackType = "success";
            } catch (Throwable $e) {
                $feedback = "Could not save source preferences yet. " . $e->getMessage();
                $feedbackType = "danger";
            }
        } elseif ($section === "topics") {
            $keptCustomTopics = parsePostedList("topics_custom_keep");
            $updatedTopics = array_values(array_unique(array_merge(
                parsePostedList("topics"),
                $keptCustomTopics,
                parseCustomList(trim($_POST["topics_custom"] ?? ""))
            )));
            $preferences["topics"] = $updatedTopics;

            try {
                setUserPrefTopics($pdo, $userId, $preferences["topics"]);
                $feedback = "Preferences updated.";
                $feedbackType = "success";
            } catch (Throwable $e) {
                $feedback = "Could not save topic preferences yet. " . $e->getMessage();
                $feedbackType = "danger";
            }
        } else {
            $feedback = "Unknown preference section.";
            $feedbackType = "danger";
        }
    }

    $accountData = getCurrentAccountData($pdo, $userId);
}

$countriesOptions = ["US", "GB", "CA", "AU", "DE", "FR", "IN", "JP", "BR", "MX"];
$languageOptions = ["English", "Spanish", "French", "German", "Italian", "Portuguese", "Arabic", "Russian", "Hindi"];
$sourcesOptions = ["BBC", "CNN", "Reuters", "Associated Press", "Al Jazeera", "The Guardian", "NPR", "Bloomberg"];
$topicsOptions = ["Politics", "Science", "Sports", "Technology", "Health", "Business", "Environment", "Education", "Economy", "Crime"];

$customCountries = array_values(array_diff($accountData["preferences"]["countries"], $countriesOptions));
$customLanguages = array_values(array_diff($accountData["preferences"]["languages"], $languageOptions));
$customSources = array_values(array_diff($accountData["preferences"]["sources"], $sourcesOptions));
$customTopics = array_values(array_diff($accountData["preferences"]["topics"], $topicsOptions));
$initial = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Account</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="./css/styles.css?v=<?php echo filemtime(__DIR__ . '/css/styles.css'); ?>">
    </head>
    <body>
        <?php require_once './components/navbar.php'; ?>

        <div class="page-header">
            <h1>Account</h1>
            <p>Manage your profile and personalization settings.</p>
        </div>

        <?php if ($feedback !== ""): ?>
            <div class="account-feedback-wrap">
                <div class="alert alert-<?php echo htmlspecialchars($feedbackType); ?> mb-0">
                    <?php echo htmlspecialchars($feedback); ?>
                </div>
            </div>
        <?php endif; ?>

        <main class="account-page-wrap">

            <section class="account-left">
                <div class="account-card">
                    <h2>Profile</h2>
                    <div class="profile-photo-shell">
                        <div class="profile-photo-placeholder"><?php echo htmlspecialchars($initial); ?></div>
                    </div>

                    <p class="profile-name"><?php echo htmlspecialchars($username); ?></p>
                </div>
            </section>

            <section class="account-right">
                <div class="account-card">
                    <div class="pref-header">
                        <h2>Edit Preferences</h2>
                    </div>

                    <div class="pref-section">
                        <div class="pref-section-top">
                            <h3>Countries</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm pref-edit-btn" data-target="countries-editor">Edit</button>
                        </div>
                        <div class="pref-chip-row">
                            <?php if (count($accountData["preferences"]["countries"]) === 0): ?>
                                <span class="pref-empty">No countries selected.</span>
                            <?php else: ?>
                                <?php foreach ($accountData["preferences"]["countries"] as $country): ?>
                                    <span class="pref-chip"><?php echo htmlspecialchars($country); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <form action="profile.php" method="POST" class="pref-editor d-none" id="countries-editor">
                            <input type="hidden" name="action" value="update_preferences">
                            <input type="hidden" name="section" value="countries">
                            <div class="pref-checkbox-grid">
                                <?php foreach ($countriesOptions as $option): ?>
                                    <label><input type="checkbox" name="countries[]" value="<?php echo htmlspecialchars($option); ?>" <?php echo in_array($option, $accountData["preferences"]["countries"], true) ? "checked" : ""; ?>> <?php echo htmlspecialchars($option); ?></label>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($customCountries) > 0): ?>
                                <div class="pref-custom-section mt-3">
                                    <div class="pref-custom-title">Custom countries</div>
                                    <div class="pref-checkbox-grid">
                                        <?php foreach ($customCountries as $customCountry): ?>
                                            <label><input type="checkbox" name="countries[]" value="<?php echo htmlspecialchars($customCountry); ?>" checked> <?php echo htmlspecialchars($customCountry); ?></label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <label class="form-label mt-2 mb-1">Additional countries (comma-separated)</label>
                            <input type="text" name="countries_custom" class="form-control form-control-sm" placeholder="e.g. NZ, ZA">
                            <div class="pref-actions mt-2">
                                <button type="submit" class="btn btn-primary btn-sm">Save Countries</button>
                                <button type="button" class="btn btn-secondary btn-sm pref-cancel-btn">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <div class="pref-section">
                        <div class="pref-section-top">
                            <h3>Languages</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm pref-edit-btn" data-target="languages-editor">Edit</button>
                        </div>
                        <div class="pref-chip-row">
                            <?php if (count($accountData["preferences"]["languages"]) === 0): ?>
                                <span class="pref-empty">No languages selected.</span>
                            <?php else: ?>
                                <?php foreach ($accountData["preferences"]["languages"] as $language): ?>
                                    <span class="pref-chip"><?php echo htmlspecialchars($language); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <form action="profile.php" method="POST" class="pref-editor d-none" id="languages-editor">
                            <input type="hidden" name="action" value="update_preferences">
                            <input type="hidden" name="section" value="languages">
                            <div class="pref-checkbox-grid">
                                <?php foreach ($languageOptions as $option): ?>
                                    <label><input type="checkbox" name="languages[]" value="<?php echo htmlspecialchars($option); ?>" <?php echo in_array($option, $accountData["preferences"]["languages"], true) ? "checked" : ""; ?>> <?php echo htmlspecialchars($option); ?></label>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($customLanguages) > 0): ?>
                                <div class="pref-custom-section mt-3">
                                    <div class="pref-custom-title">Custom languages</div>
                                    <div class="pref-checkbox-grid">
                                        <?php foreach ($customLanguages as $customLanguage): ?>
                                            <label><input type="checkbox" name="languages[]" value="<?php echo htmlspecialchars($customLanguage); ?>" checked> <?php echo htmlspecialchars($customLanguage); ?></label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <label class="form-label mt-2 mb-1">Additional languages (comma-separated)</label>
                            <input type="text" name="languages_custom" class="form-control form-control-sm" placeholder="e.g. Korean, Dutch">
                            <div class="pref-actions mt-2">
                                <button type="submit" class="btn btn-primary btn-sm">Save Languages</button>
                                <button type="button" class="btn btn-secondary btn-sm pref-cancel-btn">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <div class="pref-section">
                        <div class="pref-section-top">
                            <h3>Sources</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm pref-edit-btn" data-target="sources-editor">Edit</button>
                        </div>
                        <div class="pref-chip-row">
                            <?php if (count($accountData["preferences"]["sources"]) === 0): ?>
                                <span class="pref-empty">No sources selected.</span>
                            <?php else: ?>
                                <?php foreach ($accountData["preferences"]["sources"] as $source): ?>
                                    <span class="pref-chip"><?php echo htmlspecialchars($source); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <form action="profile.php" method="POST" class="pref-editor d-none" id="sources-editor">
                            <input type="hidden" name="action" value="update_preferences">
                            <input type="hidden" name="section" value="sources">
                            <div class="pref-checkbox-grid">
                                <?php foreach ($sourcesOptions as $option): ?>
                                    <label><input type="checkbox" name="sources[]" value="<?php echo htmlspecialchars($option); ?>" <?php echo in_array($option, $accountData["preferences"]["sources"], true) ? "checked" : ""; ?>> <?php echo htmlspecialchars($option); ?></label>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($customSources) > 0): ?>
                                <div class="pref-custom-section mt-3">
                                    <div class="pref-custom-title">Custom sources</div>
                                    <div class="pref-checkbox-grid">
                                        <?php foreach ($customSources as $customSource): ?>
                                            <label><input type="checkbox" name="sources[]" value="<?php echo htmlspecialchars($customSource); ?>" checked> <?php echo htmlspecialchars($customSource); ?></label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <label class="form-label mt-2 mb-1">Additional sources (comma-separated)</label>
                            <p style="line-height: 0.8; font-style: italic; color: #9a9c9f; font-size: 0.875rem;">Please enter the domain name of the source</p>
                            <input type="text" name="sources_custom" class="form-control form-control-sm" placeholder="e.g. nbcnews.com, wired.com">
                            <div class="pref-actions mt-2">
                                <button type="submit" class="btn btn-primary btn-sm">Save Sources</button>
                                <button type="button" class="btn btn-secondary btn-sm pref-cancel-btn">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <div class="pref-section">
                        <div class="pref-section-top">
                            <h3>Topics</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm pref-edit-btn" data-target="topics-editor">Edit</button>
                        </div>
                        <div class="pref-chip-row">
                            <?php if (count($accountData["preferences"]["topics"]) === 0): ?>
                                <span class="pref-empty">No topics selected.</span>
                            <?php else: ?>
                                <?php foreach ($accountData["preferences"]["topics"] as $topic): ?>
                                    <span class="pref-chip"><?php echo htmlspecialchars($topic); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <form action="profile.php" method="POST" class="pref-editor d-none" id="topics-editor">
                            <input type="hidden" name="action" value="update_preferences">
                            <input type="hidden" name="section" value="topics">
                            <div class="pref-checkbox-grid">
                                <?php foreach ($topicsOptions as $option): ?>
                                    <label><input type="checkbox" name="topics[]" value="<?php echo htmlspecialchars($option); ?>" <?php echo in_array($option, $accountData["preferences"]["topics"], true) ? "checked" : ""; ?>> <?php echo htmlspecialchars($option); ?></label>
                                <?php endforeach; ?>
                            </div>
<?php if (count($customTopics) === 0): ?>
    <label class="form-label mt-3 mb-1">Custom topics</label>
    <div class="pref-checkbox-grid">
        <span class="pref-empty">No custom topics added.</span>
    </div>
<?php else: ?>
    <div class="pref-custom-section mt-3">
        <div class="pref-custom-title">Custom topics</div>
        <div class="pref-checkbox-grid">
            <?php foreach ($customTopics as $customTopic): ?>
                <label><input type="checkbox" name="topics[]" value="<?php echo htmlspecialchars($customTopic); ?>" checked> <?php echo htmlspecialchars($customTopic); ?></label>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
                            <label class="form-label mt-2 mb-1">Additional topics (comma-separated)</label>
                            <input type="text" name="topics_custom" class="form-control form-control-sm" placeholder="e.g. AI, Startups">
                            <div class="pref-actions mt-2">
                                <button type="submit" class="btn btn-primary btn-sm">Save Topics</button>
                                <button type="button" class="btn btn-secondary btn-sm pref-cancel-btn">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </main>

        <script>
            document.querySelectorAll(".pref-edit-btn").forEach(function(btn) {
                btn.addEventListener("click", function() {
                    var id = btn.getAttribute("data-target");
                    var editor = document.getElementById(id);
                    if (editor) {
                        editor.classList.remove("d-none");
                    }
                });
            });

            document.querySelectorAll(".pref-cancel-btn").forEach(function(btn) {
                btn.addEventListener("click", function() {
                    var editor = btn.closest(".pref-editor");
                    if (editor) {
                        editor.classList.add("d-none");
                    }
                });
            });
        </script>
    </body>
</html>
