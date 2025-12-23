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

// Ambil data permintaan
$all_permintaan = getData('permintaan', "id = '$permintaan_id'", '', 1);

if(empty($all_permintaan)) {
    $_SESSION['error'] = "❌ Permintaan dengan ID $permintaan_id tidak ditemukan";
    header('Location: berkas.php');
    exit;
}

$permintaan = $all_permintaan[0];

// Cek kepemilikan
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

// **PERBAIKAN CRITICAL: Proses decrypt dengan benar**
$response_data = null;
$has_file = false;
$file_data = null;
$decrypt_error = '';
$file_preview_available = false;

if(!empty($permintaan['data_dikirim'])) {
    // **GUNAKAN FUNGSI BARU: decrypt khusus penerima**
    $decrypted = decryptForRecipient(
        $permintaan['data_dikirim'],
        $permintaan['ke_rs'],  // RS pengirim
        $rs_kode               // RS penerima (kita)
    );
    
    if(!empty($decrypted)) {
        $response_data = json_decode($decrypted, true);
        
        if($response_data && is_array($response_data)) {
            // Debug info
            error_log("✅ Berhasil decrypt data untuk RS: $rs_kode");
            
            if(isset($response_data['file_data'])) {
                $has_file = true;
                $file_data = $response_data['file_data'];
                
                // Cek apakah file bisa dipreview
                $allowed_preview_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
                if(isset($file_data['file_type']) && in_array($file_data['file_type'], $allowed_preview_types)) {
                    $file_preview_available = true;
                }
            }
        } else {
            $decrypt_error = "Data tidak valid setelah decrypt";
        }
    } else {
        $decrypt_error = "Gagal mendecrypt data dengan kunci yang tersedia";
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
        /* ... (CSS tetap sama) ... */
        
        /* TAMBAH CSS UNTUK FILE PREVIEW */
        .file-preview-container {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            background: white;
            max-height: 500px;
            overflow: auto;
        }
        
        .pdf-preview {
            width: 100%;
            height: 400px;
            border: none;
        }
        
        .image-preview {
            max-width: 100%;
            max-height: 400px;
            display: block;
            margin: 0 auto;
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
        
        .unsupported-file {
            padding: 30px;
            text-align: center;
            color: #6c757d;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .view-only-badge {
            background: #6c757d;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8em;
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
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3><i class="bi bi-file-earmark-medical"></i> Detail Data Medis</h3>
                    <p class="text-muted">
                        Mode: <span class="view-only-badge"><i class="bi bi-eye"></i> VIEW-ONLY</span>
                        | Expired: <?php echo date('d/m/Y', strtotime($expired_date)); ?>
                    </p>
                </div>
                <div>
                    <a href="berkas.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali ke Arsip
                    </a>
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
                                <div class="col-md-10">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-file-earmark-text fs-1 text-primary me-3"></i>
                                        <div>
                                            <p class="mb-1"><strong><?php echo htmlspecialchars($file_data['original_name']); ?></strong></p>
                                            <p class="mb-1 small text-muted">
                                                <i class="bi bi-hdd"></i> <?php echo round($file_data['file_size'] / 1024, 2); ?> KB
                                                <span class="mx-2">•</span>
                                                <i class="bi bi-card-text"></i> <?php echo $file_data['file_type']; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- FILE PREVIEW BERDASARKAN TYPE -->
                            <?php if($file_preview_available && isset($file_data['file_data_base64'])): ?>
                            <div class="mt-3">
                                <h6><i class="bi bi-eye"></i> Preview Dokumen:</h6>
                                <div class="file-preview-container mt-2">
                                    <?php 
                                    $file_type = $file_data['file_type'];
                                    $base64_data = $file_data['file_data_base64'];
                                    
                                    if(strpos($file_type, 'image/') === 0): ?>
                                        <!-- Preview Gambar -->
                                        <img src="data:<?php echo $file_type; ?>;base64,<?php echo $base64_data; ?>" 
                                             class="image-preview" 
                                             alt="Preview <?php echo htmlspecialchars($file_data['original_name']); ?>">
                                            
                                    <?php elseif($file_type == 'application/pdf'): ?>
                                        <!-- Preview PDF -->
                                        <iframe src="data:application/pdf;base64,<?php echo $base64_data; ?>" 
                                                class="pdf-preview" 
                                                title="PDF Preview">
                                            Browser Anda tidak mendukung preview PDF.
                                        </iframe>
                                        
                                    <?php elseif($file_type == 'text/plain' || strpos($file_type, 'text/') === 0): ?>
                                        <!-- Preview Text -->
                                        <div class="text-preview">
                                            <?php 
                                            $text_content = base64_decode($base64_data);
                                            echo htmlspecialchars(substr($text_content, 0, 5000));
                                            if(strlen($text_content) > 5000) echo "\n\n... [File terlalu besar, hanya menampilkan 5000 karakter pertama]";
                                            ?>
                                        </div>
                                        
                                    <?php else: ?>
                                        <!-- File tidak support preview -->
                                        <div class="unsupported-file">
                                            <i class="bi bi-file-earmark-x" style="font-size: 3em;"></i>
                                            <h6 class="mt-3">Preview tidak tersedia</h6>
                                            <p class="small">File tipe <?php echo $file_type; ?> hanya dapat dilihat dengan aplikasi yang sesuai.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
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
                <a href="berkas.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar Arsip
                </a>
                
                <?php if($response_data): ?>
                <div class="alert alert-light border d-inline-block m-0 p-2">
                    <i class="bi bi-shield-check text-success"></i>
                    <small class="text-muted">Mode VIEW-ONLY aktif - Tidak ada opsi download untuk keamanan</small>
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
        if(e.target.closest('.file-preview-container')) {
            e.preventDefault();
            alert('Klik kanan dinonaktifkan untuk keamanan data pasien.');
        }
    });
    </script>
</body>
</html>