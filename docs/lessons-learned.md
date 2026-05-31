# CodeCryptix – Lessons Learned

This document captures the key technical challenges faced during the development of CodeCryptix, focusing on real issues in obfuscation, deobfuscation, and system design.

---

## Challenge: Reversible Code Obfuscation Without Breaking Original Logic

### Problem

The system applies multiple transformations to user code during obfuscation:

- Variable renaming
- String encoding
- Dummy function injection
- Control-flow insertion

The challenge was ensuring that **all transformations could be reversed correctly** during deobfuscation.

If even one step was not perfectly reversible, the original code could be partially corrupted or incorrectly restored.

---

### Supporting Code

**File:** `obfuscate.php`

```php
// Variable renaming
$code = preg_replace(
    '/\b' . preg_quote($id, '/') . '\b/',
    $obf,
    $code
);

// String encoding
$code = preg_replace_callback(
    '/(["\'])(?:\\\\.|(?!\1).)*\1/s',
    fn($m) => '__STR__' . base64_encode(substr($m[0],1,-1)) . '__',
    $original
);
````

**File:** `deobfuscate.php`

```php
// Restore variable names
foreach ($map as $obf => $orig) {
    $code = preg_replace(
        '/\b' . preg_quote($obf, '/') . '\b/',
        $orig,
        $code
    );
}

// Restore strings
$code = preg_replace_callback(
    '/__STR__(.*?)__/',
    fn($m) => '"' . base64_decode($m[1]) . '"',
    $code
);
```

---

### Impact

* Small mistakes in transformation order could break output
* Debugging was difficult because multiple transformations overlapped
* Ensuring exact reversibility required careful tracking of all changes

---

### Lesson Learned

* Every obfuscation step must have a strict reverse operation
* Order of transformations is critical for correct restoration
* Maintaining a mapping between original and obfuscated identifiers is essential for reliability

---

## Challenge: Secure Key-Based Encryption for Code Storage

### Problem

Each obfuscated code is encrypted using a key derived from:

* User passkey
* Code-specific salt (`code_id`)
* PBKDF2 hashing

The challenge was that:

* Even a small passkey mismatch completely breaks decryption
* Lost passkey means permanent loss of code access
* Debugging failed decryptions is non-trivial

---

### Supporting Code

**File:** `obfuscate.php`

```php
$salt = hash('sha256', 'obf-salt-' . $code_id, true);
$key  = hash_pbkdf2('sha256', $password, $salt, 150000, 32, true);
```

**File:** `deobfuscate.php`

```php
$salt = hash('sha256', 'obf-salt-' . $code_id, true);
$key  = hash_pbkdf2('sha256', $password, $salt, 150000, 32, true);

$json = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
```

---

### Impact

* Decryption fails completely if passkey is incorrect
* No recovery mechanism for lost passkeys
* Errors are hard to debug due to encryption failure behavior

---

### Lesson Learned

* Strong encryption improves security but reduces usability
* Systems must clearly warn users about irreversible key loss
* Security features should be balanced with user experience

---

## Challenge: Using Database State to Track Obfuscated Code

### Problem

Deobfuscation relies on fetching the latest record:

* No direct reference ID from frontend
* Uses `ORDER BY timestamp DESC LIMIT 1`

This creates ambiguity when multiple obfuscations exist.

---

### Supporting Code

**File:** `deobfuscate.php`

```php
$res = pg_query_params(
    $conn,
    "SELECT o.code_id, o.obj_key, o.obfuscated_code
     FROM obfuscation o
     JOIN codesnippet c ON c.code_id = o.code_id
     WHERE c.user_id = $1
     ORDER BY o.timestamp DESC
     LIMIT 1",
    [$_SESSION['user_id']]
);
```

---

### Impact

* Only latest obfuscated code is retrieved
* Older versions are not directly accessible
* Hard to trace which input produced which output

---

### Lesson Learned

* Relying on “latest record” logic can lead to unclear system behavior
* Each obfuscation should ideally return a unique reference ID
* Explicit data flow is more reliable than implicit database ordering


