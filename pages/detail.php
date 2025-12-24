<?php
session_start();
if(!isset($_SESSION['rs_kode'])){
    header('Location: ../login.php');
    exit;
}

$rs_kode = $_SESSION['rs_kode'];
$rs_nama = $_SESSION['rs_nama'];

require_once '../config/database.php';
require_once '../config/encryption.php';

// **PERBAIKAN: Pastikan ID ada dan valid**
if(!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID permintaan tidak ditemukan";
    header('Location: berkas.php');
    exit;
}

$permintaan_id = intval($_GET['id']);

// **DEBUG: Aktifkan untuk troubleshooting**
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ambil semua permintaan untuk debugging
$all_permintaan = getData('permintaan', '', 'id DESC');
$permintaan = null;

// Cari permintaan berdasarkan ID
foreach($all_permintaan as $p) {
    if(isset($p['id']) && $p['id'] == $permintaan_id) {
        $permintaan = $p;
        break;
    }
}

if(!$permintaan) {
    $_SESSION['error'] = "❌ Permintaan dengan ID $permintaan_id tidak ditemukan";
    header('Location: berkas.php');
    exit;
}

// **VERIFIKASI: Pastikan ini permintaan KITA (penerima)**
if($permintaan['dari_rs'] != $rs_kode) {
    $_SESSION['error'] = "❌ Akses ditolak! Ini bukan permintaan Anda.";
    header('Location: berkas.php');
    exit;
}

// Cek expired
$today = date('Y-m-d');
$expired_date = $permintaan['tanggal_expired'] ?? '';
$is_expired = $expired_date && $expired_date < $today;

if($is_expired) {
    $_SESSION['error'] = "❌ Akses data sudah expired sejak " . date('d/m/Y', strtotime($expired_date));
    header('Location: berkas.php');
    exit;
}

// Hanya bisa melihat jika status 'diterima'
if($permintaan['status'] != 'diterima') {
    $_SESSION['error'] = "❌ Data belum dikirim oleh RS tujuan. Status: " . $permintaan['status'];
    header('Location: berkas.php');
    exit;
}

// **PROSES DECRYPT DATA**
$response_data = null;
$has_file = false;
$file_data = null;
$decrypt_error = '';
$file_preview_available = false;
$file_preview_html = '';

