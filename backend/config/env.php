<?php
$envPath = __DIR__ . '/../.env';

if (!file_exists($envPath)) {
    die("ERROR: .env file not found at " . $envPath);
}

$env = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($env as $line) {
    $line = trim($line);

    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }

    if (!str_contains($line, '=')) {
        continue;
    }

    list($key, $value) = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value);
}
?>