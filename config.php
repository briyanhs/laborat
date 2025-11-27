<?php
// 1. Konfigurasi Error Reporting
// Ubah menjadi 0 saat website sudah "Live" / dipakai user asli.
// Ubah menjadi 1 saat Anda sedang coding/debugging.
error_reporting(0); 
ini_set('display_errors', 0);

// 2. Konfigurasi Base URL (Kode Lama Anda)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$root_path = preg_replace('#/(admin|user|logout)$#', '', $script_path); // Update regex agar mencakup folder user/logout
$base_url = $protocol . $host . $root_path . '/';

define('BASE_URL', $base_url);
?>