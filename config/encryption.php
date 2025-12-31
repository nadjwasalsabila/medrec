<?php
function encryptData($data, $key) {
    if (empty($key)) {
        return null;
    }
    
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt(
        $data,
        'AES-256-CBC',
        hash('sha256', $key, true),
        OPENSSL_RAW_DATA,
        $iv
    );
    
    if ($encrypted === false) {
        return null;
    }
    
    return base64_encode($iv . $encrypted);
}

function decryptData($encrypted, $key) {
    if (empty($encrypted) || empty($key)) {
        return null;
    }
    
    $data = base64_decode($encrypted);
    if ($data === false) {
        return null;
    }
    
    $iv = substr($data, 0, 16);
    $ciphertext = substr($data, 16);
    
    $decrypted = openssl_decrypt(
        $ciphertext,
        'AES-256-CBC',
        hash('sha256', $key, true),
        OPENSSL_RAW_DATA,
        $iv
    );
    
    return $decrypted !== false ? $decrypted : null;
}

function generateKey() {
    return base64_encode(openssl_random_pseudo_bytes(32));
}
?>