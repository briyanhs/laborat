<?php
include '../database/database.php';
session_start();

// Keamanan: Pastikan user login dan levelnya "User"
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['level'] != "User") {
    die("Akses ditolak.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_m_hasil_uji = $_POST['id_m_hasil_uji'];
    $tipe = $_POST['tipe'];
    $user_id = $_SESSION['user_id']; // Menggunakan id_user sesuai database Anda

    // Tentukan tabel master untuk mengecek token
    $master_table = ($tipe == 'fisika') ? 'master_hasil_uji' : 'master_hasil_uji_bacteriology';
    $id_column = 'id_m_hasil_uji';

    // 1. Cek apakah token sudah ada di master tabel?
    $stmt_check = mysqli_prepare($con, "SELECT verification_token FROM $master_table WHERE $id_column = ?");
    mysqli_stmt_bind_param($stmt_check, "i", $id_m_hasil_uji);
    mysqli_stmt_execute($stmt_check);
    $result = mysqli_stmt_get_result($stmt_check);
    $data = mysqli_fetch_assoc($result);

    // 2. Jika token BELUM ADA (ini verifikasi pertama), buat token
    if (empty($data['verification_token'])) {
        $token = bin2hex(random_bytes(20)); // Token acak 40 karakter
        $stmt_update = mysqli_prepare($con, "UPDATE $master_table SET verification_token = ? WHERE $id_column = ?");
        mysqli_stmt_bind_param($stmt_update, "si", $token, $id_m_hasil_uji);
        mysqli_stmt_execute($stmt_update);
    }

    // 3. Masukkan log verifikasi. 
    // Jika user sudah pernah verifikasi ini, query akan gagal (karena UNIQUE KEY)
    $query_log = "INSERT INTO log_verifikasi (id_hasil_uji, tipe_uji, id_user_verifier) 
                  VALUES (?, ?, ?)";
    
    $stmt_log = mysqli_prepare($con, $query_log);
    mysqli_stmt_bind_param($stmt_log, "isi", $id_m_hasil_uji, $tipe, $user_id);
    
    if (mysqli_stmt_execute($stmt_log)) {
        // Berhasil mencatat verifikasi
        header("location: halaman_user.php?pesan=verif_sukses");
    } else {
        // Gagal (kemungkinan karena duplikat / sudah pernah verifikasi)
        header("location: halaman_user.php?pesan=verif_gagal");
    }
    
    mysqli_close($con);
}
?>