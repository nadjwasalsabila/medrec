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
    <title>Login - MedRec Transfer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            width: 100%;
            max-width: 450px;
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-icon {
            font-size: 70px;
            color: #667eea;
            margin-bottom: 15px;
            display: inline-block;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .hospital-icon {
            color: #667eea;
            margin-right: 10px;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1em;
            width: 100%;
            transition: all 0.3s;
            margin-top: 10px;
        }
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-login:active {
            transform: translateY(-1px);
        }
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
        }
        .rs-option {
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.2s;
            cursor: pointer;
        }
        .rs-option:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }
        .rs-code {
            font-weight: bold;
            color: #667eea;
            min-width: 60px;
        }
        .rs-name {
            color: #555;
            flex: 1;
        }
        .quick-login {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 25px;
        }
        .system-info {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .error-message {
            animation: shake 0.5s;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        .floating-hospitals {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        .floating-hospitals i {
            position: absolute;
            font-size: 24px;
            color: rgba(255, 255, 255, 0.1);
            animation: float 15s infinite linear;
        }
        @keyframes float {
            0% { transform: translateY(100vh) rotate(0deg); }
            100% { transform: translateY(-100px) rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Floating hospitals background -->
    <div class="floating-hospitals" id="floatingHospitals"></div>
    
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="bi bi-shield-plus"></i>
                </div>
                <h2 class="mb-2">MedRec Transfer</h2>
                <p class="text-muted">Sistem Transfer Data Rekam Medis Antar Rumah Sakit</p>
            </div>
            
            <?php if($error): ?>
            <div class="alert alert-danger error-message mb-4">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="functions/auth.php" id="loginForm">
                <div class="mb-4">
                    <label class="form-label">Rumah Sakit</label>
                    <select name="kode_rs" class="form-select" required id="rsSelect">
                        <option value="">-- Pilih Rumah Sakit --</option>
                        <option value="RS001">RS001 - RS Umum Kota</option>
                        <option value="RS002">RS002 - RS Khusus Jantung</option>
                        <option value="RS003">RS003 - RS Ibu Anak</option>
                    </select>
                    <div class="form-text">Pilih rumah sakit Anda</div>
                </div>
                
                <div class="mb-4 position-relative">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" 
                           id="passwordInput" required placeholder="Masukkan password">
                    <span class="password-toggle" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </span>
                    <div class="form-text">Default password: rs001pass, rs002pass, rs003pass</div>
                </div>
                
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">
                        Ingat saya
                    </label>
                </div>
                
                <button type="submit" class="btn btn-login" id="loginButton">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk ke Sistem
                </button>
            </form>
            
            <!-- Quick Login (Untuk Testing) -->
            <div class="quick-login">
                <h6 class="mb-3"><i class="bi bi-lightning"></i> Quick Login (Testing)</h6>
                <div class="d-grid gap-2">
                    <div class="rs-option" onclick="quickLogin('RS001', 'rs001pass')">
                        <span class="rs-code">RS001</span>
                        <span class="rs-name">RS Umum Kota</span>
                        <small class="text-muted">rs001pass</small>
                    </div>
                    <div class="rs-option" onclick="quickLogin('RS002', 'rs002pass')">
                        <span class="rs-code">RS002</span>
                        <span class="rs-name">RS Khusus Jantung</span>
                        <small class="text-muted">rs002pass</small>
                    </div>
                    <div class="rs-option" onclick="quickLogin('RS003', 'rs003pass')">
                        <span class="rs-code">RS003</span>
                        <span class="rs-name">RS Ibu Anak</span>
                        <small class="text-muted">rs003pass</small>
                    </div>
                </div>
            </div>
            
            <div class="system-info">
                <small class="text-muted">
                    <i class="bi bi-shield-check"></i>
                    Sistem Keamanan Data Medis - Enkripsi AES-256
                    <br>
                    <i class="bi bi-clock"></i>
                    Session Timeout: 30 menit
                </small>
            </div>
        </div>
    </div>

    <script>
    // Generate floating hospitals icons
    function createFloatingIcons() {
        const container = document.getElementById('floatingHospitals');
        const icons = ['bi-hospital', 'bi-heart-pulse', 'bi-capsule', 'bi-activity', 'bi-droplet'];
        
        for (let i = 0; i < 15; i++) {
            const icon = document.createElement('i');
            icon.className = `bi ${icons[Math.floor(Math.random() * icons.length)]}`;
            
            // Random position and animation
            icon.style.left = `${Math.random() * 100}%`;
            icon.style.animationDuration = `${15 + Math.random() * 20}s`;
            icon.style.animationDelay = `${Math.random() * 5}s`;
            
            container.appendChild(icon);
        }
    }
    
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('passwordInput');
    
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    });
    
    // Quick login function
    function quickLogin(kodeRs, password) {
        document.getElementById('rsSelect').value = kodeRs;
        document.getElementById('passwordInput').value = password;
        
        // Highlight selected
        document.querySelectorAll('.rs-option').forEach(opt => {
            opt.style.background = opt.querySelector('.rs-code').textContent === kodeRs ? 
                'linear-gradient(135deg, #667eea20 0%, #764ba220 100%)' : '';
        });
        
        // Auto submit after 1 second
        setTimeout(() => {
            document.getElementById('loginButton').innerHTML = 
                '<span class="spinner-border spinner-border-sm" role="status"></span> Logging in...';
            document.getElementById('loginForm').submit();
        }, 1000);
    }
    
    // Form submission with loading state
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const button = document.getElementById('loginButton');
        const originalText = button.innerHTML;
        
        button.innerHTML = 
            '<span class="spinner-border spinner-border-sm" role="status"></span> Memproses...';
        button.disabled = true;
        
        // Simulate network delay for demo
        setTimeout(() => {
            button.disabled = false;
            button.innerHTML = originalText;
        }, 2000);
    });
    
    // Remember me functionality
    document.getElementById('rememberMe').addEventListener('change', function() {
        if (this.checked) {
            localStorage.setItem('rememberLastRS', document.getElementById('rsSelect').value);
        } else {
            localStorage.removeItem('rememberLastRS');
        }
    });
    
    // Load remembered RS
    window.addEventListener('load', function() {
        createFloatingIcons();
        
        const lastRS = localStorage.getItem('rememberLastRS');
        if (lastRS) {
            document.getElementById('rsSelect').value = lastRS;
            document.getElementById('rememberMe').checked = true;
        }
        
        // Add focus effect
        const inputs = document.querySelectorAll('.form-control, .form-select');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    });
    
    // Add CSS for focused state
    const style = document.createElement('style');
    style.textContent = `
        .focused .form-label { color: #667eea; }
        .spinner-border { vertical-align: middle; }
    `;
    document.head.appendChild(style);
    
    // Auto-detect URL parameters for demo
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('demo')) {
        const demos = {
            'rs001': ['RS001', 'rs001pass'],
            'rs002': ['RS002', 'rs002pass'],
            'rs003': ['RS003', 'rs003pass']
        };
        const demo = urlParams.get('demo');
        if (demos[demo]) {
            quickLogin(demos[demo][0], demos[demo][1]);
        }
    }
    </script>
</body>
</html>