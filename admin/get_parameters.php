<?php
// admin/get_parameters.php

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

    // --- ATURAN BISNIS PAKET AIR BERSIH ---
    // Pastikan ID ini sesuai dengan ID paket "Air Bersih" di database Anda
    $id_paket_air_bersih = 2; 
    $params_to_modify = range(12, 16); // ID Kadmium s.d. Aluminium
    $param_to_remove = 17; // ID Sisa Khlor
    // --- AKHIR ATURAN BISNIS ---

    $query = "SELECT p.id_parameter, p.nama_parameter, p.satuan, p.kadar_maksimum, p.metode_uji, p.kategori 
              FROM detail_paket_pengujian_fisika_kimia dp
              JOIN parameter_uji p ON dp.id_parameter = p.id_parameter
              WHERE dp.id_paket = ? ORDER BY p.id_parameter ASC";

    $stmt = mysqli_prepare($con, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_paket);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                
                // Terapkan aturan HANYA JIKA ini paket "Air Bersih"
                if ($id_paket == $id_paket_air_bersih) { 
                    if ($row['id_parameter'] == $param_to_remove) {
                        continue; // Lewati Sisa Khlor
                    }
                    if (in_array($row['id_parameter'], $params_to_modify)) {
                        $row['kadar_maksimum'] = '-'; // Ubah nilainya
                    }
                }
                
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

if (isset($con)) {
    mysqli_close($con);
}
?>