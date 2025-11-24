<?php
include '../database/database.php';
include '../config.php';
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id_parameter'];
    $nama = $_POST['nama_parameter'];
    $satuan = $_POST['satuan'];
    $baku = $_POST['nilai_baku_mutu'];
    $metode = $_POST['metode_uji'];

    if (!empty($id)) {
        $sql = "UPDATE parameter_uji_bacteriology SET nama_parameter=?, satuan=?, nilai_baku_mutu=?, metode_uji=? WHERE id_parameter=?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "ssssi", $nama, $satuan, $baku, $metode, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            header("location: pengaturan.php?pesan=sukses_edit");
        } else {
            header("location: pengaturan.php?pesan=gagal&error_msg=" . urlencode(mysqli_error($con)));
        }
        mysqli_stmt_close($stmt);
    }
}
mysqli_close($con);
?>