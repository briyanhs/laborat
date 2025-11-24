<?php
/**
 * Daftar nama folder yang dianggap sebagai "sub-aplikasi"
 * dan perlu "dihapus" dari path untuk menemukan URL dasar.
 */
$sub_folders = ['admin', 'user', 'lab', 'report', 'dll'];

// 1. Deteksi protokol: http atau https
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';

// 2. Ambil nama host (misal: localhost)
$host = $_SERVER['HTTP_HOST'];

// 3. Ambil path direktori dari skrip yang sedang diakses
// Contoh: /proyek-saya/admin
$script_path = dirname($_SERVER['SCRIPT_NAME']);

// 4. Dapatkan segmen/nama folder terakhir dari path
// Contoh: 'admin'
$last_segment = basename($script_path);

// 5. Periksa apakah folder terakhir ada di dalam daftar $sub_folders
if (in_array($last_segment, $sub_folders)) {
    // Jika YA (misal, kita ada di /proyek-saya/admin):
    // Kita "naik satu level" untuk mendapatkan path root-nya.
    // dirname('/proyek-saya/admin') akan menghasilkan '/proyek-saya'
    $root_path = dirname($script_path);
} else {
    // Jika TIDAK (misal, kita ada di /proyek-saya/index.php):
    // Berarti kita sudah berada di path root.
    $root_path = $script_path;
}

// 6. Gabungkan semuanya
// rtrim() digunakan untuk mencegah '//' jika $root_path adalah '/'
// (misal, jika web Anda ada di root domain langsung)
$base_url = $protocol . $host . rtrim($root_path, '/') . '/';

// 7. Simpan sebagai konstanta
define('BASE_URL', $base_url);

// (Untuk debugging, hapus baris ini di produksi)
// echo BASE_URL; 
?>