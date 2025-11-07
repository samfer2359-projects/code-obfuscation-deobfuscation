<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html?error=not_logged_in");
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
      <span id="username">Welcome, User!</span>
      <button class="logout-btn">Logout</button>
    </div>
  </nav>
  <br><br><br>
  <h1>Code Deobfuscator</h1>

<input type="password" id="userPassword" placeholder="Enter your password...">


<div>
  <button id="deobfuscateBtn" type="button">Deobfuscate</button>
  <button onclick="clearText()">Clear</button>
</div>

<textarea id="outputCode" readonly placeholder="Deobfuscated code will appear here..."></textarea>


  <script>
  // Display stored username (guarded)
  const username = localStorage.getItem("username") || "User";
  const usernameElem = document.getElementById("username");
  if (usernameElem) usernameElem.textContent = `Welcome, ${username}!`;
  // Only set userNameDisplay if that element exists
  const userNameDisplayElem = document.getElementById("userNameDisplay");
  if (userNameDisplayElem) userNameDisplayElem.textContent = username;

  // Logout functionality (safe guard)
  const logoutBtn = document.querySelector(".logout-btn");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", () => {
      localStorage.removeItem("username");
      // optionally call server logout endpoint if you have one
      window.location.href = "login.html";
    });
  }

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
      // IMPORTANT: choose the correct URL below:
      // If your api file is in the same public folder use './deobfuscate.php'
      // If it's in a separate api folder use '/dynamic-code-obfuscation-deobfuscation/api/deobfuscate.php'
      const fetchUrl = './deobfuscate.php'; // ← change this if your file is elsewhere

      console.log('Posting to', fetchUrl);
      const res = await fetch(fetchUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
      });

      // redirect to login if server says unauthorized
      if (res.status === 401) {
        window.location.href = 'login.html?error=not_logged_in';
        return;
      }

      // read raw text for safer debugging
      const text = await res.text();

      // try parse JSON
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
