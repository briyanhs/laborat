<?php
// admin/get_data_for_edit_bacteriology.php

// 1. Mulai Output Buffering (Mencegah JSON rusak karena whitespace)
ob_start();

include '../database/database.php';
include '../config.php';

// 2. Cek Session (PENTING: Keamanan)
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    ob_end_clean();
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login.']);
    exit();
}

// Pastikan koneksi database ada
if (!isset($con) || !$con) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal.']);
    exit();
}

// 3. Set Charset (Agar simbol mikroorganisme/satuan tidak rusak)
mysqli_set_charset($con, "utf8mb4");

$response = ['success' => false, 'master_data' => null, 'detail_data' => []];

if (isset($_POST['id_m_hasil_uji'])) {
    $id_m_hasil_uji = intval($_POST['id_m_hasil_uji']);

    try {
        // --- QUERY 1: AMBIL DATA MASTER ---
        $query_master = "SELECT * FROM master_hasil_uji_bacteriology WHERE id_m_hasil_uji = ?";
        $stmt_master = mysqli_prepare($con, $query_master);

        if (!$stmt_master) {
            throw new Exception("Query master error: " . mysqli_error($con));
        }

        mysqli_stmt_bind_param($stmt_master, 'i', $id_m_hasil_uji);
        mysqli_stmt_execute($stmt_master);
        $result_master = mysqli_stmt_get_result($stmt_master);
        $master_data = mysqli_fetch_assoc($result_master);
        mysqli_stmt_close($stmt_master);

        if ($master_data) {
            $response['master_data'] = $master_data;

            // --- QUERY 2: AMBIL DATA DETAIL ---
            // Mengurutkan berdasarkan ID agar urutan parameter konsisten
            $query_detail = "SELECT * FROM hasil_uji_bacteriology WHERE id_m_hasil_uji = ? ORDER BY id ASC";
            $stmt_detail = mysqli_prepare($con, $query_detail);

            if (!$stmt_detail) {
                throw new Exception("Query detail error: " . mysqli_error($con));
            }

            mysqli_stmt_bind_param($stmt_detail, 'i', $id_m_hasil_uji);
            mysqli_stmt_execute($stmt_detail);
            $result_detail = mysqli_stmt_get_result($stmt_detail);

            while ($row = mysqli_fetch_assoc($result_detail)) {
                $response['detail_data'][] = $row;
            }
            mysqli_stmt_close($stmt_detail);

            $response['success'] = true;
        } else {
            $response['message'] = 'Data tidak ditemukan.';
        }
    } catch (Exception $e) {
        // Log error asli di server
        error_log("Error Edit Bacteriology: " . $e->getMessage());
        $response['message'] = 'Terjadi kesalahan sistem saat mengambil data.';
    }
} else {
    $response['message'] = 'ID Hasil Uji tidak valid.';
}

mysqli_close($con);

// 4. Bersihkan buffer dan kirim JSON
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
