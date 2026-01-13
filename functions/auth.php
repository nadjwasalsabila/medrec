<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Ambil input
$kode_rs = $_POST['kode_rs'] ?? '';
$password = $_POST['password'] ?? '';

// Validasi input
if (empty($kode_rs) || empty($password)) {
    header('Location: ../index.php?error=1');
    exit;
}

// Ambil data RS dari database (CACHE $rumah_sakit)
if (!isset($rumah_sakit[$kode_rs])) {
    header('Location: ../index.php?error=1');
    exit;
}

$rs = $rumah_sakit[$kode_rs];

// ✅ VERIFIKASI PASSWORD (INI KUNCI UTAMA)
if (!password_verify($password, $rs['password'])) {
    header('Location: ../index.php?error=1');
    exit;
}

// ✅ LOGIN BERHASIL
$_SESSION['rs_kode'] = $kode_rs;
$_SESSION['rs_nama'] = $rs['nama'];
$_SESSION['rs_key']  = $rs['key'];
$_SESSION['logged_in'] = true;
$_SESSION['login_time'] = time();

// (opsional) simpan histori login
try {
    createData('histori', [
        'id' => uniqid() . '_' . time(),
        'rs_kode' => $kode_rs,
        'aksi' => 'login',
        'detail' => 'Login berhasil',
        'waktu' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    // tidak perlu hentikan login
}

// 🚀 REDIRECT
header('Location: ../dashboard.php');
exit;
