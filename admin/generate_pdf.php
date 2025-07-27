<?php
// Include Dompdf Autoload
require_once '../vendor/autoload.php';
include '../database/database.php';
include '../config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Pastikan ID Master Hasil Uji diterima
if (!isset($_GET['id_m_hasil_uji']) || !is_numeric($_GET['id_m_hasil_uji'])) {
    die("ID Master Hasil Uji tidak valid.");
}

$id_m_hasil_uji = $_GET['id_m_hasil_uji'];

// --- Ambil Data Master Hasil Uji ---
$query_master = "SELECT * FROM master_hasil_uji WHERE id_m_hasil_uji = ?";
$stmt_master = mysqli_prepare($con, $query_master);
mysqli_stmt_bind_param($stmt_master, "i", $id_m_hasil_uji);
mysqli_stmt_execute($stmt_master);
$result_master = mysqli_stmt_get_result($stmt_master);
$master_data = mysqli_fetch_assoc($result_master);

if (!$master_data) {
    die("Data Master Hasil Uji tidak ditemukan.");
}

// --- Ambil Data Detail Parameter Hasil Uji ---
$query_detail = "
    SELECT
        id,
        hasil,
        nama_parameter,
        satuan,
        kadar_maksimum,
        metode_uji,
        kategori
    FROM
        hasil_uji
    WHERE
        id_m_hasil_uji = ?
    ORDER BY
        CASE
            WHEN kategori = 'Fisika' THEN 1
            WHEN kategori = 'Kimia' THEN 2
            ELSE 3
        END,
        nama_parameter ASC;
";

$stmt_detail = mysqli_prepare($con, $query_detail);
mysqli_stmt_bind_param($stmt_detail, "i", $id_m_hasil_uji);
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);
$detail_data = [];
while ($row = mysqli_fetch_assoc($result_detail)) {
    $detail_data[] = $row;
}

// Group parameters by category (pindahkan logika ini ke generate_pdf.php)
$grouped_parameters = [];
foreach ($detail_data as $param) {
    $grouped_parameters[$param['kategori']][] = $param;
}

// --- Inisialisasi Dompdf ---
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// --- Capture HTML Output (Output Buffering) ---
ob_start(); // Mulai output buffering
include '../admin/generate_template.php'; // Masukkan file template HTML Anda
$html = ob_get_clean(); // Ambil semua output dan simpan ke $html

$dompdf->loadHtml($html);

$dompdf->setPaper(array(0, 0, 612.28, 935.43), 'portrait');

$dompdf->render();

$dompdf->stream("Laporan_Hasil_Uji_" . $master_data['no_lab'] . ".pdf", array("Attachment" => FALSE));

// Tutup koneksi database
mysqli_close($con);
?>