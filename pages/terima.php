<?php
session_start();

// Set timezone Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

if(!isset($_SESSION['rs_kode'])){
    header('Location: ../login.php');
    exit;
}

$rs_kode = $_SESSION['rs_kode'];
$rs_nama = $_SESSION['rs_nama'];

require_once '../config/database.php';
require_once '../config/encryption.php';

// Inisialisasi variabel
$success = '';
$error = '';

// Ambil permintaan masuk ke RS ini (status pending)
$permintaan_masuk = getData('permintaan', "ke_rs = '$rs_kode' AND status = 'pending'");
usort($permintaan_masuk, function($a, $b) {
    return strtotime($b['tanggal_permintaan']) - strtotime($a['tanggal_permintaan']);
});

// Ambil histori pengiriman (permintaan yang sudah kita kirim)
$histori_kirim = getData('permintaan', "ke_rs = '$rs_kode' AND status = 'diterima'");
usort($histori_kirim, function($a, $b) {
    $date_a = $a['tanggal_diterima'] ?? $a['tanggal_permintaan'];
    $date_b = $b['tanggal_diterima'] ?? $b['tanggal_permintaan'];
    return strtotime($date_b) - strtotime($date_a);
});


// Proses tolak permintaan
if(isset($_POST['tolak_permintaan'])){
    $permintaan_id = $_POST['permintaan_id'] ?? '';
    $alasan_penolakan = $_POST['alasan_penolakan'] ?? 'Tidak ada alasan yang diberikan';
    $dari_rs = $_POST['dari_rs'] ?? '';
    $pasien_nama = $_POST['pasien_nama'] ?? '';
    
    // Cari data permintaan
    $permintaan_data = null;
    foreach($permintaan_masuk as $pm) {
        if($pm['id'] == $permintaan_id) {
            $permintaan_data = $pm;
            break;
        }
    }
    
    if(!$permintaan_data) {
        $error = "❌ Permintaan tidak ditemukan";
    } else {
        // Update status menjadi 'ditolak'
        $update_data = [
            'status' => 'ditolak',
            'keterangan' => $alasan_penolakan
        ];
        
        $result = updateData('permintaan', $permintaan_id, $update_data);
        
        if($result['success']){
            // Simpan histori
            createData('histori', [
                'permintaan_id' => $permintaan_id,
                'rs_id' => $rs_kode,
                'aksi' => 'menolak_permintaan',
                'keterangan' => 'Menolak permintaan data pasien ' . $pasien_nama . 
                               ' dari RS ' . $dari_rs . '. Alasan: ' . $alasan_penolakan,
                'waktu' => date('Y-m-d H:i:s')
            ]);
            
            $success = "✅ Permintaan dari RS " . $dari_rs . " berhasil ditolak!";
            $success .= "<br><small>Alasan: " . htmlspecialchars($alasan_penolakan) . "</small>";
            
            // Refresh data
            $permintaan_masuk = getData('permintaan', "ke_rs = '$rs_kode' AND status = 'pending'");
            usort($permintaan_masuk, function($a, $b) {
                return strtotime($b['tanggal_permintaan']) - strtotime($a['tanggal_permintaan']);
            });
        } else {
            $error = "❌ Gagal menolak permintaan";
        }
    }
}

