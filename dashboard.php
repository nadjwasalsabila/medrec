<?php
session_start();
if(!isset($_SESSION['rs_kode'])){
    header('Location: login.php');
    exit;
}

$rs_kode = $_SESSION['rs_kode'];
$rs_nama = $_SESSION['rs_nama'];

require_once 'config/database.php';

// **HAPUS SEMUA DEBUG LOGGING**
// Ambil statistik tanpa logging

// Hitung permintaan masuk (ke RS kita, status pending)
$permintaan_masuk = getData('permintaan', "ke_rs = '$rs_kode' AND status = 'pending'");
$jumlah_masuk = count($permintaan_masuk);

// Hitung permintaan kita (dari RS kita, semua status)
$permintaan_kita_all = getData('permintaan', "dari_rs = '$rs_kode'");
$permintaan_kita = [];
foreach($permintaan_kita_all as $p) {
    if(isset($p['id']) && isset($p['pasien_nama']) && !empty(trim($p['pasien_nama']))) {
        $permintaan_kita[] = $p;
    }
}
$jumlah_kita = count($permintaan_kita);

// Hitung permintaan diterima (status diterima)
$diterima_count = 0;
foreach($permintaan_kita as $p) {
    if(isset($p['status']) && $p['status'] == 'diterima') {
        $diterima_count++;
    }
}

// Hitung permintaan ditolak (status ditolak)
$ditolak_count = 0;
foreach($permintaan_kita as $p) {
    if(isset($p['status']) && $p['status'] == 'ditolak') {
        $ditolak_count++;
    }
}

