<?php
// test_encryption.php - Debug tool untuk cek enkripsi

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔐 Test Encryption</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .card { margin-bottom: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1 class='mb-4'>🔐 Test Enkripsi Sistem</h1>";

// Load configs
require_once 'config/database.php';
require_once 'config/encryption.php';

echo "<div class='card'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>1. Test Kunci Enkripsi per RS</h5>
    </div>
    <div class='card-body'>
        <table class='table table-bordered'>
            <thead>
                <tr>
                    <th>RS Code</th>
                    <th>Key Found</th>
                    <th>Key Value (first 20 chars)</th>
                </tr>
            </thead>
            <tbody>";

$rs_list = ['RS001', 'RS002', 'RS003'];
foreach($rs_list as $rs) {
    $key = getHospitalKey($rs);
    $found = !empty($key);
    echo "<tr>
            <td>$rs</td>
            <td class='" . ($found ? 'success' : 'error') . "'>" . ($found ? '✅ DITEMUKAN' : '❌ TIDAK DITEMUKAN') . "</td>
            <td><code>" . substr($key, 0, 20) . "...</code></td>
          </tr>";
}

echo "</tbody></table></div></div>";

echo "<div class='card'>
    <div class='card-header bg-info text-white'>
        <h5 class='mb-0'>2. Test Encrypt/Decrypt</h5>
    </div>
    <div class='card-body'>";

// Test 1: Encrypt dengan key RS001, decrypt dengan key RS001
$test_data = json_encode(['test' => 'Hello World', 'timestamp' => date('Y-m-d H:i:s'), 'number' => 12345]);
$test_key = getHospitalKey('RS001');

echo "<h6>Test dengan RS001 key:</h6>";
echo "<p><strong>Original Data:</strong> " . htmlspecialchars($test_data) . "</p>";

$encrypted = encryptData($test_data, $test_key);
echo "<p><strong>Encrypted (first 100 chars):</strong><br><code>" . substr($encrypted, 0, 100) . "...</code></p>";

$decrypted = decryptData($encrypted, $test_key);
echo "<p><strong>Decrypted:</strong> " . htmlspecialchars($decrypted) . "</p>";

$match = ($decrypted == $test_data);
echo "<p class='" . ($match ? 'success' : 'error') . "'>✅ Decrypt " . ($match ? 'BERHASIL' : 'GAGAL') . "</p>";

// Test 2: Coba dengan key yang salah
echo "<hr><h6>Test dengan key yang salah:</h6>";
$wrong_key = 'wrong-password-123';
$decrypted_wrong = decryptData($encrypted, $wrong_key);
echo "<p><strong>Decrypt dengan key salah:</strong> " . ($decrypted_wrong ? 'ADA DATA' : 'KOSONG') . "</p>";

echo "</div></div>";

echo "<div class='card'>
    <div class='card-header bg-warning'>
        <h5 class='mb-0'>3. Cek Data di Database</h5>
    </div>
    <div class='card-body'>";

// Ambil data dari JSON
$permintaan_data = getData('permintaan', '', 'id', 10);

if(empty($permintaan_data)) {
    echo "<p class='error'>❌ Tidak ada data permintaan</p>";
} else {
    echo "<table class='table table-bordered'>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Dari RS</th>
                    <th>Ke RS</th>
                    <th>Status</th>
                    <th>Data Dikirim</th>
                    <th>Panjang Data</th>
                </tr>
            </thead>
            <tbody>";
    
    foreach($permintaan_data as $row) {
        $has_data = !empty($row['data_dikirim']);
        $data_length = $has_data ? strlen($row['data_dikirim']) : 0;
        
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['dari_rs']}</td>
                <td>{$row['ke_rs']}</td>
                <td><span class='badge bg-" . ($row['status'] == 'diterima' ? 'success' : 'warning') . "'>{$row['status']}</span></td>
                <td>" . ($has_data ? '✅ ADA' : '❌ TIDAK ADA') . "</td>
                <td>{$data_length} chars</td>
              </tr>";
    }
    
    echo "</tbody></table>";
}

echo "</div></div>";

