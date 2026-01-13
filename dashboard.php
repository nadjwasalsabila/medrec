<?php
session_start();
if(!isset($_SESSION['rs_kode'])){
    header('Location: login.php');
    exit;
}

$rs_kode = $_SESSION['rs_kode'];
$rs_nama = $_SESSION['rs_nama'];

require_once 'config/database.php';

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

// Hitung permintaan diterima
$diterima_count = 0;
foreach($permintaan_kita as $p) {
    if(isset($p['status']) && $p['status'] == 'diterima') {
        $diterima_count++;
    }
}

// Hitung permintaan ditolak
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
<html lang="id">
<head>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($rs_nama); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
        }

        .main-content {
            margin-top: 60px;
            margin-left: 0;
            padding: 32px;
            min-height: calc(100vh - 60px);
        }
        
        /* Welcome Hero Card */
        .hero-card {
            background: #4F7CFF;
            border-radius: 24px;
            padding: 48px;
            color: white;
            margin-bottom: 32px;
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4);
            position: relative;
            overflow: hidden;
        }
        
        .hero-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .hero-card h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 12px;
            position: relative;
        }
        
        .hero-card p {
            font-size: 1.125rem;
            opacity: 0.95;
            position: relative;
        }
        
        .hero-icon {
            font-size: 5rem;
            opacity: 0.2;
            position: absolute;
            right: 48px;
            bottom: 24px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--accent-color);
            transition: width 0.4s;
        }
        
        .stat-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        
        .stat-card:hover::before {
            width: 100%;
            opacity: 0.08;
        }
        
        .stat-icon-wrapper {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            background: var(--accent-color);
            box-shadow: 0 8px 16px var(--shadow-color);
        }
        
        .stat-icon-wrapper i {
            font-size: 28px;
            color: white;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #1a202c;
            line-height: 1;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 0.9375rem;
            color: #718096;
            font-weight: 500;
        }
        
        .stat-trend {
            font-size: 0.875rem;
            color: #48bb78;
            margin-top: 8px;
        }
        
        /* Color Variants */
        .stat-incoming {
            --accent-color: #8b5cf6;
            --shadow-color: rgba(139, 92, 246, 0.3);
        }
        
        .stat-outgoing {
            --accent-color: #4F7CFF;
            --shadow-color: rgba(79, 124, 255, 0.3);
        }
        
        .stat-accepted {
            --accent-color: #10b981;
            --shadow-color: rgba(16, 185, 129, 0.3);
        }
        
        .stat-rejected {
            --accent-color: #ef4444;
            --shadow-color: rgba(239, 68, 68, 0.3);
        }
        
        /* Section Title */
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a202c;
            margin: 0;
        }
        
        .section-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        /* Quick Actions */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .quick-action-card {
            background: white;
            border-radius: 20px;
            padding: 32px 24px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            display: block;
        }
        
        .quick-action-card:hover {
            transform: translateY(-8px);
            border-color: #4F7CFF;
            box-shadow: 0 16px 32px rgba(79, 124, 255, 0.2);
        }
        
        .quick-action-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            border-radius: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s;
        }
        
        .quick-action-card:hover .quick-action-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .quick-action-icon i {
            font-size: 32px;
            color: white;
        }
        
        .quick-action-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 8px;
        }
        
        .quick-action-desc {
            font-size: 0.875rem;
            color: #718096;
            margin: 0;
        }
        
        /* Recent Activity */
        .activity-card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
            transition: all 0.3s;
            background: #f8fafc;
        }
        
        .activity-item:hover {
            background: #f1f5f9;
            transform: translateX(8px);
        }
        
        .activity-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .activity-icon.icon-send {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .activity-icon.icon-inbox {
            background: linear-gradient(135deg, #4F7CFF 0%, #3b5bdb 100%);
            color: white;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 4px;
        }
        
        .activity-meta {
            font-size: 0.875rem;
            color: #718096;
        }
        
        .activity-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8125rem;
            font-weight: 600;
        }
        
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-accepted {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #94a3b8;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 16px;
            opacity: 0.3;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
            
            .hero-card {
                padding: 32px 24px;
            }
            
            .hero-card h1 {
                font-size: 1.75rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/topbar.php'; ?>
    <?php include 'components/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Hero Welcome Card -->
        <div class="hero-card">
            <h1>Selamat Datang, <?php echo htmlspecialchars($rs_nama); ?>!</h1>
            <p>Sistem Rekam Medis Elektronik Terenkripsi End-to-End</p>
            <i class="bi bi-hospital hero-icon"></i>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card stat-incoming">
                <div class="stat-icon-wrapper">
                    <i class="bi bi-inbox-fill"></i>
                </div>
                <div class="stat-number"><?php echo $jumlah_masuk; ?></div>
                <div class="stat-label">Permintaan Masuk</div>
                <div class="stat-trend"><i class="bi bi-arrow-up"></i> Menunggu respon</div>
            </div>
            
            <div class="stat-card stat-outgoing">
                <div class="stat-icon-wrapper">
                    <i class="bi bi-send-fill"></i>
                </div>
                <div class="stat-number"><?php echo $jumlah_kita; ?></div>
                <div class="stat-label">Permintaan Diajukan</div>
                <div class="stat-trend"><i class="bi bi-graph-up"></i> Total diajukan</div>
            </div>
            
            <div class="stat-card stat-accepted">
                <div class="stat-icon-wrapper">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-number"><?php echo $diterima_count; ?></div>
                <div class="stat-label">Diterima</div>
                <div class="stat-trend"><i class="bi bi-check2"></i> Berhasil diterima</div>
            </div>
            
            <div class="stat-card stat-rejected">
                <div class="stat-icon-wrapper">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="stat-number"><?php echo $ditolak_count; ?></div>
                <div class="stat-label">Ditolak</div>
                <div class="stat-trend"><i class="bi bi-x"></i> Tidak disetujui</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="section-header">
            <div class="section-icon">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <h2 class="section-title">Akses Cepat</h2>
        </div>
        
        <div class="quick-actions-grid">
            <a href="pages/ajukan.php" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="bi bi-send-plus-fill"></i>
                </div>
                <div class="quick-action-title">Ajukan Permintaan</div>
                <p class="quick-action-desc">Minta data rekam medis dari RS lain</p>
            </a>
            
            <a href="pages/terima.php" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="bi bi-inbox-fill"></i>
                </div>
                <div class="quick-action-title">Permintaan Masuk</div>
                <p class="quick-action-desc"><?php echo $jumlah_masuk; ?> permintaan menunggu</p>
            </a>
            
            <a href="pages/berkas.php" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="bi bi-folder-check"></i>
                </div>
                <div class="quick-action-title">Berkas Diterima</div>
                <p class="quick-action-desc">Lihat arsip data yang diterima</p>
            </a>
            
            <a href="pages/histori.php" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="quick-action-title">Histori Aktivitas</div>
                <p class="quick-action-desc">Riwayat semua transaksi</p>
            </a>
        </div>
        
        <!-- Recent Activity -->
        <div class="section-header">
            <div class="section-icon">
                <i class="bi bi-activity"></i>
            </div>
            <h2 class="section-title">Aktivitas Terbaru</h2>
        </div>
        
        <div class="activity-card">
            <?php if(empty($recent_permintaan)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>Belum ada aktivitas permintaan</p>
                </div>
            <?php else: ?>
                <?php foreach($recent_permintaan as $req): 
                    $status = $req['status'] ?? 'pending';
                    $badge_class = $status == 'diterima' ? 'badge-accepted' : ($status == 'ditolak' ? 'badge-rejected' : 'badge-pending');
                    $icon_class = $status == 'diterima' ? 'icon-inbox' : 'icon-send';
                ?>
                <div class="activity-item">
                    <div class="activity-icon <?php echo $icon_class; ?>">
                        <i class="bi bi-<?php echo $status == 'diterima' ? 'check-circle' : 'send'; ?>"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title"><?php echo htmlspecialchars($req['pasien_nama']); ?></div>
                        <div class="activity-meta">
                            <i class="bi bi-hospital"></i> <?php echo htmlspecialchars($req['ke_rs']); ?> • 
                            <i class="bi bi-calendar"></i> <?php echo date('d M Y', strtotime($req['tanggal_permintaan'])); ?>
                        </div>
                    </div>
                    <span class="activity-badge <?php echo $badge_class; ?>">
                        <?php echo strtoupper($status); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>