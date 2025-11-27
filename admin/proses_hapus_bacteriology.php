<?php
// admin/proses_hapus_bacteriology.php

include '../database/database.php'; // Sesuaikan path jika perlu
include '../config.php'; //
session_start(); // Jika perlu validasi login

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Token CSRF tidak valid! Akses ditolak.");
}

// Pastikan request adalah POST dan ID Master ada
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_m_hasil_uji'])) {

    $id_m_hasil_uji = intval($_POST['id_m_hasil_uji']);

    mysqli_begin_transaction($con); // Mulai transaksi untuk memastikan keduanya terhapus atau tidak sama sekali

    try {
        // 1. Hapus Data Detail Terlebih Dahulu
        // Ini penting jika Anda tidak menggunakan ON DELETE CASCADE di foreign key
        $query_delete_detail = "DELETE FROM hasil_uji_bacteriology WHERE id_m_hasil_uji = ?";
        $stmt_detail = mysqli_prepare($con, $query_delete_detail);
        if (!$stmt_detail) {
            throw new Exception("Prepare statement delete detail gagal: " . mysqli_error($con));
        }

        mysqli_stmt_bind_param($stmt_detail, 'i', $id_m_hasil_uji);
        if (!mysqli_stmt_execute($stmt_detail)) {
            throw new Exception("Eksekusi delete detail gagal: " . mysqli_stmt_error($stmt_detail));
        }
        mysqli_stmt_close($stmt_detail);

        // 2. Hapus Data Master
        $query_delete_master = "DELETE FROM master_hasil_uji_bacteriology WHERE id_m_hasil_uji = ?";
        $stmt_master = mysqli_prepare($con, $query_delete_master);
        if (!$stmt_master) {
            throw new Exception("Prepare statement delete master gagal: " . mysqli_error($con));
        }

        mysqli_stmt_bind_param($stmt_master, 'i', $id_m_hasil_uji);
        if (!mysqli_stmt_execute($stmt_master)) {
            throw new Exception("Eksekusi delete master gagal: " . mysqli_stmt_error($stmt_master));
        }

        // Periksa apakah baris benar-benar terhapus (opsional tapi bagus)
        $affected_rows = mysqli_stmt_affected_rows($stmt_master);
        mysqli_stmt_close($stmt_master);

        if ($affected_rows > 0) {
            // Jika berhasil
            mysqli_commit($con);
            header("location:../admin/bacteriology.php?pesan=sukses_hapus");
            exit();
        } else {
            // Jika ID tidak ditemukan (mungkin sudah dihapus sebelumnya)
            throw new Exception("Data master dengan ID $id_m_hasil_uji tidak ditemukan untuk dihapus.");
        }
    } catch (Exception $e) {
        // Jika ada error
        mysqli_rollback($con);
        $error_msg = urlencode($e->getMessage());
        error_log("Error proses_hapus_bacteriology: " . $e->getMessage()); // Log error
        header("location:../admin/bacteriology.php?pesan=gagal&error_msg=" . $error_msg);
        exit();
    }
} else {
    // Jika request tidak valid atau ID tidak ada
    header("location:../admin/bacteriology.php?pesan=gagal&error_msg=" . urlencode("Permintaan hapus tidak valid."));
    exit();
}

// Tutup koneksi jika masih terbuka
if (isset($con) && mysqli_ping($con)) {
    mysqli_close($con);
}
