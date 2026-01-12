<?php
/* ===============================
   KONEKSI KE SUPABASE (POSTGRESQL)
   Updated untuk v0.23+ compatibility
================================ */

require_once __DIR__ . '/env.php';

$host     = getenv('DB_HOST') ?: 'aws-1-ap-southeast-2.pooler.supabase.com'; 
$port     = getenv('DB_PORT') ?: '5432';
$dbname   = getenv('DB_NAME') ?: 'postgres';
$user     = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: '';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Koneksi Database Gagal: " . $e->getMessage());
}

/* ===============================
   DATA RUMAH SAKIT (Cache dari DB)
================================ */
$rumah_sakit = [];

try {
    $stmt = $pdo->query("SELECT * FROM rumah_sakit ORDER BY kode");
    while ($row = $stmt->fetch()) {
        $rumah_sakit[$row['kode']] = [
            'password' => $row['password'],
            'nama'     => $row['nama'],
            'key'      => $row['encryption_key']
        ];
    }
} catch (PDOException $e) {
    error_log("Failed to load rumah_sakit data: " . $e->getMessage());
}

/* ===============================
   FUNGSI DATABASE (PostgreSQL Compatible)
================================ */

/**
 * Get data dengan filter
 * @param string $collection Nama tabel
 * @param string $filter WHERE clause (tanpa "WHERE")
 * @return array
 */
function getData($collection, $filter = '') {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM " . pdb_escape_identifier($collection);
        
        if (!empty($filter)) {
            // Convert filter syntax jika perlu
            // PocketBase: dari_rs = 'RS001' AND status = 'pending'
            // PostgreSQL: sama, tapi kita escape identifier
            $sql .= " WHERE " . $filter;
        }
        
        // Default order by created/tanggal_permintaan DESC
        if ($collection === 'permintaan') {
            $sql .= " ORDER BY tanggal_permintaan DESC";
        } elseif ($collection === 'histori') {
            $sql .= " ORDER BY waktu DESC";
        } else {
            $sql .= " ORDER BY created DESC";
        }
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("getData Error ({$collection}): " . $e->getMessage());
        error_log("Query: " . $sql);
        return [];
    }
}

/**
 * Create data baru
 * @param string $collection Nama tabel
 * @param array $data Data yang akan disimpan
 * @return array ['success' => bool, 'id' => string]
 */
function createData($collection, $data) {
    global $pdo;
    
    try {
        // Generate ID jika belum ada
        if (!isset($data['id'])) {
            $data['id'] = uniqid() . '_' . time();
        }
        
        // Set timestamps
        if (!isset($data['created'])) {
            $data['created'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['updated'])) {
            $data['updated'] = date('Y-m-d H:i:s');
        }
        
        // Build INSERT query
        $columns = array_keys($data);
        $placeholders = array_map(function($col) { return ':' . $col; }, $columns);
        
        $sql = "INSERT INTO " . pdb_escape_identifier($collection) . " (" 
             . implode(', ', array_map('pdb_escape_identifier', $columns)) . ") VALUES (" 
             . implode(', ', $placeholders) . ") RETURNING id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        
        $result = $stmt->fetch();
        
        return [
            'success' => true, 
            'id' => $result['id'] ?? $data['id']
        ];
        
    } catch (PDOException $e) {
        error_log("createData Error ({$collection}): " . $e->getMessage());
        error_log("Data: " . json_encode($data));
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Update data
 * @param string $collection Nama tabel
 * @param string $id ID record
 * @param array $data Data yang akan diupdate
 * @return array ['success' => bool]
 */
function updateData($collection, $id, $data) {
    global $pdo;
    
    try {
        // Auto-update timestamp
        $data['updated'] = date('Y-m-d H:i:s');
        
        // Build UPDATE query
        $sets = [];
        foreach ($data as $key => $value) {
            $sets[] = pdb_escape_identifier($key) . " = :" . $key;
        }
        
        $sql = "UPDATE " . pdb_escape_identifier($collection) 
             . " SET " . implode(', ', $sets) 
             . " WHERE id = :target_id";
        
        $data['target_id'] = $id;
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($data);
        
        return ['success' => $success];
        
    } catch (PDOException $e) {
        error_log("updateData Error ({$collection}, {$id}): " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Delete data
 * @param string $collection Nama tabel
 * @param string $id ID record
 * @return array ['success' => bool]
 */
function deleteData($collection, $id) {
    global $pdo;
    
    try {
        $sql = "DELETE FROM " . pdb_escape_identifier($collection) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute(['id' => $id]);
        
        return ['success' => $success];
        
    } catch (PDOException $e) {
        error_log("deleteData Error ({$collection}, {$id}): " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get single record by ID
 * @param string $collection Nama tabel
 * @param string $id ID record
 * @return array|null
 */
function getById($collection, $id) {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM " . pdb_escape_identifier($collection) . " WHERE id = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $result = $stmt->fetch();
        return $result ?: null;
        
    } catch (PDOException $e) {
        error_log("getById Error ({$collection}, {$id}): " . $e->getMessage());
        return null;
    }
}

/**
 * Get encryption key untuk RS tertentu
 * @param string $rs_kode Kode RS (RS001, RS002, etc)
 * @return string|null
 */
function getHospitalKey($rs_kode) {
    global $rumah_sakit;
    return $rumah_sakit[$rs_kode]['key'] ?? null;
}

/**
 * Helper: Escape identifier PostgreSQL
 * @param string $identifier
 * @return string
 */
function pdb_escape_identifier($identifier) {
    return '"' . str_replace('"', '""', $identifier) . '"';
}

/**
 * Helper: Count records
 * @param string $collection Nama tabel
 * @param string $filter WHERE clause (tanpa "WHERE")
 * @return int
 */
function countData($collection, $filter = '') {
    global $pdo;
    
    try {
        $sql = "SELECT COUNT(*) as total FROM " . pdb_escape_identifier($collection);
        
        if (!empty($filter)) {
            $sql .= " WHERE " . $filter;
        }
        
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch();
        
        return (int) ($result['total'] ?? 0);
        
    } catch (PDOException $e) {
        error_log("countData Error ({$collection}): " . $e->getMessage());
        return 0;
    }
}

/* ===============================
   TESTING CONNECTION (Uncomment untuk debug)
================================ */
/*
try {
    $test = $pdo->query("SELECT COUNT(*) as count FROM rumah_sakit")->fetch();
    error_log("✅ Database Connected! Total RS: " . $test['count']);
} catch (PDOException $e) {
    error_log("❌ Database Test Failed: " . $e->getMessage());
}
*/
?>