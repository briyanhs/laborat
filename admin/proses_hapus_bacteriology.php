<?php
// admin/proses_hapus_bacteriology.php

include '../database/database.php';
include '../config.php';

// 1. Konfigurasi Session Aman
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// 2. Cek Login (WAJIB)
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

// 3. Cek CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Akses Ditolak: Token CSRF tidak valid.");
}

// 4. Validasi Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_m_hasil_uji'])) {

    $id_m_hasil_uji = intval($_POST['id_m_hasil_uji']);

    if ($id_m_hasil_uji <= 0) {
        header("location:../admin/bacteriology.php?pesan=gagal&error_msg=" . urlencode("ID Data tidak valid."));
        exit();
    }

    // Mulai Transaksi
    mysqli_begin_transaction($con);

    try {
        // A. Hapus Data Detail (Child)
        $query_delete_detail = "DELETE FROM hasil_uji_bacteriology WHERE id_m_hasil_uji = ?";
        $stmt_detail = mysqli_prepare($con, $query_delete_detail);
        
        if ($stmt_detail) {
            mysqli_stmt_bind_param($stmt_detail, 'i', $id_m_hasil_uji);
            if (!mysqli_stmt_execute($stmt_detail)) {
                throw new Exception("Gagal menghapus data detail: " . mysqli_stmt_error($stmt_detail));
            }
            mysqli_stmt_close($stmt_detail);
        } else {
            throw new Exception("Prepare statement detail gagal: " . mysqli_error($con));
        }

        // B. Hapus Data Log Verifikasi (Opsional: Membersihkan sampah log jika ada)
        // Jika Anda ingin log tetap ada (history), hapus blok B ini.
        // Namun biasanya jika master dihapus, log terkait juga dihapus atau di-set NULL.
        $query_delete_log = "DELETE FROM log_verifikasi WHERE id_hasil_uji = ? AND tipe_uji = 'bakteri'";
        $stmt_log = mysqli_prepare($con, $query_delete_log);
        if ($stmt_log) {
            mysqli_stmt_bind_param($stmt_log, 'i', $id_m_hasil_uji);
            mysqli_stmt_execute($stmt_log); // Tidak perlu throw error jika gagal, karena ini pelengkap
            mysqli_stmt_close($stmt_log);
        }

        // C. Hapus Data Master (Parent)
        $query_delete_master = "DELETE FROM master_hasil_uji_bacteriology WHERE id_m_hasil_uji = ?";
        $stmt_master = mysqli_prepare($con, $query_delete_master);

        if ($stmt_master) {
            mysqli_stmt_bind_param($stmt_master, 'i', $id_m_hasil_uji);
            if (!mysqli_stmt_execute($stmt_master)) {
                throw new Exception("Gagal menghapus data master: " . mysqli_stmt_error($stmt_master));
            }

            // Cek apakah ada data yang terhapus
            if (mysqli_stmt_affected_rows($stmt_master) === 0) {
                // Jika 0, berarti ID tidak ditemukan (mungkin sudah dihapus orang lain)
                throw new Exception("Data tidak ditemukan atau sudah dihapus sebelumnya.");
            }
            mysqli_stmt_close($stmt_master);
        } else {
            throw new Exception("Prepare statement master gagal: " . mysqli_error($con));
        }

        // Commit Transaksi
        mysqli_commit($con);
        header("location:../admin/bacteriology.php?pesan=sukses_hapus");
        exit();

    } catch (Exception $e) {
        // Rollback jika terjadi error
        mysqli_rollback($con);
        
        // Log error yang sebenarnya di server (untuk admin/developer)
        error_log("Error Hapus Bacteriology (ID: $id_m_hasil_uji): " . $e->getMessage());
        
        // Tampilkan pesan umum ke user (Keamanan)
        $user_msg = urlencode("Terjadi kesalahan sistem saat menghapus data.");
        header("location:../admin/bacteriology.php?pesan=gagal&error_msg=" . $user_msg);
        exit();
    }
} else {
    // Request tidak valid
    header("location:../admin/bacteriology.php?pesan=gagal&error_msg=" . urlencode("Permintaan tidak valid."));
    exit();
}

if (isset($con)) {
    mysqli_close($con);
}
?>