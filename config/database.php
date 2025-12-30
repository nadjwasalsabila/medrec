<?php
/* ===============================
   DATA RUMAH SAKIT
================================ */
$rumah_sakit = [
    'RS001' => [
        'password' => 'rs001pass',
        'nama'     => 'RS Umum Kota',
        'key'      => 'key-rs001'
    ],
    'RS002' => [
        'password' => 'rs002pass',
        'nama'     => 'RS Khusus Jantung',
        'key'      => 'key-rs002'
    ],
    'RS003' => [
        'password' => 'rs003pass',
        'nama'     => 'RS Ibu Anak',
        'key'      => 'key-rs003'
    ],
];

/* ===============================
   FUNGSI SIMULASI DATABASE (FILE-BASED)
================================ */
function getData($collection, $filter = '') {
    $file = "../data/{$collection}.json";
    
    if (!file_exists($file)) {
        return [];
    }
    
    $data = json_decode(file_get_contents($file), true) ?: [];
    
    // Filter data
    if (!empty($filter)) {
        $filtered = [];
        foreach ($data as $item) {
            $match = true;
            
            // Simple filter parser
            if (strpos($filter, 'AND') !== false) {
                $conditions = explode('AND', $filter);
                foreach ($conditions as $cond) {
                    $cond = trim($cond);
                    if (strpos($cond, '=') !== false) {
                        list($key, $value) = explode('=', $cond, 2);
                        $key = trim($key);
                        $value = trim($value, " '");
                        
                        if (!isset($item[$key]) || $item[$key] != $value) {
                            $match = false;
                            break;
                        }
                    }
                }
            } elseif (strpos($filter, '=') !== false) {
                list($key, $value) = explode('=', $filter, 2);
                $key = trim($key);
                $value = trim($value, " '");
                
                $match = isset($item[$key]) && $item[$key] == $value;
            }
            
            if ($match) {
                $filtered[] = $item;
            }
        }
        return $filtered;
    }
    
    return $data;
}

function createData($collection, $data) {
    $file = "../data/{$collection}.json";
    
    // Buat folder data jika belum ada
    if (!is_dir('../data')) {
        mkdir('../data', 0755, true);
    }
    
    // Generate ID unik
    $data['id'] = uniqid() . '_' . time();
    $data['created'] = date('Y-m-d H:i:s');
    $data['updated'] = date('Y-m-d H:i:s');
    
    // Baca data yang ada
    $existing = [];
    if (file_exists($file)) {
        $existing = json_decode(file_get_contents($file), true) ?: [];
    }
    
    // Tambah data baru
    $existing[] = $data;
    
    // Simpan ke file
    file_put_contents($file, json_encode($existing, JSON_PRETTY_PRINT));
    
    return ['success' => true, 'id' => $data['id']];
}

function updateData($collection, $id, $data) {
    $file = "../data/{$collection}.json";
    
    if (!file_exists($file)) {
        return ['success' => false, 'error' => 'File not found'];
    }
    
    $items = json_decode(file_get_contents($file), true) ?: [];
    $found = false;
    
    foreach ($items as &$item) {
        if ($item['id'] == $id) {
            $item = array_merge($item, $data);
            $item['updated'] = date('Y-m-d H:i:s');
            $found = true;
            break;
        }
    }
    
    if ($found) {
        file_put_contents($file, json_encode($items, JSON_PRETTY_PRINT));
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => 'Item not found'];
}

function deleteData($collection, $id) {
    $file = "../data/{$collection}.json";
    
    if (!file_exists($file)) {
        return ['success' => false, 'error' => 'File not found'];
    }
    
    $items = json_decode(file_get_contents($file), true) ?: [];
    $new_items = [];
    $found = false;
    
    foreach ($items as $item) {
        if ($item['id'] != $id) {
            $new_items[] = $item;
        } else {
            $found = true;
        }
    }
    
    if ($found) {
        file_put_contents($file, json_encode($new_items, JSON_PRETTY_PRINT));
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => 'Item not found'];
}

function getById($collection, $id) {
    $items = getData($collection);
    
    foreach ($items as $item) {
        if ($item['id'] == $id) {
            return $item;
        }
    }
    
    return null;
}

/* ===============================
   KUNCI RS (DIPAKAI ENCRYPT/DECRYPT)
================================ */
function getHospitalKey($rs_kode) {
    global $rumah_sakit;
    return $rumah_sakit[$rs_kode]['key'] ?? null;
}
?>