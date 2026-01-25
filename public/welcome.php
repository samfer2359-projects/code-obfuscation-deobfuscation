<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html?error=not_logged_in');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    
    $_SESSION = array();

    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    
    session_destroy();

    
    header('Location: login.html?logged_out=1');
    exit;
}


$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome | Codecryptix</title>
  <link rel="stylesheet" href="nav.css">
  <link rel="stylesheet" href="welcome.css">
</head>
<body>
  <nav>
    <div class="logo">Codecryptix</div>
    <ul class="nav-links">
      <li><a href="welcome.php">Home</a></li>
      <li><a href="obfuscator.php">Obfuscate</a></li>
      <li><a href="deobfuscator.php">Deobfuscate</a></li>
      <li><a href="awareness.html">Learn & Protect</a></li>
      <li><a href="aboutpage.html">About</a></li>
    </ul>
    <div class="user-info">
      <span id="username">Welcome, <?php echo $username; ?>!</span>

      <a href="welcome.php?logout=1" class="logout-btn" style="text-decoration:none;">Logout</a>


    </div>
  </nav>

  <main class="welcome-section">
    <div class="welcome-box">
      
<h1>Hello, <span id="userNameDisplay"><?php echo $username; ?></span></h1>

      <p>Welcome to <strong>Codecryptix</strong> — your space to protect, transform, and explore code securely.</p>

      <div class="dashboard-buttons">
        <a href="obfuscator.php" class="dash-btn">🧩 Obfuscate Code</a>
        <a href="deobfuscator.php" class="dash-btn">🔓 Deobfuscate Code</a>
        <a href="awareness.html" class="dash-btn">🧠 Learn & Protect</a>
      </div>
    </div>
  </main>

  <footer>
    <p>© 2025 Codecryptix | Built with 💚 for creative coders</p>
  </footer>

</body>
</html>
