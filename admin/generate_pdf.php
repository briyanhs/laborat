<?php
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
use Endroid\QrCode\ErrorCorrectionLevel;
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
// Ambil siapa saja yang memverifikasi tipe 'fisika' (mencakup kimia juga dalam konteks ini)
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

// --- 4. GENERATE QR CODE ---
$qrCodeBase64 = '';
if (!empty($master_data['verification_token'])) {
    $verification_url = BASE_URL . 'public_verify.php?token=' . urlencode($master_data['verification_token']);

    // Buat QR Code
    $builder = new Builder(
        writer: new PngWriter(),
        data: ($verification_url),
        encoding: new Encoding('UTF-8'),
        errorCorrectionLevel: ErrorCorrectionLevel::High,
        margin: -15
    );
    $result = $builder->build();
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
        -- Urutkan agar Fisika muncul duluan, baru Kimia, lalu lainnya
        CASE
            WHEN kategori = 'Fisika' THEN 1
            WHEN kategori = 'Kimia' THEN 2
            ELSE 3
        END,
        id ASC -- Urutkan berdasarkan ID input agar urutan form terjaga
";

$stmt_detail = mysqli_prepare($con, $query_detail);
mysqli_stmt_bind_param($stmt_detail, "i", $id_m_hasil_uji);
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);

$detail_data = [];
while ($row = mysqli_fetch_assoc($result_detail)) {
    $detail_data[] = $row;
}

// Grouping array berdasarkan kategori untuk ditampilkan per section di PDF
$grouped_parameters = [];
foreach ($detail_data as $param) {
    $kategori = $param['kategori'] ?: 'Lainnya'; // Default jika kategori kosong
    $grouped_parameters[$kategori][] = $param;
}

// Tutup koneksi sebelum render PDF
mysqli_close($con);

// --- 6. RENDER PDF ---
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);

// Output Buffering untuk mengambil HTML template
ob_start();
// Pastikan nama file template benar dan ada di folder yang sama dengan file ini
include 'generate_template.php';
$html = ob_get_clean();

$dompdf->loadHtml($html);

// Set ukuran kertas F4 (Folio)
$dompdf->setPaper(array(0, 0, 612.28, 935.43), 'portrait');

$dompdf->render();

// Stream PDF
// Gunakan preg_replace pada nama file agar aman saat didownload
$filename = "Laporan_Hasil_Uji_" . preg_replace('/[^A-Za-z0-9\-]/', '_', $master_data['no_analisa']) . ".pdf";
$dompdf->stream($filename, array("Attachment" => FALSE));
