<?php
session_start();
if(!isset($_SESSION['rs_kode'])){
    header('Location: ../login.php');
    exit;
}

$rs_kode = $_SESSION['rs_kode'];
$rs_nama = $_SESSION['rs_nama'];
require_once '../config/database.php';

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Proses cabut akses
$success = '';
$error = '';

if(isset($_POST['revoke_access'])){
    $permintaan_id = $_POST['permintaan_id'] ?? '';
    $pasien_nama = $_POST['pasien_nama'] ?? '';
    $dari_rs = $_POST['dari_rs'] ?? '';
    
    if($permintaan_id) {
        global $pdo;
        
        try {
            // Hapus data terenkripsi dengan SQL langsung (set NULL)
            // Status tetap 'diterima' tapi data sudah tidak bisa diakses
            $sql = "UPDATE permintaan SET data_dikirim = NULL, updated = :updated WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $success_update = $stmt->execute([
                'updated' => date('Y-m-d H:i:s'),
                'id' => $permintaan_id
            ]);
            
            if($success_update) {
                // Log ke histori
                createData('histori', [
                    'permintaan_id' => $permintaan_id,
                    'rs_id' => $rs_kode,
                    'aksi' => 'cabut_akses',
                    'keterangan' => 'Mencabut akses data pasien ' . $pasien_nama . 
                                   ' dari RS ' . $dari_rs,
                    'waktu' => date('Y-m-d H:i:s')
                ]);
                
                $success = "✅ Akses data berhasil dicabut! Data tidak dapat diakses lagi oleh RS " . $dari_rs;
            } else {
                $error = "❌ Gagal mencabut akses";
            }
        } catch (PDOException $e) {
            $error = "❌ Gagal mencabut akses: " . $e->getMessage();
            error_log("Revoke access error: " . $e->getMessage());
        }
    }
}

// Ambil semua histori
$all_histori = getData('histori', "rs_id = '$rs_kode'");

// Ambil permintaan untuk tab lainnya
// Data Dikirim = Permintaan yang masuk ke kita (ke_rs) dan sudah kita kirim (status diterima)
$permintaan_kirim = getData('permintaan', "ke_rs = '$rs_kode' AND status = 'diterima'");
// Data Diterima = Permintaan yang kita buat (dari_rs) dan sudah diterima oleh RS tujuan (status diterima)
$permintaan_terima = getData('permintaan', "dari_rs = '$rs_kode' AND status = 'diterima'");

// Sort data
usort($all_histori, function($a, $b) {
    return strtotime($b['waktu']) - strtotime($a['waktu']);
});

usort($permintaan_kirim, function($a, $b) {
    return strtotime($b['tanggal_permintaan']) - strtotime($a['tanggal_permintaan']);
});

