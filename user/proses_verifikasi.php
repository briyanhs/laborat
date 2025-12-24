<?php
// admin/proses_verifikasi.php (FIX COLUMN NAME & DEBUG MODE & SEQUENCE CHECK)

require_once '../vendor/autoload.php';
include '../database/database.php';
include '../config.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Writer\PngWriter;

// FUNGSI DEBUG
function catatLog($pesan)
{
    //$file = 'debug_log.txt';
    //$waktu = date("Y-m-d H:i:s");
    //file_put_contents($file, "[$waktu] $pesan" . PHP_EOL, FILE_APPEND);
}

session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict']);
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['level'] != "User") {
    die("Akses ditolak");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Validasi Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        // Handle CSRF jika perlu
    }

    $id_m_hasil_uji = isset($_POST['id_m_hasil_uji']) ? intval($_POST['id_m_hasil_uji']) : 0;
    $tipe           = $_POST['tipe'] ?? '';
    $user_id        = $_SESSION['user_id'];

    // Ambil Nama User Login
    $query_user = mysqli_query($con, "SELECT nama FROM user WHERE id_user = '$user_id'");
    $data_user = mysqli_fetch_assoc($query_user);
    $nama_user_login = $data_user['nama'] ?? '';

    // Nama Direktur untuk Validasi Urutan
    $nama_direktur_fix = "Sarwoko Priyo Saptono, S.H";

    catatLog("--- START VERIFIKASI ---");
    catatLog("User: '$nama_user_login' | ID Uji: $id_m_hasil_uji | Tipe: $tipe");

    if (!in_array($tipe, ['fisika', 'bakteri'])) die("Tipe invalid");

    $master_table = ($tipe == 'fisika') ? 'master_hasil_uji' : 'master_hasil_uji_bacteriology';

    mysqli_begin_transaction($con);

    try {
        // A. Cek Duplicate
        $query_check_dup = "SELECT id FROM log_verifikasi WHERE id_hasil_uji = ? AND tipe_uji = ? AND id_user_verifier = ?";
        $stmt_dup = mysqli_prepare($con, $query_check_dup);
        mysqli_stmt_bind_param($stmt_dup, "isi", $id_m_hasil_uji, $tipe, $user_id);
        mysqli_stmt_execute($stmt_dup);
        mysqli_stmt_store_result($stmt_dup);

        if (mysqli_stmt_num_rows($stmt_dup) > 0) {
            mysqli_stmt_close($stmt_dup);
            throw new Exception("Anda sudah pernah memverifikasi data ini sebelumnya.");
        }
        mysqli_stmt_close($stmt_dup);

        // ================================================================
        // B. CEK URUTAN VERIFIKASI (LOGIKA SEQUENCE) - BARU DITAMBAHKAN
        // ================================================================
        if ($nama_user_login === $nama_direktur_fix) {
            // Hitung berapa orang yang sudah tanda tangan sebelumnya
            $q_count = "SELECT COUNT(*) as total FROM log_verifikasi WHERE id_hasil_uji = ? AND tipe_uji = ?";
            $stmt_c = mysqli_prepare($con, $q_count);
            mysqli_stmt_bind_param($stmt_c, "is", $id_m_hasil_uji, $tipe);
            mysqli_stmt_execute($stmt_c);
            $res_c = mysqli_stmt_get_result($stmt_c);
            $row_c = mysqli_fetch_assoc($res_c);
            $total_ttd = intval($row_c['total']);
            mysqli_stmt_close($stmt_c);

            // Jika kurang dari 2 (artinya Manajer & Asisten belum lengkap), TOLAK.
            if ($total_ttd < 2) {
                throw new Exception("sequence_error");
            }
        }
        // ================================================================

        // C. Generate Token (Jika belum ada)
        $q_token = mysqli_query($con, "SELECT verification_token FROM $master_table WHERE id_m_hasil_uji = '$id_m_hasil_uji'");
        $d_token = mysqli_fetch_assoc($q_token);
        if (empty($d_token['verification_token'])) {
            $token = bin2hex(random_bytes(20));
            mysqli_query($con, "UPDATE $master_table SET verification_token = '$token' WHERE id_m_hasil_uji = '$id_m_hasil_uji'");
        }

        // D. Insert Log Verifikasi
        $query_insert = "INSERT INTO log_verifikasi (id_hasil_uji, tipe_uji, id_user_verifier, verification_timestamp) VALUES (?, ?, ?, NOW())";
        $stmt_insert = mysqli_prepare($con, $query_insert);
        mysqli_stmt_bind_param($stmt_insert, "isi", $id_m_hasil_uji, $tipe, $user_id);

        if (!mysqli_stmt_execute($stmt_insert)) {
            throw new Exception("Gagal Insert Log: " . mysqli_error($con));
        }
        mysqli_stmt_close($stmt_insert);

        catatLog("Sukses Insert Log Verifikasi");

        // E. LOGIKA ARSIP OTOMATIS (Trigger: Nama Direktur)
        if ($nama_user_login === $nama_direktur_fix) {
            catatLog("User adalah Direktur. Mengecek Arsip...");

            $cek_arsip = mysqli_query($con, "SELECT id_arsip FROM arsip_laporan WHERE id_m_hasil_uji = '$id_m_hasil_uji' AND kategori = '$tipe'");

            if (mysqli_num_rows($cek_arsip) == 0) {
                catatLog("Arsip belum ada. Memulai Generate PDF...");
                $sukses_arsip = arsipLaporanOtomatis($con, $id_m_hasil_uji, $tipe);
                catatLog("Status Generate PDF: " . ($sukses_arsip ? "BERHASIL" : "GAGAL"));
            } else {
                catatLog("Arsip sudah ada. Skip.");
            }
        } else {
            catatLog("User bukan Direktur (atau nama tidak cocok). Skip Arsip.");
        }

        mysqli_commit($con);
        header("location: halaman_user.php?pesan=verif_sukses");

    } catch (Exception $e) {
        mysqli_rollback($con);
        catatLog("ERROR TRANSACTION: " . $e->getMessage());

        // Handle Pesan Error
        if ($e->getMessage() == "Anda sudah pernah memverifikasi data ini sebelumnya.") {
            header("location: halaman_user.php?pesan=verif_gagal");
        } 
        elseif ($e->getMessage() == "sequence_error") {
            // Redirect dengan pesan sequence error (akan ditangkap SweetAlert)
            $msg = urlencode("Mohon maaf, Anda belum bisa verifikasi. Pastikan Manajer Perencana dan Asisten Manajer Laboratorium sudah tanda tangan.");
            header("location: halaman_user.php?pesan=gagal&error_msg=" . $msg);
        } 
        else {
            header("location: halaman_user.php?pesan=gagal");
        }
    }
}

