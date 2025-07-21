<?php
// Include Dompdf Autoload
require_once '../vendor/autoload.php'; // Sesuaikan path jika berbeda
include '../database/database.php'; // Asumsikan ini koneksi database Anda
include '../config.php'; // Asumsikan ini file konfigurasi (untuk BASE_URL dll)

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
// KOREKSI: Ambil langsung dari tabel hasil_uji karena sudah menyimpan semua detail
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

// --- Inisialisasi Dompdf ---
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); // Penting jika ada gambar atau CSS eksternal
$dompdf = new Dompdf($options);

// --- Buat Konten HTML untuk PDF ---
// Struktur HTML ini harus meniru desain dari gambar x.jpg
// Sesuaikan styling, tabel, dan penempatan data
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laporan Hasil Uji</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 10mm 15mm; /* Mengurangi margin atas/bawah dan samping */
            font-size: 10pt; /* Ukuran font dasar yang lebih kecil */
        }
        .header {
            text-align: center;
            margin-bottom: 8px; /* Mengurangi jarak bawah header */
            line-height: 1.1; /* Mengurangi tinggi baris di header */
        }
        .header img {
            width: 65px; /* Mengurangi lebar logo lagi */
            float: left;
            margin-right: 10px;
        }
        .header h4 {
            font-size: 12pt; /* Mengurangi ukuran font judul di header */
            margin-top: 0;
            margin-bottom: 2px;
        }
        .header p {
            font-size: 7.5pt; /* Mengurangi ukuran font paragraf di header */
            margin: 0;
        }
        .line {
            border-bottom: 0.5px solid #000; /* Mengurangi ketebalan garis sangat tipis */
            margin-top: 3px;
            margin-bottom: 12px; /* Mengurangi jarak bawah garis */
        }
        h3 {
            font-size: 12.5pt; /* Ukuran font judul laporan */
            margin: 5px 0 10px 0; /* Margin atas/bawah judul laporan */
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px; /* Mengurangi jarak bawah tabel info */
            font-size: 8.5pt;
        }
        .info-table td {
            padding: 1px 0; /* Mengurangi padding di sel tabel info */
            vertical-align: top;
        }
        .info-table td.label {
            width: 120px; /* Sesuaikan lebar label jika perlu */
        }
        .info-table td.value {
            width: calc(50% - 120px);
        }

        .parameter-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px; /* Mengurangi jarak atas tabel parameter */
        }
        .parameter-table th, .parameter-table td {
            border: 1px solid #000;
            padding: 2.5px 2px; /* Mengurangi padding di sel tabel parameter */
            text-align: center;
            font-size: 8pt; /* Ukuran font tabel parameter yang lebih kecil */
        }
        .parameter-table th {
            background-color: #f2f2f2;
        }
        .parameter-table td.text-left {
            text-align: left;
            padding-left: 5px; /* Mengurangi padding kiri untuk teks rata kiri */
        }
        .notes {
            font-size: 7pt; /* Ukuran font catatan yang lebih kecil */
            margin-top: 8px; /* Mengurangi jarak atas catatan */
            line-height: 1.2; /* Mengurangi tinggi baris catatan */
        }
        .notes ol {
            margin-left: 12px; /* Mengurangi indentasi list */
            padding-left: 0;
        }
        .notes li {
            margin-bottom: 2px; /* Mengurangi spasi antar item list */
        }

        .footer-signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px; /* Mengurangi jarak atas tanda tangan */
        }
        .footer-signatures td {
            width: 33%;
            text-align: center;
            vertical-align: top;
            padding: 8px 0; /* Mengurangi padding di sel tanda tangan */
            font-size: 8.5pt; /* Ukuran font tanda tangan */
        }
        .footer-signatures .date-place {
            text-align: right;
            margin-bottom: 10px; /* Mengurangi jarak bawah tanggal/tempat */
            font-size: 8.5pt;
        }
        .name {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 10px; /* Mengurangi jarak atas nama */
        }
        .npp {
            font-size: 7pt; /* Ukuran font NPP yang lebih kecil */
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="'.BASE_URL.'assets/img/logo_pdam.png" alt="Logo"> <h4>PEMERINTAH KOTA SURAKARTA</h4>
        <h4>PERUSAHAAN UMUM DAERAH AIR MINUM</h4>
        <p>Jl. LUU, Adi Sucipto No. 143 Telp. (0271) 712465, 723093, Fax. (0271) 712536</p>
        <p>E-mail: pdamSolo@indo.net.id | pdam@toyaweningsolo.co.id</p>
        <p>Website: www.toyaweningsolo.co.id</p>
        <p>SURAKARTA 57145</p>
    </div>
    <div class="line"></div>

    <h3 style="text-align: center; margin: 10px 0;">LAPORAN HASIL UJI</h3>

    <table class="info-table">
        <tr>
            <td class="label">1. Jenis Air</td>
            <td>: ' . htmlspecialchars($master_data['jenis_air']) . '</td>
            <td class="label right-align">Dikirim/Diambil</td>
            <td class="value">: ' . htmlspecialchars($master_data['pengirim']) . '</td>
        </tr>
        <tr>
            <td class="label">2. Berasal dari</td>
            <td>: ' . htmlspecialchars($master_data['lokasi_uji']) . '</td>
            <td class="label right-align">Diterima</td>
            <td class="value">: ' . date('d F Y', strtotime($master_data['tanggal_uji'])) . '</td>
        </tr>
        <tr>
            <td class="label">3. No. Lab.</td>
            <td>: ' . htmlspecialchars($master_data['no_lab']) . '</td>
            <td class="label right-align">Tanggal Uji</td>
            <td class="value">: ' . date('d F Y', strtotime($master_data['tanggal_uji'])) . '</td>
        </tr>
    </table>

    <table class="parameter-table">
        <thead>
            <tr>
                <th>No.</th>
                <th>Parameter</th>
                <th>Satuan</th>
                <th>Kadar Maksimum *)</th>
                <th>Hasil Uji</th>
                <th>Metode Uji</th>
            </tr>
        </thead>
        <tbody>';

