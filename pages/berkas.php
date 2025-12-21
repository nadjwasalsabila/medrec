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

// **PERBAIKAN: Pastikan session key sudah sesuai**
if(!isset($_SESSION['rs_key'])) {
    $_SESSION['rs_key'] = getHospitalKey($rs_kode);
    error_log("🎯 Session rs_key di-set untuk RS: " . $rs_kode . " -> " . substr($_SESSION['rs_key'], 0, 10) . "...");
}

// Debug info
error_log("=== BERKAS.PHP - ARSIP PERMINTAAN ===");
error_log("RS KITA: " . $rs_kode);
error_log("RS NAMA: " . $rs_nama);
error_log("RS KEY: " . (isset($_SESSION['rs_key']) ? substr($_SESSION['rs_key'], 0, 20) . '...' : 'TIDAK ADA'));

// **PERBAIKAN: Ambil permintaan yang KITA AJUKAN**
$permintaan_kita = getData('permintaan', "dari_rs = '$rs_kode'", 'id DESC');

error_log("📊 Jumlah permintaan kita: " . count($permintaan_kita));

// **PERBAIKAN: Debug detail setiap permintaan**
foreach($permintaan_kita as $index => $p) {
    error_log("📋 Permintaan #" . ($index+1) . ": ID=" . $p['id'] . 
              ", Pasien=" . $p['pasien_nama'] . 
              ", Status=" . $p['status'] . 
              ", DataDikirim=" . (!empty($p['data_dikirim']) ? "YES (" . strlen($p['data_dikirim']) . " chars)" : "NO"));
}

// **PERBAIKAN: Filter hanya data yang valid**
$permintaan_kita = array_filter($permintaan_kita, function($item) {
    return isset($item['id']) && !empty($item['pasien_nama']);
});