usort($permintaan_terima, function($a, $b) {
    return strtotime($b['tanggal_diterima'] ?? $b['tanggal_permintaan']) - strtotime($a['tanggal_diterima'] ?? $a['tanggal_permintaan']);
});
?>
<!DOCTYPE html>
<html>
<head>
    <title>Histori - <?php echo htmlspecialchars($rs_nama); ?></title>
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
        
        .histori-timeline {
            position: relative;
            padding-left: 20px;
        }
        
        .histori-timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--gray-200);
        }
        
        .histori-item-wrapper {
            position: relative;
            margin-bottom: 24px;
        }
        
        .histori-dot {
            position: absolute;
            left: -25px;
            top: 15px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--gray-400);
            border: 2px solid white;
            box-shadow: 0 0 0 2px var(--gray-200);
        }
        
        .histori-date-separator {
            margin: 30px 0 15px -20px;
            padding-left: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <?php include '../components/topbar.php'; ?>
    
    <?php include '../components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container mt-4">
            
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Histori Aktivitas</h2>
                    <p class="text-muted">Jejak audit dan riwayat transfer data RS <?php echo $rs_nama; ?></p>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if($success): ?>
                <div class="alert-modern alert-success mb-4">
                    <i class="bi bi-check-circle-fill" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <strong>Sukses</strong><br>
                        <?php echo $success; ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert-modern alert-danger mb-4">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <strong>Error</strong><br>
                        <?php echo $error; ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <ul class="nav nav-pills mb-4 gap-2" id="historiTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-4 py-2" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button">
                        <i class="bi bi-list-ul me-2"></i>Semua Aktivitas
                        <?php if(!empty($all_histori)): ?>
                            <span class="badge bg-white text-primary ms-2 rounded-pill"><?php echo count($all_histori); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 py-2" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button">
                        <i class="bi bi-cloud-arrow-up me-2"></i>Data Dikirim
                        <?php if(!empty($permintaan_kirim)): ?>
                            <span class="badge bg-white text-primary ms-2 rounded-pill"><?php echo count($permintaan_kirim); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 py-2" id="received-tab" data-bs-toggle="tab" data-bs-target="#received" type="button">
                        <i class="bi bi-cloud-arrow-down me-2"></i>Data Diterima
                        <?php if(!empty($permintaan_terima)): ?>
                            <span class="badge bg-white text-primary ms-2 rounded-pill"><?php echo count($permintaan_terima); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content p-3 border border-top-0 rounded-bottom" id="historiTabContent">
                <!-- Tab 1: Semua -->
                <div class="tab-pane fade show active" id="all" role="tabpanel">
                    <?php if(empty($all_histori)): ?>
                        <div class="alert-modern alert-info bg-white text-center p-5">
                            <div class="bg-info bg-opacity-10 text-info rounded-circle d-inline-flex p-3 mb-3">
                                <i class="bi bi-info-circle fs-1"></i>
                            </div>
                            <h5 class="text-dark fw-bold">Belum ada aktivitas</h5>
                            <p class="text-muted">Aktivitas login dan transfer data akan dicatat di sini.</p>
                        </div>
                    <?php else: ?>
                        <div class="content-card p-4">
                            <div class="histori-timeline">
                                <?php 
                                $current_date = '';
                                foreach($all_histori as $hist): 
                                    $hist_date = date('d F Y', strtotime($hist['waktu']));
                                    
                                    // Separator tanggal
                                    if($hist_date != $current_date) {
                                        echo '<div class="histori-date-separator">' . $hist_date . '</div>';
                                        $current_current_date = $hist_date;
                                    }
                                    $current_date = $hist_date;

                                    // Tentukan ikon
                                    $icon_class = 'bg-primary';
                                    $icon = 'bi-person';
                                    
                                    if(strpos($hist['aksi'], 'kirim') !== false) {
                                        $icon_class = 'bg-success';
                                        $icon = 'bi-send';
                                    } elseif(strpos($hist['aksi'], 'ajukan') !== false) {
                                        $icon_class = 'bg-info';
                                        $icon = 'bi-send-plus';
                                    } elseif(strpos($hist['aksi'], 'hapus') !== false || strpos($hist['aksi'], 'cabut') !== false) {
                                        $icon_class = 'bg-danger';
                                        $icon = 'bi-trash';
                                    } elseif(strpos($hist['aksi'], 'login') !== false) {
                                        $icon_class = 'bg-primary';
                                        $icon = 'bi-person';
                                    } else {
                                        $icon_class = 'bg-secondary';
                                        $icon = 'bi-inbox';
                                    }
                                ?>
                                <div class="histori-item-wrapper ps-3">
                                    <div class="histori-dot <?php echo $icon_class; ?>"></div>
                                    <div class="card border-0 shadow-sm mb-3 hover-shadow transition-all bg-light">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="badge <?php echo $icon_class; ?> rounded-pill me-2">
                                                    <i class="bi <?php echo $icon; ?> me-1"></i>
                                                    <?php echo strtoupper(str_replace('_', ' ', $hist['aksi'])); ?>
                                                </div>
                                                <small class="text-muted ms-auto">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?php echo date('H:i', strtotime($hist['waktu'])); ?>
                                                </small>
                                            </div>
                                            <p class="mb-1 text-dark"><?php echo $hist['keterangan']; ?></p>

                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab 2: Data Dikirim -->
                <div class="tab-pane fade" id="sent" role="tabpanel">
                    <?php if(empty($permintaan_kirim)): ?>
                        <div class="alert-modern alert-info bg-white text-center p-5">
                            <div class="bg-info bg-opacity-10 text-info rounded-circle d-inline-flex p-3 mb-3">
                                <i class="bi bi-send fs-1"></i>
                            </div>
                            <h5 class="text-dark fw-bold">Belum ada data dikirim</h5>
                            <p class="text-muted">Permintaan yang Anda kirim ke RS lain akan muncul di sini.</p>
                        </div>
                    <?php else: ?>
                        <div class="content-card p-0 overflow-hidden">
                            <div class="table-responsive">
                                <table class="table-modern w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Dari RS</th>
                                            <th>Pasien</th>
                                            <th>Status</th>
                                            <th>Expired</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($permintaan_kirim as $pk): 
                                            $today = date('Y-m-d');
                                            $expired = $pk['tanggal_expired'] ?? '';
                                            $is_expired = $expired && $expired < $today;
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-medium text-dark"><?php echo date('d M Y', strtotime($pk['tanggal_permintaan'])); ?></div>
                                                <div class="small text-muted"><?php echo date('H:i', strtotime($pk['tanggal_permintaan'])); ?></div>
                                            </td>
                                            <td><span class="badge bg-light text-primary border"><i class="bi bi-hospital me-1"></i> <?php echo $pk['dari_rs']; ?></span></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo $pk['pasien_nama']; ?></div>
                                                <small class="text-muted"><i class="bi bi-card-text me-1"></i> <?php echo $pk['pasien_nik']; ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $status_badge = '';
                                                if($pk['status'] == 'pending') {
                                                    $status_badge = '<span class="badge-modern badge-warning"><i class="bi bi-clock me-1"></i> Pending</span>';
                                                } elseif($pk['status'] == 'diterima') {
                                                    $status_badge = '<span class="badge-modern badge-success"><i class="bi bi-check-circle me-1"></i> Terkirim</span>';
                                                } elseif($pk['status'] == 'ditolak') {
                                                    $status_badge = '<span class="badge-modern badge-danger"><i class="bi bi-x-circle me-1"></i> Ditolak</span>';
                                                } elseif($pk['status'] == 'expired' || $is_expired) {
                                                    $status_badge = '<span class="badge-modern badge-secondary"><i class="bi bi-hourglass-bottom me-1"></i> Expired</span>';
                                                }
                                                echo $status_badge;
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if($expired) {
                                                    echo '<div class="small text-muted mb-1">' . date('d M Y', strtotime($expired)) . '</div>';
                                                    if($is_expired): ?>
                                                        <span class="badge bg-secondary p-1" style="font-size: 0.65rem;">EXPIRED</span>
                                                    <?php else: 
                                                        $days_left = round((strtotime($expired) - strtotime($today)) / (60 * 60 * 24));
                                                        if($days_left <= 3): ?>
                                                            <span class="text-danger small fw-bold"><i class="bi bi-exclamation-triangle me-1"></i><?php echo $days_left; ?> hari</span>
                                                        <?php endif;
                                                    endif;
                                                } else {
                                                    echo '<span class="text-muted">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $is_data_revoked = empty($pk['data_dikirim']);
                                                if($is_data_revoked): ?>
                                                    <button type="button" class="btn-modern btn-secondary-modern btn-sm py-1 px-3 opacity-75" disabled>
                                                        <i class="bi bi-lock-fill me-1"></i> Dicabut
                                                    </button>
                                                <?php elseif(!$is_expired): ?>
                                                    <button type="button" class="btn-modern btn-danger-modern btn-sm py-1 px-3" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#revokeModal"
                                                            data-id="<?php echo $pk['id']; ?>"
                                                            data-nama="<?php echo htmlspecialchars($pk['pasien_nama']); ?>"
                                                            data-dari-rs="<?php echo htmlspecialchars($pk['dari_rs']); ?>">
                                                        <i class="bi bi-lock-fill me-1"></i> Cabut
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted small">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab 3: Data Diterima -->
                <div class="tab-pane fade" id="received" role="tabpanel">
                    <?php if(empty($permintaan_terima)): ?>
                        <div class="alert-modern alert-info bg-white text-center p-5">
                            <div class="bg-info bg-opacity-10 text-info rounded-circle d-inline-flex p-3 mb-3">
                                <i class="bi bi-inbox fs-1"></i>
                            </div>
                            <h5 class="text-dark fw-bold">Belum ada data diterima</h5>
                            <p class="text-muted">Data yang Anda terima dari RS lain akan muncul di sini.</p>
                        </div>
                    <?php else: ?>
                        <div class="content-card p-0 overflow-hidden">
                            <div class="table-responsive">
                                <table class="table-modern w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Ke RS</th>
                                            <th>Pasien</th>
                                            <th>Expired</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($permintaan_terima as $pt): 
                                            $today = date('Y-m-d');
                                            $expired = $pt['tanggal_expired'] ?? '';
                                            $is_expired = $expired && $expired < $today;
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-medium text-dark"><?php echo date('d M Y', strtotime($pt['tanggal_permintaan'])); ?></div>
                                                <div class="small text-muted"><?php echo date('H:i', strtotime($pt['tanggal_permintaan'])); ?></div>
                                            </td>
                                            <td><span class="badge bg-light text-success border"><i class="bi bi-hospital me-1"></i> <?php echo $pt['ke_rs']; ?></span></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo $pt['pasien_nama']; ?></div>
                                                <small class="text-muted"><i class="bi bi-card-text me-1"></i> <?php echo $pt['pasien_nik']; ?></small>
                                            </td>
                                            <td>
                                                <?php if($expired): ?>
                                                    <?php echo date('d M Y', strtotime($expired)); ?>
                                                    <?php if($is_expired): ?>
                                                        <br><span class="badge bg-secondary p-1" style="font-size: 0.65rem;">EXPIRED</span>
                                                    <?php else: 
                                                        $days_left = round((strtotime($expired) - strtotime($today)) / (60 * 60 * 24));
                                                        if($days_left <= 3): ?>
                                                            <br><span class="text-danger small fw-bold"><i class="bi bi-exclamation-triangle me-1"></i><?php echo $days_left; ?> hari</span>
                                                        <?php endif;
                                                    endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($is_expired): ?>
                                                    <span class="badge-modern badge-secondary">
                                                        <i class="bi bi-hourglass-bottom me-1"></i> Expired
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge-modern badge-success">
                                                        <i class="bi bi-check-circle me-1"></i> Aktif
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Summary -->
            <!-- Summary -->
            <div class="mt-5">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="stat-card p-4 bg-white shadow-sm border-0 h-100 position-relative overflow-hidden group-hover-effect" style="border-radius: 20px;">
                            <div class="d-flex justify-content-between align-items-start position-relative z-1">
                                <div>
                                    <p class="text-muted small fw-bold mb-1 text-uppercase ls-1">Total Aktivitas</p>
                                    <h2 class="display-5 fw-bold text-dark mb-0"><?php echo count($all_histori); ?></h2>
                                </div>
                                <div class="icon-shape bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="bi bi-list-ul fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card p-4 bg-white shadow-sm border-0 h-100 position-relative overflow-hidden group-hover-effect" style="border-radius: 20px;">
                            <div class="d-flex justify-content-between align-items-start position-relative z-1">
                                <div>
                                    <p class="text-muted small fw-bold mb-1 text-uppercase ls-1">Data Dikirim</p>
                                    <h2 class="display-5 fw-bold text-dark mb-0"><?php echo count($permintaan_kirim); ?></h2>
                                </div>
                                <div class="icon-shape bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="bi bi-cloud-arrow-up fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card p-4 bg-white shadow-sm border-0 h-100 position-relative overflow-hidden group-hover-effect" style="border-radius: 20px;">
                            <div class="d-flex justify-content-between align-items-start position-relative z-1">
                                <div>
                                    <p class="text-muted small fw-bold mb-1 text-uppercase ls-1">Data Diterima</p>
                                    <h2 class="display-5 fw-bold text-dark mb-0"><?php echo count($permintaan_terima); ?></h2>
                                </div>
                                <div class="icon-shape bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="bi bi-cloud-arrow-down fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Cabut Akses -->
    <!-- Modal Cabut Akses -->
    <div class="modal fade" id="revokeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg p-0" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header bg-danger text-white p-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-lock-fill me-2"></i> Cabut Akses Data
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST" action="">
                    <div class="modal-body p-4">
                        <div class="text-center mb-4">
                            <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex p-3 mb-2">
                                <i class="bi bi-shield-lock-fill fs-1"></i>
                            </div>
                            <h5 class="fw-bold">Konfirmasi Pencabutan Akses</h5>
                        </div>

                        <div class="alert-modern alert-warning small mb-3">
                            <strong>⚠️ Perhatian:</strong>
                            <ul class="mb-0 mt-2 ps-3">
                                <li>RS penerima <strong>tidak dapat mengakses</strong> data lagi</li>
                                <li>Data akan dianggap <strong>expired/dicabut</strong></li>
                                <li>Tindakan ini <strong>tidak dapat dibatalkan</strong></li>
                            </ul>
                        </div>

                        <div class="card bg-light border-0 p-3 mb-3">
                            <p class="mb-2 text-muted small">Anda akan mencabut akses untuk:</p>
                            <div id="revokeInfo" class="fw-bold text-dark"></div>
                            <input type="hidden" name="permintaan_id" id="revokePermintaanId">
                            <input type="hidden" name="pasien_nama" id="revokePasienNama">
                            <input type="hidden" name="dari_rs" id="revokeDariRs">
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light p-3 border-top justify-content-center">
                        <button type="button" class="btn-modern btn-secondary-modern me-2 px-4" data-bs-dismiss="modal">
                             Batal
                        </button>
                        <button type="submit" name="revoke_access" class="btn-modern btn-danger-modern px-4">
                            <i class="bi bi-lock-fill me-2"></i> Ya, Cabut Akses
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Set active tab based on URL hash
    document.addEventListener('DOMContentLoaded', function() {
        const hash = window.location.hash;
        if (hash) {
            const tabTrigger = document.querySelector(`[data-bs-target="${hash}"]`);
            if (tabTrigger) {
                new bootstrap.Tab(tabTrigger).show();
            }
        }
    });
    
    // Update URL when tab changes
    document.querySelectorAll('#historiTab button').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (event) {
            const target = event.target.getAttribute('data-bs-target');
            window.location.hash = target;
        });
    });
    
    // Initialize revoke modal
    const revokeModal = document.getElementById('revokeModal');
    if(revokeModal) {
        revokeModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const permintaanId = button.getAttribute('data-id');
            const pasienNama = button.getAttribute('data-nama');
            const dariRs = button.getAttribute('data-dari-rs');
            
            // Set data to form
            document.getElementById('revokePermintaanId').value = permintaanId;
            document.getElementById('revokePasienNama').value = pasienNama;
            document.getElementById('revokeDariRs').value = dariRs;
            document.getElementById('revokeInfo').innerHTML = '<strong>Pasien:</strong> ' + pasienNama + '<br><strong>RS Penerima:</strong> ' + dariRs;
        });
    }
    </script>
</body>
</html>