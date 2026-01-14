<?php
if (!isset($_SESSION)) session_start();

$root_dir = dirname(dirname(__FILE__));
$current_script = str_replace('\\', '/', $_SERVER['PHP_SELF']);
$is_in_pages = (stripos($current_script, '/pages/') !== false);
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

        // Hitung berkas diterima (sementara tanpa is_read check)
        $archive_data = getData(
            'permintaan',
            "dari_rs='$rs_kode' AND status='diterima'
             AND (tanggal_expired IS NULL OR tanggal_expired>='$today')"
        );
        
        // Filter hanya yang belum dibuka (jika kolom is_read ada)
        $unread_count = 0;
        foreach($archive_data as $item) {
            // Jika is_read tidak ada, false, null, 0, atau 'f' (PostgreSQL boolean), hitung sebagai unread
            $is_read = $item['is_read'] ?? null;
            if($is_read === null || $is_read === false || $is_read === 'f' || $is_read === 0 || $is_read === '0' || $is_read === '') {
                $unread_count++;
            }
        }
        
        $archive_count = $unread_count;
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}
?>

<nav class="sidebar" id="sidebar">
    
    <!-- User Info -->
    <div class="user-info">
        <p class="small text-muted mb-0">Logged in as:</p>
        <p class="fw-bold mb-0"><?= htmlspecialchars($_SESSION['rs_nama'] ?? 'Nama RS') ?></p>
        <p class="small text-muted"><?= htmlspecialchars($_SESSION['rs_kode'] ?? 'RS001') ?></p>
    </div>

    <!-- Menu Items -->
    <ul class="list-unstyled sidebar-menu">
        <li>
            <a href="<?= $base_path ?>dashboard.php" id="dashboard-link" class="menu-item">
                <i class="bi bi-grid-fill"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?= $base_path ?>pages/pasien.php" id="pasien-link" class="menu-item">
                <i class="bi bi-people-fill"></i>
                <span>Data Pasien</span>
            </a>
        </li>
        <li>
            <a href="<?= $base_path ?>pages/ajukan.php" id="ajukan-link" class="menu-item">
                <i class="bi bi-send-fill"></i>
                <span>Ajukan Permintaan</span>
            </a>
        </li>
        <li>
            <a href="<?= $base_path ?>pages/terima.php" id="terima-link" class="menu-item">
                <i class="bi bi-inbox-fill"></i>
                <span>Permintaan Masuk</span>
                <?php if ($pending_count > 0): ?>
                    <span class="badge bg-danger ms-auto"><?= $pending_count ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="<?= $base_path ?>pages/berkas.php" id="berkas-link" class="menu-item">
                <i class="bi bi-folder-check"></i>
                <span>Berkas Diterima</span>
                <?php if ($archive_count > 0): ?>
                    <span class="badge bg-info ms-auto"><?= $archive_count ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="<?= $base_path ?>pages/histori.php" id="histori-link" class="menu-item">
                <i class="bi bi-clock-history"></i>
                <span>Histori</span>
            </a>
        </li>
        <li>
            <a href="<?= $base_path ?>logout.php" class="menu-item text-danger"
               onclick="return confirm('Yakin ingin logout?')">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<style>
:root {
    --topbar-height: 60px;
    --sidebar-width: 220px;
    --primary-blue: #4F7CFF;
}

.sidebar {
    position: fixed;
    top: 60px;
    left: 0;
    width: var(--sidebar-width);
    height: calc(100vh - 60px);
    background: #ffffff;
    border-right: 1px solid #e5e7eb;
    transform: translateX(-100%);
    transition: transform .3s ease;
    z-index: 1000;
    overflow-y: auto;
}

.sidebar.active { 
    transform: translateX(0); 
}

/* Logo */
.sidebar-logo {
    padding: 24px 20px;
    text-align: center;
    border-bottom: 1px solid #e5e7eb;
}

.logo-text {
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 0.5px;
    margin: 0;
    color: #1f2937;
}

.logo-text .text-primary {
    color: var(--primary-blue) !important;
}

/* User Info */
.user-info {
    padding: 16px 20px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.user-info p {
    line-height: 1.4;
}

/* Menu */
.sidebar-menu {
    padding: 12px 12px; /* Added horizontal padding for floating effect */
    margin: 0;
}

.sidebar-menu li {
    margin: 4px 0; /* Vertical spacing between items */
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    color: #6b7280 !important;
    text-decoration: none !important;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    border-radius: 8px; /* Rounded corners for all items */
    /* border-left removed */
}

.menu-item i {
    width: 20px;
    margin-right: 12px;
    font-size: 18px; /* Slightly larger icons */
}

.menu-item span {
    flex: 1;
}

.menu-item:hover {
    background: #f3f4f6;
    color: #1f2937 !important;
}

.menu-item.active {
    background: var(--primary-blue);
    color: white !important;
    box-shadow: 0 4px 6px rgba(79, 124, 255, 0.2); /* Soft shadow */
}

.menu-item.text-danger {
    color: #dc2626 !important;
}

.menu-item.text-danger:hover {
    background: #fef2f2;
}

/* Badge */
.menu-item .badge {
    font-size: 11px !important;
    width: 24px !important;
    height: 24px !important;
    min-width: 24px !important;
    max-width: 24px !important;
    padding: 0 !important;
    border-radius: 50% !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-weight: 600 !important;
    aspect-ratio: 1 / 1;
    line-height: 1 !important;
}

/* Overlay */
.sidebar-overlay {
    position: fixed;
    top: 60px;
    inset: 0;
    background: rgba(0,0,0,0.3);
    display: none;
    z-index: 999;
}

.sidebar-overlay.active { 
    display: block; 
}

/* Main Content */
.main-content {
    transition: margin-left 0.3s ease;
    margin-left: 0;
    margin-top: var(--topbar-height);
}

@media (min-width: 992px) {
    body.sidebar-open .main-content {
        margin-left: var(--sidebar-width);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('sidebarToggle');
    
    // Get current page
    const currentPath = window.location.pathname;
    const menuLinks = document.querySelectorAll('.menu-item');
    
    // Set active menu
    menuLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (currentPath.includes(href.replace('../', '').replace('./', ''))) {
            link.classList.add('active');
        }
    });

    function toggleSidebar() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.classList.toggle('sidebar-open');
    }

    toggle?.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', toggleSidebar);
});
</script>
