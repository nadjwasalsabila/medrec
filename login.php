<?php
// index.php - Login Page
session_start();

// Jika sudah login, redirect ke dashboard
if(isset($_SESSION['rs_kode'])) {
    header('Location: dashboard.php');
    exit;
}

// Tangani error dari login
$error = isset($_GET['error']) ? 'Kode RS atau Password salah!' : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <title>Login - MedRec Transfer</title>
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
        
        .login-card {
            width: 100%;
            max-width: 450px;
            padding: 40px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            animation: slideUp 0.5s ease-out;
            position: relative;
            z-index: 10;
        }

        .brand-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3a60db 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            margin: 0 auto 24px;
            box-shadow: var(--shadow-md);
            transform: rotate(-5deg);
        }

        /* Quick Login Modern Styles */
        .quick-login {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }
        
        .rs-option {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 10px;
        }
        
        .rs-option:hover {
            border-color: var(--primary-blue);
            background: var(--primary-blue-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        .rs-code {
            font-weight: 700;
            color: var(--primary-blue);
            font-size: 1rem;
            min-width: 70px;
        }
        
        .rs-name {
            flex-grow: 1;
            font-size: 0.9rem;
            color: var(--gray-700);
            font-weight: 500;
        }
        
        .rs-pass-badge {
            font-family: monospace;
            font-size: 0.75rem;
            color: var(--gray-500);
            background: var(--gray-100);
            padding: 4px 8px;
            border-radius: 6px;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="content-card login-card shadow-lg border-0">
        <div class="text-center mb-4">
             <img src="assets/img/logo.png" alt="Logo" style="height: 130px; width: auto;">
            <h3 class="fw-bold text-dark mb-1">Medical Record Transfer</h3>
            <p class="text-muted">Akses Data Rekam Medis Terpadu</p>
        </div>

        <?php if($error): ?>
        <div class="alert-modern alert-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div>
                <strong>Login Gagal</strong>
                <div class="small"><?php echo $error; ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Form -->
        <form action="functions/auth.php" method="POST" id="loginForm">
            <div class="mb-4 text-start">
                <label class="form-label small text-muted fw-bold text-uppercase ls-1">Rumah Sakit</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start" style="border-radius: 10px 0 0 10px; border-color: var(--gray-300);">
                        <i class="bi bi-building text-muted"></i>
                    </span>
                    <select name="kode_rs" class="form-select form-control-modern border-start-0" required id="rsSelect" style="border-radius: 0 10px 10px 0;">
                        <option value="">-- Pilih Rumah Sakit --</option>
                        <option value="RS001">RS001 - RS Umum Kota</option>
                        <option value="RS002">RS002 - RS Khusus Jantung</option>
                        <option value="RS003">RS003 - RS Ibu Anak</option>
                    </select>
                </div>
            </div>

            <div class="mb-4 text-start">
                <label class="form-label small text-muted fw-bold text-uppercase ls-1">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start" style="border-radius: 10px 0 0 10px; border-color: var(--gray-300);">
                        <i class="bi bi-key text-muted"></i>
                    </span>
                    <input type="password" name="password" id="passwordInput" class="form-control form-control-modern border-start-0" placeholder="Masukkan password" required style="border-radius: 0 10px 10px 0;">
                </div>
            </div>

            <button type="submit" id="loginButton" class="btn-modern btn-primary-modern w-100 justify-content-center py-3 mt-2 shadow-sm">
                Masuk Sistem <i class="bi bi-arrow-right"></i>
            </button>
        </form>
        
        <!-- Quick Login Section (Ported from Legacy) -->
        <div class="quick-login text-start">
            <h6 class="mb-3 text-muted small fw-bold text-uppercase ls-1">
                <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Quick Login (Testing)
            </h6>
            
            <div class="rs-option" onclick="quickLogin('RS001', 'rs001pass')">
                <span class="rs-code">RS001</span>
                <span class="rs-name">RS Umum Kota</span>
                <span class="rs-pass-badge">rs001pass</span>
            </div>
            
            <div class="rs-option" onclick="quickLogin('RS002', 'rs002pass')">
                <span class="rs-code">RS002</span>
                <span class="rs-name">RS Khusus Jantung</span>
                <span class="rs-pass-badge">rs002pass</span>
            </div>
            
            <div class="rs-option" onclick="quickLogin('RS003', 'rs003pass')">
                <span class="rs-code">RS003</span>
                <span class="rs-name">RS Ibu Anak</span>
                <span class="rs-pass-badge">rs003pass</span>
            </div>
        </div>

        <div class="text-center mt-4 pt-3">
            <small class="text-muted text-opacity-75">
                &copy; <?php echo date('Y'); ?> MedRec Transfer System
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function quickLogin(kodeRs, password) {
        // Set value
        document.getElementById('rsSelect').value = kodeRs;
        document.getElementById('passwordInput').value = password;
        
        // Visual feedback
        const btn = document.getElementById('loginButton');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Logging in...';
        btn.disabled = true;
        
        // Submit immediately
        setTimeout(() => {
            document.getElementById('loginForm').submit();
        }, 500); // Small delay for visual feedback
    }
    
    // Add focus effects for input group
    const inputs = document.querySelectorAll('.form-control, .form-select');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.querySelector('.input-group-text').style.borderColor = 'var(--primary-blue)';
            this.parentElement.querySelector('.input-group-text').style.color = 'var(--primary-blue)';
        });
        input.addEventListener('blur', function() {
            this.parentElement.querySelector('.input-group-text').style.borderColor = 'var(--gray-300)';
            this.parentElement.querySelector('.input-group-text').style.color = '';
        });
    });
    </script>
</body>
</html>