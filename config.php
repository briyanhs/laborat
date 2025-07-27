<?php
// Deteksi protokol: http atau https
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';

// Ambil nama host (misal: localhost)
$host = $_SERVER['HTTP_HOST'];

// Ambil path dari script, lalu hapus nama file (index.php) dan subfolder (admin, lab, dll)
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$root_path = preg_replace('#/(admin)$#', '', $script_path);

// Gabungkan semuanya
$base_url = $protocol . $host . $root_path . '/';

// Simpan sebagai konstanta
define('BASE_URL', $base_url);
?>
