<?php
include '../database/database.php';
include '../config.php';

// Pastikan skrip ini diakses melalui metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validasi data yang diterima dari formulir
    $id = isset($_POST['id_metode_uji_bacteriology']) ? $_POST['id_metode_uji_bacteriology'] : '';
    $nama_metode_uji = isset($_POST['nama_metode_uji']) ? $_POST['nama_metode_uji'] : '';
    $kategori = isset($_POST['kategori']) ? $_POST['kategori'] : '';

    // Periksa apakah semua data yang diperlukan tersedia
    if (!empty($id) && !empty($nama_metode_uji) && !empty($kategori)) {
        // Gunakan prepared statement untuk mencegah SQL injection
        $sql = "UPDATE metode_uji_bacteriology SET nama_metode_uji = ?, kategori = ? WHERE id_metode_uji_bacteriology = ?";
        
        // Persiapan statement
        $stmt = mysqli_prepare($con, $sql);

        if ($stmt) {
            // Bind parameter ke statement
            // 'ssi' berarti: string, string, integer
            mysqli_stmt_bind_param($stmt, "ssi", $nama_metode_uji, $kategori, $id);

            // Eksekusi statement
            if (mysqli_stmt_execute($stmt)) {
                // Berhasil diperbarui, alihkan kembali ke halaman pengaturan
                header("location: pengaturan.php?pesan=sukses_edit");
                exit();
            } else {
                // Gagal eksekusi statement
                header("location: pengaturan.php?pesan=gagal&error_msg=" . urlencode(mysqli_stmt_error($stmt)));
                exit();
            }

            // Tutup statement
            mysqli_stmt_close($stmt);
        } else {
            // Gagal mempersiapkan statement
            header("location: pengaturan.php?pesan=gagal&error_msg=" . urlencode(mysqli_error($con)));
            exit();
        }
    } else {
        // Data tidak lengkap
        header("location: pengaturan.php?pesan=gagal&error_msg=" . urlencode("Data tidak lengkap."));
        exit();
    }
} else {
    // Akses langsung ke skrip tidak diperbolehkan
    header("location: pengaturan.php");
    exit();
}
?>