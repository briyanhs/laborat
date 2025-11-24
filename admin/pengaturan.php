<!DOCTYPE html>
<?php
include '../database/database.php';
include '../config.php';
session_start(); // Pastikan session_start() ada di awal, sebelum output apapun

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

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
            if (isset($_GET['error_msg'])) {
                $message .= ' Detail: ' . htmlspecialchars($_GET['error_msg']);
            }
            break;
        case 'gagal_param':
            $alertType = 'danger';
            $message = '❌ Gagal: Data parameter hasil uji tidak lengkap atau tidak valid.';
            break;
    }
}

// Ambil data master hasil uji untuk tabel


?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan</title>
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
                LABORATORIUM<br>PDAM SURAKARTA
            </div>
            <a href="dashboard_lab.php"><i class="fas fa-fw fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="fisika_kimia.php"><i class="fas fa-fw fa-microscope"></i> <span>Fisika dan Kimia</span></a>
            <a href="bacteriology.php"><i class="fas fa-fw fa-flask"></i> <span>Mikrobiologi</span></a>
            <a href="laporan.php"><i class="fas fa-fw fa-archive"></i> <span>Laporan</span></a>
            <a href="pengaturan.php" class="active"><i class="fas fa-fw fa-gear"></i> <span>Pengaturan</span></a>
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
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Metode Uji Mikrobiologi</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        Tambah Data
                    </button>
                </div>

                <div class="table-responsive">
                    <table id="tabel-metode-uji-bacteriology" class="table table-striped table-bordered nowrap" style="width:100%">
                        <thead class="table-primary">
                            <tr>
                                <th>Nama Metode Uji</th>
                                <th>Kategori</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambah" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
        <div class="modal-dialog modal-m modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white shadow-sm">
                    <h5 class="modal-title" id="modalTambahLabel"><i class="fas fa-plus-circle me-2"></i>Tambah Metode Uji Mikrobiologi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="<?= BASE_URL ?>admin/proses_tambah_metode_uji_bacteriology.php" method="POST" id="formTambah">
                        <h6 class="text-primary border-bottom pb-2 mb-3">Metode Uji Mikrobiologi</h6>
                        <div class="mb-3">
                            <label for="nama_metode_uji_tambah" class="form-label">Nama Metode Uji</label>
                            <input type="text" name="nama_metode_uji" id="nama_metode_uji_tambah" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="kategori_tambah" class="form-label">Kategori</label>
                            <select name="kategori" class="form-select" id="kategori_tambah" required>
                                <option value="" disabled selected>-- Pilih Kategori --</option>
                                <option value="Tes Coliform">Tes Coliform</option>
                                <option value="Tes Coli Tinja">Tes Coli Tinja</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer shadow-lg">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i>Batal</button>
                    <button type="submit" form="formTambah" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Data</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEdit" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
        <div class="modal-dialog modal-m modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white shadow-sm">
                    <h5 class="modal-title" id="modalEditLabel"><i class="fas fa-edit me-2"></i>Edit Metode Uji Mikrobiologi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="<?= BASE_URL ?>admin/proses_edit_metode_uji_bacteriology.php" method="POST" id="formEdit">
                        <input type="hidden" name="id_metode_uji_bacteriology" id="edit-id-master">
                        <h6 class="text-primary border-bottom pb-2 mb-3">Metode Uji Mikrobiologi</h6>
                        <div class="mb-3">
                            <label for="nama_metode_uji_edit" class="form-label">Nama Metode Uji</label>
                            <input type="text" name="nama_metode_uji" id="nama_metode_uji_edit" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="kategori_edit" class="form-label">Kategori</label>
                            <select name="kategori" class="form-select" id="kategori_edit" required>
                                <option value="" disabled selected>-- Pilih Kategori --</option>
                                <option value="Tes Coliform">Tes Coliform</option>
                                <option value="Tes Coli Tinja">Tes Coli Tinja</option>
                            </select>

                        </div>
                    </form>
                </div>
                <div class="modal-footer shadow-lg">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i>Tutup</button>
                    <button type="submit" form="formEdit" class="btn btn-warning text-white"><i class="fas fa-save me-2"></i>Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="modalHapus" tabindex="-1" aria-labelledby="modalHapusLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="<?= BASE_URL ?>admin/proses_hapus_metode_uji_bacteriology.php" method="POST">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="modalHapusLabel">Konfirmasi Hapus Data</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_metode_uji_bacteriology" id="hapus_id_metode_uji_bacteriology">
                        <p>Apakah Anda yakin ingin menghapus data metode uji dengan Nama : <strong id="hapus_nama_metode_uji"></strong>?</p>
                        <p class="text-danger">Tindakan ini juga akan menghapus semua terkait dengan metode uji mikrobiologi!</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($message)) : ?>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= BASE_URL ?>bootstrap/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>datatables/datatables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <script>
        // Inisialisasi Toast Bootstrap
        window.addEventListener('DOMContentLoaded', () => {
            const toastEl = document.getElementById('toastPesan');
            if (toastEl) {
                const toast = new bootstrap.Toast(toastEl, {
                    delay: 4000
                });
                toast.show();
                const url = new URL(window.location);
                url.searchParams.delete('pesan');
                window.history.replaceState({}, document.title, url);
            }
        });

        $(document).ready(function() {
            var tabel = $('#tabel-metode-uji-bacteriology').DataTable({
                paging: true,
                lengthChange: true,
                info: true,
                searching: true,
                language: {
                    search: "Cari:",
                    zeroRecords: "Data tidak ditemukan",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                    infoFiltered: "(disaring dari _MAX_ total data)",
                    lengthMenu: "Tampilkan _MENU_ data",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    }
                }
            })

            // Toggle sidebar
            $("#menu-toggle").click(function(e) {
                e.preventDefault();
                $("#wrapper").toggleClass("toggled");
            });
        });

        // JavaScript untuk memuat data ke modal edit
        $(document).on('click', '.btn-edit', function() {
            var id = $(this).data('id');
            var nama = $(this).data('nama');
            var kategori = $(this).data('kategori');

            // Mengisi form di dalam modalEdit
            $('#modalEdit #edit-id-master').val(id);
            $('#modalEdit #nama_metode_uji_edit').val(nama);
            $('#modalEdit #kategori_edit').val(kategori).trigger('change'); // Menggunakan .val() untuk select dan .trigger('change') jika menggunakan Select2
        });

        // JavaScript untuk memuat data ke modal hapus
        $(document).on('click', '.btn-hapus', function() {
            var id = $(this).data('id');
            var nama = $(this).data('nama');

            $('#hapus_id_metode_uji_bacteriology').val(id);
            $('#hapus_nama_metode_uji').text(nama);
        });
    </script>

</body>

</html>