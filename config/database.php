<?php
// config/database.php - NO OUTPUT VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Data RS untuk login
$rumah_sakit = [
    'RS001' => ['password' => 'rs001pass', 'nama' => 'RS Umum Kota', 'key' => 'key-rs001'],
    'RS002' => ['password' => 'rs002pass', 'nama' => 'RS Khusus Jantung', 'key' => 'key-rs002'],
    'RS003' => ['password' => 'rs003pass', 'nama' => 'RS Ibu Anak', 'key' => 'key-rs003']
];

// File untuk menyimpan data
$data_files = [
    'permintaan' => __DIR__ . '/../data_permintaan.json',
    'histori' => __DIR__ . '/../data_histori.json'
];

// Buat file jika belum ada - TANPA ECHO
foreach ($data_files as $file) {
    if (!file_exists($file)) {
        @file_put_contents($file, '[]');
    }
}

// **FUNGSI GET DATA**
function getData($table, $filter = '', $sort = '', $limit = 1000) {
    global $data_files;
    
    if (!isset($data_files[$table])) {
        return [];
    }
    
    $content = @file_get_contents($data_files[$table]);
    if (!$content) {
        return [];
    }
    
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

// **FUNGSI CREATE DATA**  
function createData($table, $new_data) {
    global $data_files;
    
    if (!isset($data_files[$table])) {
        return ['success' => false, 'error' => 'Table not found'];
    }
    
    $content = @file_get_contents($data_files[$table]);
    $data = json_decode($content, true) ?: [];
    
    // Generate ID
    $new_id = 1;
    if (!empty($data)) {
        $ids = array_column($data, 'id');
        if (!empty($ids)) {
            $new_id = max($ids) + 1;
        }
    }
    
    $new_data['id'] = $new_id;
    $new_data['created_at'] = date('Y-m-d H:i:s');
    $data[] = $new_data;
    
    if (@file_put_contents($data_files[$table], json_encode($data, JSON_PRETTY_PRINT))) {
        return ['success' => true, 'id' => $new_id];
    }
    
    return ['success' => false, 'error' => 'Failed to save'];
}

// **FUNGSI UPDATE DATA**
function updateData($table, $id, $update_data) {
    global $data_files;
    
    if (!isset($data_files[$table])) {
        return ['success' => false, 'error' => 'Table not found'];
    }
    
    $content = @file_get_contents($data_files[$table]);
    $data = json_decode($content, true) ?: [];
    
    $updated = false;
    foreach ($data as &$item) {
        if (isset($item['id']) && $item['id'] == $id) {
            $item = array_merge($item, $update_data);
            $updated = true;
            break;
        }
    }
    
    if ($updated && @file_put_contents($data_files[$table], json_encode($data, JSON_PRETTY_PRINT))) {
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => 'Update failed'];
}

// **FUNGSI DELETE DATA**
function deleteData($table, $id) {
    global $data_files;
    
    if (!isset($data_files[$table])) {
        return ['success' => false, 'error' => 'Table not found'];
    }
    
    $content = @file_get_contents($data_files[$table]);
    $data = json_decode($content, true) ?: [];
    
    $new_data = [];
    $deleted = false;
    
    foreach ($data as $item) {
        if (isset($item['id']) && $item['id'] == $id) {
            $deleted = true;
        } else {
            $new_data[] = $item;
        }
    }
    
    if ($deleted && @file_put_contents($data_files[$table], json_encode($new_data, JSON_PRETTY_PRINT))) {
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => 'Delete failed'];
}
?>