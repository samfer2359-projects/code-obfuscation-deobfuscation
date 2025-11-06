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
  

  <h1>Code Deobfuscator</h1>

<input type="password" id="accessToken" placeholder="Enter your access token..." 
  style="width:90%; padding:10px; margin:10px 0; border-radius:6px; border:1px solid #ccc;">

<div>
  <button id="deobfuscateBtn" type="button">Deobfuscate</button>
  <button onclick="clearText()">Clear</button>
</div>

<textarea id="outputCode" readonly placeholder="Deobfuscated code will appear here..."></textarea>


  <script>
  function clearText() {
    document.getElementById('accessToken').value = '';
    document.getElementById('outputCode').value = '';
  }

  document.getElementById('deobfuscateBtn').addEventListener('click', async function () {
    const token = document.getElementById('accessToken').value.trim();
    if (!token) {
      alert('Please enter your access token.');
      return;
    }

    const formData = new FormData();
    formData.append('access_token', token);

    try {
      const res = await fetch('../api/deobfuscate.php', {
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
        document.getElementById('outputCode').value = data.deobfuscated_code;
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
