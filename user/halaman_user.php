<!DOCTYPE html>
<?php
include '../database/database.php';
include '../config.php';
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['level'] != "User") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

$user_nama = $_SESSION['nama_lengkap']; // Sesuai dengan cek_login.php
$user_id = $_SESSION['user_id'];        // Sesuai dengan cek_login.php

// Tentukan halaman mana yang aktif (Default: pending)
$view = isset($_GET['view']) ? $_GET['view'] : 'pending';

// ==================================================================================
// BAGIAN 1: LOGIKA QUERY BERDASARKAN MENU YANG DIPILIH
// ==================================================================================

if ($view == 'pending') {
    // --- QUERY DAFTAR TUNGGU (Verifikasi < 3) ---

    // Fisika Pending
    $query_fisika = "
        SELECT 
            m.id_m_hasil_uji, m.no_analisa, m.nama_pelanggan, m.jenis_sampel, m.tanggal_pengujian,
            COUNT(lv.id) as total_verifikasi,
            MAX(CASE WHEN lv.id_user_verifier = ? THEN 1 ELSE 0 END) as user_sudah_verifikasi
        FROM master_hasil_uji m
        LEFT JOIN log_verifikasi lv ON m.id_m_hasil_uji = lv.id_hasil_uji AND lv.tipe_uji = 'fisika'
        GROUP BY m.id_m_hasil_uji
        HAVING total_verifikasi < 3
        ORDER BY m.id_m_hasil_uji DESC";
    $stmt_f = mysqli_prepare($con, $query_fisika);
    mysqli_stmt_bind_param($stmt_f, "i", $user_id);
    mysqli_stmt_execute($stmt_f);
    $sql_fisika = mysqli_stmt_get_result($stmt_f);

    // Bakteriologi Pending
    $query_bakteri = "
        SELECT 
            m.id_m_hasil_uji, m.no_analisa, m.nama_pelanggan, m.jenis_sampel, m.tanggal_pengujian,
            COUNT(lv.id) as total_verifikasi,
            MAX(CASE WHEN lv.id_user_verifier = ? THEN 1 ELSE 0 END) as user_sudah_verifikasi
        FROM master_hasil_uji_bacteriology m
        LEFT JOIN log_verifikasi lv ON m.id_m_hasil_uji = lv.id_hasil_uji AND lv.tipe_uji = 'bakteri'
        GROUP BY m.id_m_hasil_uji
        HAVING total_verifikasi < 3
        ORDER BY m.id_m_hasil_uji DESC";
    $stmt_b = mysqli_prepare($con, $query_bakteri);
    mysqli_stmt_bind_param($stmt_b, "i", $user_id);
    mysqli_stmt_execute($stmt_b);
    $sql_bakteri = mysqli_stmt_get_result($stmt_b);
} else {
    // --- QUERY DATA SELESAI (Verifikasi >= 3) ---

    // Fisika Selesai
    $query_fisika_selesai = "
        SELECT 
            m.id_m_hasil_uji, m.no_analisa, m.nama_pelanggan, m.jenis_sampel, m.tanggal_pengujian,
            COUNT(lv.id) as total_verifikasi
        FROM master_hasil_uji m
        LEFT JOIN log_verifikasi lv ON m.id_m_hasil_uji = lv.id_hasil_uji AND lv.tipe_uji = 'fisika'
        GROUP BY m.id_m_hasil_uji
        HAVING total_verifikasi >= 3
        ORDER BY m.id_m_hasil_uji DESC";
    $sql_fisika = mysqli_query($con, $query_fisika_selesai);

    // Bakteriologi Selesai
    $query_bakteri_selesai = "
        SELECT 
            m.id_m_hasil_uji, m.no_analisa, m.nama_pelanggan, m.jenis_sampel, m.tanggal_pengujian,
            COUNT(lv.id) as total_verifikasi
        FROM master_hasil_uji_bacteriology m
        LEFT JOIN log_verifikasi lv ON m.id_m_hasil_uji = lv.id_hasil_uji AND lv.tipe_uji = 'bakteri'
        GROUP BY m.id_m_hasil_uji
        HAVING total_verifikasi >= 3
        ORDER BY m.id_m_hasil_uji DESC";
    $sql_bakteri = mysqli_query($con, $query_bakteri_selesai);
}

