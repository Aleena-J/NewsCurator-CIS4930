<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $loggedIn = isset($_SESSION["user_id"]);
    $username = $loggedIn ? $_SESSION["username"] : "";
    $page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar-custom">
    <a class="brand" href="../frontend/dashboard.php">NewsCurator</a>

    <ul class="nav-links">
        <li class="nav-home"><a href="../frontend/dashboard.php" <?php if ($page == "../frontend/dashboard.php") echo 'class="active"'; ?>>Home</a></li>
        <li><a href="../frontend/search.php" <?php if ($page == "../frontend/search.php") echo 'class="active"'; ?>>Search</a></li>
    </ul>

    <div class="nav-right">
        <?php if ($loggedIn) { ?>
            <span class="nav-username">Hi, <?php echo htmlspecialchars($username); ?>!</span>
            <a href="../frontend/profile.php" class="btn-nav btn-nav-outline">Profile</a>
            <a href="../backend/auth/logout.php" class="btn-nav btn-nav-solid">Logout</a>
        <?php } else { ?>
            <a href="../frontend/login.php" class="btn-nav btn-nav-outline">Login</a>
            <a href="../frontend/register.php" class="btn-nav btn-nav-solid">Register</a>
        <?php } ?>
    </div>
</nav>