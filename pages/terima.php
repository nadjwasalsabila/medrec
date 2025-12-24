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

// Inisialisasi variabel
$permintaan_masuk = [];
$histori_kirim = [];
$success = '';
$error = '';

// Ambil permintaan masuk ke RS ini (status pending)
$permintaan_masuk_raw = getData('permintaan', "ke_rs = '$rs_kode' AND status = 'pending'", 'tanggal_permintaan DESC');

// Filter hanya data valid
$permintaan_masuk = [];
foreach($permintaan_masuk_raw as $p) {
    if(isset($p['id']) && 
       isset($p['pasien_nama']) && !empty(trim($p['pasien_nama'])) &&
       isset($p['dari_rs']) && !empty(trim($p['dari_rs']))) {
        $permintaan_masuk[] = $p;
    }
}

// Ambil histori pengiriman
$histori_kirim_raw = getData('permintaan', "dari_rs = '$rs_kode' AND status = 'diterima'", 'tanggal_diterima DESC');

$histori_kirim = [];
foreach($histori_kirim_raw as $p) {
    if(isset($p['id']) && 
       isset($p['pasien_nama']) && !empty(trim($p['pasien_nama'])) &&
       isset($p['ke_rs']) && !empty(trim($p['ke_rs']))) {
        $histori_kirim[] = $p;
    }
}

