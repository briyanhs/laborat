<?php
include '../database/database.php';
include '../config.php';

// --- 1. SECURITY: Session Config ---
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict']);
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

// --- 2. INPUT HANDLING ---
$jenis_laporan = isset($_GET['jenis']) ? $_GET['jenis'] : null;
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

// --- 3. KONFIGURASI DINAMIS (Mapping Kolom & SQL) ---
$reportConfig = [];
$result = null;

if ($jenis_laporan) {
    if ($jenis_laporan === 'fisika') {
        $reportConfig = [
            'title' => 'Laporan Uji Fisika',
            'icon'  => 'fas fa-wind',
            'color' => 'info',
            // Definisi Kolom: 'Key Database' => 'Label Header'
            'columns' => [
                'suhu' => 'Suhu (°C)',
                'tds' => 'TDS (mg/l)',
                'kekeruhan' => 'Kekeruhan (NTU)',
                'warna' => 'Warna (TCU)',
                'bau' => 'Bau'
            ]
        ];
        // Query Fisika
        $sql = "SELECT m.wilayah, m.no_analisa, m.alamat AS sampel, m.tanggal_pengujian,
                MAX(CASE WHEN h.nama_parameter = 'Suhu' THEN h.hasil END) AS suhu,
                MAX(CASE WHEN h.nama_parameter = 'Total Dissolve Solid' THEN h.hasil END) AS tds,
                MAX(CASE WHEN h.nama_parameter = 'Kekeruhan' THEN h.hasil END) AS kekeruhan,
                MAX(CASE WHEN h.nama_parameter = 'Warna' THEN h.hasil END) AS warna,
                MAX(CASE WHEN h.nama_parameter = 'Bau' THEN h.hasil END) AS bau
                FROM master_hasil_uji m JOIN hasil_uji h ON m.id_m_hasil_uji = h.id_m_hasil_uji
                WHERE MONTH(m.tanggal_pengujian) = ? AND YEAR(m.tanggal_pengujian) = ?
                GROUP BY m.id_m_hasil_uji ORDER BY m.wilayah, m.tanggal_pengujian, m.id_m_hasil_uji";
    } elseif ($jenis_laporan === 'kimia') {
        $reportConfig = [
            'title' => 'Laporan Uji Kimia',
            'icon'  => 'fas fa-atom',
            'color' => 'warning',
            'columns' => [
                'ph' => 'pH',
                'fe' => 'Besi (Fe)',
                'mn' => 'Mangan (Mn)',
                'nitrit' => 'Nitrit (NO₂⁻)',
                'nitrat' => 'Nitrat (NO₃⁻)',
                'cr' => 'Kromium (Cr⁶⁺)',
                'cd' => 'Kadmium (Cd)',
                'arsen' => 'Arsen (As)',
                'pb' => 'Timbal (Pb)',
                'fluoride' => 'Fluoride (F)',
                'al' => 'Aluminium (Al)',
                'sisa_khlor' => 'Sisa Khlor'
            ]
        ];
        // Query Kimia
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
                GROUP BY m.id_m_hasil_uji ORDER BY m.wilayah, m.tanggal_pengujian, m.id_m_hasil_uji";
    } elseif ($jenis_laporan === 'mikrobiologi') {
        $reportConfig = [
            'title' => 'Laporan Uji Mikrobiologi',
            'icon'  => 'fas fa-flask',
            'color' => 'success',
            'columns' => [
                'tes_coliform' => 'Coliform',
                'tes_coliform_penegasan' => 'Penegasan',
                'tes_ecoli' => 'Coli Tinja',
                'ph' => 'pH',
                'sisa_khlor' => 'Sisa Khlor'
            ]
        ];
        // Query Mikrobiologi
        $sql = "SELECT m.wilayah, m.no_analisa, m.alamat AS sampel, m.tanggal_pengujian,
                MAX(CASE WHEN h.nama_parameter = 'Tes Coliform' THEN h.hasil END) AS tes_coliform,
                MAX(CASE WHEN h.nama_parameter = 'Tes Coli Tinja' THEN h.hasil END) AS tes_ecoli,
                MAX(CASE WHEN h.nama_parameter = 'pH' THEN h.hasil END) AS ph,
                MAX(CASE WHEN h.nama_parameter = 'Sisa Chlor' THEN h.hasil END) AS sisa_khlor,
                MAX(CASE WHEN h.nama_parameter = 'Tes Coliform' THEN h.penegasan END) AS tes_coliform_penegasan
                FROM master_hasil_uji_bacteriology m JOIN hasil_uji_bacteriology h ON m.id_m_hasil_uji = h.id_m_hasil_uji
                WHERE MONTH(m.tanggal_pengujian) = ? AND YEAR(m.tanggal_pengujian) = ?
                GROUP BY m.id_m_hasil_uji ORDER BY m.wilayah, m.tanggal_pengujian, m.id_m_hasil_uji";
    }

    // Eksekusi Query
    if (isset($sql)) {
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ii", $bulan, $tahun);
        $stmt->execute();
        $result = $stmt->get_result();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Hasil Uji</title>
    <link href="<?= BASE_URL ?>bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .table-laporan th,
        .table-laporan td {
            font-size: 0.85rem;
            vertical-align: middle;
            white-space: nowrap;
        }

        .wilayah-header th {
            background-color: #e9ecef !important;
            text-align: left !important;
            padding-left: 15px !important;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="d-flex" id="wrapper">
        <div class="sidebar p-2" id="sidebar-wrapper">
            <div class="sidebar-heading">LABORATORIUM<br>PDAM SURAKARTA</div>
            <a href="dashboard_lab.php"><i class="fas fa-fw fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="fisika_kimia.php"><i class="fas fa-fw fa-microscope"></i> <span>Fisika dan Kimia</span></a>
            <a href="bacteriology.php"><i class="fas fa-fw fa-flask"></i> <span>Mikrobiologi</span></a>
            <a href="laporan.php" class="active"><i class="fas fa-fw fa-archive"></i> <span>Laporan</span></a>
            <a href="pengaturan.php"><i class="fas fa-fw fa-gear"></i> <span>Pengaturan</span></a>
            <a href="<?= BASE_URL ?>logout/logout.php"><i class="fas fa-fw fa-sign-out-alt"></i> <span>Log Out</span></a>
        </div>

        <div class="flex-grow-1" id="page-content-wrapper">
            <div class="dashboard-header">
                <button class="btn btn-primary navbar-toggler-custom" id="menu-toggle"><span class="fas fa-bars"></span></button>
                <h4 class="mb-0">Laporan Hasil Uji</h4>
                <a href="<?= BASE_URL ?>logout/logout.php" class="btn btn-outline-danger d-none d-md-block">Log Out</a>
            </div>

            <div class="content-fluid mt-3">
                <?php if ($jenis_laporan == null): ?>
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Pilih Jenis Laporan</h5>
                        </div>
                        <div class="card-body text-center">
                            <p class="text-muted">Silakan pilih laporan yang ingin Anda lihat.</p>
                            <div class="d-grid gap-3 col-md-6 mx-auto mt-4">
                                <a href="laporan.php?jenis=fisika" class="btn btn-info btn-lg text-white"><i class="fas fa-wind me-2"></i> Laporan Fisika</a>
                                <a href="laporan.php?jenis=kimia" class="btn btn-warning btn-lg text-white"><i class="fas fa-atom me-2"></i> Laporan Kimia</a>
                                <a href="laporan.php?jenis=mikrobiologi" class="btn btn-success btn-lg"><i class="fas fa-flask me-2"></i> Laporan Mikrobiologi</a>
                            </div>
                        </div>
                    </div>

                <?php elseif ($jenis_laporan == 'fisika'): ?>
                    <div class="card shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Laporan Uji Fisika</h5>
                            <a href="laporan.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
                        </div>
                        <div class="card-body">
                            <form action="laporan.php" method="GET" class="row g-3 align-items-center mb-4">
                                <input type="hidden" name="jenis" value="fisika">
                                <div class="col-md-4"><label for="bulan" class="form-label">Pilih Bulan</label><select name="bulan" id="bulan" class="form-select"><?php for ($i = 1; $i <= 12; $i++): ?><option value="<?= $i; ?>" <?= ($i == $bulan) ? 'selected' : ''; ?>><?= htmlspecialchars($nama_bulan[$i]); ?></option><?php endfor; ?></select></div>
                                <div class="col-md-4"><label for="tahun" class="form-label">Tahun</label><input type="number" name="tahun" id="tahun" class="form-control" value="<?= htmlspecialchars($tahun); ?>"></div>
                                <div class="col-md-4 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Tampilkan</button></div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <a href="export_laporan.php?jenis=fisika&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" target="_blank" class="btn btn-danger w-100">
                                        <i class="fas fa-file-pdf me-2"></i>Export PDF
                                    </a>
                                </div>
                            </form>
                            <hr>
                            <h4 class="text-center mt-4">Hasil Pemeriksaan Parameter Fisika Air</h4>
                            <h5 class="text-center text-muted mb-4">Bulan: <?= htmlspecialchars($nama_bulan[$bulan]) . ' ' . htmlspecialchars($tahun); ?></h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover table-laporan">
                                    <thead class="table-dark">
                                        <tr>
                                            <th class="text-center">No.</th>
                                            <th class="text-center">No.Analisa</th>
                                            <th>Sampel</th>
                                            <th class="text-center">Tgl Uji</th>
                                            <th class="text-center">Suhu (°C)</th>
                                            <th class="text-center">TDS (mg/l)</th>
                                            <th class="text-center">Kekeruhan (NTU)</th>
                                            <th class="text-center">Warna (TCU)</th>
                                            <th class="text-center">Bau</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): $no = 1;
                                            $current_wilayah = null;
                                            while ($row = $result->fetch_assoc()): if ($row['wilayah'] != $current_wilayah): $current_wilayah = $row['wilayah']; ?><tr class="wilayah-header">
                                                        <th colspan="9"> <?= strtoupper(htmlspecialchars($current_wilayah)); ?></th>
                                                    </tr><?php endif; ?><tr>
                                                    <td class="text-center"><?= $no++; ?></td>
                                                    <td><?= htmlspecialchars($row['no_analisa']); ?></td>
                                                    <td><?= htmlspecialchars($row['sampel']); ?></td>
                                                    <td><?= date('d-m-Y', strtotime($row['tanggal_pengujian'])); ?></td>
                                                    <td><?= htmlspecialchars($row['suhu']); ?></td>
                                                    <td><?= htmlspecialchars($row['tds']); ?></td>
                                                    <td><?= htmlspecialchars($row['kekeruhan']); ?></td>
                                                    <td><?= htmlspecialchars($row['warna']); ?></td>
                                                    <td><?= htmlspecialchars($row['bau']); ?></td>
                                                </tr><?php endwhile;
                                                else: ?><tr>
                                                <td colspan="9" class="text-center">Tidak ada data untuk periode yang dipilih.</td>
                                            </tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($jenis_laporan == 'kimia'): ?>
                    <div class="card shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Laporan Uji Kimia</h5>
                            <a href="laporan.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
                        </div>
                        <div class="card-body">
                            <form action="laporan.php" method="GET" class="row g-3 align-items-center mb-4">
                                <input type="hidden" name="jenis" value="kimia">
                                <div class="col-md-4"><label for="bulan" class="form-label">Pilih Bulan</label><select name="bulan" id="bulan" class="form-select"><?php for ($i = 1; $i <= 12; $i++): ?><option value="<?= $i; ?>" <?= ($i == $bulan) ? 'selected' : ''; ?>><?= htmlspecialchars($nama_bulan[$i]); ?></option><?php endfor; ?></select></div>
                                <div class="col-md-4"><label for="tahun" class="form-label">Tahun</label><input type="number" name="tahun" id="tahun" class="form-control" value="<?= htmlspecialchars($tahun); ?>"></div>
                                <div class="col-md-4 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Tampilkan</button></div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <a href="export_laporan.php?jenis=kimia&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" target="_blank" class="btn btn-danger w-100">
                                        <i class="fas fa-file-pdf me-2"></i>Export PDF
                                    </a>
                                </div>
                            </form>
                            <hr>
                            <h4 class="text-center mt-4">Hasil Pemeriksaan Parameter Kimia Air</h4>
                            <h5 class="text-center text-muted mb-4">Bulan: <?= htmlspecialchars($nama_bulan[$bulan]) . ' ' . htmlspecialchars($tahun); ?></h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-laporan" style="font-size: 0.9rem;">
                                    <thead class="table-dark">
                                        <tr>
                                            <th class="text-center align-middle">No.</th>
                                            <th class="align-middle">Info Sampel</th>
                                            <th class="align-middle">Info Uji</th>
                                            <th class="align-middle">Hasil Utama</th>
                                            <th class="align-middle">Logam Berat Terlarut</th>
                                            <th class="align-middle">Parameter Anorganik</th>
                                            <th class="align-middle">Lain-lain</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): $no = 1;
                                            $current_wilayah = null;
                                            while ($row = $result->fetch_assoc()): if ($row['wilayah'] != $current_wilayah): $current_wilayah = $row['wilayah']; ?><tr class="wilayah-header">
                                                        <th colspan="7"><?= strtoupper(htmlspecialchars($current_wilayah)); ?></th>
                                                    </tr><?php endif; ?>
                                                <tr>
                                                    <td class="text-center align-middle"><?= $no++; ?></td>
                                                    <td style="white-space: normal;">
                                                        <strong>No. Analisa:</strong> <?= htmlspecialchars($row['no_analisa']); ?><br>
                                                        <strong>Lokasi:</strong> <?= htmlspecialchars($row['sampel']); ?>
                                                    </td>
                                                    <td class="align-middle">
                                                        <strong>Tgl:</strong> <?= date('d-m-Y', strtotime($row['tanggal_pengujian'])); ?>
                                                    </td>
                                                    <td class="align-middle">
                                                        <strong>pH:</strong> <?= htmlspecialchars($row['ph']); ?><br>
                                                        <strong>Besi (Fe):</strong> <?= htmlspecialchars($row['fe']); ?><br>
                                                        <strong>Mangan (Mn):</strong> <?= htmlspecialchars($row['mn']); ?>
                                                    </td>
                                                    <td class="align-middle">
                                                        <strong>Kromium (Cr⁶⁺):</strong> <?= htmlspecialchars($row['cr']); ?><br>
                                                        <strong>Kadmium (Cd):</strong> <?= htmlspecialchars($row['cd']); ?><br>
                                                        <strong>Arsen (As):</strong> <?= htmlspecialchars($row['arsen']); ?><br>
                                                        <strong>Timbal (Pb):</strong> <?= htmlspecialchars($row['pb']); ?>
                                                    </td>
                                                    <td class="align-middle">
                                                        <strong>Nitrit (NO₂⁻):</strong> <?= htmlspecialchars($row['nitrit']); ?><br>
                                                        <strong>Nitrat (NO₃⁻):</strong> <?= htmlspecialchars($row['nitrat']); ?><br>
                                                        <strong>Fluoride (F):</strong> <?= htmlspecialchars($row['fluoride']); ?>
                                                    </td>
                                                    <td class="align-middle">
                                                        <strong>Aluminium (Al):</strong> <?= htmlspecialchars($row['al']); ?><br>
                                                        <strong>Sisa Khlor:</strong> <?= htmlspecialchars($row['sisa_khlor']); ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile;
                                        else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Tidak ada data untuk periode yang dipilih.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($jenis_laporan == 'mikrobiologi'): ?>
                    <div class="card shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Laporan Uji Mikrobiologi</h5>
                            <a href="laporan.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
                        </div>
                        <div class="card-body">
                            <form action="laporan.php" method="GET" class="row g-3 align-items-center mb-4">
                                <input type="hidden" name="jenis" value="mikrobiologi">
                                <div class="col-md-4"><label for="bulan" class="form-label">Pilih Bulan</label><select name="bulan" id="bulan" class="form-select"><?php for ($i = 1; $i <= 12; $i++): ?><option value="<?= $i; ?>" <?= ($i == $bulan) ? 'selected' : ''; ?>><?= htmlspecialchars($nama_bulan[$i]); ?></option><?php endfor; ?></select></div>
                                <div class="col-md-4"><label for="tahun" class="form-label">Tahun</label><input type="number" name="tahun" id="tahun" class="form-control" value="<?= htmlspecialchars($tahun); ?>"></div>
                                <div class="col-md-4 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Tampilkan</button></div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <a href="export_laporan.php?jenis=mikrobiologi&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" target="_blank" class="btn btn-danger w-100">
                                        <i class="fas fa-file-pdf me-2"></i>Export PDF
                                    </a>
                                </div>
                            </form>
                            <hr>
                            <h4 class="text-center mt-4">Hasil Pemeriksaan Parameter Mikrobiologi</h4>
                            <h5 class="text-center text-muted mb-4">Bulan: <?= htmlspecialchars($nama_bulan[$bulan]) . ' ' . htmlspecialchars($tahun); ?></h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover table-laporan">
                                    <thead class="table-dark">
                                        <tr>
                                            <th class="text-center">No.</th>
                                            <th class="text-center">No.Analisa</th>
                                            <th>Sampel</th>
                                            <th class="text-center">Tgl Uji</th>
                                            <th class="text-center">Tes Coliform</th>
                                            <th class="text-center">Penegasan</th>
                                            <th class="text-center">Tes Coli Tinja</th>
                                            <th class="text-center">pH</th>
                                            <th class="text-center">Sisa Khlor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): $no = 1;
                                            $current_wilayah = null;
                                            while ($row = $result->fetch_assoc()): if ($row['wilayah'] != $current_wilayah): $current_wilayah = $row['wilayah']; ?><tr class="wilayah-header">
                                                        <th colspan="9"> <?= strtoupper(htmlspecialchars($current_wilayah)); ?></th>
                                                    </tr><?php endif; ?><tr>
                                                    <td class="text-center"><?= $no++; ?></td>
                                                    <td><?= htmlspecialchars($row['no_analisa']); ?></td>
                                                    <td><?= htmlspecialchars($row['sampel']); ?></td>
                                                    <td><?= date('d-m-Y', strtotime($row['tanggal_pengujian'])); ?></td>
                                                    <td><?= htmlspecialchars($row['tes_coliform']); ?></td>
                                                    <td><?= htmlspecialchars($row['tes_coliform_penegasan']); ?></td>
                                                    <td><?= htmlspecialchars($row['tes_ecoli']); ?></td>
                                                    <td><?= htmlspecialchars($row['ph']); ?></td>
                                                    <td><?= htmlspecialchars($row['sisa_khlor']); ?></td>
                                                </tr><?php endwhile;
                                                else: ?><tr>
                                                <td colspan="9" class="text-center">Tidak ada data untuk periode yang dipilih.</td>
                                            </tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (isset($stmt)) $stmt->close();
                $con->close(); ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= BASE_URL ?>bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#menu-toggle").click(function(e) {
                e.preventDefault();
                $("#wrapper").toggleClass("toggled");
            });
        });
    </script>
</body>

</html>