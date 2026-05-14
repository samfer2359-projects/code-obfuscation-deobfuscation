# Codecryptix – Reversible Code Obfuscator & Deobfuscator

Codecryptix is a web-based application that enables users to obfuscate and deobfuscate source code using reversible techniques and a secure passkey system. It supports multiple programming languages and applies transformations such as identifier obfuscation, string shielding, and control-flow modification.

---

# Website UI

## Main Page
![Main UI](static/main.png)

## Signup Page
![Sign Up UI](static/signup.png)  

## Login Page
![Login UI](static/login.png)

## Home Dashboard
![Home UI](static/home.png)

## Obfuscation Pages
![Obfuscation UI 1](static/ob1.png)
### User enters code , language and passkey to obfuscate code
![Obfuscation UI 2](static/ob2.png)
### Result
![Obfuscation UI 3](static/ob3.png)

### Deobfuscation Pages
![Deobfuscation UI 1](static/deob1.png)
### User enters passkey to deobfuscate code
![Deobfuscation UI 2](static/deob2.png)
### Result
![Deobfuscation UI 3](static/deob3.png)

## Learn and Protect Page
![LP UI 1](static/lp1.png)
![LP UI 2](static/lp2.png)
![LP UI 3](static/lp3.png)

## About Page
![About UI 1](static/ab1.png)
![About UI 2](static/ab2.png)


---

## Features

- Multi-language support: JavaScript, Python, C  
- Reversible obfuscation using a user-defined passkey  
- Identifier and string protection  
- Control-flow transformation and dummy function injection  
- User authentication system for secure access  
- Web-based interface for easy code input and retrieval  

---

## Tech Stack

- Frontend: HTML, CSS, JavaScript  
- Backend: PHP  
- Database: PostgreSQL  
- Server: Apache  

---

## How It Works

### Obfuscation
1. User logs into the system  
2. Inputs source code and selects language  
3. Provides a passkey  
4. Code is transformed using obfuscation techniques  
5. Obfuscated output is generated and stored  

### Deobfuscation
1. User provides the correct passkey  
2. System reverses transformations  
3. Original code is restored  

---

## Setup Requirements

- Apache server with PHP 8.x  
- PostgreSQL database  
- Import schema from `database.sql`  

---

## Sample Test Code

```javascript
function greet(name) {
    let message = "Hello, " + name;
    console.log(message);
    return message;
}

let userName = "Alice";
greet(userName);
```
---

## Project Info / Credits

**Codecryptix** is a **group project** developed collaboratively by:

- Siddhi Kale
- Esha Gadekar
- Riddhi Chogale
- Samantha Fernandes

## Project Status

This project is currently configured for local development.  
A production deployment version is planned as part of future improvements.
