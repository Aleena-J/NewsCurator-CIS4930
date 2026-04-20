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
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/styles.css">
</head>
<body>

<?php require_once './components/navbar.php'; ?>

<div class="container mt-5" style="max-width: 450px;">
    <h2 class="mb-4">Create Account</h2>

    <?php
    // Handle errors from register_handler.php
    if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?php
            if ($_GET['error'] === 'empty') echo "Please fill in all fields.";
            elseif ($_GET['error'] === 'match') echo "Passwords do not match.";
            elseif ($_GET['error'] === 'exists') echo "Username already exists.";
            else echo "Registration failed.";
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            Account created successfully. You can now log in.
        </div>
    <?php endif; ?>

    <form id="registerForm" action="../backend/auth/register_handler.php" method="POST">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>

    <p class="mt-3 mb-0">
        Already have an account?
        <a href="login.php">Login here</a>
    </p>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$("#registerForm").on("submit", function(e) {
    const password = $("input[name='password']").val();
    const confirmPassword = $("input[name='confirm_password']").val();

    if (password !== confirmPassword) {
        e.preventDefault();
        alert("Passwords do not match.");
    }
});
</script>
</body>
</html>