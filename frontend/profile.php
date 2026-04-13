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

function decodePrefList(?string $json): array
{
    if (!$json) {
        return [];
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return [];
    }

    $clean = [];
    foreach ($decoded as $item) {
        $item = trim((string) $item);
        if ($item !== "") {
            $clean[] = $item;
        }
    }

    return array_values(array_unique($clean));
}

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

function getCurrentAccountData(PDO $pdo, int $userId): array
{
    $prefRow = null;
    try {
        $prefStmt = $pdo->prepare("SELECT countries, languages, sources, topics FROM user_preferences WHERE user_id = ?");
        $prefStmt->execute([$userId]);
        $prefRow = $prefStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $prefRow = null;
    }

    $topicItems = [];
    try {
        $topicStmt = $pdo->prepare("SELECT t.name FROM user_pref_topic upt JOIN topic t ON upt.topic_id = t.topic_id WHERE upt.user_id = ? ORDER BY t.name");
        $topicStmt->execute([$userId]);
        $topicItems = $topicStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } catch (Throwable $e) {
        // fall back to the pre-existing JSON-based field if the new table isn't available yet
        $topicItems = decodePrefList($prefRow["topics"] ?? null);
    }

    $sourceItems = [];
    try {
        $sourceStmt = $pdo->prepare("SELECT s.name FROM user_pref_source ups JOIN source s ON ups.source_id = s.source_id WHERE ups.user_id = ? ORDER BY s.name");
        $sourceStmt->execute([$userId]);
        $sourceItems = $sourceStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } catch (Throwable $e) {
        // fall back to the pre-existing JSON-based field if the new table isn't available yet
        $sourceItems = decodePrefList($prefRow["sources"] ?? null);
    }

    return [
        "preferences" => [
            "countries" => decodePrefList($prefRow["countries"] ?? null),
            "languages" => decodePrefList($prefRow["languages"] ?? null),
            "sources" => array_values(array_unique(array_map('trim', $sourceItems))),
            "topics" => array_values(array_unique(array_map('trim', $topicItems))),
        ],
    ];
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

function setUserPrefTopics(PDO $pdo, int $userId, array $topics, string $prefType = 'selected'): void
{
    $topicIdsByName = ensureTopicsExist($pdo, $topics);
    $topicIds = array_map(function($id) { return (int) $id; }, array_values($topicIdsByName));

    // delete old links and insert new ones
    $deleteStmt = $pdo->prepare("DELETE FROM user_pref_topic WHERE user_id = ?");
    $deleteStmt->execute([$userId]);

    if (count($topicIds) === 0) {
        return;
    }

    $insertStmt = $pdo->prepare("INSERT INTO user_pref_topic (user_id, topic_id, pref_type) VALUES (?, ?, ?)");
    foreach ($topicIds as $topicId) {
        $insertStmt->execute([$userId, $topicId, $prefType]);
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
                // ignore and continue
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

function setUserPrefSources(PDO $pdo, int $userId, array $sources, string $prefType = 'selected'): void
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
        INSERT INTO user_pref_source (user_id, source_id, pref_type)
        VALUES (?, ?, ?)
    ");

    foreach ($sourceIds as $sourceId) {
        $insertStmt->execute([$userId, $sourceId, $prefType]);
    }
}

function getStoredPhotoPath(int $userId): string
{
    $pattern = __DIR__ . "/uploads/profile_photos/user_" . $userId . "_*";
    $matches = glob($pattern);
    if (!is_array($matches) || count($matches) === 0) {
        return "";
    }

    usort($matches, function (string $a, string $b): int {
        return filemtime($b) <=> filemtime($a);
    });

    $latestFile = basename($matches[0]);
    return "uploads/profile_photos/" . $latestFile;
}

$accountData = getCurrentAccountData($pdo, $userId);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "update_photo") {
        if (!isset($_FILES["profile_photo"]) || !is_array($_FILES["profile_photo"])) {
            $feedback = "No file was uploaded.";
            $feedbackType = "danger";
        } elseif (($_FILES["profile_photo"]["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $feedback = "Unable to upload photo. Please try again.";
            $feedbackType = "danger";
        } elseif (($_FILES["profile_photo"]["size"] ?? 0) > 2 * 1024 * 1024) {
            $feedback = "Photo must be under 2MB.";
            $feedbackType = "danger";
        } else {
            $tmpPath = $_FILES["profile_photo"]["tmp_name"];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpPath);
            $allowedTypes = [
                "image/jpeg" => "jpg",
                "image/png" => "png",
                "image/webp" => "webp",
            ];

            if (!isset($allowedTypes[$mimeType])) {
                $feedback = "Please upload a JPG, PNG, or WEBP image.";
                $feedbackType = "danger";
            } else {
                $uploadDir = __DIR__ . "/uploads/profile_photos";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $filename = "user_" . $userId . "_" . bin2hex(random_bytes(8)) . "." . $allowedTypes[$mimeType];
                $destinationPath = $uploadDir . "/" . $filename;

                if (move_uploaded_file($tmpPath, $destinationPath)) {
                    $feedback = "Profile photo updated.";
                    $feedbackType = "success";
                } else {
                    $feedback = "Could not save the uploaded image.";
                    $feedbackType = "danger";
                }
            }
        }
    }

    if ($action === "update_preferences") {
        $section = $_POST["section"] ?? "";
        $preferences = $accountData["preferences"];

        if ($section === "countries") {
            $customCountriesInput = parseCustomList(trim($_POST["countries_custom"] ?? ""));
            $invalidCountries = [];
            foreach ($customCountriesInput as $country) {
                if (!in_array(strtoupper($country), $countriesOptions, true)) {
                    $invalidCountries[] = $country;
                }
            }
            if (!empty($invalidCountries)) {
                $feedback = "Invalid country code(s): " . implode(", ", $invalidCountries) . ". Please use valid ISO country codes.";
                $feedbackType = "danger";
            } else {
                $preferences["countries"] = array_values(array_unique(array_merge(
                    parsePostedList("countries"),
                    $customCountriesInput
                )));
            }
        } elseif ($section === "languages") {
            $customLanguagesInput = parseCustomList(trim($_POST["languages_custom"] ?? ""));
            $invalidLanguages = [];
            foreach ($customLanguagesInput as $language) {
                if (!in_array(ucfirst(strtolower($language)), $languageOptions, true)) {
                    $invalidLanguages[] = $language;
                }
            }
            if (!empty($invalidLanguages)) {
                $feedback = "Invalid language(s): " . implode(", ", $invalidLanguages) . ". Language is not supported. Please use valid language names.";
                $feedbackType = "danger";
            } else {
                $preferences["languages"] = array_values(array_unique(array_merge(
                    parsePostedList("languages"),
                    $customLanguagesInput
                )));
            }
        } elseif ($section === "sources") {
            $preferences["sources"] = array_values(array_unique(array_merge(
                parsePostedList("sources"),
                parseCustomList(trim($_POST["sources_custom"] ?? ""))
            )));

            try {
                setUserPrefSources($pdo, $userId, $preferences["sources"], 'selected');
            } catch (Throwable $e) {
                $feedback = "Could not save source preferences yet. " . $e->getMessage();
                $feedbackType = "danger";
            }
        } elseif ($section === "topics") {
            $preferences["topics"] = array_values(array_unique(array_merge(
                parsePostedList("topics"),
                parseCustomList(trim($_POST["topics_custom"] ?? ""))
            )));

            try {
                setUserPrefTopics($pdo, $userId, $preferences["topics"], 'selected');
            } catch (Throwable $e) {
                $feedback = "Could not save topic preferences yet. " . $e->getMessage();
                $feedbackType = "danger";
            }
        } else {
            $feedback = "Unknown preference section.";
            $feedbackType = "danger";
        }

        if ($feedbackType !== "danger") {
            $savePrefStmt = $pdo->prepare("
                INSERT INTO user_preferences (user_id, countries, languages, sources, topics)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    countries = VALUES(countries),
                    languages = VALUES(languages),
                    sources = VALUES(sources),
                    topics = VALUES(topics)
            ");
            $savePrefStmt->execute([
                $userId,
                json_encode($preferences["countries"]),
                json_encode($preferences["languages"]),
                json_encode($preferences["sources"]),
                json_encode($preferences["topics"]),
            ]);

            $feedback = "Preferences updated.";
            $feedbackType = "success";
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

$profilePhotoPath = trim(getStoredPhotoPath($userId));
$profilePhotoExists = $profilePhotoPath !== "" && file_exists(__DIR__ . "/" . $profilePhotoPath);
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
                        <?php if ($profilePhotoExists): ?>
                            <img class="profile-photo" src="<?php echo htmlspecialchars($profilePhotoPath); ?>" alt="Profile photo">
                        <?php else: ?>
                            <div class="profile-photo-placeholder"><?php echo htmlspecialchars($initial); ?></div>
                        <?php endif; ?>
                    </div>

                    <p class="profile-name"><?php echo htmlspecialchars($username); ?></p>

                    <form action="profile.php" method="POST" enctype="multipart/form-data" class="photo-form">
                        <input type="hidden" name="action" value="update_photo">
                        <label for="profile-photo-input" class="form-label mb-1">Edit photo</label>
                        <input id="profile-photo-input" type="file" name="profile_photo" class="form-control form-control-sm mb-2" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Save Photo</button>
                    </form>
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
                            <input type="text" name="sources_custom" class="form-control form-control-sm" placeholder="e.g. TechCrunch, Wired">
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
                            <?php if (count($customTopics) > 0): ?>
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
