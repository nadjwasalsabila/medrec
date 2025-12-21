<?php
session_start();
if(!isset($_SESSION['rs_kode'])){
    header('Location: ../login.php');
    exit;
}

$rs_kode = $_SESSION['rs_kode'];
$rs_nama = $_SESSION['rs_nama'];

// Include config
require_once '../config/database.php';
require_once '../config/encryption.php';

$error = '';
$success = '';

// Proses form jika disubmit
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    
    $new_permintaan = [ // PERBAIKAN: Definisikan variabel $new_permintaan
        'dari_rs' => $rs_kode,
        'ke_rs' => $_POST['ke_rs'],
        'pasien_nik' => $_POST['nik'],
        'pasien_nama' => $_POST['nama'],
        'urgensi' => $_POST['urgensi'],
        'keterangan' => $_POST['keterangan'],
        'status' => 'pending',
        'token_akses' => bin2hex(random_bytes(16)), // Token unik
        'tanggal_permintaan' => date('Y-m-d'),
        'tanggal_expired' => date('Y-m-d', strtotime('+14 days'))
    ];
    
    // DEBUG: Tampilkan data yang akan dikirim
    error_log("=== AJUKAN.PHP DEBUG ===");
    error_log("Data to insert: " . json_encode($new_permintaan));

    $result = createData('permintaan', $new_permintaan);

    error_log("Create result: " . json_encode($result));

    if($result['success']){
        // Log histori
        createData('histori', [
            'permintaan_id' => $result['id'],
            'rs_id' => $rs_kode,
            'aksi' => 'mengajukan',
            'keterangan' => 'Permintaan data pasien ' . $_POST['nama'] . ' ke ' . $_POST['ke_rs'],
            'waktu' => date('Y-m-d H:i:s')
        ]);
        
        $success = "✅ Permintaan berhasil dikirim! ID: " . $result['id'];
        
    } else {
        // Tampilkan error detail
        $error = "❌ Gagal mengirim permintaan: " . ($result['error'] ?? 'Unknown error');
        error_log("Ajukan error: " . $error);
    }
}