if(!empty($permintaan['data_dikirim'])) {
    // **PERBAIKAN: Fungsi decrypt yang lebih baik**
    function tryDecryptData($encrypted_data, $rs_kode, $ke_rs) {
        // Coba dengan kunci penerima (kita)
        $our_key = getHospitalKey($rs_kode);
        $decrypted = decryptData($encrypted_data, $our_key);
        
        if(!empty($decrypted)) {
            $data = json_decode($decrypted, true);
            if($data && is_array($data)) {
                return ['success' => true, 'data' => $data, 'key_used' => 'penerima'];
            }
        }
        
        // Coba dengan kunci pengirim
        $sender_key = getHospitalKey($ke_rs);
        $decrypted = decryptData($encrypted_data, $sender_key);
        
        if(!empty($decrypted)) {
            $data = json_decode($decrypted, true);
            if($data && is_array($data)) {
                return ['success' => true, 'data' => $data, 'key_used' => 'pengirim'];
            }
        }
        
        // Coba semua kunci yang mungkin
        $possible_keys = ['key-rs001', 'key-rs002', 'key-rs003', 'default-key-12345'];
        foreach($possible_keys as $key) {
            $decrypted = decryptData($encrypted_data, $key);
            if(!empty($decrypted)) {
                $data = json_decode($decrypted, true);
                if($data && is_array($data)) {
                    return ['success' => true, 'data' => $data, 'key_used' => 'fallback'];
                }
            }
        }
        
        return ['success' => false, 'error' => 'Gagal decrypt dengan semua kunci'];
    }
    
    $decrypt_result = tryDecryptData($permintaan['data_dikirim'], $rs_kode, $permintaan['ke_rs']);
    
    if($decrypt_result['success']) {
        $response_data = $decrypt_result['data'];
        
        if(isset($response_data['file_data'])) {
            $has_file = true;
            $file_data = $response_data['file_data'];
            
            // Generate preview berdasarkan tipe file
            if(isset($file_data['file_type']) && isset($file_data['file_data_base64'])) {
                $file_type = $file_data['file_type'];
                $base64_data = $file_data['file_data_base64'];
                
                if(strpos($file_type, 'image/') === 0) {
                    $file_preview_available = true;
                    $file_preview_html = '<img src="data:' . $file_type . ';base64,' . $base64_data . '" class="img-fluid" style="max-height: 400px;" alt="Preview">';
                } elseif($file_type == 'application/pdf') {
                    $file_preview_available = true;
                    $file_preview_html = '<iframe src="data:application/pdf;base64,' . $base64_data . '" class="pdf-preview" style="width: 100%; height: 400px;"></iframe>';
                } elseif(strpos($file_type, 'text/') === 0) {
                    $file_preview_available = true;
                    $text_content = base64_decode($base64_data);
                    $file_preview_html = '<div class="text-preview">' . htmlspecialchars(substr($text_content, 0, 5000)) . '</div>';
                }
            }
        }
    } else {
        $decrypt_error = $decrypt_result['error'];
    }
} else {
    $decrypt_error = "Tidak ada data yang dikirim";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Data - <?php echo htmlspecialchars($rs_nama); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(180deg, #2c3e50, #1a2530);
            z-index: 1100;
            display: flex;
            align-items: center;
            padding: 0 20px;
        }
        .main-content { 
            margin-top: 60px;
            margin-left: 250px; 
            padding: 20px; 
            min-height: 100vh; 
            background: #f8f9fa;
        }
        
        @media (max-width: 768px) { 
            .main-content { 
                margin-left: 0 !important; 
                padding: 15px;
            } 
        }
        
        .card-header { 
            background: linear-gradient(45deg, #0d6efd 0%, #0dcaf0 100%); 
            color: white; 
        }
        
        .patient-card {
            border-left: 5px solid #0d6efd;
            border-radius: 10px;
        }
        
        .data-card {
            border-left: 5px solid #198754;
            border-radius: 10px;
        }
        
        .info-box { 
            background: white; 
            border-radius: 8px; 
            padding: 15px; 
            margin: 10px 0;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .file-info { 
            border-left: 4px solid #0dcaf0; 
            padding-left: 15px; 
        }
        
        .medical-history { 
            white-space: pre-wrap; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            max-height: 400px;
            overflow-y: auto;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .file-preview-container {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            background: white;
        }
        
        .pdf-preview {
            width: 100%;
            height: 400px;
            border: none;
        }
        
        .text-preview {
            white-space: pre-wrap;
            font-family: monospace;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .view-only-badge {
            background: #6c757d;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-back:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <button id="sidebarToggle" class="btn btn-secondary">
            <i class="bi bi-list"></i>
        </button>
        <div class="ms-auto badge bg-linear-gradient(180deg, #2c3e50, #1a2530)">Sistem Aktif</div>
    </div>
    <?php include '../components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container mt-4">
            
            <!-- Error/Success Messages -->
            <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <h5><i class="bi bi-exclamation-triangle"></i> Error</h5>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Debug Info (Hanya tampil jika ada error) -->
            <?php if(!empty($decrypt_error) && $decrypt_error != "Tidak ada data yang dikirim"): ?>
            <div class="alert alert-warning">
                <h6><i class="bi bi-exclamation-triangle"></i> Debug Info</h6>
                <p class="mb-1 small">ID: <?php echo $permintaan_id; ?></p>
                <p class="mb-1 small">RS Anda: <?php echo $rs_kode; ?></p>
                <p class="mb-1 small">RS Pengirim: <?php echo $permintaan['ke_rs']; ?></p>
                <p class="mb-0 small">Error: <?php echo htmlspecialchars($decrypt_error); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3><i class="bi bi-file-earmark-medical"></i> Detail Data Medis</h3>
                    <p class="text-muted">
                        <span class="view-only-badge"><i class="bi bi-eye"></i> VIEW-ONLY</span>
                        | ID: #<?php echo $permintaan_id; ?>
                        | Expired: <?php echo $expired_date ? date('d/m/Y', strtotime($expired_date)) : 'Tidak ditentukan'; ?>
                    </p>
                </div>
                <div>
                    <a href="berkas.php" class="btn-back">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            
            <!-- Patient Info Card -->
            <div class="card mb-4 shadow-sm patient-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person-badge"></i> Informasi Pasien</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box">
                                <h6><i class="bi bi-person text-primary"></i> Identitas Pasien</h6>
                                <p class="mb-1"><strong>Nama:</strong> <?php echo htmlspecialchars($permintaan['pasien_nama']); ?></p>
                                <p class="mb-0"><strong>NIK:</strong> <?php echo htmlspecialchars($permintaan['pasien_nik']); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <h6><i class="bi bi-hospital text-info"></i> Informasi RS</h6>
                                <p class="mb-1"><strong>RS Pengirim Data:</strong> <?php echo $permintaan['ke_rs']; ?></p>
                                <p class="mb-0"><strong>Status:</strong> 
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> DITERIMA
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="info-box">
                                <h6><i class="bi bi-calendar-check text-success"></i> Waktu</h6>
                                <p class="mb-1"><strong>Tanggal Permintaan:</strong><br>
                                    <?php echo date('d F Y H:i', strtotime($permintaan['tanggal_permintaan'])); ?></p>
                                <?php if(isset($permintaan['tanggal_dikirim'])): ?>
                                <p class="mb-0"><strong>Dikirim pada:</strong><br>
                                    <?php echo date('d F Y H:i', strtotime($permintaan['tanggal_dikirim'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <h6><i class="bi bi-flag text-warning"></i> Urgensi</h6>
                                <?php 
                                $urgensi_class = [
                                    'urgent' => 'danger',
                                    'biasa' => 'warning', 
                                    'tidak_urgent' => 'info'
                                ];
                                $urgensi = $permintaan['urgensi'] ?? 'biasa';
                                ?>
                                <span class="badge bg-<?php echo $urgensi_class[$urgensi] ?? 'secondary'; ?> p-2">
                                    <i class="bi bi-exclamation-circle"></i>
                                    <?php echo strtoupper($urgensi); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6><i class="bi bi-chat-left-text text-secondary"></i> Alasan Permintaan</h6>
                        <div class="alert alert-light border rounded p-3">
                            <?php echo nl2br(htmlspecialchars($permintaan['keterangan'] ?? 'Tidak ada keterangan')); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Data from Other Hospital -->
            <div class="card shadow-sm mb-4 data-card">
                <div class="card-header <?php echo $response_data ? 'bg-success' : 'bg-danger'; ?> text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-inbox"></i> Data dari RS <?php echo $permintaan['ke_rs']; ?>
                        <?php if($response_data): ?>
                            <small class="float-end">
                                <i class="bi bi-check-circle"></i> Data Terbuka
                            </small>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if(!empty($decrypt_error)): ?>
                        <!-- Error Message -->
                        <div class="alert alert-danger">
                            <h5><i class="bi bi-exclamation-triangle"></i> Gagal Membuka Data</h5>
                            <p class="mb-2"><?php echo htmlspecialchars($decrypt_error); ?></p>
                            <p class="small text-muted">
                                Data mungkin dienkripsi dengan kunci yang berbeda atau format tidak sesuai.
                            </p>
                        </div>
                        
                    <?php elseif($response_data): ?>
                        <!-- Success Message -->
                        <div class="alert alert-success">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                                <div>
                                    <h6 class="mb-1">Data berhasil dibuka! (Mode View-Only)</h6>
                                    <p class="mb-0">Data dari RS <?php echo $permintaan['ke_rs']; ?> untuk pasien 
                                        <strong><?php echo htmlspecialchars($response_data['pasien_nama'] ?? $permintaan['pasien_nama']); ?></strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- File Attachment dengan PREVIEW -->
                        <?php if($has_file && $file_data): ?>
                        <div class="alert alert-info file-info mb-4">
                            <h6><i class="bi bi-paperclip"></i> Dokumen Terlampir</h6>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-file-earmark-text fs-1 text-primary me-3"></i>
                                        <div>
                                            <p class="mb-1"><strong><?php echo htmlspecialchars($file_data['original_name']); ?></strong></p>
                                            <p class="mb-1 small text-muted">
                                                <i class="bi bi-hdd"></i> <?php echo round($file_data['file_size'] / 1024, 2); ?> KB
                                                <span class="mx-2">•</span>
                                                <i class="bi bi-card-text"></i> <?php echo $file_data['file_type']; ?>
                                                <?php if(isset($file_data['upload_time'])): ?>
                                                <span class="mx-2">•</span>
                                                <i class="bi bi-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($file_data['upload_time'])); ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- FILE PREVIEW -->
                            <?php if($file_preview_available): ?>
                            <div class="mt-3">
                                <h6><i class="bi bi-eye"></i> Preview Dokumen:</h6>
                                <div class="file-preview-container mt-2">
                                    <?php echo $file_preview_html; ?>
                                </div>
                            </div>
                            <?php elseif($has_file): ?>
                            <div class="alert alert-warning mt-3">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Preview tidak tersedia:</strong> Format file tidak mendukung preview langsung.
                            </div>
                            <?php endif; ?>
                            
                            <!-- Informasi keamanan -->
                            <div class="mt-3 alert alert-light border">
                                <i class="bi bi-shield-check text-success"></i>
                                <strong>Mode View-Only:</strong> Dokumen ini hanya dapat dilihat di sini untuk keamanan data pasien. 
                                Tidak ada opsi download yang tersedia.
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Medical History -->
                        <?php if(isset($response_data['riwayat_medis']) && !empty($response_data['riwayat_medis'])): ?>
                        <div class="mb-4">
                            <h6><i class="bi bi-clipboard-pulse text-success"></i> Riwayat Medis</h6>
                            <div class="medical-history">
                                <?php echo nl2br(htmlspecialchars($response_data['riwayat_medis'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Additional Info -->
                        <?php if(isset($response_data['keterangan_tambahan']) && !empty($response_data['keterangan_tambahan'])): ?>
                        <div class="alert alert-warning mb-4">
                            <h6><i class="bi bi-chat-left-text"></i> Keterangan Tambahan</h6>
                            <?php echo nl2br(htmlspecialchars($response_data['keterangan_tambahan'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Metadata -->
                        <div class="row mt-4 pt-3 border-top">
                            <div class="col-md-4">
                                <p class="small">
                                    <i class="bi bi-clock text-muted"></i>
                                    <strong>Dikirim pada:</strong><br>
                                    <?php echo isset($response_data['timestamp']) ? date('d/m/Y H:i', strtotime($response_data['timestamp'])) : 'Tidak tersedia'; ?>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p class="small">
                                    <i class="bi bi-person-check text-muted"></i>
                                    <strong>Dikirim oleh RS:</strong><br>
                                    <?php echo $response_data['dikirim_oleh'] ?? $permintaan['ke_rs']; ?>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p class="small">
                                    <i class="bi bi-calendar-event text-muted"></i>
                                    <strong>Expired dalam:</strong><br>
                                    <?php echo isset($response_data['expired_days_set']) ? $response_data['expired_days_set'] . ' hari' : '7 hari (default)'; ?>
                                </p>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <!-- No Data -->
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 3em;"></i>
                            <h4 class="mt-3 text-muted">Belum Ada Data</h4>
                            <p class="text-muted">RS <?php echo $permintaan['ke_rs']; ?> belum mengirim data untuk permintaan ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="mt-4 d-flex justify-content-between">
                <a href="berkas.php" class="btn-back">
                    <i class="bi bi-arrow-left"></i> Kembali ke Arsip
                </a>
                
                <?php if($response_data): ?>
                <div class="alert alert-light border d-inline-block m-0 p-2">
                    <i class="bi bi-shield-check text-success"></i>
                    <small class="text-muted">Mode VIEW-ONLY aktif - Data hanya untuk keperluan medis</small>
                </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Mode view-only aktif
    console.log("Mode view-only: File hanya dapat dilihat, tidak dapat didownload.");
    
    // Blok klik kanan pada preview file untuk mencegah save
    document.addEventListener('contextmenu', function(e) {
        const previewContainer = e.target.closest('.file-preview-container');
        if(previewContainer) {
            e.preventDefault();
            alert('Klik kanan dinonaktifkan untuk keamanan data pasien.');
        }
    });
    
    // Debug info
    document.addEventListener('DOMContentLoaded', function() {
        console.log("Halaman detail.php dimuat untuk ID:", <?php echo $permintaan_id; ?>);
        console.log("RS:", '<?php echo $rs_kode; ?>');
        console.log("Data ada:", <?php echo $response_data ? 'true' : 'false'; ?>);
    });
    </script>
</body>
</html>