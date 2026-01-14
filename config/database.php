<?php

require_once __DIR__ . '/env.php';

$db_type = env('DB_TYPE', 'pgsql');
$pdo = null;
$rumah_sakit = [];

// PostgreSQL Connection (Supabase)
if ($db_type === 'pgsql') {
    $host = env('DB_HOST', 'aws-1-ap-southeast-2.pooler.supabase.com');
    $port = env('DB_PORT', '6543');
    $dbname = env('DB_NAME', 'postgres');
    $user = env('DB_USER', 'postgres.hynaalnhtpkdagiccvzv');
    $password = env('DB_PASSWORD', 'Medrecnibos');
    $sslmode = env('DB_SSLMODE', 'require');
    
    try {
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        if ($sslmode) {
            $dsn .= ";sslmode=$sslmode";
        }
        
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 10
        ]);
        
        // Test connection
        $pdo->query("SELECT 1 as test");
        
    } catch (PDOException $e) {
        // Fallback to SQLite
        $db_type = 'sqlite';
    }
}

// SQLite Connection (fallback)
if ($db_type === 'sqlite' || $pdo === null) {
    $db_path = env('DB_PATH', __DIR__ . '/../database.sqlite');
    $db_path = realpath(dirname($db_path)) . '/' . basename($db_path);
    
    try {
        // Create directory if not exists
        $db_dir = dirname($db_path);
        if (!is_dir($db_dir)) {
            mkdir($db_dir, 0755, true);
        }
        
        $pdo = new PDO("sqlite:$db_path");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        
        // Initialize SQLite database with tables
        initSqliteDatabase($pdo);
        
    } catch (PDOException $e) {
        die("Database connection failed");
    }
}

