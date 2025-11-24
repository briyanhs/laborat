<?php
// get_data_for_edit.php (VERSI PERBAIKAN FINAL)

include '../database/database.php';
include '../config.php';
session_start();

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'Permintaan tidak valid.',
    'master_data' => null,
    'detail_data' => []
];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_m_hasil_uji'])) {
    if (!isset($con) || !$con) {
        $response['message'] = 'Koneksi database gagal.';
        echo json_encode($response);
        exit();
    }

    $id_m_hasil_uji = intval($_POST['id_m_hasil_uji']);

    if ($id_m_hasil_uji <= 0) {
        $response['message'] = 'ID Master Hasil Uji tidak valid.';
        echo json_encode($response);
        exit();
    }

    // 1. Ambil data master LENGKAP sesuai form di fisika_kimia.php
    $query_master = "SELECT * FROM master_hasil_uji WHERE id_m_hasil_uji = ?";
    $stmt_master = mysqli_prepare($con, $query_master);
    mysqli_stmt_bind_param($stmt_master, "i", $id_m_hasil_uji);
    mysqli_stmt_execute($stmt_master);
    $result_master = mysqli_stmt_get_result($stmt_master);

    if ($master_data = mysqli_fetch_assoc($result_master)) {
        $response['master_data'] = $master_data;

        // 2. Ambil semua data detail yang terkait
        $query_detail = "SELECT id, nama_parameter, satuan, kadar_maksimum, metode_uji, kategori, hasil, status, keterangan FROM hasil_uji WHERE id_m_hasil_uji = ? ORDER BY id ASC";
        $stmt_detail = mysqli_prepare($con, $query_detail);
        mysqli_stmt_bind_param($stmt_detail, "i", $id_m_hasil_uji);
        mysqli_stmt_execute($stmt_detail);
        $result_detail = mysqli_stmt_get_result($stmt_detail);

        if ($result_detail) {
            while ($row_detail = mysqli_fetch_assoc($result_detail)) {
                $response['detail_data'][] = $row_detail;
            }
            $response['success'] = true;
            $response['message'] = 'Data berhasil diambil.';
        } else {
            $response['message'] = 'Gagal mengambil data detail: ' . mysqli_error($con);
        }
        mysqli_stmt_close($stmt_detail);
    } else {
        $response['message'] = 'Data master tidak ditemukan.';
    }
    mysqli_stmt_close($stmt_master);
}

echo json_encode($response);

if (isset($con)) {
    mysqli_close($con);
}
