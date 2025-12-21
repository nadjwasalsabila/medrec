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

// Pastikan ID ada
if(!isset($_GET['id'])) {
    $_SESSION['error'] = "ID permintaan tidak ditemukan";
    header('Location: berkas.php');
    exit;
}

$permintaan_id = intval($_GET['id']);

// **DEBUG DETAILED**
error_log("=== DETAIL.PHP DEBUG ===");
error_log("Permintaan ID dari URL: $permintaan_id");
error_log("RS Kode: $rs_kode");
error_log("RS Nama: $rs_nama");

// **PERBAIKAN CRITICAL: Ambil data dengan query TEPAT**
// CARA 1: Query langsung dengan kondisi
$all_permintaan = getData('permintaan', "id = '$permintaan_id'", '', 1);

error_log("Total data ditemukan dengan ID $permintaan_id: " . count($all_permintaan));

if(empty($all_permintaan)) {
    $_SESSION['error'] = "❌ Permintaan dengan ID $permintaan_id tidak ditemukan di database";
    header('Location: berkas.php');
    exit;
}

// Ambil data pertama
$permintaan = $all_permintaan[0];

// **VERIFIKASI KEPEMILIKAN: Pastikan ini permintaan KITA**
error_log("Data ditemukan:");
error_log("  ID: " . $permintaan['id']);
error_log("  Dari RS: " . $permintaan['dari_rs']);
error_log("  Ke RS: " . $permintaan['ke_rs']);
error_log("  Pasien: " . $permintaan['pasien_nama']);
error_log("  Status: " . $permintaan['status']);

// Cek apakah ini permintaan KITA (dari_rs harus sama dengan RS kita)
if($permintaan['dari_rs'] != $rs_kode) {
    $_SESSION['error'] = "❌ Akses ditolak! Ini bukan permintaan Anda. (Dari RS: " . $permintaan['dari_rs'] . ")";
    header('Location: berkas.php');
    exit;
}

// Hanya bisa melihat jika status 'diterima'
if($permintaan['status'] != 'diterima') {
    $_SESSION['error'] = "❌ Data belum dikirim oleh RS tujuan. Status: " . $permintaan['status'];
    header('Location: berkas.php');
    exit;
}

// **PERBAIKAN: Proses decrypt dengan benar**
$response_data = null;
$has_file = false;
$file_data = null;
$decrypt_error = '';

