<?php
require_once "../config/db.php";

$username = trim($_POST["username"] ?? '');
$password = $_POST["password"] ?? '';
$confirmPassword = $_POST["confirm_password"] ?? '';

if ($username === '' || $password === '' || $confirmPassword === '') {
    header("Location: ../../frontend/register.php?error=empty");
    exit();
}

if ($password !== $confirmPassword) {
    header("Location: ../../frontend/register.php?error=match");
    exit();
}

// Check if username already exists
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->execute([$username]);
$existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingUser) {
    header("Location: ../../frontend/register.php?error=exists");
    exit();
}

// Hash the password before storing it
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
$stmt->execute([$username, $hashedPassword]);

header("Location: ../../frontend/register.php?success=1");
exit();
?>