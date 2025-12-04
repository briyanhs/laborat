<?php
// admin/proses_edit_all.php (MODUL FISIKA & KIMIA)

include '../database/database.php';
include '../config.php';
session_start();

// 1. Cek Session & CSRF Token
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Akses Ditolak: Token CSRF tidak valid.");
}

// --- FUNGSI CEK KEPATUHAN (SAMA DENGAN JS, TAPI PHP VERSION) ---
function cekKepatuhan($hasil, $standar)
{
    if ($hasil === null || $standar === null || trim((string)$hasil) === '' || trim((string)$standar) === '') {
        return ''; // Data tidak lengkap
    }

    $standarStr = trim((string)$standar);
    $hasilStr = trim((string)$hasil);

    // Skip jika standar deskriptif (bukan angka)
    if (stripos($standarStr, 'suhu udara') !== false) {
        return '';
    }

    // Normalisasi: Ganti koma dengan titik
    $standarStr = str_replace(',', '.', $standarStr);
    $hasilStr = str_replace(',', '.', $hasilStr);
    $hasilNum = is_numeric($hasilStr) ? (float)$hasilStr : null;

    // Normalisasi dash
    $standarNormalized = str_replace(['–', '—'], '-', $standarStr);

    // 1. Cek Range (e.g., "6.5 - 8.5")
    if (strpos($standarNormalized, '-') !== false) {
        $parts = explode('-', $standarNormalized);
        if (count($parts) === 2) {
            // Ambil angka saja dari string (misal ada spasi)
            $min = (float)filter_var($parts[0], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $max = (float)filter_var($parts[1], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            if ($hasilNum !== null) {
                // Gunakan epsilon untuk komparasi float yang presisi
                return ($hasilNum >= $min && $hasilNum <= $max) ? 'Memenuhi' : 'Tidak Memenuhi';
            }
        }
    }

    // 2. Cek Kurang Dari (e.g., "< 50")
    if (str_starts_with($standarStr, '<')) {
        $maxStr = trim(substr($standarStr, 1));
        if (is_numeric($maxStr) && $hasilNum !== null) {
            return $hasilNum < (float)$maxStr ? 'Memenuhi' : 'Tidak Memenuhi';
        }
    }

    // 3. Cek Lebih Dari (e.g., "> 1")
    if (str_starts_with($standarStr, '>')) {
        $minStr = trim(substr($standarStr, 1));
        if (is_numeric($minStr) && $hasilNum !== null) {
            return $hasilNum > (float)$minStr ? 'Memenuhi' : 'Tidak Memenuhi';
        }
    }

    // 4. Cek Nilai Pasti (Angka Tunggal = Maksimum)
    if (is_numeric($standarStr) && $hasilNum !== null) {
        return $hasilNum <= (float)$standarStr ? 'Memenuhi' : 'Tidak Memenuhi';
    }

    // 5. Cek String Sama Persis (Non-Numerik)
    return strtolower($hasilStr) === strtolower($standarStr) ? 'Memenuhi' : 'Tidak Memenuhi';
}
// ===============================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($con) || !$con) {
        header("Location: fisika_kimia.php?pesan=gagal&error_msg=db_connect_failed");
        exit();
    }

    // Ambil Data Post
    $id_m_hasil_uji      = isset($_POST['id_m_hasil_uji']) ? intval($_POST['id_m_hasil_uji']) : 0;
    $nama_pelanggan      = $_POST['nama_pelanggan'] ?? '';
    $alamat              = $_POST['alamat'] ?? '';
    $status_pelanggan    = $_POST['status_pelanggan'] ?? '';
    $jenis_sampel        = $_POST['jenis_sampel'] ?? '';
    $keterangan_sampel   = $_POST['keterangan_sampel'] ?? '';
    $nama_pengirim       = $_POST['nama_pengirim'] ?? '';
    $no_analisa          = $_POST['no_analisa'] ?? '';
    $wilayah             = $_POST['wilayah'] ?? '';
    $tanggal_pengambilan = $_POST['tanggal_pengambilan'] ?? '';
    $tanggal_pengiriman  = $_POST['tanggal_pengiriman'] ?? '';
    $tanggal_penerimaan  = $_POST['tanggal_penerimaan'] ?? '';
    $tanggal_pengujian   = $_POST['tanggal_pengujian'] ?? '';

    $global_status_param = $_POST['global_status_param'] ?? 'Proses';
    $hasil_uji_details   = $_POST['hasil_uji'] ?? []; // Array hasil dari form edit

    if (empty($id_m_hasil_uji) || empty($no_analisa)) {
        header("Location: fisika_kimia.php?pesan=gagal&error_msg=Data ID atau No Analisa kosong");
        exit();
    }

    mysqli_begin_transaction($con);

    try {
        // 1. UPDATE DATA MASTER
        $query_update_master = "UPDATE master_hasil_uji SET 
            nama_pelanggan=?, alamat=?, status_pelanggan=?, jenis_sampel=?, 
            keterangan_sampel=?, nama_pengirim=?, no_analisa=?, wilayah=?, 
            tanggal_pengambilan=?, tanggal_pengiriman=?, tanggal_penerimaan=?, tanggal_pengujian=? 
            WHERE id_m_hasil_uji = ?";

        $stmt_master = mysqli_prepare($con, $query_update_master);
        mysqli_stmt_bind_param(
            $stmt_master,
            "ssssssssssssi",
            $nama_pelanggan,
            $alamat,
            $status_pelanggan,
            $jenis_sampel,
            $keterangan_sampel,
            $nama_pengirim,
            $no_analisa,
            $wilayah,
            $tanggal_pengambilan,
            $tanggal_pengiriman,
            $tanggal_penerimaan,
            $tanggal_pengujian,
            $id_m_hasil_uji
        );

        if (!mysqli_stmt_execute($stmt_master)) {
            throw new Exception("Gagal update data master: " . mysqli_stmt_error($stmt_master));
        }
        mysqli_stmt_close($stmt_master);

        // 2. UPDATE DETAIL HASIL UJI & HITUNG ULANG KETERANGAN
        if (!empty($hasil_uji_details)) {
            // Prepare statement di luar loop untuk efisiensi
            $stmt_get_kadar = mysqli_prepare($con, "SELECT kadar_maksimum FROM hasil_uji WHERE id = ? AND id_m_hasil_uji = ?");
            $stmt_update_detail = mysqli_prepare($con, "UPDATE hasil_uji SET hasil = ?, status = ?, keterangan = ? WHERE id = ?");

            foreach ($hasil_uji_details as $id_hasil_uji => $hasil_value) {
                $id_hasil_uji_clean = intval($id_hasil_uji);

                if ($id_hasil_uji_clean > 0) {
                    // A. Ambil kadar maksimum lama dari DB untuk validasi ulang
                    mysqli_stmt_bind_param($stmt_get_kadar, "ii", $id_hasil_uji_clean, $id_m_hasil_uji);
                    mysqli_stmt_execute($stmt_get_kadar);
                    $result_kadar = mysqli_stmt_get_result($stmt_get_kadar);
                    $param_data = mysqli_fetch_assoc($result_kadar);

                    if ($param_data) {
                        $kadar_maksimum = $param_data['kadar_maksimum'];

                        // B. Hitung Keterangan Baru (Memenuhi/Tidak)
                        $keterangan_baru = cekKepatuhan($hasil_value, $kadar_maksimum);

                        // C. Update Data Detail
                        mysqli_stmt_bind_param($stmt_update_detail, "sssi", $hasil_value, $global_status_param, $keterangan_baru, $id_hasil_uji_clean);
                        if (!mysqli_stmt_execute($stmt_update_detail)) {
                            throw new Exception("Gagal update detail ID $id_hasil_uji_clean");
                        }
                    }
                }
            }
            mysqli_stmt_close($stmt_get_kadar);
            mysqli_stmt_close($stmt_update_detail);
        }

        mysqli_commit($con);
        header("Location: fisika_kimia.php?pesan=sukses_edit");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($con);
        error_log("Error Edit All Fisika: " . $e->getMessage());
        header("Location: fisika_kimia.php?pesan=gagal&error_msg=" . urlencode("Terjadi kesalahan sistem."));
        exit();
    }
} else {
    header("Location: fisika_kimia.php?pesan=gagal&error_msg=Invalid Request");
    exit();
}

if (isset($con)) {
    mysqli_close($con);
}
