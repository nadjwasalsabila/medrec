<?php
// functions/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

$kode_rs = $_POST['kode_rs'] ?? '';
$password = $_POST['password'] ?? '';

// Validasi login
if (isset($rumah_sakit[$kode_rs]) && (password_verify($password, $rumah_sakit[$kode_rs]['password']) || $rumah_sakit[$kode_rs]['password'] == $password)) {
    // Login berhasil
    $_SESSION['rs_kode'] = $kode_rs;
    $_SESSION['rs_nama'] = $rumah_sakit[$kode_rs]['nama'];
    $_SESSION['rs_key'] = $rumah_sakit[$kode_rs]['key'];
    $_SESSION['login_time'] = time();
    
    // Set cookie untuk remember me (30 hari)
    if (isset($_POST['remember'])) {
        setcookie('remember_rs', $kode_rs, time() + (30 * 24 * 60 * 60), '/');
    }
    
    // Log login activity
    $log_data = [
        'rs_id' => $kode_rs,
        'aksi' => 'login',
        'keterangan' => 'Login ke sistem',
        'waktu' => date('Y-m-d H:i:s')
    ];
    @createData('histori', $log_data);
    
    // Redirect ke dashboard
    header('Location: ../dashboard.php');
    exit;
} else {
    // Login gagal
    header('Location: ../login.php?error=1');
    exit;
}
?>