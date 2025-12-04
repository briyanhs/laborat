<?php
// admin/get_parameters_bacteriology.php

// 1. Mulai Output Buffering
ob_start();

include '../database/database.php';
include '../config.php';

// 2. Cek Session (Keamanan)
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    ob_end_clean();
    http_response_code(403); // Forbidden
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login.']);
    exit();
}

// Pastikan koneksi database ada
if (!isset($con) || !$con) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal.']);
    exit();
}

// 3. Set Charset ke UTF-8 (PENTING untuk simbol mikrobiologi)
mysqli_set_charset($con, "utf8mb4");

$response = ['success' => false, 'parameters' => []];

if (isset($_POST['id_paket'])) {
    $id_paket = intval($_POST['id_paket']);

    // Query menggunakan Prepared Statement (Aman dari SQL Injection)
    $query = "
        SELECT
            p.id_parameter,
            p.nama_parameter,
            p.satuan,
            p.nilai_baku_mutu,
            p.metode_uji
        FROM
            parameter_uji_bacteriology p
        JOIN
            detail_paket_pengujian_bacteriology dp ON p.id_parameter = dp.id_parameter
        WHERE
            dp.id_paket = ?
        ORDER BY 
            p.id_parameter ASC
    ";

    $stmt = mysqli_prepare($con, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id_paket);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            $parameters = [];
            while ($row = mysqli_fetch_assoc($result)) {
                // Casting ke string untuk konsistensi data di Javascript
                $row['id_parameter'] = (string)$row['id_parameter'];
                $parameters[] = $row;
            }
            $response['success'] = true;
            $response['parameters'] = $parameters;
        }
        mysqli_stmt_close($stmt);
    } else {
        // Log error di server, jangan tampilkan detail query ke user
        error_log("Database Error (get_parameters_bacteriology): " . mysqli_error($con));
        $response['message'] = 'Terjadi kesalahan pada sistem database.';
    }
} else {
    $response['message'] = 'ID Paket tidak diterima.';
}

mysqli_close($con);

// 4. Bersihkan buffer dan kirim JSON
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// Gunakan JSON_UNESCAPED_UNICODE agar simbol (seperti coliform/ml atau °C) tidak rusak
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>