$message = '';
$alertType = 'success';
if (isset($_GET['pesan'])) {
    if ($_GET['pesan'] == 'verif_sukses') {
        $message = '✅ Data berhasil diverifikasi.';
    }
    if ($_GET['pesan'] == 'verif_gagal') {
        $message = '❌ Anda sudah pernah memverifikasi data ini.';
        $alertType = 'danger';
    }
}
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Halaman Verifikasi User</title>
    <link href="<?= BASE_URL ?>bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?= BASE_URL ?>datatables/datatables.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <link href="style.css" rel="stylesheet">
</head>

<body>
    <div class="d-flex" id="wrapper">
        <div class="sidebar p-2" id="sidebar-wrapper">
            <div class="sidebar-heading">
                VERIFIKATOR<br>PDAM SURAKARTA
            </div>

            <a href="halaman_user.php?view=pending" class="<?= ($view == 'pending') ? 'active' : '' ?>">
                <i class="fas fa-fw fa-hourglass-half"></i> <span>Daftar Tunggu</span>
            </a>

            <a href="halaman_user.php?view=selesai" class="<?= ($view == 'selesai') ? 'active' : '' ?>">
                <i class="fas fa-fw fa-check-double"></i> <span>Arsip Selesai</span>
            </a>

            <a href="laporan.php" class="">
                <i class="fas fa-fw fa-archive"></i> <span>Laporan</span>
            </a>
            <a href="<?= BASE_URL ?>logout/logout.php"><i class="fas fa-fw fa-sign-out-alt"></i> <span>Log Out</span></a>
        </div>

        <div class="flex-grow-1" id="page-content-wrapper">
            <div class="dashboard-header">
                <button class="btn btn-primary navbar-toggler-custom" id="menu-toggle">
                    <span class="fas fa-bars"></span>
                </button>
                <h4 class="mb-0">Selamat Datang, <?php echo htmlspecialchars($user_nama); ?></h4>
                <a href="<?= BASE_URL ?>logout/logout.php" class="btn btn-outline-danger d-none d-md-block">Log Out</a>
            </div>

            <div class="content-fluid mt-3">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($view == 'pending'): ?>
                    <h4 class="mb-4 border-bottom pb-2">Daftar Tunggu Verifikasi</h4>

                    <h5 class="text-primary">Fisika & Kimia</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th>No Analisa</th>
                                    <th>Pelanggan</th>
                                    <th>Tanggal Uji</th>
                                    <th>Progress</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($r = mysqli_fetch_assoc($sql_fisika)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['no_analisa']); ?></td>
                                        <td><?php echo htmlspecialchars($r['nama_pelanggan']); ?></td>
                                        <td><?php echo htmlspecialchars($r['tanggal_pengujian']); ?></td>
                                        <td><span class="badge bg-warning text-dark"><?= $r['total_verifikasi'] ?> / 3</span></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>admin/generate_pdf.php?id_m_hasil_uji=<?= $r['id_m_hasil_uji']; ?>" target="_blank" class="btn btn-info btn-sm"><i class="fa fa-eye"></i> PDF</a>
                                            <?php if ($r['user_sudah_verifikasi']): ?>
                                                <button class="btn btn-success btn-sm" disabled><i class="fa fa-check"></i> Sudah</button>
                                            <?php else: ?>
                                                <button class="btn btn-primary btn-sm btn-verif" data-id="<?= $r['id_m_hasil_uji']; ?>" data-tipe="fisika" data-bs-toggle="modal" data-bs-target="#modalVerif"><i class="fa fa-pencil"></i> Verifikasi</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php if (mysqli_num_rows($sql_fisika) == 0) echo '<tr><td colspan="5" class="text-center text-muted">Tidak ada data pending.</td></tr>'; ?>
                            </tbody>
                        </table>
                    </div>

                    <h5 class="text-primary mt-4">Mikrobiologi</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th>No Analisa</th>
                                    <th>Pelanggan</th>
                                    <th>Tanggal Uji</th>
                                    <th>Progress</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($r = mysqli_fetch_assoc($sql_bakteri)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['no_analisa']); ?></td>
                                        <td><?php echo htmlspecialchars($r['nama_pelanggan']); ?></td>
                                        <td><?php echo htmlspecialchars($r['tanggal_pengujian']); ?></td>
                                        <td><span class="badge bg-warning text-dark"><?= $r['total_verifikasi'] ?> / 3</span></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>admin/generate_pdf_bacteriology.php?id_m_hasil_uji=<?= $r['id_m_hasil_uji']; ?>" target="_blank" class="btn btn-info btn-sm"><i class="fa fa-eye"></i> PDF</a>
                                            <?php if ($r['user_sudah_verifikasi']): ?>
                                                <button class="btn btn-success btn-sm" disabled><i class="fa fa-check"></i> Sudah</button>
                                            <?php else: ?>
                                                <button class="btn btn-primary btn-sm btn-verif" data-id="<?= $r['id_m_hasil_uji']; ?>" data-tipe="bakteri" data-bs-toggle="modal" data-bs-target="#modalVerif"><i class="fa fa-pencil"></i> Verifikasi</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php if (mysqli_num_rows($sql_bakteri) == 0) echo '<tr><td colspan="5" class="text-center text-muted">Tidak ada data pending.</td></tr>'; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else: ?>
                    <h4 class="mb-4 border-bottom pb-2">Arsip Data Terverifikasi Selesai</h4>

                    <h5 class="text-success">Fisika & Kimia</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-hover" id="table-fisika-selesai">
                            <thead class="table-success">
                                <tr>
                                    <th>No Analisa</th>
                                    <th>Pelanggan</th>
                                    <th>Tanggal Uji</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($r = mysqli_fetch_assoc($sql_fisika)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['no_analisa']); ?></td>
                                        <td><?php echo htmlspecialchars($r['nama_pelanggan']); ?></td>
                                        <td><?php echo htmlspecialchars($r['tanggal_pengujian']); ?></td>
                                        <td><span class="badge bg-success">Selesai (3/3)</span></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>admin/generate_pdf.php?id_m_hasil_uji=<?= $r['id_m_hasil_uji']; ?>" target="_blank" class="btn btn-outline-success btn-sm"><i class="fa fa-print"></i> Cetak PDF Final</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <h5 class="text-success mt-4">Mikrobiologi</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="table-bakteri-selesai">
                            <thead class="table-success">
                                <tr>
                                    <th>No Analisa</th>
                                    <th>Pelanggan</th>
                                    <th>Tanggal Uji</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($r = mysqli_fetch_assoc($sql_bakteri)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['no_analisa']); ?></td>
                                        <td><?php echo htmlspecialchars($r['nama_pelanggan']); ?></td>
                                        <td><?php echo htmlspecialchars($r['tanggal_pengujian']); ?></td>
                                        <td><span class="badge bg-success">Selesai (3/3)</span></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>admin/generate_pdf_bacteriology.php?id_m_hasil_uji=<?= $r['id_m_hasil_uji']; ?>" target="_blank" class="btn btn-outline-success btn-sm"><i class="fa fa-print"></i> Cetak PDF Final</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalVerif" tabindex="-1">
        <div class="modal-dialog">
            <form action="proses_verifikasi.php" method="POST">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Konfirmasi Verifikasi</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin memverifikasi data ini? <br>
                            <small class="text-muted">Tindakan ini akan mencatat nama Anda sebagai penanda tangan dokumen ini.</small>
                        </p>
                        <input type="hidden" name="id_m_hasil_uji" id="verif_id">
                        <input type="hidden" name="tipe" id="verif_tipe">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Ya, Verifikasi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= BASE_URL ?>bootstrap/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>datatables/datatables.js"></script>
    <script>
        $(document).ready(function() {
            $("#menu-toggle").click(function(e) {
                e.preventDefault();
                $("#wrapper").toggleClass("toggled");
            });

            $('.btn-verif').click(function() {
                $('#verif_id').val($(this).data('id'));
                $('#verif_tipe').val($(this).data('tipe'));
            });

            // Aktifkan Datatable hanya jika tabel arsip ada (view=selesai)
            if ($('#table-fisika-selesai').length) {
                $('#table-fisika-selesai, #table-bakteri-selesai').DataTable({
                    "pageLength": 10,
                    "searching": true,
                    "ordering": true,
                    "language": {
                        "search": "Cari Arsip:",
                        "zeroRecords": "Tidak ada arsip yang ditemukan"
                    }
                });
            }
            if (window.history.replaceState) {
                const url = new URL(window.location.href);
                if (url.searchParams.has('pesan')) {
                    url.searchParams.delete('pesan');
                    window.history.replaceState(null, '', url.toString());
                }
            }
        });
    </script>
</body>

</html>
<?php mysqli_close($con); ?>