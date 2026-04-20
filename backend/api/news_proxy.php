<?php
    require_once __DIR__ . '/../config/env.php';
    header('Content-Type: application/json');

    //Get stored api credentials in server-side env variable
    $apiKey = $_ENV['API_KEY'] ?? '';

    if (isset($_GET['next'])) {
        //Pagination requests use the 'next' parameter in the API
        $next = $_GET['next'];
        if (strpos($next, 'http') === 0) {
            $url = $next;
        } else {
            $url = "https://api.webz.io" . $next;
        }
    } else {
        //Base query
        //highlight snippets, newest-first ordering
        $params = [
            'token' => $apiKey,
            'sort' => 'crawled',
            'order' => 'desc',
            'highlight' => 'true',
        ];

        if (!empty($_GET['q'])) {
            // q can contain special characters, preserve them to be encoded later (>=, =<, ...)
            $params['q'] = rawurldecode($_GET['q']);
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

        //If no meaningful filters were given, fall back to default
        if (empty($params['q']) && empty($params['country']) && empty($params['language']) && empty($params['category'])) {
            $params['q'] = "performance_score:>=3 domain_rank:<2000";
        }

        //Build final API call URL
        $url = "https://api.webz.io/newsApiLite?" . http_build_query($params);
    }

    $response = file_get_contents($url);
    
    echo $response;
?>