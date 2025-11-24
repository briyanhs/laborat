<?php
// Include Dompdf Autoload
require_once '../vendor/autoload.php';
include '../database/database.php';
include '../config.php';

mysqli_set_charset($con, "utf8mb4");

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder; // <--- PENTING UNTUK 'Builder::create()'
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
// Pastikan ID Master Hasil Uji diterima
if (!isset($_GET['id_m_hasil_uji']) || !is_numeric($_GET['id_m_hasil_uji'])) {
    die("ID Master Hasil Uji tidak valid.");
}

$id_m_hasil_uji = $_GET['id_m_hasil_uji'];

// --- Ambil Data Master Hasil Uji (Gunakan query Anda yang benar) ---
$query_master = "SELECT m.*, u.nama as verifier_name 
                 FROM master_hasil_uji_bacteriology m 
                 LEFT JOIN user u ON m.verified_by_user_id = u.id_user
                 WHERE m.id_m_hasil_uji = ?";
// SAYA SALAH, INI SEHARUSNYA TIDAK ADA LAGI. 
// Query master tidak perlu join user lagi.
$query_master = "SELECT * FROM master_hasil_uji_bacteriology WHERE id_m_hasil_uji = ?";

$stmt_master = mysqli_prepare($con, $query_master);
mysqli_stmt_bind_param($stmt_master, "i", $id_m_hasil_uji);
mysqli_stmt_execute($stmt_master);
$result_master = mysqli_stmt_get_result($stmt_master);
$master_data = mysqli_fetch_assoc($result_master);

if (!$master_data) {
    die("Data Master Hasil Uji tidak ditemukan.");
}

// --- LOGIKA QR CODE DAN VERIFIKATOR BARU ---
$qrCodeBase64 = '';
$verifiers = []; // Ini akan menampung nama-nama verifikator

// 1. Ambil daftar siapa saja yang sudah verifikasi
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
    // Buat array seperti: ['Ratih Hastuti, S.Si' => true]
    $verifiers[$row['nama']] = true;
}

// 2. Jika ada token, buat QR Code-nya.
if (!empty($master_data['verification_token'])) {
    // Generate URL publik
    $verification_url = BASE_URL . 'public_verify.php?token=' . $master_data['verification_token'];

    // Buat QR code (Sintaks Anda yang benar)
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
// --- LOGIKA QR CODE SELESAI ---

// --- Ambil Data Detail Parameter Hasil Uji ---
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
        id_m_hasil_uji = ?;
";

$stmt_detail = mysqli_prepare($con, $query_detail);
mysqli_stmt_bind_param($stmt_detail, "i", $id_m_hasil_uji);
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);
$detail_data = [];
while ($row = mysqli_fetch_assoc($result_detail)) {
    $detail_data[] = $row;
}

// --- Inisialisasi Dompdf ---
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Menggunakan output buffering untuk menangkap output dari file template
ob_start();
include 'generate_template_bacteriology.php';
$html = ob_get_clean();

$dompdf->loadHtml($html);

$dompdf->setPaper(array(0, 0, 612.28, 935.43), 'portrait');

$dompdf->render();

$dompdf->stream("Laporan Hasil Uji Bakteriologi " . $master_data['no_analisa'] . ".pdf", array("Attachment" => FALSE));

mysqli_close($con);
