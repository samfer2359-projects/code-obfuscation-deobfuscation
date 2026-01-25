<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html?error=not_logged_in");
    exit;
}

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
  <title>Code Obfuscator</title>
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

  form {
  width: 95vw; 
  max-width: none; 
  display: flex;
  flex-direction: column;
  align-items: center;
  margin: 0 auto;
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

.pass-note {
  width: 90%;
  margin: 8px 0;
  padding: 10px 12px;
  border-left: 4px solid #4caf50;
  background: #f6fff6;
  color: #163b2a;
  border-radius: 6px;
  font-size: 14px;
  line-height: 1.4;
  box-sizing: border-box;
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

      
      <a href="welcome.php?logout=1" class="logout-btn" style="text-decoration:none;">Logout</a>

    </div>
  </nav>
  <br><br><br>
  <h1>Code Obfuscator</h1>
  <form id="obfuscateForm" method="post" action="obfuscate.php">

  <textarea id="inputCode" name="original_code" placeholder="Paste your code here..."></textarea>
  <input type="text" id="language" name="language" placeholder="Enter language">

  <div class="pass-note" role="note" aria-live="polite">
  <strong>Set a passkey you will remember:</strong>
  This passkey will be used to decrypt (deobfuscate) your code later. <em>Do not</em> use your account password — choose a separate passkey and save it securely. If you lose this passkey, the original code cannot be recovered.
</div>


<input type="password" id="userPassword" placeholder="Enter your passkey...">



 

  <div>
    <button type="submit">Obfuscate</button>

    <button type="button" onclick="clearText()">Clear</button>
  </div>
  <textarea id="outputCode" readonly placeholder="Obfuscated code will appear here..."></textarea>
  
  




  </form>

  <script>

  /*
    Client-side logic for code obfuscation:
    - Handles form submission via fetch
    - Sends code, language, and passkey to secure API
    - Displays obfuscated output
    - Manages session-based logout
  */




  function clearText() {
    const input = document.getElementById('inputCode');
    const output = document.getElementById('outputCode');
    const lang = document.getElementById('language');
    const pass = document.getElementById('userPassword');

    if (input) input.value = '';
    if (output) output.value = '';
    if (lang) lang.value = '';
    if (pass) pass.value = ''; 
  }

  document.getElementById('obfuscateForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const input = (document.getElementById('inputCode') || { value: '' }).value;
    const language = (document.getElementById('language') || { value: '' }).value;
    const password = (document.getElementById('userPassword') || { value: '' }).value;

    if (!input || !language) {
      alert('Please provide code and language.');
      return;
    }
    if (!password) {
      alert('Please enter your password.');
      return;
    }

   
    const formData = new FormData();
    formData.append('original_code', input);
    formData.append('language', language);
    formData.append('password', password);

   
    const submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    try {
      const res = await fetch('obfuscate.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
      });

      // Redirect to login if session has expired
      if (res.status === 401) {
        window.location.href = 'login.html?error=not_logged_in';
        return;
      }

     
      if (!res.ok) {
        const txt = await res.text();
        throw new Error(txt || 'Server error');
      }

      const data = await res.json();

      if (data.success) {
        
        const out = document.getElementById('outputCode');
        if (out) out.value = data.obfuscated_code || '';

        
        const passField = document.getElementById('userPassword');
        if (passField) passField.value = '';

        
        console.log('obfuscation method:', data.method_used || '(unknown)');
      } else {
        alert('Error: ' + (data.error || 'Unknown error'));
      }
    } catch (err) {
      console.error(err);
      alert('Obfuscation failed: ' + err.message);
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });
</script>


</body>
</html>
