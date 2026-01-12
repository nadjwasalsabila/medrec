<?php
// components/topbar.php
// Pastikan session sudah start di file induk
$topbar_rs_nama = $_SESSION['rs_nama'] ?? 'Rumah Sakit';
$topbar_rs_kode = $_SESSION['rs_kode'] ?? 'RS001';
?>
<div class="topbar">
    <div class="d-flex align-items-center">
        <button id="sidebarToggle" class="btn-menu">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h5 class="mb-0">MEDICAL <span class="text-primary">RECORD</span></h5>
        </div>
    </div>
    
    <div class="d-flex align-items-center gap-3">
        <div class="topbar-user">
            <i class="bi bi-hospital"></i>
            <span><?= htmlspecialchars($topbar_rs_kode) ?></span>
        </div>
        <div id="realtimeClock" class="topbar-clock">
            <i class="bi bi-clock"></i>
            <span>Memuat waktu...</span>
        </div>
    </div>
</div>

<style>
.topbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 60px;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    z-index: 1100;
}

.btn-menu {
    background: transparent;
    border: none;
    font-size: 24px;
    color: #374151;
    padding: 8px;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.2s;
}

.btn-menu:hover {
    background: #f3f4f6;
}

.topbar-title {
    margin-left: 16px;
}

.topbar-title h5 {
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 0.5px;
    color: #1f2937;
}

.topbar-title .text-primary {
    color: #4F7CFF !important;
}

.topbar-user {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #f9fafb;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

.topbar-user i {
    color: #4F7CFF;
}

.topbar-clock {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #f9fafb;
    border-radius: 8px;
    font-size: 13px;
    color: #6b7280;
}

.topbar-clock i {
    color: #9ca3af;
}

@media (max-width: 768px) {
    .topbar-title {
        display: none;
    }
    
    .topbar-user span {
        display: none;
    }
    
    .topbar-clock span {
        font-size: 11px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Realtime Clock Logic
    function updateClock() {
        const now = new Date();
        const options = { 
            weekday: 'short', 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit'
        };
        // Gunakan locale Indonesia
        const timeString = now.toLocaleDateString('id-ID', options);
        
        const clockElement = document.getElementById('realtimeClock');
        if(clockElement) {
            clockElement.innerHTML = '<i class="bi bi-clock"></i><span>' + timeString + '</span>';
        }
    }
    
    // Update setiap menit (tidak perlu setiap detik)
    setInterval(updateClock, 60000);
    // Jalankan langsung saat load
    updateClock();
});
</script>
