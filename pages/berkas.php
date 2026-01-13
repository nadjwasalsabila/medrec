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

// Ambil permintaan yang KITA AJUKAN
$permintaan_kita = getData('permintaan', "dari_rs = '$rs_kode'");
// (Logika mark as read dipindah ke detail.php agar badge tidak langsung hilang)

usort($permintaan_kita, function($a, $b) {
    return strtotime($b['created']) - strtotime($a['created']);
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
        
        $status = $permintaan['status'];
        
        if($status == 'pending' || $is_expired || $status == 'ditolak') {
            // Hapus dari database
            $result = deleteData('permintaan', $permintaan_id);
            
            if($result['success']) {
                $success = "✅ Permintaan berhasil dihapus!";
                
                // Log histori
                createData('histori', [
                    'permintaan_id' => $permintaan_id,
                    'rs_id' => $rs_kode,
                    'aksi' => 'hapus_permintaan',
                    'keterangan' => 'Menghapus permintaan untuk pasien ' . $permintaan['pasien_nama'],
                    'waktu' => date('Y-m-d H:i:s')
                ]);
                
                // Refresh data
                $permintaan_kita = getData('permintaan', "dari_rs = '$rs_kode'");
                usort($permintaan_kita, function($a, $b) {
                    return strtotime($b['created']) - strtotime($a['created']);
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
    <link rel="icon" type="image/png" href="/assets/img/logo.png">
    <title>Arsip Permintaan - <?php echo htmlspecialchars($rs_nama); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/modern-theme.css">
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
        
        .request-card[data-status="diterima"]::before { background: var(--success); }
        .request-card[data-status="pending"]::before { background: var(--warning); }
        .request-card[data-status="ditolak"]::before { background: var(--danger); }
        .request-card[data-status="expired"]::before { background: var(--secondary); }
        
        .request-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .data-preview {
            background: var(--light-blue);
            border-radius: var(--radius-md);
            padding: 20px;
            margin-top: 20px;
            border-left: 4px solid var(--primary-blue);
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
        .delete-btn-inline {
            color: #dc3545;
            font-size: 1.1em;
            opacity: 0.7;
            transition: all 0.2s;
        }
        .delete-btn-inline:hover {
            opacity: 1;
            color: #dc3545;
            transform: scale(1.1);
        }
        .decryption-success {
            background: linear-gradient(45deg, #198754, #157347);
            color: white;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .decryption-failed {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include '../components/topbar.php'; ?>
    
    <?php include '../components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container mt-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark"><i class="bi bi-folder2-open text-primary me-2"></i>Berkas Diterima</h2>
                    <p class="text-muted">Arsip data medis yang diterima dari Rumah Sakit lain</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="ajukan.php" class="btn-modern btn-primary-modern">
                        <i class="bi bi-plus-lg"></i> Permintaan Baru
                    </a>
                </div>
            </div>
            
            <!-- Error/Success Messages -->
            <?php if(isset($error)): ?>
            <div class="alert-modern alert-danger mb-4">
                <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.5rem;"></i>
                <div class="flex-grow-1">
                    <strong>Error</strong><br>
                    <?php echo $error; ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
            <div class="alert-modern alert-success mb-4">
                <i class="bi bi-check-circle-fill" style="font-size: 1.5rem;"></i>
                <div class="flex-grow-1">
                    <strong>Sukses</strong><br>
                    <?php echo $success; ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Search Box -->
            <div class="mb-4">
                <div class="position-relative">
                    <input type="text" 
                           id="searchPatient" 
                           class="form-control form-control-lg ps-5" 
                           placeholder="Cari nama pasien..." 
                           style="border-radius: 12px; border: 2px solid var(--gray-200); transition: all 0.3s;">
                    <i class="bi bi-search position-absolute text-muted" 
                       style="left: 18px; top: 50%; transform: translateY(-50%); font-size: 1.2rem;"></i>
                </div>
            </div>
            
            <!-- Info Panel -->
            <div class="row mb-5">
                <div class="col-md-3">
                    <div class="content-card p-3 d-flex align-items-center mb-3 mb-md-0">
                        <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle me-3 flex-shrink-0" style="width: 50px; height: 50px;">
                            <i class="bi bi-check-lg fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Diterima</h6>
                            <small class="text-muted">Data siap dibuka</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="content-card p-3 d-flex align-items-center mb-3 mb-md-0">
                        <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle me-3 flex-shrink-0" style="width: 50px; height: 50px;">
                            <i class="bi bi-hourglass-split fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Pending</h6>
                            <small class="text-muted">Menunggu respons</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="content-card p-3 d-flex align-items-center mb-3 mb-md-0">
                        <div class="d-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle me-3 flex-shrink-0" style="width: 50px; height: 50px;">
                            <i class="bi bi-x-lg fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Ditolak</h6>
                            <small class="text-muted">Permintaan ditolak</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="content-card p-3 d-flex align-items-center">
                        <div class="d-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-secondary rounded-circle me-3 flex-shrink-0" style="width: 50px; height: 50px;">
                            <i class="bi bi-archive fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Arsip</h6>
                            <small class="text-muted">Total Permintaan</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Daftar Permintaan -->
            <div class="row">
                <?php if(empty($permintaan_kita)): ?>
                    <div class="col-12">
                        <div class="text-center py-5 content-card">
                            <div class="bg-light rounded-circle d-inline-flex p-4 mb-3">
                                <i class="bi bi-folder2-open text-secondary" style="font-size: 3rem;"></i>
                            </div>
                            <h4 class="text-dark">Belum ada berkas diterima</h4>
                            <p class="text-muted mb-4">Data yang dikirimkan oleh RS lain akan muncul di sini.</p>
                            <a href="ajukan.php" class="btn-modern btn-primary-modern">
                                <i class="bi bi-send"></i> Ajukan Permintaan
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php 
                    $counter = 0;
                    $data_diterima_counter = 0;
                    
                    foreach($permintaan_kita as $permintaan): 
                        $permintaan_id = $permintaan['id'] ?? 0;
                        $pasien_nama = htmlspecialchars($permintaan['pasien_nama'] ?? '');
                        $pasien_nik = htmlspecialchars($permintaan['pasien_nik'] ?? '');
                        $ke_rs = $permintaan['ke_rs'] ?? '';
                        $status = $permintaan['status'] ?? 'pending';
                        $urgensi = $permintaan['urgensi'] ?? 'biasa';
                        $keterangan = htmlspecialchars($permintaan['keterangan'] ?? 'Tidak ada keterangan');
                        $tanggal_permintaan = $permintaan['tanggal_permintaan'] ?? date('Y-m-d H:i:s');
                        
                        $today = date('Y-m-d');
                        $expired_date = $permintaan['tanggal_expired'] ?? '';
                        $is_expired = $expired_date && $expired_date < $today;
                        
                        // Logika decrypt data - HANYA jika status 'diterima'
                        $has_response_data = false;
                        $response_data = null;
                        $decryption_success = false;
                        $is_revoked = false; // Cek apakah data dicabut
                        
                        if($status == 'diterima' && !empty($permintaan['data_dikirim'])) {
                            // Gunakan kunci RS kita sendiri (kita yang meminta)
                            $our_key = getHospitalKey($rs_kode);
                            
                            if ($our_key) {
                                $decrypted = decryptData($permintaan['data_dikirim'], $our_key);
                                
                                if(!empty($decrypted)) {
                                    $temp_data = json_decode($decrypted, true);
                                    if($temp_data && is_array($temp_data)) {
                                        $response_data = $temp_data;
                                        $has_response_data = true;
                                        $decryption_success = true;
                                    }
                                }
                            }
                        } elseif($status == 'diterima' && empty($permintaan['data_dikirim'])) {
                            // Status diterima tapi data kosong = dicabut
                            $is_revoked = true;
                        }
                        
                        // Tentukan apakah bisa dihapus
                        $can_delete = ($status == 'pending' || 
                                      $status == 'ditolak' || 
                                      ($status == 'diterima' && $is_expired));
                        
                        $counter++;
                        if($status == 'diterima') $data_diterima_counter++;
                    ?>
                    <div class="col-lg-6 mb-4">
                        <div class="request-card h-100 d-flex flex-column" data-status="<?php echo $status == 'diterima' && $is_expired ? 'expired' : $status; ?>">
                            
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-size: 1.25rem;">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold text-dark mb-0">
                                            <?php echo $pasien_nama; ?>
                                        </h5>
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-card-text me-1"></i> <?php echo $pasien_nik; ?>
                                        </div>
                                    </div>
                                </div>

                                <?php if($can_delete): ?>
                                <a href="#"
                                   class="btn btn-light rounded-circle text-danger shadow-sm border-0"
                                   onclick="return confirmDelete('<?php echo $permintaan_id; ?>', '<?php echo $pasien_nama; ?>', '<?php echo $ke_rs; ?>')"
                                   title="Hapus permintaan"
                                   style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Status Badges -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <?php 
                                $status_badge = '';
                                
                                if($is_revoked) {
                                    $status_badge = '<span class="badge-modern badge-secondary"><i class="bi bi-lock-fill"></i> DICABUT</span>';
                                } elseif($is_expired && $status == 'diterima') {
                                    $status_badge = '<span class="badge-modern badge-secondary"><i class="bi bi-hourglass-bottom"></i> EXPIRED</span>';
                                } elseif($status == 'diterima') {
                                    $status_badge = '<span class="badge-modern badge-success"><i class="bi bi-check-circle"></i> DITERIMA</span>';
                                } elseif($status == 'ditolak') {
                                    $status_badge = '<span class="badge-modern badge-danger"><i class="bi bi-x-circle"></i> DITOLAK</span>';
                                } else {
                                    $status_badge = '<span class="badge-modern badge-warning"><i class="bi bi-clock-history"></i> PENDING</span>';
                                }
                                echo $status_badge;
                                ?>
                                
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-hospital text-primary me-1"></i> <?php echo $ke_rs; ?>
                                </span>
                            </div>

                            <!-- Informasi Grid -->
                            <div class="row g-3 small mb-4">
                                <div class="col-6">
                                    <div class="text-muted mb-1">Tanggal Permintaan</div>
                                    <div class="fw-medium text-dark"><i class="bi bi-calendar3 me-1"></i> <?php echo date('d M Y', strtotime($tanggal_permintaan)); ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted mb-1">Urgensi</div>
                                    <div>
                                        <?php if($urgensi == 'urgent'): ?>
                                            <span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> URGENT</span>
                                        <?php elseif($urgensi == 'biasa'): ?>
                                            <span class="text-warning fw-bold text-dark"><i class="bi bi-clock"></i> BIASA</span>
                                        <?php else: ?>
                                            <span class="text-info fw-bold"><i class="bi bi-info-circle"></i> TIDAK URGENT</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if($expired_date): ?>
                                <div class="col-12">
                                    <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded-2 border border-light">
                                        <span class="text-muted">Masa Berlaku:</span>
                                        <span class="<?php echo $is_expired ? 'text-danger fw-bold' : 'text-success fw-medium'; ?>">
                                            <?php echo date('d M Y', strtotime($expired_date)); ?>
                                            <?php if(!$is_expired): 
                                                $days_left = round((strtotime($expired_date) - strtotime($today)) / (60 * 60 * 24));
                                                if($days_left <= 3) echo " <span class='text-danger ms-1'>($days_left hari lagi)</span>";
                                            endif; ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Keterangan -->
                            <div class="p-3 bg-light rounded-3 mb-4 border border-light flex-grow-1">
                                <div class="d-flex align-items-start text-secondary">
                                    <i class="bi bi-chat-left-text me-2 mt-1"></i>
                                    <div>
                                        <strong class="d-block text-dark small mb-1"><?php echo ($status == 'ditolak') ? 'Alasan Penolakan:' : 'Alasan Permintaan:'; ?></strong>
                                        <span class="fst-italic small">"<?php echo $keterangan; ?>"</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Data Response dari RS Lain -->
                            <?php if($status == 'diterima'): ?>
                                <div class="mt-auto">
                                    <div class="data-preview border-0 shadow-none bg-primary bg-opacity-10 p-3 rounded-3 position-relative overflow-hidden">
                                        <!-- Decorative Icon -->
                                        <i class="bi bi-file-earmark-medical position-absolute text-primary opacity-10" style="font-size: 5rem; top: -10px; right: -10px; transform: rotate(15deg);"></i>
                                        
                                        <h6 class="fw-bold text-primary mb-3 position-relative"><i class="bi bi-inbox-fill me-2"></i>Berkas Diterima</h6>
                                        
                                        <?php if($decryption_success && $response_data): ?>
                                            <?php if($is_expired): ?>
                                                <!-- Pesan untuk data expired -->
                                                <div class="alert-modern alert-warning p-2 mb-3 bg-white bg-opacity-75 border-0 shadow-sm">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
                                                        <div class="small fw-bold text-dark">Data sudah expired!</div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <!-- Pesan untuk data aktif -->
                                                <div class="alert-modern alert-success p-2 mb-3 bg-white bg-opacity-75 border-0 shadow-sm">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-shield-lock-fill me-2 text-success"></i>
                                                        <div class="small">
                                                            <strong class="d-block text-dark">Dekripsi Berhasil</strong>
                                                            <span class="text-muted" style="font-size: 0.75rem;">Key: RS <?php echo $rs_kode; ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if(isset($response_data['file_data'])): ?>
                                            <div class="file-card bg-white p-2 rounded-3 shadow-sm mb-3 d-flex align-items-center position-relative">
                                                <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-2 me-2">
                                                    <i class="bi bi-file-earmark-pdf fs-5"></i>
                                                </div>
                                                <div class="overflow-hidden">
                                                    <div class="fw-bold small text-truncate text-dark">
                                                        <?php echo htmlspecialchars($response_data['file_data']['original_name'] ?? 'File terlampir'); ?>
                                                    </div>
                                                    <div class="small text-muted" style="font-size: 0.7rem;">
                                                        <?php echo round(($response_data['file_data']['file_size'] ?? 0) / 1024, 2); ?> KB
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if(!$is_expired): ?>
                                            <a href="detail.php?id=<?php echo $permintaan_id; ?>" class="btn-modern btn-primary-modern w-100 justify-content-center btn-sm py-2 shadow-sm position-relative">
                                                <span>Buka Berkas</span> <i class="bi bi-arrow-right ms-2"></i>
                                            </a>
                                            <?php else: ?>
                                            <button disabled class="btn btn-secondary w-100 btn-sm py-2 opacity-75 position-relative">
                                                <i class="bi bi-eye-slash-fill me-2"></i> Tidak Dapat Diakses
                                            </button>
                                            <?php endif; ?>
                                            
                                        <?php elseif($is_revoked): ?>
                                            <div class="alert-modern alert-secondary bg-white bg-opacity-75 border-0 shadow-sm p-3 text-center position-relative">
                                                <i class="bi bi-file-earmark-x fs-1 text-secondary opacity-50 mb-2"></i>
                                                <div class="fw-bold text-dark">Akses Dicabut</div>
                                                <div class="small text-muted">File sudah tidak tersedia</div>
                                            </div>
                                        <?php else: ?>
                                             <div class="alert-modern alert-danger bg-white bg-opacity-75 border-0 shadow-sm p-2 position-relative">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-x-circle-fill me-2 text-danger"></i>
                                                    <div class="small fw-bold text-dark">Gagal Dekripsi</div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php elseif($status == 'pending'): ?>
                                <div class="alert alert-warning">
                            <?php elseif($status == 'pending'): ?>
                                <div class="alert-modern alert-warning small p-2 m-0 bg-white">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-clock-history me-2 text-warning fs-5"></i>
                                        <span class="text-dark">Menunggu respons dari RS <?php echo $ke_rs; ?></span>
                                    </div>
                                </div>
                            <?php elseif($status == 'ditolak'): ?>
                                <div class="alert-modern alert-danger small p-2 m-0 bg-white">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-x-circle-fill me-2 text-danger fs-5"></i>
                                        <span class="text-dark">Ditolak oleh RS <?php echo $ke_rs; ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Footer Info -->
            <?php if(!empty($permintaan_kita)): ?>
            <!-- Footer Info -->
            <?php if(!empty($permintaan_kita)): ?>
            <div class="mt-5 pt-4 border-top border-2">
                <div class="d-flex justify-content-between text-muted small">
                    <div>
                        <i class="bi bi-info-circle me-1"></i>
                        Menampilkan <?php echo $counter; ?> permintaan
                        <?php if($data_diterima_counter > 0): ?>
                            <span class="badge bg-light text-success border ms-1 rounded-pill"><?php echo $data_diterima_counter; ?> diterima</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <i class="bi bi-shield-check me-1"></i> End-to-end encrypted
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal konfirmasi hapus -->
    <!-- Modal konfirmasi hapus -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg p-0" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header bg-danger text-white p-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-trash me-2"></i> Konfirmasi Hapus
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex p-3 mb-3">
                        <i class="bi bi-exclamation-triangle-fill fs-1"></i>
                    </div>
                    
                    <h5 class="fw-bold mb-3">Hapus Permintaan ini?</h5>
                    
                    <div class="card bg-light border-0 p-3 mb-4 text-start">
                        <div class="d-flex mb-2">
                            <span class="text-muted me-2" style="width: 80px;">Pasien:</span>
                            <span class="fw-bold text-dark" id="deletePatientName"></span>
                        </div>
                        <div class="d-flex">
                            <span class="text-muted me-2" style="width: 80px;">RS Tujuan:</span>
                            <span class="fw-bold text-dark" id="deleteRSTujuan"></span>
                        </div>
                    </div>
                    
                    <p class="text-muted small mb-0">
                        Permintaan ini akan dihapus permanen dari arsip Anda.
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
                <div class="modal-footer bg-light p-3 border-top justify-content-center">
                    <button type="button" class="btn-modern btn-secondary-modern me-2 px-4" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="deleteConfirmLink" class="btn-modern btn-danger-modern px-4">
                        Ya, Hapus Sekarang
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
    
    // Fungsi pencarian pasien
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchPatient');
        
        if (searchInput) {
            // Add focus effect
            searchInput.addEventListener('focus', function() {
                this.style.borderColor = '#0d6efd';
                this.style.boxShadow = '0 0 0 0.25rem rgba(13, 110, 253, 0.25)';
            });
            
            searchInput.addEventListener('blur', function() {
                this.style.borderColor = 'var(--gray-200)';
                this.style.boxShadow = 'none';
            });
            
            // Search functionality
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const patientCards = document.querySelectorAll('.request-card');
                let visibleCount = 0;
                
                patientCards.forEach(card => {
                    const cardParent = card.closest('.col-lg-6');
                    const patientNameElement = card.querySelector('h5.fw-bold.text-dark');
                    
                    if (patientNameElement) {
                        const patientName = patientNameElement.textContent.toLowerCase().trim();
                        
                        if (patientName.includes(searchTerm)) {
                            cardParent.style.display = '';
                            visibleCount++;
                        } else {
                            cardParent.style.display = 'none';
                        }
                    }
                });
                
                // Show message if no results found
                const existingNoResult = document.getElementById('noResultsMessage');
                if (existingNoResult) {
                    existingNoResult.remove();
                }
                
                if (visibleCount === 0 && searchTerm !== '' && patientCards.length > 0) {
                    const noResultsDiv = document.createElement('div');
                    noResultsDiv.id = 'noResultsMessage';
                    noResultsDiv.className = 'col-12';
                    noResultsDiv.innerHTML = `
                        <div class="text-center py-5 content-card">
                            <div class="bg-light rounded-circle d-inline-flex p-4 mb-3">
                                <i class="bi bi-search text-secondary" style="font-size: 3rem;"></i>
                            </div>
                            <h4 class="text-dark">Tidak ada hasil ditemukan</h4>
                            <p class="text-muted mb-0">Tidak ada pasien dengan nama "${searchInput.value}"</p>
                        </div>
                    `;
                    document.querySelector('.row').appendChild(noResultsDiv);
                }
            });
        }
    });
    </script>
</body>
</html>