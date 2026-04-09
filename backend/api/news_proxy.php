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
        $params = [
            'token' => $apiKey,
            'sort' => 'crawled',
            'order' => 'desc',
            'highlight' => 'true',
        ];

        if (!empty($_GET['q'])) {
            $params['q'] = $_GET['q'];
        }
        if (!empty($_GET['country'])) {
            $params['country'] = $_GET['country'];
        }
        if (!empty($_GET['language'])) {
            $params['language'] = $_GET['language'];
        }
        if (!empty($_GET['category'])) {
            $params['category'] = $_GET['category'];
        }

        if (empty($params['q']) && empty($params['country']) && empty($params['language']) && empty($params['category'])) {
            $params['q'] = "performance_score:5";
        }

        $url = "https://api.webz.io/newsApiLite?" . http_build_query($params);
    }

    $response = file_get_contents($url);
    
    echo $response;
?>