// Ambil 5 permintaan terbaru
$recent_permintaan = array_slice($permintaan_kita, 0, 5);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - <?php echo htmlspecialchars($rs_nama); ?></title>
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
        
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card i {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        .card-incoming {
            background: linear-gradient(45deg, #ff6b6b, #ee5a52);
        }
        .card-outgoing {
            background: linear-gradient(45deg, #48dbfb, #0abde3);
        }
        .card-accepted {
            background: linear-gradient(45deg, #1dd1a1, #10ac84);
        }
        .card-rejected {
            background: linear-gradient(45deg, #ff9ff3, #f368e0);
        }
        .recent-item {
            border-left: 4px solid #0d6efd;
            padding: 15px;
            margin-bottom: 10px;
            background: white;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .recent-item:hover {
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }
        .status-badge {
            font-size: 0.75em;
            padding: 3px 8px;
            border-radius: 15px;
        }
        .welcome-card {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .quick-action {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border: 1px solid #dee2e6;
            transition: all 0.3s;
        }
        .quick-action:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #0d6efd;
        }
        .quick-action i {
            font-size: 2em;
            margin-bottom: 15px;
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <!-- Include sidebar -->
    <?php include 'components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Welcome Card -->
            <div class="welcome-card mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2>Selamat Datang, <?php echo htmlspecialchars($rs_nama); ?>!</h2>
                        <p class="mb-0">Sistem Rekam Medis Elektronik Terenkripsi</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <i class="bi bi-hospital" style="font-size: 4em; opacity: 0.8;"></i>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card card-incoming">
                        <i class="bi bi-inbox"></i>
                        <div class="stat-value"><?php echo $jumlah_masuk; ?></div>
                        <div class="stat-label">Permintaan Masuk</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card card-outgoing">
                        <i class="bi bi-send"></i>
                        <div class="stat-value"><?php echo $jumlah_kita; ?></div>
                        <div class="stat-label">Permintaan Diajukan</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card card-accepted">
                        <i class="bi bi-check-circle"></i>
                        <div class="stat-value"><?php echo $diterima_count; ?></div>
                        <div class="stat-label">Diterima</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card card-rejected">
                        <i class="bi bi-x-circle"></i>
                        <div class="stat-value"><?php echo $ditolak_count; ?></div>
                        <div class="stat-label">Ditolak</div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <h4><i class="bi bi-lightning-charge text-warning"></i> Akses Cepat</h4>
                </div>
                <div class="col-md-3">
                    <a href="pages/ajukan.php" class="text-decoration-none">
                        <div class="quick-action">
                            <i class="bi bi-send-plus"></i>
                            <h6>Ajukan Permintaan</h6>
                            <p class="small text-muted">Minta data ke RS lain</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="pages/terima.php" class="text-decoration-none">
                        <div class="quick-action">
                            <i class="bi bi-inbox"></i>
                            <h6>Permintaan Masuk</h6>
                            <p class="small text-muted">
                                <?php echo $jumlah_masuk; ?> menunggu
                            </p>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="pages/berkas.php" class="text-decoration-none">
                        <div class="quick-action">
                            <i class="bi bi-archive"></i>
                            <h6>Arsip Permintaan</h6>
                            <p class="small text-muted">
                                <?php echo $jumlah_kita; ?> permintaan
                            </p>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <div class="quick-action">
                        <i class="bi bi-shield-check"></i>
                        <h6>Keamanan Data</h6>
                        <p class="small text-muted">Terenkripsi end-to-end</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Requests -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Permintaan Terbaru</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($recent_permintaan)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-inbox text-muted" style="font-size: 3em;"></i>
                                    <p class="text-muted mt-3">Belum ada permintaan</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($recent_permintaan as $item): ?>
                                <div class="recent-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <i class="bi bi-person-circle text-primary"></i>
                                                <?php echo htmlspecialchars($item['pasien_nama'] ?? 'Pasien'); ?>
                                            </h6>
                                            <p class="mb-1 small text-muted">
                                                Ke: <?php echo $item['ke_rs'] ?? 'RS'; ?>
                                            </p>
                                        </div>
                                        <div>
                                            <?php 
                                            $status = $item['status'] ?? 'pending';
                                            $status_class = 'bg-warning text-dark';
                                            if($status == 'diterima') $status_class = 'bg-success';
                                            if($status == 'ditolak') $status_class = 'bg-danger';
                                            ?>
                                            <span class="badge <?php echo $status_class; ?> status-badge">
                                                <?php echo strtoupper($status); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <p class="mb-0 small text-muted">
                                        <i class="bi bi-calendar"></i>
                                        <?php echo date('d M Y', strtotime($item['tanggal_permintaan'] ?? 'now')); ?>
                                    </p>
                                </div>
                                <?php endforeach; ?>
                                <div class="text-center mt-3">
                                    <a href="pages/berkas.php" class="btn btn-outline-primary btn-sm">
                                        Lihat Semua <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                 <!-- Kolom Kanan: Aktivitas -->
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-activity"></i> Aktivitas Terakhir
                    </div>
                    <div class="card-body p-0">
                        <?php if(empty($aktifitas_terakhir)): ?>
                            <div class="p-3 text-center">
                                <i class="bi bi-inbox text-muted"></i>
                                <p class="text-muted mt-2 small">Belum ada aktivitas</p>
                            </div>
                        <?php else: ?>
                            <div style="max-height: 250px; overflow-y: auto;">
                                <?php foreach($aktifitas_terakhir as $aktifitas): 
                                    $waktu = strtotime($aktifitas['tanggal']);
                                    $display_time = date('H:i', $waktu);
                                ?>
                                <div class="activity-item">
                                    <div class="d-flex">
                                        <div class="me-2">
                                            <i class="bi bi-check-circle text-success"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <small class="d-block"><?php echo htmlspecialchars($aktifitas['aksi']); ?></small>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars(substr($aktifitas['keterangan'], 0, 30)); ?>...</small>
                                            <small class="text-muted"><i class="bi bi-clock"></i> <?php echo $display_time; ?></small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="mt-4 pt-3 border-top">
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="bi bi-cpu"></i> Sistem Rekam Medis Elektronik
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            <i class="bi bi-calendar"></i> <?php echo date('d F Y'); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Simple animations
    document.addEventListener('DOMContentLoaded', function() {
        // Add subtle animation to stat cards
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach((card, index) => {
            card.style.animationDelay = (index * 0.1) + 's';
            card.classList.add('animate__animated', 'animate__fadeInUp');
        });
    });
    </script>
</body>
</html>