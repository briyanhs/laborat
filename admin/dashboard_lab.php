<!DOCTYPE html>
<?php
include '../database/database.php';
include '../config.php';
session_start(); // Pastikan session_start() ada di awal, sebelum output apapun

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

// Inisialisasi variabel untuk menghindari error jika query gagal
$total_pengujian = 0;

$query_summary = "
    SELECT SUM(total) AS total_gabungan
    FROM (
        SELECT COUNT(*) AS total FROM master_hasil_uji_bacteriology
        UNION ALL
        SELECT COUNT(*) AS total FROM master_hasil_uji
    ) AS subquery;";

$result_summary = mysqli_query($con, $query_summary);

if ($result_summary) {
    $summary_data = mysqli_fetch_assoc($result_summary);
    $total_pengujian = $summary_data['total_gabungan'] ?? 0;
}

//jml_proses
$total_proses = 0;
$query_proses = "
    SELECT SUM(jml_proses) AS total_proses
    FROM (
    SELECT COUNT(DISTINCT master_hasil_uji_bacteriology.id_m_hasil_uji) AS jml_proses
    FROM master_hasil_uji_bacteriology
    INNER JOIN hasil_uji_bacteriology ON master_hasil_uji_bacteriology.id_m_hasil_uji = hasil_uji_bacteriology.id_m_hasil_uji
    WHERE hasil_uji_bacteriology.status = 'Proses'

    UNION ALL

    SELECT COUNT(DISTINCT master_hasil_uji.id_m_hasil_uji) AS jml_proses
    FROM master_hasil_uji
    INNER JOIN hasil_uji ON master_hasil_uji.id_m_hasil_uji = hasil_uji.id_m_hasil_uji
    WHERE hasil_uji.status = 'Proses'
) AS subquery;";

$result_proses = mysqli_query($con, $query_proses);

if ($result_proses) {
    $proses_data = mysqli_fetch_assoc($result_proses);
    $total_proses = $proses_data['total_proses'] ?? 0;
}

//jml_selesai
$total_selesai = 0;
$query_selesai = "
    SELECT SUM(jml_selesai) AS total_selesai
    FROM (
    SELECT COUNT(DISTINCT master_hasil_uji_bacteriology.id_m_hasil_uji) AS jml_selesai
    FROM master_hasil_uji_bacteriology
    INNER JOIN hasil_uji_bacteriology ON master_hasil_uji_bacteriology.id_m_hasil_uji = hasil_uji_bacteriology.id_m_hasil_uji
    WHERE hasil_uji_bacteriology.status = 'Selesai'

    UNION ALL

    SELECT COUNT(DISTINCT master_hasil_uji.id_m_hasil_uji) AS jml_selesai
    FROM master_hasil_uji
    INNER JOIN hasil_uji ON master_hasil_uji.id_m_hasil_uji = hasil_uji.id_m_hasil_uji
    WHERE hasil_uji.status = 'Selesai'
) AS subquery;";

$result_selesai = mysqli_query($con, $query_selesai);

if ($result_selesai) {
    $selesai_data = mysqli_fetch_assoc($result_selesai);
    $total_selesai = $selesai_data['total_selesai'] ?? 0;
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
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                    </div>
                <?php endif; ?>
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