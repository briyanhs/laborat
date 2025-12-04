<?php
// admin/get_all_parameters_bacteriology.php

// 1. Mulai Output Buffering untuk mencegah "whitespace injection" yang merusak JSON
ob_start();

include '../database/database.php';
include '../config.php';

session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    // Jangan echo teks biasa, tetap kirim JSON error atau exit silent
    ob_end_clean();
    http_response_code(403);
    exit();
}

// Pastikan koneksi berhasil
if (!isset($con) || !$con) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['results' => []]);
    exit();
}

// 2. Set Charset ke UTF-8 (PENTING untuk simbol fisika/kimia seperti °, µ, ³, dll)
mysqli_set_charset($con, "utf8mb4");

// Ambil search term
$search_term = $_GET['q'] ?? '';

// Query dengan LIMIT untuk performa
$query = "SELECT id_parameter, nama_parameter, satuan, nilai_baku_mutu, metode_uji 
          FROM parameter_uji_bacteriology 
          WHERE nama_parameter LIKE ? 
          ORDER BY nama_parameter ASC 
          LIMIT 50";

$stmt = mysqli_prepare($con, $query);
$response_data = [];

if ($stmt) {
    $search_param = '%' . $search_term . '%';
    mysqli_stmt_bind_param($stmt, "s", $search_param);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $response_data[] = [
                'id' => (string)$row['id_parameter'],
                'text' => $row['nama_parameter'], // Text yang muncul di dropdown

                // Data tambahan untuk autofill via Javascript
                'id_parameter' => (string)$row['id_parameter'],
                'nama_parameter' => $row['nama_parameter'],
                'satuan' => $row['satuan'],
                'nilai_baku_mutu' => $row['nilai_baku_mutu'],
                'metode_uji' => $row['metode_uji']
            ];
        }
    }
    mysqli_stmt_close($stmt);
} else {
    // Log error di server jika query salah
    error_log("Database Error: " . mysqli_error($con));
}

mysqli_close($con);

// 3. Bersihkan buffer sebelum kirim output
ob_end_clean();

// 4. Set Header dan encoding JSON
header('Content-Type: application/json; charset=utf-8');

// Gunakan JSON_UNESCAPED_UNICODE agar simbol tidak berubah jadi kode aneh (\u00b0)
echo json_encode(['results' => $response_data], JSON_UNESCAPED_UNICODE);
