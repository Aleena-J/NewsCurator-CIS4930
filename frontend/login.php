<?php
    session_start();
    // redirect automatically to dashboard if user is logged in
    if (isset($_SESSION["user_id"])) {
        header("Location: ../frontend/dashboard.php");
        exit();
    }
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/styles.css">
</head>
<body>

<?php require_once './components/navbar.php'; ?>

<div class="container mt-5" style="max-width: 450px;">
    <h2 class="mb-4">Login</h2>

    <?php
    // Handle errors from login_handler.php
    if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?php
            if ($_GET['error'] === 'empty') echo "Please fill in all fields.";
            else echo "Invalid username or password.";
            ?>
        </div>
    <?php endif; ?>

    <form id="loginForm" action="../backend/auth/login_handler.php" method="POST">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="username" name="username" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-success w-100">Login</button>
    </form>

    <p class="mt-3 mb-0">
        Need an account?
        <a href="register.php">Register here</a>
    </p>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$("#loginForm").on("submit", function(e) {
    const username = $("input[name='username']").val().trim();
    const password = $("input[name='password']").val().trim();

    if (username === "" || password === "") {
        e.preventDefault();
        alert("Please fill in all fields.");
    }
});
</script>
</body>
</html>