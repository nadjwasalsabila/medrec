<?php
// config/encryption.php - VERSI DIPERBAIKI

// **KUNCI ENKRIPSI**
$encryption_keys = [
    'RS001' => 'key-rs001',
    'RS002' => 'key-rs002', 
    'RS003' => 'key-rs003'
];

// Fungsi untuk mendapatkan kunci berdasarkan RS - **INILAH YANG HILANG!**
function getHospitalKey($rs_code) {
    global $encryption_keys;
    
    // Bersihkan dan uppercase kode RS
    $rs_upper = strtoupper(trim($rs_code));
    
    // Jika kode RS ditemukan, kembalikan kuncinya
    if (isset($encryption_keys[$rs_upper])) {
        return $encryption_keys[$rs_upper];
    }
    
    // Fallback ke default key
    return 'default-key-12345';
}

// Enkripsi data
function encryptData($data, $key) {
    if (empty($data) || empty($key)) {
        return '';
    }
    
    // Jika data bukan string, encode ke JSON
    if (!is_string($data)) {
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($data === false) {
            return '';
        }
    }
    
    $method = 'AES-256-CBC';
    $iv_length = openssl_cipher_iv_length($method);
    
    // Generate initialization vector
    $iv = openssl_random_pseudo_bytes($iv_length);
    if ($iv === false) {
        return '';
    }
    
    // Encrypt data
    $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
    if ($encrypted === false) {
        return '';
    }
    
    // Gabungkan IV dengan encrypted data dan encode ke base64
    return base64_encode($iv . $encrypted);
}

// Dekripsi data  
function decryptData($encrypted_data, $key) {
    if (empty($encrypted_data) || empty($key)) {
        return '';
    }
    
    // Decode dari base64
    $data = base64_decode($encrypted_data);
    if ($data === false) {
        return '';
    }
    
    $method = 'AES-256-CBC';
    $iv_length = openssl_cipher_iv_length($method);
    
    // Pastikan data cukup panjang untuk IV
    if (strlen($data) < $iv_length) {
        return '';
    }
    
    // Pisahkan IV dari encrypted data
    $iv = substr($data, 0, $iv_length);
    $encrypted = substr($data, $iv_length);
    
    // Decrypt data
    $decrypted = openssl_decrypt($encrypted, $method, $key, OPENSSL_RAW_DATA, $iv);
    
    return $decrypted !== false ? $decrypted : '';
}

// **FUNGSI TAMBAHAN UNTUK VIEW-ONLY FILE**
function generateFilePreview($file_data) {
    if (!isset($file_data['file_type']) || !isset($file_data['file_data_base64'])) {
        return null;
    }
    
    $file_type = $file_data['file_type'];
    $base64_data = $file_data['file_data_base64'];
    
    // Tentukan tipe file dan generate preview sesuai
    if (strpos($file_type, 'image/') === 0) {
        // Preview gambar
        return '<img src="data:' . $file_type . ';base64,' . $base64_data . '" class="img-fluid" style="max-height: 400px;">';
    } elseif ($file_type == 'application/pdf') {
        // Preview PDF
        return '<iframe src="data:application/pdf;base64,' . $base64_data . '" class="w-100" style="height: 400px; border: none;"></iframe>';
    } elseif (strpos($file_type, 'text/') === 0) {
        // Preview text
        $text_content = base64_decode($base64_data);
        $preview_content = htmlspecialchars(substr($text_content, 0, 5000));
        if (strlen($text_content) > 5000) {
            $preview_content .= "\n\n... [File terlalu besar, hanya menampilkan 5000 karakter pertama]";
        }
        return '<pre class="bg-light p-3" style="max-height: 400px; overflow: auto;">' . $preview_content . '</pre>';
    }
    
    // File tidak support preview
    return '<div class="alert alert-warning">
                <i class="bi bi-file-earmark-x"></i>
                File tipe <strong>' . htmlspecialchars($file_type) . '</strong> tidak mendukung preview langsung.
            </div>';
}

// **FUNGSI UNTUK VALIDASI PERMINTAAN**
function validatePermintaanAccess($permintaan, $rs_kode) {
    // Pastikan permintaan milik RS yang login
    if ($permintaan['dari_rs'] != $rs_kode) {
        return ['success' => false, 'error' => 'Akses ditolak! Ini bukan permintaan Anda.'];
    }
    
    // Cek status
    if ($permintaan['status'] != 'diterima') {
        return ['success' => false, 'error' => 'Data belum dikirim oleh RS tujuan. Status: ' . $permintaan['status']];
    }
    
    // Cek expired
    $today = date('Y-m-d');
    $expired_date = $permintaan['tanggal_expired'] ?? '';
    if ($expired_date && $expired_date < $today) {
        return ['success' => false, 'error' => 'Akses data sudah expired sejak ' . date('d/m/Y', strtotime($expired_date))];
    }
    
    return ['success' => true];
}
?>