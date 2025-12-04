<?php
// admin/get_data_for_edit.php

// 1. Mulai Output Buffering (Mencegah output sampah merusak JSON)
ob_start();

include '../database/database.php';
include '../config.php';

session_start();

// 2. Cek Session & Validasi Akses
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

// 3. Set Charset ke UTF-8 (Wajib untuk simbol Fisika/Kimia)
mysqli_set_charset($con, "utf8mb4");

$response = [
    'success' => false,
    'message' => 'Permintaan tidak valid.',
    'master_data' => null,
    'detail_data' => []
];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_m_hasil_uji'])) {

    $id_m_hasil_uji = intval($_POST['id_m_hasil_uji']);

    if ($id_m_hasil_uji > 0) {
        // --- QUERY 1: AMBIL DATA MASTER ---
        $query_master = "SELECT * FROM master_hasil_uji WHERE id_m_hasil_uji = ?";
        $stmt_master = mysqli_prepare($con, $query_master);

        if ($stmt_master) {
            mysqli_stmt_bind_param($stmt_master, "i", $id_m_hasil_uji);
            mysqli_stmt_execute($stmt_master);
            $result_master = mysqli_stmt_get_result($stmt_master);

            if ($master_data = mysqli_fetch_assoc($result_master)) {
                $response['master_data'] = $master_data;

                // --- QUERY 2: AMBIL DATA DETAIL ---
                // Urutkan berdasarkan ID ASC agar urutan parameter di form rapi
                $query_detail = "SELECT id, nama_parameter, satuan, kadar_maksimum, metode_uji, kategori, hasil, status, keterangan 
                                 FROM hasil_uji 
                                 WHERE id_m_hasil_uji = ? 
                                 ORDER BY id ASC";

                $stmt_detail = mysqli_prepare($con, $query_detail);

                if ($stmt_detail) {
                    mysqli_stmt_bind_param($stmt_detail, "i", $id_m_hasil_uji);
                    mysqli_stmt_execute($stmt_detail);
                    $result_detail = mysqli_stmt_get_result($stmt_detail);

                    while ($row_detail = mysqli_fetch_assoc($result_detail)) {
                        $response['detail_data'][] = $row_detail;
                    }

                    mysqli_stmt_close($stmt_detail);

                    $response['success'] = true;
                    $response['message'] = 'Data berhasil diambil.';
                } else {
                    // Log error di server, jangan tampilkan ke user
                    error_log("Query Detail Error: " . mysqli_error($con));
                    $response['message'] = 'Gagal mengambil detail parameter.';
                }
            } else {
                $response['message'] = 'Data master tidak ditemukan.';
            }
            mysqli_stmt_close($stmt_master);
        } else {
            error_log("Query Master Error: " . mysqli_error($con));
            $response['message'] = 'Terjadi kesalahan sistem.';
        }
    } else {
        $response['message'] = 'ID tidak valid.';
    }
}

mysqli_close($con);

// 4. Bersihkan buffer dan kirim output JSON yang bersih
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// JSON_UNESCAPED_UNICODE agar simbol tidak berubah jadi kode acak (\uXXXX)
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>