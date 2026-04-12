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
            'site_type' => 'news',
        ];

        if (!empty($_GET['q'])) {
            $params['q'] = rawurldecode($_GET['q']);
        }
        
        if (!empty($_GET['category'])) {
            $params['category'] = $_GET['category'];
        }

        if (empty($params['q']) && empty($params['country']) && empty($params['language']) && empty($params['category'])) {
            $params['q'] = "performance_score:>=3 domain_rank:<2000";
        }

        $query = http_build_query($params);

        $langs = $_GET['language'] ?? $_GET['language[]'] ?? [];
        if (!is_array($langs)) $langs = [$langs];
        foreach ($langs as $l) {
        $l = trim((string)$l);
        if ($l !== '') $query .= '&language=' . rawurlencode($l);
        }

        $countries = $_GET['country'] ?? $_GET['country[]'] ?? [];
        if (!is_array($countries)) $countries = [$countries];
        foreach ($countries as $c) {
        $c = trim((string)$c);
        if ($c !== '') $query .= '&country=' . rawurlencode($c);
        }

        $url = "https://api.webz.io/newsApiLite?" . $query;
        
    }

    $response = file_get_contents($url);
    $out = json_decode($response, true);
    if (!is_array($out)) {
    $out = ["_raw" => $response];
    }
    echo json_encode($out);
    exit;
?>