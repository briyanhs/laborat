<!DOCTYPE html>
<?php include '../database/database.php';
include '../config.php';
?>
<?php
$message = '';
$alertType = 'success';

if (isset($_GET['pesan'])) {
    switch ($_GET['pesan']) {
        case 'sukses_tambah':
            $message = '✅ Data berhasil ditambahkan.';
            break;
        case 'sukses_edit':
            $message = '✏️ Data berhasil diperbarui.';
            break;
        case 'sukses_hapus':
            $message = '🗑️ Data berhasil dihapus.';
            break;
        case 'gagal':
            $alertType = 'danger';
            $message = '❌ Terjadi kesalahan saat memproses data.';
            break;
    }
}
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lab Dashboard</title>
    <link href="<?= BASE_URL ?>bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>lab/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>fontawesome/css/font-awesome.min.css">
    <!--datatables-->
    <link rel="stylesheet" type="text/css" href="<?= BASE_URL ?>datatables/datatables.css" />
    <script type="text/javascript" src="<?= BASE_URL ?>datatables/datatables.js"></script>


</head>

<body>
    <?php
    session_start();
    if ($_SESSION['status'] != "login") {
        header("location:../index.php?pesan=belum_login");
    }
    ?>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar p-2">
            <div class="logo">
                LABORATORIUM<br>PDAM SURAKARTA
            </div>
            <div class="logo-line"></div>
            <a href="dashboard_lab.php">Dashboard</a>
            <a href="laporan.php">Laporan</a>

        </div>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <!-- Header -->
            <div class="dashboard-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Laboratory Dashboard</h4>
                <div>
                    <a href="<?= BASE_URL ?>logout/logout.php" class="btn btn-outline-danger">Log Out</a>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="container-fluid mt-3">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card shadow card-lab p-2">
                            <h5>Total Samples</h5>
                            <h2>245</h2>
                            <p class="text-muted">Updated 2 hours ago</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow card-lab p-2">
                            <h5>In Progress</h5>
                            <h2>58</h2>
                            <p class="text-muted">Running Tests</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow card-lab p-2">
                            <h5>Completed</h5>
                            <h2>187</h2>
                            <p class="text-muted">Reports Sent</p>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Activity</h5>
                    <!-- Tombol Tambah Data -->
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        Tambah Data
                    </button>


                </div>
                <table id="tabelLab" class="table table-striped table-bordered nowrap" style="width:100%">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>No Lab</th>
                            <th>Pemohon</th>
                            <th>Status</th>
                            <th>Alamat</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php
                        $query = "SELECT * FROM lap_hasil_uji";
                        $sql = mysqli_query($con, $query);
                        $no = 0;
                        ?>
                        <?php
                        while ($result = mysqli_fetch_assoc($sql)) {

                        ?>
                            <tr>
                                <td><?php echo $result['id_hasil_uji']; ?></td>
                                <td><?php echo $result['no_lab']; ?></td>
                                <td><?php echo $result['pemohon']; ?></td>
                                <td><?php echo $result['status']; ?></td>
                                <td><?php echo $result['alamat']; ?></td>
                                <td><?php echo $result['tgl_lap']; ?></td>
                                <td>
                                    <!-- Tombol Edit -->
                                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $result['id_hasil_uji'] ?>">
                                        <i class="fa fa-pencil"></i>
                                    </button>

                                    <!-- Tombol Hapus -->
                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalHapus<?= $result['id_hasil_uji'] ?>">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>

                            </tr>
                            <!-- Modal Edit -->
                            <div class="modal fade" id="modalEdit<?= $result['id_hasil_uji'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="<?= BASE_URL ?>lab/proses_edit.php" method="POST">
                                        <input type="hidden" name="id_hasil_uji" value="<?= $result['id_hasil_uji'] ?>">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Data - No Lab <?= $result['no_lab'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label>No Lab</label>
                                                    <input type="text" name="no_lab" class="form-control" value="<?= $result['no_lab'] ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label>Pemohon</label>
                                                    <input type="text" name="pemohon" class="form-control" value="<?= $result['pemohon'] ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label>Status</label>
                                                    <select name="status" class="form-control" required>
                                                        <option value="In Progress" <?= $result['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                        <option value="Completed" <?= $result['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label>Alamat</label>
                                                    <input type="text" name="alamat" class="form-control" value="<?= $result['alamat'] ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label>Tanggal</label>
                                                    <input type="date" name="tgl_lap" class="form-control" value="<?= $result['tgl_lap'] ?>" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">Simpan</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <!-- Modal Hapus -->
                            <div class="modal fade" id="modalHapus<?= $result['id_hasil_uji'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="<?= BASE_URL ?>lab/proses_hapus.php" method="POST">
                                        <input type="hidden" name="id_hasil_uji" value="<?= $result['id_hasil_uji'] ?>">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Konfirmasi Hapus</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Yakin ingin menghapus data dengan No Lab <strong><?= $result['no_lab'] ?></strong>?</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-danger">Hapus</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>



                        <?php
                        }
                        ?>
                        <!-- Modal Tambah Data -->
                        <div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="<?= BASE_URL ?>lab/proses_tambah.php" method="POST">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalTambahLabel">Tambah Data</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label>No Lab</label>
                                                <input type="text" name="no_lab" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label>Pemohon</label>
                                                <input type="text" name="pemohon" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label>Status</label>
                                                <select name="status" class="form-control" required>
                                                    <option value="In Progress">In Progress</option>
                                                    <option value="Completed">Completed</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label>Alamat</label>
                                                <input type="text" name="alamat" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label>Tanggal</label>
                                                <input type="date" name="tgl_lap" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </tbody>
                </table>

            </div>
        </div>
    </div>
    <?php if (!empty($message)): ?>
        <!-- Toast Container -->
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
            <div id="toastPesan" class="toast align-items-center text-bg-<?= $alertType ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?= $message ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>



    <script src="<?= BASE_URL ?>bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const toastEl = document.getElementById('toastPesan');
            if (toastEl) {
                const toast = new bootstrap.Toast(toastEl, {
                    delay: 4000
                });
                toast.show();
                // Bersihkan parameter URL agar tidak muncul saat refresh
                const url = new URL(window.location);
                url.searchParams.delete('pesan');
                window.history.replaceState({}, document.title, url);
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#tabelLab').DataTable({
                responsive: true, // tetap responsif di HP
                scrollX: true, // scroll horizontal aktif
                scrollY: '37vh', // scroll vertikal aktif
                scrollCollapse: true,
                paging: false, // tanpa pagination
                info: false, // sembunyikan info
                lengthChange: false,
                language: {
                    search: "Cari:",
                    zeroRecords: "Data tidak ditemukan"
                }
            });
        });
    </script>



</body>

</html>