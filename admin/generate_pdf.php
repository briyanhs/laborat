<?php
// admin/generate_pdf.php (Versi PHP 7.4 & Endroid QR v4)

// Pastikan tidak ada spasi/enter sebelum tag PHP
require_once '../vendor/autoload.php';
include '../database/database.php';
include '../config.php';

// Set charset agar simbol kimia/matematika terbaca
mysqli_set_charset($con, "utf8mb4");

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
// PERBAIKAN 1: Gunakan Class spesifik, bukan Enum (karena PHP 7.4 belum support Enum)
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Writer\PngWriter;

// --- 1. VALIDASI INPUT ---
if (!isset($_GET['id_m_hasil_uji']) || !is_numeric($_GET['id_m_hasil_uji'])) {
    http_response_code(400);
    die("Error: ID Master Hasil Uji tidak valid.");
}

$id_m_hasil_uji = (int) $_GET['id_m_hasil_uji'];

// --- 2. AMBIL DATA MASTER ---
$query_master = "SELECT * FROM master_hasil_uji WHERE id_m_hasil_uji = ?";
$stmt_master = mysqli_prepare($con, $query_master);
mysqli_stmt_bind_param($stmt_master, "i", $id_m_hasil_uji);
mysqli_stmt_execute($stmt_master);
$result_master = mysqli_stmt_get_result($stmt_master);
$master_data = mysqli_fetch_assoc($result_master);

if (!$master_data) {
    http_response_code(404);
    die("Error: Data Master Hasil Uji tidak ditemukan.");
}

// --- 3. LOGIKA VERIFIKATOR (TTD) ---
$verifiers = [];
$query_log = "
    SELECT u.nama 
    FROM log_verifikasi lv
    JOIN user u ON lv.id_user_verifier = u.id_user
    WHERE lv.id_hasil_uji = ? AND lv.tipe_uji = 'fisika'
";
$stmt_log = mysqli_prepare($con, $query_log);
mysqli_stmt_bind_param($stmt_log, "i", $id_m_hasil_uji);
mysqli_stmt_execute($stmt_log);
$result_log = mysqli_stmt_get_result($stmt_log);

while ($row = mysqli_fetch_assoc($result_log)) {
    $verifiers[$row['nama']] = true;
}

// --- 4. GENERATE QR CODE (VERSI PHP 7.4) ---
$qrCodeBase64 = '';
if (!empty($master_data['verification_token'])) {
    // Gunakan URL yang valid
    $verification_url = BASE_URL . 'public_verify.php?token=' . urlencode($master_data['verification_token']);

    // PERBAIKAN 2: Menggunakan Builder::create() dan Method Chaining
    // PHP 7.4 tidak mendukung 'Named Arguments' (contoh: writer: new PngWriter())
    $result = Builder::create()
        ->writer(new PngWriter())
        ->writerOptions([])
        ->data($verification_url)
        ->encoding(new Encoding('UTF-8'))
        ->errorCorrectionLevel(new ErrorCorrectionLevelHigh()) // Gunakan Class instance
        ->size(100)            // Ukuran pixel
        ->margin(-20)            // Margin
        ->build();
        
    $qrString = $result->getString();
    $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrString);
}

// --- 5. AMBIL DETAIL PARAMETER & GROUPING ---
$query_detail = "
    SELECT
        id, hasil, nama_parameter, satuan, kadar_maksimum,
        metode_uji, kategori, keterangan
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
        nama_parameter ASC
";

$stmt_detail = mysqli_prepare($con, $query_detail);
mysqli_stmt_bind_param($stmt_detail, "i", $id_m_hasil_uji);
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);

$detail_data = [];
while ($row = mysqli_fetch_assoc($result_detail)) {
    $detail_data[] = $row;
}

// Grouping
$grouped_parameters = [];
foreach ($detail_data as $param) {
    $kategori = !empty($param['kategori']) ? $param['kategori'] : 'Lainnya';
    $grouped_parameters[$kategori][] = $param;
}

// Tutup koneksi
mysqli_close($con);

// --- 6. RENDER PDF ---
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);

// Output Buffering
ob_start();
include 'generate_template.php'; 
$html = ob_get_clean();

$dompdf->loadHtml($html);

// Set ukuran kertas F4 (Folio) - Ukuran dalam point (1 inch = 72 pt)
// F4 = 210mm x 330mm = 8.27in x 13.0in
// 8.27 * 72 ≈ 595.44
// 13.0 * 72 = 936
$dompdf->setPaper(array(0, 0, 612.28, 935.43), 'portrait'); // Settingan awal Anda

$dompdf->render();

// Stream PDF
$clean_no_analisa = preg_replace('/[^A-Za-z0-9\-]/', '_', $master_data['no_analisa']);
$filename = "Laporan_Hasil_Uji_" . $clean_no_analisa . ".pdf";
$dompdf->stream($filename, array("Attachment" => 0));
?>