<?php
    require_once __DIR__ . '/../config/env.php';
    header('Content-Type: application/json');

    $apiKey = $_ENV['API_KEY'] ?? '';

    if (isset($_GET['next'])) {
        $next = $_GET['next'];
        if (strpos($next, 'http') === 0) {
            $url = $next;
        } else {
            $url = "https://api.webz.io" . $next;
        }
    } else {
        $query = isset($_GET['q']) ? $_GET['q'] : "performance_score:5";

        $url = "https://api.webz.io/newsApiLite"
            ."?token=" . $apiKey
            ."&q=" . urlencode($query)
            ."&sort=crawled&order=desc"
            ."&highlight=true";
    }

    $response = file_get_contents($url);
    
    echo $response;
?>