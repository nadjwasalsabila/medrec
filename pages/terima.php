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

// **HAPUS DEBUG LOGGING**
// Inisialisasi variabel tanpa logging
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
$histori_kirim_raw = getData('permintaan', "dari_rs = '$rs_kode' AND status = 'diterima'", 'tanggal_permintaan DESC');

$histori_kirim = [];
foreach($histori_kirim_raw as $p) {
    if(isset($p['id']) && 
       isset($p['pasien_nama']) && !empty(trim($p['pasien_nama'])) &&
       isset($p['ke_rs']) && !empty(trim($p['ke_rs']))) {
        $histori_kirim[] = $p;
    }
}

// Proses kirim data dengan file
if(isset($_POST['kirim_data'])){
    // ... (kode proses kirim data tetap sama, TAPI HAPUS error_log())
    // Hapus semua error_log() di dalam proses ini
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Permintaan Masuk - <?php echo htmlspecialchars($rs_nama); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        /* Style untuk main-content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s;
            min-height: 100vh;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                padding-left: 15px;
                padding-right: 15px;
            }
        }
        
        /* Style untuk status sudah dikirim */
        .status-sent {
            background: #f0fff4 !important;
            border-left: 4px solid #28a745 !important;
        }
        .status-sent .status-badge {
            background: #28a745 !important;
            color: white !important;
        }
        .status-pending {
            background: #fff8f0 !important;
            border-left: 4px solid #ffc107 !important;
        }
        .status-pending .status-badge {
            background: #ffc107 !important;
            color: #000 !important;
        }
        
        .file-upload-box {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        .file-upload-box:hover {
            border-color: #0d6efd;
            background: #e7f1ff;
        }
        .file-upload-box i {
            font-size: 3em;
            color: #6c757d;
            margin-bottom: 15px;
        }
        .file-preview {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 10px;
        }
        .file-icon {
            font-size: 2em;
            margin-right: 10px;
        }
        .file-size {
            color: #6c757d;
            font-size: 0.9em;
        }
        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .expired-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .expired-option {
            flex: 1;
            min-width: 100px;
        }
        .expired-option input[type="radio"] {
            display: none;
        }
        .expired-option label {
            display: block;
            padding: 10px 15px;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            text-align: center;
                cursor: pointer;
            transition: all 0.3s;
        }
        .expired-option input[type="radio"]:checked + label {
            background: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        .expired-option label:hover {
            border-color: #0d6efd;
            background: #e7f1ff;
        }
        .custom-expired-input {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .list-group-item {
            margin-bottom: 15px;
            border-radius: 10px !important;
            border: 1px solid #dee2e6 !important;
        }
        .list-group-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .sent-info {
            background: #d4edda;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <!-- Include sidebar -->
    <?php include '../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3><i class="bi bi-inbox"></i> Permintaan Masuk</h3>
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
            
            <?php if(!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <div class="mt-2">
                    <a href="terima.php" class="btn btn-sm btn-outline-success">Refresh Halaman</a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Daftar Permintaan Masuk -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <i class="bi bi-clock"></i> Permintaan Menunggu
                    <span class="badge bg-dark float-end"><?php echo count($permintaan_masuk); ?> permintaan</span>
                </div>
                <div class="card-body">
                    <?php if(empty($permintaan_masuk)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-check-circle"></i> Tidak ada permintaan yang menunggu.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php 
                            // Cek apakah ada permintaan yang baru saja dikirim
                            $recently_sent = isset($_POST['kirim_data']) ? ($_POST['permintaan_id'] ?? null) : null;
                            
                            foreach($permintaan_masuk as $pm): 
                                // Jika ini permintaan yang baru saja dikirim, tampilkan status "Sudah Dikirim"
                                $is_sent = ($recently_sent == $pm['id']);
                            ?>
                            <div class="list-group-item <?php echo $is_sent ? 'status-sent' : 'status-pending'; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="me-3">
                                        <?php if($is_sent): ?>
                                        <span class="badge status-badge mb-2">
                                            <i class="bi bi-check-circle"></i> Sudah Dikirim
                                        </span>
                                        <?php else: ?>
                                        <span class="badge status-badge mb-2">
                                            <i class="bi bi-clock"></i> Menunggu
                                        </span>
                                        <?php endif; ?>
                                        
                                        <h6 class="mb-1">
                                            <i class="bi bi-person-circle"></i> 
                                            <?php echo htmlspecialchars($pm['pasien_nama'] ?? 'N/A'); ?>
                                            <small class="text-muted">(NIK: <?php echo htmlspecialchars($pm['pasien_nik'] ?? 'N/A'); ?>)</small>
                                        </h6>
                                        <p class="mb-1">
                                            <i class="bi bi-hospital"></i> Dari: <strong><?php echo htmlspecialchars($pm['dari_rs'] ?? 'N/A'); ?></strong>
                                        </p>
                                        <p class="mb-1">
                                            <i class="bi bi-chat-left-text"></i> 
                                            <?php echo htmlspecialchars($pm['keterangan'] ?? 'Tidak ada keterangan'); ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> 
                                            Diminta: <?php echo date('d M Y', strtotime($pm['tanggal_permintaan'] ?? 'now')); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <?php if(($pm['urgensi'] ?? '') == 'urgent'): ?>
                                        <span class="badge bg-danger mb-2">URGENT</span>
                                        <?php elseif(($pm['urgensi'] ?? '') == 'biasa'): ?>
                                        <span class="badge bg-warning text-dark mb-2">BIASA</span>
                                        <?php else: ?>
                                        <span class="badge bg-info mb-2">TIDAK URGENT</span>
                                        <?php endif; ?>
                                        
                                        <?php if($is_sent): ?>
                                        <div class="mt-2">
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-lg"></i> Selesai
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if(!$is_sent): ?>
                                <!-- Form Kirim Data dengan Upload File (HANYA tampil jika belum dikirim) -->
                                <div class="mt-3 p-3 border-top">
                                    <form method="POST" enctype="multipart/form-data" id="formKirim_<?php echo $pm['id']; ?>" 
                                          onsubmit="return validateForm('<?php echo $pm['id']; ?>')">
                                        <input type="hidden" name="permintaan_id" value="<?php echo $pm['id']; ?>">
                                        <input type="hidden" name="dari_rs" value="<?php echo htmlspecialchars($pm['dari_rs']); ?>">
                                        <input type="hidden" name="pasien_nik" value="<?php echo htmlspecialchars($pm['pasien_nik']); ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <!-- File Upload -->
                                                <div class="form-section">
                                                    <h6><i class="bi bi-paperclip"></i> Upload Dokumen</h6>
                                                    <p class="text-muted small">Unggah file rekam medis (PDF, DOC, XLS, JPG, PNG, TXT)</p>
                                                    
                                                    <div class="file-upload-box" onclick="document.getElementById('fileInput_<?php echo $pm['id']; ?>').click()">
                                                        <i class="bi bi-cloud-upload"></i>
                                                        <p class="mb-2">Klik untuk memilih file</p>
                                                        <small class="text-muted">Maksimal 10MB</small>
                                                        <input type="file" name="data_file" id="fileInput_<?php echo $pm['id']; ?>" 
                                                               class="d-none" onchange="previewFile('<?php echo $pm['id']; ?>', this)">
                                                        
                                                        <div id="filePreview_<?php echo $pm['id']; ?>" class="file-preview d-none">
                                                            <div class="d-flex align-items-center">
                                                                <i class="bi bi-file-text file-icon text-primary"></i>
                                                                <div>
                                                                    <div id="fileName_<?php echo $pm['id']; ?>"></div>
                                                                    <div id="fileSize_<?php echo $pm['id']; ?>" class="file-size"></div>
                                                                </div>
                                                                <button type="button" class="btn btn-sm btn-outline-danger ms-auto" 
                                                                        onclick="clearFile('<?php echo $pm['id']; ?>')">
                                                                    <i class="bi bi-x"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="alert alert-info small">
                                                        <i class="bi bi-info-circle"></i>
                                                        File akan dienkripsi sebelum dikirim. Hanya RS tujuan yang bisa membuka.
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <!-- Text Data -->
                                                <div class="form-section">
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
                                                
                                                <!-- Expired Date Options -->
                                                <div class="form-section">
                                                    <h6><i class="bi bi-calendar-check"></i> Masa Expired Data</h6>
                                                    <p class="text-muted small">Pilih berapa lama data dapat diakses oleh RS tujuan</p>
                                                    
                                                    <div class="expired-options">
                                                        <div class="expired-option">
                                                            <input type="radio" name="expired_days" id="exp_7_<?php echo $pm['id']; ?>" value="7" checked>
                                                            <label for="exp_7_<?php echo $pm['id']; ?>">
                                                                <strong>7 Hari</strong><br>
                                                                <small>1 Minggu</small>
                                                            </label>
                                                        </div>
                                                        <div class="expired-option">
                                                            <input type="radio" name="expired_days" id="exp_14_<?php echo $pm['id']; ?>" value="14">
                                                            <label for="exp_14_<?php echo $pm['id']; ?>">
                                                                <strong>14 Hari</strong><br>
                                                                <small>2 Minggu</small>
                                                            </label>
                                                        </div>
                                                        <div class="expired-option">
                                                            <input type="radio" name="expired_days" id="exp_30_<?php echo $pm['id']; ?>" value="30">
                                                            <label for="exp_30_<?php echo $pm['id']; ?>">
                                                                <strong>30 Hari</strong><br>
                                                                <small>1 Bulan</small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Custom Expired Input -->
                                                    <div class="custom-expired-input mt-3">
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="radio" 
                                                                   name="expired_days" id="custom_exp_<?php echo $pm['id']; ?>" value="custom">
                                                            <label class="form-check-label" for="custom_exp_<?php echo $pm['id']; ?>">
                                                                <strong>Custom Expired</strong>
                                                            </label>
                                                        </div>
                                                        
                                                        <div class="input-group">
                                                            <input type="number" 
                                                                   class="form-control custom-expired-days" 
                                                                   id="custom_days_<?php echo $pm['id']; ?>" 
                                                                   placeholder="Masukkan jumlah hari"
                                                                   min="1" max="365"
                                                                   onchange="updateCustomExpired('<?php echo $pm['id']; ?>', this.value)">
                                                            <span class="input-group-text">hari</span>
                                                        </div>
                                                        <small class="text-muted d-block mt-2">
                                                            Maksimal 365 hari (1 tahun). 
                                                            <span id="expired_date_preview_<?php echo $pm['id']; ?>" class="text-primary"></span>
                                                        </small>
                                                    </div>
                                                    
                                                    <div class="alert alert-warning small mt-3">
                                                        <i class="bi bi-exclamation-triangle"></i>
                                                        <strong>Perhatian:</strong> Setelah masa expired, data tidak dapat diakses lagi oleh RS tujuan.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Confirm dan Kirim -->
                                        <div class="border-top pt-3 mt-3">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="confirm_<?php echo $pm['id']; ?>" required>
                                                <label class="form-check-label" for="confirm_<?php echo $pm['id']; ?>">
                                                    <small>
                                                        Saya setuju mengirim data. Data akan <strong>expired sesuai pilihan di atas</strong> 
                                                        dan hanya bisa diakses oleh RS tujuan.
                                                    </small>
                                                </label>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">
                                                    <i class="bi bi-shield-lock"></i> Data terenkripsi end-to-end
                                                </small>
                                                <div>
                                                    <button type="submit" name="kirim_data" class="btn btn-success">
                                                        <i class="bi bi-send-check"></i> Kirim Data
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <?php else: ?>
                                <!-- Jika sudah dikirim, tampilkan info -->
                                <div class="sent-info mt-3">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                                        <div>
                                            <h6 class="mb-1 text-success">Data sudah dikirim!</h6>
                                            <p class="mb-1 small">Data pasien telah dikirim ke RS tujuan. Anda dapat melihatnya di bagian "Data yang Telah Dikirim" di bawah.</p>
                                            <small class="text-muted">
                                                <i class="bi bi-clock"></i> Dikirim: <?php echo date('d M Y H:i'); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Histori Pengiriman -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-check2-all"></i> Data yang Telah Dikirim
                    <span class="badge bg-light text-dark float-end"><?php echo count($histori_kirim); ?> data</span>
                </div>
                <div class="card-body">
                    <?php if(empty($histori_kirim)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Belum ada data yang dikirim.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="bi bi-calendar"></i> Tanggal</th>
                                        <th><i class="bi bi-hospital"></i> Ke RS</th>
                                        <th><i class="bi bi-person"></i> Nama Pasien</th>
                                        <th><i class="bi bi-paperclip"></i> Dokumen</th>
                                        <th><i class="bi bi-clock"></i> Expired</th>
                                        <th><i class="bi bi-flag"></i> Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($histori_kirim as $hk): 
                                        $today = date('Y-m-d');
                                        $expired = $hk['tanggal_expired'] ?? '';
                                        $is_expired = $expired && $expired < $today;
                                        
                                        // Hitung sisa hari
                                        $days_left = 0;
                                        if($expired && !$is_expired) {
                                            $days_left = round((strtotime($expired) - strtotime($today)) / (60 * 60 * 24));
                                        }
                                        
                                        // Cek apakah ada data yang terenkripsi
                                        $has_file = false;
                                        $file_info = '';
                                        if (!empty($hk['data_dikirim'])) {
                                            $has_file = true;
                                            $file_info = 'Data terenkripsi';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($hk['tanggal_diterima'] ?? $hk['tanggal_permintaan'])); ?></td>
                                        <td><strong class="text-primary"><?php echo htmlspecialchars($hk['ke_rs'] ?? 'N/A'); ?></strong></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($hk['pasien_nama'] ?? 'N/A'); ?></div>
                                            <small class="text-muted">NIK: <?php echo htmlspecialchars($hk['pasien_nik'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td>
                                            <?php if($has_file): ?>
                                                <span class="badge bg-info">
                                                    <i class="bi bi-paperclip"></i> <?php echo $file_info; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Teks saja</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($expired): ?>
                                                <?php echo date('d M Y', strtotime($expired)); ?>
                                                <?php if($is_expired): ?>
                                                    <br><span class="badge bg-dark">Expired</span>
                                                <?php else: ?>
                                                    <br>
                                                    <small class="<?php echo $days_left <= 3 ? 'text-danger' : 'text-success'; ?>">
                                                        <i class="bi bi-clock"></i> 
                                                        <?php echo $days_left; ?> hari lagi
                                                    </small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($is_expired): ?>
                                                <span class="badge bg-dark">Expired</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Info Footer -->
            <div class="mt-4 pt-3 border-top">
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i>
                            <?php echo count($permintaan_masuk); ?> permintaan menunggu • 
                            <?php echo count($histori_kirim); ?> data telah dikirim
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            <i class="bi bi-shield-check"></i> Semua data terenkripsi end-to-end
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- End main-content -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function previewFile(formId, input) {
        const file = input.files[0];
        if (file) {
            const preview = document.getElementById('filePreview_' + formId);
            const fileName = document.getElementById('fileName_' + formId);
            const fileSize = document.getElementById('fileSize_' + formId);
            
            // Format size
            const formatSize = (bytes) => {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            };
            
            // Set icon based on file type
            const iconMap = {
                'pdf': 'bi-file-pdf',
                'doc': 'bi-file-word',
                'docx': 'bi-file-word',
                'xls': 'bi-file-excel',
                'xlsx': 'bi-file-excel',
                'jpg': 'bi-file-image',
                'jpeg': 'bi-file-image',
                'png': 'bi-file-image',
                'txt': 'bi-file-text'
            };
            
            const extension = file.name.split('.').pop().toLowerCase();
            const iconClass = iconMap[extension] || 'bi-file-text';
            
            // Update preview
            preview.querySelector('.file-icon').className = 'bi ' + iconClass + ' file-icon text-primary';
            fileName.textContent = file.name;
            fileSize.textContent = formatSize(file.size);
            preview.classList.remove('d-none');
            
            // Validate file size (10MB max)
            if (file.size > 10485760) {
                alert('Ukuran file terlalu besar. Maksimal 10MB.');
                clearFile(formId);
            }
        }
    }
    
    function clearFile(formId) {
        const input = document.getElementById('fileInput_' + formId);
        const preview = document.getElementById('filePreview_' + formId);
        
        input.value = '';
        preview.classList.add('d-none');
    }
    
    function updateCustomExpired(formId, days) {
        const customRadio = document.getElementById('custom_exp_' + formId);
        const preview = document.getElementById('expired_date_preview_' + formId);
        
        if (days && days >= 1 && days <= 365) {
            customRadio.value = days;
            const today = new Date();
            const expiredDate = new Date();
            expiredDate.setDate(today.getDate() + parseInt(days));
            
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            preview.textContent = 'Expired: ' + expiredDate.toLocaleDateString('id-ID', options);
            
            // Auto select custom radio
            customRadio.checked = true;
        } else {
            preview.textContent = '';
        }
    }
    
    function validateForm(formId) {
        const fileInput = document.getElementById('fileInput_' + formId);
        const textData = document.querySelector('#formKirim_' + formId + ' textarea[name="text_data"]');
        const confirmCheck = document.getElementById('confirm_' + formId);
        const expiredDaysInput = document.querySelector('input[name="expired_days"]:checked');
        
        // Minimal harus ada file ATAU teks
        if (!fileInput.files[0] && !textData.value.trim()) {
            alert('Harap upload file ATAU isi data teks.');
            return false;
        }
        
        if (!confirmCheck.checked) {
            alert('Harap konfirmasi persetujuan pengiriman data.');
            return false;
        }
        
        if (!expiredDaysInput) {
            alert('Harap pilih masa expired data.');
            return false;
        }
        
        // Get expired days value
        let expiredDays = expiredDaysInput.value;
        if (expiredDays === 'custom') {
            const customInput = document.getElementById('custom_days_' + formId);
            if (!customInput.value || customInput.value < 1 || customInput.value > 365) {
                alert('Harap masukkan jumlah hari expired yang valid (1-365 hari).');
                customInput.focus();
                return false;
            }
            expiredDays = customInput.value;
        }
        
        // Calculate expired date
        const today = new Date();
        const expiredDate = new Date();
        expiredDate.setDate(today.getDate() + parseInt(expiredDays));
        
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = expiredDate.toLocaleDateString('id-ID', options);
        
        const confirmMsg = fileInput.files[0] ? 
            `Kirim data dengan file "${fileInput.files[0].name}"?\n\n` +
            `Masa expired: ${expiredDays} hari\n` +
            `Tanggal expired: ${formattedDate}\n\n` +
            `Data hanya bisa diakses oleh RS tujuan selama masa expired.` :
            `Kirim data teks saja?\n\n` +
            `Masa expired: ${expiredDays} hari\n` +
            `Tanggal expired: ${formattedDate}\n\n` +
            `Data hanya bisa diakses oleh RS tujuan selama masa expired.`;
        
        return confirm(confirmMsg);
    }
    
    // Initialize expired date preview
    document.addEventListener('DOMContentLoaded', function() {
        // Update preview for all forms
        <?php foreach($permintaan_masuk as $pm): ?>
        updateCustomExpired('<?php echo $pm['id']; ?>', 7); // Default 7 hari
        <?php endforeach; ?>
        
        // Handle custom input change
        document.querySelectorAll('.custom-expired-days').forEach(input => {
            input.addEventListener('input', function() {
                const formId = this.id.replace('custom_days_', '');
                updateCustomExpired(formId, this.value);
            });
        });
        
        // Handle radio change
        document.querySelectorAll('input[name^="expired_days"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const formId = this.id.split('_').pop();
                if (this.id.startsWith('custom_exp_')) {
                    document.getElementById('custom_days_' + formId).focus();
                }
            });
        });
    });
    
    // Drag and drop functionality
    document.querySelectorAll('.file-upload-box').forEach(box => {
        box.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#0d6efd';
            this.style.background = '#e7f1ff';
        });
        
        box.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#dee2e6';
            this.style.background = '#f8f9fa';
        });
        
        box.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#dee2e6';
            this.style.background = '#f8f9fa';
            
            const formId = this.closest('[id^="formKirim_"]')?.id.replace('formKirim_', '');
            if (formId) {
                const fileInput = document.getElementById('fileInput_' + formId);
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    previewFile(formId, fileInput);
                }
            }
        });
    });
    </script>
</body>
</html>