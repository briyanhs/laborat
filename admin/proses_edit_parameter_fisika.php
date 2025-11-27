<?php
include '../database/database.php';
include '../config.php';
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // === CEK CSRF TOKEN ===
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Token CSRF tidak valid! Akses ditolak.");
    }
    // ======================
    $id = $_POST['id_parameter'];
    $nama = $_POST['nama_parameter'];
    $satuan = $_POST['satuan'];
    $kadar = $_POST['kadar_maksimum'];
    $metode = $_POST['metode_uji'];
    $kategori = $_POST['kategori'];

    if (!empty($id)) {
        $sql = "UPDATE parameter_uji SET nama_parameter=?, satuan=?, kadar_maksimum=?, metode_uji=?, kategori=? WHERE id_parameter=?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "sssssi", $nama, $satuan, $kadar, $metode, $kategori, $id);

        if (mysqli_stmt_execute($stmt)) {
            header("location: pengaturan.php?pesan=sukses_edit");
        } else {
            header("location: pengaturan.php?pesan=gagal&error_msg=" . urlencode(mysqli_error($con)));
        }
        mysqli_stmt_close($stmt);
    }
}
mysqli_close($con);
