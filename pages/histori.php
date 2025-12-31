<?php
session_start();
if(!isset($_SESSION['rs_kode'])){
    header('Location: ../login.php');
    exit;
}

$rs_kode = $_SESSION['rs_kode'];
$rs_nama = $_SESSION['rs_nama'];
require_once '../config/database.php';

// Ambil semua histori
$all_histori = getData('histori', "rs_id = '$rs_kode'");

// Ambil permintaan untuk tab lainnya
$permintaan_kirim = getData('permintaan', "dari_rs = '$rs_kode'");
$permintaan_terima = getData('permintaan', "ke_rs = '$rs_kode' AND status = 'diterima'");

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
        
        .list-group-item {
            border-left: 4px solid #dee2e6;
            transition: all 0.3s;
        }
        .list-group-item:hover {
            border-left-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }
        
        .histori-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        
        .icon-login { background: #6f42c1; color: white; }
        .icon-send { background: #198754; color: white; }
        .icon-receive { background: #0dcaf0; color: white; }
        .icon-delete { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="topbar">
        <button id="sidebarToggle" class="btn btn-secondary">
            <i class="bi bi-list"></i>
        </button>
        <div class="ms-auto badge bg-light text-dark">
            <i class="bi bi-clock-history"></i> Histori
        </div>
    </div>
    
    <?php include '../components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container mt-4">
            
            <h3><i class="bi bi-clock-history"></i> Histori Aktivitas</h3>
            <p class="text-muted">RS: <strong><?php echo $rs_kode; ?> - <?php echo $rs_nama; ?></strong></p>
            
            <!-- Tabs -->
            <ul class="nav nav-tabs" id="historiTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button">
                        <i class="bi bi-list"></i> Semua Aktivitas
                        <?php if(!empty($all_histori)): ?>
                            <span class="badge bg-secondary ms-1"><?php echo count($all_histori); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button">
                        <i class="bi bi-send"></i> Data Dikirim
                        <?php if(!empty($permintaan_kirim)): ?>
                            <span class="badge bg-primary ms-1"><?php echo count($permintaan_kirim); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="received-tab" data-bs-toggle="tab" data-bs-target="#received" type="button">
                        <i class="bi bi-inbox"></i> Data Diterima
                        <?php if(!empty($permintaan_terima)): ?>
                            <span class="badge bg-success ms-1"><?php echo count($permintaan_terima); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content p-3 border border-top-0 rounded-bottom" id="historiTabContent">
                <!-- Tab 1: Semua -->
                <div class="tab-pane fade show active" id="all" role="tabpanel">
                    <?php if(empty($all_histori)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Belum ada aktivitas.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach($all_histori as $hist): 
                                // Tentukan ikon berdasarkan aksi
                                $icon_class = 'icon-login';
                                $icon = 'bi-person';
                                
                                if(strpos($hist['aksi'], 'kirim') !== false) {
                                    $icon_class = 'icon-send';
                                    $icon = 'bi-send';
                                } elseif(strpos($hist['aksi'], 'ajukan') !== false) {
                                    $icon_class = 'icon-send';
                                    $icon = 'bi-send-plus';
                                } elseif(strpos($hist['aksi'], 'hapus') !== false) {
                                    $icon_class = 'icon-delete';
                                    $icon = 'bi-trash';
                                } elseif(strpos($hist['aksi'], 'login') !== false) {
                                    $icon_class = 'icon-login';
                                    $icon = 'bi-person';
                                } else {
                                    $icon_class = 'icon-receive';
                                    $icon = 'bi-inbox';
                                }
                            ?>
                            <div class="list-group-item">
                                <div class="d-flex align-items-start">
                                    <div class="histori-icon <?php echo $icon_class; ?>">
                                        <i class="bi <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <?php echo ucwords(str_replace('_', ' ', $hist['aksi'])); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo date('d M Y H:i', strtotime($hist['waktu'])); ?>
                                            </small>
                                        </div>
                                        <p class="mb-1"><?php echo $hist['keterangan']; ?></p>
                                        <?php if($hist['permintaan_id']): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-link"></i> ID Permintaan: <?php echo substr($hist['permintaan_id'], 0, 8); ?>...
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab 2: Data Dikirim -->
                <div class="tab-pane fade" id="sent" role="tabpanel">
                    <?php if(empty($permintaan_kirim)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Belum ada data yang dikirim.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="bi bi-calendar"></i> Tanggal</th>
                                        <th><i class="bi bi-hospital"></i> RS Tujuan</th>
                                        <th><i class="bi bi-person"></i> Pasien</th>
                                        <th><i class="bi bi-flag"></i> Status</th>
                                        <th><i class="bi bi-clock"></i> Expired</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($permintaan_kirim as $pk): 
                                        $today = date('Y-m-d');
                                        $expired = $pk['tanggal_expired'] ?? '';
                                        $is_expired = $expired && $expired < $today;
                                    ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($pk['tanggal_permintaan'])); ?></td>
                                        <td><strong class="text-primary"><?php echo $pk['ke_rs']; ?></strong></td>
                                        <td>
                                            <div><?php echo $pk['pasien_nama']; ?></div>
                                            <small class="text-muted">NIK: <?php echo $pk['pasien_nik']; ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            $status_color = 'secondary';
                                            $status_icon = 'bi-dot';
                                            if($pk['status'] == 'pending') {
                                                $status_color = 'warning';
                                                $status_icon = 'bi-clock';
                                            } elseif($pk['status'] == 'diterima') {
                                                $status_color = 'success';
                                                $status_icon = 'bi-check-circle';
                                            } elseif($pk['status'] == 'ditolak') {
                                                $status_color = 'danger';
                                                $status_icon = 'bi-x-circle';
                                            } elseif($pk['status'] == 'expired' || $is_expired) {
                                                $status_color = 'dark';
                                                $status_icon = 'bi-hourglass-bottom';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_color; ?>">
                                                <i class="bi <?php echo $status_icon; ?>"></i>
                                                <?php echo ucfirst($pk['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if($expired) {
                                                echo date('d M Y', strtotime($expired));
                                                if($is_expired): ?>
                                                    <br><span class="badge bg-dark">Expired</span>
                                                <?php else: 
                                                    $days_left = round((strtotime($expired) - strtotime($today)) / (60 * 60 * 24));
                                                    if($days_left <= 3): ?>
                                                        <br><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> <?php echo $days_left; ?> hari lagi</small>
                                                    <?php endif;
                                                endif;
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab 3: Data Diterima -->
                <div class="tab-pane fade" id="received" role="tabpanel">
                    <?php if(empty($permintaan_terima)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Belum ada data yang diterima.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="bi bi-calendar"></i> Tanggal</th>
                                        <th><i class="bi bi-hospital"></i> RS Pengirim</th>
                                        <th><i class="bi bi-person"></i> Pasien</th>
                                        <th><i class="bi bi-clock"></i> Expired</th>
                                        <th><i class="bi bi-flag"></i> Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($permintaan_terima as $pt): 
                                        $today = date('Y-m-d');
                                        $expired = $pt['tanggal_expired'] ?? '';
                                        $is_expired = $expired && $expired < $today;
                                    ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($pt['tanggal_permintaan'])); ?></td>
                                        <td><strong class="text-success"><?php echo $pt['dari_rs']; ?></strong></td>
                                        <td>
                                            <div><?php echo $pt['pasien_nama']; ?></div>
                                            <small class="text-muted">NIK: <?php echo $pt['pasien_nik']; ?></small>
                                        </td>
                                        <td>
                                            <?php if($expired): ?>
                                                <?php echo date('d M Y', strtotime($expired)); ?>
                                                <?php if($is_expired): ?>
                                                    <br><span class="badge bg-dark">Expired</span>
                                                <?php else: 
                                                    $days_left = round((strtotime($expired) - strtotime($today)) / (60 * 60 * 24));
                                                    if($days_left <= 3): ?>
                                                        <br><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> <?php echo $days_left; ?> hari lagi</small>
                                                    <?php endif;
                                                endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($is_expired): ?>
                                                <span class="badge bg-dark">
                                                    <i class="bi bi-hourglass-bottom"></i> Expired
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> Aktif
                                                </span>
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
            
            <!-- Summary -->
            <div class="mt-4 pt-3 border-top">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-list text-primary"></i>
                                </h5>
                                <h3><?php echo count($all_histori); ?></h3>
                                <p class="card-text text-muted">Total Aktivitas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-send text-primary"></i>
                                </h5>
                                <h3><?php echo count($permintaan_kirim); ?></h3>
                                <p class="card-text text-muted">Data Dikirim</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-inbox text-primary"></i>
                                </h5>
                                <h3><?php echo count($permintaan_terima); ?></h3>
                                <p class="card-text text-muted">Data Diterima</p>
                            </div>
                        </div>
                    </div>
                </div>
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
    </script>
</body>
</html>