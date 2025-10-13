<?php
$s = "X927xveCSmn70GALXYPgHJxBCd+g37SY358DqDqAXyTR18nY6p7YtzlfYicWAURRpKwouCOz8JsMp1wSH+MEjZBGpTuArzdPhs0+kxd1pftrMY9edYjbZyjPEjGIA+GWLPIh4ydw4xc55JE6UXRlwP/Qo2hmHA==";

// Base64 decode to get raw binary (IV + ciphertext)
$data = base64_decode($s);

echo "After Decoding Base64: <br> $data <br><br>";

// Get IV length for AES-128-CTR
$iv_length = openssl_cipher_iv_length('AES-128-CTR');

// Extract IV from the beginning of data
$iv = substr($data, 0, $iv_length);

// Extract ciphertext (everything after IV)
$ciphertext = substr($data, $iv_length);

// Decrypt using raw data option (OPENSSL_RAW_DATA = 1)
$plain = openssl_decrypt($ciphertext, 'AES-128-CTR', 'TestKeyEncrypt16', OPENSSL_RAW_DATA, $iv);

echo "Decrypted Code:<br>$plain<br>";
?>
