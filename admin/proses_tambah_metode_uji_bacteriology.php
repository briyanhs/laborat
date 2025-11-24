<?php
include '../database/database.php';
include '../config.php';

// Pastikan request method adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "admin/pengaturan.php?pesan=gagal&error_msg=" . urlencode("Metode request tidak valid."));
    exit();
}

// Tangkap dan sanitasi data dari form
$nama_metode_uji = mysqli_real_escape_string($con, $_POST['nama_metode_uji']); // ID dari dropdown
$kategori = mysqli_real_escape_string($con, $_POST['kategori']);


// Mulai transaksi untuk memastikan kedua INSERT berhasil
mysqli_begin_transaction($con);
try {
    // 1. INSERT data ke metode uji bacteriology
    $query_master = "INSERT INTO metode_uji_bacteriology (nama_metode_uji, kategori) VALUES (?, ?)";
    $stmt_master = mysqli_prepare($con, $query_master);
    if ($stmt_master === false) {
        throw new Exception(mysqli_error($con));
    }
    mysqli_stmt_bind_param($stmt_master, 'ss', $nama_metode_uji, $kategori);
    mysqli_stmt_execute($stmt_master);
    $id_metode_uji_bacteriology = mysqli_insert_id($con);
    mysqli_stmt_close($stmt_master);

    // Commit transaksi jika semua berhasil
    mysqli_commit($con);
    header("Location: " . BASE_URL . "admin/pengaturan.php?pesan=sukses_tambah");
    exit();

} catch (Exception $e) {
    // Rollback transaksi jika ada yang gagal
    mysqli_rollback($con);
    header("Location: " . BASE_URL . "admin/pengaturan.php?pesan=gagal&error_msg=" . urlencode($e->getMessage()));
    exit();
} finally {
    mysqli_close($con);
}
?>