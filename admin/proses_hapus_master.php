<?php
// admin/proses_hapus_master.php

include '../database/database.php';
include '../config.php';

// 1. Konfigurasi Session Aman
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// 2. Cek CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Token CSRF tidak valid! Akses ditolak.");
}

// 3. Cek Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($con) || !$con) {
        header("Location: fisika_kimia.php?pesan=gagal&error_msg=db_connect_failed");
        exit();
    }

    $id_m_hasil_uji = isset($_POST['id_m_hasil_uji']) ? intval($_POST['id_m_hasil_uji']) : 0;

    if ($id_m_hasil_uji <= 0) {
        header("Location: fisika_kimia.php?pesan=gagal&error_msg=" . urlencode("ID tidak valid."));
        exit();
    }

    // Mulai transaksi
    mysqli_begin_transaction($con);

    try {
        // A. Hapus Log Verifikasi (Pembersihan Data)
        $stmt_log = mysqli_prepare($con, "DELETE FROM log_verifikasi WHERE id_hasil_uji = ? AND tipe_uji = 'fisika'");
        if ($stmt_log) {
            mysqli_stmt_bind_param($stmt_log, "i", $id_m_hasil_uji);
            mysqli_stmt_execute($stmt_log); // Tidak perlu throw error jika gagal (opsional)
            mysqli_stmt_close($stmt_log);
        }

        // B. Hapus Data Detail (Child)
        $stmt_detail = mysqli_prepare($con, "DELETE FROM hasil_uji WHERE id_m_hasil_uji = ?");
        if ($stmt_detail) {
            mysqli_stmt_bind_param($stmt_detail, "i", $id_m_hasil_uji);
            if (!mysqli_stmt_execute($stmt_detail)) {
                throw new Exception("Gagal menghapus detail: " . mysqli_stmt_error($stmt_detail));
            }
            mysqli_stmt_close($stmt_detail);
        } else {
            throw new Exception("Prepare detail gagal.");
        }

        // C. Hapus Data Master (Parent)
        $stmt_master = mysqli_prepare($con, "DELETE FROM master_hasil_uji WHERE id_m_hasil_uji = ?");
        if ($stmt_master) {
            mysqli_stmt_bind_param($stmt_master, "i", $id_m_hasil_uji);
            if (!mysqli_stmt_execute($stmt_master)) {
                throw new Exception("Gagal menghapus master: " . mysqli_stmt_error($stmt_master));
            }

            // Cek apakah ada baris yang benar-benar terhapus
            if (mysqli_stmt_affected_rows($stmt_master) === 0) {
                throw new Exception("Data tidak ditemukan atau sudah dihapus sebelumnya.");
            }
            mysqli_stmt_close($stmt_master);
        } else {
            throw new Exception("Prepare master gagal.");
        }

        // Commit
        mysqli_commit($con);
        header("Location: fisika_kimia.php?pesan=sukses_hapus");
        exit();

    } catch (Exception $e) {
        // Rollback
        mysqli_rollback($con);
        
        // Log Error (Hanya untuk Admin/Developer)
        error_log("Error Hapus Master Fisika (ID: $id_m_hasil_uji): " . $e->getMessage());
        
        // Redirect dengan pesan umum (Keamanan)
        $user_msg = urlencode("Terjadi kesalahan sistem saat menghapus data.");
        header("Location: fisika_kimia.php?pesan=gagal&error_msg=" . $user_msg);
        exit();
    }
} else {
    header("Location: fisika_kimia.php?pesan=gagal&error_msg=invalid_request");
    exit();
}

if (isset($con)) {
    mysqli_close($con);
}
?>