<?php
// proses_edit_all.php (VERSI PERBAIKAN FINAL)

include '../database/database.php';
include '../config.php';
session_start();

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Token CSRF tidak valid! Akses ditolak.");
}
function cekKepatuhan($hasil, $standar)
{
    if ($hasil === null || $standar === null || trim((string)$hasil) === '' || trim((string)$standar) === '') {
        return '';
    }
    $standarStr = trim((string)$standar);
    $hasilStr = trim((string)$hasil);
    if (str_contains(strtolower($standarStr), 'suhu udara')) {
        return '';
    }
    $standarStr = str_replace(',', '.', $standarStr);
    $hasilStr = str_replace(',', '.', $hasilStr);
    $hasilNum = is_numeric($hasilStr) ? (float)$hasilStr : null;
    $standarNormalized = str_replace(['–', '—'], '-', $standarStr);
    if (str_contains($standarNormalized, '-')) {
        $parts = explode('-', $standarNormalized);
        if (count($parts) === 2) {
            preg_match('/-?\d+\.?\d*/', $parts[0], $minMatch);
            preg_match('/-?\d+\.?\d*/', $parts[1], $maxMatch);
            if (!empty($minMatch) && !empty($maxMatch) && $hasilNum !== null) {
                $min = (float)$minMatch[0];
                $max = (float)$maxMatch[0];
                $epsilon = 0.000001;
                $isMemenuhi = ($hasilNum > $min - $epsilon) && ($hasilNum < $max + $epsilon);
                return $isMemenuhi ? 'Memenuhi' : 'Tidak Memenuhi';
            }
        }
    }
    if (str_starts_with($standarStr, '<')) {
        $maxStr = trim(substr($standarStr, 1));
        if (is_numeric($maxStr) && $hasilNum !== null) {
            return $hasilNum < (float)$maxStr ? 'Memenuhi' : 'Tidak Memenuhi';
        }
    }
    if (str_starts_with($standarStr, '>')) {
        $minStr = trim(substr($standarStr, 1));
        if (is_numeric($minStr) && $hasilNum !== null) {
            return $hasilNum > (float)$minStr ? 'Memenuhi' : 'Tidak Memenuhi';
        }
    }
    if ($hasilNum === null) {
        return strtolower($hasilStr) === strtolower($standarStr) ? 'Memenuhi' : 'Tidak Memenuhi';
    }
    if (is_numeric($standarStr)) {
        return $hasilNum <= (float)$standarStr ? 'Memenuhi' : 'Tidak Memenuhi';
    }
    return '';
}
// ====================================================================


if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($con) || !$con) {
        header("Location: fisika_kimia.php?pesan=gagal&error=db_connect_failed");
        exit();
    }

    // Ambil data dari form yang sudah disesuaikan dengan fisika_kimia.php
    $id_m_hasil_uji      = isset($_POST['id_m_hasil_uji']) ? intval($_POST['id_m_hasil_uji']) : 0;
    $nama_pelanggan      = $_POST['nama_pelanggan'] ?? '';
    $alamat              = $_POST['alamat'] ?? '';
    $status_pelanggan    = $_POST['status_pelanggan'] ?? '';
    $jenis_sampel        = $_POST['jenis_sampel'] ?? '';
    $keterangan_sampel   = $_POST['keterangan_sampel'] ?? '';
    $nama_pengirim       = $_POST['nama_pengirim'] ?? '';
    $no_analisa          = $_POST['no_analisa'] ?? '';
    $wilayah             = $_POST['wilayah'] ?? '';
    $tanggal_pengujian   = $_POST['tanggal_pengujian'] ?? '';
    // Tanggal lain jika diperlukan
    $tanggal_pengambilan = $_POST['tanggal_pengambilan'] ?? '';
    $tanggal_pengiriman  = $_POST['tanggal_pengiriman'] ?? '';
    $tanggal_penerimaan  = $_POST['tanggal_penerimaan'] ?? '';

    $global_status_param = $_POST['global_status_param'] ?? 'Proses';
    $hasil_uji_details   = $_POST['hasil_uji'] ?? [];

    if (empty($id_m_hasil_uji) || empty($no_analisa)) {
        header("Location: fisika_kimia.php?pesan=gagal&error=invalid_master_data");
        exit();
    }

    mysqli_begin_transaction($con);

    try {
        // 1. Update data master_hasil_uji dengan field yang BENAR
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
        mysqli_stmt_execute($stmt_master);
        mysqli_stmt_close($stmt_master);

        // 2. Update data detail hasil_uji
        if (!empty($hasil_uji_details)) {
            $stmt_get_kadar = mysqli_prepare($con, "SELECT kadar_maksimum FROM hasil_uji WHERE id = ? AND id_m_hasil_uji = ?");
            $stmt_update_detail = mysqli_prepare($con, "UPDATE hasil_uji SET hasil = ?, status = ?, keterangan = ? WHERE id = ?");

            foreach ($hasil_uji_details as $id_hasil_uji => $hasil_value) {
                $id_hasil_uji_clean = intval($id_hasil_uji);
                if ($id_hasil_uji_clean > 0 && !is_null($hasil_value)) {
                    mysqli_stmt_bind_param($stmt_get_kadar, "ii", $id_hasil_uji_clean, $id_m_hasil_uji);
                    mysqli_stmt_execute($stmt_get_kadar);
                    $result_kadar = mysqli_stmt_get_result($stmt_get_kadar);

                    if ($param_data = mysqli_fetch_assoc($result_kadar)) {
                        $kadar_maksimum = $param_data['kadar_maksimum'];
                        $keterangan_baru = cekKepatuhan($hasil_value, $kadar_maksimum);
                        mysqli_stmt_bind_param($stmt_update_detail, "sssi", $hasil_value, $global_status_param, $keterangan_baru, $id_hasil_uji_clean);
                        mysqli_stmt_execute($stmt_update_detail);
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
        header("Location: fisika_kimia.php?pesan=gagal&error_msg=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: fisika_kimia.php?pesan=gagal&error=invalid_request_method");
    exit();
}

if (isset($con)) {
    mysqli_close($con);
}
