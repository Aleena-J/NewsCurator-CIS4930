<?php
require_once "../config/db.php";

$username = trim($_POST["username"] ?? '');
$email = trim($_POST["email"] ?? '');
$password = $_POST["password"] ?? '';
$confirmPassword = $_POST["confirm_password"] ?? '';

if ($username === '' || $email === '' || $password === '' || $confirmPassword === '') {
    header("Location: ../../frontend/register.php?error=empty");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../../frontend/register.php?error=email");
    exit();
}

if ($password !== $confirmPassword) {
    header("Location: ../../frontend/register.php?error=match");
    exit();
}

// Check if username or email already exists
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
$existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingUser) {
    header("Location: ../../frontend/register.php?error=exists");
    exit();
}

// Hash the password before storing it
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
$stmt->execute([$username, $email, $hashedPassword]);

header("Location: ../../frontend/register.php?success=1");
exit();
?>