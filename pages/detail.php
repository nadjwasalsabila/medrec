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
    <link rel="icon" type="image/png" href="/assets/img/logo.png">
    <title>Detail Data - <?php echo htmlspecialchars($_SESSION['rs_nama']); ?></title>
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
        
        /* Watermark Overlay */
        .watermark-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            opacity: 0.1;
            user-select: none;
            overflow: hidden;
        }
        
        .watermark-text {
            font-size: 3em;
            font-weight: 800;
            color: var(--dark);
            transform: rotate(-30deg);
            text-align: center;
            line-height: 1.5;
            text-transform: uppercase;
            letter-spacing: 5px;
        }
        
        .watermark-info {
            font-size: 1.2em;
            color: var(--secondary);
            margin-top: 20px;
            font-weight: 600;
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
                    <h2 class="fw-bold text-dark"><i class="bi bi-file-earmark-medical text-primary me-2"></i>Detail Data Pasien</h2>
                    <p class="text-muted">Berkas medis digital dari RS <strong class="text-primary"><?php echo htmlspecialchars($permintaan['ke_rs']); ?></strong></p>
                </div>
                <div>
                    <a href="berkas.php" class="btn-modern btn-secondary-modern px-4">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                </div>
            </div>
            
            <!-- Info Dekripsi Berhasil -->
            <div class="alert-modern alert-success mb-4 p-4 shadow-sm border-0 bg-white">
                <div class="d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center p-3 me-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-shield-check-fill fs-2"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Dekripsi Berhasil</h5>
                        <p class="text-muted mb-0">Data telah didekripsi aman menggunakan kunci privat RS <?php echo htmlspecialchars($rs_kode); ?></p>
                    </div>
                </div>
            </div>
            
            <?php
            // Mark as read jika belum dibaca
            if(empty($permintaan['is_read']) || $permintaan['is_read'] == false) {
                updateData('permintaan', $permintaan['id'], ['is_read' => true]);
            }
            ?>
            
            <!-- Info Pasien -->
            <!-- Info Pasien -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="content-card h-100">
                        <h5 class="fw-bold mb-4 border-bottom pb-2"><i class="bi bi-person-bounding-box text-primary me-2"></i>Informasi Pasien</h5>
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded me-3">
                                <i class="bi bi-person fs-5"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Nama Lengkap</small>
                                <span class="fw-bold text-dark fs-5"><?php echo htmlspecialchars($permintaan['pasien_nama']); ?></span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded me-3">
                                <i class="bi bi-card-text fs-5"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Nomor Induk Kependudukan (NIK)</small>
                                <span class="fw-bold text-dark fs-5"><?php echo htmlspecialchars($permintaan['pasien_nik']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="content-card h-100">
                        <h5 class="fw-bold mb-4 border-bottom pb-2"><i class="bi bi-info-circle text-primary me-2"></i>Detail Permintaan</h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <small class="text-muted d-block mb-1">RS Pengirim</small>
                                <div class="badge bg-light text-primary border px-3 py-2">
                                    <i class="bi bi-hospital me-1"></i> <?php echo htmlspecialchars($permintaan['ke_rs']); ?>
                                </div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block mb-1">Tanggal Diterima</small>
                                <div class="fw-bold text-dark"><?php echo date('d M Y, H:i', strtotime($permintaan['tanggal_diterima'])); ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block mb-1">Berlaku Hingga</small>
                                <div class="fw-bold text-danger"><?php echo date('d M Y', strtotime($permintaan['tanggal_expired'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Expired Warning -->
            <?php 
            $expired_date = $permintaan['tanggal_expired'];
            $days_left = round((strtotime($expired_date) - strtotime($today)) / (60 * 60 * 24));
            
            if ($days_left <= 3): ?>
            <div class="alert-modern alert-warning mb-4">
                <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
                <div class="flex-grow-1">
                    <strong>Masa Berlaku Hampir Habis!</strong><br>
                    Data ini akan kadaluarsa dan dihapus otomatis dalam <strong><?php echo $days_left; ?> hari</strong>.
                </div>
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
                    
                <div class="content-card mb-4" id="file-viewer-section">
                    <h5 class="fw-bold mb-4 border-bottom pb-2"><i class="bi bi-file-earmark-medical text-primary me-2"></i>Berkas Medis Digital</h5>
                    
                    <!-- Tampilkan file berdasarkan tipe -->
                    <?php 
                    $file_type = $file_data['file_type'] ?? '';
                    $is_pdf = strpos($file_type, 'pdf') !== false;
                    $is_image = strpos($file_type, 'image') !== false;
                    ?>
                    
                    <div class="mb-3">
                        <?php if ($is_pdf && file_exists($file_path)): ?>
                            <!-- Secure PDF Viewer using PDF.js -->
                            <div id="pdf-viewer-container" class="bg-secondary p-4 rounded-3 shadow-inner" style="max-height: 800px; overflow-y: auto; text-align: center; position: relative;" oncontextmenu="return false;">
                                <!-- Watermark Overlay -->
                                <div class="watermark-overlay">
                                    <div class="watermark-text">
                                        CONFIDENTIAL<br>
                                        <?php echo htmlspecialchars($rs_kode); ?><br>
                                        <span class="watermark-info"><?php echo date('d M Y H:i:s', strtotime($permintaan['tanggal_diterima'])); ?></span>
                                    </div>
                                </div>
                                
                                <div id="pdf-loader" class="text-white">
                                    <div class="spinner-border text-light" role="status"></div>
                                    <p class="mt-2">Memuat dokumen aman...</p>
                                </div>
                                <div id="pdf-canvas-container"></div>
                            </div>
                            <!-- PDF.js Script (unchanged) -->
                            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
                            <script>
                                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                                const url = '<?php echo $file_path; ?>';
                                const container = document.getElementById('pdf-canvas-container');
                                const loader = document.getElementById('pdf-loader');
                                pdfjsLib.getDocument(url).promise.then(function(pdf) {
                                    loader.style.display = 'none';
                                    for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                                        pdf.getPage(pageNum).then(function(page) {
                                            const scale = 1.5;
                                            const viewport = page.getViewport({scale: scale});
                                            const canvas = document.createElement('canvas');
                                            canvas.className = 'mb-3 shadow-sm rounded';
                                            canvas.style.maxWidth = '100%';
                                            canvas.style.height = 'auto';
                                            canvas.oncontextmenu = function(e) { e.preventDefault(); return false; };
                                            const context = canvas.getContext('2d');
                                            canvas.height = viewport.height;
                                            canvas.width = viewport.width;
                                            container.appendChild(canvas);
                                            const renderContext = { canvasContext: context, viewport: viewport };
                                            page.render(renderContext);
                                        });
                                    }
                                }).catch(function(error) {
                                    loader.innerHTML = '<div class="alert-modern alert-danger">Gagal memuat dokumen.</div>';
                                    console.error('Error loading PDF:', error);
                                });
                            </script>
                        <?php elseif ($is_image && file_exists($file_path)): ?>
                            <div class="text-center p-3 bg-light rounded-3 border border-1">
                                <img src="<?php echo $file_path; ?>" 
                                     alt="Preview" 
                                     class="img-fluid rounded shadow-sm" 
                                     style="max-height: 500px;"
                                     oncontextmenu="return false;">
                            </div>
                        <?php else: ?>
                            <div class="alert-modern alert-info mb-0">
                                <i class="bi bi-info-circle-fill fs-5 text-info"></i>
                                <div class="flex-grow-1">File tidak dapat dipreview secara langsung.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!file_exists($file_path)): ?>
                    <div class="alert-modern alert-warning">
                        <i class="bi bi-exclamation-triangle-fill fs-5 text-warning"></i>
                        <div class="flex-grow-1">File tidak ditemukan di server.</div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Text Data -->
                <?php if (isset($decrypted_data['riwayat_medis']) && !empty(trim($decrypted_data['riwayat_medis']))): ?>
                <div class="content-card mb-4" id="medical-history-section">
                    <h5 class="fw-bold mb-4 border-bottom pb-2"><i class="bi bi-file-text text-primary me-2"></i>Riwayat Medis Pasien</h5>
                    <div class="p-4 bg-light rounded-3 border" style="white-space: pre-line; font-family: 'Inter', sans-serif; line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($decrypted_data['riwayat_medis'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Keterangan Tambahan -->
                <?php if (isset($decrypted_data['keterangan_tambahan']) && !empty(trim($decrypted_data['keterangan_tambahan']))): ?>
                <div class="content-card mb-4">
                    <h5 class="fw-bold mb-4 border-bottom pb-2"><i class="bi bi-chat-left-text text-primary me-2"></i>Catatan Tambahan</h5>
                    <div class="p-4 bg-light rounded-3 border">
                        <?php echo htmlspecialchars($decrypted_data['keterangan_tambahan']); ?>
                    </div>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="alert-modern alert-danger p-5 text-center">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex p-3 mb-3">
                        <i class="bi bi-exclamation-triangle-fill fs-1"></i>
                    </div>
                    <h5 class="fw-bold">Gagal Mendekripsi Data</h5>
                    <p class="mb-0">Kunci enkripsi tidak sesuai atau data telah rusak.</p>
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
    <script>
    // Security Scripts
    document.addEventListener('keydown', function(e) {
        // Block Ctrl+S (Save), Ctrl+P (Print), Ctrl+U (Source)
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'p' || e.key === 'u')) {
            e.preventDefault();
            alert('Fitur ini dinonaktifkan untuk keamanan data.');
            return false;
        }
    });

    // Disable Right Click Globally
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });
    </script>
</body>
</html>