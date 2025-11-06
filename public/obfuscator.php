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
  <title>Code Obfuscator</title>
  
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
  width: 95vw; /* 95% of the viewport width */
  max-width: none; /* remove restriction */
  display: flex;
  flex-direction: column;
  align-items: center;
  margin: 0 auto;
}

/* Navigation Bar */
nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 40px;
    background-color: #e8f5e9;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    border-radius: 0 0 15px 15px;
}

nav .logo {
    font-size: 28px;
    font-weight: bold;
    color: #212121;
}

nav .nav-links {
    list-style: none;
    display: flex;
    gap: 25px;
}

nav .nav-links li a {
    text-decoration: none;
    color: #212121;
    font-weight: 500;
    transition: color 0.3s;
}

nav .nav-links li a:hover {
    color: #4caf50;
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
            <li><a href="index.html">Home</a></li>
            <li><a href="obfuscator.html">Obfuscate</a></li>
            <li><a href="deobfuscator.html">Deobfuscate</a></li>
            <li><a href="awareness.html">Learn & Protect</a></li>
            <li><a href="aboutpage.html">About</a></li>
        </ul>
    </nav>
  <h1>Code Obfuscator</h1>
  <form id="obfuscateForm" method="post" action="../api/obfuscate.php">

  <textarea id="inputCode" name="original_code" placeholder="Paste your code here..."></textarea>
  <input type="text" id="language" name="language" placeholder="Enter language (e.g. PHP)" 
  style="width:90%; padding:10px; margin:10px 0; border-radius:6px; border:1px solid #ccc; font-size:16px;">


 

  <div>
    <button type="submit">Obfuscate</button>
    <button type="button" onclick="clearText()">Clear</button>
  </div>
  <textarea id="outputCode" readonly placeholder="Obfuscated code will appear here..."></textarea>
  
  <input type="text" id="accessToken" readonly placeholder="Access token (keep this secret) will appear here..." 
  style="width:90%; padding:10px; margin:10px 0; border-radius:6px; border:1px solid #ccc; font-size:16px;">



  </form>
  <script>
  function clearText() {
  document.getElementById('inputCode').value = '';
  document.getElementById('outputCode').value = '';
  const lang = document.getElementById('language');
  if (lang) lang.value = '';
  const tokenField = document.getElementById('accessToken');
  if (tokenField) tokenField.value = '';
}


  document.getElementById('obfuscateForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const input = document.getElementById('inputCode').value;
    const language = document.getElementById('language').value;

    if (!input || !language) {
      alert('Please provide code and language.');
      return;
    }

    // build form data
    const formData = new FormData();
    formData.append('original_code', input);
    formData.append('language', language);

    try {
      const res = await fetch('../api/obfuscate.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
      });

      if (!res.ok) {
        const txt = await res.text();
        throw new Error(txt || 'Server error');
      }

      const data = await res.json();

      if (data.success) {
  // show base64 obfuscated blob in output textarea
  document.getElementById('outputCode').value = data.obfuscated_code || '';

  // show access_token to the user (obj_key is never exposed)
  const tokenField = document.getElementById('accessToken');
  if (tokenField) tokenField.value = String(data.access_token || '');

  // log only the token and method (do not log obj_key)
  console.log('access_token', data.access_token, 'method', data.method_used);
} else {
  alert('Error: ' + (data.error || 'Unknown error'));
}

    } catch (err) {
      console.error(err);
      alert('Obfuscation failed: ' + err.message);
    }
  });
</script>

</body>
</html>
