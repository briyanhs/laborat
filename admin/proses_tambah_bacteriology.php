<?php
// admin/proses_tambah_bacteriology.php

include '../database/database.php';
include '../config.php';

// Konfigurasi Session Aman
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// 1. Cek Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

// 2. Cek CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Akses Ditolak: Token CSRF tidak valid.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- AMBIL DATA MASTER ---
    // Gunakan null coalescing operator (??) untuk menghindari error undefined index
    $nama_pelanggan    = $_POST['nama_pelanggan'] ?? '';
    $alamat            = $_POST['alamat'] ?? '';
    $status_pelanggan  = $_POST['status_pelanggan'] ?? '';
    $jenis_sampel      = $_POST['jenis_sampel'] ?? '';
    $jenis_pengujian   = $_POST['jenis_pengujian'] ?? '';
    $keterangan_sampel = $_POST['keterangan_sampel'] ?? '';
    $nama_pengirim     = $_POST['nama_pengirim'] ?? '';
    $no_analisa        = $_POST['no_analisa'] ?? '';
    $wilayah           = $_POST['wilayah'] ?? '';
    $status_global     = $_POST['status'] ?? 'Proses';

    // --- HANDLING TANGGAL (Agar bisa NULL jika kosong) ---
    $tanggal_pengambilan = !empty($_POST['tanggal_pengambilan']) ? $_POST['tanggal_pengambilan'] : NULL;
    $tanggal_pengiriman  = !empty($_POST['tanggal_pengiriman']) ? $_POST['tanggal_pengiriman'] : NULL;
    $tanggal_penerimaan  = !empty($_POST['tanggal_penerimaan']) ? $_POST['tanggal_penerimaan'] : NULL;
    $tanggal_pengujian   = !empty($_POST['tanggal_pengujian']) ? $_POST['tanggal_pengujian'] : NULL;

    // Mulai Transaksi Database
    mysqli_begin_transaction($con);

    try {
        // ==========================================
        // 1. INSERT DATA MASTER
        // ==========================================
        $query_master = "INSERT INTO master_hasil_uji_bacteriology 
                        (nama_pelanggan, alamat, status_pelanggan, tanggal_pengambilan, tanggal_pengiriman, tanggal_penerimaan, tanggal_pengujian, nama_pengirim, jenis_sampel, jenis_pengujian, keterangan_sampel, no_analisa, wilayah, verification_token) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)";

        $stmt_master = mysqli_prepare($con, $query_master);
        if (!$stmt_master) {
            throw new Exception("Gagal prepare master: " . mysqli_error($con));
        }

        // Bind parameter (13 string 's')
        mysqli_stmt_bind_param(
            $stmt_master,
            "sssssssssssss",
            $nama_pelanggan,
            $alamat,
            $status_pelanggan,
            $tanggal_pengambilan,
            $tanggal_pengiriman,
            $tanggal_penerimaan,
            $tanggal_pengujian,
            $nama_pengirim,
            $jenis_sampel,
            $jenis_pengujian,
            $keterangan_sampel,
            $no_analisa,
            $wilayah
        );

        if (!mysqli_stmt_execute($stmt_master)) {
            throw new Exception("Gagal eksekusi master: " . mysqli_stmt_error($stmt_master));
        }

        // Ambil ID Master yang baru dibuat
        $id_m_hasil_uji = mysqli_insert_id($con);
        mysqli_stmt_close($stmt_master);

        // ==========================================
        // 2. INSERT DETAIL PARAMETER
        // ==========================================
        if (isset($_POST['hasil']) && is_array($_POST['hasil'])) {
            $hasil_analisa   = $_POST['hasil'];
            $penegasan_list  = $_POST['penegasan'] ?? [];
            $keterangan_list = $_POST['keterangan'] ?? [];
            $param_details   = $_POST['param_details'] ?? []; // Data hidden dari form

            $query_detail = "INSERT INTO hasil_uji_bacteriology 
                            (id_m_hasil_uji, nama_parameter, satuan, nilai_baku_mutu, metode_uji, hasil, penegasan, keterangan, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt_detail = mysqli_prepare($con, $query_detail);
            if (!$stmt_detail) {
                throw new Exception("Gagal prepare detail: " . mysqli_error($con));
            }

            foreach ($hasil_analisa as $id_param => $hasil) {
                // Ambil data dari hidden input berdasarkan ID Parameter
                $nama_parameter  = $param_details[$id_param]['nama_parameter'] ?? '';
                $satuan          = $param_details[$id_param]['satuan'] ?? '';
                $nilai_baku_mutu = $param_details[$id_param]['nilai_baku_mutu'] ?? '';
                $metode_uji      = $param_details[$id_param]['metode_uji'] ?? '';
                
                $penegasan       = $penegasan_list[$id_param] ?? '';
                $keterangan      = $keterangan_list[$id_param] ?? '';

                // Bind param: integer (i) dan 8 string (s)
                mysqli_stmt_bind_param(
                    $stmt_detail,
                    "issssssss",
                    $id_m_hasil_uji,
                    $nama_parameter,
                    $satuan,
                    $nilai_baku_mutu,
                    $metode_uji,
                    $hasil,
                    $penegasan,
                    $keterangan,
                    $status_global
                );

                if (!mysqli_stmt_execute($stmt_detail)) {
                    throw new Exception("Gagal simpan detail parameter (ID Param: $id_param): " . mysqli_stmt_error($stmt_detail));
                }
            }
            mysqli_stmt_close($stmt_detail);
        }

        // Jika semua sukses, Commit
        mysqli_commit($con);
        header("location:../admin/bacteriology.php?pesan=sukses_tambah");
        exit();

    } catch (Exception $e) {
        // Jika error, Rollback
        mysqli_rollback($con);
        
        // Log error ke server
        error_log("Error Tambah Bacteriology: " . $e->getMessage());
        
        // Redirect dengan pesan error
        $error_msg = urlencode("Terjadi kesalahan saat menyimpan data.");
        header("location:../admin/bacteriology.php?pesan=gagal&error_msg=" . $error_msg);
        exit();
    }
} else {
    // Akses invalid
    header("location:../admin/bacteriology.php");
    exit();
}

if (isset($con)) {
    mysqli_close($con);
}
?>