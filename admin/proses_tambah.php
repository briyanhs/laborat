<?php
// admin/proses_tambah.php

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

    // --- LOGIKA RENTANG YANG DISempurnakan ---
    // 1. Normalisasi semua jenis karakter strip menjadi strip biasa.
    $standarNormalized = str_replace(['–', '—'], '-', $standarStr);

    if (str_contains($standarNormalized, '-')) {
        $parts = explode('-', $standarNormalized);
        if (count($parts) === 2) {
            // 2. Ekstrak hanya angka dari setiap bagian, abaikan teks lain.
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
    // --- AKHIR LOGIKA RENTANG ---

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
        $standarNum = (float)$standarStr;
        return $hasilNum <= $standarNum ? 'Memenuhi' : 'Tidak Memenuhi';
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

    $nama_pelanggan      = mysqli_real_escape_string($con, $_POST['nama_pelanggan'] ?? '');
    $alamat              = mysqli_real_escape_string($con, $_POST['alamat'] ?? '');
    $status_pelanggan    = mysqli_real_escape_string($con, $_POST['status_pelanggan'] ?? '');
    $jenis_sampel        = mysqli_real_escape_string($con, $_POST['jenis_sampel'] ?? '');
    $keterangan_sampel   = mysqli_real_escape_string($con, $_POST['keterangan_sampel'] ?? '');
    $nama_pengirim       = mysqli_real_escape_string($con, $_POST['nama_pengirim'] ?? '');
    $no_analisa          = mysqli_real_escape_string($con, $_POST['no_analisa'] ?? '');
    $wilayah             = mysqli_real_escape_string($con, $_POST['wilayah'] ?? '');
    $tanggal_pengambilan = mysqli_real_escape_string($con, $_POST['tanggal_pengambilan'] ?? '');
    $tanggal_pengiriman  = mysqli_real_escape_string($con, $_POST['tanggal_pengiriman'] ?? '');
    $tanggal_penerimaan  = mysqli_real_escape_string($con, $_POST['tanggal_penerimaan'] ?? '');
    $tanggal_pengujian   = mysqli_real_escape_string($con, $_POST['tanggal_pengujian'] ?? '');
    $status              = mysqli_real_escape_string($con, $_POST['status'] ?? '');

    $hasil_parameters = $_POST['hasil'] ?? [];
    $param_details_from_form = $_POST['param_details'] ?? [];

    if (empty($nama_pelanggan) || empty($no_analisa) || empty($hasil_parameters) || empty($param_details_from_form)) {
        header("Location: fisika_kimia.php?pesan=gagal&error=incomplete_data");
        exit();
    }

    mysqli_begin_transaction($con);

    try {
        $query_insert_master = "INSERT INTO master_hasil_uji (nama_pelanggan, alamat, status_pelanggan, jenis_sampel, keterangan_sampel, nama_pengirim, no_analisa, wilayah, tanggal_pengambilan, tanggal_pengiriman, tanggal_penerimaan, tanggal_pengujian) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_master = mysqli_prepare($con, $query_insert_master);
        mysqli_stmt_bind_param($stmt_master, "ssssssssssss", $nama_pelanggan, $alamat, $status_pelanggan, $jenis_sampel, $keterangan_sampel, $nama_pengirim, $no_analisa, $wilayah, $tanggal_pengambilan, $tanggal_pengiriman, $tanggal_penerimaan, $tanggal_pengujian);
        mysqli_stmt_execute($stmt_master);

        $id_m_hasil_uji = mysqli_insert_id($con);
        mysqli_stmt_close($stmt_master);

        if ($id_m_hasil_uji == 0) {
            throw new Exception("Gagal mendapatkan ID master setelah proses insert.");
        }

        $query_insert_hasil = "INSERT INTO hasil_uji (id_m_hasil_uji, nama_parameter, satuan, kadar_maksimum, metode_uji, kategori, hasil, status, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_hasil = mysqli_prepare($con, $query_insert_hasil);

        foreach ($hasil_parameters as $param_id => $hasil_value) {
            if (isset($param_details_from_form[$param_id])) {
                $details = $param_details_from_form[$param_id];

                $nama_parameter = $details['nama_parameter'];
                $satuan         = $details['satuan'];
                $kadar_maksimum = $details['kadar_maksimum'];
                $metode_uji     = $details['metode_uji'];
                $kategori       = $details['kategori'];

                $keterangan = cekKepatuhan($hasil_value, $kadar_maksimum);

                mysqli_stmt_bind_param(
                    $stmt_hasil,
                    "issssssss",
                    $id_m_hasil_uji,
                    $nama_parameter,
                    $satuan,
                    $kadar_maksimum,
                    $metode_uji,
                    $kategori,
                    $hasil_value,
                    $status,
                    $keterangan
                );
                mysqli_stmt_execute($stmt_hasil);
            }
        }
        mysqli_stmt_close($stmt_hasil);

        mysqli_commit($con);
        header("Location: fisika_kimia.php?pesan=sukses_tambah");
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