if(!empty($permintaan['data_dikirim'])) {
    error_log("🔑 Proses decrypt data untuk ID $permintaan_id");
    error_log("   Data length: " . strlen($permintaan['data_dikirim']) . " bytes");
    
    // **PERBAIKAN: Gunakan logika decrypt yang sama dengan berkas.php**
    // Data dienkripsi dengan kunci RS KITA (penerima)
    $our_key = getHospitalKey($rs_kode);
    error_log("   Menggunakan kunci kita ($rs_kode): " . substr($our_key, 0, 10) . "...");
    
    $decrypted = decryptData($permintaan['data_dikirim'], $our_key);
    
    if(empty($decrypted)) {
        // Coba dengan kunci RS pengirim (alternatif)
        $sender_key = getHospitalKey($permintaan['ke_rs']);
        error_log("   Gagal dengan kunci kita, coba kunci pengirim (" . $permintaan['ke_rs'] . "): " . substr($sender_key, 0, 10) . "...");
        
        $decrypted = decryptData($permintaan['data_dikirim'], $sender_key);
    }
    
    if(!empty($decrypted)) {
        $response_data = json_decode($decrypted, true);
        
        if($response_data && is_array($response_data)) {
            error_log("   ✅ Berhasil decrypt data");
            
            // Debug data yang didapat
            error_log("   Data keys: " . implode(', ', array_keys($response_data)));
            error_log("   Pasien dalam data: " . ($response_data['pasien_nama'] ?? 'Tidak ada'));
            
            if(isset($response_data['file_data'])) {
                $has_file = true;
                $file_data = $response_data['file_data'];
                error_log("   📁 File ditemukan: " . ($file_data['original_name'] ?? 'Unknown'));
            }
        } else {
            $decrypt_error = "Data tidak valid setelah decrypt";
            error_log("   ❌ JSON decode gagal");
        }
    } else {
        $decrypt_error = "Gagal mendecrypt data";
        error_log("   ❌ Decrypt gagal dengan semua kunci");
    }
} else {
    $decrypt_error = "Tidak ada data yang dikirim";
    error_log("   ❌ data_dikirim kosong");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Data - <?php echo htmlspecialchars($rs_nama); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .main-content { 
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
        
        .encryption-badge {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8em;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .btn-view {
            background: linear-gradient(45deg, #0d6efd, #0b5ed7);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-view:hover {
            background: linear-gradient(45deg, #0b5ed7, #0a58ca);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }
    </style>
</head>
<body>
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
            
            <!-- **PERBAIKAN: Debug Info Header** -->
            <div class="alert alert-info mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1"><i class="bi bi-info-circle"></i> Detail Permintaan</h5>
                        <p class="mb-0 small">
                            ID: <strong>#<?php echo $permintaan['id']; ?></strong> | 
                            Pasien: <strong><?php echo htmlspecialchars($permintaan['pasien_nama']); ?></strong> | 
                            RS Tujuan: <strong><?php echo $permintaan['ke_rs']; ?></strong>
                        </p>
                    </div>
                    <span class="encryption-badge">
                        <i class="bi bi-shield-check"></i> Data Terenkripsi
                    </span>
                </div>
            </div>
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3><i class="bi bi-file-earmark-medical"></i> Detail Data Medis</h3>
                    <p class="text-muted">RS: <strong><?php echo $rs_kode; ?> - <?php echo htmlspecialchars($rs_nama); ?></strong></p>
                </div>
                <div>
                    <a href="berkas.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali ke Arsip
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
                                    <span class="badge bg-success status-badge">
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
                                    <?php echo date('d F Y', strtotime($permintaan['tanggal_permintaan'])); ?></p>
                                <p class="mb-0"><strong>Jam:</strong> <?php echo date('H:i', strtotime($permintaan['tanggal_permintaan'])); ?></p>
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
                            <?php echo nl2br(htmlspecialchars($permintaan['keterangan'])); ?>
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
                            
                            <div class="mt-3">
                                <a href="berkas.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali ke Arsip
                                </a>
                            </div>
                        </div>
                        
                    <?php elseif($response_data): ?>
                        <!-- Success Message -->
                        <div class="alert alert-success">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                                <div>
                                    <h6 class="mb-1">Data berhasil dibuka!</h6>
                                    <p class="mb-0">Berikut data yang dikirim oleh RS <?php echo $permintaan['ke_rs']; ?> untuk pasien 
                                        <strong><?php echo htmlspecialchars($response_data['pasien_nama'] ?? $permintaan['pasien_nama']); ?></strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- **PERBAIKAN: Tampilkan info pasien dari data** -->
                        <?php if(isset($response_data['pasien_nama']) && $response_data['pasien_nama'] != $permintaan['pasien_nama']): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Perhatian:</strong> Nama pasien dalam data berbeda dengan permintaan.<br>
                            Permintaan: <strong><?php echo htmlspecialchars($permintaan['pasien_nama']); ?></strong><br>
                            Data: <strong><?php echo htmlspecialchars($response_data['pasien_nama']); ?></strong>
                        </div>
                        <?php endif; ?>
                        
                        <!-- File Attachment -->
                        <?php if($has_file && $file_data): ?>
                        <div class="alert alert-info file-info mb-4">
                            <h6><i class="bi bi-paperclip"></i> Dokumen Terlampir</h6>
                            <div class="row">
                                <div class="col-md-10">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-file-earmark-text fs-1 text-primary me-3"></i>
                                        <div>
                                            <p class="mb-1"><strong><?php echo htmlspecialchars($file_data['original_name']); ?></strong></p>
                                            <p class="mb-1 small text-muted">
                                                <i class="bi bi-hdd"></i> <?php echo round($file_data['file_size'] / 1024, 2); ?> KB
                                                <span class="mx-2">•</span>
                                                <i class="bi bi-card-text"></i> <?php echo $file_data['file_type']; ?>
                                                <span class="mx-2">•</span>
                                                <i class="bi bi-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($file_data['upload_time'] ?? 'now')); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Informasi keamanan -->
                            <div class="mt-3 alert alert-light border">
                                <i class="bi bi-eye text-info"></i>
                                <strong>Mode View-Only:</strong> Dokumen ini hanya dapat dilihat di sini untuk keamanan data pasien.
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
                                    <strong>Expired:</strong><br>
                                    <?php echo isset($response_data['expired_days_set']) ? $response_data['expired_days_set'] . ' hari' : 'Tidak ditentukan'; ?>
                                </p>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <!-- No Data -->
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 3em;"></i>
                            <h4 class="mt-3 text-muted">Belum Ada Data</h4>
                            <p class="text-muted">RS <?php echo $permintaan['ke_rs']; ?> belum mengirim data untuk permintaan ini.</p>
                            <div class="mt-3">
                                <a href="berkas.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali ke Arsip
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Expired Info -->
            <?php if(!empty($permintaan['tanggal_expired'])): 
                $today = date('Y-m-d');
                $expired = $permintaan['tanggal_expired'];
                $is_expired = $expired < $today;
                $days_left = $is_expired ? 0 : round((strtotime($expired) - strtotime($today)) / (60 * 60 * 24));
            ?>
            <div class="card shadow-sm">
                <div class="card-header <?php echo $is_expired ? 'bg-dark' : 'bg-info'; ?> text-white">
                    <h6 class="mb-0"><i class="bi bi-calendar-check"></i> Masa Berlaku Akses Data</h6>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <p class="mb-1">
                                <strong>Tanggal Expired:</strong> 
                                <?php echo date('d F Y', strtotime($expired)); ?>
                            </p>
                            <?php if($is_expired): ?>
                                <p class="text-danger mb-0 small">
                                    <i class="bi bi-exclamation-triangle"></i> 
                                    <strong>PERHATIAN:</strong> Data sudah tidak dapat diakses sejak 
                                    <?php echo date('d/m/Y', strtotime($expired)); ?>
                                </p>
                            <?php else: ?>
                                <p class="text-success mb-0">
                                    <i class="bi bi-check-circle"></i> 
                                    Data dapat diakses selama <strong><?php echo $days_left; ?> hari</strong> lagi
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if($is_expired): ?>
                            <span class="badge bg-dark p-2 fs-6">
                                <i class="bi bi-hourglass-bottom"></i> KADALUARSA
                            </span>
                            <?php else: ?>
                            <span class="badge <?php echo $days_left <= 3 ? 'bg-danger' : 'bg-success'; ?> p-2 fs-6">
                                <i class="bi bi-clock"></i> 
                                <?php echo $days_left; ?> HARI LAGI
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="mt-4 d-flex justify-content-between">
                <a href="berkas.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar Arsip
                </a>
                
                <?php if($response_data): ?>
                <div class="alert alert-light border d-inline-block m-0 p-2">
                    <i class="bi bi-shield-check text-success"></i>
                    <small class="text-muted">Data telah diverifikasi dan hanya untuk keperluan medis</small>
                </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Hapus semua fungsi download
    console.log("Mode view-only aktif: Tidak ada opsi download untuk keamanan data pasien.");
    
    // Tambah smooth scroll untuk medical history yang panjang
    document.addEventListener('DOMContentLoaded', function() {
        const medicalHistory = document.querySelector('.medical-history');
        if(medicalHistory && medicalHistory.scrollHeight > 400) {
            // Tambah scroll indicator
            const indicator = document.createElement('div');
            indicator.className = 'text-center text-muted small mt-2';
            indicator.innerHTML = '<i class="bi bi-arrows-expand"></i> Scroll untuk melihat lebih lanjut';
            medicalHistory.parentNode.appendChild(indicator);
        }
    });
    </script>
</body>
</html>