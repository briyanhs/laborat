<?php
// admin/proses_hapus_master.php

include '../database/database.php';
include '../config.php';
session_start();

// 1. Cek CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Token CSRF tidak valid! Akses ditolak.");
}

if ($_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($con) || !$con) {
        header("Location: fisika_kimia.php?pesan=gagal&error=db_connect_failed");
        exit();
    }

    $id_m_hasil_uji = isset($_POST['id_m_hasil_uji']) ? intval($_POST['id_m_hasil_uji']) : 0;

    if (empty($id_m_hasil_uji)) {
        header("Location: fisika_kimia.php?pesan=gagal&error=id_not_provided");
        exit();
    }

    // Mulai transaksi
    mysqli_begin_transaction($con);

    try {
        // 2. Hapus detail (Pakai Prepared Statement)
        $stmt_detail = mysqli_prepare($con, "DELETE FROM hasil_uji WHERE id_m_hasil_uji = ?");
        mysqli_stmt_bind_param($stmt_detail, "i", $id_m_hasil_uji);
        if (!mysqli_stmt_execute($stmt_detail)) {
            throw new Exception("Gagal menghapus detail: " . mysqli_stmt_error($stmt_detail));
        }
        mysqli_stmt_close($stmt_detail);

        // 3. Hapus master (Pakai Prepared Statement)
        $stmt_master = mysqli_prepare($con, "DELETE FROM master_hasil_uji WHERE id_m_hasil_uji = ?");
        mysqli_stmt_bind_param($stmt_master, "i", $id_m_hasil_uji);
        if (!mysqli_stmt_execute($stmt_master)) {
            throw new Exception("Gagal menghapus master: " . mysqli_stmt_error($stmt_master));
        }
        mysqli_stmt_close($stmt_master);

        mysqli_commit($con);
        header("Location: fisika_kimia.php?pesan=sukses_hapus");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($con);
        error_log("Proses hapus master gagal: " . $e->getMessage());
        header("Location: fisika_kimia.php?pesan=gagal&error_msg=" . urlencode($e->getMessage()));
        exit();
    }
}
