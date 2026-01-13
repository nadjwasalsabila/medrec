<?php
// config/env.php
// Clean .env loader (NO echo, NO output, SAFE for production)

// Daftar lokasi kemungkinan file .env
$possiblePaths = [
    __DIR__ . '/../.env.local',
    __DIR__ . '/../.env',
    __DIR__ . '/../../.env.local',
    __DIR__ . '/../../.env',
];

$envPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $envPath = $path;
        break;
    }
}

// Jika .env tidak ditemukan → STOP (tapi TANPA echo)
if (!$envPath) {
    throw new RuntimeException(
        '.env file not found. Checked paths: ' . implode(', ', $possiblePaths)
    );
}

// Load isi .env
$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    $line = trim($line);

    // Skip komentar & baris kosong
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }

    // Parse KEY=VALUE
    if (strpos($line, '=') === false) {
        continue;
    }

    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);

    // Hapus tanda kutip
    $value = trim($value, "\"'");

    // Set environment
    putenv("$key=$value");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

/**
 * Helper env()
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function env(string $key, $default = null) {
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    $lower = strtolower($value);

    if ($lower === 'true') return true;
    if ($lower === 'false') return false;

    if (is_numeric($value)) {
        return strpos($value, '.') !== false ? (float)$value : (int)$value;
    }

    return $value;
}
