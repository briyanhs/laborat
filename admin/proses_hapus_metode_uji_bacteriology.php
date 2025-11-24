<?php
include '../database/database.php';
include '../config.php';
session_start(); // Pastikan session_start() ada di awal, sebelum output apapun
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    // Redirect ke halaman login jika tidak ada sesi yang valid
    header("location:../index.php?pesan=belum_login");
    exit();
}

if (!isset($_POST['id_metode_uji_bacteriology'])) {
    header("Location: " . BASE_URL . "admin/pengaturan.php?pesan=gagal&error_msg=" . urlencode("ID data tidak ditemukan."));
    exit();
}

$id_metode_uji_bacteriology = mysqli_real_escape_string($con, $_POST['id_metode_uji_bacteriology']);

// Memulai transaksi
mysqli_begin_transaction($con);

try {
    // Hapus data dari tabel metode_uji_bacteriology
    $query_detail = "DELETE FROM metode_uji_bacteriology WHERE id_metode_uji_bacteriology = ?";
    $stmt_detail = mysqli_prepare($con, $query_detail);
    if ($stmt_detail === false) {
        throw new Exception(mysqli_error($con));
    }
    mysqli_stmt_bind_param($stmt_detail, 'i', $id_metode_uji_bacteriology);
    mysqli_stmt_execute($stmt_detail);

    //Periksa apakah ada baris yang benar-benar terhapus
    $affected_rows = mysqli_stmt_affected_rows($stmt_detail);
    mysqli_stmt_close($stmt_detail);

    if ($affected_rows > 0) {
        // Commit transaksi jika penghapusan berhasil
        mysqli_commit($con);
        header("Location: ". BASE_URL . "admin/pengaturan.php?pesan=sukses_hapus");
    } else {
        // Tidak ada baris yang terhapus, mungkin ID tidak ditemukan.
        // Tidak perlu rollback karena tidak ada perubahan, tapi kita kirim pesan gagal.
        throw new Exception("Data dengan ID yang diberikan tidak ditemukan.");
    }
    exit();


} catch (Exception $e) {
    // Rollback transaksi jika ada yang gagal
    mysqli_rollback($con);
    header("Location: " . BASE_URL . "admin/pengaturan.php?pesan=gagal&error_msg=" . urlencode($e->getMessage()));
    exit();
} finally {
    mysqli_close($con);
}
