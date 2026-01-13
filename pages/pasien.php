<?php
session_start();
if(!isset($_SESSION['rs_kode'])){
    header('Location: ../login.php');
    exit;
}

$rs_kode = $_SESSION['rs_kode'];
$rs_nama = $_SESSION['rs_nama'];

require_once '../config/database.php';
require_once '../config/encryption.php';

// Get encryption key for this hospital
$hospital_key = getHospitalKey($rs_kode);

$success = '';
$error = '';

// Handle form submission - Tambah Pasien
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if($_POST['action'] === 'tambah') {
        $nik = trim($_POST['nik'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $riwayat = trim($_POST['riwayat'] ?? '');
        
        // Validation
        if(empty($nik) || empty($nama)) {
            $error = 'NIK dan Nama wajib diisi!';
        } elseif(strlen($nik) < 16) {
            $error = 'NIK harus 16 digit!';
        } elseif(!$hospital_key) {
            $error = 'Encryption key tidak ditemukan!';
        } else {
            // Check duplicate NIK (decrypt and compare)
            $existing_patients = getData('pasien', "rs_asal = '$rs_kode'");
            $nik_exists = false;
            foreach($existing_patients as $p) {
                $decrypted_nik = decryptData($p['nik_encrypted'], $hospital_key);
                if($decrypted_nik === $nik) {
                    $nik_exists = true;
                    break;
                }
            }
            
            if($nik_exists) {
                $error = 'NIK <strong>' . htmlspecialchars($nik) . '</strong> sudah terdaftar! Gunakan NIK lain.';
            } else {
                // Generate unique ID
                $id = 'pas_' . strtolower($rs_kode) . '_' . uniqid();
                
                // Encrypt data
                $nik_encrypted = encryptData($nik, $hospital_key);
                $nama_encrypted = encryptData($nama, $hospital_key);
                $riwayat_encrypted = encryptData($riwayat ?: 'Belum ada riwayat', $hospital_key);
                
                // Insert to database
                $result = createData('pasien', [
                    'id' => $id,
                    'nik_encrypted' => $nik_encrypted,
                    'nama_encrypted' => $nama_encrypted,
                    'riwayat_encrypted' => $riwayat_encrypted,
                    'rs_asal' => $rs_kode,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                if($result['success']) {
                    $success = "Pasien <strong>$nama</strong> berhasil ditambahkan!";
                } else {
                    $error = 'Gagal menambahkan pasien: ' . ($result['error'] ?? 'Unknown error');
                }
            }
        }
    }
}

// Get ALL patients from ALL hospitals (for shared visibility)
$pasien_list = getData('pasien', ''); // No filter - get all

// Decrypt patient data for display
// Nama & NIK: visible to all (decrypt with respective RS key)
// Riwayat: only visible to owner RS
$decrypted_patients = [];
foreach($pasien_list as $p) {
    $patient_rs = $p['rs_asal'] ?? '';
    $patient_key = getHospitalKey($patient_rs);
    
    // Check if this patient belongs to current RS
    $is_owner = ($patient_rs === $rs_kode);
    
    // Decrypt nama and NIK (visible to all)
    $nama = $patient_key ? decryptData($p['nama_encrypted'], $patient_key) : '[Encrypted]';
    $nik = $patient_key ? decryptData($p['nik_encrypted'], $patient_key) : '[Encrypted]';
    
    // Riwayat: only decrypt if owner, otherwise show restricted message
    if($is_owner && $patient_key) {
        $riwayat = decryptData($p['riwayat_encrypted'], $patient_key);
    } else {
        $riwayat = null; // Will show "Akses Terbatas" in UI
    }
    
    $decrypted_patients[] = [
        'id' => $p['id'],
        'nik' => $nik,
        'nama' => $nama,
        'riwayat' => $riwayat,
        'rs_asal' => $patient_rs,
        'is_owner' => $is_owner,
        'created_at' => $p['created_at'] ?? $p['created']
    ];
}

// Sort by name
usort($decrypted_patients, function($a, $b) {
    return strcmp($a['nama'], $b['nama']);
});
?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Pasien - <?php echo htmlspecialchars($rs_nama); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/modern-theme.css">
    <style>
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(180deg, #2c3e50, #1a2530);
            z-index: 1100;
            display: flex;
            align-items: center;
            padding: 0 20px;
        }
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
        
        .patient-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 16px;
            border: 1px solid var(--gray-200);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .patient-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-blue);
        }
        
        .patient-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .patient-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .riwayat-preview {
            background: var(--light-blue);
            border-radius: var(--radius-md);
            padding: 12px 16px;
            margin-top: 12px;
            border-left: 3px solid var(--primary-blue);
            font-size: 0.9rem;
            color: #4a5568;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 20px;
            border: 1px solid var(--gray-200);
            text-align: center;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 1.5rem;
        }
        
        .form-floating label {
            color: #6b7280;
        }
        
        .modal-header {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .modal-footer {
            border-top: none;
            padding-top: 0;
        }
    </style>
</head>
<body>
    <?php include '../components/topbar.php'; ?>
    
    <?php include '../components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container mt-4">
            <!-- Success/Error Messages -->
            <?php if($success): ?>
            <div class="alert alert-modern alert-success mb-4">
                <i class="bi bi-check-circle-fill" style="font-size: 1.5rem;"></i>
                <div class="flex-grow-1">
                    <strong>Berhasil!</strong><br>
                    <?php echo $success; ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if($error): ?>
            <div class="alert alert-modern alert-danger mb-4">
                <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.5rem;"></i>
                <div class="flex-grow-1">
                    <strong>Error!</strong><br>
                    <?php echo $error; ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark"><i class="bi bi-people-fill text-primary me-2"></i>Data Pasien</h2>
                    <p class="text-muted">Daftar pasien terdaftar di <?php echo htmlspecialchars($rs_nama); ?></p>
                </div>
                <div>
                    <button type="button" class="btn-modern btn-primary-modern" data-bs-toggle="modal" data-bs-target="#tambahPasienModal">
                        <i class="bi bi-plus-lg"></i> Tambah Pasien
                    </button>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h3 class="fw-bold mb-1"><?php echo count($decrypted_patients); ?></h3>
                        <p class="text-muted mb-0 small">Total Pasien</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="stat-card">
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <h3 class="fw-bold mb-1">AES-256</h3>
                        <p class="text-muted mb-0 small">Enkripsi Data</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-hospital"></i>
                        </div>
                        <h3 class="fw-bold mb-1"><?php echo $rs_kode; ?></h3>
                        <p class="text-muted mb-0 small">Kode RS</p>
                </div>
                </div>
            </div>
            
            <!-- Filter & Sort Controls -->
            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Filter Rumah Sakit</label>
                    <select id="filterRS" class="form-select" style="border-radius: 10px;">
                        <option value="all">🏥 Semua Rumah Sakit</option>
                        <option value="<?php echo $rs_kode; ?>" selected>⭐ <?php echo $rs_kode; ?> (RS Saya)</option>
                        <?php 
                        // Get unique RS codes
                        $rs_list = array_unique(array_column($decrypted_patients, 'rs_asal'));
                        sort($rs_list);
                        foreach($rs_list as $rs):
                            if($rs !== $rs_kode):
                        ?>
                        <option value="<?php echo $rs; ?>"><?php echo $rs; ?></option>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Urutkan</label>
                    <select id="sortBy" class="form-select" style="border-radius: 10px;">
                        <option value="nama-asc">📝 Nama (A-Z)</option>
                        <option value="nama-desc">📝 Nama (Z-A)</option>
                        <option value="date-desc">📅 Terbaru</option>
                        <option value="date-asc">📅 Terlama</option>
                        <option value="rs-asc">🏥 RS (A-Z)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Pencarian</label>
                    <div class="position-relative">
                        <input type="text" 
                               id="searchPatient" 
                               class="form-control ps-5" 
                               placeholder="Cari nama pasien..." 
                               style="border-radius: 10px;">
                        <i class="bi bi-search position-absolute text-muted" 
                           style="left: 14px; top: 50%; transform: translateY(-50%);"></i>
                    </div>
                </div>
            </div>
            
            <!-- Patient List -->
            <?php if(empty($decrypted_patients)): ?>
            <div class="text-center py-5 content-card">
                <div class="bg-light rounded-circle d-inline-flex p-4 mb-3">
                    <i class="bi bi-people text-secondary" style="font-size: 3rem;"></i>
                </div>
                <h4 class="text-dark">Belum ada data pasien</h4>
                <p class="text-muted mb-4">Klik tombol "Tambah Pasien" untuk menambahkan pasien baru.</p>
                <button type="button" class="btn-modern btn-primary-modern" data-bs-toggle="modal" data-bs-target="#tambahPasienModal">
                    <i class="bi bi-plus-lg"></i> Tambah Pasien Pertama
                </button>
            </div>
            <?php else: ?>
            <div class="row" id="patientList">
                <?php foreach($decrypted_patients as $patient): ?>
                <div class="col-lg-6 patient-item">
                    <div class="patient-card <?php echo $patient['is_owner'] ? '' : 'border-secondary'; ?>" 
                         style="<?php echo $patient['is_owner'] ? '' : 'opacity: 0.9;'; ?>">
                        <div class="d-flex align-items-start">
                            <div class="patient-avatar me-3 flex-shrink-0" 
                                 style="<?php echo $patient['is_owner'] ? '' : 'background: linear-gradient(135deg, #6c757d, #495057);'; ?>">
                                <?php echo strtoupper(substr($patient['nama'], 0, 1)); ?>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="fw-bold text-dark mb-1 patient-name">
                                    <?php echo htmlspecialchars($patient['nama']); ?>
                                </h5>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-card-text me-1"></i>
                                        <?php echo htmlspecialchars($patient['nik']); ?>
                                    </span>
                                    <span class="badge <?php echo $patient['is_owner'] ? 'bg-success' : 'bg-secondary'; ?> text-white border-0">
                                        <i class="bi bi-hospital me-1"></i>
                                        <?php echo htmlspecialchars($patient['rs_asal']); ?>
                                    </span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border-0">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        <?php echo date('d M Y', strtotime($patient['created_at'])); ?>
                                    </span>
                                </div>
                                
                                <?php if($patient['is_owner'] && $patient['riwayat']): ?>
                                <div class="riwayat-preview">
                                    <i class="bi bi-file-medical me-1"></i>
                                    <?php 
                                    $riwayat = $patient['riwayat'];
                                    echo htmlspecialchars(strlen($riwayat) > 150 ? substr($riwayat, 0, 150) . '...' : $riwayat); 
                                    ?>
                                </div>
                                <?php else: ?>
                                <div class="riwayat-preview bg-secondary bg-opacity-10" style="border-left-color: #6c757d;">
                                    <i class="bi bi-lock me-1 text-secondary"></i>
                                    <span class="text-secondary fst-italic">Akses terbatas - Data milik <?php echo htmlspecialchars($patient['rs_asal']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Footer Info -->
            <div class="mt-4 pt-4 border-top border-2">
                <div class="d-flex justify-content-between text-muted small">
                    <div>
                        <i class="bi bi-info-circle me-1"></i>
                        Menampilkan <span id="visibleCount"><?php echo count($decrypted_patients); ?></span> pasien
                    </div>
                    <div>
                        <i class="bi bi-shield-lock me-1"></i>
                        Data terenkripsi end-to-end
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal Tambah Pasien -->
    <div class="modal fade" id="tambahPasienModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header bg-primary text-white p-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-plus-fill me-2"></i>Tambah Pasien Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="tambah">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-medium">NIK <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-card-text text-muted"></i>
                                </span>
                                <input type="text" 
                                       name="nik" 
                                       class="form-control border-start-0 ps-0" 
                                       placeholder="Masukkan 16 digit NIK" 
                                       maxlength="16"
                                       pattern="[0-9]{16}"
                                       required
                                       style="border-radius: 0 8px 8px 0;">
                            </div>
                            <small class="text-muted">NIK akan dienkripsi dengan AES-256</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-medium">Nama Lengkap <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-person text-muted"></i>
                                </span>
                                <input type="text" 
                                       name="nama" 
                                       class="form-control border-start-0 ps-0" 
                                       placeholder="Masukkan nama lengkap pasien" 
                                       required
                                       style="border-radius: 0 8px 8px 0;">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-medium">Riwayat Medis</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 align-items-start pt-2">
                                    <i class="bi bi-file-medical text-muted"></i>
                                </span>
                                <textarea name="riwayat" 
                                          class="form-control border-start-0 ps-0" 
                                          rows="4" 
                                          placeholder="Masukkan riwayat medis, alergi, obat rutin, dll..."
                                          style="border-radius: 0 8px 8px 0;"></textarea>
                            </div>
                            <small class="text-muted">Opsional - bisa dikosongkan</small>
                        </div>
                        
                        <div class="alert alert-info small mb-0 d-flex align-items-center">
                            <i class="bi bi-shield-lock-fill me-2"></i>
                            <span>Semua data akan dienkripsi sebelum disimpan ke database</span>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-modern btn-primary-modern px-4">
                            <i class="bi bi-check-lg me-1"></i> Simpan Pasien
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchPatient');
        const filterRS = document.getElementById('filterRS');
        const sortBy = document.getElementById('sortBy');
        const patientList = document.getElementById('patientList');
        
        // Apply all filters
        function applyFilters() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const selectedRS = filterRS ? filterRS.value : 'all';
            const patientItems = document.querySelectorAll('.patient-item');
            let visibleCount = 0;
            
            patientItems.forEach(item => {
                const nameElement = item.querySelector('.patient-name');
                const rsElement = item.querySelector('.badge.bg-success, .badge.bg-secondary');
                
                if (nameElement && rsElement) {
                    const name = nameElement.textContent.toLowerCase().trim();
                    const rs = rsElement.textContent.trim();
                    
                    const matchesSearch = searchTerm === '' || name.includes(searchTerm);
                    const matchesRS = selectedRS === 'all' || rs === selectedRS;
                    
                    if (matchesSearch && matchesRS) {
                        item.style.display = '';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                }
            });
            
            // Update counter
            const countElement = document.getElementById('visibleCount');
            if (countElement) {
                countElement.textContent = visibleCount;
            }
            
            // Show no results message
            const existingNoResult = document.getElementById('noResultsMessage');
            if (existingNoResult) existingNoResult.remove();
            
            if (visibleCount === 0 && patientItems.length > 0) {
                const noResultsDiv = document.createElement('div');
                noResultsDiv.id = 'noResultsMessage';
                noResultsDiv.className = 'col-12';
                noResultsDiv.innerHTML = `
                    <div class="text-center py-5 content-card">
                        <div class="bg-light rounded-circle d-inline-flex p-4 mb-3">
                            <i class="bi bi-search text-secondary" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="text-dark">Tidak ada hasil ditemukan</h4>
                        <p class="text-muted mb-0">Coba ubah filter atau kata pencarian</p>
                    </div>
                `;
                patientList.appendChild(noResultsDiv);
            }
        }
        
        // Sort functionality
        function sortPatients() {
            if (!patientList || !sortBy) return;
            
            const sortValue = sortBy.value;
            const items = Array.from(document.querySelectorAll('.patient-item'));
            
            items.sort((a, b) => {
                const nameA = a.querySelector('.patient-name')?.textContent.trim() || '';
                const nameB = b.querySelector('.patient-name')?.textContent.trim() || '';
                const dateA = a.querySelector('.badge.bg-primary')?.textContent.trim() || '';
                const dateB = b.querySelector('.badge.bg-primary')?.textContent.trim() || '';
                const rsA = a.querySelector('.badge.bg-success, .badge.bg-secondary')?.textContent.trim() || '';
                const rsB = b.querySelector('.badge.bg-success, .badge.bg-secondary')?.textContent.trim() || '';
                
                switch(sortValue) {
                    case 'nama-asc':
                        return nameA.localeCompare(nameB);
                    case 'nama-desc':
                        return nameB.localeCompare(nameA);
                    case 'date-desc':
                        return new Date(dateB) - new Date(dateA);
                    case 'date-asc':
                        return new Date(dateA) - new Date(dateB);
                    case 'rs-asc':
                        return rsA.localeCompare(rsB);
                    default:
                        return 0;
                }
            });
            
            // Re-append sorted items
            items.forEach(item => patientList.appendChild(item));
            applyFilters();
        }
        
        // Event listeners
        if (searchInput) {
            searchInput.addEventListener('keyup', applyFilters);
            searchInput.addEventListener('focus', function() {
                this.style.borderColor = '#0d6efd';
                this.style.boxShadow = '0 0 0 0.25rem rgba(13, 110, 253, 0.25)';
            });
            searchInput.addEventListener('blur', function() {
                this.style.borderColor = '';
                this.style.boxShadow = 'none';
            });
        }
        
        if (filterRS) {
            filterRS.addEventListener('change', applyFilters);
        }
        
        if (sortBy) {
            sortBy.addEventListener('change', sortPatients);
        }
        
        // Apply initial filter (default to "RS Saya")
        applyFilters();
        
        // NIK input - only numbers
        const nikInput = document.querySelector('input[name="nik"]');
        if (nikInput) {
            nikInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    });
    </script>
</body>
</html>
