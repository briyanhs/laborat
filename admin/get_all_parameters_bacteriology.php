<?php
// admin/get_all_parameters_bacteriology.php

// Pastikan path ini benar dari lokasi file ini
include '../database/database.php';
include '../config.php';

header('Content-Type: application/json');

// Pastikan koneksi berhasil
if (!isset($con) || !$con) {
    // Jika koneksi gagal, kirim format JSON kosong yang benar
    echo json_encode(['results' => []]);
    exit();
}

// 1. Ambil search term dari Select2 ('q' adalah nama parameter defaultnya)
$search_term = $_GET['q'] ?? '';

// 2. Gunakan placeholder '?' untuk keamanan (mencegah SQL Injection)
$query = "SELECT id_parameter, nama_parameter, satuan, nilai_baku_mutu, metode_uji 
          FROM parameter_uji_bacteriology 
          WHERE nama_parameter LIKE ? 
          ORDER BY nama_parameter ASC";

$stmt = mysqli_prepare($con, $query);
$response_data = [];

if ($stmt) {
    // 3. Tambahkan wildcard '%' ke variabel sebelum di-bind
    $search_param = '%' . $search_term . '%';
    mysqli_stmt_bind_param($stmt, "s", $search_param);

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // 4. Buat array baru untuk setiap baris dengan format yang dibutuhkan Select2
            $response_data[] = [
                'id'   => (string)$row['id_parameter'], // WAJIB: key 'id' untuk nilai
                'text' => $row['nama_parameter'],     // WAJIB: key 'text' untuk tampilan

                // Data tambahan yang kita butuhkan nanti di JavaScript
                'id_parameter'    => (string)$row['id_parameter'],
                'nama_parameter'  => $row['nama_parameter'],
                'satuan'          => $row['satuan'],
                'nilai_baku_mutu' => $row['nilai_baku_mutu'],
                'metode_uji'      => $row['metode_uji']
            ];
        }
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($con);

// 5. Bungkus hasil akhir dalam objek dengan key 'results'
echo json_encode(['results' => $response_data]);
