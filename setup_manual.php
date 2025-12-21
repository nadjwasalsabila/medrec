<?php
// setup_manual.php - Manual Setup Instructions
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Manual MedRec Transfer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .step-card { margin: 15px 0; border-left: 5px solid #0d6efd; }
        .step-number { 
            background: #0d6efd; 
            color: white; 
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center;
            margin-right: 10px;
        }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; }
        .btn-action { margin: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-center mb-4">
            <h1 class="text-primary">🚑 Setup Database MedRec Transfer</h1>
            <p class="text-muted">Manual Setup Guide - Tidak perlu coding!</p>
        </div>

        <!-- STEP 1 -->
        <div class="card step-card">
            <div class="card-body">
                <h4><span class="step-number">1</span> Pastikan PocketBase Berjalan</h4>
                <p>Buka Command Prompt/PowerShell di folder pocketbase:</p>
                <div class="alert alert-info">
                    <strong>Lokasi:</strong> <code>C:\xampp\htdocs\medrec\pocketbase\</code>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Perintah:</h6>
                        <pre>pocketbase.exe serve</pre>
                    </div>
                    <div class="col-md-6">
                        <h6>Output yang diharapkan:</h6>
                        <pre>Server started at http://127.0.0.1:8090</pre>
                    </div>
                </div>
                
                <a href="http://localhost:8090/" target="_blank" class="btn btn-primary btn-action">
                    🔗 Cek PocketBase
                </a>
                <button onclick="copyToClipboard('cd C:\\xampp\\htdocs\\medrec\\pocketbase')" class="btn btn-secondary btn-action">
                    📋 Copy Path
                </button>
            </div>
        </div>

        <!-- STEP 2 -->
        <div class="card step-card">
            <div class="card-body">
                <h4><span class="step-number">2</span> Login ke Admin Panel PocketBase</h4>
                <p>Buka admin panel di browser:</p>
                
                <div class="alert alert-warning">
                    <strong>URL:</strong> 
                    <a href="http://localhost:8090/_/" target="_blank" class="alert-link">
                        http://localhost:8090/_/
                    </a>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Login Credentials:</h6>
                        <div class="card">
                            <div class="card-body">
                                <p><strong>Email:</strong> <code>admin@example.com</code></p>
                                <p><strong>Password:</strong> <code>admin12345678</code></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Setelah login sukses:</h6>
                        <img src="https://via.placeholder.com/400x250/0d6efd/ffffff?text=PocketBase+Admin+Dashboard" 
                             class="img-thumbnail" alt="Admin Dashboard">
                    </div>
                </div>
                
                <a href="http://localhost:8090/_/" target="_blank" class="btn btn-success btn-action">
                    🔑 Login ke Admin Panel
                </a>
            </div>
        </div>

        <!-- STEP 3 -->
        <div class="card step-card">
            <div class="card-body">
                <h4><span class="step-number">3</span> Buat Collections (Tabel Database)</h4>
                <p class="text-muted">Lakukan ini 3 kali untuk membuat 3 collections berbeda</p>
                
                <div class="accordion" id="collectionsAccordion">
                    
                    <!-- Collection 1 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                📋 Collection 1: <strong>"pasien"</strong>
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#collectionsAccordion">
                            <div class="accordion-body">
                                <p>Klik: <strong>Collections</strong> → <strong>New Collection</strong></p>
                                <table class="table table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="30%">Field Name</th>
                                            <th width="30%">Type</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>nik_encrypted</td><td><span class="badge bg-info">text</span></td><td>NIK pasien (terenkripsi)</td></tr>
                                        <tr><td>nama_encrypted</td><td><span class="badge bg-info">text</span></td><td>Nama pasien (terenkripsi)</td></tr>
                                        <tr><td>riwayat_encrypted</td><td><span class="badge bg-info">text</span></td><td>Riwayat penyakit (terenkripsi)</td></tr>
                                        <tr><td>rs_asal</td><td><span class="badge bg-info">text</span></td><td>RS asal pasien (RS001, RS002, dll)</td></tr>
                                        <tr><td>created_at</td><td><span class="badge bg-warning">date</span></td><td>Tanggal dibuat</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Collection 2 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                📋 Collection 2: <strong>"permintaan"</strong>
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#collectionsAccordion">
                            <div class="accordion-body">
                                <table class="table table-bordered">
                                    <thead class="table-dark">
                                        <tr><th>Field Name</th><th>Type</th><th>Keterangan</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>dari_rs</td><td><span class="badge bg-info">text</span></td><td>RS pengirim</td></tr>
                                        <tr><td>ke_rs</td><td><span class="badge bg-info">text</span></td><td>RS tujuan</td></tr>
                                        <tr><td>pasien_nik</td><td><span class="badge bg-info">text</span></td><td>NIK pasien (plain text untuk search)</td></tr>
                                        <tr><td>pasien_nama</td><td><span class="badge bg-info">text</span></td><td>Nama pasien</td></tr>
                                        <tr><td>urgensi</td><td><span class="badge bg-success">select</span></td><td>Options: urgent, biasa, tidak_urgent</td></tr>
                                        <tr><td>keterangan</td><td><span class="badge bg-info">text</span></td><td>Alasan permintaan</td></tr>
                                        <tr><td>status</td><td><span class="badge bg-success">select</span></td><td>Options: pending, diterima, ditolak, expired</td></tr>
                                        <tr><td>token_akses</td><td><span class="badge bg-info">text</span></td><td>Token unik untuk akses</td></tr>
                                        <tr><td>tanggal_permintaan</td><td><span class="badge bg-warning">date</span></td><td>Tanggal diajukan</td></tr>
                                        <tr><td>tanggal_expired</td><td><span class="badge bg-warning">date</span></td><td>Tanggal kadaluarsa</td></tr>
                                        <tr><td>data_dikirim</td><td><span class="badge bg-info">text</span></td><td>Data yang dikirim (terenkripsi)</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Collection 3 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                📋 Collection 3: <strong>"histori"</strong>
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#collectionsAccordion">
                            <div class="accordion-body">
                                <table class="table table-bordered">
                                    <thead class="table-dark">
                                        <tr><th>Field Name</th><th>Type</th><th>Keterangan</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>permintaan_id</td><td><span class="badge bg-info">text</span></td><td>ID dari tabel permintaan</td></tr>
                                        <tr><td>rs_id</td><td><span class="badge bg-info">text</span></td><td>Kode RS yang melakukan aksi</td></tr>
                                        <tr><td>aksi</td><td><span class="badge bg-info">text</span></td><td>mengajukan, mengirim, membuka</td></tr>
                                        <tr><td>keterangan</td><td><span class="badge bg-info">text</span></td><td>Detail aksi</td></tr>
                                        <tr><td>waktu</td><td><span class="badge bg-warning">date</span></td><td>Waktu aksi</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 4 -->
        <div class="card step-card">
            <div class="card-body">
                <h4><span class="step-number">4</span> Tambah Data Contoh (Optional)</h4>
                <p>Setelah collections dibuat, tambah data dummy untuk testing:</p>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <strong>📝 Data Pasien Contoh</strong>
                            </div>
                            <div class="card-body">
                                <p>Klik collection <strong>"pasien"</strong> → <strong>"New Record"</strong></p>
                                <pre>{
  "nik_encrypted": "[kosong dulu]",
  "nama_encrypted": "[kosong dulu]",
  "riwayat_encrypted": "Diabetes tipe 2",
  "rs_asal": "RS001",
  "created_at": "2024-03-15"
}</pre>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <strong>🚀 Quick Test Data</strong>
                            </div>
                            <div class="card-body">
                                <button onclick="generateTestData()" class="btn btn-success w-100 mb-2">
                                    🧪 Generate Test Data
                                </button>
                                <small class="text-muted">Klik untuk generate data testing otomatis</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 5 -->
        <div class="card step-card bg-success text-white">
            <div class="card-body">
                <h4><span class="step-number">✓</span> Setup Selesai!</h4>
                <p>Aplikasi sudah siap digunakan. Login dengan credentials berikut:</p>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light text-dark">
                            <div class="card-body text-center">
                                <h5>🏥 RS001</h5>
                                <p><strong>Password:</strong> rs001pass</p>
                                <a href="../index.php" class="btn btn-primary">Login</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light text-dark">
                            <div class="card-body text-center">
                                <h5>🏥 RS002</h5>
                                <p><strong>Password:</strong> rs002pass</p>
                                <a href="../index.php" class="btn btn-primary">Login</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light text-dark">
                            <div class="card-body text-center">
                                <h5>🏥 RS003</h5>
                                <p><strong>Password:</strong> rs003pass</p>
                                <a href="../index.php" class="btn btn-primary">Login</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="bg-white">
                
                <div class="text-center mt-3">
                    <a href="../index.php" class="btn btn-light btn-lg">
                        🚀 Mulai Aplikasi MedRec
                    </a>
                    <a href="http://localhost:8090/_/" target="_blank" class="btn btn-outline-light btn-lg">
                        📊 Buka Admin PocketBase
                    </a>
                </div>
            </div>
        </div>

        <!-- Troubleshooting -->
        <div class="card mt-4 border-danger">
            <div class="card-header bg-danger text-white">
                🚨 Troubleshooting
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="30%">Masalah</th>
                        <th>Solusi</th>
                    </tr>
                    <tr>
                        <td>PocketBase tidak jalan</td>
                        <td>Buka Command Prompt sebagai Administrator</td>
                    </tr>
                    <tr>
                        <td>Port 8090 dipakai</td>
                        <td>Ganti port: <code>pocketbase.exe serve --http=127.0.0.1:8091</code></td>
                    </tr>
                    <tr>
                        <td>Tidak bisa login admin</td>
                        <td>Reset: Hapus folder <code>pb_data</code> dan jalankan ulang</td>
                    </tr>
                    <tr>
                        <td>Aplikasi error</td>
                        <td>Cek <code>config/database.php</code> - sesuaikan port</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Copied to clipboard: ' + text);
        });
    }
    
    function generateTestData() {
        if(confirm('Generate test data ke PocketBase?')) {
            // Ini bisa dikembangkan untuk insert data via API
            alert('Fitur ini perlu dikembangkan dengan PocketBase API');
            window.open('http://localhost:8090/_/', '_blank');
        }
    }
    </script>
</body>
</html>