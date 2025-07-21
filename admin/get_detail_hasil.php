<?php
// Ini adalah file lab/get_detail_hasil.php

// Aktifkan pelaporan error untuk debugging (HAPUS di PRODUKSI)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/detail_hasil_error.log');

include '../database/database.php'; // Sesuaikan path jika berbeda
include '../config.php';

// Pastikan koneksi $con tersedia
if (!isset($con) || !$con) {
    echo '<p class="text-danger">Error: Koneksi database tidak tersedia.</p>';
    exit;
}

// Cek apakah ada data id_m_hasil_uji yang dikirimkan via POST
if (isset($_POST['id_m_hasil_uji'])) {
    $id_m_hasil_uji = intval($_POST['id_m_hasil_uji']); // Pastikan itu integer untuk keamanan

    // Query untuk mengambil parameter berdasarkan id_m_hasil_uji dari tabel hasil_uji
    $query = "SELECT nama_parameter, satuan, kadar_maksimum, metode_uji, kategori, hasil, status
              FROM hasil_uji
              WHERE id_m_hasil_uji = $id_m_hasil_uji
              ORDER BY nama_parameter ASC"; // Urutkan parameter agar rapi

    $result = mysqli_query($con, $query);

    if (!$result) {
        echo '<p class="text-danger">Error fetching detail results: ' . mysqli_error($con) . '</p>';
        exit;
    }

    // Cek apakah query berhasil dan ada baris data yang ditemukan
    if (mysqli_num_rows($result) > 0) {
        echo '<table class="table table-bordered table-sm">';
        echo '<thead class="table-light">';
        echo '<tr>';
        echo '<th>No</th>';
        echo '<th>Parameter</th>';
        echo '<th>Satuan</th>';
        echo '<th>Kadar Maksimum</th>';
        echo '<th>Metode</th>';
        echo '<th>Kategori</th>';
        echo '<th>Hasil Uji</th>';
        echo '<th>Status</th>'; // Tambahkan kolom status
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        $no = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . $no++ . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_parameter']) . '</td>';
            echo '<td>' . htmlspecialchars($row['satuan']) . '</td>';
            echo '<td>' . htmlspecialchars($row['kadar_maksimum']) . '</td>';
            echo '<td>' . htmlspecialchars($row['metode_uji']) . '</td>';
            echo '<td>' . htmlspecialchars($row['kategori']) . '</td>';
            echo '<td>' . htmlspecialchars($row['hasil']) . '</td>'; // Menampilkan hasil uji yang sudah disimpan
            echo '<td>' . htmlspecialchars($row['status']) . '</td>'; // Menampilkan status
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p class="text-muted">Tidak ada detail hasil uji untuk No Lab ini.</p>';
    }
} else {
    echo '<p class="text-danger">ID Master Hasil Uji tidak diterima. Terjadi kesalahan.</p>';
}
?>