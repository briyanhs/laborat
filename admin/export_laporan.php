<?php
require_once '../vendor/autoload.php';
include '../database/database.php';
include '../config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    exit("Akses ditolak.");
}

// Ambil parameter dari URL
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : null;
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('n');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

$nama_bulan = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];

if (!$jenis) {
    die("Jenis laporan tidak dipilih.");
}

// --- QUERY DATA ---
$result = null;
$judul_laporan = "";

if ($jenis === 'fisika') {
    $judul_laporan = "Laporan Pengujian Fisika";
    $sql = "SELECT m.wilayah, m.no_analisa, m.alamat AS sampel, m.tanggal_pengujian,
            MAX(CASE WHEN h.nama_parameter = 'Suhu' THEN h.hasil END) AS suhu,
            MAX(CASE WHEN h.nama_parameter = 'Total Dissolve Solid' THEN h.hasil END) AS tds,
            MAX(CASE WHEN h.nama_parameter = 'Kekeruhan' THEN h.hasil END) AS kekeruhan,
            MAX(CASE WHEN h.nama_parameter = 'Warna' THEN h.hasil END) AS warna,
            MAX(CASE WHEN h.nama_parameter = 'Bau' THEN h.hasil END) AS bau
            FROM master_hasil_uji m JOIN hasil_uji h ON m.id_m_hasil_uji = h.id_m_hasil_uji
            WHERE MONTH(m.tanggal_pengujian) = ? AND YEAR(m.tanggal_pengujian) = ?
            GROUP BY m.id_m_hasil_uji ORDER BY m.wilayah, m.tanggal_pengujian";
} elseif ($jenis === 'kimia') {
    $judul_laporan = "Laporan Pengujian Kimia";
    $sql = "SELECT m.wilayah, m.no_analisa, m.alamat AS sampel, m.tanggal_pengujian,
            MAX(CASE WHEN h.nama_parameter = 'pH' THEN h.hasil END) AS ph,
            MAX(CASE WHEN h.nama_parameter = 'Besi (Fe)' THEN h.hasil END) AS fe,
            MAX(CASE WHEN h.nama_parameter = 'Mangan (Mn)' THEN h.hasil END) AS mn,
            MAX(CASE WHEN h.nama_parameter = 'Nitrit (NO₂⁻) terlarut' THEN h.hasil END) AS nitrit,
            MAX(CASE WHEN h.nama_parameter = 'Nitrat (NO₃⁻) terlarut' THEN h.hasil END) AS nitrat,
            MAX(CASE WHEN h.nama_parameter = 'Kromium Valensi 6 (Cr⁶⁺) terlarut' THEN h.hasil END) AS cr,
            MAX(CASE WHEN h.nama_parameter = 'Kadmium (Cd) terlarut' THEN h.hasil END) AS cd,
            MAX(CASE WHEN h.nama_parameter = 'Arsen (As) terlarut' THEN h.hasil END) AS arsen,
            MAX(CASE WHEN h.nama_parameter = 'Timbal (Pb) terlarut' THEN h.hasil END) AS pb,
            MAX(CASE WHEN h.nama_parameter = 'Fluoride (F) terlarut' THEN h.hasil END) AS fluoride,
            MAX(CASE WHEN h.nama_parameter = 'Aluminium (Al) terlarut' THEN h.hasil END) AS al,
            MAX(CASE WHEN h.nama_parameter = 'Sisa Khlor' THEN h.hasil END) AS sisa_khlor
            FROM master_hasil_uji m JOIN hasil_uji h ON m.id_m_hasil_uji = h.id_m_hasil_uji
            WHERE MONTH(m.tanggal_pengujian) = ? AND YEAR(m.tanggal_pengujian) = ?
            GROUP BY m.id_m_hasil_uji ORDER BY m.wilayah, m.tanggal_pengujian";
} elseif ($jenis === 'mikrobiologi') {
    $judul_laporan = "Laporan Pengujian Mikrobiologi";
    $sql = "SELECT m.wilayah, m.no_analisa, m.alamat AS sampel, m.tanggal_pengujian,
            MAX(CASE WHEN h.nama_parameter = 'Tes Coliform' THEN h.hasil END) AS tes_coliform,
            MAX(CASE WHEN h.nama_parameter = 'Tes Coli Tinja' THEN h.hasil END) AS tes_ecoli,
            MAX(CASE WHEN h.nama_parameter = 'pH' THEN h.hasil END) AS ph,
            MAX(CASE WHEN h.nama_parameter = 'Sisa Chlor' THEN h.hasil END) AS sisa_khlor,
            MAX(CASE WHEN h.nama_parameter = 'Tes Coliform' THEN h.penegasan END) AS tes_coliform_penegasan
            FROM master_hasil_uji_bacteriology m JOIN hasil_uji_bacteriology h ON m.id_m_hasil_uji = h.id_m_hasil_uji
            WHERE MONTH(m.tanggal_pengujian) = ? AND YEAR(m.tanggal_pengujian) = ?
            GROUP BY m.id_m_hasil_uji ORDER BY m.wilayah, m.tanggal_pengujian";
}

