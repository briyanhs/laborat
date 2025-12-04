<?php
// admin/get_parameters.php

// 1. Mulai Output Buffering (Mencegah whitespace merusak JSON)
ob_start();

include '../database/database.php';
include '../config.php';

// 2. Cek Session (Keamanan Wajib)
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    ob_end_clean();
    http_response_code(403);
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

// 3. Set Charset (Penting untuk simbol kimia/fisika)
mysqli_set_charset($con, "utf8mb4");

$response = [
    'success' => false,
    'message' => 'Invalid request.',
    'parameters' => []
];

if (isset($_POST['id_paket'])) {
    $id_paket = intval($_POST['id_paket']);

    // --- ATURAN BISNIS PAKET AIR BERSIH (HARDCODED ID) ---
    // PENTING: Pastikan ID di database tidak berubah. Jika anda menghapus/input ulang parameter,
    // ID bisa berubah dan logika ini harus disesuaikan manual.
    $id_paket_air_bersih = 2;
    $params_to_modify = range(12, 16); // ID Kadmium s.d. Aluminium
    $param_to_remove = 17; // ID Sisa Khlor
    // --- AKHIR ATURAN BISNIS ---

    // Query mengambil data parameter berdasarkan paket
    $query = "SELECT p.id_parameter, p.nama_parameter, p.satuan, p.kadar_maksimum, p.metode_uji, p.kategori 
              FROM detail_paket_pengujian_fisika_kimia dp
              JOIN parameter_uji p ON dp.id_parameter = p.id_parameter
              WHERE dp.id_paket = ? 
              ORDER BY p.id_parameter ASC";

    $stmt = mysqli_prepare($con, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_paket);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $current_id = intval($row['id_parameter']);

                // --- LOGIKA MODIFIKASI KHUSUS AIR BERSIH ---
                if ($id_paket == $id_paket_air_bersih) {
                    // 1. Skip Sisa Khlor (ID 17)
                    if ($current_id == $param_to_remove) {
                        continue;
                    }
                    // 2. Ubah Kadar Maksimum untuk ID 12-16
                    if (in_array($current_id, $params_to_modify)) {
                        $row['kadar_maksimum'] = '-';
                    }
                }
                // -------------------------------------------

                // Casting ID ke string untuk konsistensi JS
                $row['id_parameter'] = (string)$current_id;

                $response['parameters'][] = $row;
            }
            $response['success'] = true;
            $response['message'] = 'Parameters retrieved successfully.';
        } else {
            $response['message'] = 'Gagal mengambil data.';
        }
        mysqli_stmt_close($stmt);
    } else {
        // Log error di server
        error_log("Database Error (get_parameters): " . mysqli_error($con));
        $response['message'] = 'Terjadi kesalahan sistem.';
    }
} else {
    $response['message'] = 'ID Paket tidak diterima.';
}

mysqli_close($con);

// 4. Bersihkan buffer dan kirim JSON
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// Gunakan JSON_UNESCAPED_UNICODE agar simbol tidak rusak
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>