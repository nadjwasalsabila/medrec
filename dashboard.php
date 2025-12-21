<?php
session_start();
if(!isset($_SESSION['rs_kode'])){
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';
require_once 'config/encryption.php';

$rs_kode = $_SESSION['rs_kode'];
$rs_nama = $_SESSION['rs_nama'];
$today = date('Y-m-d');

// ====================== DATA STATISTIK ======================
$permintaan_menunggu = getData('permintaan', 
    "ke_rs = '$rs_kode' AND status = 'pending' 
     AND (tanggal_expired IS NULL OR tanggal_expired >= '$today')");
$count_menunggu = count($permintaan_menunggu);

$data_dikirim = getData('permintaan', 
    "dari_rs = '$rs_kode' AND status = 'diterima' 
     AND (tanggal_expired IS NULL OR tanggal_expired >= '$today')");
$count_dikirim = count($data_dikirim);

$three_days_later = date('Y-m-d', strtotime('+3 days'));
$all_data = getData('permintaan', "dari_rs = '$rs_kode' AND status = 'diterima'");
$count_expired = 0;
foreach ($all_data as $item) {
    $expired_date = $item['tanggal_expired'] ?? '';
    if ($expired_date && $expired_date >= $today && $expired_date <= $three_days_later) {
        $count_expired++;
    }
}

// Aktivitas terakhir
$aktifitas_terakhir = [];
$histori_data = getData('histori', "rs_id = '$rs_kode'", '-waktu', 10);
if (!empty($histori_data)) {
    foreach($histori_data as $h) {
        $aktifitas_terakhir[] = [
            'tanggal' => $h['waktu'] ?? date('Y-m-d H:i:s'),
            'aksi' => $h['aksi'] ?? 'Aktivitas',
            'keterangan' => $h['keterangan'] ?? 'Tidak ada keterangan',
        ];
    }
}

// Permintaan urgent
$permintaan_urgent = getData('permintaan', 
    "ke_rs = '$rs_kode' AND status = 'pending' AND urgensi = 'urgent'
     AND (tanggal_expired IS NULL OR tanggal_expired >= '$today')", 
    '-tanggal_permintaan', 3);
?>


<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - <?php echo $rs_nama; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .main-content {
            padding: 20px;
            transition: all 0.3s;
        }
        
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .urgent-item {
            border-left: 4px solid #e74c3c;
            background: #fff5f5;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
        }
        
        .activity-item {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .quick-action-btn {
            padding: 10px;
            margin-bottom: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
         <div>
             <h3 class="mb-1">Selamat datang, <?php echo $rs_nama; ?></h3>
                <p class="text-muted mb-0">
                <i class="bi bi-calendar-check"></i> 
            <?php
                // Simple version tanpa translation
                echo date('l, d F Y');
            ?> • 
            <i class="bi bi-clock"></i> <span id="liveClock"><?php echo date('H:i:s'); ?></span>
                </p>
        </div>
    <div class="text-end">
        <span class="badge bg-primary">
            <i class="bi bi-shield-check"></i> Sistem Aktif
        </span>
    </div>
</div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card bg-warning text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">Permintaan Menunggu</h5>
                            <h2 class="mb-0"><?php echo $count_menunggu; ?></h2>
                            <small>Butuh tindakan segera</small>
                        </div>
                        <i class="bi bi-clock-history" style="font-size: 2.5em; opacity: 0.8;"></i>
                    </div>
                    <?php if($count_menunggu > 0): ?>
                    <div class="mt-3">
                        <a href="pages/terima.php" class="btn btn-sm btn-light">
                            <i class="bi bi-eye"></i> Lihat Semua
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stat-card bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">Data Dikirim</h5>
                            <h2 class="mb-0"><?php echo $count_dikirim; ?></h2>
                            <small>Berhasil dikirim</small>
                        </div>
                        <i class="bi bi-check-circle" style="font-size: 2.5em; opacity: 0.8;"></i>
                    </div>
                    <?php if($count_dikirim > 0): ?>
                    <div class="mt-3">
                        <a href="pages/berkas.php" class="btn btn-sm btn-light">
                            <i class="bi bi-folder-check"></i> Lihat Berkas
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stat-card bg-danger text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">Akan Expired</h5>
                            <h2 class="mb-0"><?php echo $count_expired; ?></h2>
                            <small>Dalam 3 hari</small>
                        </div>
                        <i class="bi bi-exclamation-triangle" style="font-size: 2.5em; opacity: 0.8;"></i>
                    </div>
                    <?php if($count_expired > 0): ?>
                    <div class="mt-3">
                        <a href="pages/berkas.php" class="btn btn-sm btn-light">
                            <i class="bi bi-clock"></i> Cek Detail
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        
    <!-- Kolom Kiri: Permintaan Urgent -->
        <div class="row">
            <div class="col-md-8">
                <div class="card h-100">
                    <div class="card-header bg-danger text-white">
                        <i class="bi bi-alarm"></i> Permintaan URGENT
                        <?php if(count($permintaan_urgent) > 0): ?>
                            <span class="badge bg-light text-danger float-end">
                                <?php echo count($permintaan_urgent); ?> URGENT
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if(empty($permintaan_urgent)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-check-circle text-success" style="font-size: 3em;"></i>
                                <p class="text-muted mt-2">Tidak ada permintaan urgent</p>
                                <a href="pages/terima.php" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-inbox"></i> Lihat Semua Permintaan
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach($permintaan_urgent as $urgent): 
                                $tanggal_permintaan = $urgent['tanggal_permintaan'] ?? date('Y-m-d H:i:s');
                                $waktu_permintaan = strtotime($tanggal_permintaan);
                                $display_time = date('H:i', $waktu_permintaan);
                            ?>
                            <div class="urgent-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="bi bi-person-circle text-danger"></i>
                                            <?php echo htmlspecialchars($urgent['pasien_nama'] ?? 'N/A'); ?>
                                            <span class="badge bg-danger ms-2">URGENT</span>
                                        </h6>
                                        <p class="mb-1">
                                            <i class="bi bi-hospital"></i> 
                                            Dari: <strong><?php echo htmlspecialchars($urgent['dari_rs'] ?? 'N/A'); ?></strong>
                                        </p>
                                        <p class="mb-1">
                                            <i class="bi bi-clock text-danger"></i> 
                                            Jam: <strong><?php echo $display_time; ?></strong>
                                        </p>
                                        <p class="mb-1 text-muted">
                                            <i class="bi bi-card-text"></i> 
                                            <?php echo isset($urgent['keterangan']) ? 
                                                htmlspecialchars(substr($urgent['keterangan'], 0, 100) . '...') : 
                                                'Tidak ada keterangan'; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="pages/terima.php?action=view&id=<?php echo $urgent['id']; ?>" 
                                       class="btn btn-sm btn-danger">
                                        <i class="bi bi-check-circle"></i> Tanggapi Sekarang
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
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
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <i class="bi bi-lightning"></i> Quick Actions
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6">
                                <a href="pages/ajukan.php" class="btn btn-outline-primary w-100 quick-action-btn">
                                    <i class="bi bi-send"></i> Ajukan
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="pages/terima.php" class="btn btn-outline-success w-100 quick-action-btn">
                                    <i class="bi bi-inbox"></i> Permintaan
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="pages/berkas.php" class="btn btn-outline-warning w-100 quick-action-btn">
                                    <i class="bi bi-folder-check"></i> Berkas
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="pages/histori.php" class="btn btn-outline-secondary w-100 quick-action-btn">
                                    <i class="bi bi-clock-history"></i> Histori
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="mt-4 pt-3 border-top">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="bi bi-info-circle"></i> Informasi Sistem</h6>
                    <ul class="small text-muted">
                        <li><i class="bi bi-check-circle text-success"></i> Data expired setelah 14 hari</li>
                        <li><i class="bi bi-exclamation-triangle text-danger"></i> Urgent harus ditanggapi dalam 2 jam</li>
                        <li><i class="bi bi-shield-check text-primary"></i> Data terenkripsi end-to-end</li>
                    </ul>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        <i class="bi bi-shield-check"></i> Sistem MedRec Transfer v1.0
                        <br>
                        <i class="bi bi-clock"></i> Update: <span id="updateTime"><?php echo date('H:i:s'); ?></span>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Live clock
    function updateClock() {
        const now = new Date();
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const seconds = now.getSeconds().toString().padStart(2, '0');
        
        document.getElementById('liveClock').textContent = `${hours}:${minutes}:${seconds}`;
        document.getElementById('updateTime').textContent = `${hours}:${minutes}:${seconds}`;
    }
    
    setInterval(updateClock, 1000);
    updateClock();
    
    // Auto-refresh setiap 60 detik
    setInterval(() => {
        // Aktifkan jika diperlukan
        // location.reload();
    }, 60000);
    </script>
</body>
</html>