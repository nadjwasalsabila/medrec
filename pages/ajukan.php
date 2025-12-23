<?php
session_start();
if(!isset($_SESSION['rs_kode'])){
    header('Location: ../login.php');
    exit;
}

$rs_kode = $_SESSION['rs_kode'];
$rs_nama = $_SESSION['rs_nama'];

require_once '../config/database.php';

// Inisialisasi variabel
$error = '';
$success = '';
$pasien_nama = '';
$pasien_nik = '';
$ke_rs = '';
$urgensi = 'biasa';
$keterangan = '';

// List RS tujuan (kecuali RS sendiri)
$rs_tujuan_list = ['RS001', 'RS002', 'RS003'];
$rs_tujuan_options = [];
foreach($rs_tujuan_list as $rs) {
    if($rs != $rs_kode) {
        $rs_tujuan_options[] = $rs;
    }
}

// Proses form submission
if(isset($_POST['submit_permintaan'])){
    $pasien_nama = trim($_POST['pasien_nama'] ?? '');
    $pasien_nik = trim($_POST['pasien_nik'] ?? '');
    $ke_rs = $_POST['ke_rs'] ?? '';
    $urgensi = $_POST['urgensi'] ?? 'biasa';
    $keterangan = trim($_POST['keterangan'] ?? '');
    
    // Validasi input
    if(empty($pasien_nama) || empty($pasien_nik) || empty($ke_rs) || empty($keterangan)) {
        $error = "❌ Harap isi semua field yang wajib diisi!";
    } elseif($ke_rs == $rs_kode) {
        $error = "❌ Tidak dapat mengajukan permintaan ke RS sendiri!";
    } elseif(!in_array($ke_rs, $rs_tujuan_options)) {
        $error = "❌ RS tujuan tidak valid!";
    } else {
        // Simpan ke database
        $result = createData('permintaan', [
            'dari_rs' => $rs_kode,
            'ke_rs' => $ke_rs,
            'pasien_nama' => $pasien_nama,
            'pasien_nik' => $pasien_nik,
            'urgensi' => $urgensi,
            'keterangan' => $keterangan,
            'status' => 'pending',
            'tanggal_permintaan' => date('Y-m-d H:i:s')
        ]);
        
        if($result['success']){
            // Simpan histori
            createData('histori', [
                'permintaan_id' => $result['id'],
                'rs_id' => $rs_kode,
                'aksi' => 'mengajukan_permintaan',
                'keterangan' => 'Mengajukan permintaan data pasien ' . $pasien_nama . ' ke ' . $ke_rs,
                'waktu' => date('Y-m-d H:i:s')
            ]);
            
            $success = "✅ Permintaan berhasil diajukan ke RS " . $ke_rs . "!";
            
            // Reset form values untuk clear form
            $pasien_nama = '';
            $pasien_nik = '';
            $ke_rs = '';
            $urgensi = 'biasa';
            $keterangan = '';
        } else {
            $error = "❌ Gagal mengajukan permintaan. Silakan coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ajukan Permintaan - <?php echo htmlspecialchars($rs_nama); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s;
            min-height: 100vh;
            background: #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                padding-left: 15px;
                padding-right: 15px;
            }
        }
        
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
        }
        
        .form-header {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .required-label::after {
            content: " *";
            color: #dc3545;
        }
        
        .urgency-option {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 10px;
        }
        
        .urgency-option input[type="radio"] {
            display: none;
        }
        
        .urgency-option:hover {
            border-color: #0d6efd;
            background: #f0f8ff;
        }
        
        .urgency-option input[type="radio"]:checked + .urgency-content {
            border-color: #0d6efd;
            background: #e7f1ff;
        }
        
        .urgency-content {
            border: 2px solid transparent;
            border-radius: 8px;
            padding: 10px;
        }
        
        .urgency-icon {
            font-size: 1.5em;
            margin-bottom: 10px;
        }
        
        .btn-submit {
            background: linear-gradient(45deg, #0d6efd, #0b5ed7);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            background: linear-gradient(45deg, #0b5ed7, #0a58ca);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            border-left: 4px solid #0dcaf0;
        }
    </style>
</head>
<body>
    <!-- Include sidebar -->
    <?php include '../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3><i class="bi bi-send-plus text-primary"></i> Ajukan Permintaan Data</h3>
                    <p class="text-muted">Minta data rekam medis pasien ke RS lain</p>
                </div>
                <div>
                    <a href="berkas.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali ke Arsip
                    </a>
                </div>
            </div>
            
            <!-- HANYA SATU ALERT - Tidak double -->
            <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Error</h5>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <h5 class="alert-heading"><i class="bi bi-check-circle"></i> Sukses</h5>
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <div class="mt-3">
                    <a href="ajukan.php" class="btn btn-sm btn-outline-success">Ajukan Lagi</a>
                    <a href="berkas.php" class="btn btn-sm btn-outline-primary ms-2">Lihat Arsip</a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Form Card -->
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="form-card">
                        <div class="form-header">
                            <h4><i class="bi bi-file-earmark-medical"></i> Form Permintaan Data</h4>
                            <p class="text-muted mb-0">Isi data pasien dan alasan permintaan</p>
                        </div>
                        
                        <form method="POST" action="" id="permintaanForm">
                            <div class="row">
                                <!-- Data Pasien -->
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <h5><i class="bi bi-person-badge text-primary"></i> Data Pasien</h5>
                                        <p class="text-muted small">Informasi identitas pasien</p>
                                        
                                        <div class="mb-3">
                                            <label class="form-label required-label">
                                                <i class="bi bi-person"></i> Nama Lengkap Pasien
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="pasien_nama" 
                                                   value="<?php echo htmlspecialchars($pasien_nama); ?>"
                                                   placeholder="Masukkan nama lengkap pasien"
                                                   required>
                                            <div class="form-text">Nama sesuai dengan identitas resmi</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label required-label">
                                                <i class="bi bi-card-text"></i> Nomor Induk Kependudukan (NIK)
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="pasien_nik" 
                                                   value="<?php echo htmlspecialchars($pasien_nik); ?>"
                                                   placeholder="Masukkan 16 digit NIK"
                                                   required
                                                   pattern="[0-9]{16}"
                                                   maxlength="16">
                                            <div class="form-text">16 digit angka NIK</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- RS Tujuan dan Urgensi -->
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <h5><i class="bi bi-hospital text-info"></i> Tujuan & Urgensi</h5>
                                        <p class="text-muted small">Pilih RS tujuan dan tingkat urgensi</p>
                                        
                                        <div class="mb-3">
                                            <label class="form-label required-label">
                                                <i class="bi bi-buildings"></i> RS Tujuan
                                            </label>
                                            <select class="form-select" name="ke_rs" required>
                                                <option value="">-- Pilih RS Tujuan --</option>
                                                <?php foreach($rs_tujuan_options as $rs): ?>
                                                <option value="<?php echo $rs; ?>" 
                                                    <?php echo ($ke_rs == $rs) ? 'selected' : ''; ?>>
                                                    <?php echo $rs; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Pilih RS yang memiliki data pasien</div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label required-label">
                                                <i class="bi bi-clock"></i> Tingkat Urgensi
                                            </label>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label class="urgency-option">
                                                        <input type="radio" name="urgensi" value="urgent" 
                                                            <?php echo ($urgensi == 'urgent') ? 'checked' : ''; ?> required>
                                                        <div class="urgency-content">
                                                            <div class="urgency-icon text-danger">
                                                                <i class="bi bi-exclamation-triangle"></i>
                                                            </div>
                                                            <div class="fw-bold">Urgent</div>
                                                            <small class="text-muted">Segera dibutuhkan</small>
                                                        </div>
                                                    </label>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="urgency-option">
                                                        <input type="radio" name="urgensi" value="biasa" 
                                                            <?php echo ($urgensi == 'biasa') ? 'checked' : ''; ?> required>
                                                        <div class="urgency-content">
                                                            <div class="urgency-icon text-warning">
                                                                <i class="bi bi-clock"></i>
                                                            </div>
                                                            <div class="fw-bold">Biasa</div>
                                                            <small class="text-muted">Bisa menunggu</small>
                                                        </div>
                                                    </label>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="urgency-option">
                                                        <input type="radio" name="urgensi" value="tidak_urgent" 
                                                            <?php echo ($urgensi == 'tidak_urgent') ? 'checked' : ''; ?> required>
                                                        <div class="urgency-content">
                                                            <div class="urgency-icon text-info">
                                                                <i class="bi bi-calendar"></i>
                                                            </div>
                                                            <div class="fw-bold">Tidak Urgent</div>
                                                            <small class="text-muted">Bisa ditunda</small>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Keterangan -->
                            <div class="mb-4">
                                <h5><i class="bi bi-chat-left-text text-success"></i> Alasan Permintaan</h5>
                                <p class="text-muted small">Jelaskan alasan permintaan data pasien</p>
                                
                                <div class="mb-3">
                                    <label class="form-label required-label">
                                        <i class="bi bi-file-text"></i> Keterangan / Alasan Permintaan
                                    </label>
                                    <textarea class="form-control" 
                                              name="keterangan" 
                                              rows="4" 
                                              placeholder="Contoh: Pasien membutuhkan tindakan lanjutan, butuh riwayat penyakit sebelumnya, dll."
                                              required><?php echo htmlspecialchars($keterangan); ?></textarea>
                                    <div class="form-text">
                                        Jelaskan secara jelas mengapa data pasien dibutuhkan
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Informasi Penting -->
                            <div class="info-box">
                                <h6><i class="bi bi-info-circle text-info"></i> Informasi Penting</h6>
                                <ul class="mb-0 small">
                                    <li>Permintaan akan dikirim ke RS tujuan untuk ditinjau</li>
                                    <li>RS tujuan dapat menerima atau menolak permintaan</li>
                                    <li>Data yang dikirim akan terenkripsi untuk keamanan</li>
                                    <li>Proses dapat memakan waktu 1-3 hari kerja</li>
                                    <li>Status permintaan dapat dipantau di menu <strong>Arsip Permintaan</strong></li>
                                </ul>
                            </div>
                            
                            <!-- Form Actions -->
                            <div class="d-flex justify-content-between mt-4 pt-4 border-top">
                                <div>
                                    <small class="text-muted">
                                        <i class="bi bi-shield-check"></i> Data akan dienkripsi end-to-end
                                    </small>
                                </div>
                                <div>
                                    <button type="reset" class="btn btn-outline-secondary me-2">
                                        <i class="bi bi-x-circle"></i> Reset Form
                                    </button>
                                    <button type="submit" name="submit_permintaan" class="btn-submit">
                                        <i class="bi bi-send-check"></i> Ajukan Permintaan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="mt-4 pt-3 border-top text-center">
                <small class="text-muted">
                    <i class="bi bi-lock"></i> Semua komunikasi dan data dienkripsi untuk keamanan pasien
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Form validation and confirmation
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('permintaanForm');
        
        form.addEventListener('submit', function(e) {
            // Validasi NIK (16 digit)
            const nikInput = document.querySelector('input[name="pasien_nik"]');
            if(nikInput.value.length !== 16 || !/^\d+$/.test(nikInput.value)) {
                e.preventDefault();
                alert('NIK harus berupa 16 digit angka!');
                nikInput.focus();
                return false;
            }
            
            // Validasi nama tidak kosong
            const namaInput = document.querySelector('input[name="pasien_nama"]');
            if(namaInput.value.trim().length < 3) {
                e.preventDefault();
                alert('Nama pasien minimal 3 karakter!');
                namaInput.focus();
                return false;
            }
            
            // Confirmation dialog
            const rsTujuan = document.querySelector('select[name="ke_rs"]').value;
            const pasienNama = namaInput.value;
            const urgensi = document.querySelector('input[name="urgensi"]:checked').value;
            
            const urgensiText = {
                'urgent': 'URGENT (Segera dibutuhkan)',
                'biasa': 'BIASA (Bisa menunggu)',
                'tidak_urgent': 'TIDAK URGENT (Bisa ditunda)'
            };
            
            const confirmMsg = `Konfirmasi Permintaan:\n\n` +
                              `Pasien: ${pasienNama}\n` +
                              `RS Tujuan: ${rsTujuan}\n` +
                              `Urgensi: ${urgensiText[urgensi]}\n\n` +
                              `Apakah data yang diisi sudah benar?`;
            
            if(!confirm(confirmMsg)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Auto format NIK
        const nikInput = document.querySelector('input[name="pasien_nik"]');
        nikInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 16);
        });
        
        // Auto capitalize nama
        const namaInput = document.querySelector('input[name="pasien_nama"]');
        namaInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        
        // Style urgency options on click
        document.querySelectorAll('.urgency-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.urgency-option').forEach(opt => {
                    opt.querySelector('.urgency-content').style.borderColor = 'transparent';
                    opt.querySelector('.urgency-content').style.background = 'transparent';
                });
                
                this.querySelector('.urgency-content').style.borderColor = '#0d6efd';
                this.querySelector('.urgency-content').style.background = '#e7f1ff';
            });
        });
    });
    </script>
</body>
</html>