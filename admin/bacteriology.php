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
// HAPUS query lama Anda, dan GANTI DENGAN INI:

$query_master_data = "
    SELECT
        m.id_m_hasil_uji,
        m.nama_pelanggan,
        m.alamat,
        m.status_pelanggan,
        m.tanggal_pengujian,
        m.nama_pengirim,
        m.jenis_sampel,
        m.jenis_pengujian,
        m.keterangan_sampel,
        m.no_analisa,
        m.wilayah,
        
        -- Ini adalah logika status UJI (Selesai/Proses)
        CASE
            WHEN SUM(CASE WHEN h.status = 'Proses' THEN 1 ELSE 0 END) > 0 THEN 'Proses'
            WHEN COUNT(h.id) > 0 THEN 'Selesai'
            ELSE 'Belum Ada Detail'
        END AS status_display,

        -- Ini adalah logika status VERIFIKASI (Hitung dari log)
        COUNT(DISTINCT lv.id_user_verifier) as total_verifikasi
        
    FROM
        master_hasil_uji_bacteriology m
    LEFT JOIN
        hasil_uji_bacteriology h ON m.id_m_hasil_uji = h.id_m_hasil_uji
    LEFT JOIN 
        log_verifikasi lv ON m.id_m_hasil_uji = lv.id_hasil_uji AND lv.tipe_uji = 'bakteri'
    GROUP BY
        m.id_m_hasil_uji
    ORDER BY
        m.id_m_hasil_uji DESC
";

$sql_master_data = mysqli_query($con, $query_master_data);

if (!$sql_master_data) {
    die("Query database gagal: " . mysqli_error($con));
}

