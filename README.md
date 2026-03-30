# Codecryptix – Basic Reversible Code Obfuscator & Deobfuscator

**Codecryptix** is a **basic**, web-based code obfuscation and deobfuscation system. It allows users to obfuscate source code with reversible techniques and recover the original code using a passkey. The tool supports **JavaScript, Python, and C** and includes simple control-flow wrapping, dummy functions, string shielding, and identifier obfuscation.

**Note:** The obfuscation and deobfuscation logic was developed with guidance from AI tools to illustrate reversible code obfuscation concepts.

---

## Features

- **Multi-language support:** JavaScript, Python, C  
- **Reversible obfuscation:** Obfuscated code can be decrypted using a user-defined passkey  
- **Identifier & string protection:** Obfuscates variable/function names and shields string literals  
- **Control-flow & dummy functions:** Adds simple complexity to hinder casual reverse engineering  
- **User authentication:** Only registered users can obfuscate/deobfuscate code  
- **Safe recovery:** Original code is restored only with the correct passkey  
- **Web-based UI:** Easy-to-use interface for code submission and retrieval  

---

## Requirements

- Apache web server with PHP 8.x  
- PostgreSQL database  
- Database schema provided in `database.sql`  

---

## Usage

### Obfuscate Code
1. Login to your account.  
2. Go to **Obfuscator** (`obfuscator.php`).  
3. Paste your source code into the textarea.  
4. Enter the programming language (`Python`, `C`, or `JavaScript`).  
5. Enter a **passkey** (separate from your login password; save it securely).  
6. Click **Obfuscate** to generate the obfuscated code.  
7. Copy or save the obfuscated output for later use.  

### Deobfuscate Code
1. Login to your account.  
2. Go to **Deobfuscator** (`deobfuscator.php`).  
3. Enter the passkey used during obfuscation.  
4. Click **Deobfuscate**.  
5. The original source code will appear in a read-only textarea.  

---

## Test Code

You can use the `Test Code.txt` file to try obfuscation and deobfuscation. Example snippets included:

**JavaScript (JS)**
```javascript
function greet(name) {
    let message = "Hello, " + name;
    console.log(message);
    return message;
}
```

---

## Project Info / Credits

**Codecryptix** is a **group project** developed collaboratively by:

- Siddhi Kale  
- Esha Gadekar
- Riddhi Chogale
- Samantha Fernandes  

The core logic and design of the obfuscator/deobfuscator were implemented with **assistance from AI tools**.  
All contributors worked together on the frontend, backend, and database integration.

let userName = "Alice";
greet(userName);
