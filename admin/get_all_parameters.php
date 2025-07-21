<?php
// lab/get_all_parameters.php
// Mengambil semua parameter dari tabel parameter_uji untuk Select2

include '../database/database.php';
include '../config.php';

header('Content-Type: application/json');

$response = [];

if (!isset($con) || !$con) {
    echo json_encode([]); // Mengembalikan array kosong jika koneksi gagal
    exit();
}

$search_term = $_GET['q'] ?? ''; // Search term dari Select2
$page = $_GET['page'] ?? 1; // Current page, if pagination is used (optional for smaller datasets)
$limit = 10; // Number of results per page

$offset = ($page - 1) * $limit;

$query = "SELECT id_parameter, nama_parameter, satuan, kadar_maksimum, metode_uji, kategori 
          FROM parameter_uji
          WHERE nama_parameter LIKE ?
          ORDER BY nama_parameter ASC";
// LIMIT ?, ?"; // Tambahkan LIMIT dan OFFSET jika ingin pagination di Select2

$stmt = mysqli_prepare($con, $query);
if ($stmt) {
    $search_param = '%' . $search_term . '%';
    mysqli_stmt_bind_param($stmt, "s", $search_param);
    // mysqli_stmt_bind_param($stmt, "sii", $search_param, $limit, $offset); // Jika pakai LIMIT/OFFSET

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $response[] = $row; // Mengembalikan array objek parameter
        }
    }
    mysqli_stmt_close($stmt);
}

echo json_encode($response);
mysqli_close($con);
