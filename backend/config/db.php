<?php

require_once __DIR__ . '/db_config.php';

try {
    $pdo = new PDO(
		"mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
		$username,
		$password,
		[
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		]
	);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>