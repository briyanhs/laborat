<?php
// lab/get_parameters.php
// Mengambil parameter berdasarkan ID Paket dan mengembalikan dalam format JSON

include '../database/database.php';
include '../config.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'Invalid request.',
    'parameters' => []
];

if (isset($_POST['id_paket'])) {
    $id_paket = intval($_POST['id_paket']);

    if (!isset($con) || !$con) {
        $response['message'] = 'Database connection failed.';
        echo json_encode($response);
        exit();
    }

    $query = "SELECT p.id_parameter, p.nama_parameter, p.satuan, p.kadar_maksimum, p.metode_uji, p.kategori 
              FROM detail_paket_pengujian_fisika_kimia dp
              JOIN parameter_uji p ON dp.id_parameter = p.id_parameter
              WHERE dp.id_paket = ? ORDER BY p.nama_parameter ASC"; // Order by nama_parameter untuk tampilan yang rapi

    $stmt = mysqli_prepare($con, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_paket);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $response['parameters'][] = $row;
            }
            $response['success'] = true;
            $response['message'] = 'Parameters retrieved successfully.';
        } else {
            $response['message'] = 'Failed to retrieve parameters: ' . mysqli_error($con);
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Failed to prepare statement: ' . mysqli_error($con);
    }
} else {
    $response['message'] = 'id_paket is not set.';
}

echo json_encode($response);
mysqli_close($con);