?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mikrobiologi</title>
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
            <a href="bacteriology.php" class="active"><i class="fas fa-fw fa-flask"></i> <span>Mikrobiologi</span></a>
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
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Hasil Uji Mikrobiologi</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        Tambah Data
                    </button>
                </div>

                <div class="table-responsive">
                    <table id="tabel-bacteriology" class="table table-striped table-bordered dt-responsive" style="width:100%">
                        <thead class="table-primary">
                            <tr>
                                <th>No Analisa</th>
                                <th>Jenis Sample</th>
                                <th>Pelanggan</th>
                                <!--<th>penerima</th>-->
                                <th>Status Uji</th>
                                <th>Status Verifikasi</th>
                                <th>Alamat</th>
                                <th>Wilayah</th>
                                <th>Tanggal Uji</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($result_master = mysqli_fetch_assoc($sql_master_data)) {
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result_master['no_analisa']); ?></td>
                                    <td><?php echo htmlspecialchars($result_master['jenis_sampel']); ?></td>
                                    <td><?php echo htmlspecialchars($result_master['nama_pelanggan']); ?></td>
                                    <td><?php echo htmlspecialchars($result_master['status_display']); ?></td>
                                    <td>
                                        <?php
                                        $total_verif = $result_master['total_verifikasi'];
                                        if ($total_verif >= 3) {
                                            echo '<span class="badge bg-success">Selesai (3/3)</span>';
                                        } else {
                                            echo '<span class="badge bg-warning text-dark">Pending (' . $total_verif . '/3)</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($result_master['alamat']); ?></td>
                                    <td><?php echo htmlspecialchars($result_master['wilayah']); ?></td>
                                    <td><?php echo htmlspecialchars($result_master['tanggal_pengujian']); ?></td>
                                    <td>
                                        <button class="btn btn-info btn-sm btn-detail"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalDetail"
                                            data-id_m_hasil_uji="<?= htmlspecialchars($result_master['id_m_hasil_uji']); ?>"
                                            data-no_analisa="<?= htmlspecialchars($result_master['no_analisa']); ?>">
                                            <i class="fa fa-eye"></i>
                                        </button>

                                        <button class="btn btn-warning btn-sm text-white btn-edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEdit"
                                            data-id_m_hasil_uji="<?= htmlspecialchars($result_master['id_m_hasil_uji']); ?>">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm btn-hapus"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalHapus"
                                            data-id_m_hasil_uji="<?= htmlspecialchars($result_master['id_m_hasil_uji']); ?>"
                                            data-no_analisa="<?= htmlspecialchars($result_master['no_analisa']); ?>">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                        <a href="<?= BASE_URL ?>admin/generate_pdf_bacteriology.php?id_m_hasil_uji=<?= htmlspecialchars($result_master['id_m_hasil_uji']); ?>" target="_blank" class="btn btn-primary btn-sm">
                                            <i class="fa fa-file-pdf"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form action="<?= BASE_URL ?>admin/proses_tambah_bacteriology.php" method="POST" id="formTambah">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTambahLabel">Tambah Data Hasil Uji Mikrobiologi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Pilih Paket:</label>
                                <select name="id_paket" class="form-select mb-3" id="paketSelect" required>
                                    <option value="" disabled selected>-- Pilih Paket --</option>
                                    <?php
                                    $paket_query = mysqli_query($con, "SELECT id_paket, nama_paket FROM paket_pengujian_bacteriology");
                                    while ($p = mysqli_fetch_assoc($paket_query)) {
                                        echo "<option value='{$p['id_paket']}'>" . htmlspecialchars($p['nama_paket']) . "</option>";
                                    }
                                    ?>
                                </select>

                                <label class="form-label">Nama Pelanggan</label>
                                <input type="text" name="nama_pelanggan" class="form-control mb-3" required>

                                <label class="form-label">Alamat</label>
                                <input type="text" name="alamat" class="form-control mb-3" required>

                                <label class="form-label">Status Pelanggan</label>
                                <select name="status_pelanggan" class="form-select mb-3" required>
                                    <option value="" disabled>-- Pelanggan / Non Pelanggan --</option>
                                    <option value="Pelanggan">Pelanggan</option>
                                    <option value="Non Pelanggan">Non Pelanggan</option>
                                </select>

                                <label class="form-label">Jenis Sampel</label>
                                <input type="text" name="jenis_sampel" class="form-control mb-3" required>

                                <label class="form-label">Jenis Pengujian</label>
                                <input type="text" name="jenis_pengujian" class="form-control mb-3" required>

                                <label class="form-label">Keterangan Sampel</label>
                                <input type="text" name="keterangan_sampel" class="form-control mb-3" required>

                                <label class="form-label">Nama Pengirim</label>
                                <input type="text" name="nama_pengirim" class="form-control mb-3" required>

                                <label class="form-label">Nomor Analisa</label>
                                <input type="text" name="no_analisa" class="form-control mb-3" required>

                                <label class="form-label">Wilayah:</label>
                                <select name="wilayah" class="form-select mb-3" required>
                                    <option value="" disabled>-- Pilih Wilayah --</option>
                                    <option value="Wilayah Utara">Wilayah Utara</option>
                                    <option value="Wilayah Tengah">Wilayah Tengah</option>
                                    <option value="Wilayah Selatan">Wilayah Selatan</option>
                                </select>

                                <label class="form-label">Tanggal Pengambilan</label>
                                <input type="date" name="tanggal_pengambilan" class="form-control mb-3" required>

                                <label class="form-label">Tanggal Pengiriman</label>
                                <input type="date" name="tanggal_pengiriman" class="form-control mb-3" required>

                                <label class="form-label">Tanggal Penerimaan</label>
                                <input type="date" name="tanggal_penerimaan" class="form-control mb-3" required>

                                <label class="form-label">Tanggal Pengujian</label>
                                <input type="date" name="tanggal_pengujian" class="form-control mb-3" required>

                                <label class="form-label">Status Pengujian</label>
                                <select name="status" class="form-select mb-3" required>
                                    <option value="">-- Pilih Status --</option>
                                    <option value="Proses">Proses</option>
                                    <option value="Selesai">Selesai</option>
                                </select>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Form Hasil Uji</label>
                                <div id="parameterContainer" class="table-responsive border p-2 rounded">
                                    <p class="text-muted">Silakan pilih paket pengujian...</p>
                                </div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="addParameterBtn">
                                        <i class="fa fa-plus"></i> Tambah Parameter Lain
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalEdit" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="modalEditLabel"><i class="fas fa-edit me-2"></i>Edit Data Hasil Uji</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="edit-modal-body">
                    <div class="text-center my-5">
                        <div class="spinner-border text-warning" role="status"></div>
                        <p class="mt-2">Memuat data...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalHapus" tabindex="-1" aria-labelledby="modalHapusLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="<?= BASE_URL ?>admin/proses_hapus_bacteriology.php" method="POST">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="modalHapusLabel">Konfirmasi Hapus Data</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_m_hasil_uji" id="hapus_id_m_hasil_uji">
                        <p>Apakah Anda yakin ingin menghapus data dengan No Analisa: <strong id="hapus_no_analisa"></strong>?</p>
                        <p class="text-danger">Tindakan ini akan menghapus semua detail parameter yang terkait!</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalDetail" tabindex="-1" aria-labelledby="modalDetailLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetailLabel">Detail Hasil Uji</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <div class="text-center my-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAddCustomParameter" tabindex="-1" aria-labelledby="modalAddCustomParameterLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddCustomParameterLabel">Tambah Parameter dari Database</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="selectParameterToAdd" class="form-label">Pilih Parameter:</label>
                    <select id="selectParameterToAdd" class="form-select w-100" style="width: 100%;"></select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="addSelectedParameterBtn">Tambah ke Form</button>
                </div>
            </div>
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
            var tabel = $('#tabel-bacteriology').DataTable({
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

            const tabelBody = $('#tabel-bacteriology tbody');
            let existingParameterIds = new Set();
            let parameterTableInitialized = false;

            // Fungsi untuk menginisialisasi tabel parameter di modal tambah
            function initializeParameterTable() {
                if (!parameterTableInitialized) {
                    $('#parameterContainer').html(`
                    <table id="tambahParameterTable" class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Parameter</th>
                                <th>Satuan</th>
                                <th>Baku Mutu</th>
                                <th>Metode Uji</th>
                                <th>Hasil Analisa</th>
                                <th>Penegasan</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>`);
                    parameterTableInitialized = true;
                    existingParameterIds.clear();
                }
            }

            // Fungsi untuk menambahkan baris parameter baru
            function addParameterRow(param) {
                if (existingParameterIds.has(String(param.id_parameter))) {
                    alert('Parameter "' + param.nama_parameter + '" sudah ada di daftar.');
                    return;
                }

                initializeParameterTable();

                const $tbody = $('#tambahParameterTable tbody');
                const rowCount = $tbody.children('tr').length + 1;

                const newRow = `
                <tr data-param-id="${param.id_parameter}">
                    <td>${rowCount}</td>
                    <td>${param.nama_parameter || ''}</td>
                    <td>${param.satuan || ''}</td>
                    <td>${param.nilai_baku_mutu || ''}</td>
                    <td>${param.metode_uji || ''}</td>
                    <td><input type="text" class="form-control form-control-sm" name="hasil[${param.id_parameter}]" required></td>
                    <td><input type="text" class="form-control form-control-sm" name="penegasan[${param.id_parameter}]"></td>
                    <td><input type="text" class="form-control form-control-sm" name="keterangan[${param.id_parameter}]"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-param"><i class="fa fa-times"></i></button></td>
                    
                    <input type="hidden" name="param_details[${param.id_parameter}][nama_parameter]" value="${param.nama_parameter || ''}">
                    <input type="hidden" name="param_details[${param.id_parameter}][satuan]" value="${param.satuan || ''}">
                    <input type="hidden" name="param_details[${param.id_parameter}][nilai_baku_mutu]" value="${param.nilai_baku_mutu || ''}">
                    <input type="hidden" name="param_details[${param.id_parameter}][metode_uji]" value="${param.metode_uji || ''}">
                </tr>`;
                $tbody.append(newRow);
                existingParameterIds.add(String(param.id_parameter));
                updateRowNumbers();
            }

            function updateRowNumbers() {
                $('#tambahParameterTable tbody tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
            }

            // Event listener untuk hapus baris parameter
            $('#parameterContainer').on('click', '.btn-remove-param', function() {
                const $row = $(this).closest('tr');
                const paramId = String($row.data('param-id'));
                existingParameterIds.delete(paramId);
                $row.remove();
                updateRowNumbers();
                if ($('#tambahParameterTable tbody tr').length === 0) {
                    $('#parameterContainer').html('<p class="text-muted">Silakan pilih paket...</p>');
                    parameterTableInitialized = false;
                }
            });

            // AJAX untuk memuat parameter berdasarkan paket
            $('#paketSelect').change(function() {
                const id_paket = $(this).val();
                existingParameterIds.clear();
                parameterTableInitialized = false;
                $('#parameterContainer').empty();

                if (id_paket) {
                    $.ajax({
                        url: '<?= BASE_URL ?>admin/get_parameters_bacteriology.php', // FILE BARU
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            id_paket: id_paket
                        },
                        beforeSend: function() {
                            $('#parameterContainer').html('<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Memuat parameter...</div>');
                        },
                        success: function(response) {
                            if (response.success && response.parameters.length > 0) {

                                // Langkah 1: Buat tabel dengan data asli terlebih dahulu
                                initializeParameterTable();
                                $('#tambahParameterTable tbody').empty();
                                response.parameters.forEach(param => addParameterRow(param));

                                // --- AWAL KODE PERBAIKAN ---
                                // Langkah 2: Setelah tabel dibuat, cari dan modifikasi baris yang spesifik
                                const selectedPackageName = $('#paketSelect option:selected').text();

                                if (selectedPackageName === 'Mikrobiologi Air Bersih') {
                                    // Cari setiap baris di dalam tabel yang baru dibuat
                                    $('#tambahParameterTable tbody tr').each(function() {
                                        const $row = $(this);
                                        // Dapatkan nama parameter dari kolom kedua (td:nth-child(2))
                                        const parameterName = $row.find('td:nth-child(2)').text();

                                        // Jika nama parameter adalah "Sisa Chlor"
                                        if (parameterName.includes('Sisa Chlor')) {

                                            // 1. Perbarui sel tabel yang terlihat (kolom ke-4)
                                            $row.find('td:nth-child(4)').text('0');

                                            // 2. Perbarui nilai input tersembunyi yang akan dikirim ke server
                                            // Ini adalah langkah kunci yang memperbaiki bug
                                            $row.find('input[name*="[nilai_baku_mutu]"]').val('0');
                                        }
                                    });
                                }
                                // --- AKHIR KODE PERBAIKAN ---

                            } else {
                                $('#parameterContainer').html('<p class="text-muted">Tidak ada parameter untuk paket ini.</p>');
                                parameterTableInitialized = false;
                            }
                        },
                        error: function(xhr) {
                            $('#parameterContainer').html('<p class="text-danger">Gagal memuat parameter.</p>');
                        }
                    });
                } else {
                    $('#parameterContainer').html('<p class="text-muted">Silakan pilih paket pengujian...</p>');
                }
            });

            // Reset form saat modal tambah ditutup
            $('#modalTambah').on('hidden.bs.modal', function() {
                $('#formTambah')[0].reset();
                $('#paketSelect').val('').trigger('change');
            });

            // 1. Inisialisasi Select2 di dalam modal custom
            $('#selectParameterToAdd').select2({
                dropdownParent: $('#modalAddCustomParameter'), // Penting agar dropdown muncul di atas modal
                placeholder: 'Cari nama parameter...',
                ajax: {
                    url: '<?= BASE_URL ?>admin/get_all_parameters_bacteriology.php',
                    dataType: 'json',
                    delay: 250, // Waktu tunggu sebelum request
                    processResults: function(data) {
                        // Filter hasil untuk tidak menampilkan parameter yang sudah ada di form
                        let filteredResults = data.results.filter(param => !existingParameterIds.has(param.id));
                        return {
                            results: filteredResults
                        };
                    },
                    cache: true
                }
            });

            // 2. Event handler untuk tombol #addParameterBtn
            $('#addParameterBtn').on('click', function() {
                // Reset pilihan sebelumnya
                $('#selectParameterToAdd').val(null).trigger('change');
                // Tampilkan modal
                var customModal = new bootstrap.Modal(document.getElementById('modalAddCustomParameter'));
                customModal.show();
            });

            // 3. Event handler untuk tombol "Tambah ke Form" di dalam modal custom
            $('#addSelectedParameterBtn').on('click', function() {
                var selectedData = $('#selectParameterToAdd').select2('data')[0];
                if (selectedData) {
                    // 'selectedData' berisi semua info dari backend
                    addParameterRow(selectedData);
                    // Tutup modal setelah menambah
                    var customModal = bootstrap.Modal.getInstance(document.getElementById('modalAddCustomParameter'));
                    customModal.hide();
                } else {
                    alert('Silakan pilih parameter terlebih dahulu.');
                }
            });

            // Tombol Detail
            tabelBody.on('click', '.btn-detail', function() {
                const id_m_hasil_uji = $(this).data('id_m_hasil_uji');
                const no_analisa = $(this).data('no_analisa');
                $('#modalDetailLabel').html(`<i class="fas fa-info-circle me-2"></i>Detail Data: ${no_analisa}`);

                $.ajax({
                    url: '<?= BASE_URL ?>admin/get_detail_hasil_bacteriology.php',
                    type: 'POST',
                    data: {
                        id_m_hasil_uji: id_m_hasil_uji
                    },
                    beforeSend: function() {
                        $('#detailContent').html('<div class="text-center my-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Memuat data...</p></div>');
                    },
                    success: function(response) {
                        $('#detailContent').html(response);
                    },
                    error: function() {
                        $('#detailContent').html('<div class="alert alert-danger">Gagal memuat detail.</div>');
                    }
                });
            });

            // Tombol Edit (memuat konten ke dalam modal)
            tabelBody.on('click', '.btn-edit', function() {
                const id_m_hasil_uji = $(this).data('id_m_hasil_uji');
                const $modalBody = $('#edit-modal-body');
                $modalBody.html('<div class="text-center my-5"><div class="spinner-border text-warning"></div><p class="mt-2">Memuat data edit...</p></div>');

                $.ajax({
                    url: '<?= BASE_URL ?>admin/get_data_for_edit_bacteriology.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id_m_hasil_uji: id_m_hasil_uji
                    },
                    success: function(response) {
                        if (response.success && response.master_data) {
                            const master = response.master_data;
                            const details = response.detail_data;
                            let formHtml = `<form action="<?= BASE_URL ?>admin/proses_edit_bacteriology.php" method="POST" id="formEditBacteriology">
                                <input type="hidden" name="id_m_hasil_uji" value="${master.id_m_hasil_uji}">
                                <h5 class="mb-3 text-warning border-bottom pb-2">Edit Info Umum</h5>
                                <div class="row mb-3 g-3">
                                    <div class="col-md-6"><label class="form-label">No. Analisa</label><input type="text" class="form-control" name="no_analisa" value="${master.no_analisa || ''}" required></div>
                                    <div class="col-md-6"><label class="form-label">Nama Pelanggan</label><input type="text" class="form-control" name="nama_pelanggan" value="${master.nama_pelanggan || ''}" required></div>
                                    <div class="col-md-6"><label class="form-label">Alamat</label><input type="text" class="form-control" name="alamat" value="${master.alamat || ''}" required></div>
                                    <div class="col-md-6"><label class="form-label">Status Pelanggan</label><select class="form-select" name="status_pelanggan" required><option value="Pelanggan" ${master.status_pelanggan === 'Pelanggan' ? 'selected' : ''}>Pelanggan</option><option value="Non Pelanggan" ${master.status_pelanggan === 'Non Pelanggan' ? 'selected' : ''}>Non Pelanggan</option></select></div>
                                    <div class="col-md-6"><label class="form-label">Wilayah</label><select class="form-select" name="wilayah" required><option value="Wilayah Utara" ${master.wilayah === 'Wilayah Utara' ? 'selected' : ''}>Utara</option><option value="Wilayah Tengah" ${master.wilayah === 'Wilayah Tengah' ? 'selected' : ''}>Tengah</option><option value="Wilayah Selatan" ${master.wilayah === 'Wilayah Selatan' ? 'selected' : ''}>Selatan</option></select></div>
                                    <div class="col-md-6"><label class="form-label">Jenis Sampel</label><input type="text" class="form-control" name="jenis_sampel" value="${master.jenis_sampel || ''}" required></div>
                                    <div class="col-md-6"><label class="form-label">Jenis Pengujian</label><input type="text" class="form-control" name="jenis_pengujian" value="${master.jenis_pengujian || ''}" required></div>
                                    <div class="col-md-6"><label class="form-label">Ket. Sampel</label><input type="text" class="form-control" name="keterangan_sampel" value="${master.keterangan_sampel || ''}"></div>
                                    <div class="col-md-6"><label class="form-label">Nama Pengirim</label><input type="text" class="form-control" name="nama_pengirim" value="${master.nama_pengirim || ''}" required></div>
                                    <div class="col-md-6">
                                        <label for="edit_tanggal_pengambilan" class="form-label">Tgl Pengambilan</label>
                                        <input type="date" class="form-control" id="edit_tanggal_pengambilan" name="tanggal_pengambilan" value="${master.tanggal_pengambilan || ''}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="edit_tanggal_pengiriman" class="form-label">Tgl Pengiriman</label>
                                        <input type="date" class="form-control" id="edit_tanggal_pengiriman" name="tanggal_pengiriman" value="${master.tanggal_pengiriman || ''}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="edit_tanggal_penerimaan" class="form-label">Tgl Penerimaan</label>
                                        <input type="date" class="form-control" id="edit_tanggal_penerimaan" name="tanggal_penerimaan" value="${master.tanggal_penerimaan || ''}" required>
                                    </div>
                                     <div class="col-md-6">
                                        <label for="edit_tanggal_pengujian" class="form-label">Tgl Pengujian</label>
                                        <input type="date" class="form-control" id="edit_tanggal_pengujian" name="tanggal_pengujian" value="${master.tanggal_pengujian || ''}" required>
                                    </div>
                                    </div>
                                <h5 class="mb-3 mt-4 text-warning border-bottom pb-2">Edit Detail Parameter</h5>`;

                            let globalStatus = 'Selesai';
                            if (details.some(d => d.status === 'Proses')) {
                                globalStatus = 'Proses';
                            }
                            formHtml += `<div class="mb-3">
                                <label class="form-label">Status Global</label><select class="form-select" name="global_status"><option value="Proses" ${globalStatus === 'Proses' ? 'selected' : ''}>Proses</option><option value="Selesai" ${globalStatus === 'Selesai' ? 'selected' : ''}>Selesai</option></select>
                                <small class="text-muted">Akan mengubah status semua parameter.</small></div>`;

                            if (details.length > 0) {
                                formHtml += `<div class="table-responsive"><table class="table table-bordered table-sm">
                                    <thead class="table-light"><tr><th>Parameter</th><th>Hasil</th><th>Penegasan</th><th>Keterangan</th></tr></thead><tbody>`;
                                details.forEach(d => {
                                    formHtml += `<tr><td>${d.nama_parameter || ''}<input type="hidden" name="detail_ids[]" value="${d.id}"></td>
                                        <td><input type="text" class="form-control form-control-sm" name="hasil[${d.id}]" value="${d.hasil || ''}"></td>
                                        <td><input type="text" class="form-control form-control-sm" name="penegasan[${d.id}]" value="${d.penegasan || ''}"></td>
                                        <td><input type="text" class="form-control form-control-sm" name="keterangan[${d.id}]" value="${d.keterangan || ''}"></td></tr>`;
                                });
                                formHtml += `</tbody></table></div>`;
                            } else {
                                formHtml += '<p class="text-muted">Tidak ada detail parameter.</p>';
                            }

                            formHtml += `<div class="modal-footer mt-3"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan Perubahan</button></div></form>`;
                            $modalBody.html(formHtml);
                            $('#modalEditLabel').text(`Edit Data: ${master.no_analisa || 'N/A'}`);
                        } else {
                            $modalBody.html(`<div class="alert alert-danger">Gagal memuat: ${response.message || 'Error.'}</div>`);
                        }
                    },
                    error: function(xhr) {
                        console.error("AJAX Error:", xhr.responseText);
                        $modalBody.html('<div class="alert alert-danger">Error AJAX.</div>');
                    }
                });
            });

            // Tombol Hapus
            tabelBody.on('click', '.btn-hapus', function() {
                const id_m_hasil_uji = $(this).data('id_m_hasil_uji');
                const no_analisa = $(this).data('no_analisa');
                $('#hapus_id_m_hasil_uji').val(id_m_hasil_uji);
                $('#hapus_no_analisa').text(no_analisa);
            });
        });
    </script>

    <?php
    // Tutup koneksi database di akhir file setelah semua operasi selesai
    if (isset($con) && is_object($con) && method_exists($con, 'close')) {
        mysqli_close($con);
    }
    ?>

</body>

</html>