<?php
// admin/proses_tambah.php (MODUL FISIKA & KIMIA)

include '../database/database.php';
include '../config.php';

// 1. Security Session
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// 2. Cek Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

// 3. Cek CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Token CSRF tidak valid! Akses ditolak.");
}

// --- FUNGSI LOGIKA KEPATUHAN ---
function cekKepatuhan($hasil, $standar)
{
    // Pastikan input di-cast ke string dan di-trim
    $hasilStr = trim((string)$hasil);
    $standarStr = trim((string)$standar);

    if ($hasilStr === '' || $standarStr === '') {
        return '';
    }

    // Abaikan jika standar berupa teks deskriptif (misal: "Suhu Udara")
    if (stripos($standarStr, 'suhu udara') !== false) {
        return '';
    }

    // Normalisasi: Ganti koma dengan titik (Format Indonesia ke Internasional)
    $standarStr = str_replace(',', '.', $standarStr);
    $hasilStr = str_replace(',', '.', $hasilStr);

    // Cek apakah hasil adalah angka valid
    $hasilNum = is_numeric($hasilStr) ? (float)$hasilStr : null;

    // 1. LOGIKA RENTANG (Contoh: 6.5 - 8.5)
    $standarNormalized = str_replace(['–', '—'], '-', $standarStr); // Handle berbagai jenis dash
    if (strpos($standarNormalized, '-') !== false) {
        $parts = explode('-', $standarNormalized);
        if (count($parts) === 2) {
            // Ambil angka saja (bersihkan karakter aneh jika ada)
            $min = (float)filter_var($parts[0], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $max = (float)filter_var($parts[1], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            if ($hasilNum !== null) {
                // Gunakan epsilon untuk akurasi float
                $epsilon = 0.000001;
                $isMemenuhi = ($hasilNum >= ($min - $epsilon)) && ($hasilNum <= ($max + $epsilon));
                return $isMemenuhi ? 'Memenuhi' : 'Tidak Memenuhi';
            }
        }
    }

    // 2. LOGIKA KURANG DARI (Contoh: < 10)
    if (str_starts_with($standarStr, '<')) {
        $maxStr = trim(substr($standarStr, 1));
        if (is_numeric($maxStr) && $hasilNum !== null) {
            return $hasilNum < (float)$maxStr ? 'Memenuhi' : 'Tidak Memenuhi';
        }
    }

    // 3. LOGIKA LEBIH DARI (Contoh: > 1)
    if (str_starts_with($standarStr, '>')) {
        $minStr = trim(substr($standarStr, 1));
        if (is_numeric($minStr) && $hasilNum !== null) {
            return $hasilNum > (float)$minStr ? 'Memenuhi' : 'Tidak Memenuhi';
        }
    }

    // 4. LOGIKA NILAI PASTI / TEKS
    if ($hasilNum === null) {
        // Jika hasil bukan angka (misal: "Negatif"), bandingkan string case-insensitive
        return strtolower($hasilStr) === strtolower($standarStr) ? 'Memenuhi' : 'Tidak Memenuhi';
    }

    // 5. LOGIKA MAKSIMUM STANDAR (Jika hanya angka, anggap itu batas maksimum)
    if (is_numeric($standarStr)) {
        return $hasilNum <= (float)$standarStr ? 'Memenuhi' : 'Tidak Memenuhi';
    }

    return '';
}
// ====================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($con) || !$con) {
        header("Location: fisika_kimia.php?pesan=gagal&error_msg=db_connect_failed");
        exit();
    }

    // --- AMBIL DATA INPUT (TANPA real_escape_string karena pakai Prepared Statement) ---
    $nama_pelanggan    = $_POST['nama_pelanggan'] ?? '';
    $alamat            = $_POST['alamat'] ?? '';
    $status_pelanggan  = $_POST['status_pelanggan'] ?? '';
    $jenis_sampel      = $_POST['jenis_sampel'] ?? '';
    $keterangan_sampel = $_POST['keterangan_sampel'] ?? '';
    $nama_pengirim     = $_POST['nama_pengirim'] ?? '';
    $no_analisa        = $_POST['no_analisa'] ?? '';
    $wilayah           = $_POST['wilayah'] ?? '';
    $status_global     = $_POST['status'] ?? 'Proses';

    // --- HANDLING TANGGAL (Ubah string kosong jadi NULL) ---
    $tanggal_pengambilan = !empty($_POST['tanggal_pengambilan']) ? $_POST['tanggal_pengambilan'] : NULL;
    $tanggal_pengiriman  = !empty($_POST['tanggal_pengiriman']) ? $_POST['tanggal_pengiriman'] : NULL;
    $tanggal_penerimaan  = !empty($_POST['tanggal_penerimaan']) ? $_POST['tanggal_penerimaan'] : NULL;
    $tanggal_pengujian   = !empty($_POST['tanggal_pengujian']) ? $_POST['tanggal_pengujian'] : NULL;

    // Data Array Parameter
    $hasil_parameters = $_POST['hasil'] ?? [];
    $param_details_from_form = $_POST['param_details'] ?? [];

    // Validasi Dasar
    if (empty($nama_pelanggan) || empty($no_analisa)) {
        header("Location: fisika_kimia.php?pesan=gagal&error_msg=Data Pelanggan atau No Analisa tidak boleh kosong");
        exit();
    }

    // Mulai Transaksi
    mysqli_begin_transaction($con);

    try {
        // 1. INSERT DATA MASTER
        $query_insert_master = "INSERT INTO master_hasil_uji 
            (nama_pelanggan, alamat, status_pelanggan, jenis_sampel, keterangan_sampel, nama_pengirim, no_analisa, wilayah, tanggal_pengambilan, tanggal_pengiriman, tanggal_penerimaan, tanggal_pengujian) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_master = mysqli_prepare($con, $query_insert_master);
        if (!$stmt_master) {
            throw new Exception("Prepare Master Failed: " . mysqli_error($con));
        }

        // Bind: 12 string ('s')
        mysqli_stmt_bind_param($stmt_master, "ssssssssssss", 
            $nama_pelanggan, $alamat, $status_pelanggan, $jenis_sampel, 
            $keterangan_sampel, $nama_pengirim, $no_analisa, $wilayah, 
            $tanggal_pengambilan, $tanggal_pengiriman, $tanggal_penerimaan, $tanggal_pengujian
        );
        
        if (!mysqli_stmt_execute($stmt_master)) {
            throw new Exception("Execute Master Failed: " . mysqli_stmt_error($stmt_master));
        }

        $id_m_hasil_uji = mysqli_insert_id($con);
        mysqli_stmt_close($stmt_master);

        if ($id_m_hasil_uji == 0) {
            throw new Exception("Gagal mendapatkan ID master baru.");
        }

        // 2. INSERT DATA DETAIL (HASIL UJI)
        if (!empty($hasil_parameters)) {
            $query_insert_hasil = "INSERT INTO hasil_uji 
                (id_m_hasil_uji, nama_parameter, satuan, kadar_maksimum, metode_uji, kategori, hasil, status, keterangan) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_hasil = mysqli_prepare($con, $query_insert_hasil);
            if (!$stmt_hasil) {
                throw new Exception("Prepare Detail Failed: " . mysqli_error($con));
            }

            foreach ($hasil_parameters as $param_id => $hasil_value) {
                // Pastikan detail parameter ada (dari input hidden)
                if (isset($param_details_from_form[$param_id])) {
                    $details = $param_details_from_form[$param_id];

                    $nama_parameter = $details['nama_parameter'] ?? '';
                    $satuan         = $details['satuan'] ?? '';
                    $kadar_maksimum = $details['kadar_maksimum'] ?? '';
                    $metode_uji     = $details['metode_uji'] ?? '';
                    $kategori       = $details['kategori'] ?? '';

                    // Hitung status kepatuhan secara otomatis
                    $keterangan = cekKepatuhan($hasil_value, $kadar_maksimum);

                    // Bind: 1 int, 8 string
                    mysqli_stmt_bind_param($stmt_hasil, "issssssss", 
                        $id_m_hasil_uji, 
                        $nama_parameter, 
                        $satuan, 
                        $kadar_maksimum, 
                        $metode_uji, 
                        $kategori, 
                        $hasil_value, 
                        $status_global, 
                        $keterangan
                    );
                    
                    if (!mysqli_stmt_execute($stmt_hasil)) {
                        throw new Exception("Execute Detail Failed (Param ID: $param_id)");
                    }
                }
            }
            mysqli_stmt_close($stmt_hasil);
        }

        // Commit Transaksi
        mysqli_commit($con);
        header("Location: fisika_kimia.php?pesan=sukses_tambah");
        exit();

    } catch (Exception $e) {
        // Rollback jika error
        mysqli_rollback($con);
        
        // Log error ke server
        error_log("Error Tambah Fisika/Kimia: " . $e->getMessage());
        
        // Redirect dengan pesan error umum
        $error_msg = urlencode("Terjadi kesalahan sistem saat menyimpan data.");
        header("Location: fisika_kimia.php?pesan=gagal&error_msg=" . $error_msg);
        exit();
    }
} else {
    header("Location: fisika_kimia.php?pesan=gagal&error=invalid_request");
    exit();
}

if (isset($con)) {
    mysqli_close($con);
}
?>