<?php
// Pastikan tidak ada output (spasi/enter) sebelum tag PHP
require_once '../vendor/autoload.php';
include '../database/database.php';
include '../config.php';

// Set charset koneksi agar simbol khusus (mikro, derajat, dll) tampil benar
mysqli_set_charset($con, "utf8mb4");

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

// --- 1. VALIDASI INPUT ---
if (!isset($_GET['id_m_hasil_uji']) || !is_numeric($_GET['id_m_hasil_uji'])) {
    http_response_code(400); // Bad Request
    die("Error: ID Master Hasil Uji tidak valid atau tidak ditemukan.");
}

$id_m_hasil_uji = (int) $_GET['id_m_hasil_uji'];

// --- 2. AMBIL DATA MASTER ---
$query_master = "SELECT * FROM master_hasil_uji_bacteriology WHERE id_m_hasil_uji = ?";
$stmt_master = mysqli_prepare($con, $query_master);
mysqli_stmt_bind_param($stmt_master, "i", $id_m_hasil_uji);
mysqli_stmt_execute($stmt_master);
$result_master = mysqli_stmt_get_result($stmt_master);
$master_data = mysqli_fetch_assoc($result_master);

if (!$master_data) {
    http_response_code(404); // Not Found
    die("Error: Data Laporan tidak ditemukan di database.");
}

// --- 3. LOGIKA VERIFIKATOR (TTD) ---
// Mengambil daftar nama user yang sudah memverifikasi data ini di tabel log
$verifiers = []; 
$query_log = "
    SELECT u.nama 
    FROM log_verifikasi lv
    JOIN user u ON lv.id_user_verifier = u.id_user
    WHERE lv.id_hasil_uji = ? AND lv.tipe_uji = 'bakteri'
";
$stmt_log = mysqli_prepare($con, $query_log);
mysqli_stmt_bind_param($stmt_log, "i", $id_m_hasil_uji);
mysqli_stmt_execute($stmt_log);
$result_log = mysqli_stmt_get_result($stmt_log);

while ($row = mysqli_fetch_assoc($result_log)) {
    // Key array menggunakan nama agar mudah dicek di template (isset)
    $verifiers[$row['nama']] = true;
}

// --- 4. GENERATE QR CODE ---
$qrCodeBase64 = '';
if (!empty($master_data['verification_token'])) {
    // URL yang akan dibuka saat QR di-scan
    $verification_url = BASE_URL . 'public_verify.php?token=' . urlencode($master_data['verification_token']);

    // Generate QR Code
    // Margin jangan negatif, set ke 0 atau 2 agar scanner bisa membaca border
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

// --- 5. AMBIL DETAIL PARAMETER HASIL UJI ---
$query_detail = "
    SELECT
        id,
        hasil,
        penegasan,
        nama_parameter,
        satuan,
        nilai_baku_mutu,
        keterangan,
        metode_uji
    FROM
        hasil_uji_bacteriology
    WHERE
        id_m_hasil_uji = ?
    ORDER BY id ASC
";

$stmt_detail = mysqli_prepare($con, $query_detail);
mysqli_stmt_bind_param($stmt_detail, "i", $id_m_hasil_uji);
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);

$detail_data = [];
while ($row = mysqli_fetch_assoc($result_detail)) {
    $detail_data[] = $row;
}

// Tutup koneksi database sebelum render PDF untuk menghemat resource
mysqli_close($con);

// --- 6. RENDER PDF MENGGUNAKAN DOMPDF ---
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); // Penting untuk memuat gambar/logo via URL
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);

// Tangkap output HTML dari file template
ob_start();
// Variabel $master_data, $detail_data, $verifiers, $qrCodeBase64 akan dikirim ke template ini
include 'generate_template_bacteriology.php'; 
$html = ob_get_clean();

$dompdf->loadHtml($html);

// Set ukuran kertas F4 (Folio) atau A4
// Ukuran F4 dalam point: 612.28 x 935.43 (kurang lebih 21.59cm x 33.02cm)
$dompdf->setPaper(array(0, 0, 612.28, 935.43), 'portrait');

$dompdf->render();

// Stream PDF ke browser (Attachment => false agar terbuka di browser, true untuk download otomatis)
$dompdf->stream("Laporan_Bakteriologi_" . preg_replace('/[^A-Za-z0-9\-]/', '_', $master_data['no_analisa']) . ".pdf", array("Attachment" => FALSE));
?>