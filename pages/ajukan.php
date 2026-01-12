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

// Ambil histori permintaan kita
$histori_permintaan = getData('permintaan', "dari_rs = '$rs_kode'");
usort($histori_permintaan, function($a, $b) {
    return strtotime($b['created']) - strtotime($a['created']);
});
$histori_permintaan = array_slice($histori_permintaan, 0, 10);

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
    } elseif(strlen($pasien_nik) != 16 || !is_numeric($pasien_nik)) {
        $error = "❌ NIK harus 16 digit angka!";
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
            $permintaan_id = $result['id'];
            
            // Simpan histori
            createData('histori', [
                'permintaan_id' => $permintaan_id,
                'rs_id' => $rs_kode,
                'aksi' => 'mengajukan_permintaan',
                'keterangan' => 'Mengajukan permintaan data pasien ' . $pasien_nama . ' ke ' . $ke_rs,
                'waktu' => date('Y-m-d H:i:s')
            ]);
            
            $success = "✅ Permintaan berhasil diajukan ke RS " . $ke_rs . "!";
            
            // Refresh histori
            $histori_permintaan = getData('permintaan', "dari_rs = '$rs_kode'");
            usort($histori_permintaan, function($a, $b) {
                return strtotime($b['created']) - strtotime($a['created']);
            });
            $histori_permintaan = array_slice($histori_permintaan, 0, 10);
            
            // Reset form
            $pasien_nama = $pasien_nik = $ke_rs = $keterangan = '';
            $urgensi = 'biasa';
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/modern-theme.css">
    <style>
        .main-content {
            margin-top: 60px;
            margin-left: 0;
            padding: 32px;
            min-height: calc(100vh - 60px);
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
        }
        
        .urgency-option {
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .urgency-option label {
            display: block;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .urgency-option input[type="radio"] {
            display: none;
        }
        
        .urgency-option input[type="radio"]:checked + label {
            border-color: var(--primary-blue);
            background: var(--primary-blue-light);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        
        .urgency-option:hover label {
            border-color: var(--primary-blue);
        }
        
        .urgency-icon {
            font-size: 2rem;
            margin-bottom: 12px;
            display: block;
            transition: transform 0.3s;
        }
        
        .urgency-option:hover .urgency-icon {
            transform: scale(1.1);
        }
        
        .histori-item {
            background: white;
            border-radius: var(--radius-md);
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid var(--gray-200);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .histori-item:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-blue);
        }
        
        .histori-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <?php include '../components/topbar.php'; ?>
    
    <?php include '../components/sidebar.php'; ?>
    
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
            
            <!-- Alert -->
            <!-- Alert -->
            <?php if(!empty($error)): ?>
            <div class="alert-modern alert-danger">
                <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.5rem;"></i>
                <div>
                    <strong>Error</strong><br>
                    <?php echo $error; ?>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
            <div class="alert-modern alert-success">
                <i class="bi bi-check-circle-fill" style="font-size: 1.5rem;"></i>
                <div class="flex-grow-1">
                    <strong>Sukses</strong><br>
                    <?php echo $success; ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <div class="mb-4 text-center">
                <a href="ajukan.php" class="btn-modern btn-success-modern mb-2">
                    <i class="bi bi-plus-circle"></i> Ajukan Lagi
                </a>
                <a href="berkas.php" class="btn-modern btn-primary-modern mb-2 ms-2">
                    <i class="bi bi-archive"></i> Lihat Arsip
                </a>
            </div>
            <?php endif; ?>
            
            <!-- Form Card -->
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="content-card shadow-lg p-5">
                        <div class="text-center mb-5">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-file-earmark-medical" style="font-size: 2.5rem;"></i>
                            </div>
                            <h2 class="fw-bold text-dark">Form Permintaan Data</h2>
                            <p class="text-muted">Isi formulir lengkap untuk mengajukan permohonan data medis</p>
                        </div>
                        
                        <form method="POST" action="" id="permintaanForm">
                            <div class="row">
                                <!-- Data Pasien -->
                                <div class="col-md-6">
                                    <div class="mb-5">
                                        <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">
                                            <i class="bi bi-person-badge text-primary me-2"></i>Data Pasien
                                        </h5>
                                        
                                        <div class="form-group mb-4">
                                            <label class="form-label required-label">
                                                Nama Lengkap Pasien
                                            </label>
                                            <input type="text" 
                                                   class="form-control-modern" 
                                                   name="pasien_nama" 
                                                   value="<?php echo htmlspecialchars($pasien_nama); ?>"
                                                   placeholder="Masukkan nama lengkap pasien"
                                                   required>
                                            <div class="form-text">Nama sesuai dengan identitas resmi</div>
                                        </div>
                                        
                                        <div class="form-group mb-4">
                                            <label class="form-label required-label">
                                                Nomor Induk Kependudukan (NIK)
                                            </label>
                                            <input type="text" 
                                                   class="form-control-modern" 
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
                                        
                                        <div class="form-group mb-4">
                                            <label class="form-label required-label">
                                                RS Tujuan
                                            </label>
                                            <select class="form-control-modern" name="ke_rs" required>
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
                                        
                                        <div class="form-group mb-4">
                                            <label class="form-label required-label mb-3">
                                                Tingkat Urgensi
                                            </label>
                                            <div class="row g-3">
                                                <div class="col-4">
                                                    <div class="urgency-option">
                                                        <input type="radio" name="urgensi" id="urg_1" value="urgent" <?php echo ($urgensi == 'urgent') ? 'checked' : ''; ?> required>
                                                        <label for="urg_1">
                                                            <i class="bi bi-exclamation-triangle text-danger urgency-icon"></i>
                                                            <div class="fw-bold text-danger">Urgent</div>
                                                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Segera</small>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="urgency-option">
                                                        <input type="radio" name="urgensi" id="urg_2" value="biasa" <?php echo ($urgensi == 'biasa') ? 'checked' : ''; ?> required>
                                                        <label for="urg_2">
                                                            <i class="bi bi-clock text-warning urgency-icon"></i>
                                                            <div class="fw-bold text-warning">Biasa</div>
                                                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Standar</small>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="urgency-option">
                                                        <input type="radio" name="urgensi" id="urg_3" value="tidak_urgent" <?php echo ($urgensi == 'tidak_urgent') ? 'checked' : ''; ?> required>
                                                        <label for="urg_3">
                                                            <i class="bi bi-calendar text-info urgency-icon"></i>
                                                            <div class="fw-bold text-info">Santai</div>
                                                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Bisa ditunda</small>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Keterangan -->
                            <div class="mb-5">
                                <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">
                                    <i class="bi bi-chat-left-text text-success me-2"></i>Alasan Permintaan
                                </h5>
                                
                                <div class="form-group mb-4">
                                    <label class="form-label required-label">
                                        Keterangan / Alasan
                                    </label>
                                    <textarea class="form-control-modern" 
                                              name="keterangan" 
                                              rows="4" 
                                              placeholder="Contoh: Pasien membutuhkan tindakan lanjutan..."
                                              required><?php echo htmlspecialchars($keterangan); ?></textarea>
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
                                    <button type="reset" class="btn-modern btn-secondary-modern me-2">
                                        <i class="bi bi-x-circle"></i> Reset
                                    </button>
                                    <button type="submit" name="submit_permintaan" class="btn-modern btn-primary-modern px-5">
                                        <i class="bi bi-send-fill"></i> Ajukan Permintaan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- **HISTORI PERMINTAAN** -->
                    <div class="content-card mt-5 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="fw-bold mb-1"><i class="bi bi-clock-history text-primary"></i> Histori Permintaan</h4>
                                <p class="text-muted mb-0">10 permintaan terakhir yang Anda ajukan</p>
                            </div>
                            <span class="badge bg-primary rounded-pill px-3 py-2">
                                <?php echo count($histori_permintaan); ?> permintaan
                            </span>
                        </div>
                        
                        <?php if(empty($histori_permintaan)): ?>
                            <div class="text-center py-5">
                                <div class="bg-light rounded-circle d-inline-flex p-4 mb-3">
                                    <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                </div>
                                <h5 class="text-dark">Belum ada permintaan</h5>
                                <p class="text-muted">Ajukan permintaan pertama Anda di atas</p>
                            </div>
                        <?php else: ?>
                            <div class="histori-list">
                                <?php 
                                $today = date('Y-m-d');
                                foreach($histori_permintaan as $item): 
                                    $status = $item['status'];
                                    
                                    // Status Logic
                                    if ($status == 'diterima') {
                                        $icon_bg = 'bg-success bg-opacity-10';
                                        $icon_color = 'text-success';
                                        $icon = 'bi-check-lg';
                                        $status_badge = '<span class="badge-modern badge-success"><i class="bi bi-check-circle"></i> DITERIMA</span>';
                                    } elseif ($status == 'ditolak') {
                                        $icon_bg = 'bg-danger bg-opacity-10';
                                        $icon_color = 'text-danger';
                                        $icon = 'bi-x-lg';
                                        $status_badge = '<span class="badge-modern badge-danger"><i class="bi bi-x-circle"></i> DITOLAK</span>';
                                    } else {
                                        $icon_bg = 'bg-warning bg-opacity-10';
                                        $icon_color = 'text-warning';
                                        $icon = 'bi-hourglass-split';
                                        $status_badge = '<span class="badge-modern badge-warning"><i class="bi bi-hourglass"></i> PENDING</span>';
                                    }
                                    
                                    // Expired check overrides status
                                    $expired_date = $item['tanggal_expired'] ?? '';
                                    $is_expired = $expired_date && $expired_date < $today;
                                    if($is_expired && $status == 'diterima') {
                                        $status_badge = '<span class="badge-modern badge-secondary"><i class="bi bi-clock"></i> EXPIRED</span>';
                                        $icon_bg = 'bg-secondary bg-opacity-10';
                                        $icon_color = 'text-secondary';
                                    }
                                ?>
                                <div class="histori-item p-3 mb-3 border rounded-3 bg-white shadow-sm position-relative hover-card w-100">
                                    <div class="histori-icon <?php echo $icon_bg . ' ' . $icon_color; ?> flex-shrink-0" style="width:50px; height:50px; font-size:1.5rem;">
                                        <i class="bi <?php echo $icon; ?>"></i>
                                    </div>
                                    
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <!-- Row 1: Nama & Status -->
                                        <div class="d-flex flex-wrap align-items-center mb-1 gap-2">
                                            <h6 class="fw-bold mb-0 text-truncate" style="max-width: 100%;"><?php echo htmlspecialchars($item['pasien_nama']); ?></h6>
                                            <?php echo $status_badge; ?>
                                        </div>
                                        
                                        <!-- Row 2: Meta Info (RS, Tgl, NIK) -->
                                        <div class="d-flex flex-wrap align-items-center text-muted small mb-1 gap-2">
                                            <span class="d-inline-flex align-items-center">
                                                <i class="bi bi-hospital me-1"></i> <?php echo $item['ke_rs']; ?>
                                            </span>
                                            <span class="text-secondary d-none d-sm-inline">•</span>
                                            <span class="d-inline-flex align-items-center">
                                                <i class="bi bi-calendar me-1"></i> <?php echo date('d M', strtotime($item['tanggal_permintaan'])); ?>
                                            </span>
                                            <span class="text-secondary d-none d-sm-inline">•</span>
                                            <span class="d-inline-flex align-items-center text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($item['pasien_nik']); ?>">
                                                <i class="bi bi-card-text me-1"></i> <?php echo htmlspecialchars($item['pasien_nik']); ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Row 3: Urgensi & Ket -->
                                        <div class="small text-truncate text-muted">
                                            <?php if($item['urgensi'] == 'urgent'): ?>
                                                <span class="text-danger fw-bold me-2"><i class="bi bi-exclamation-triangle-fill"></i> URGENT</span>
                                            <?php endif; ?>
                                            <span class="fst-italic">"<?php echo substr(htmlspecialchars($item['keterangan']), 0, 50) . (strlen($item['keterangan']) > 50 ? '...' : ''); ?>"</span>
                                        </div>
                                    </div>
                                    
                                    <a href="detail.php?id=<?php echo $item['id']; ?>" class="btn btn-light btn-sm rounded-circle p-2 flex-shrink-0 ms-auto" title="Lihat Detail">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="histori.php" class="btn-modern btn-outline-primary-modern">
                                    <i class="bi bi-clock-history"></i> Lihat Histori Lengkap
                                </a>
                            </div>
                        <?php endif; ?>
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

        // Initialize Custom Selects
        document.querySelectorAll('select.form-control-modern').forEach(el => {
            if(el.parentElement.classList.contains('custom-select-wrapper')) return;
            
            // Native select is hidden by CSS opacity to allow validation focus
            // el.style.display = 'none'; // REMOVED
            
            const wrapper = document.createElement('div');
            wrapper.className = 'custom-select-wrapper';
            el.parentNode.insertBefore(wrapper, el);
            wrapper.appendChild(el);
            
            const trigger = document.createElement('div');
            trigger.className = 'custom-select-trigger';
            const selectedOption = el.options[el.selectedIndex];
            trigger.textContent = selectedOption ? selectedOption.textContent : 'Pilih...';
            wrapper.appendChild(trigger);
            
            const optionsDiv = document.createElement('div');
            optionsDiv.className = 'custom-options';
            
            Array.from(el.options).forEach(opt => {
                if(opt.disabled && opt.value === '') return; 
                const optionDiv = document.createElement('div');
                optionDiv.className = 'custom-option' + (opt.selected ? ' selected' : '') + (opt.disabled ? ' disabled' : '');
                optionDiv.textContent = opt.textContent;
                
                if(!opt.disabled) {
                    optionDiv.addEventListener('click', e => {
                        e.stopPropagation();
                        trigger.textContent = opt.textContent;
                        wrapper.classList.remove('open');
                        wrapper.querySelectorAll('.custom-option').forEach(o => o.classList.remove('selected'));
                        optionDiv.classList.add('selected');
                        el.value = opt.value;
                        el.dispatchEvent(new Event('change'));
                    });
                }
                optionsDiv.appendChild(optionDiv);
            });
            wrapper.appendChild(optionsDiv);
            
            trigger.addEventListener('click', e => {
                document.querySelectorAll('.custom-select-wrapper').forEach(w => {
                    if(w !== wrapper) w.classList.remove('open');
                });
                if(!el.disabled) {
                    e.stopPropagation();
                    wrapper.classList.toggle('open');
                }
            });
        });

        document.addEventListener('click', () => {
            document.querySelectorAll('.custom-select-wrapper').forEach(w => w.classList.remove('open'));
        });
    });
    </script>
</body>
</html>