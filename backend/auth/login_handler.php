<?php
session_start();
require_once "../config/db.php";

$username = trim($_POST["username"] ?? '');
$password = $_POST["password"] ?? '';

if ($username === '' || $password === '') {
    header("Location: ../../frontend/login.php?error=empty");
    exit();
}

$stmt = $pdo->prepare("SELECT user_id, username, password FROM users WHERE username = ?");
$stmt->execute([$username]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user["password"])) {
    $_SESSION["user_id"] = $user["user_id"];
    $_SESSION["username"] = $user["username"];

    header("Location: ../../frontend/dashboard.php");
    exit();
} else {
    header("Location: ../../frontend/login.php?error=invalid");
    exit();
}
?>