// Proses kirim data
if(isset($_POST['kirim_data'])){
    $permintaan_id = $_POST['permintaan_id'] ?? '';
    $pasien_nik = $_POST['pasien_nik'] ?? '';
    $dari_rs = $_POST['dari_rs'] ?? '';
    $expired_days = $_POST['expired_days'] ?? 14;
    
    // Validasi
    $expired_days = intval($expired_days);
    if($expired_days < 1) $expired_days = 1;
    if($expired_days > 365) $expired_days = 365;
    
    // Cari data permintaan
    $permintaan_data = null;
    foreach($permintaan_masuk as $pm) {
        if($pm['id'] == $permintaan_id) {
            $permintaan_data = $pm;
            break;
        }
    }
    
    if(!$permintaan_data) {
        $error = "❌ Permintaan tidak ditemukan";
    } else {
        $file_data = [];
        $text_data = $_POST['text_data'] ?? '';
        
        // Handle file upload jika ada
        if(isset($_FILES['data_file']) && $_FILES['data_file']['error'] == 0){
            // Buat folder uploads jika belum ada
            if(!is_dir('../uploads')) {
                mkdir('../uploads', 0755, true);
            }
            
            $file = $_FILES['data_file'];
            $file_name = 'data_' . $pasien_nik . '_' . time() . '_' . basename($file['name']);
            $file_path = '../uploads/' . $file_name;
            
            if(move_uploaded_file($file['tmp_name'], $file_path)){
                $file_data = [
                    'file_name' => $file_name,
                    'original_name' => $file['name'],
                    'file_type' => $file['type'],
                    'file_size' => $file['size']
                ];
            }
        }
        
        // Siapkan data untuk dienkripsi
        $pasien_data_array = [
            'pasien_nama' => $permintaan_data['pasien_nama'],
            'pasien_nik' => $pasien_nik,
            'riwayat_medis' => $text_data,
            'file_data' => $file_data,
            'keterangan_tambahan' => $_POST['keterangan_tambahan'] ?? '',
            'dikirim_oleh' => $rs_kode,
            'dikirim_pada' => date('Y-m-d H:i:s'),
            'expired_days' => $expired_days
        ];
        
        $pasien_data = json_encode($pasien_data_array, JSON_UNESCAPED_UNICODE);
        
        // **ENKRIPSI dengan kunci RS yang meminta**
        $key_peminta = getHospitalKey($dari_rs);
        $data_terenkripsi = encryptData($pasien_data, $key_peminta);
        
        if(!$data_terenkripsi) {
            $error = "❌ Gagal mengenkripsi data";
        } else {
            // Update database
            $update_data = [
                'status' => 'diterima',
                'data_dikirim' => $data_terenkripsi,
                'tanggal_expired' => date('Y-m-d', strtotime("+{$expired_days} days")),
                'tanggal_diterima' => date('Y-m-d H:i:s')
            ];
            
            $result = updateData('permintaan', $permintaan_id, $update_data);
            
            if($result['success']){
                // Simpan histori
                createData('histori', [
                    'permintaan_id' => $permintaan_id,
                    'rs_id' => $rs_kode,
                    'aksi' => 'mengirim_data',
                    'keterangan' => 'Mengirim data pasien ' . $permintaan_data['pasien_nama'] . 
                                   ' ke RS ' . $dari_rs,
                    'waktu' => date('Y-m-d H:i:s')
                ]);
                
                $success = "✅ Data berhasil dikirim ke RS " . $dari_rs . "!";
                $success .= "<br><small>Data dienkripsi dengan kunci RS " . $dari_rs . "</small>";
                
                // Refresh data
                $permintaan_masuk = getData('permintaan', "ke_rs = '$rs_kode' AND status = 'pending'");
                usort($permintaan_masuk, function($a, $b) {
                    return strtotime($b['tanggal_permintaan']) - strtotime($a['tanggal_permintaan']);
                });
                
                $histori_kirim = getData('permintaan', "ke_rs = '$rs_kode' AND status = 'diterima'");
                usort($histori_kirim, function($a, $b) {
                    $date_a = $a['tanggal_diterima'] ?? $a['tanggal_permintaan'];
                    $date_b = $b['tanggal_diterima'] ?? $b['tanggal_permintaan'];
                    return strtotime($date_b) - strtotime($date_a);
                });
            } else {
                $error = "❌ Gagal mengupdate database";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Permintaan Masuk - <?php echo htmlspecialchars($rs_nama); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/modern-theme.css">
    <style>
        .main-content {
            margin-top: 60px;
            margin-left: 0;
            padding: 32px;
            min-height: calc(100vh - 60px);
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
        }
        
        .request-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid var(--gray-200);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .request-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--gray-300);
        }
        
        .request-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .request-card.urgent::before {
            background: var(--danger);
        }
        
        .request-card.urgent {
            background: #fff5f5;
        }
        
        .request-card.biasa::before {
            background: var(--warning);
        }
        
        .histori-item {
            background: white;
            border-radius: var(--radius-md);
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid var(--gray-200);
            transition: all 0.3s;
        }
        
        .histori-item:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow-sm);
            border-color: var(--primary-blue);
        }
        
        .patient-name {
            color: var(--primary-blue);
            font-weight: 600;
        }
        
        .btn-send {
            background: var(--success);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .btn-send:hover {
            background: #157347;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(25, 135, 84, 0.3);
        }
        
        .modal-form {
            padding: 20px;
        }
        
        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .file-upload-area:hover {
            border-color: #0d6efd;
            background: #e7f1ff;
        }
        
        .file-preview {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 10px;
            display: none;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .histori-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #dee2e6;
        }
        
        .histori-item {
            border-left: 4px solid #198754;
            padding: 15px;
            margin-bottom: 10px;
            background: #f0fff4;
            border-radius: 8px;
        }
        
        .expired-item {
            border-left-color: #6c757d;
            background: #f8f9fa;
        }
        
        .encryption-info {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '../components/topbar.php'; ?>
    
    <?php include '../components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark"><i class="bi bi-inbox text-primary me-2"></i>Permintaan Masuk</h2>
                    <p class="text-muted">Kelola permintaan data medis dari Rumah Sakit lain</p>
                </div>
                <div>
                    <a href="ajukan.php" class="btn-modern btn-primary-modern">
                        <i class="bi bi-send"></i> Ajukan Permintaan
                    </a>
                </div>
            </div>
            
            <!-- Informasi Enkripsi -->
            <div class="alert-modern alert-primary mb-4">
                <i class="bi bi-shield-lock" style="font-size: 1.5rem;"></i>
                <div class="flex-grow-1">
                    <strong>Sistem Enkripsi End-to-End</strong><br>
                    Data yang Anda kirim akan dienkripsi dengan kunci RS peminta. Hanya RS tersebut yang dapat membuka data.
                </div>
            </div>
            
            <!-- Alerts -->
            <?php if(!empty($success)): ?>
            <div class="alert-modern alert-success mb-4">
                <i class="bi bi-check-circle-fill" style="font-size: 1.5rem;"></i>
                <div class="flex-grow-1">
                    <strong>Sukses</strong><br>
                    <?php echo $success; ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($error)): ?>
            <div class="alert-modern alert-danger mb-4">
                <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.5rem;"></i>
                <div class="flex-grow-1">
                    <strong>Error</strong><br>
                    <?php echo $error; ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Daftar Permintaan Masuk -->
            <div class="content-card shadow-sm p-4 mb-5">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                    <div>
                        <h5 class="fw-bold mb-0"><i class="bi bi-clock-history text-warning me-2"></i>Permintaan Menunggu</h5>
                        <small class="text-muted">Perlu respons segera</small>
                    </div>
                    <?php if(count($permintaan_masuk) > 0): ?>
                    <span class="badge bg-danger rounded-pill px-3 py-2">
                        <i class="bi bi-exclamation-circle me-1"></i> <?php echo count($permintaan_masuk); ?> Pending
                    </span>
                    <?php else: ?>
                    <span class="badge bg-success rounded-pill px-3 py-2">
                        <i class="bi bi-check-circle me-1"></i> Semua Selesai
                    </span>
                    <?php endif; ?>
                </div>

                <div class="card-body p-0">
                    <?php if(empty($permintaan_masuk)): ?>
                        <div class="text-center py-5">
                            <div class="bg-light rounded-circle d-inline-flex p-4 mb-3">
                                <i class="bi bi-check-lg text-success" style="font-size: 2rem;"></i>
                            </div>
                            <h5 class="text-dark">Tidak ada permintaan menunggu</h5>
                            <p class="text-muted">Kerja bagus! Semua permintaan telah Anda proses.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($permintaan_masuk as $pm): ?>
                        <div class="request-card mb-4 position-relative overflow-hidden">
                            <div class="row align-items-center">
                                <div class="col-lg-8">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; font-size: 1.5rem;">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-1">
                                                <?php echo htmlspecialchars($pm['pasien_nama']); ?>
                                            </h5>
                                            <div class="d-flex gap-3 text-muted small mb-2">
                                                <span><i class="bi bi-card-text me-1"></i> <?php echo htmlspecialchars($pm['pasien_nik']); ?></span>
                                                <span class="text-primary fw-medium"><i class="bi bi-hospital me-1"></i> <?php echo htmlspecialchars($pm['dari_rs']); ?></span>
                                                <span><i class="bi bi-calendar me-1"></i> <?php echo date('d M Y H:i', strtotime($pm['tanggal_permintaan'])); ?></span>
                                            </div>
                                            <div class="p-3 bg-light rounded-3 mt-2 border border-light">
                                                <i class="bi bi-chat-left-text text-secondary me-2"></i> 
                                                <span class="fst-italic text-dark">"<?php echo htmlspecialchars($pm['keterangan']); ?>"</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-4 text-end mt-3 mt-lg-0">
                                    <div class="d-flex flex-column align-items-end gap-2">
                                        <?php if(($pm['urgensi'] ?? '') == 'urgent'): ?>
                                            <span class="badge rounded-pill bg-danger px-3 py-2 shadow-sm mb-2">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> URGENT
                                            </span>
                                        <?php elseif(($pm['urgensi'] ?? '') == 'biasa'): ?>
                                            <span class="badge rounded-pill bg-warning text-dark px-3 py-2 shadow-sm mb-2">
                                                <i class="bi bi-clock me-1"></i> BIASA
                                            </span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-info px-3 py-2 shadow-sm mb-2">
                                                <i class="bi bi-calendar me-1"></i> TIDAK URGENT
                                            </span>
                                        <?php endif; ?>
                                        
                                        <!-- Tombol Aksi -->
                                        <div class="d-flex gap-2 w-100 justify-content-end">
                                            <button type="button" class="btn-modern btn-danger-modern flex-fill" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#tolakModal"
                                                    data-id="<?php echo $pm['id']; ?>"
                                                    data-nama="<?php echo htmlspecialchars($pm['pasien_nama']); ?>"
                                                    data-dari-rs="<?php echo htmlspecialchars($pm['dari_rs']); ?>">
                                                <i class="bi bi-x-circle"></i> Tolak
                                            </button>
                                            
                                            <button type="button" class="btn-modern btn-success-modern flex-fill" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#kirimModal"
                                                    data-id="<?php echo $pm['id']; ?>"
                                                    data-nama="<?php echo htmlspecialchars($pm['pasien_nama']); ?>"
                                                    data-nik="<?php echo htmlspecialchars($pm['pasien_nik']); ?>"
                                                    data-dari-rs="<?php echo htmlspecialchars($pm['dari_rs']); ?>">
                                                <i class="bi bi-send-check"></i> Kirim Data
                                            </button>
                                        </div>
                                        
                                        <div class="mt-2 small text-muted fst-italic">
                                            <i class="bi bi-key-fill text-warning"></i> Enkripsi kunci RS <?php echo htmlspecialchars($pm['dari_rs']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Histori Pengiriman -->
            <div class="content-card mt-5 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1"><i class="bi bi-check2-all text-success me-2"></i>Data Telah Dikirim</h4>
                        <p class="text-muted mb-0">Riwayat data medis yang telah berhasil dikirim</p>
                    </div>
                    <span class="badge bg-success rounded-pill px-3 py-2">
                        <i class="bi bi-check-circle me-1"></i> <?php echo count($histori_kirim); ?> terkirim
                    </span>
                </div>
                
                <?php if(empty($histori_kirim)): ?>
                    <div class="alert-modern alert-info text-center">
                        <i class="bi bi-info-circle" style="font-size: 1.5rem;"></i>
                        <div class="flex-grow-1">
                            Belum ada riwayat pengiriman data.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="histori-list">
                    <?php foreach($histori_kirim as $hk): 
                        $today = date('Y-m-d');
                        $expired = $hk['tanggal_expired'] ?? '';
                        $is_expired = $expired && $expired < $today;
                    ?>
                    <div class="histori-item p-3 mb-3 border rounded-3 bg-white shadow-sm d-flex align-items-center gap-3">
                        <div class="bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.5rem;">
                            <i class="bi bi-file-earmark-check"></i>
                        </div>
                        
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($hk['pasien_nama']); ?></h6>
                            <div class="d-flex gap-3 text-muted small">
                                <span><i class="bi bi-hospital me-1"></i> Ke: <?php echo htmlspecialchars($hk['dari_rs']); ?></span>
                                <span><i class="bi bi-calendar me-1"></i> <?php echo date('d M Y H:i', strtotime($hk['tanggal_diterima'] ?? $hk['tanggal_permintaan'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <?php if($is_expired): ?>
                                <span class="badge-modern badge-secondary">
                                    <i class="bi bi-hourglass-bottom"></i> EXPIRED
                                </span>
                            <?php else: ?>
                                <?php 
                                $days_left = round((strtotime($expired) - strtotime($today)) / (60 * 60 * 24));
                                ?>
                                <span class="badge-modern badge-success">
                                    <i class="bi bi-check-circle"></i> AKTIF
                                </span>
                                <?php if($days_left <= 3): ?>
                                    <div class="text-danger small mt-1 fw-bold"><i class="bi bi-exclamation-triangle"></i> <?php echo $days_left; ?> hari lagi</div>
                                <?php else: ?>
                                    <div class="text-muted small mt-1"><?php echo $days_left; ?> hari lagi</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Footer -->
            <div class="mt-4 pt-3 border-top text-center">
                <small class="text-muted">
                    <i class="bi bi-shield-check"></i> Semua data dienkripsi end-to-end dengan AES-256
                </small>
            </div>
        </div>
    </div>
    
    <!-- Modal untuk Kirim Data -->
    <div class="modal fade" id="kirimModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-send-check"></i> Kirim Data Pasien
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST" action="" enctype="multipart/form-data" id="kirimForm">
                    <div class="modal-form">
                        <!-- Info Pasien -->
                        <div class="alert alert-info mb-4">
                            <h6><i class="bi bi-person-circle"></i> Data Pasien</h6>
                            <p class="mb-1" id="modalPasienNama"></p>
                            <p class="mb-0 small" id="modalPasienInfo"></p>
                            <input type="hidden" name="permintaan_id" id="modalPermintaanId">
                            <input type="hidden" name="pasien_nik" id="modalPasienNik">
                            <input type="hidden" name="dari_rs" id="modalDariRs">
                        </div>
                        
                        <!-- Info Enkripsi -->
                        <div class="alert alert-warning mb-4">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-shield-lock me-2" style="font-size: 1.5em;"></i>
                                <div>
                                    <h6 class="mb-1">Informasi Enkripsi</h6>
                                    <p class="mb-0 small">
                                        Data akan <strong>dienkripsi dengan kunci RS peminta</strong>.
                                        Hanya RS tersebut yang dapat membuka data ini.
                                        <br>
                                        <span id="encryptionInfo"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- File Upload -->
                        <div class="mb-4">
                            <h6><i class="bi bi-paperclip"></i> Upload Dokumen (Opsional)</h6>
                            <p class="text-muted small">Unggah file rekam medis (PDF, DOC, XLS, JPG, PNG, TXT) maksimal 10MB</p>
                            
                            <div class="file-upload-area" onclick="document.getElementById('fileInput').click()">
                                <i class="bi bi-cloud-upload" style="font-size: 2em;"></i>
                                <p class="mb-2">Klik untuk memilih file</p>
                                <small class="text-muted">Drag & drop atau klik untuk upload</small>
                                <input type="file" name="data_file" id="fileInput" class="d-none" onchange="previewFile()">
                            </div>
                            
                            <div id="filePreview" class="file-preview">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-file-text text-primary me-3" style="font-size: 1.5em;"></i>
                                    <div>
                                        <div id="fileName"></div>
                                        <div id="fileSize" class="small text-muted"></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-auto" onclick="clearFile()">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Text Data -->
                        <div class="mb-4">
                            <h6><i class="bi bi-text-paragraph"></i> Data Teks (Opsional)</h6>
                            <div class="mb-3">
                                <label class="form-label">Riwayat Medis / Catatan</label>
                                <textarea name="text_data" class="form-control" rows="4" 
                                          placeholder="Tambahkan catatan medis, riwayat penyakit, alergi, dll..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Keterangan Tambahan</label>
                                <input type="text" name="keterangan_tambahan" class="form-control" 
                                       placeholder="Contoh: Data lab, hasil rontgen, dll">
                            </div>
                        </div>
                        
                        <!-- Expired Options -->
                        <div class="mb-4">
                            <h6><i class="bi bi-calendar-check"></i> Masa Expired Data</h6>
                            <p class="text-muted small">Pilih berapa lama data dapat diakses oleh RS tujuan</p>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="expired_days" id="exp7" value="7" checked>
                                        <label class="form-check-label" for="exp7">
                                            <strong>7 Hari</strong><br>
                                            <small>1 Minggu</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="expired_days" id="exp14" value="14">
                                        <label class="form-check-label" for="exp14">
                                            <strong>14 Hari</strong><br>
                                            <small>2 Minggu</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="expired_days" id="exp30" value="30">
                                        <label class="form-check-label" for="exp30">
                                            <strong>30 Hari</strong><br>
                                            <small>1 Bulan</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Custom Expired -->
                            <div class="mt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="expired_days" id="expCustom" value="custom">
                                    <label class="form-check-label" for="expCustom">
                                        <strong>Custom Expired</strong>
                                    </label>
                                </div>
                                
                                <div class="input-group mt-2" style="display: none;" id="customExpiredInput">
                                    <input type="number" class="form-control" id="customDays" placeholder="Masukkan jumlah hari" min="1" max="365">
                                    <span class="input-group-text">hari</span>
                                </div>
                                <small class="text-muted d-block mt-1">Maksimal 365 hari (1 tahun)</small>
                            </div>
                        </div>
                        
                        <!-- Konfirmasi -->
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Perhatian:</strong> Setelah masa expired, data tidak dapat diakses lagi oleh RS tujuan.
                        </div>
                        
                        <!-- Modal Footer -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle"></i> Batal
                            </button>
                            <button type="submit" name="kirim_data" class="btn btn-primary">
                                <i class="bi bi-send-check"></i> Kirim Data Terenkripsi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal untuk Kirim Data -->
    <div class="modal fade" id="kirimModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header bg-primary text-white p-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-send-check me-2"></i>Kirim Data Pasien
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST" action="" enctype="multipart/form-data" id="kirimForm">
                    <div class="modal-body p-4">
                        <!-- Info Pasien -->
                        <div class="alert-modern alert-primary mb-4 p-3 border-start border-4 border-primary bg-primary bg-opacity-10">
                            <div class="d-flex">
                                <div class="me-3">
                                    <i class="bi bi-person-circle fs-3 text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1" id="modalPasienNama">-</h6>
                                    <p class="mb-0 small text-muted" id="modalPasienInfo">-</p>
                                </div>
                            </div>
                            <input type="hidden" name="permintaan_id" id="modalPermintaanId">
                            <input type="hidden" name="pasien_nik" id="modalPasienNik">
                            <input type="hidden" name="dari_rs" id="modalDariRs">
                        </div>
                        
                        <!-- Info Enkripsi -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-shield-lock-fill text-warning me-2"></i>
                                <span class="fw-bold text-dark">Enkripsi End-to-End</span>
                            </div>
                            <p class="small text-muted mb-0" id="encryptionInfo">Data akan dienkripsi.</p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Dokumen (Opsional)</label>
                                <div class="file-upload-area border-2 border-dashed rounded-3 p-4 text-center cursor-pointer transition-all hover:bg-light" style="border-color: #cbd5e1;" onclick="document.getElementById('fileInput').click()">
                                    <i class="bi bi-cloud-arrow-up text-primary" style="font-size: 2.5em;"></i>
                                    <p class="mb-1 mt-2 fw-medium">Klik untuk upload file</p>
                                    <small class="text-muted d-block">PDF, DOCX, JPG, PNG (Max 10MB)</small>
                                    <input type="file" name="data_file" id="fileInput" class="d-none" onchange="previewFile()">
                                </div>
                                <div id="filePreview" class="file-preview mt-2 p-2 border rounded bg-light" style="display: none;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center text-truncate">
                                            <i class="bi bi-file-earmark-text text-primary me-2"></i>
                                            <div>
                                                <div id="fileName" class="fw-medium text-truncate" style="max-width: 150px;">Filename</div>
                                                <div id="fileSize" class="small text-muted">0 KB</div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="clearFile()"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Catatan / Riwayat Medis</label>
                                    <textarea name="text_data" class="form-control-modern" rows="4" style="height: 120px;"
                                              placeholder="Tuliskan catatan medis atau tempel teks hasil lab..."></textarea>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label fw-medium">Keterangan Tambahan</label>
                                    <input type="text" name="keterangan_tambahan" class="form-control-modern" 
                                           placeholder="Info file atau lampiran...">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Expired Options -->
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-3"><i class="bi bi-clock-history me-2"></i>Masa Berlaku Akses</label>
                            <div class="d-flex gap-3 flex-wrap">
                                <div class="flex-fill">
                                    <input type="radio" class="btn-check" name="expired_days" id="exp7" value="7" checked>
                                    <label class="btn btn-outline-primary w-100 p-2" for="exp7">
                                        <span class="d-block fw-bold">7 Hari</span>
                                        <span class="small">Standard</span>
                                    </label>
                                </div>
                                <div class="flex-fill">
                                    <input type="radio" class="btn-check" name="expired_days" id="exp14" value="14">
                                    <label class="btn btn-outline-primary w-100 p-2" for="exp14">
                                        <span class="d-block fw-bold">14 Hari</span>
                                        <span class="small">Extensive</span>
                                    </label>
                                </div>
                                <div class="flex-fill">
                                    <input type="radio" class="btn-check" name="expired_days" id="exp30" value="30">
                                    <label class="btn btn-outline-primary w-100 p-2" for="exp30">
                                        <span class="d-block fw-bold">30 Hari</span>
                                        <span class="small">Long Term</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <div class="d-flex align-items-center">
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="radio" name="expired_days" id="expCustom" value="custom">
                                        <label class="form-check-label" for="expCustom">Custom</label>
                                    </div>
                                    <div class="input-group" style="display: none; max-width: 200px;" id="customExpiredInput">
                                        <input type="number" class="form-control form-control-sm" id="customDays" placeholder="Hari" min="1" max="365">
                                        <span class="input-group-text bg-white">hari</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning d-flex align-items-center p-2 small m-0">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <div>Akses data akan otomatis dicabut setelah masa berlaku habis.</div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light p-3 border-top">
                        <button type="button" class="btn-modern btn-secondary-modern" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="kirim_data" class="btn-modern btn-primary-modern px-4">
                            <i class="bi bi-lock-fill me-1"></i> Enkripsi & Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal untuk Tolak Permintaan -->
    <div class="modal fade" id="tolakModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg p-0" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header bg-danger text-white p-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-x-circle me-2"></i>Tolak Permintaan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST" action="" id="tolakForm">
                    <div class="modal-body p-4">
                        <div class="text-center mb-4">
                            <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex p-3 mb-2">
                                <i class="bi bi-exclamation-lg fs-1"></i>
                            </div>
                            <h6 class="fw-bold">Konfirmasi Penolakan</h6>
                            <p class="text-muted small mb-0" id="tolakPasienInfo"></p>
                            <input type="hidden" name="permintaan_id" id="tolakPermintaanId">
                            <input type="hidden" name="dari_rs" id="tolakDariRs">
                            <input type="hidden" name="pasien_nama" id="tolakPasienNama">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Alasan Penolakan</label>
                            <textarea name="alasan_penolakan" class="form-control-modern" rows="3" 
                                      placeholder="Jelaskan alasan penolakan..."></textarea>
                        </div>
                        
                        <p class="small text-danger text-center"><i class="bi bi-info-circle me-1"></i>Aksi ini tidak dapat dibatalkan.</p>
                    </div>
                    
                    <div class="modal-footer bg-light p-3 border-top justify-content-center">
                        <button type="button" class="btn-modern btn-secondary-modern me-2" data-bs-dismiss="modal">Kembali</button>
                        <button type="submit" name="tolak_permintaan" class="btn-modern btn-danger-modern px-4">
                            Tolak Permintaan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Inisialisasi modal
    const kirimModal = document.getElementById('kirimModal');
    kirimModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const permintaanId = button.getAttribute('data-id');
        const pasienNama = button.getAttribute('data-nama');
        const pasienNik = button.getAttribute('data-nik');
        const dariRs = button.getAttribute('data-dari-rs');
        
        // Set data ke form
        document.getElementById('modalPermintaanId').value = permintaanId;
        document.getElementById('modalPasienNik').value = pasienNik;
        document.getElementById('modalDariRs').value = dariRs;
        document.getElementById('modalPasienNama').textContent = pasienNama;
        document.getElementById('modalPasienInfo').textContent = 'NIK: ' + pasienNik + ' • Dari RS: ' + dariRs;
        document.getElementById('encryptionInfo').textContent = 'Data akan dienkripsi dengan kunci RS ' + dariRs + '. Hanya RS ' + dariRs + ' yang dapat membuka data ini.';
        
        // Reset form
        document.getElementById('kirimForm').reset();
        clearFile();
    });
    
    // Inisialisasi modal tolak
    const tolakModal = document.getElementById('tolakModal');
    tolakModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const permintaanId = button.getAttribute('data-id');
        const pasienNama = button.getAttribute('data-nama');
        const dariRs = button.getAttribute('data-dari-rs');
        
        // Set data ke form
        document.getElementById('tolakPermintaanId').value = permintaanId;
        document.getElementById('tolakDariRs').value = dariRs;
        document.getElementById('tolakPasienNama').value = pasienNama;
        document.getElementById('tolakPasienInfo').innerHTML = '<strong>Pasien:</strong> ' + pasienNama + '<br><strong>Dari RS:</strong> ' + dariRs;
        
        // Reset form
        document.getElementById('tolakForm').reset();
        // Restore hidden fields after reset
        document.getElementById('tolakPermintaanId').value = permintaanId;
        document.getElementById('tolakDariRs').value = dariRs;
        document.getElementById('tolakPasienNama').value = pasienNama;
    });
    
    // File preview
    function previewFile() {
        const fileInput = document.getElementById('fileInput');
        const preview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            
            // Format size
            const formatSize = (bytes) => {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            };
            
            fileName.textContent = file.name;
            fileSize.textContent = formatSize(file.size);
            preview.style.display = 'block';
            
            // Validate size
            if (file.size > 10485760) {
                alert('Ukuran file terlalu besar. Maksimal 10MB.');
                clearFile();
            }
        }
    }
    
    function clearFile() {
        document.getElementById('fileInput').value = '';
        document.getElementById('filePreview').style.display = 'none';
    }
    
    // Custom expired input
    document.querySelectorAll('input[name="expired_days"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const customInput = document.getElementById('customExpiredInput');
            if (this.id === 'expCustom') {
                customInput.style.display = 'flex';
                document.getElementById('customDays').focus();
            } else {
                customInput.style.display = 'none';
            }
        });
    });
    
    // Form validation
    document.getElementById('kirimForm').addEventListener('submit', function(e) {
        // Validasi minimal ada file atau teks
        const fileInput = document.getElementById('fileInput');
        const textData = document.querySelector('textarea[name="text_data"]');
        
        if (!fileInput.files[0] && !textData.value.trim()) {
            e.preventDefault();
            alert('Harap upload file ATAU isi data teks.');
            return false;
        }
        
        // Handle custom expired days
        const customRadio = document.getElementById('expCustom');
        if (customRadio.checked) {
            const customDays = document.getElementById('customDays').value;
            if (!customDays || customDays < 1 || customDays > 365) {
                e.preventDefault();
                alert('Harap masukkan jumlah hari yang valid (1-365 hari).');
                return false;
            }
            customRadio.value = customDays;
        }
        
        // Confirmation
        const pasienNama = document.getElementById('modalPasienNama').textContent;
        const dariRs = document.getElementById('modalDariRs').value;
        const expiredDaysInput = document.querySelector('input[name="expired_days"]:checked');
        let expiredDays = expiredDaysInput.value;
        
        if (expiredDaysInput.id === 'expCustom') {
            expiredDays = document.getElementById('customDays').value;
        }
        
        const confirmMsg = `Konfirmasi Pengiriman Data:\n` +
                          `Ke: ${dariRs}\n` +
                          `Pasien: ${pasienNama}\n` +
                          `Durasi Akses: ${expiredDays} hari\n\n` +
                          `Lanjutkan proses enkripsi dan pengiriman?`;
        
        return confirm(confirmMsg);
    });
    </script>
</body>
</html>