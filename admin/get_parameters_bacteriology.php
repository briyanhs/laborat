<?php
// admin/get_parameters_bacteriology.php
include '../database/database.php';
include '../config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'parameters' => []];

if (isset($_POST['id_paket'])) {
    $id_paket = intval($_POST['id_paket']);

    // Query untuk mengambil parameter berdasarkan id_paket
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
    mysqli_stmt_bind_param($stmt, 'i', $id_paket);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $parameters = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Konversi id_parameter ke string agar konsisten di JS
            $row['id_parameter'] = (string)$row['id_parameter'];
            $parameters[] = $row;
        }
        $response['success'] = true;
        $response['parameters'] = $parameters;
    } else {
        $response['message'] = 'Query gagal: ' . mysqli_error($con);
    }
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'ID Paket tidak diterima.';
}

mysqli_close($con);
echo json_encode($response);
?>