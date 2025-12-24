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

$permintaan_kita_raw = getData('permintaan', "dari_rs = '$rs_kode'", 'id DESC');

// **PERBAIKAN: Filter hanya data yang valid dengan lengkap**
$permintaan_kita = [];
foreach($permintaan_kita_raw as $p) {
    // Validasi data yang lengkap
    if(isset($p['id']) && 
       isset($p['pasien_nama']) && !empty(trim($p['pasien_nama'])) &&
       isset($p['status']) && !empty(trim($p['status'])) &&
       isset($p['ke_rs']) && !empty(trim($p['ke_rs'])) &&
       isset($p['pasien_nik']) && !empty(trim($p['pasien_nik']))) {
        $permintaan_kita[] = $p;
    }
}

// Proses HAPUS permintaan
if(isset($_GET['delete']) && isset($_GET['id'])) {
    $permintaan_id = intval($_GET['id']);
    
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
        
        $status = $permintaan['status'];
        
        if($status == 'pending' || $is_expired || $status == 'ditolak') {
            // Hapus dari database
            $result = deleteData('permintaan', $permintaan_id);
            
            if($result['success']) {
                $success = "✅ Permintaan berhasil dihapus!";
                
                // Refresh data
                $permintaan_kita_raw = getData('permintaan', "dari_rs = '$rs_kode'", 'id DESC');
                
                // Filter ulang
                $permintaan_kita = [];
                foreach($permintaan_kita_raw as $p) {
                    if(isset($p['id']) && 
                       isset($p['pasien_nama']) && !empty(trim($p['pasien_nama'])) &&
                       isset($p['status']) && !empty(trim($p['status'])) &&
                       isset($p['ke_rs']) && !empty(trim($p['ke_rs'])) &&
                       isset($p['pasien_nik']) && !empty(trim($p['pasien_nik']))) {
                        $permintaan_kita[] = $p;
                    }
                }
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
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
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
            color: #dc3545;
            text-decoration: none;
        }
        .delete-btn:hover {
            opacity: 1;
            color: #dc3545 !important;
        }
        .btn-detail {
            background: #0dcaf0;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-detail:hover {
            background: #0ba8c8;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        .patient-info {
            color: #0d6efd;
            font-weight: 600;
        }
        .rs-badge {
            background: #6c757d;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.85em;
        }
        .urgensi-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.85em;
        }
    </style>
</head>
<body>
    <!-- Include sidebar -->
    <?php include '../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container mt-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="mb-1"><i class="bi bi-archive text-primary"></i> Arsip Permintaan yang Diajukan</h3>
                    <p class="text-muted mb-0">Menampilkan semua permintaan yang Anda ajukan ke RS lain</p>
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
            
            <!-- Error/Success Messages -->
            <?php if(isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Error</h5>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <h5 class="alert-heading"><i class="bi bi-check-circle"></i> Sukses</h5>
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
            
            <!-- **HAPUS DEBUG PANEL** -->
            
            <!-- Daftar Permintaan -->
            <div class="row">
                <?php if(empty($permintaan_kita)): ?>
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h4 class="mt-3">Belum ada permintaan yang diajukan</h4>
                            <p class="text-muted mb-4">Ajukan permintaan pertama Anda ke RS lain</p>
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
                        // **Data sudah divalidasi, jadi tidak perlu ?? operator**
                        $permintaan_id = $permintaan['id'];
                        $pasien_nama = htmlspecialchars($permintaan['pasien_nama']);
                        $pasien_nik = htmlspecialchars($permintaan['pasien_nik']);
                        $ke_rs = $permintaan['ke_rs'];
                        $status = $permintaan['status'];
                        $urgensi = $permintaan['urgensi'] ?? 'biasa';
                        $keterangan = htmlspecialchars($permintaan['keterangan'] ?? 'Tidak ada keterangan');
                        $tanggal_permintaan = $permintaan['tanggal_permintaan'];
                        
                        $today = date('Y-m-d');
                        $expired_date = $permintaan['tanggal_expired'] ?? '';
                        $is_expired = $expired_date && $expired_date < $today;
                        
                        // Logika decrypt data
                        $has_response_data = false;
                        $response_data = null;
                        
                        if($status == 'diterima' && !empty($permintaan['data_dikirim'])) {
                            $keys_to_try = [
                                getHospitalKey($ke_rs),
                                getHospitalKey($rs_kode),
                                'key-rs001', 'key-rs002', 'key-rs003'
                            ];
                            
                            foreach($keys_to_try as $key) {
                                $decrypted = decryptData($permintaan['data_dikirim'], $key);
                                
                                if(!empty($decrypted)) {
                                    $temp_data = json_decode($decrypted, true);
                                    if($temp_data && is_array($temp_data)) {
                                        $response_data = $temp_data;
                                        $has_response_data = true;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        // Tentukan apakah bisa dihapus
                        $can_delete = ($status == 'pending' || 
                                      $status == 'ditolak' || 
                                      ($status == 'diterima' && $is_expired));
                        
                        $counter++;
                        if($status == 'diterima') $data_diterima_counter++;
                    ?>
                    <div class="col-md-6">
                        <div class="request-card position-relative">
                            
                            <!-- Tombol Hapus -->
                            <?php if($can_delete): ?>
                            <a href="#" class="delete-btn" 
                               onclick="return confirmDelete(<?php echo $permintaan_id; ?>, '<?php echo $pasien_nama; ?>', '<?php echo $ke_rs; ?>')"
                               title="Hapus permintaan">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                            
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1 patient-info">
                                        <i class="bi bi-person-circle"></i>
                                        <?php echo $pasien_nama; ?>
                                    </h5>
                                    <p class="mb-1 text-muted small">
                                        <i class="bi bi-card-text"></i> NIK: <?php echo $pasien_nik; ?>
                                    </p>
                                </div>
                                <div class="text-end">
                                    <span class="rs-badge">
                                        <i class="bi bi-hospital"></i> <?php echo $ke_rs; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Informasi Permintaan -->
                            <div class="mb-3">
                                <div class="row small">
                                    <div class="col-6">
                                        <i class="bi bi-calendar text-primary"></i>
                                        <strong>Tanggal:</strong><br>
                                        <?php echo date('d M Y', strtotime($tanggal_permintaan)); ?>
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
                                        $urgensi_color = $urgensi_badge[$urgensi] ?? 'secondary';
                                        ?>
                                        <span class="badge urgensi-badge bg-<?php echo $urgensi_color; ?>">
                                            <?php echo strtoupper($urgensi); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Keterangan -->
                            <div class="alert alert-light border mb-3">
                                <i class="bi bi-chat-left-text"></i>
                                <strong>Alasan Permintaan:</strong><br>
                                <small><?php echo $keterangan; ?></small>
                            </div>
                            
                            <!-- Status dan Expired -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <?php 
                                    $status_class = 'status-pending';
                                    $status_icon = 'bi-clock';
                                    
                                    if($status == 'diterima') {
                                        $status_class = 'status-diterima';
                                        $status_icon = 'bi-check-circle';
                                    } elseif($status == 'ditolak') {
                                        $status_class = 'status-ditolak';
                                        $status_icon = 'bi-x-circle';
                                    } elseif($is_expired) {
                                        $status_class = 'status-expired';
                                        $status_icon = 'bi-hourglass-bottom';
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?> status-badge">
                                        <i class="bi <?php echo $status_icon; ?>"></i>
                                        <?php echo strtoupper($status); ?>
                                    </span>
                                </div>
                                
                                <div class="text-end">
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
                            
                            <!-- Data Response dari RS Lain -->
                            <?php if($status == 'diterima'): ?>
                                <div class="data-preview">
                                    <h6><i class="bi bi-inbox text-success"></i> Data dari <?php echo $ke_rs; ?></h6>
                                    
                                    <?php if($has_response_data && $response_data): ?>
                                        <div class="alert alert-success small mb-3">
                                            <i class="bi bi-check-circle"></i> Data berhasil dibuka
                                        </div>
                                        
                                        <?php if(isset($response_data['file_data'])): ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-file-earmark-text text-primary me-2"></i>
                                            <div>
                                                <div class="small fw-bold">
                                                    <?php echo htmlspecialchars($response_data['file_data']['original_name'] ?? 'File terlampir'); ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <?php echo round(($response_data['file_data']['file_size'] ?? 0) / 1024, 2); ?> KB
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if(isset($response_data['riwayat_medis']) && !empty($response_data['riwayat_medis'])): ?>
                                        <div class="mb-3">
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
                                        <div class="alert alert-warning small mb-3">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            Data terenkripsi
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Tombol Lihat Detail -->
                                    <div class="mt-3 text-center">
                                        
                                    <!-- **OPTION 1: Relatif path dari pages/ ke pages/detail.php** -->
                                    <a href="detail.php?id=<?php echo $permintaan_id; ?>" class="btn-detail">
                                        <i class="bi bi-eye"></i> Lihat Detail Lengkap
                                    </a>
                                    </div>
                                </div>
                            <?php elseif($status == 'pending'): ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-clock-history"></i>
                                    Menunggu respons dari <?php echo $ke_rs; ?>
                                </div>
                            <?php elseif($status == 'ditolak'): ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-x-circle"></i>
                                    Ditolak oleh <?php echo $ke_rs; ?>
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
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i>
                            Menampilkan <?php echo $counter; ?> permintaan
                            <?php if($data_diterima_counter > 0): ?>
                                (<?php echo $data_diterima_counter; ?> dengan data)
                            <?php endif; ?>
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
        <div class="modal-dialog modal-dialog-centered">
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
        return false; // Prevent default link behavior
    }
    </script>
</body>
</html>