echo "<div class='card'>
    <div class='card-header bg-danger text-white'>
        <h5 class='mb-0'>4. Manual Decrypt Test</h5>
    </div>
    <div class='card-body'>
        <form method='post' class='mb-4'>
            <div class='row'>
                <div class='col-md-4'>
                    <label>ID Permintaan:</label>
                    <input type='number' name='test_id' class='form-control' placeholder='Contoh: 2'>
                </div>
                <div class='col-md-4'>
                    <label>RS Code (penerima):</label>
                    <select name='rs_code' class='form-control'>
                        <option value='RS001'>RS001</option>
                        <option value='RS002'>RS002</option>
                        <option value='RS003'>RS003</option>
                    </select>
                </div>
                <div class='col-md-4'>
                    <label>&nbsp;</label><br>
                    <button type='submit' name='test_decrypt' class='btn btn-primary'>Test Decrypt</button>
                </div>
            </div>
        </form>";
        
if(isset($_POST['test_decrypt']) && !empty($_POST['test_id'])) {
    $test_id = $_POST['test_id'];
    $test_rs = $_POST['rs_code'];
    
    echo "<h6>Testing Decrypt for ID: $test_id, RS: $test_rs</h6>";
    
    $data = getData('permintaan', "id = '$test_id'", '', 1);
    
    if(empty($data)) {
        echo "<p class='error'>❌ Data tidak ditemukan</p>";
    } else {
        $row = $data[0];
        echo "<p><strong>Dari RS:</strong> {$row['dari_rs']}</p>";
        echo "<p><strong>Ke RS:</strong> {$row['ke_rs']}</p>";
        echo "<p><strong>Status:</strong> {$row['status']}</p>";
        
        if(empty($row['data_dikirim'])) {
            echo "<p class='error'>❌ Tidak ada data_dikirim</p>";
        } else {
            echo "<p><strong>Data length:</strong> " . strlen($row['data_dikirim']) . " chars</p>";
            echo "<p><strong>Data preview (first 200 chars):</strong></p>
                  <pre>" . htmlspecialchars(substr($row['data_dikirim'], 0, 200)) . "</pre>";
            
            // Coba decrypt dengan kunci RS yang diminta
            $test_key = getHospitalKey($test_rs);
            echo "<p><strong>Key yang digunakan:</strong> " . substr($test_key, 0, 20) . "...</p>";
            
            $decrypted = decryptData($row['data_dikirim'], $test_key);
            
            if(empty($decrypted)) {
                echo "<p class='error'>❌ Gagal decrypt dengan key {$test_rs}</p>";
                
                // Coba semua kunci
                echo "<p><strong>Coba semua kunci:</strong></p>";
                $all_keys = ['RS001', 'RS002', 'RS003'];
                foreach($all_keys as $rs) {
                    $key = getHospitalKey($rs);
                    $decrypted_test = decryptData($row['data_dikirim'], $key);
                    if(!empty($decrypted_test)) {
                        echo "<p class='success'>✅ Berhasil dengan key {$rs}!</p>";
                        echo "<pre>" . htmlspecialchars($decrypted_test) . "</pre>";
                        break;
                    }
                }
            } else {
                echo "<p class='success'>✅ Berhasil decrypt!</p>";
                echo "<pre>" . htmlspecialchars($decrypted) . "</pre>";
                
                // Coba parse JSON
                $json_data = json_decode($decrypted, true);
                if($json_data && is_array($json_data)) {
                    echo "<p class='success'>✅ JSON valid!</p>";
                    echo "<pre>" . htmlspecialchars(print_r($json_data, true)) . "</pre>";
                } else {
                    echo "<p class='error'>❌ Bukan JSON valid</p>";
                }
            }
        }
    }
}

echo "</div></div>";

echo "<div class='card'>
    <div class='card-header bg-success text-white'>
        <h5 class='mb-0'>5. Solusi Quick Fix</h5>
    </div>
    <div class='card-body'>
        <h6>Jika decrypt gagal, coba:</h6>
        <ol>
            <li>Pastikan RS pengirim menggunakan kunci yang benar</li>
            <li>RS pengirim harus encrypt dengan kunci RS PENERIMA, bukan kunci sendiri</li>
            <li>Cek file <code>config/encryption.php</code> - kunci harus sama dengan di <code>config/database.php</code></li>
            <li>Jika perlu, minta RS pengirim kirim ulang data</li>
        </ol>
        
        <h6 class='mt-4'>Kunci yang seharusnya:</h6>
        <ul>
            <li>RS001: <code>key-rs001</code></li>
            <li>RS002: <code>key-rs002</code></li>
            <li>RS003: <code>key-rs003</code></li>
        </ul>
    </div>
</div>";

echo "</div></body></html>";