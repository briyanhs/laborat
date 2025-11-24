<?php
// lab/proses_hapus_master.php

// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/hapus_master_error.log');

include '../database/database.php';
include '../config.php';
session_start();

if ($_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pastikan koneksi database tersedia
    if (!isset($con) || !$con) {
        header("Location: fisika_kimia.php?pesan=gagal&error=db_connect_failed");
        exit();
    }

    // Ambil id_m_hasil_uji dari POST
    $id_m_hasil_uji = isset($_POST['id_m_hasil_uji']) ? intval($_POST['id_m_hasil_uji']) : 0;

    // Basic validation
    if (empty($id_m_hasil_uji)) {
        header("Location: fisika_kimia.php?pesan=gagal&error=id_not_provided");
        exit();
    }

    // Memulai transaksi
    mysqli_begin_transaction($con);

    try {
        // Hapus data dari tabel hasil_uji terlebih dahulu (karena ada foreign key constraint)
        $query_delete_detail = "DELETE FROM hasil_uji WHERE id_m_hasil_uji = $id_m_hasil_uji";
        if (!mysqli_query($con, $query_delete_detail)) {
            throw new Exception("Error deleting detail results: " . mysqli_error($con));
        }

        // Kemudian hapus data dari master_hasil_uji
        $query_delete_master = "DELETE FROM master_hasil_uji WHERE id_m_hasil_uji = $id_m_hasil_uji";
        if (!mysqli_query($con, $query_delete_master)) {
            throw new Exception("Error deleting master result: " . mysqli_error($con));
        }

        // Commit transaksi jika kedua query berhasil
        mysqli_commit($con);
        header("Location: fisika_kimia.php?pesan=sukses_hapus");
        exit();

    } catch (Exception $e) {
        // Rollback transaksi jika ada error
        mysqli_rollback($con);
        error_log("Proses hapus master data gagal: " . $e->getMessage());
        header("Location: fisika_kimia.php?pesan=gagal&error_msg=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Jika bukan metode POST, redirect kembali
    header("Location: fisika_kimia.php?pesan=gagal&error=invalid_request_method");
    exit();
}
?>