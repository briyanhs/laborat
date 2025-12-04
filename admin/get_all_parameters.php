<?php
// admin/get_all_parameters.php

// 1. Mulai Output Buffering untuk mencegah "whitespace injection"
ob_start();

include '../database/database.php';
include '../config.php';

session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    ob_end_clean();
    http_response_code(403);
    exit();
}

// Pastikan koneksi berhasil
if (!isset($con) || !$con) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

// 2. Set Charset ke UTF-8 (Penting untuk simbol Fisika/Kimia)
mysqli_set_charset($con, "utf8mb4");

// 3. Parameter Pencarian & Pagination
$search_term = $_GET['q'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Menampilkan 20 data per scroll
$offset = ($page - 1) * $limit;

// 4. Query dengan LIMIT dan OFFSET
$query = "SELECT id_parameter, nama_parameter, satuan, kadar_maksimum, metode_uji, kategori 
          FROM parameter_uji
          WHERE nama_parameter LIKE ?
          ORDER BY nama_parameter ASC
          LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($con, $query);
$response_data = [];

if ($stmt) {
    $search_param = '%' . $search_term . '%';

    // Bind param: string (s), integer (i), integer (i)
    mysqli_stmt_bind_param($stmt, "sii", $search_param, $limit, $offset);

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Format sesuai kebutuhan Select2 dan Autofill JavaScript
            $response_data[] = [
                // Wajib untuk Select2
                'id' => (string)$row['id_parameter'],
                'text' => $row['nama_parameter'],

                // Data tambahan untuk autofill form
                'id_parameter'   => (string)$row['id_parameter'],
                'nama_parameter' => $row['nama_parameter'],
                'satuan'         => $row['satuan'],
                'kadar_maksimum' => $row['kadar_maksimum'],
                'metode_uji'     => $row['metode_uji'],
                'kategori'       => $row['kategori']
            ];
        }
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Database Error: " . mysqli_error($con));
}

mysqli_close($con);

// 5. Bersihkan buffer dan kirim JSON
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// Gunakan JSON_UNESCAPED_UNICODE agar simbol (seperti °C, µ) tidak rusak
echo json_encode($response_data, JSON_UNESCAPED_UNICODE);
