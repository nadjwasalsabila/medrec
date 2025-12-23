<?php
// config/encryption.php - NO OUTPUT VERSION

// **KUNCI ENKRIPSI**
$encryption_keys = [
    'RS001' => 'key-rs001',
    'RS002' => 'key-rs002', 
    'RS003' => 'key-rs003'
];

// Fungsi untuk mendapatkan kunci berdasarkan RS
function getHospitalKey($rs_code) {
    global $encryption_keys;
    
    $rs_upper = strtoupper(trim($rs_code));
    
    return $encryption_keys[$rs_upper] ?? 'default-key-12345';
}

// Enkripsi data
function encryptData($data, $key) {
    if (empty($data) || empty($key)) {
        return '';
    }
    
    if (!is_string($data)) {
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    $method = 'AES-256-CBC';
    $iv_length = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($iv_length);
    
    $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
    
    if ($encrypted === false) {
        return '';
    }
    
    return base64_encode($iv . $encrypted);
}

// Dekripsi data  
function decryptData($encrypted_data, $key) {
    if (empty($encrypted_data) || empty($key)) {
        return '';
    }
    
    $data = base64_decode($encrypted_data);
    if ($data === false) {
        return '';
    }
    
    $method = 'AES-256-CBC';
    $iv_length = openssl_cipher_iv_length($method);
    
    if (strlen($data) < $iv_length) {
        return '';
    }
    
    $iv = substr($data, 0, $iv_length);
    $encrypted = substr($data, $iv_length);
    
    $decrypted = openssl_decrypt($encrypted, $method, $key, OPENSSL_RAW_DATA, $iv);
    
    return $decrypted !== false ? $decrypted : '';
}

// Enkripsi file (binary)
function encryptFile($data, $key) {
    if (empty($data) || empty($key)) {
        return '';
    }
    
    $method = 'AES-256-CBC';
    $iv_length = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($iv_length);
    
    $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
    
    if ($encrypted === false) {
        return '';
    }
    
    return $iv . $encrypted;
}

// Dekripsi file
function decryptFile($encrypted_data, $key) {
    if (empty($encrypted_data) || empty($key)) {
        return '';
    }
    
    $method = 'AES-256-CBC';
    $iv_length = openssl_cipher_iv_length($method);
    
    if (strlen($encrypted_data) < $iv_length) {
        return '';
    }
    
    $iv = substr($encrypted_data, 0, $iv_length);
    $encrypted = substr($encrypted_data, $iv_length);
    
    $decrypted = openssl_decrypt($encrypted, $method, $key, OPENSSL_RAW_DATA, $iv);
    
    return $decrypted !== false ? $decrypted : '';
}

// Fungsi decrypt khusus untuk RS penerima
function decryptForRecipient($encrypted_data, $sender_rs_code, $recipient_rs_code) {
    if (empty($encrypted_data)) {
        return '';
    }
    
    // Coba dengan kunci penerima dulu (paling umum)
    $recipient_key = getHospitalKey($recipient_rs_code);
    $decrypted = decryptData($encrypted_data, $recipient_key);
    
    if (!empty($decrypted)) {
        $data = json_decode($decrypted, true);
        if ($data && is_array($data)) {
            return $decrypted;
        }
    }
    
    // Jika gagal, coba dengan kunci pengirim
    $sender_key = getHospitalKey($sender_rs_code);
    $decrypted = decryptData($encrypted_data, $sender_key);
    
    if (!empty($decrypted)) {
        $data = json_decode($decrypted, true);
        if ($data && is_array($data)) {
            return $decrypted;
        }
    }
    
    return '';
}

// Fungsi untuk melihat file (view-only, no download)
function viewFileOnly($file_data_base64, $file_type, $original_name) {
    if (empty($file_data_base64)) {
        return ['success' => false, 'error' => 'File data kosong'];
    }
    
    $decoded = base64_decode($file_data_base64);
    if ($decoded === false) {
        return ['success' => false, 'error' => 'File data tidak valid'];
    }
    
    return [
        'success' => true,
        'file_type' => $file_type,
        'original_name' => $original_name,
        'data' => $decoded
    ];
}
?>