// Proses HAPUS permintaan
if(isset($_GET['delete']) && isset($_GET['id'])) {
    $permintaan_id = $_GET['id'];
    
    // Cari permintaan yang KITA ajukan
    $permintaan = null;
    foreach($permintaan_kita as $p) {
        if($p['id'] == $permintaan_id) {
            $permintaan = $p;
            break;
        }
    }
    
    if($permintaan) {
        // Hanya bisa hapus jika status bukan 'diterima' atau sudah expired
        $today = date('Y-m-d');
        $expired_date = $permintaan['tanggal_expired'] ?? '';
        $is_expired = $expired_date && $expired_date < $today;
        
        if(($permintaan['status'] ?? '') == 'pending' || $is_expired || ($permintaan['status'] ?? '') == 'ditolak') {
            // Hapus dari database
            $result = deleteData('permintaan', $permintaan_id);
            
            if($result['success']) {
                $success = "✅ Permintaan berhasil dihapus!";
                
                // Refresh data
                $permintaan_kita = getData('permintaan', "dari_rs = '$rs_kode'", 'id DESC');
                    
                // Filter ulang
                $permintaan_kita = array_filter($permintaan_kita, function($item) {
                    return isset($item['id']) && !empty($item['pasien_nama']);
                });
            } else {
                $error = "❌ Gagal menghapus permintaan";
            }
        } else {
            $error = "❌ Tidak dapat menghapus permintaan yang masih aktif dan belum expired!";
        }
    } else {
        $error = "❌ Permintaan tidak ditemukan atau bukan milik Anda!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Arsip Permintaan - <?php echo htmlspecialchars($rs_nama); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
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
        
        .request-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .request-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8em;
            padding: 5px 12px;
            border-radius: 20px;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-diterima { background: #198754; color: white; }
        .status-ditolak { background: #dc3545; color: white; }
        .status-expired { background: #6c757d; color: white; }
        .data-preview {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            border-left: 4px solid #0d6efd;
        }
        .delete-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        .request-card:hover .delete-btn {
            opacity: 1;
        }
        .delete-btn:hover {
            color: #dc3545 !important;
        }
        .btn-detail {
            background: #0dcaf0;
            color: white;
            border: none;
            transition: all 0.3s;
        }
        .btn-detail:hover {
            background: #0ba8c8;
            transform: translateY(-2px);
        }
        .encryption-badge {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            color: white;
            font-size: 0.7em;
        }
    </style>
</head>
<body>
    <!-- Include sidebar -->
    <?php include '../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container mt-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Arsip Permintaan</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3><i class="bi bi-archive"></i> Arsip Permintaan yang Diajukan</h3>
                    <p class="text-muted">Menampilkan semua permintaan yang Anda ajukan ke RS lain</p>
                </div>
                <div>
                    <a href="ajukan.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Permintaan Baru
                    </a>
                    <a href="terima.php" class="btn btn-outline-success ms-2">
                        <i class="bi bi-inbox"></i> Permintaan Masuk
                    </a>
                </div>
            </div>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Info Panel -->
            <div class="alert alert-info mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle me-3" style="font-size: 1.5em;"></i>
                    <div>
                        <h6 class="mb-1">Arsip Permintaan Anda</h6>
                        <p class="mb-0 small">
                            Ini adalah arsip permintaan yang <strong>Anda ajukan ke RS lain</strong>.
                            <br><span class="text-success">✓ Diterima:</span> RS tujuan sudah mengirim data
                            <br><span class="text-warning">⏳ Pending:</span> Menunggu respons RS tujuan
                            <br><span class="text-danger">✗ Ditolak:</span> Permintaan ditolak RS tujuan
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- **PERBAIKAN: Debug Panel untuk Developer** -->
            <div class="alert alert-warning mb-4">
                <h6><i class="bi bi-bug"></i> Debug Info</h6>
                <small>
                    RS Kode: <strong><?php echo $rs_kode; ?></strong><br>
                    Total Permintaan: <strong><?php echo count($permintaan_kita); ?></strong><br>
                    Key: <?php echo substr($_SESSION['rs_key'] ?? 'NO KEY', 0, 20); ?>...
                </small>
            </div>
            
            <!-- Daftar Permintaan -->
            <div class="row">
                <?php if(empty($permintaan_kita)): ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 4em; color: #dee2e6;"></i>
                            <h4 class="text-muted mt-3">Belum ada permintaan yang diajukan</h4>
                            <p class="text-muted">Ajukan permintaan pertama Anda ke RS lain</p>
                            <a href="ajukan.php" class="btn btn-primary">
                                <i class="bi bi-send"></i> Ajukan Permintaan Pertama
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php 
                    $counter = 0;
                    $data_diterima_counter = 0;
                    foreach($permintaan_kita as $permintaan): 
                        $today = date('Y-m-d');
                        $expired_date = $permintaan['tanggal_expired'] ?? '';
                        $is_expired = $expired_date && $expired_date < $today;
                        
                        // **PERBAIKAN: Logika decrypt data**
                        $has_response_data = false;
                        $response_data = null;
                        $decrypt_error = '';
                        
                        if($permintaan['status'] == 'diterima' && !empty($permintaan['data_dikirim'])) {
                            error_log("🔄 Proses decrypt data ID: " . $permintaan['id']);
                            
                            // **KUNCI YANG DICOBA:**
                            // 1. Kunci RS pengirim (ke_rs) - karena data dienkripsi dengan kunci RS kita
                            // 2. Kunci kita sendiri (dari_rs)
                            // 3. Kunci default
                            
                            $keys_to_try = [
                                getHospitalKey($permintaan['ke_rs']),  // RS pengirim
                                getHospitalKey($rs_kode),              // RS kita
                                'key-rs001', 'key-rs002', 'key-rs003'  // Default
                            ];
                            
                            foreach($keys_to_try as $key_index => $key) {
                                error_log("  🔑 Coba key $key_index: " . substr($key, 0, 10) . "...");
                                $decrypted = decryptData($permintaan['data_dikirim'], $key);
                                
                                if(!empty($decrypted)) {
                                    $temp_data = json_decode($decrypted, true);
                                    if($temp_data && is_array($temp_data)) {
                                        $response_data = $temp_data;
                                        $has_response_data = true;
                                        error_log("  ✅ Berhasil decrypt dengan key $key_index");
                                        break;
                                    }
                                }
                            }
                            
                            if(!$has_response_data) {
                                $decrypt_error = "Gagal membuka data terenkripsi";
                                error_log("  ❌ Gagal decrypt semua key");
                            }
                        }
                        
                        // Tentukan apakah bisa dihapus
                        $can_delete = ($permintaan['status'] == 'pending' || 
                                      $permintaan['status'] == 'ditolak' || 
                                      ($permintaan['status'] == 'diterima' && $is_expired));
                        
                        $counter++;
                    ?>
                    <div class="col-md-6">
                        <div class="request-card position-relative">
                            
                            <!-- Tombol Hapus -->
                            <?php if($can_delete): ?>
                            <a href="#" class="delete-btn text-danger" 
                               onclick="confirmDelete(<?php echo $permintaan['id']; ?>, '<?php echo htmlspecialchars($permintaan['pasien_nama']); ?>', '<?php echo $permintaan['ke_rs']; ?>')"
                               title="Hapus permintaan">
                                <i class="bi bi-trash" style="font-size: 1.2em;"></i>
                            </a>
                            <?php endif; ?>
                            
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1">
                                        <i class="bi bi-person-circle text-primary"></i>
                                        <?php echo htmlspecialchars($permintaan['pasien_nama']); ?>
                                    </h5>
                                    <p class="mb-1 text-muted small">
                                        NIK: <?php echo htmlspecialchars($permintaan['pasien_nik']); ?>
                                    </p>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-info">
                                        <i class="bi bi-hospital"></i> <?php echo $permintaan['ke_rs']; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Informasi Permintaan -->
                            <div class="mb-3">
                                <div class="row small">
                                    <div class="col-6">
                                        <i class="bi bi-calendar text-primary"></i>
                                        <strong>Tanggal:</strong><br>
                                        <?php echo date('d M Y', strtotime($permintaan['tanggal_permintaan'])); ?>
                                    </div>
                                    <div class="col-6">
                                        <i class="bi bi-flag text-warning"></i>
                                        <strong>Urgensi:</strong><br>
                                        <?php 
                                        $urgensi_badge = [
                                            'urgent' => 'danger',
                                            'biasa' => 'warning',
                                            'tidak_urgent' => 'info'
                                        ];
                                        $urgensi_color = $urgensi_badge[$permintaan['urgensi']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $urgensi_color; ?>">
                                            <?php echo strtoupper($permintaan['urgensi']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Keterangan -->
                            <div class="alert alert-light border mb-3">
                                <i class="bi bi-chat-left-text"></i>
                                <strong>Alasan Permintaan:</strong><br>
                                <small><?php echo htmlspecialchars($permintaan['keterangan']); ?></small>
                            </div>
                            
                            <!-- Status -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <?php 
                                    $status_class = 'status-pending';
                                    $status_icon = 'bi-clock';
                                    
                                    if($permintaan['status'] == 'diterima') {
                                        $status_class = 'status-diterima';
                                        $status_icon = 'bi-check-circle';
                                    } elseif($permintaan['status'] == 'ditolak') {
                                        $status_class = 'status-ditolak';
                                        $status_icon = 'bi-x-circle';
                                    } elseif($is_expired) {
                                        $status_class = 'status-expired';
                                        $status_icon = 'bi-hourglass-bottom';
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <i class="bi <?php echo $status_icon; ?>"></i>
                                        <?php echo strtoupper($permintaan['status']); ?>
                                    </span>
                                </div>
                                
                                <div>
                                    <?php if($expired_date): ?>
                                        <small class="<?php echo $is_expired ? 'text-danger' : 'text-success'; ?>">
                                            <i class="bi bi-calendar"></i>
                                            Exp: <?php echo date('d/m/Y', strtotime($expired_date)); ?>
                                        </small>
                                        <?php if(!$is_expired): ?>
                                            <?php 
                                            $days_left = round((strtotime($expired_date) - strtotime($today)) / (60 * 60 * 24));
                                            if($days_left <= 3): ?>
                                                <br><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> <?php echo $days_left; ?> hari lagi</small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- **PERBAIKAN: Data Response dari RS Lain (jika ada) -->
                            <?php if($permintaan['status'] == 'diterima'): ?>
                                <div class="data-preview">
                                    <h6><i class="bi bi-inbox text-success"></i> Data dari <?php echo $permintaan['ke_rs']; ?></h6>
                                    
                                    <?php if($has_response_data && $response_data): ?>
                                        <!-- Jika berhasil decrypt -->
                                        <div class="alert alert-success small">
                                            <i class="bi bi-check-circle"></i> Data berhasil dibuka
                                        </div>
                                        
                                        <?php if(isset($response_data['file_data'])): ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-file-earmark-text text-primary me-2"></i>
                                            <div>
                                                <div class="small fw-bold"><?php echo htmlspecialchars($response_data['file_data']['original_name'] ?? 'File'); ?></div>
                                                <div class="small text-muted">
                                                    <?php echo round(($response_data['file_data']['file_size'] ?? 0) / 1024, 2); ?> KB
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if(isset($response_data['riwayat_medis']) && !empty($response_data['riwayat_medis'])): ?>
                                        <div class="mb-2">
                                            <i class="bi bi-text-paragraph text-info"></i>
                                            <strong>Preview Riwayat:</strong><br>
                                            <div class="small text-muted mt-1">
                                                <?php 
                                                $preview = substr($response_data['riwayat_medis'], 0, 100);
                                                echo nl2br(htmlspecialchars($preview));
                                                if(strlen($response_data['riwayat_medis']) > 100) echo '...';
                                                ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                    <?php else: ?>
                                        <!-- Jika gagal decrypt -->
                                        <div class="alert alert-warning small">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            Data terenkripsi. 
                                            <?php if(!empty($decrypt_error)): ?>
                                                <br><small class="text-danger"><?php echo htmlspecialchars($decrypt_error); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- **PERBAIKAN: TOMBOL LIHAT DETAIL - SELALU TAMPIL untuk status diterima -->
                                    <div class="mt-3 text-center">
                                        <a href="detail.php?id=<?php echo $permintaan['id']; ?>" 
                                           class="btn btn-detail btn-sm">
                                            <i class="bi bi-eye"></i> Lihat Detail Lengkap
                                        </a>
                                    </div>
                                </div>
                            <?php elseif($permintaan['status'] == 'pending'): ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-clock-history"></i>
                                    Menunggu respons dari <?php echo $permintaan['ke_rs']; ?>
                                </div>
                            <?php elseif($permintaan['status'] == 'ditolak'): ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-x-circle"></i>
                                    Ditolak oleh <?php echo $permintaan['ke_rs']; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Info Delete (jika tidak bisa dihapus) -->
                            <?php if(!$can_delete && $permintaan['status'] == 'diterima' && !$is_expired): ?>
                            <div class="mt-2 small text-muted text-center">
                                <i class="bi bi-info-circle"></i> Data aktif, tunggu expired untuk menghapus
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Footer Info -->
            <?php if(!empty($permintaan_kita)): ?>
            <div class="mt-4 pt-3 border-top">
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i>
                            Menampilkan <?php echo $counter; ?> permintaan 
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            <i class="bi bi-shield-check"></i> Semua data terenkripsi
                        </small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div> <!-- End main-content -->
    
    <!-- Modal konfirmasi hapus -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle"></i> Konfirmasi Hapus
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-trash text-danger" style="font-size: 3em;"></i>
                        <h5 class="mt-3">Hapus Permintaan?</h5>
                    </div>
                    <p>Anda akan menghapus permintaan untuk:</p>
                    <div class="alert alert-warning">
                        <strong>Pasien:</strong> <span id="deletePatientName"></span><br>
                        <strong>RS Tujuan:</strong> <span id="deleteRSTujuan"></span>
                    </div>
                    <p class="text-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <strong>Perhatian:</strong> Aksi ini tidak dapat dibatalkan!
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <a href="#" id="deleteConfirmLink" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Ya, Hapus
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Fungsi konfirmasi hapus dengan modal Bootstrap
    function confirmDelete(id, nama_pasien, rs_tujuan) {
        document.getElementById('deletePatientName').textContent = nama_pasien;
        document.getElementById('deleteRSTujuan').textContent = rs_tujuan;
        document.getElementById('deleteConfirmLink').href = `berkas.php?delete=1&id=${id}`;
        
        const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        modal.show();
    }
    
    // Auto refresh setiap 30 detik untuk update status
    setInterval(() => {
        console.log("🔄 Auto-refresh arsip...");
        location.reload();
    }, 30000);
    </script>
</body>
</html>