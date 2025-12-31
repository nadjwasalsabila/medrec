<?php
if (!isset($_SESSION))
    session_start();

$root_dir = dirname(dirname(__FILE__));
$is_in_pages = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false);
$base_path = $is_in_pages ? '../' : './';

$db_loaded = false;
$db_path = $root_dir . '/config/database.php';
if (file_exists($db_path)) {
    require_once $db_path;
    $db_loaded = true;
}

$pending_count = 0;
$archive_count = 0;

if ($db_loaded && isset($_SESSION['rs_kode'])) {
    $rs_kode = $_SESSION['rs_kode'];
    $today = date('Y-m-d');
    try {
        $pending_count = count(getData(
            'permintaan',
            "ke_rs='$rs_kode' AND status='pending' 
             AND (tanggal_expired IS NULL OR tanggal_expired>='$today')"
        ));

        $archive_count = count(getData(
            'permintaan',
            "dari_rs='$rs_kode' AND status='diterima'
             AND (tanggal_expired IS NULL OR tanggal_expired>='$today')"
        ));
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}
?>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-header p-3 text-white">
        <h4 class="mb-1">🏥 <?= htmlspecialchars($_SESSION['rs_kode'] ?? 'RS') ?></h4>
        <p class="mb-0 small"><?= htmlspecialchars($_SESSION['rs_nama'] ?? 'Nama RS') ?></p>
    </div>

    <hr class="bg-white mx-3">

    <ul class="list-unstyled components">
        <li><a href="<?= $base_path ?>dashboard.php" id="dashboard-link"><i class="bi bi-house"></i> Dashboard</a></li>
        <li><a href="<?= $base_path ?>pages/ajukan.php" id="ajukan-link"><i class="bi bi-send"></i> Ajukan
                Permintaan</a></li>
        <li>
            <a href="<?= $base_path ?>pages/terima.php" id="terima-link">
                <i class="bi bi-inbox"></i> Permintaan Masuk
                <?php if ($pending_count > 0): ?>
                    <span class="urgent-badge float-end"><?= $pending_count ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="<?= $base_path ?>pages/berkas.php" id="berkas-link">
                <i class="bi bi-folder-check"></i> Berkas Diterima
                <?php if ($archive_count > 0): ?>
                    <span class="badge bg-info float-end"><?= $archive_count ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li><a href="<?= $base_path ?>pages/histori.php" id="histori-link"><i class="bi bi-clock-history"></i>
                Histori</a></li>
    </ul>

    <div class="mt-auto">
        <hr class="bg-white mx-3">
        <a href="<?= $base_path ?>logout.php" class="text-danger px-4 d-block"
            onclick="return confirm('Yakin ingin logout?')">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</nav>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<style>
    :root {
        --topbar-height: 60px;
        --sidebar-width: 250px;
        /* SAMA dengan sidebar.php */
    }

    .sidebar {
        position: fixed;
        top: 60px;
        left: 0;
        width: 250px;
        height: calc(100vh - 60px);
        background: linear-gradient(180deg, #2c3e50, #1a2530);
        transform: translateX(-100%);
        transition: transform .3s ease;
        z-index: 1000;
    }

    /* Sidebar 
.sidebar {
    position: fixed;
    left: -260px;
    top: var(--topbar-height);
    width: 260px;
    height: calc(100vh - var(--topbar-height));
    transition: left 0.3s ease;
    z-index: 1000;
}*/
    .sidebar-header {
        padding: 24px 20px;
        text-align: center;
        /* INI KUNCI */
    }

    .sidebar-header h4 {
        font-weight: 600;
        margin-bottom: 4px;
    }

    .sidebar-header p {
        font-size: 13px;
        opacity: 0.85;
    }

    /* JARAK ANTAR MENU */
    .sidebar .components li {
        margin: 6px 0;
        /* jarak antar item */
    }

    .sidebar .components a {
        padding: 14px 22px;
        /* bikin item lebih tinggi */
        border-radius: 6px;
    }

    .sidebar .components a {
        display: flex;
        align-items: center;
    }

    .sidebar .components a i {
        width: 20px;
        text-align: center;
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .sidebar-overlay {
        position: fixed;
        top: 60px;
        inset: 0;
        background: transparant;
        display: none;
        z-index: 999;
    }

    .sidebar-overlay.active {
        display: block;
    }

    /* RESET LINK SIDEBAR BIAR RAPI KAYA AWAL */
    .sidebar a {
        color: #ecf0f1 !important;
        text-decoration: none !important;
        font-weight: 500;
    }

    .sidebar a i {
        margin-right: 8px;
    }

    .sidebar a:hover {
        color: #ffffff !important;
        background: rgba(52, 73, 94, 0.8);
    }

    .sidebar a.active {
        background: rgba(52, 152, 219, 0.2);
        border-left: 4px solid #2980b9;
        color: #ffffff !important;
    }

    /* ===============================
   BADGE STYLE FIX
================================ */

    /* Badge merah (urgent / pending) */
    .urgent-badge {
        background: #e74c3c;
        color: #fff;
        min-width: 26px;
        height: 26px;
        border-radius: 50%;
        /* BUNDER */
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
        margin-left: auto;
    }

    /* Badge biru (arsip / berkas) */
    .badge.bg-info {
        background: #0dcaf0 !important;
        color: #fff;
        min-width: 32px;
        height: 26px;
        border-radius: 6px;
        /* KOTAK */
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
        margin-left: auto;
    }

    /* Sidebar aktif */
    .sidebar.sidebar-open {
        left: 0;
    }

    /* Konten utama */
    .main-content,
    .content-wrapper {
        transition: margin-left 0.3s ease;
        margin-left: 0;
        margin-top: var(--topbar-height);
    }


    /* Saat sidebar aktif, konten geser */
    .sidebar-open~.main-content,
    .sidebar-open~.content-wrapper {
        margin-left: 260px;
    }

    @media (min-width: 992px) {

        body.sidebar-open .main-content,
        body.sidebar-open .content-wrapper {
            margin-left: var(--sidebar-width);
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggle = document.getElementById('sidebarToggle');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');

            // 🔥 INI YANG KURANG
            document.body.classList.toggle('sidebar-open');
        }

        toggle?.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);
    });
</script>