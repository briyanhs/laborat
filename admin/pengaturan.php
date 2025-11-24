<!DOCTYPE html>
<?php
include '../database/database.php';
include '../config.php';
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

$message = '';
$alertType = 'success';

if (isset($_GET['pesan'])) {
    switch ($_GET['pesan']) {
        case 'sukses_edit': $message = '✏️ Data berhasil diperbarui.'; break;
        case 'gagal': 
            $alertType = 'danger'; 
            $message = '❌ Terjadi kesalahan.'; 
            if (isset($_GET['error_msg'])) $message .= ' Detail: ' . htmlspecialchars($_GET['error_msg']);
            break;
    }
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Parameter & Metode</title>
    <link href="<?= BASE_URL ?>bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?= BASE_URL ?>datatables/datatables.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <div class="sidebar p-2" id="sidebar-wrapper">
            <div class="sidebar-heading">LABORATORIUM<br>PDAM SURAKARTA</div>
            <a href="dashboard_lab.php"><i class="fas fa-fw fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="fisika_kimia.php"><i class="fas fa-fw fa-microscope"></i> <span>Fisika dan Kimia</span></a>
            <a href="bacteriology.php"><i class="fas fa-fw fa-flask"></i> <span>Mikrobiologi</span></a>
            <a href="laporan.php"><i class="fas fa-fw fa-archive"></i> <span>Laporan</span></a>
            <a href="pengaturan.php" class="active"><i class="fas fa-fw fa-gear"></i> <span>Pengaturan</span></a>
            <a href="<?= BASE_URL ?>logout/logout.php"><i class="fas fa-fw fa-sign-out-alt"></i> <span>Log Out</span></a>
        </div>

        <div class="flex-grow-1" id="page-content-wrapper">
            <div class="dashboard-header">
                <button class="btn btn-primary navbar-toggler-custom" id="menu-toggle"><span class="fas fa-bars"></span></button>
                <h4 class="mb-0">Pengaturan Parameter & Metode Uji</h4>
                <a href="<?= BASE_URL ?>logout/logout.php" class="btn btn-outline-danger d-none d-md-block">Log Out</a>
            </div>

            <div class="content-fluid mt-3 p-3">
                <?php if (!empty($message)): ?>
                    <div id="alertNotifikasi" class="alert alert-<?= $alertType; ?> alert-dismissible fade show" role="alert">
                        <?= $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="fisika-tab" data-bs-toggle="tab" data-bs-target="#fisika-pane" type="button" role="tab"><i class="fas fa-atom me-2"></i>Parameter Fisika & Kimia</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="bakteri-tab" data-bs-toggle="tab" data-bs-target="#bakteri-pane" type="button" role="tab"><i class="fas fa-bacterium me-2"></i>Parameter Mikrobiologi</button>
                    </li>
                </ul>

                <div class="tab-content border border-top-0 p-3 bg-white shadow-sm" id="myTabContent">
                    
                    <div class="tab-pane fade show active" id="fisika-pane" role="tabpanel">
                        <div class="table-responsive">
                            <table id="tabel-fisika" class="table table-striped table-bordered" style="width:100%">
                                <thead class="table-primary">
                                    <tr>
                                        <th>No</th>
                                        <th>Parameter</th>
                                        <th>Satuan</th>
                                        <th>Kadar Maks</th>
                                        <th>Metode Uji</th>
                                        <th>Kategori</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $q_fisika = mysqli_query($con, "SELECT * FROM parameter_uji ORDER BY kategori ASC, nama_parameter ASC");
                                    while ($rf = mysqli_fetch_assoc($q_fisika)) { ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($rf['nama_parameter']); ?></td>
                                            <td><?= htmlspecialchars($rf['satuan']); ?></td>
                                            <td><?= htmlspecialchars($rf['kadar_maksimum']); ?></td>
                                            <td><?= htmlspecialchars($rf['metode_uji']); ?></td>
                                            <td><?= htmlspecialchars($rf['kategori']); ?></td>
                                            <td>
                                                <button class="btn btn-warning btn-sm text-white btn-edit-fisika" 
                                                    data-bs-toggle="modal" data-bs-target="#modalEditFisika"
                                                    data-id="<?= $rf['id_parameter']; ?>"
                                                    data-nama="<?= htmlspecialchars($rf['nama_parameter']); ?>"
                                                    data-satuan="<?= htmlspecialchars($rf['satuan']); ?>"
                                                    data-kadar="<?= htmlspecialchars($rf['kadar_maksimum']); ?>"
                                                    data-metode="<?= htmlspecialchars($rf['metode_uji']); ?>"
                                                    data-kategori="<?= htmlspecialchars($rf['kategori']); ?>">
                                                    <i class="fa fa-edit"></i> Edit
                                                </button>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="bakteri-pane" role="tabpanel">
                        <div class="table-responsive">
                            <table id="tabel-bakteri" class="table table-striped table-bordered" style="width:100%">
                                <thead class="table-success">
                                    <tr>
                                        <th>No</th>
                                        <th>Parameter</th>
                                        <th>Satuan</th>
                                        <th>Baku Mutu</th>
                                        <th>Metode Uji</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $q_bakteri = mysqli_query($con, "SELECT * FROM parameter_uji_bacteriology ORDER BY nama_parameter ASC");
                                    while ($rb = mysqli_fetch_assoc($q_bakteri)) { ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($rb['nama_parameter']); ?></td>
                                            <td><?= htmlspecialchars($rb['satuan']); ?></td>
                                            <td><?= htmlspecialchars($rb['nilai_baku_mutu']); ?></td>
                                            <td><?= htmlspecialchars($rb['metode_uji']); ?></td>
                                            <td>
                                                <button class="btn btn-warning btn-sm text-white btn-edit-bakteri"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditBakteri"
                                                    data-id="<?= $rb['id_parameter']; ?>"
                                                    data-nama="<?= htmlspecialchars($rb['nama_parameter']); ?>"
                                                    data-satuan="<?= htmlspecialchars($rb['satuan']); ?>"
                                                    data-baku="<?= htmlspecialchars($rb['nilai_baku_mutu']); ?>"
                                                    data-metode="<?= htmlspecialchars($rb['metode_uji']); ?>">
                                                    <i class="fa fa-edit"></i> Edit
                                                </button>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div> 
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditFisika" tabindex="-1">
        <div class="modal-dialog">
            <form action="proses_edit_parameter_fisika.php" method="POST">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white"><h5 class="modal-title">Edit Parameter Fisika/Kimia</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="id_parameter" id="edit_id_fisika">
                        
                        <div class="mb-2">
                            <label>Nama Parameter</label>
                            <input type="text" name="nama_parameter" id="edit_nama_fisika" class="form-control" required>
                        </div>
                        <div class="row mb-2">
                            <div class="col">
                                <label>Satuan</label>
                                <input type="text" name="satuan" id="edit_satuan_fisika" class="form-control">
                            </div>
                            <div class="col">
                                <label>Kadar Maksimum</label>
                                <input type="text" name="kadar_maksimum" id="edit_kadar_fisika" class="form-control">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label>Metode Uji</label>
                            <input type="text" name="metode_uji" id="edit_metode_fisika" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label>Kategori</label>
                            <select name="kategori" id="edit_kategori_fisika" class="form-select" required>
                                <option value="Fisika">Fisika</option>
                                <option value="Kimia">Kimia</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-warning text-white">Update</button></div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalEditBakteri" tabindex="-1">
        <div class="modal-dialog">
            <form action="proses_edit_parameter_bakteri.php" method="POST">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white"><h5 class="modal-title">Edit Parameter Mikrobiologi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="id_parameter" id="edit_id_bakteri">
                        
                        <div class="mb-2">
                            <label>Nama Parameter</label>
                            <input type="text" name="nama_parameter" id="edit_nama_bakteri" class="form-control" required>
                        </div>
                        <div class="row mb-2">
                            <div class="col">
                                <label>Satuan</label>
                                <input type="text" name="satuan" id="edit_satuan_bakteri" class="form-control">
                            </div>
                            <div class="col">
                                <label>Nilai Baku Mutu</label>
                                <input type="text" name="nilai_baku_mutu" id="edit_baku_bakteri" class="form-control">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label>Metode Uji</label>
                            <input type="text" name="metode_uji" id="edit_metode_bakteri" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-warning text-white">Update</button></div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= BASE_URL ?>bootstrap/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>datatables/datatables.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const alertEl = document.getElementById('alertNotifikasi');
            if (alertEl) {
                setTimeout(() => { const bsAlert = new bootstrap.Alert(alertEl); bsAlert.close(); }, 2000);
                if (window.history.replaceState) {
                    const url = new URL(window.location); url.searchParams.delete('pesan'); window.history.replaceState({}, document.title, url);
                }
            }
        });

        $(document).ready(function() {
            $('#tabel-fisika').DataTable();
            $('#tabel-bakteri').DataTable();
            $("#menu-toggle").click(function(e) { e.preventDefault(); $("#wrapper").toggleClass("toggled"); });

            // Isi Modal Edit Fisika
            $('.btn-edit-fisika').click(function() {
                $('#edit_id_fisika').val($(this).data('id'));
                $('#edit_nama_fisika').val($(this).data('nama'));
                $('#edit_satuan_fisika').val($(this).data('satuan'));
                $('#edit_kadar_fisika').val($(this).data('kadar'));
                $('#edit_metode_fisika').val($(this).data('metode'));
                $('#edit_kategori_fisika').val($(this).data('kategori'));
            });

            // Isi Modal Edit Bakteri
            $('.btn-edit-bakteri').click(function() {
                $('#edit_id_bakteri').val($(this).data('id'));
                $('#edit_nama_bakteri').val($(this).data('nama'));
                $('#edit_satuan_bakteri').val($(this).data('satuan'));
                $('#edit_baku_bakteri').val($(this).data('baku'));
                $('#edit_metode_bakteri').val($(this).data('metode'));
            });
        });
    </script>
</body>
</html>