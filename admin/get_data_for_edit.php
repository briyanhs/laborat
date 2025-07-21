<?php
// lab/get_data_for_edit.php
// Mengambil data master_hasil_uji dan semua hasil_uji terkait dalam format JSON

include '../database/database.php';
include '../config.php';
session_start();

header('Content-Type: application/json'); // Penting: Memberi tahu browser bahwa respons adalah JSON

$response = [
    'success' => false,
    'message' => 'Invalid request.',
    'master_data' => null,
    'detail_data' => []
];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_m_hasil_uji'])) {
    if (!isset($con) || !$con) {
        $response['message'] = 'Database connection failed.';
        echo json_encode($response);
        exit();
    }

    $id_m_hasil_uji = intval($_POST['id_m_hasil_uji']);

    if ($id_m_hasil_uji <= 0) {
        $response['message'] = 'Invalid ID Master Hasil Uji.';
        echo json_encode($response);
        exit();
    }

    // 1. Ambil data dari master_hasil_uji
    $query_master = "SELECT id_m_hasil_uji, no_lab, jenis_air, pengirim, penguji, lokasi_uji, tanggal_uji FROM master_hasil_uji WHERE id_m_hasil_uji = $id_m_hasil_uji";
    $result_master = mysqli_query($con, $query_master);

    if ($result_master && mysqli_num_rows($result_master) > 0) {
        $master_data = mysqli_fetch_assoc($result_master);
        
        // Ambil status master dari salah satu hasil_uji yang terhubung (untuk ditampilkan di dropdown master)
        // Jika ada logika status aggregate yang lebih kompleks, ini perlu disesuaikan
        $status_query = mysqli_query($con, "SELECT status FROM hasil_uji WHERE id_m_hasil_uji = $id_m_hasil_uji LIMIT 1");
        $status_row = mysqli_fetch_assoc($status_query);
        $master_data['status_master'] = $status_row ? $status_row['status'] : 'Proses'; // Default 'Proses' jika tidak ada detail

        $response['master_data'] = $master_data;

        // 2. Ambil semua data dari hasil_uji yang terkait
        $query_detail = "SELECT id, nama_parameter, satuan, kadar_maksimum, metode_uji, kategori, hasil, status FROM hasil_uji WHERE id_m_hasil_uji = $id_m_hasil_uji ORDER BY id ASC";
        $result_detail = mysqli_query($con, $query_detail);

        if ($result_detail) {
            while ($row_detail = mysqli_fetch_assoc($result_detail)) {
                $response['detail_data'][] = $row_detail;
            }
            $response['success'] = true;
            $response['message'] = 'Data retrieved successfully.';
        } else {
            $response['message'] = 'Failed to retrieve detail data: ' . mysqli_error($con);
        }

    } else {
        $response['message'] = 'Master data not found or failed to retrieve master data: ' . mysqli_error($con);
    }
}

echo json_encode($response);
mysqli_close($con);
?>