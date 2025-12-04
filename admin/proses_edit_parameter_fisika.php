<?php
// admin/proses_edit_parameter_fisika.php

include '../database/database.php';
include '../config.php';

// Konfigurasi Session Aman
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// Cek Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Cek CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Akses ditolak: Token CSRF tidak valid.");
    }

    // 2. Ambil & Sanitasi Input (Trim spasi)
    $id       = isset($_POST['id_parameter']) ? intval($_POST['id_parameter']) : 0;
    $nama     = isset($_POST['nama_parameter']) ? trim($_POST['nama_parameter']) : '';
    $satuan   = isset($_POST['satuan']) ? trim($_POST['satuan']) : '';
    $kadar    = isset($_POST['kadar_maksimum']) ? trim($_POST['kadar_maksimum']) : '';
    $metode   = isset($_POST['metode_uji']) ? trim($_POST['metode_uji']) : '';
    $kategori = isset($_POST['kategori']) ? trim($_POST['kategori']) : '';

    // 3. Validasi Input Dasar
    if ($id <= 0 || empty($nama) || empty($kategori)) {
        header("location: pengaturan.php?pesan=gagal&error_msg=" . urlencode("Data tidak lengkap (ID, Nama, atau Kategori kosong)."));
        exit();
    }

    // 4. Proses Update Database
    $sql = "UPDATE parameter_uji SET 
            nama_parameter = ?, 
            satuan = ?, 
            kadar_maksimum = ?, 
            metode_uji = ?, 
            kategori = ? 
            WHERE id_parameter = ?";

    $stmt = mysqli_prepare($con, $sql);

    if ($stmt) {
        // Bind param: string, string, string, string, string, integer
        mysqli_stmt_bind_param($stmt, "sssssi", $nama, $satuan, $kadar, $metode, $kategori, $id);

        if (mysqli_stmt_execute($stmt)) {
            // Sukses
            header("location: pengaturan.php?pesan=sukses_edit");
        } else {
            // Gagal Execute - Log error ke server, tampilkan pesan umum ke user
            error_log("Error Update Parameter Fisika/Kimia: " . mysqli_stmt_error($stmt));
            header("location: pengaturan.php?pesan=gagal&error_msg=" . urlencode("Gagal memperbarui data database."));
        }
        mysqli_stmt_close($stmt);
    } else {
        // Gagal Prepare
        error_log("Error Prepare Fisika/Kimia: " . mysqli_error($con));
        header("location: pengaturan.php?pesan=gagal&error_msg=" . urlencode("Terjadi kesalahan sistem."));
    }

} else {
    // Jika akses bukan POST
    header("location: pengaturan.php");
    exit();
}

if (isset($con)) {
    mysqli_close($con);
}
?>