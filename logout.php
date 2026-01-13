<?php
// logout.php
session_start();

// Ambil info user sebelum logout
$rs_kode = $_SESSION['rs_kode'] ?? 'Tidak diketahui';
$rs_nama = $_SESSION['rs_nama'] ?? 'Tidak diketahui';

// Hapus semua session
session_unset();
session_destroy();

// Set cookie expired
setcookie(session_name(), '', time() - 3600, '/');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <title>Logout - MedRec Transfer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/modern-theme.css">
    <style>
        body {
            /* Background handled by modern-theme */
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .logout-card {
            width: 100%;
            max-width: 480px;
            text-align: center;
            padding: 40px;
            animation: slideUp 0.5s ease-out;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .logout-icon {
            width: 80px;
            height: 80px;
            background: var(--gray-100);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-blue);
            font-size: 2.5rem;
            margin: 0 auto 24px;
            position: relative;
        }

        .check-badge {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--success);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            border: 3px solid white;
        }

        .user-info-card {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 16px;
            margin: 24px 0;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .spinner-mini {
            width: 1rem;
            height: 1rem;
            border: 2px solid var(--gray-300);
            border-top-color: var(--primary-blue);
            border-radius: 50%;
            display: inline-block;
            animation: spin 1s linear infinite;
            vertical-align: text-bottom;
        }
        
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <div class="content-card logout-card shadow-lg border-0">
        <div class="logout-icon">
            <i class="bi bi-box-arrow-right"></i>
            <div class="check-badge">
                <i class="bi bi-check"></i>
            </div>
        </div>
        
        <h2 class="fw-bold text-dark mb-2">Logout Berhasil</h2>
        <p class="text-muted mb-0">Sesi Anda telah aman diakhiri</p>
        
        <div class="user-info-card">
            <div class="bg-white p-2 rounded shadow-sm text-primary">
                <i class="bi bi-hospital fs-3"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($rs_nama); ?></h6>
                <div class="text-muted small font-monospace">
                    <?php echo htmlspecialchars($rs_kode); ?>
                </div>
            </div>
        </div>
        
        <div class="alert-modern alert-success mb-4 text-start">
            <i class="bi bi-shield-check-fill fs-5"></i>
            <div>
                <strong>Aman!</strong>
                <div class="small">Semua data sesi browser telah dibersihkan.</div>
            </div>
        </div>
        
        <div class="d-grid gap-2">
            <a href="login.php" class="btn-modern btn-primary-modern justify-content-center py-2">
                <i class="bi bi-box-arrow-in-right"></i> Login Kembali
            </a>
            <!-- Cancel button logic was removing interval, but users navigate away usually -->
        </div>
        
        <div class="mt-4 pt-3 border-top text-muted small" id="countdownContainer">
            <span class="spinner-mini me-1"></span>
            Redirect otomatis dalam <span id="countdown" class="fw-bold text-dark">10</span> detik
        </div>
    </div>

    <script>
    // Countdown Logic
    let countdown = 10;
    const countdownEl = document.getElementById('countdown');
    const containerEl = document.getElementById('countdownContainer');
    
    const interval = setInterval(() => {
        countdown--;
        if(countdownEl) countdownEl.textContent = countdown;
        
        if (countdown <= 0) {
            clearInterval(interval);
            if(containerEl) containerEl.innerHTML = '<span class="text-primary fw-bold">Mengalihkan...</span>';
            window.location.href = 'login.php';
        }
    }, 1000);
    
    // Stop countdown on interaction
    document.querySelectorAll('a').forEach(el => {
        el.addEventListener('click', () => clearInterval(interval));
    });
    </script>
</body>
</html>