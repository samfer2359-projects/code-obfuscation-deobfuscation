<?php
// Protect page: only accessible to authenticated users
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html?error=not_logged_in");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header("Location: login.html?logged_out=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Code Deobfuscator</title>
  <link rel="stylesheet" href="nav.css">
  <link rel="stylesheet" href="welcome.css">
  <style>
  body {
    background-color: #fffdf7;
    color: #212121;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    padding: 20px;
  }

  h1 {
    color: #212121;
    margin-bottom: 15px;
  }

  textarea {
    width: 90%;
    height: 200px;
    margin: 10px 0;
    padding: 12px;
    border: 2px solid #6b7280;
    border-radius: 8px;
    font-family: monospace;
    font-size: 16px;
    background: #ffffff;
    color: #212121;
    transition: border 0.3s ease;
  }

  textarea:focus {
    outline: none;
    border: 2px solid #4caf50;
  }

  input[type="text"],
input[type="password"] {
  width: 90%;
  padding: 12px;
  margin: 10px 0;
  border: 2px solid #6b7280;
  border-radius: 8px;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  font-size: 16px;
  background: #ffffff;
  color: #212121;
  transition: border 0.3s ease;
}

input[type="text"]:focus,
input[type="password"]:focus {
  outline: none;
  border: 2px solid #4caf50;
}


  button {
    background-color: #4caf50;
    color: #fff;
    border: none;
    padding: 12px 24px;
    margin: 8px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s ease;
  }

  button:hover {
    background-color: #388e3c;
  }
</style>

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
    <span class="username-display">
        Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
    </span>
    <a href="deobfuscator.php?logout=1" class="logout-btn" style="text-decoration:none;">Logout</a>
    </div>

  </nav>
  <br><br><br>
  <h1>Code Deobfuscator</h1>

<input type="password" id="userPassword" placeholder="Enter your passkey...">


<div>
  <button id="deobfuscateBtn" type="button">Deobfuscate</button>
  <button onclick="clearText()">Clear</button>
</div>

<textarea id="outputCode" readonly placeholder="Deobfuscated code will appear here..."></textarea>


  <script>
  
  /*
    Client-side logic for deobfuscation:
    - Displays logged-in username
    - Handles logout
    - Sends password to secure deobfuscation API
    - Displays restored source code
  */

  
 

  function clearText() {
    const pwd = document.getElementById('userPassword');
    if (pwd) pwd.value = '';
    const out = document.getElementById('outputCode');
    if (out) out.value = '';
  }

  document.getElementById('deobfuscateBtn').addEventListener('click', async function () {
    const password = (document.getElementById('userPassword') || { value: '' }).value.trim();
    if (!password) {
      alert('Please enter your password.');
      return;
    }

    const formData = new FormData();
    formData.append('password', password);

    try {
      
      const fetchUrl = './deobfuscate.php'; 

      console.log('Posting to', fetchUrl);

      // Send password to server-side deobfuscation endpoint
      const res = await fetch(fetchUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
      });

      
      // Redirect to login if session has expired
      if (res.status === 401) {
        window.location.href = 'login.html?error=not_logged_in';
        return;
      }

      
      const text = await res.text();

      
      let data;
      try {
        data = JSON.parse(text);
      } catch (err) {
        console.error('Server returned non-JSON response:', text);
        alert('Deobfuscation failed: server returned invalid response (check console).');
        return;
      }

      if (!res.ok) {
        console.error('Server error response:', data);
        alert('Error: ' + (data.error || 'Server returned an error'));
        return;
      }

      if (data.success) {
        document.getElementById('outputCode').value = data.deobfuscated_code || '';
      } else {
        alert('Error: ' + (data.error || 'Unknown error'));
      }
    } catch (err) {
      console.error(err);
      alert('Deobfuscation failed: ' + err.message);
    }
  });
</script>




</body>
</html>
