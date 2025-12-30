<?php
session_start();
if (!isset($_SESSION['rs_kode'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';
require_once '../config/encryption.php';

$id = $_GET['id'] ?? 0;
$rs_kode = $_SESSION['rs_kode'];

// Ambil data permintaan
$permintaan = getById('permintaan', $id);

if (!$permintaan) {
    die('<div class="alert alert-danger">Data tidak ditemukan</div>');
}

// Validasi akses: hanya RS yang meminta (dari_rs) yang bisa melihat
if ($permintaan['dari_rs'] !== $rs_kode) {
    die('<div class="alert alert-danger">Anda tidak memiliki akses ke data ini</div>');
}

// Validasi status: hanya yang sudah diterima
if ($permintaan['status'] !== 'diterima') {
    die('<div class="alert alert-danger">Data belum tersedia atau belum diterima</div>');
}

// Cek expired
$today = date('Y-m-d');
if (!empty($permintaan['tanggal_expired']) && $permintaan['tanggal_expired'] < $today) {
    die('<div class="alert alert-danger">Data sudah expired</div>');
}

// Dekripsi data
$data_dikirim = $permintaan['data_dikirim'] ?? '';
$decrypted_data = null;

if (!empty($data_dikirim)) {
    // Gunakan kunci RS kita sendiri (RS yang meminta)
    $key = getHospitalKey($rs_kode);
    $decrypted = decryptData($data_dikirim, $key);
    
    if ($decrypted) {
        $decrypted_data = json_decode($decrypted, true);
    }
}

if (!$decrypted_data) {
    die('<div class="alert alert-danger">Gagal mendekripsi data atau data tidak ditemukan</div>');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Detail Data - <?php echo htmlspecialchars($_SESSION['rs_nama']); ?></title>
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
                padding-left: 15px;
                padding-right: 15px;
            }
        }
        
        .info-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #0d6efd;
        }
        
        .file-preview {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px dashed #dee2e6;
        }
        
        .data-content {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .btn-download {
            background: linear-gradient(45deg, #198754, #157347);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
            color: white;
        }
        
        .expired-warning {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .decryption-success {
            background: linear-gradient(45deg, #198754, #157347);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <button id="sidebarToggle" class="btn btn-secondary">
            <i class="bi bi-list"></i>
        </button>
        <div class="ms-auto badge bg-light text-dark">
            <i class="bi bi-shield-check"></i> Data Terenkripsi
        </div>
    </div>
    
    <?php include '../components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3><i class="bi bi-file-earmark-medical text-primary"></i> Detail Data Pasien</h3>
                    <p class="text-muted">Data dari <?php echo htmlspecialchars($permintaan['ke_rs']); ?></p>
                </div>
                <div>
                    <a href="berkas.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            
            <!-- Info Dekripsi Berhasil -->
            <div class="decryption-success">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-3" style="font-size: 2em;"></i>
                    <div>
                        <h5 class="mb-1">Data Berhasil Didekripsi!</h5>
                        <p class="mb-0">Menggunakan kunci RS <?php echo htmlspecialchars($rs_kode); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Info Pasien -->
            <div class="info-box">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="bi bi-person-circle"></i> Informasi Pasien</h5>
                        <p><strong>Nama:</strong> <?php echo htmlspecialchars($permintaan['pasien_nama']); ?></p>
                        <p><strong>NIK:</strong> <?php echo htmlspecialchars($permintaan['pasien_nik']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="bi bi-info-circle"></i> Informasi Permintaan</h5>
                        <p><strong>RS Pengirim:</strong> <?php echo htmlspecialchars($permintaan['ke_rs']); ?></p>
                        <p><strong>Tanggal Dikirim:</strong> <?php echo date('d M Y H:i', strtotime($permintaan['tanggal_diterima'])); ?></p>
                        <p><strong>Expired:</strong> <?php echo date('d M Y', strtotime($permintaan['tanggal_expired'])); ?></p>
                        <p><strong>Dikirim Oleh:</strong> <?php echo htmlspecialchars($decrypted_data['dikirim_oleh'] ?? 'RS'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Expired Warning -->
            <?php 
            $expired_date = $permintaan['tanggal_expired'];
            $days_left = round((strtotime($expired_date) - strtotime($today)) / (60 * 60 * 24));
            
            if ($days_left <= 3): ?>
            <div class="expired-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Perhatian!</strong> Data akan expired dalam <?php echo $days_left; ?> hari
            </div>
            <?php endif; ?>
            
            <!-- Data yang Dikirim -->
            <?php if ($decrypted_data): ?>
                <!-- File Preview -->
                <?php if (isset($decrypted_data['file_data']) && !empty($decrypted_data['file_data'])): 
                    $file_data = $decrypted_data['file_data'];
                    $file_path = '../uploads/' . ($file_data['file_name'] ?? '');
                ?>
                <div class="file-preview">
                    <h5><i class="bi bi-paperclip"></i> Dokumen Lampiran</h5>
                    
                    <div class="d-flex align-items-center mb-3 p-3 bg-light rounded">
                        <i class="bi bi-file-earmark-text text-primary" style="font-size: 2em; margin-right: 15px;"></i>
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($file_data['original_name'] ?? 'file'); ?></h6>
                            <p class="mb-0 small text-muted">
                                <?php 
                                $size = $file_data['file_size'] ?? 0;
                                echo round($size / 1024, 2); ?> KB
                                <span class="mx-2">•</span>
                                <?php echo htmlspecialchars($file_data['file_type'] ?? 'Unknown type'); ?>
                                <span class="mx-2">•</span>
                                Upload: <?php echo date('d M Y H:i', strtotime($decrypted_data['dikirim_pada'] ?? 'now')); ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Tampilkan file berdasarkan tipe -->
                    <?php 
                    $file_type = $file_data['file_type'] ?? '';
                    $is_pdf = strpos($file_type, 'pdf') !== false;
                    $is_image = strpos($file_type, 'image') !== false;
                    ?>
                    
                    <div class="mb-3">
                        <?php if ($is_pdf && file_exists($file_path)): ?>
                            <iframe src="<?php echo $file_path; ?>" 
                                    width="100%" 
                                    height="600px" 
                                    style="border: 1px solid #dee2e6; border-radius: 5px;">
                                Browser Anda tidak mendukung preview PDF. <a href="<?php echo $file_path; ?>" download>Download file</a>
                            </iframe>
                        <?php elseif ($is_image && file_exists($file_path)): ?>
                            <div class="text-center">
                                <img src="<?php echo $file_path; ?>" 
                                     alt="Preview" 
                                     class="img-fluid rounded" 
                                     style="max-height: 500px;">
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                File tidak dapat dipreview secara langsung. Silakan download untuk melihat.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Tombol Download -->
                    <?php if (file_exists($file_path)): ?>
                    <div class="text-center">
                        <a href="<?php echo $file_path; ?>" 
                           class="btn-download"
                           download="<?php echo htmlspecialchars($file_data['original_name'] ?? 'file'); ?>">
                            <i class="bi bi-download"></i> Download File
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        File tidak ditemukan di server.
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Text Data -->
                <?php if (isset($decrypted_data['riwayat_medis']) && !empty(trim($decrypted_data['riwayat_medis']))): ?>
                <div class="data-content">
                    <h5><i class="bi bi-text-paragraph text-info"></i> Riwayat Medis</h5>
                    <div class="p-3 bg-light rounded" style="white-space: pre-line;">
                        <?php echo nl2br(htmlspecialchars($decrypted_data['riwayat_medis'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Keterangan Tambahan -->
                <?php if (isset($decrypted_data['keterangan_tambahan']) && !empty(trim($decrypted_data['keterangan_tambahan']))): ?>
                <div class="data-content">
                    <h5><i class="bi bi-chat-left-text text-success"></i> Keterangan Tambahan</h5>
                    <div class="p-3 bg-light rounded">
                        <?php echo htmlspecialchars($decrypted_data['keterangan_tambahan']); ?>
                    </div>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    Gagal mendekripsi data. Mungkin kunci enkripsi tidak sesuai.
                </div>
            <?php endif; ?>
            
            <!-- Footer -->
            <div class="mt-4 pt-3 border-top text-center">
                <small class="text-muted">
                    <i class="bi bi-shield-check"></i> Data ini dienkripsi dengan AES-256 dan hanya dapat diakses oleh RS <?php echo htmlspecialchars($rs_kode); ?>
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>