// Group parameters by category as shown in your example image (x.jpg)
$grouped_parameters = [];
foreach ($detail_data as $param) {
    $grouped_parameters[$param['kategori']][] = $param;
}

$no_global = 1; // Nomor urut global
$kategori_index = 0; // Untuk I., II.
$kategori_labels = ['I.', 'II.', 'III.', 'IV.', 'V.']; // Bisa ditambahkan jika ada lebih dari 2 kategori

foreach ($grouped_parameters as $kategori => $params) {
    if (isset($kategori_labels[$kategori_index])) {
        $html .= '<tr>
                    <td class="text-left" colspan="6"><b>' . $kategori_labels[$kategori_index] . ' ' . htmlspecialchars(strtoupper($kategori)) . '</b></td>
                  </tr>';
        $kategori_index++;
    } else {
         $html .= '<tr>
                    <td class="text-left" colspan="6"><b>' . htmlspecialchars(strtoupper($kategori)) . '</b></td>
                  </tr>';
    }

    foreach ($params as $param) {
        $html .= '
            <tr>
                <td>' . $no_global++ . '</td>
                <td class="text-left">' . htmlspecialchars($param['nama_parameter']) . '</td>
                <td>' . htmlspecialchars($param['satuan']) . '</td>
                <td>' . htmlspecialchars($param['kadar_maksimum']) . '</td>
                <td>' . htmlspecialchars($param['hasil']) . '</td>
                <td>' . htmlspecialchars($param['metode_uji']) . '</td>
            </tr>';
    }
}

$html .= '
        </tbody>
    </table>

    <div class="notes">
        <p>*) Persyaratan Kualitas Air Minum menurut Per.Men.Kes RI No. 2 Tahun 2023</p>
        <p>Catatan:</p>
        <ol>
            <li>Hasil Uji ini hanya berlaku untuk contoh yang diuji.</li>
            <li>Laporan Hasil Uji ini tidak boleh digandakan tanpa izin Laboratorium PERUMDA Air Minum Kota Surakarta, kecuali secara lengkap.</li>
        </ol>
    </div>

    <div class="footer-signatures">
        <p class="date-place">Surakarta, ' . date('d F Y', strtotime($master_data['tanggal_uji'])) . '</p>
        <table style="width:100%;">
            <tr>
                <td style="width:33%; text-align: left;">Diteliti<br>Manajer Perencanaan dan Pengembangan</td>
                <td style="width:33%;"></td>
                <td style="width:33%; text-align: right;">Diperiksa<br>Asisten Manajer Laboratorium</td>
            </tr>
            <tr>
                <td style="width:33%; text-align: left;"><div class="name">R. Agus Rendy, ST</div><div class="npp">NPP. 483 300 872</div></td>
                <td style="33%;"></td>
                <td style="33%; text-align: right;"><div class="name">Srl Moro, Am.D</div><div class="npp">NPP. 502 160 174</div></td>
            </tr>
        </table>
        <div style="text-align: center; margin-top: 20px;">
            <p>Mengetahui:<br>Direktur Teknik<br>PERUMDA AIR MINUM KOTA SURAKARTA</p>
            <div class="name">Toya Sarwoko Priyo Saptono, SH</div>
            <div class="npp">NPP. 450 190 269</div>
        </div>
    </div>
</body>
</html>';

$dompdf->loadHtml($html);

// (Opsional) Atur ukuran dan orientasi kertas
// Ukuran F4: 215mm x 330mm (lebar x tinggi)
$dompdf->setPaper(array(0, 0, 612.28, 935.43), 'portrait'); // Konversi mm ke point (1mm = 2.83465pt)

// Render HTML menjadi PDF
$dompdf->render();

// Output PDF ke browser
$dompdf->stream("Laporan_Hasil_Uji_" . $master_data['no_lab'] . ".pdf", array("Attachment" => false));

// Tutup koneksi database
mysqli_close($con);
?>