$stmt = $con->prepare($sql);
$stmt->bind_param("ii", $bulan, $tahun);
$stmt->execute();
$result = $stmt->get_result();

// --- MULAI MEMBUAT HTML PDF ---
ob_start();
?>
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?= $judul_laporan ?></title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10pt;
        }

        h3,
        h4 {
            text-align: center;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            font-size: 8pt;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #f2f2f2;
        }

        .text-left {
            text-align: left;
        }

        .wilayah-row {
            background-color: #e0e0e0;
            font-weight: bold;
            text-align: left;
        }

        /* Perbaikan: Style khusus untuk simbol kimia agar rapi */
        sup {
            font-size: 0.7em;
            vertical-align: super;
        }

        sub {
            font-size: 0.7em;
            vertical-align: sub;
        }
    </style>
</head>

<body>
    <h3>LABORATORIUM PDAM SURAKARTA</h3>
    <h4><?= strtoupper($judul_laporan) ?></h4>
    <p style="text-align: center;">Periode: <?= $nama_bulan[$bulan] . ' ' . $tahun ?></p>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>No. Analisa</th>
                <th>Sampel / Lokasi</th>
                <th>Tgl Uji</th>
                <?php if ($jenis == 'fisika'): ?>
                    <th>Suhu (°C)</th>
                    <th>TDS (mg/l)</th>
                    <th>Kekeruhan (NTU)</th>
                    <th>Warna (TCU)</th>
                    <th>Bau</th>
                <?php elseif ($jenis == 'kimia'): ?>
                    <th>pH</th>
                    <th>Fe</th>
                    <th>Mn</th>
                    <th>NO<sub>2</sub><sup>-</sup></th>
                    <th>NO<sub>3</sub><sup>-</sup></th>
                    <th>Cr<sup>6+</sup></th>
                    <th>Cd</th>
                    <th>As</th>
                    <th>Pb</th>
                    <th>F</th>
                    <th>Al</th>
                    <th>Sisa Khlor</th>
                <?php elseif ($jenis == 'mikrobiologi'): ?>
                    <th>Coliform</th>
                    <th>Penegasan</th>
                    <th>Coli Tinja</th>
                    <th>pH</th>
                    <th>Sisa Khlor</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0):
                $no = 1;
                $current_wilayah = null;
                while ($row = $result->fetch_assoc()):
                    if ($row['wilayah'] != $current_wilayah):
                        $current_wilayah = $row['wilayah'];
                        $colspan = ($jenis == 'kimia') ? 16 : (($jenis == 'fisika') ? 9 : 9);
            ?>
                        <tr>
                            <td colspan="<?= $colspan ?>" class="wilayah-row text-left">WILAYAH: <?= strtoupper($current_wilayah) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['no_analisa']) ?></td>
                        <td class="text-left"><?= htmlspecialchars($row['sampel']) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal_pengujian'])) ?></td>

                        <?php if ($jenis == 'fisika'): ?>
                            <td><?= $row['suhu'] ?></td>
                            <td><?= $row['tds'] ?></td>
                            <td><?= $row['kekeruhan'] ?></td>
                            <td><?= $row['warna'] ?></td>
                            <td><?= $row['bau'] ?></td>
                        <?php elseif ($jenis == 'kimia'): ?>
                            <td><?= $row['ph'] ?></td>
                            <td><?= $row['fe'] ?></td>
                            <td><?= $row['mn'] ?></td>
                            <td><?= $row['nitrit'] ?></td>
                            <td><?= $row['nitrat'] ?></td>
                            <td><?= $row['cr'] ?></td>
                            <td><?= $row['cd'] ?></td>
                            <td><?= $row['arsen'] ?></td>
                            <td><?= $row['pb'] ?></td>
                            <td><?= $row['fluoride'] ?></td>
                            <td><?= $row['al'] ?></td>
                            <td><?= $row['sisa_khlor'] ?></td>
                        <?php elseif ($jenis == 'mikrobiologi'): ?>
                            <td><?= $row['tes_coliform'] ?></td>
                            <td><?= $row['tes_coliform_penegasan'] ?></td>
                            <td><?= $row['tes_ecoli'] ?></td>
                            <td><?= $row['ph'] ?></td>
                            <td><?= $row['sisa_khlor'] ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="20">Tidak ada data.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>
<?php
$html = ob_get_clean();
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
// Set kertas Landscape agar tabel muat
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("Laporan_" . ucfirst($jenis) . "_" . $nama_bulan[$bulan] . "_" . $tahun . ".pdf", array("Attachment" => 0));
?>