// Proses kirim data dengan file (dari modal)
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
        $error_upload = '';
        $file_data = [];
        
        // Handle file upload
        if(isset($_FILES['data_file']) && $_FILES['data_file']['error'] == 0){
            $allowed_types = [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'application/vnd.ms-excel' => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'text/plain' => 'txt'
            ];
            
            $file = $_FILES['data_file'];
            $file_type = $file['type'];
            $file_size = $file['size'];
            
            if(!array_key_exists($file_type, $allowed_types)){
                $error_upload = "Jenis file tidak diizinkan";
            }
            elseif($file_size > 10485760){
                $error_upload = "Ukuran file terlalu besar. Maksimal 10MB.";
            }
            else{
                $extension = $allowed_types[$file_type];
                $file_name = 'data_' . $pasien_nik . '_' . time() . '.' . $extension;
                $file_path = '../uploads/' . $file_name;
                
                if(move_uploaded_file($file['tmp_name'], $file_path)){
                    $file_data = [
                        'file_name' => $file_name,
                        'original_name' => $file['name'],
                        'file_type' => $file_type,
                        'file_size' => $file_size,
                        'upload_time' => date('Y-m-d H:i:s')
                    ];
                } else {
                    $error_upload = "Gagal menyimpan file.";
                }
            }
        }
        
        // Text data
        $text_data = $_POST['text_data'] ?? '';
        
        // Gabungkan data
        $pasien_data_array = [
            'pasien_nama' => $permintaan_data['pasien_nama'],
            'pasien_nik' => $pasien_nik,
            'riwayat_medis' => $text_data,
            'file_data' => $file_data,
            'keterangan_tambahan' => $_POST['keterangan_tambahan'] ?? '',
            'timestamp' => date('Y-m-d H:i:s'),
            'dikirim_oleh' => $rs_kode,
            'expired_days_set' => $expired_days
        ];
        
        $pasien_data = json_encode($pasien_data_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Enkripsi data
        $key_for_data = getHospitalKey($permintaan_data['dari_rs']); // Kunci RS pengirim
        $data_terenkripsi = encryptData($pasien_data, $key_for_data);
        
        if(empty($data_terenkripsi)) {
            $error_upload = "Gagal mengenkripsi data";
        }
        
        if(empty($error_upload)){
            // Update database
            $update_data = [
                'status' => 'diterima',
                'data_dikirim' => $data_terenkripsi,
                'tanggal_expired' => date('Y-m-d', strtotime("+{$expired_days} days")),
                'tanggal_diterima' => date('Y-m-d H:i:s')
            ];
            
            $result = updateData('permintaan', $permintaan_id, $update_data);
            
            if($result['success']){
                // Log histori
                createData('histori', [
                    'permintaan_id' => $permintaan_id,
                    'rs_id' => $rs_kode,
                    'aksi' => 'mengirim_data',
                    'keterangan' => 'Mengirim data pasien ' . $permintaan_data['pasien_nama'] . 
                                   ' ke ' . $permintaan_data['dari_rs'] . ' (Expired: ' . $expired_days . ' hari)',
                    'waktu' => date('Y-m-d H:i:s')
                ]);
                
                $success = "✅ Data berhasil dikirim ke RS " . $permintaan_data['dari_rs'] . "!";
                
                // Refresh data
                $permintaan_masuk_raw = getData('permintaan', "ke_rs = '$rs_kode' AND status = 'pending'", 'tanggal_permintaan DESC');
                $permintaan_masuk = [];
                foreach($permintaan_masuk_raw as $p) {
                    if(isset($p['id']) && 
                       isset($p['pasien_nama']) && !empty(trim($p['pasien_nama'])) &&
                       isset($p['dari_rs']) && !empty(trim($p['dari_rs']))) {
                        $permintaan_masuk[] = $p;
                    }
                }
                
                $histori_kirim_raw = getData('permintaan', "dari_rs = '$rs_kode' AND status = 'diterima'", 'tanggal_diterima DESC');
                $histori_kirim = [];
                foreach($histori_kirim_raw as $p) {
                    if(isset($p['id']) && 
                       isset($p['pasien_nama']) && !empty(trim($p['pasien_nama'])) &&
                       isset($p['ke_rs']) && !empty(trim($p['ke_rs']))) {
                        $histori_kirim[] = $p;
                    }
                }
            } else {
                $error = "❌ Gagal mengirim data";
            }
        } else {
            $error = "❌ " . $error_upload;
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
            transition: margin-left 0.3s;
            min-height: 100vh;
            background: #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                padding-left: 15px;
                padding-right: 15px;
            }
        }
        
        .request-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            transition: all 0.3s;
        }
        
        .request-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #0d6efd;
        }
        
        .urgensi-badge {
            font-size: 0.8em;
            padding: 4px 10px;
            border-radius: 15px;
        }
        
        .patient-name {
            color: #0d6efd;
            font-weight: 600;
        }
        
        .btn-send {
            background: #198754;
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
    </style>
</head>
<body>
    <div class="topbar">
        <button id="sidebarToggle" class="btn btn-secondary">
            <i class="bi bi-list"></i>
        </button>
        <div class="ms-auto badge bg-linear-gradient(180deg, #2c3e50, #1a2530)">Sistem Aktif</div>
    </div>
    <!-- Include sidebar -->
    <?php include '../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3><i class="bi bi-inbox text-primary"></i> Permintaan Masuk</h3>
                    <p class="text-muted">RS: <strong><?php echo htmlspecialchars($rs_kode); ?> - <?php echo htmlspecialchars($rs_nama); ?></strong></p>
                </div>
                <div>
                    <a href="ajukan.php" class="btn btn-outline-primary">
                        <i class="bi bi-send"></i> Ajukan Permintaan
                    </a>
                    <a href="berkas.php" class="btn btn-outline-success ms-2">
                        <i class="bi bi-archive"></i> Arsip Permintaan
                    </a>
                </div>
            </div>
            
            <!-- Alerts -->
            <?php if(!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Daftar Permintaan Masuk -->
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-clock"></i> Permintaan Menunggu</h5>
                    <small class="text-muted">Ada <?php echo count($permintaan_masuk); ?> permintaan yang perlu ditanggapi</small>
                </div>
                <div class="card-body">
                    <?php if(empty($permintaan_masuk)): ?>
                        <div class="empty-state">
                            <i class="bi bi-check-circle"></i>
                            <h5 class="mt-3">Tidak ada permintaan yang menunggu</h5>
                            <p class="text-muted">Semua permintaan telah ditanggapi</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($permintaan_masuk as $pm): ?>
                        <div class="request-card">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            <div class="bg-primary text-white rounded-circle p-2" style="width: 40px; height: 40px; text-align: center;">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h5 class="patient-name mb-1">
                                                <?php echo htmlspecialchars($pm['pasien_nama']); ?>
                                            </h5>
                                            <p class="mb-1 text-muted small">
                                                <i class="bi bi-card-text"></i> NIK: <?php echo htmlspecialchars($pm['pasien_nik']); ?>
                                                <span class="mx-2">•</span>
                                                <i class="bi bi-hospital"></i> Dari: <?php echo htmlspecialchars($pm['dari_rs']); ?>
                                            </p>
                                            <p class="mb-2 small">
                                                <i class="bi bi-calendar"></i> 
                                                Diminta: <?php echo date('d M Y', strtotime($pm['tanggal_permintaan'])); ?>
                                            </p>
                                            <p class="mb-0 small text-muted">
                                                <i class="bi bi-chat-left-text"></i> 
                                                <?php echo htmlspecialchars($pm['keterangan']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 text-end">
                                    <?php if(($pm['urgensi'] ?? '') == 'urgent'): ?>
                                        <span class="badge bg-danger urgensi-badge mb-2">URGENT</span>
                                    <?php elseif(($pm['urgensi'] ?? '') == 'biasa'): ?>
                                        <span class="badge bg-warning urgensi-badge mb-2">BIASA</span>
                                    <?php else: ?>
                                        <span class="badge bg-info urgensi-badge mb-2">TIDAK URGENT</span>
                                    <?php endif; ?>
                                    
                                    <br>
                                    
                                    <!-- Tombol Kirim Data - Trigger Modal -->
                                    <button type="button" class="btn-send" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#kirimModal"
                                            data-id="<?php echo $pm['id']; ?>"
                                            data-nama="<?php echo htmlspecialchars($pm['pasien_nama']); ?>"
                                            data-nik="<?php echo htmlspecialchars($pm['pasien_nik']); ?>"
                                            data-dari-rs="<?php echo htmlspecialchars($pm['dari_rs']); ?>">
                                        <i class="bi bi-send-check"></i> Kirim Data
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Histori Pengiriman -->
            <div class="histori-card mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5><i class="bi bi-check2-all text-success"></i> Data yang Telah Dikirim</h5>
                        <p class="text-muted small">Riwayat data yang telah Anda kirim</p>
                    </div>
                    <span class="badge bg-success">
                        <?php echo count($histori_kirim); ?> data
                    </span>
                </div>
                
                <?php if(empty($histori_kirim)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Belum ada data yang dikirim.
                    </div>
                <?php else: ?>
                    <?php foreach($histori_kirim as $hk): 
                        $today = date('Y-m-d');
                        $expired = $hk['tanggal_expired'] ?? '';
                        $is_expired = $expired && $expired < $today;
                    ?>
                    <div class="histori-item <?php echo $is_expired ? 'expired-item' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">
                                    <i class="bi bi-person-circle"></i>
                                    <?php echo htmlspecialchars($hk['pasien_nama']); ?>
                                </h6>
                                <p class="mb-1 small text-muted">
                                    <i class="bi bi-hospital"></i> Ke: <?php echo htmlspecialchars($hk['ke_rs']); ?>
                                    <span class="mx-2">•</span>
                                    <i class="bi bi-calendar"></i> 
                                    <?php echo date('d M Y', strtotime($hk['tanggal_diterima'] ?? $hk['tanggal_permintaan'])); ?>
                                </p>
                            </div>
                            <div class="text-end">
                                <?php if($is_expired): ?>
                                    <span class="badge bg-dark">Expired</span>
                                <?php else: ?>
                                    <?php 
                                    $days_left = round((strtotime($expired) - strtotime($today)) / (60 * 60 * 24));
                                    ?>
                                    <span class="badge bg-success">Aktif</span>
                                    <br>
                                    <small class="text-muted"><?php echo $days_left; ?> hari lagi</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Footer -->
            <div class="mt-4 pt-3 border-top text-center">
                <small class="text-muted">
                    <i class="bi bi-shield-check"></i> Semua data terenkripsi end-to-end
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
                                <i class="bi bi-send-check"></i> Kirim Data
                            </button>
                        </div>
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
        
        // Reset form
        document.getElementById('kirimForm').reset();
        clearFile();
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
    
    // Drag and drop
    const fileUploadArea = document.querySelector('.file-upload-area');
    fileUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#0d6efd';
        this.style.background = '#e7f1ff';
    });
    
    fileUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = '#dee2e6';
        this.style.background = '#f8f9fa';
    });
    
    fileUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '#dee2e6';
        this.style.background = '#f8f9fa';
        
        const fileInput = document.getElementById('fileInput');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            previewFile();
        }
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
        const expiredDaysInput = document.querySelector('input[name="expired_days"]:checked');
        let expiredDays = expiredDaysInput.value;
        
        if (expiredDaysInput.id === 'expCustom') {
            expiredDays = document.getElementById('customDays').value;
        }
        
        const confirmMsg = `Kirim data untuk pasien:\n\n` +
                          `${pasienNama}\n\n` +
                          `Masa expired: ${expiredDays} hari\n\n` +
                          `Apakah Anda yakin?`;
        
        return confirm(confirmMsg);
    });
    </script>
</body>
</html>