/* ===============================
   INITIALIZE SQLITE DATABASE
================================ */
function initSqliteDatabase($pdo) {
    try {
        // Create tables if not exists
        $tables = [
            'rumah_sakit' => "
                CREATE TABLE IF NOT EXISTS rumah_sakit (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    kode VARCHAR(10) UNIQUE NOT NULL,
                    nama VARCHAR(100) NOT NULL,
                    password VARCHAR(100) NOT NULL,
                    encryption_key TEXT,
                    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ",
            'rekam_medis' => "
                CREATE TABLE IF NOT EXISTS rekam_medis (
                    id VARCHAR(50) PRIMARY KEY,
                    rs_asal VARCHAR(10) NOT NULL,
                    no_rm VARCHAR(50) NOT NULL,
                    nama_pasien VARCHAR(100) NOT NULL,
                    tanggal_lahir DATE,
                    jenis_kelamin VARCHAR(10),
                    diagnosa TEXT,
                    pengobatan TEXT,
                    data_encrypted TEXT NOT NULL,
                    iv TEXT NOT NULL,
                    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ",
            'permintaan' => "
                CREATE TABLE IF NOT EXISTS permintaan (
                    id VARCHAR(50) PRIMARY KEY,
                    dari_rs VARCHAR(10) NOT NULL,
                    untuk_rs VARCHAR(10) NOT NULL,
                    no_rm VARCHAR(50) NOT NULL,
                    nama_pasien VARCHAR(100),
                    alasan TEXT,
                    status VARCHAR(20) DEFAULT 'pending',
                    tanggal_permintaan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    tanggal_diproses TIMESTAMP,
                    data_dikirim TEXT,
                    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ",
            'histori' => "
                CREATE TABLE IF NOT EXISTS histori (
                    id VARCHAR(50) PRIMARY KEY,
                    rs_kode VARCHAR(10) NOT NULL,
                    aksi VARCHAR(50) NOT NULL,
                    detail TEXT,
                    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            "
        ];
        
        foreach ($tables as $table => $sql) {
            $pdo->exec($sql);
        }
        
        // Insert sample hospitals if not exists
        $count = $pdo->query("SELECT COUNT(*) as count FROM rumah_sakit")->fetch()['count'];
        if ($count == 0) {
            $sample_hospitals = [
                ['RS001', 'RS Umum Kota', 'rs001pass', 'key_rs001_' . bin2hex(random_bytes(8))],
                ['RS002', 'RS Khusus Jantung', 'rs002pass', 'key_rs002_' . bin2hex(random_bytes(8))],
                ['RS003', 'RS Ibu Anak', 'rs003pass', 'key_rs003_' . bin2hex(random_bytes(8))]
            ];
            
            $stmt = $pdo->prepare("INSERT INTO rumah_sakit (kode, nama, password, encryption_key) VALUES (?, ?, ?, ?)");
            foreach ($sample_hospitals as $hospital) {
                $stmt->execute($hospital);
            }
        }
        
    } catch (Exception $e) {
        // Silent fail
    }
}

/* ===============================
   LOAD HOSPITAL DATA
================================ */
function loadHospitalData() {
    global $pdo, $rumah_sakit;
    
    try {
        $stmt = $pdo->query("SELECT * FROM rumah_sakit ORDER BY kode");
        $hospitals = $stmt->fetchAll();
        
        foreach ($hospitals as $row) {
            $rumah_sakit[$row['kode']] = [
                'password' => $row['password'],
                'nama'     => $row['nama'],
                'key'      => $row['encryption_key'] ?? ''
            ];
        }
        
        return true;
        
    } catch (Exception $e) {
        // Create default hospitals if table doesn't exist
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS rumah_sakit (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    kode VARCHAR(10) UNIQUE NOT NULL,
                    nama VARCHAR(100) NOT NULL,
                    password VARCHAR(100) NOT NULL,
                    encryption_key TEXT,
                    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            $default_hospitals = [
                ['RS001', 'RS Harapan Sehat', 'rs001pass', 'key_rs001_default'],
                ['RS002', 'RS Sejahtera', 'rs002pass', 'key_rs002_default'],
                ['RS003', 'RS Mitra Medika', 'rs003pass', 'key_rs003_default']
            ];
            
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO rumah_sakit (kode, nama, password, encryption_key) VALUES (?, ?, ?, ?)");
            foreach ($default_hospitals as $hospital) {
                $stmt->execute($hospital);
            }
            
            // Reload
            return loadHospitalData();
            
        } catch (Exception $e2) {
            return false;
        }
    }
}

// Load hospital data
loadHospitalData();

/* ===============================
   GLOBAL DATABASE FUNCTIONS
================================ */

if (!function_exists('getData')) {
    function getData($table, $where = '') {
        global $pdo;
        if (!$pdo) {
            error_log("getData: Database connection not available");
            return [];
        }
        
        try {
            $sql = "SELECT * FROM $table";
            if ($where) $sql .= " WHERE $where";
            
            // Default ordering
            if ($table === 'permintaan') {
                $sql .= " ORDER BY tanggal_permintaan DESC";
            } elseif ($table === 'histori') {
                $sql .= " ORDER BY waktu DESC";
            } elseif ($table === 'rekam_medis') {
                $sql .= " ORDER BY created DESC";
            }
            
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("getData error ($table): " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('createData')) {
    function createData($table, $data) {
        global $pdo;
        if (!$pdo) {
            error_log("createData: Database connection not available");
            return ['success' => false, 'error' => 'No database connection'];
        }
        
        try {
            if (!isset($data['id'])) {
                $data['id'] = uniqid() . '_' . time();
            }
            if (!isset($data['created'])) {
                $data['created'] = date('Y-m-d H:i:s');
            }
            
            $keys = implode(',', array_keys($data));
            $placeholders = ':' . implode(',:', array_keys($data));
            
            $sql = "INSERT INTO $table ($keys) VALUES ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            
            return ['success' => true, 'id' => $data['id']];
            
        } catch (PDOException $e) {
            error_log("createData error ($table): " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

if (!function_exists('updateData')) {
    function updateData($table, $id, $data) {
        global $pdo;
        if (!$pdo) {
            error_log("updateData: Database connection not available");
            return ['success' => false, 'error' => 'No database connection'];
        }
        
        try {
            $sets = [];
            foreach ($data as $key => $value) {
                $sets[] = "$key = :$key";
            }
            
            $sql = "UPDATE $table SET " . implode(', ', $sets) . " WHERE id = :id";
            $data['id'] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            error_log("updateData error ($table, $id): " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

if (!function_exists('deleteData')) {
    function deleteData($table, $id) {
        global $pdo;
        if (!$pdo) {
            error_log("deleteData: Database connection not available");
            return ['success' => false, 'error' => 'No database connection'];
        }
        
        try {
            $sql = "DELETE FROM $table WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            error_log("deleteData error ($table, $id): " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

if (!function_exists('getById')) {
    function getById($table, $id) {
        global $pdo;
        if (!$pdo) {
            error_log("getById: Database connection not available");
            return null;
        }
        
        try {
            $sql = "SELECT * FROM $table WHERE id = :id LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("getById error ($table, $id): " . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('getHospitalKey')) {
    function getHospitalKey($rs_kode) {
        global $rumah_sakit;
        return $rumah_sakit[$rs_kode]['key'] ?? null;
    }
}

if (!function_exists('countData')) {
    function countData($table, $where = '') {
        global $pdo;
        if (!$pdo) {
            error_log("countData: Database connection not available");
            return 0;
        }
        
        try {
            $sql = "SELECT COUNT(*) as total FROM $table";
            if ($where) $sql .= " WHERE $where";
            
            $stmt = $pdo->query($sql);
            $result = $stmt->fetch();
            
            return (int) ($result['total'] ?? 0);
            
        } catch (PDOException $e) {
            error_log("countData error ($table): " . $e->getMessage());
            return 0;
        }
    }
}
?>