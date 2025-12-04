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

// --- 1. VALIDASI INPUT ---
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('n');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// Daftar jenis laporan yang valid
$valid_jenis = ['fisika', 'kimia', 'mikrobiologi'];
if (!in_array($jenis, $valid_jenis)) {
    die("Jenis laporan tidak valid atau tidak dipilih.");
}

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

// --- 2. KONFIGURASI KOLOM & QUERY DINAMIS ---
// Kita gunakan array ini untuk mengatur judul, query select, dan header tabel
// agar tidak perlu banyak if/else di bagian HTML.

$config = [];

if ($jenis === 'fisika') {
    $config['judul'] = "Laporan Pengujian Fisika";
    // Format: 'Kunci DB' => 'Label Header HTML'
    $config['columns'] = [
        'suhu'      => 'Suhu (°C)',
        'tds'       => 'TDS (mg/l)',
        'kekeruhan' => 'Kekeruhan (NTU)',
        'warna'     => 'Warna (TCU)',
        'bau'       => 'Bau'
    ];
    $select_query = "
        MAX(CASE WHEN h.nama_parameter = 'Suhu' THEN h.hasil END) AS suhu,
        MAX(CASE WHEN h.nama_parameter = 'Total Dissolve Solid' THEN h.hasil END) AS tds,
        MAX(CASE WHEN h.nama_parameter = 'Kekeruhan' THEN h.hasil END) AS kekeruhan,
        MAX(CASE WHEN h.nama_parameter = 'Warna' THEN h.hasil END) AS warna,
        MAX(CASE WHEN h.nama_parameter = 'Bau' THEN h.hasil END) AS bau
    ";
    $table_source = "master_hasil_uji m JOIN hasil_uji h ON m.id_m_hasil_uji = h.id_m_hasil_uji";
} elseif ($jenis === 'kimia') {
    $config['judul'] = "Laporan Pengujian Kimia";
    $config['columns'] = [
        'ph'         => 'pH',
        'fe'         => 'Fe',
        'mn'         => 'Mn',
        'nitrit'     => 'NO<sub>2</sub><sup>-</sup>',
        'nitrat'     => 'NO<sub>3</sub><sup>-</sup>',
        'cr'         => 'Cr<sup>6+</sup>',
        'cd'         => 'Cd',
        'arsen'      => 'As',
        'pb'         => 'Pb',
        'fluoride'   => 'F',
        'al'         => 'Al',
        'sisa_khlor' => 'Sisa Khlor'
    ];
    $select_query = "
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
    ";
    $table_source = "master_hasil_uji m JOIN hasil_uji h ON m.id_m_hasil_uji = h.id_m_hasil_uji";
} elseif ($jenis === 'mikrobiologi') {
    $config['judul'] = "Laporan Pengujian Mikrobiologi";
    $config['columns'] = [
        'tes_coliform'           => 'Coliform',
        'tes_coliform_penegasan' => 'Penegasan',
        'tes_ecoli'              => 'Coli Tinja',
        'ph'                     => 'pH',
        'sisa_khlor'             => 'Sisa Khlor'
    ];
    $select_query = "
        MAX(CASE WHEN h.nama_parameter = 'Tes Coliform' THEN h.hasil END) AS tes_coliform,
        MAX(CASE WHEN h.nama_parameter = 'Tes Coli Tinja' THEN h.hasil END) AS tes_ecoli,
        MAX(CASE WHEN h.nama_parameter = 'pH' THEN h.hasil END) AS ph,
        MAX(CASE WHEN h.nama_parameter = 'Sisa Chlor' THEN h.hasil END) AS sisa_khlor,
        MAX(CASE WHEN h.nama_parameter = 'Tes Coliform' THEN h.penegasan END) AS tes_coliform_penegasan
    ";
    $table_source = "master_hasil_uji_bacteriology m JOIN hasil_uji_bacteriology h ON m.id_m_hasil_uji = h.id_m_hasil_uji";
}

// --- 3. EKSEKUSI QUERY ---
$sql = "SELECT 
            m.wilayah, 
            m.no_analisa, 
            m.alamat AS sampel, 
            m.tanggal_pengujian,
            $select_query
        FROM $table_source
        WHERE MONTH(m.tanggal_pengujian) = ? AND YEAR(m.tanggal_pengujian) = ?
        GROUP BY m.id_m_hasil_uji 
        ORDER BY m.wilayah ASC, m.tanggal_pengujian ASC";

$stmt = $con->prepare($sql);
$stmt->bind_param("ii", $bulan, $tahun);
$stmt->execute();
$result = $stmt->get_result();

// Hitung colspan untuk baris Wilayah (No + No Analisa + Sampel + Tgl + Jumlah Kolom Data)
$total_columns = 4 + count($config['columns']);

// --- 4. OUTPUT HTML ---
ob_start();
?>
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?= $config['judul'] ?></title>
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
            /* Penting untuk Dompdf: Header tabel berulang di halaman baru */
            page-break-inside: auto;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
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
            padding-left: 10px;
        }

        /* Helper untuk simbol kimia */
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
    <h4><?= strtoupper($config['judul']) ?></h4>
    <p style="text-align: center;">Periode: <?= $nama_bulan[$bulan] . ' ' . $tahun ?></p>

    <table>
        <thead>
            <tr>
                <th width="5%">No.</th>
                <th width="12%">No. Analisa</th>
                <th width="20%">Sampel / Lokasi</th>
                <th width="10%">Tgl Uji</th>

                <?php foreach ($config['columns'] as $db_key => $header_label): ?>
                    <th><?= $header_label ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php
                $no = 1;
                $current_wilayah = null;
                while ($row = $result->fetch_assoc()):
                ?>
                    <?php if ($row['wilayah'] != $current_wilayah):
                        $current_wilayah = $row['wilayah'];
                    ?>
                        <tr>
                            <td colspan="<?= $total_columns ?>" class="wilayah-row">
                                WILAYAH: <?= strtoupper(htmlspecialchars($current_wilayah)) ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['no_analisa']) ?></td>
                        <td class="text-left"><?= htmlspecialchars($row['sampel']) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal_pengujian'])) ?></td>

                        <?php foreach ($config['columns'] as $db_key => $header_label): ?>
                            <td>
                                <?= isset($row[$db_key]) ? htmlspecialchars($row[$db_key]) : '-' ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $total_columns ?>" style="text-align: center; padding: 20px;">
                        Data tidak ditemukan untuk periode ini.
                    </td>
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
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("Laporan_" . ucfirst($jenis) . "_" . $nama_bulan[$bulan] . "_" . $tahun . ".pdf", array("Attachment" => 0));
?>