<?php
// admin/get_data_for_edit_bacteriology.php

include '../database/database.php'; // Sesuaikan path jika perlu
include '../config.php'; // 

header('Content-Type: application/json');
$response = ['success' => false, 'master_data' => null, 'detail_data' => []];

if (isset($_POST['id_m_hasil_uji'])) {
    $id_m_hasil_uji = intval($_POST['id_m_hasil_uji']);

    mysqli_begin_transaction($con); // Mulai transaksi untuk konsistensi pembacaan

    try {
        // 1. Ambil Data Master
        $query_master = "SELECT * FROM master_hasil_uji_bacteriology WHERE id_m_hasil_uji = ?";
        $stmt_master = mysqli_prepare($con, $query_master);
        if (!$stmt_master) {
            throw new Exception("Prepare statement master gagal: " . mysqli_error($con));
        }
        mysqli_stmt_bind_param($stmt_master, 'i', $id_m_hasil_uji);
        mysqli_stmt_execute($stmt_master);
        $result_master = mysqli_stmt_get_result($stmt_master);
        $master_data = mysqli_fetch_assoc($result_master);
        mysqli_stmt_close($stmt_master);

        if ($master_data) {
            $response['master_data'] = $master_data;

            // 2. Ambil Data Detail
            $query_detail = "SELECT * FROM hasil_uji_bacteriology WHERE id_m_hasil_uji = ? ORDER BY id ASC";
            $stmt_detail = mysqli_prepare($con, $query_detail);
            if (!$stmt_detail) {
                throw new Exception("Prepare statement detail gagal: " . mysqli_error($con));
            }
            mysqli_stmt_bind_param($stmt_detail, 'i', $id_m_hasil_uji);
            mysqli_stmt_execute($stmt_detail);
            $result_detail = mysqli_stmt_get_result($stmt_detail);

            while ($row = mysqli_fetch_assoc($result_detail)) {
                $response['detail_data'][] = $row;
            }
            mysqli_stmt_close($stmt_detail);

            $response['success'] = true; // Set sukses hanya jika master ditemukan
        } else {
            $response['message'] = 'Data master dengan ID ' . $id_m_hasil_uji . ' tidak ditemukan.';
        }

        mysqli_commit($con); // Commit transaksi

    } catch (Exception $e) {
        mysqli_rollback($con); // Rollback jika ada error
        $response['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
        // Sebaiknya log error ini di server juga
        error_log("Error get_data_for_edit_bacteriology: " . $e->getMessage());
    }
} else {
    $response['message'] = 'ID Hasil Uji tidak valid atau tidak diterima.';
}

if (isset($con)) {
    mysqli_close($con);
}
echo json_encode($response);
