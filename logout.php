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
    <title>Logout - MedRec Transfer</title>
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
        }
        .logout-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            text-align: center;
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logout-icon {
            font-size: 80px;
            color: #667eea;
            margin-bottom: 20px;
            animation: bounce 1s infinite alternate;
        }
        @keyframes bounce {
            from { transform: translateY(0); }
            to { transform: translateY(-10px); }
        }
        .user-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            margin: 10px 5px;
        }
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            margin: 10px 5px;
        }
        .countdown {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 15px;
        }
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(102, 126, 234, 0.3);
            border-radius: 50%;
            border-top-color: #667eea;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <div class="logout-icon">
            <i class="bi bi-box-arrow-right"></i>
        </div>
        
        <h2 class="mb-3">Logout Berhasil</h2>
        <p class="text-muted mb-4">Anda telah berhasil logout dari sistem</p>
        
        <!-- Informasi User yang baru logout -->
        <div class="user-info">
            <div class="row">
                <div class="col-3 text-end">
                    <i class="bi bi-hospital" style="font-size: 1.5em; color: #667eea;"></i>
                </div>
                <div class="col-9 text-start">
                    <h5 class="mb-1"><?php echo htmlspecialchars($rs_nama); ?></h5>
                    <p class="mb-0 text-muted">
                        <i class="bi bi-code"></i> <?php echo htmlspecialchars($rs_kode); ?>
                        <br>
                        <small><i class="bi bi-clock"></i> <?php echo date('d M Y H:i:s'); ?></small>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info mt-4">
            <i class="bi bi-shield-check"></i>
            <strong>Sesi telah aman diakhiri</strong>
            <p class="mb-0 small">Semua data sesi telah dihapus dari server.</p>
        </div>
        
        <div class="mt-4">
            <a href="login.php" class="btn btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Login Kembali
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </button>
        </div>
        
        <div class="countdown mt-4">
            <div id="countdownText">
                <span class="spinner"></span> Redirect otomatis dalam: <span id="countdown">10</span> detik
            </div>
        </div>
        
        <div class="mt-4 pt-3 border-top">
            <small class="text-muted">
                <i class="bi bi-info-circle"></i>
                Sistem MedRec Transfer &copy; <?php echo date('Y'); ?>
            </small>
        </div>
    </div>

    <script>
    // Countdown untuk auto redirect
    let countdown = 10;
    const countdownElement = document.getElementById('countdown');
    const countdownText = document.getElementById('countdownText');
    
    const countdownInterval = setInterval(function() {
        countdown--;
        countdownElement.textContent = countdown;
        
        if (countdown <= 0) {
            clearInterval(countdownInterval);
            countdownText.innerHTML = '<i class="bi bi-check-circle text-success"></i> Mengarahkan ke halaman login...';
            window.location.href = 'login.php';
        } else if (countdown <= 3) {
            countdownText.innerHTML = '<span class="spinner"></span> Redirect otomatis dalam: <span class="text-danger fw-bold">' + countdown + '</span> detik';
        }
    }, 1000);
    
    // Cancel redirect jika user klik tombol
    document.querySelectorAll('a, button').forEach(element => {
        element.addEventListener('click', function() {
            clearInterval(countdownInterval);
            countdownText.innerHTML = '<i class="bi bi-x-circle text-secondary"></i> Redirect dibatalkan';
        });
    });
    
    // Tambah efek suara (opsional)
    function playLogoutSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 523.25; // C5
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 1);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 1);
        } catch (e) {
            console.log("Audio tidak didukung");
        }
    }
    
    // Mainkan suara saat halaman load
    window.addEventListener('load', function() {
        setTimeout(playLogoutSound, 300);
    });
    </script>
</body>
</html>