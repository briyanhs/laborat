<!DOCTYPE html>
<?php
include '../database/database.php';
include '../config.php';

// --- SECURITY: Session Config ---
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

$message = '';
$alertType = 'success';

// Menangani pesan dari proses CRUD
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
    <title>Pengaturan Parameter</title>
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
                        <button class="nav-link active fw-bold" id="fisika-tab" data-bs-toggle="tab" data-bs-target="#fisika-pane" type="button" role="tab"><i class="fas fa-atom me-2"></i>Fisika & Kimia</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="bakteri-tab" data-bs-toggle="tab" data-bs-target="#bakteri-pane" type="button" role="tab"><i class="fas fa-bacterium me-2"></i>Mikrobiologi</button>
                    </li>
                </ul>

                <div class="tab-content border border-top-0 p-3 bg-white shadow-sm rounded-bottom" id="myTabContent">

                    <div class="tab-pane fade show active" id="fisika-pane" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-primary mb-0">Daftar Parameter Fisika & Kimia</h5>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahFisika">
                                <i class="fas fa-plus"></i> Tambah Parameter
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table id="tabel-fisika" class="table table-striped table-bordered table-hover align-middle" style="width:100%">
                                <thead class="table-primary text-center">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Parameter</th>
                                        <th>Satuan</th>
                                        <th>Kadar Maks</th>
                                        <th>Metode Uji</th>
                                        <th>Kategori</th>
                                        <th width="15%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $q_fisika = mysqli_query($con, "SELECT * FROM parameter_uji ORDER BY kategori ASC, nama_parameter ASC");
                                    while ($rf = mysqli_fetch_assoc($q_fisika)) { ?>
                                        <tr>
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($rf['nama_parameter']); ?></td>
                                            <td class="text-center"><?= htmlspecialchars($rf['satuan']); ?></td>
                                            <td class="text-center"><?= htmlspecialchars($rf['kadar_maksimum']); ?></td>
                                            <td><?= htmlspecialchars($rf['metode_uji']); ?></td>
                                            <td class="text-center"><span class="badge bg-secondary"><?= htmlspecialchars($rf['kategori']); ?></span></td>
                                            <td class="text-center">
                                                <button class="btn btn-warning btn-sm text-white btn-edit-fisika mb-1"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditFisika"
                                                    data-id="<?= $rf['id_parameter']; ?>"
                                                    data-nama="<?= htmlspecialchars($rf['nama_parameter']); ?>"
                                                    data-satuan="<?= htmlspecialchars($rf['satuan']); ?>"
                                                    data-kadar="<?= htmlspecialchars($rf['kadar_maksimum']); ?>"
                                                    data-metode="<?= htmlspecialchars($rf['metode_uji']); ?>"
                                                    data-kategori="<?= htmlspecialchars($rf['kategori']); ?>">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm mb-1 btn-hapus"
                                                    data-bs-toggle="modal" data-bs-target="#modalHapus"
                                                    data-id="<?= $rf['id_parameter']; ?>"
                                                    data-tipe="fisika"
                                                    data-nama="<?= htmlspecialchars($rf['nama_parameter']); ?>">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="bakteri-pane" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-success mb-0">Daftar Parameter Mikrobiologi</h5>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahBakteri">
                                <i class="fas fa-plus"></i> Tambah Parameter
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table id="tabel-bakteri" class="table table-striped table-bordered table-hover align-middle" style="width:100%">
                                <thead class="table-success text-center">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Parameter</th>
                                        <th>Satuan</th>
                                        <th>Baku Mutu</th>
                                        <th>Metode Uji</th>
                                        <th width="15%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $q_bakteri = mysqli_query($con, "SELECT * FROM parameter_uji_bacteriology ORDER BY nama_parameter ASC");
                                    while ($rb = mysqli_fetch_assoc($q_bakteri)) { ?>
                                        <tr>
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($rb['nama_parameter']); ?></td>
                                            <td class="text-center"><?= htmlspecialchars($rb['satuan']); ?></td>
                                            <td class="text-center"><?= htmlspecialchars($rb['nilai_baku_mutu']); ?></td>
                                            <td><?= htmlspecialchars($rb['metode_uji']); ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-warning btn-sm text-white btn-edit-bakteri mb-1"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditBakteri"
                                                    data-id="<?= $rb['id_parameter']; ?>"
                                                    data-nama="<?= htmlspecialchars($rb['nama_parameter']); ?>"
                                                    data-satuan="<?= htmlspecialchars($rb['satuan']); ?>"
                                                    data-baku="<?= htmlspecialchars($rb['nilai_baku_mutu']); ?>"
                                                    data-metode="<?= htmlspecialchars($rb['metode_uji']); ?>">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm mb-1 btn-hapus"
                                                    data-bs-toggle="modal" data-bs-target="#modalHapus"
                                                    data-id="<?= $rb['id_parameter']; ?>"
                                                    data-tipe="bakteri"
                                                    data-nama="<?= htmlspecialchars($rb['nama_parameter']); ?>">
                                                    <i class="fa fa-trash"></i>
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

    <div class="modal fade" id="modalTambahFisika" tabindex="-1">
        <div class="modal-dialog">
            <form action="proses_tambah_parameter_fisika.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Tambah Parameter Fisika/Kimia</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3"><label>Nama Parameter</label><input type="text" name="nama_parameter" class="form-control" required></div>
                        <div class="row mb-3">
                            <div class="col"><label>Satuan</label><input type="text" name="satuan" class="form-control"></div>
                            <div class="col"><label>Kadar Maksimum</label><input type="text" name="kadar_maksimum" class="form-control"></div>
                        </div>
                        <div class="mb-3"><label>Metode Uji</label><input type="text" name="metode_uji" class="form-control"></div>
                        <div class="mb-3">
                            <label>Kategori</label>
                            <select name="kategori" class="form-select" required>
                                <option value="Fisika">Fisika</option>
                                <option value="Kimia">Kimia</option>
                            </select>
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

    <div class="modal fade" id="modalEditFisika" tabindex="-1">
        <div class="modal-dialog">
            <form action="proses_edit_parameter_fisika.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id_parameter" id="edit_id_fisika">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title">Edit Parameter Fisika/Kimia</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3"><label>Nama Parameter</label><input type="text" name="nama_parameter" id="edit_nama_fisika" class="form-control" required></div>
                        <div class="row mb-3">
                            <div class="col"><label>Satuan</label><input type="text" name="satuan" id="edit_satuan_fisika" class="form-control"></div>
                            <div class="col"><label>Kadar Maksimum</label><input type="text" name="kadar_maksimum" id="edit_kadar_fisika" class="form-control"></div>
                        </div>
                        <div class="mb-3"><label>Metode Uji</label><input type="text" name="metode_uji" id="edit_metode_fisika" class="form-control"></div>
                        <div class="mb-3">
                            <label>Kategori</label>
                            <select name="kategori" id="edit_kategori_fisika" class="form-select" required>
                                <option value="Fisika">Fisika</option>
                                <option value="Kimia">Kimia</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning text-white">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalTambahBakteri" tabindex="-1">
        <div class="modal-dialog">
            <form action="proses_tambah_parameter_bakteri.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Tambah Parameter Mikrobiologi</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3"><label>Nama Parameter</label><input type="text" name="nama_parameter" class="form-control" required></div>
                        <div class="row mb-3">
                            <div class="col"><label>Satuan</label><input type="text" name="satuan" class="form-control"></div>
                            <div class="col"><label>Nilai Baku Mutu</label><input type="text" name="nilai_baku_mutu" class="form-control"></div>
                        </div>
                        <div class="mb-3"><label>Metode Uji</label><input type="text" name="metode_uji" class="form-control"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalEditBakteri" tabindex="-1">
        <div class="modal-dialog">
            <form action="proses_edit_parameter_bakteri.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id_parameter" id="edit_id_bakteri">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title">Edit Parameter Mikrobiologi</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3"><label>Nama Parameter</label><input type="text" name="nama_parameter" id="edit_nama_bakteri" class="form-control" required></div>
                        <div class="row mb-3">
                            <div class="col"><label>Satuan</label><input type="text" name="satuan" id="edit_satuan_bakteri" class="form-control"></div>
                            <div class="col"><label>Nilai Baku Mutu</label><input type="text" name="nilai_baku_mutu" id="edit_baku_bakteri" class="form-control"></div>
                        </div>
                        <div class="mb-3"><label>Metode Uji</label><input type="text" name="metode_uji" id="edit_metode_bakteri" class="form-control"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning text-white">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalHapus" tabindex="-1">
        <div class="modal-dialog">
            <form action="proses_hapus_parameter.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id_parameter" id="hapus_id_parameter">
                <input type="hidden" name="tipe_parameter" id="hapus_tipe_parameter">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Konfirmasi Hapus</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus parameter: <strong id="hapus_nama_parameter"></strong>?</p>
                        <small class="text-danger">Tindakan ini tidak dapat dibatalkan.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= BASE_URL ?>bootstrap/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>datatables/datatables.js"></script>
    <script>
        // Alert Auto Close
        window.addEventListener('DOMContentLoaded', () => {
            const alertEl = document.getElementById('alertNotifikasi');
            if (alertEl) {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alertEl);
                    bsAlert.close();
                }, 3000);
                // Bersihkan URL
                const url = new URL(window.location);
                url.searchParams.delete('pesan');
                url.searchParams.delete('error_msg');
                window.history.replaceState({}, document.title, url);
            }
        });

        $(document).ready(function() {
            // Init Datatables
            $('#tabel-fisika').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
                }
            });
            $('#tabel-bakteri').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
                }
            });

            // Toggle Sidebar
            $("#menu-toggle").click(function(e) {
                e.preventDefault();
                $("#wrapper").toggleClass("toggled");
            });

            // Handler Edit Fisika
            $('#tabel-fisika').on('click', '.btn-edit-fisika', function() {
                $('#edit_id_fisika').val($(this).data('id'));
                $('#edit_nama_fisika').val($(this).data('nama'));
                $('#edit_satuan_fisika').val($(this).data('satuan'));
                $('#edit_kadar_fisika').val($(this).data('kadar'));
                $('#edit_metode_fisika').val($(this).data('metode'));
                $('#edit_kategori_fisika').val($(this).data('kategori'));
            });

            // Handler Edit Bakteri
            $('#tabel-bakteri').on('click', '.btn-edit-bakteri', function() {
                $('#edit_id_bakteri').val($(this).data('id'));
                $('#edit_nama_bakteri').val($(this).data('nama'));
                $('#edit_satuan_bakteri').val($(this).data('satuan'));
                $('#edit_baku_bakteri').val($(this).data('baku'));
                $('#edit_metode_bakteri').val($(this).data('metode'));
            });

            // Handler Hapus (Generic)
            $(document).on('click', '.btn-hapus', function() {
                $('#hapus_id_parameter').val($(this).data('id'));
                $('#hapus_tipe_parameter').val($(this).data('tipe')); // 'fisika' atau 'bakteri'
                $('#hapus_nama_parameter').text($(this).data('nama'));
            });
        });
    </script>
</body>

</html>