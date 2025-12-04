<!DOCTYPE html>
<?php
include '../database/database.php';
include '../config.php';

// --- SECURITY FIX: Pengaturan Cookie Session ---
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

// Inisialisasi variabel default
$total_pengujian = 0;
$total_proses = 0;
$total_selesai = 0;

// --- OPTIMASI QUERY DATABASE ---
// Menggabungkan 3 query terpisah menjadi 1 query tunggal agar database tidak berat.
// Logika: Kita mengambil semua ID unik dari kedua tabel (Bakteri & Fisika/Kimia) beserta statusnya.
// Kemudian kita hitung jumlahnya menggunakan Conditional Aggregation (SUM CASE WHEN).

$query_dashboard = "
    SELECT 
        COUNT(*) as total_gabungan,
        SUM(CASE WHEN status = 'Proses' THEN 1 ELSE 0 END) as total_proses,
        SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as total_selesai
    FROM (
        -- Ambil Data Bakteriologi (Distinct ID agar tidak double count jika parameter banyak)
        SELECT m.id_m_hasil_uji, h.status
        FROM master_hasil_uji_bacteriology m
        JOIN hasil_uji_bacteriology h ON m.id_m_hasil_uji = h.id_m_hasil_uji
        GROUP BY m.id_m_hasil_uji, h.status

        UNION ALL

        -- Ambil Data Fisika Kimia
        SELECT m.id_m_hasil_uji, h.status
        FROM master_hasil_uji m
        JOIN hasil_uji h ON m.id_m_hasil_uji = h.id_m_hasil_uji
        GROUP BY m.id_m_hasil_uji, h.status
    ) AS combined_data
";

$result = mysqli_query($con, $query_dashboard);

if ($result) {
    $data = mysqli_fetch_assoc($result);
    // Casting ke int untuk keamanan tipe data
    $total_pengujian = (int) ($data['total_gabungan'] ?? 0);
    $total_proses    = (int) ($data['total_proses'] ?? 0);
    $total_selesai   = (int) ($data['total_selesai'] ?? 0);
} else {
    // Log error di server jika query gagal (User tidak perlu melihat detail error SQL)
    error_log("Dashboard Query Error: " . mysqli_error($con));
}
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link href="<?= BASE_URL ?>bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?= BASE_URL ?>datatables/datatables.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <link href="<?= BASE_URL ?>admin/style.css" rel="stylesheet">
</head>

<body>
    <div class="d-flex" id="wrapper">
        <div class="sidebar p-2" id="sidebar-wrapper">
            <div class="sidebar-heading">
                LABORATORIUM<br>PDAM SURAKARTA
            </div>
            <a href="dashboard_lab.php" class="active"><i class="fas fa-fw fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="fisika_kimia.php"><i class="fas fa-fw fa-microscope"></i> <span>Fisika dan Kimia</span></a>
            <a href="bacteriology.php"><i class="fas fa-fw fa-flask"></i> <span>Mikrobiologi</span></a>
            <a href="laporan.php"><i class="fas fa-fw fa-archive"></i> <span>Laporan</span></a>
            <a href="pengaturan.php"><i class="fas fa-fw fa-gear"></i> <span>Pengaturan</span></a>
            <a href="<?= BASE_URL ?>logout/logout.php"><i class="fas fa-fw fa-sign-out-alt"></i> <span>Log Out</span></a>
        </div>
        <div class="flex-grow-1" id="page-content-wrapper">
            <div class="dashboard-header">
                <button class="btn btn-primary navbar-toggler-custom" id="menu-toggle">
                    <span class="fas fa-bars"></span>
                </button>
                <h4 class="mb-0">Laboratory Dashboard</h4>
                <a href="<?= BASE_URL ?>logout/logout.php" class="btn btn-outline-danger d-none d-md-block">Log Out</a>
            </div>

            <div class="content-fluid mt-3">
                <div class="row">
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card dashboard-card-hover text-white bg-primary bg-gradient shadow-sm rounded-4 h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-white-75 small text-uppercase fw-bold">Total Pengujian</div>
                                    <div class="h3 fw-bold mb-0"><?= $total_pengujian ?></div>
                                </div>
                                <i class="fas fa-flask fa-3x text-white-50"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card dashboard-card-hover text-white bg-warning bg-gradient shadow-sm rounded-4 h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-white-75 small text-uppercase fw-bold">Status Proses</div>
                                    <div class="h3 fw-bold mb-0"><?= $total_proses ?></div>
                                </div>
                                <i class="fas fa-hourglass-half fa-3x text-white-50"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card dashboard-card-hover text-white bg-success bg-gradient shadow-sm rounded-4 h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-white-75 small text-uppercase fw-bold">Status Selesai</div>
                                    <div class="h3 fw-bold mb-0"><?= $total_selesai ?></div>
                                </div>
                                <i class="fas fa-check-circle fa-3x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= BASE_URL ?>bootstrap/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>datatables/datatables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Toggle sidebar
            $("#menu-toggle").click(function(e) {
                e.preventDefault();
                $("#wrapper").toggleClass("toggled");
            });
        });
    </script>

</body>

</html>