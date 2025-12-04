<?php
// admin/proses_verifikasi.php

include '../database/database.php';
include '../config.php';

// 1. Konfigurasi Session Aman
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// 2. Cek Login & Level
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['level'] != "User") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 3. Cek CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("location: halaman_user.php?pesan=gagal&error_msg=" . urlencode("Token keamanan tidak valid."));
        exit();
    }

    // 4. Validasi Input
    $id_m_hasil_uji = isset($_POST['id_m_hasil_uji']) ? intval($_POST['id_m_hasil_uji']) : 0;
    $tipe           = $_POST['tipe'] ?? '';
    $user_id        = $_SESSION['user_id'];

    // Whitelist tipe untuk keamanan
    if (!in_array($tipe, ['fisika', 'bakteri'])) {
        header("location: halaman_user.php?pesan=gagal&error_msg=" . urlencode("Tipe data tidak valid."));
        exit();
    }

    $master_table = ($tipe == 'fisika') ? 'master_hasil_uji' : 'master_hasil_uji_bacteriology';

    // Mulai Transaksi agar aman
    mysqli_begin_transaction($con);

    try {
        // A. Cek Duplicate Manual (Pengganti Unique Key di Database)
        // Kita cek dulu apakah user ini sudah memverifikasi dokumen ini sebelumnya?
        $query_check_dup = "SELECT id FROM log_verifikasi WHERE id_hasil_uji = ? AND tipe_uji = ? AND id_user_verifier = ?";
        $stmt_dup = mysqli_prepare($con, $query_check_dup);
        mysqli_stmt_bind_param($stmt_dup, "isi", $id_m_hasil_uji, $tipe, $user_id);
        mysqli_stmt_execute($stmt_dup);
        mysqli_stmt_store_result($stmt_dup);

        if (mysqli_stmt_num_rows($stmt_dup) > 0) {
            // Jika sudah ada datanya, batalkan proses
            mysqli_stmt_close($stmt_dup);
            throw new Exception("Anda sudah pernah memverifikasi data ini sebelumnya."); // Pesan khusus
        }
        mysqli_stmt_close($stmt_dup);

        // B. Generate Token jika belum ada (Sama seperti logika asli Anda)
        $query_token = "SELECT verification_token FROM $master_table WHERE id_m_hasil_uji = ?";
        $stmt_token = mysqli_prepare($con, $query_token);
        mysqli_stmt_bind_param($stmt_token, "i", $id_m_hasil_uji);
        mysqli_stmt_execute($stmt_token);
        $res_token = mysqli_stmt_get_result($stmt_token);
        $data_token = mysqli_fetch_assoc($res_token);
        mysqli_stmt_close($stmt_token);

        if (!$data_token) {
            throw new Exception("Data master tidak ditemukan.");
        }

        if (empty($data_token['verification_token'])) {
            $token = bin2hex(random_bytes(20)); // Generate token random aman
            $query_update_token = "UPDATE $master_table SET verification_token = ? WHERE id_m_hasil_uji = ?";
            $stmt_update = mysqli_prepare($con, $query_update_token);
            mysqli_stmt_bind_param($stmt_update, "si", $token, $id_m_hasil_uji);
            if (!mysqli_stmt_execute($stmt_update)) {
                throw new Exception("Gagal membuat token verifikasi.");
            }
            mysqli_stmt_close($stmt_update);
        }

        // C. Insert ke Log Verifikasi (SESUAI STRUKTUR ASLI ANDA)
        // Tanpa kolom 'tanggal_verifikasi', database akan otomatis mengisi jika ada default timestamp, atau kosong jika tidak.
        $query_insert = "INSERT INTO log_verifikasi (id_hasil_uji, tipe_uji, id_user_verifier) VALUES (?, ?, ?)";

        $stmt_insert = mysqli_prepare($con, $query_insert);
        mysqli_stmt_bind_param($stmt_insert, "isi", $id_m_hasil_uji, $tipe, $user_id);

        if (!mysqli_stmt_execute($stmt_insert)) {
            throw new Exception("Gagal menyimpan data verifikasi.");
        }
        mysqli_stmt_close($stmt_insert);

        // Commit Transaksi
        mysqli_commit($con);
        header("location: halaman_user.php?pesan=verif_sukses");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($con);

        // Cek pesan error untuk menentukan redirect
        if ($e->getMessage() == "Anda sudah pernah memverifikasi data ini sebelumnya.") {
            header("location: halaman_user.php?pesan=verif_gagal"); // Redirect ke pesan "Sudah Verifikasi"
        } else {
            error_log("Error Verifikasi: " . $e->getMessage()); // Log error asli di server
            header("location: halaman_user.php?pesan=gagal&error_msg=" . urlencode("Gagal memproses verifikasi."));
        }
        exit();
    }
} else {
    header("location: halaman_user.php");
    exit();
}

if (isset($con)) {
    mysqli_close($con);
}