// Ambil histori permintaan
$histori = getData('permintaan', "dari_rs = '$rs_kode'", '-id', 10);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ajukan Permintaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .urgent { color: #dc3545; font-weight: bold; }
        .biasa { color: #ffc107; }
        .tidak-urgent { color: #0dcaf0; }
        .badge-urgent { background: #dc3545; }
        .badge-biasa { background: #ffc107; color: #000; }
        .badge-tidak { background: #0dcaf0; }
        .debug-info { 
            background: #f8f9fa; 
            border-left: 4px solid #dc3545; 
            padding: 10px; 
            margin: 10px 0;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <!-- TAMBAHKAN INI: Include sidebar -->
    <?php include '../components/sidebar.php'; ?>
    
    <!-- TAMBAHKAN INI: Bungkus konten dengan main-content -->
    <div class="main-content">
        <div class="container mt-4">
            
            <h3><i class="bi bi-send"></i> Ajukan Permintaan Data</h3>
            <p class="text-muted">Dari: <strong><?php echo $rs_nama; ?></strong> (<?php echo $rs_kode; ?>)</p>
            
            <?php 
            // Tampilkan debug info jika ada
            if(isset($result) && !isset($success)): 
            ?>
            <div class="debug-info">
                <strong>Debug Info:</strong><br>
                <pre style="font-size: 0.8em;"><?php echo htmlspecialchars(print_r($result, true)); ?></pre>
            </div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <div class="mt-2">
                    <a href="ajukan.php" class="btn btn-sm btn-outline-success">Ajukan Lagi</a>
                    <a href="../dashboard.php" class="btn btn-sm btn-outline-primary">Ke Dashboard</a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>Error:</strong> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <div class="mt-2">
                    <a href="test_connection.php" class="btn btn-sm btn-outline-danger">Test Connection</a>
                    <button onclick="location.reload()" class="btn btn-sm btn-outline-warning">Refresh</button>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-clipboard-plus"></i> Form Pengajuan
                </div>
                <div class="card-body">
                    <form method="POST" id="formAjukan" onsubmit="return confirmSubmit()">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">RS Tujuan <span class="text-danger">*</span></label>
                                    <select name="ke_rs" class="form-select" required>
                                        <option value="">-- Pilih Rumah Sakit --</option>
                                        <?php 
                                        $rs_list = ['RS001', 'RS002', 'RS003'];
                                        foreach($rs_list as $rs){
                                            if($rs != $rs_kode){
                                                $selected = ($rs == ($_POST['ke_rs'] ?? '')) ? 'selected' : '';
                                                echo "<option value='$rs' $selected>$rs</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <div class="form-text">Pilih rumah sakit tujuan</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">NIK Pasien <span class="text-danger">*</span></label>
                                    <input type="text" name="nik" class="form-control" 
                                           value="<?php echo $_POST['nik'] ?? ''; ?>" 
                                           required
                                           pattern="[0-9]{16}"
                                           maxlength="16"
                                           placeholder="16 digit NIK (contoh: 3374065612050002)">
                                    <div class="form-text">Harus 16 digit angka</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Nama Pasien <span class="text-danger">*</span></label>
                                    <input type="text" name="nama" class="form-control" 
                                           value="<?php echo $_POST['nama'] ?? ''; ?>" 
                                           required
                                           placeholder="Nama lengkap pasien">
                                    <div class="form-text">Nama lengkap pasien sesuai KTP</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tingkat Urgensi <span class="text-danger">*</span></label>
                                    <select name="urgensi" class="form-select" required>
                                        <option value="urgent" class="urgent" 
                                                <?php echo ($_POST['urgensi'] ?? '') == 'urgent' ? 'selected' : ''; ?>>
                                            🚨 URGENT (Ditangani segera - max 2 jam)
                                        </option>
                                        <option value="biasa" 
                                                <?php echo ($_POST['urgensi'] ?? 'biasa') == 'biasa' ? 'selected' : ''; ?>>
                                            🟡 BIASA (1-2 hari kerja)
                                        </option>
                                        <option value="tidak_urgent"
                                                <?php echo ($_POST['urgensi'] ?? '') == 'tidak_urgent' ? 'selected' : ''; ?>>
                                            🔵 TIDAK URGENT (3-7 hari)
                                        </option>
                                    </select>
                                    <div class="form-text">Pilih tingkat urgensi berdasarkan kebutuhan</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Keterangan / Alasan <span class="text-danger">*</span></label>
                                    <textarea name="keterangan" class="form-control" rows="4" 
                                              placeholder="Contoh: Pasien akan operasi jantung, butuh riwayat alergi dan penyakit sebelumnya..."
                                              required><?php echo $_POST['keterangan'] ?? ''; ?></textarea>
                                    <div class="form-text">Jelaskan alasan permintaan data secara detail</div>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Perhatian Penting:</strong><br>
                                    1. Data akan <strong>expired dalam waktu tertentu</strong> setelah dikirim oleh RS tujuan<br>
                                    2. Permintaan <strong>TIDAK BISA DIEDIT</strong> setelah dikirim<br>
                                    3. Pastikan data pasien sudah benar sebelum mengirim
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                            <div>
                                <a href="../dashboard.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
                                </a>
                                <button type="reset" class="btn btn-outline-secondary">
                                    <i class="bi bi-eraser"></i> Reset Form
                                </button>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send-check"></i> Kirim Permintaan
                            </button>
                        </div>
                        
                        <div class="mt-3 text-muted small">
                            <i class="bi bi-info-circle"></i>
                            Permintaan akan masuk ke sistem dan dapat dilihat oleh RS tujuan dalam waktu singkat.
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Histori Permintaan - PERBAIKAN STRUKTUR HTML -->
            <div class="card mt-4">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-clock-history"></i> Histori Permintaan Anda
                        <small class="ms-2">(RS: <?php echo $rs_kode; ?>)</small>
                    </div>
                    <div>
                        <span class="badge bg-light text-dark">
                            <?php 
                            // DEBUG: Tampilkan count
                            error_log("Histori count for $rs_kode: " . count($histori));
                            echo count($histori) . " permintaan";
                            ?>
                        </span>
                        <button class="btn btn-sm btn-outline-light ms-2" onclick="refreshHistori()">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php 
                    // DEBUG: Tampilkan raw data
                    if(empty($histori)): 
                        echo "<!-- DEBUG: Histori array is empty -->";
                    ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> 
                            Belum ada permintaan yang diajukan.
                            <?php 
                            // Cek file data
                            $data_file = '../data_permintaan.json';
                            if(file_exists($data_file)) {
                                $content = file_get_contents($data_file);
                                $all_data = json_decode($content, true);
                                echo "<br><small class='text-muted'>Total data dalam file: " . (is_array($all_data) ? count($all_data) : 0) . " records</small>";
                            }
                            ?>
                        </div>
                    <?php else: ?>
                        <!-- DEBUG: Tampilkan info -->
                        <div class="alert alert-info alert-sm mb-3">
                            <small>
                                <i class="bi bi-info-circle"></i> 
                                Menampilkan <?php echo count($histori); ?> permintaan untuk <?php echo $rs_kode; ?>
                            </small>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th width="15%">Tanggal</th>
                                        <th width="10%">RS Tujuan</th>
                                        <th width="20%">Nama Pasien</th>
                                        <th width="12%">Urgensi</th>
                                        <th width="13%">Status</th>
                                        <th width="15%">Expired</th>
                                        <th width="15%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($histori as $h): 
                                        $today = date('Y-m-d');
                                        $expired = $h['tanggal_expired'] ?? '';
                                        $is_expired = ($expired && $expired < $today && $h['status'] == 'diterima');
                                    ?>
                                    <tr>
                                        <td>
                                            <small><?php echo date('d/m/Y', strtotime($h['tanggal_permintaan'] ?? $h['created_at'])); ?></small>
                                            <br>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($h['created_at'] ?? $h['tanggal_permintaan'])); ?></small>
                                        </td>
                                        <td><strong class="text-primary"><?php echo $h['ke_rs'] ?? 'N/A'; ?></strong></td>
                                        <td>
                                            <div class="fw-bold"><?php echo $h['pasien_nama'] ?? 'N/A'; ?></div>
                                            <small class="text-muted">
                                                NIK: <?php echo isset($h['pasien_nik']) ? substr($h['pasien_nik'], 0, 8) . '...' : 'N/A'; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php 
                                            $badge_class = '';
                                            $icon = '';
                                            $urgensi = $h['urgensi'] ?? 'biasa';
                                            if($urgensi == 'urgent') {
                                                $badge_class = 'bg-danger';
                                                $icon = 'bi-alarm';
                                            } elseif($urgensi == 'biasa') {
                                                $badge_class = 'bg-warning text-dark';
                                                $icon = 'bi-clock';
                                            } else {
                                                $badge_class = 'bg-info';
                                                $icon = 'bi-calendar';
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <i class="bi <?php echo $icon; ?>"></i>
                                                <?php echo strtoupper($urgensi); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $status_color = 'secondary';
                                            $status_icon = 'bi-question-circle';
                                            $status = $h['status'] ?? 'pending';
                                            
                                            if($status == 'pending') {
                                                $status_color = 'warning';
                                                $status_icon = 'bi-clock';
                                            } elseif($status == 'diterima') {
                                                $status_color = 'success';
                                                $status_icon = 'bi-check-circle';
                                            } elseif($status == 'ditolak') {
                                                $status_color = 'danger';
                                                $status_icon = 'bi-x-circle';
                                            } elseif($status == 'expired') {
                                                $status_color = 'dark';
                                                $status_icon = 'bi-hourglass-bottom';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_color; ?>">
                                                <i class="bi <?php echo $status_icon; ?>"></i>
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if(!empty($expired)) {
                                                $expired_date = date('d/m/Y', strtotime($expired));
                                                if($is_expired) {
                                                    echo '<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> ' . $expired_date . '</span>';
                                                    echo '<br><small class="text-danger">(EXPIRED)</small>';
                                                } else {
                                                    echo $expired_date;
                                                    $diff = (strtotime($expired) - strtotime($today)) / (60 * 60 * 24);
                                                    if($diff <= 3) {
                                                        echo '<br><small class="text-warning">(' . intval($diff) . ' hari lagi)</small>';
                                                    }
                                                }
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if($status == 'pending'): ?>
                                                <span class="badge bg-secondary">Menunggu</span>
                                            <?php elseif($status == 'diterima' && !$is_expired): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php elseif($is_expired): ?>
                                                <span class="badge bg-dark">Kadaluarsa</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo ucfirst($status); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                Menampilkan <?php echo count($histori); ?> permintaan terakhir
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- END Histori Permintaan -->
            
        </div>
    </div> <!-- End main-content -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function confirmSubmit() {
        // Validasi NIK
        const nikInput = document.querySelector('input[name="nik"]');
        const nikValue = nikInput.value.trim();
        
        if (nikValue.length !== 16 || !/^\d+$/.test(nikValue)) {
            alert('NIK harus 16 digit angka!');
            nikInput.focus();
            return false;
        }
        
        // Validasi Nama
        const namaInput = document.querySelector('input[name="nama"]');
        if (namaInput.value.trim().length < 3) {
            alert('Nama pasien minimal 3 karakter!');
            namaInput.focus();
            return false;
        }
        
        // Validasi Keterangan
        const keteranganInput = document.querySelector('textarea[name="keterangan"]');
        if (keteranganInput.value.trim().length < 10) {
            alert('Keterangan minimal 10 karakter!');
            keteranganInput.focus();
            return false;
        }
        
        // Konfirmasi
        const nama = namaInput.value;
        const rsTujuan = document.querySelector('select[name="ke_rs"]').value;
        const urgensi = document.querySelector('select[name="urgensi"]').value;
        const keterangan = keteranganInput.value.substring(0, 50) + '...';
        
        const urgensiText = {
            'urgent': '🚨 URGENT (Ditangani segera)',
            'biasa': '🟡 BIASA (1-2 hari)', 
            'tidak_urgent': '🔵 TIDAK URGENT (3-7 hari)'
        };
        
        const confirmMsg = `⚠️ KONFIRMASI PENGAJUAN ⚠️\n\n` +
                          `📋 Detail Permintaan:\n` +
                          `────────────────────\n` +
                          `Nama Pasien: ${nama}\n` +
                          `RS Tujuan: ${rsTujuan}\n` +
                          `Urgensi: ${urgensiText[urgensi]}\n` +
                          `Keterangan: ${keterangan}\n\n` +
                          `❌ PERHATIAN:\n` +
                          `• Permintaan TIDAK BISA diedit setelah dikirim\n` +
                          `• Pastikan data sudah benar\n` +
                          `• Data akan expired dalam waktu yang ditentukan\n\n` +
                          `Apakah Anda yakin ingin mengirim permintaan ini?`;
        
        return confirm(confirmMsg);
    }
    
    // Auto-capitalize RS code
    document.querySelector('select[name="ke_rs"]').addEventListener('change', function(e) {
        if(e.target.value) {
            e.target.value = e.target.value.toUpperCase();
        }
    });
    
    // Format NIK input
    document.querySelector('input[name="nik"]').addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '').substring(0, 16);
    });
    
    // Auto-capitalize nama
    document.querySelector('input[name="nama"]').addEventListener('input', function(e) {
        this.value = this.value.toUpperCase();
    });
    
    // Show character count for keterangan
    const keteranganTextarea = document.querySelector('textarea[name="keterangan"]');
    const charCount = document.createElement('div');
    charCount.className = 'form-text text-end';
    charCount.id = 'charCount';
    keteranganTextarea.parentNode.appendChild(charCount);
    
    keteranganTextarea.addEventListener('input', function() {
        const length = this.value.length;
        charCount.textContent = `${length} karakter (minimal 10)`;
        if (length < 10) {
            charCount.className = 'form-text text-end text-danger';
        } else {
            charCount.className = 'form-text text-end text-success';
        }
    });
    
    // Refresh histori
    function refreshHistori() {
        if(confirm("Refresh histori permintaan?")) {
            location.reload();
        }
    }
    
    // Trigger initial count
    keteranganTextarea.dispatchEvent(new Event('input'));
    </script>
</body>
</html>