// FUNGSI GENERATE PDF (VERSI YANG SUDAH TERBUKTI BERHASIL)
function arsipLaporanOtomatis($con, $id_m_hasil_uji, $tipe_uji)
{
    // 1. Definisi Folder Arsip (Path Absolut)
    $folder_arsip = '../arsip_pdf/';

    // Cek Kesiapan Folder
    if (!is_dir($folder_arsip) || !is_writable($folder_arsip)) {
        catatLog("ERROR FOLDER: $folder_arsip tidak ditemukan atau tidak writable.");
        return false;
    }

    // 2. Setup Variabel & Path Template 
    if ($tipe_uji == 'bakteri') {
        $tabel_master = 'master_hasil_uji_bacteriology';
        $tabel_detail = 'hasil_uji_bacteriology';
        $template_file = '../admin/generate_template_bacteriology.php';
        $prefix_file = 'Laporan_Bakteri_';
    } else {
        $tabel_master = 'master_hasil_uji';
        $tabel_detail = 'hasil_uji';
        $template_file = '../admin/generate_template.php';
        $prefix_file = 'Laporan_Fisika_';
    }

    // 3. Ambil Data Master
    $q_master = mysqli_query($con, "SELECT * FROM $tabel_master WHERE id_m_hasil_uji = '$id_m_hasil_uji'");
    $master_data = mysqli_fetch_assoc($q_master);
    if (!$master_data) {
        catatLog("Data Master tidak ditemukan");
        return false;
    }

    // 4. Ambil Verifikator
    $verifiers = [];
    $q_log = mysqli_query($con, "SELECT u.nama FROM log_verifikasi lv JOIN user u ON lv.id_user_verifier = u.id_user WHERE lv.id_hasil_uji = '$id_m_hasil_uji' AND lv.tipe_uji = '$tipe_uji'");
    while ($row = mysqli_fetch_assoc($q_log)) {
        $verifiers[$row['nama']] = true;
    }

    // 5. Generate QR Code
    $qrCodeBase64 = '';
    if (!empty($master_data['verification_token'])) {
        try {
            $url_validasi = BASE_URL . "public_verify.php?token=" . $master_data['verification_token'];
            $qrResult = Builder::create()
                ->writer(new PngWriter())
                ->writerOptions([])
                ->data($url_validasi)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->size(300)->margin(0)->build();
            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrResult->getString());
        } catch (Exception $e) {
            catatLog("Error QR Code: " . $e->getMessage());
        }
    }

    // 6. Ambil Data Detail
    $detail_data = [];
    if ($tipe_uji == 'fisika') {
        $q_detail = mysqli_query($con, "SELECT * FROM $tabel_detail WHERE id_m_hasil_uji = '$id_m_hasil_uji' ORDER BY CASE WHEN kategori = 'Fisika' THEN 1 WHEN kategori = 'Kimia' THEN 2 ELSE 3 END, id ASC");
        while ($d = mysqli_fetch_assoc($q_detail)) $detail_data[] = $d;
        $grouped_parameters = [];
        foreach ($detail_data as $param) {
            $kat = $param['kategori'] ?: 'Lainnya';
            $grouped_parameters[$kat][] = $param;
        }
    } else {
        $q_detail = mysqli_query($con, "SELECT * FROM $tabel_detail WHERE id_m_hasil_uji = '$id_m_hasil_uji' ORDER BY id ASC");
        while ($d = mysqli_fetch_assoc($q_detail)) $detail_data[] = $d;
    }

    // 7. Render PDF
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    ob_start();
    // Cek keberadaan file dengan path absolut
    if (file_exists($template_file)) {
        include $template_file;
    } else {
        // Jika masih gagal, catat path lengkapnya agar kita tahu dia cari kemana
        catatLog("CRITICAL ERROR: Template tidak ditemukan di path: $template_file");
        ob_end_clean();
        return false;
    }
    $html_content = ob_get_clean();

    $dompdf->loadHtml($html_content);
    $dompdf->setPaper(array(0, 0, 612.28, 935.43), 'portrait');
    $dompdf->render();
    $pdf_output = $dompdf->output();

    // 8. Simpan File
    $clean_no_analisa = preg_replace('/[^A-Za-z0-9\-]/', '_', $master_data['no_analisa']);
    $nama_file_pdf = $prefix_file . $clean_no_analisa . '.pdf';
    $full_path = $folder_arsip . $nama_file_pdf;

    if (file_put_contents($full_path, $pdf_output) === false) {
        catatLog("Gagal menulis file PDF ke folder $full_path");
        return false;
    }

    // 9. Simpan ke DB Arsip
    $stmt_arsip = mysqli_prepare($con, "INSERT INTO arsip_laporan (id_m_hasil_uji, kategori, nama_file, tgl_arsip) VALUES (?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmt_arsip, "iss", $id_m_hasil_uji, $tipe_uji, $nama_file_pdf);

    if (mysqli_stmt_execute($stmt_arsip)) {
        catatLog("Berhasil simpan ke Tabel Arsip. Selesai.");
        mysqli_stmt_close($stmt_arsip);
        return true;
    } else {
        catatLog("Gagal simpan ke Tabel Arsip: " . mysqli_error($con));
        return false;
    }
}
?>