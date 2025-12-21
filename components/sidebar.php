        <?php
        // components/sidebar.php
        // File sidebar universal yang bisa diinclude di mana saja

        // Pastikan session sudah mulai (tidak mulai session di sini, harus di file utama)
        if (!isset($_SESSION)) {
            session_start();
        }

        // Tentukan root path
        $root_dir = dirname(dirname(__FILE__)); // Naik dari components/ ke root
        $is_in_pages = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false);
        $base_path = $is_in_pages ? '../' : './';

        // Include database jika diperlukan (untuk badge counts)
        $db_loaded = false;
        $db_path = $root_dir . '/config/database.php';
        if (file_exists($db_path)) {
            require_once $db_path;
            $db_loaded = true;
        }

        // Ambil badge counts jika database loaded
        $pending_count = 0;
        $archive_count = 0;

        if ($db_loaded && isset($_SESSION['rs_kode'])) {
            $rs_kode = $_SESSION['rs_kode'];
            $today = date('Y-m-d');
            
            try {
                $permintaan_menunggu = getData('permintaan', 
                    "ke_rs = '$rs_kode' AND status = 'pending' 
                    AND (tanggal_expired IS NULL OR tanggal_expired >= '$today')");
                $pending_count = count($permintaan_menunggu);
                
                $data_dikirim = getData('permintaan', 
                    "dari_rs = '$rs_kode' AND status = 'diterima' 
                    AND (tanggal_expired IS NULL OR tanggal_expired >= '$today')");
                $archive_count = count($data_dikirim);
            } catch (Exception $e) {
                // Silent fail, akan dihandle oleh JavaScript
                error_log("Sidebar badge error: " . $e->getMessage());
            }
        }
        ?>

        <!-- Toggle Button (Hamburger) -->
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>

        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header p-3 text-white">
                <h4 class="mb-1">🏥 <?php echo htmlspecialchars($_SESSION['rs_kode'] ?? 'RS'); ?></h4>
                <p class="mb-0 small"><?php echo htmlspecialchars($_SESSION['rs_nama'] ?? 'Nama RS'); ?></p>
                <!-- Close button untuk mobile -->
                <button class="sidebar-close d-md-none" id="sidebarClose">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <hr class="bg-white mx-3">
            <ul class="list-unstyled components">
                <li>
                    <a href="<?php echo $base_path; ?>dashboard.php" id="dashboard-link">
                        <i class="bi bi-house"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_path; ?>pages/ajukan.php" id="ajukan-link">
                        <i class="bi bi-send"></i> Ajukan Permintaan
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_path; ?>pages/terima.php" id="terima-link">
                        <i class="bi bi-inbox"></i> Permintaan Masuk
                        <?php if($pending_count > 0): ?>
                            <span class="urgent-badge float-end" id="pending-badge"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_path; ?>pages/berkas.php" id="berkas-link">
                        <i class="bi bi-folder-check"></i> Berkas Diterima
                        <?php if($archive_count > 0): ?>
                            <span class="badge bg-info float-end" id="archive-badge"><?php echo $archive_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_path; ?>pages/histori.php" id="histori-link">
                        <i class="bi bi-clock-history"></i> Histori
                    </a>
                </li>
            </ul>
            
            <div class="mt-auto">
                <hr class="bg-white mx-3">
                <ul class="list-unstyled">
                    <li>
                        <a href="<?php echo $base_path; ?>logout.php" class="text-danger logout-link" 
                        onclick="return confirm('Yakin ingin logout?')">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Overlay untuk mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <style>
        /* Sidebar Styles */
        .sidebar { 
            min-height: 100vh; 
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            background: linear-gradient(180deg, #2c3e50 0%, #1a2530 100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 3px 0 15px rgba(0,0,0,0.2);
            transform: translateX(-250px); /* Default hidden */
        }

        /* State untuk desktop - sidebar visible */
        .sidebar.active {
            transform: translateX(0);
        }

        /* Toggle Button Styles */
        .sidebar-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #3498db;
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .sidebar-toggle:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        /* Saat sidebar aktif, geser tombol */
        .sidebar.active + .sidebar-toggle {
            left: 270px; /* 250px sidebar + 20px margin */
        }

        .sidebar.active + .sidebar-toggle i {
            transform: rotate(180deg);
        }

        /* Tombol close untuk mobile */
        .sidebar-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            color: white;
            font-size: 1.5em;
            cursor: pointer;
            display: none;
            z-index: 1101;
        }

        .urgent-badge { 
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            min-width: 20px;
            text-align: center;
            animation: pulse 2s infinite;
        }

        /* Overlay untuk mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-280px);
                width: 280px;
            }
            
            .sidebar.active {
                transform: translateX(0);
                box-shadow: 5px 0 25px rgba(0,0,0,0.3);
            }
            
            .sidebar-close {
                display: block;
            }
            
            .sidebar-toggle {
                left: 20px !important;
            }
            
            /* Saat sidebar aktif di mobile, overlay muncul */
            .sidebar.active + .sidebar-overlay {
                display: block;
                opacity: 1;
            }
        }

        /* Style untuk main content - PENTING! */
        .main-content {
            margin-left: 0; /* Default no margin */
            padding: 25px;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
            background: #f8f9fa;
        }

        /* Saat sidebar aktif di desktop (>768px), geser main content */
        @media (min-width: 769px) {
            .sidebar.active ~ .main-content {
                margin-left: 250px;
            }
        }

        /* Sidebar links */
        .sidebar a { 
            color: #ecf0f1; 
            padding: 15px 20px; 
            display: block;
            border-left: 4px solid transparent;
            transition: all 0.3s;
            text-decoration: none;
            position: relative;
        }
        .sidebar a:hover { 
            background: rgba(52, 73, 94, 0.8); 
            border-left: 4px solid #3498db;
            padding-left: 25px;
        }
        .sidebar a.active { 
            background: rgba(52, 152, 219, 0.2); 
            border-left: 4px solid #2980b9;
            font-weight: 500;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7);
            }
            70% {
                box-shadow: 0 0 0 6px rgba(231, 76, 60, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(231, 76, 60, 0);
            }
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const mainContent = document.querySelector('.main-content');
            
            // Set active link berdasarkan halaman saat ini
            const currentPath = window.location.pathname;
            const currentPage = currentPath.split('/').pop();
            
            // Map halaman ke link ID
            const pageLinks = {
                'dashboard.php': 'dashboard-link',
                'ajukan.php': 'ajukan-link', 
                'terima.php': 'terima-link',
                'berkas.php': 'berkas-link',
                'histori.php': 'histori-link'
            };
            
            // Set active link
            Object.entries(pageLinks).forEach(([page, linkId]) => {
                if (currentPath.includes(page)) {
                    const link = document.getElementById(linkId);
                    if (link) link.classList.add('active');
                }
            });
            
            // Check local storage untuk sidebar state
            const savedSidebarState = localStorage.getItem('sidebarState');
            const isDesktop = window.innerWidth > 768;
            
            // Initialize sidebar state
            if (isDesktop) {
                // Desktop: default aktif
                if (savedSidebarState === 'collapsed') {
                    sidebar.classList.remove('active');
                } else {
                    sidebar.classList.add('active');
                }
            } else {
                // Mobile: default collapsed
                sidebar.classList.remove('active');
            }
            
            updateMainContentMargin();
            
            // Fungsi toggle sidebar
            function toggleSidebar() {
                sidebar.classList.toggle('active');
                
                // Update local storage
                if (isDesktop) {
                    localStorage.setItem('sidebarState', 
                        sidebar.classList.contains('active') ? 'expanded' : 'collapsed'
                    );
                }
                
                updateMainContentMargin();
                
                // Untuk mobile, toggle overlay
                if (!isDesktop && sidebar.classList.contains('active')) {
                    sidebarOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                } else {
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
            
            // Fungsi close sidebar (khusus mobile)
            function closeSidebar() {
                if (!isDesktop) {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                    updateMainContentMargin();
                }
            }
            
            // Fungsi update margin main content
            function updateMainContentMargin() {
                if (!mainContent) return;
                
                if (isDesktop && sidebar.classList.contains('active')) {
                    mainContent.style.marginLeft = '250px';
                } else {
                    mainContent.style.marginLeft = '0';
                }
            }
            
            // Event listeners
            sidebarToggle.addEventListener('click', toggleSidebar);
            sidebarClose.addEventListener('click', closeSidebar);
            sidebarOverlay.addEventListener('click', closeSidebar);
            
            // Auto close sidebar di mobile saat klik menu
            if (!isDesktop) {
                const menuLinks = sidebar.querySelectorAll('a:not(.logout-link)');
                menuLinks.forEach(link => {
                    link.addEventListener('click', closeSidebar);
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                const newIsDesktop = window.innerWidth > 768;
                
                if (newIsDesktop !== isDesktop) {
                    // Viewport changed
                    if (newIsDesktop) {
                        // Changed to desktop
                        const savedState = localStorage.getItem('sidebarState');
                        if (savedState !== 'collapsed') {
                            sidebar.classList.add('active');
                        }
                        sidebarOverlay.classList.remove('active');
                        document.body.style.overflow = '';
                    } else {
                        // Changed to mobile
                        sidebar.classList.remove('active');
                    }
                    updateMainContentMargin();
                }
            });
            
            // Keyboard shortcut: ESC untuk close sidebar
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !isDesktop) {
                    closeSidebar();
                }
            });
            
            // Auto update badge counts setiap 30 detik
            function updateBadgeCounts() {
                fetch('<?php echo $base_path; ?>pages/get_badge_counts.php')
                    .then(response => response.json())
                    .then(data => {
                        // Update pending badge
                        let pendingBadge = document.getElementById('pending-badge');
                        if (data.permintaan_masuk > 0) {
                            if (!pendingBadge) {
                                pendingBadge = document.createElement('span');
                                pendingBadge.id = 'pending-badge';
                                pendingBadge.className = 'urgent-badge float-end';
                                document.getElementById('terima-link').appendChild(pendingBadge);
                            }
                            pendingBadge.textContent = data.permintaan_masuk;
                        } else if (pendingBadge) {
                            pendingBadge.remove();
                        }
                        
                        // Update archive badge
                        let archiveBadge = document.getElementById('archive-badge');
                        if (data.berkas_diterima > 0) {
                            if (!archiveBadge) {
                                archiveBadge = document.createElement('span');
                                archiveBadge.id = 'archive-badge';
                                archiveBadge.className = 'badge bg-info float-end';
                                document.getElementById('berkas-link').appendChild(archiveBadge);
                            }
                            archiveBadge.textContent = data.berkas_diterima;
                        } else if (archiveBadge) {
                            archiveBadge.remove();
                        }
                    })
                    .catch(error => console.error('Error updating badges:', error));
            }
            
            // Initial update
            setTimeout(updateBadgeCounts, 1000);
            
            // Auto update setiap 30 detik
            setInterval(updateBadgeCounts, 30000);
        });
        </script>