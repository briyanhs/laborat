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

?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lab Dashboard</title>
    <link href="<?= BASE_URL ?>bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>fontawesome/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="<?= BASE_URL ?>datatables/datatables.css" />

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>datatables/datatables.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

</head>

<body>
    <div class="d-flex">
        <div class="sidebar p-2">
            <div class="logo">
                LABORATORIUM<br>PDAM SURAKARTA
            </div>
            <div class="logo-line"></div>
            <a href="dashboard_lab.php">Dashboard</a>
            <a href="laporan.php">Laporan</a>
        </div>

        <div class="flex-grow-1">
            <div class="dashboard-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Laboratory Dashboard</h4>
                <div>
                    <a href="<?= BASE_URL ?>logout/logout.php" class="btn btn-outline-danger">Log Out</a>
                </div>
            </div>

            <div class="container-fluid mt-3">

                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Activity</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        Tambah Data
                    </button>
                </div>

                <table id="tabelLab" class="table table-striped table-bordered nowrap" style="width:100%">
                    <thead class="table-primary">
                        <tr>
                            <th>ID Master</th>
                            <th>No Lab</th>
                            <th>Jenis Air</th>
                            <th>Pengirim</th>
                            <th>Penguji</th>
                            <th>Status</th>
                            <th>Lokasi Uji</th>
                            <th>Tanggal Uji</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!isset($con) || !$con) {
                            die("Koneksi database tidak tersedia di laporan.php.");
                        }

                        $query_master_data = "SELECT id_m_hasil_uji, no_lab, jenis_air, pengirim, penguji, lokasi_uji, tanggal_uji
                                            FROM master_hasil_uji
                                            ORDER BY id_m_hasil_uji DESC";
                        $sql_master_data = mysqli_query($con, $query_master_data);

                        if (!$sql_master_data) {
                            die("Query database gagal: " . mysqli_error($con));
                        }

                        while ($result_master = mysqli_fetch_assoc($sql_master_data)) {
                            $id_m_current = $result_master['id_m_hasil_uji'];

                            // Cek apakah ada parameter yang masih 'Proses'
                            $status_proses_query = mysqli_query($con, "SELECT COUNT(*) AS total_proses FROM hasil_uji WHERE id_m_hasil_uji = $id_m_current AND status = 'Proses'");
                            $row_proses = mysqli_fetch_assoc($status_proses_query);

                            if ($row_proses['total_proses'] > 0) {
                                $status_display = 'Proses';
                            } else {
                                // Jika tidak ada yang 'Proses', cek apakah ada detail sama sekali
                                $status_total_query = mysqli_query($con, "SELECT COUNT(*) AS total_detail FROM hasil_uji WHERE id_m_hasil_uji = $id_m_current");
                                $row_total = mysqli_fetch_assoc($status_total_query);

                                if ($row_total['total_detail'] > 0) {
                                    $status_display = 'Selesai'; // Semua detail ada dan tidak ada yang 'Proses'
                                } else {
                                    $status_display = 'Belum Ada Detail'; // Tidak ada detail parameter sama sekali
                                }
                            }
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result_master['id_m_hasil_uji']); ?></td>
                                <td><?php echo htmlspecialchars($result_master['no_lab']); ?></td>
                                <td><?php echo htmlspecialchars($result_master['jenis_air']); ?></td>
                                <td><?php echo htmlspecialchars($result_master['pengirim']); ?></td>
                                <td><?php echo htmlspecialchars($result_master['penguji']); ?></td>
                                <td><?php echo $status_display; ?></td>
                                <td><?php echo htmlspecialchars($result_master['lokasi_uji']); ?></td>
                                <td><?php echo htmlspecialchars($result_master['tanggal_uji']); ?></td>
                                <td>
                                    <button class="btn btn-info btn-sm btn-detail"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalDetail"
                                        data-id_m_hasil_uji="<?= htmlspecialchars($result_master['id_m_hasil_uji']); ?>"
                                        data-no_lab="<?= htmlspecialchars($result_master['no_lab']); ?>">
                                        <i class="fa fa-eye"></i>
                                    </button>

                                    <button class="btn btn-success btn-sm btn-edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEdit"
                                        data-id_m_hasil_uji="<?= htmlspecialchars($result_master['id_m_hasil_uji']); ?>">
                                        <i class="fa fa-pencil"></i>
                                    </button>

                                    <button class="btn btn-danger btn-sm btn-hapus"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalHapus"
                                        data-id_m_hasil_uji="<?= htmlspecialchars($result_master['id_m_hasil_uji']); ?>"
                                        data-no_lab="<?= htmlspecialchars($result_master['no_lab']); ?>">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                    <a href="<?= BASE_URL ?>admin/generate_pdf.php?id_m_hasil_uji=<?= htmlspecialchars($result_master['id_m_hasil_uji']); ?>" target="_blank" class="btn btn-primary btn-sm">
                                        <i class="fa fa-file-pdf-o"></i>
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

    <div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <form action="<?= BASE_URL ?>admin/proses_tambah.php" method="POST" id="formTambah">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTambahLabel">Tambah Data Hasil Uji</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Pilih Paket:</label>
                                <select name="id_paket" class="form-select mb-3" id="paketSelect" required>
                                    <option value="" disabled selected>-- Pilih Paket --</option>
                                    <?php
                                    $paket_query = mysqli_query($con, "SELECT id_paket, nama_paket FROM paket_pengujian_fisika_kimia");
                                    while ($p = mysqli_fetch_assoc($paket_query)) {
                                        echo "<option value='{$p['id_paket']}'>" . htmlspecialchars($p['nama_paket']) . "</option>";
                                    }
                                    ?>
                                </select>


                                <label class="form-label">Lokasi Uji:</label>
                                <input type="text" name="lokasi_uji" class="form-control mb-3" required>

                                <label class="form-label">Penguji:</label>
                                <input type="text" name="penguji" class="form-control mb-3" required>

                                <label class="form-label">Pengirim:</label>
                                <input type="text" name="pengirim" class="form-control mb-3" required>

                                <label class="form-label">Jenis Air:</label>
                                <input type="text" name="jenis_air" class="form-control mb-3" required>

                                <label class="form-label">No Lab:</label>
                                <input type="text" name="no_lab" class="form-control mb-3" required>

                                <label class="form-label">Tanggal Uji:</label>
                                <input type="date" name="tanggal_uji" class="form-control mb-3" required>

                                <label class="form-label">Status Pengujian:</label>
                                <select name="status" class="form-select mb-3" required>
                                    <option value="">-- Pilih Status --</option>
                                    <option value="Proses">Proses</option>
                                    <option value="Selesai">Selesai</option>
                                </select>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Form Hasil Uji:</label>
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

    <div class="modal fade" id="modalAddCustomParameter" tabindex="-1" aria-labelledby="modalAddCustomParameterLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddCustomParameterLabel">Tambah Parameter dari Database</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="selectParameterToAdd" class="form-label">Pilih Parameter:</label>
                    <select id="selectParameterToAdd" class="form-select w-100" style="width:100%">
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="addSelectedParameterBtn">Tambah</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <form action="<?= BASE_URL ?>admin/proses_edit_all.php" method="POST" id="formEditAll">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditLabel">Edit Data Hasil Uji Lengkap (<span id="editModalNoLabTitle"></span>)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_m_hasil_uji" id="edit_id_m_hasil_uji_all">

                        <div class="row">
                            <div class="col-md-4">
                                <h6>Data Umum Pengujian:</h6>
                                <label class="form-label">No Lab:</label>
                                <input type="text" name="no_lab" id="edit_no_lab_all" class="form-control mb-3" required>

                                <label class="form-label">Jenis Air:</label>
                                <input type="text" name="jenis_air" id="edit_jenis_air_all" class="form-control mb-3" required>

                                <label class="form-label">Pengirim:</label>
                                <input type="text" name="pengirim" id="edit_pengirim_all" class="form-control mb-3" required>

                                <label class="form-label">Penguji:</label>
                                <input type="text" name="penguji" id="edit_penguji_all" class="form-control mb-3" required>

                                <label class="form-label">Lokasi Uji:</label>
                                <input type="text" name="lokasi_uji" id="edit_lokasi_uji_all" class="form-control mb-3" required>

                                <label class="form-label">Tanggal Uji:</label>
                                <input type="date" name="tanggal_uji" id="edit_tanggal_uji_all" class="form-control mb-3" required>
                            </div>

                            <div class="col-md-8">
                                <h6>Detail Parameter Uji:</h6>
                                <div class="mb-3">
                                    <label class="form-label">Status Semua Parameter:</label>
                                    <select name="global_status_param" id="global_status_param_all" class="form-select" required>
                                        <option value="Proses">Proses</option>
                                        <option value="Selesai">Selesai</option>
                                    </select>
                                </div>
                                <div id="editParameterContainer" class="table-responsive border p-2 rounded">
                                    <p class="text-info">Memuat detail parameter...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalHapus" tabindex="-1" aria-labelledby="modalHapusLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="<?= BASE_URL ?>admin/proses_hapus_master.php" method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalHapusLabel">Konfirmasi Hapus Data</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_m_hasil_uji" id="hapus_id_m_hasil_uji">
                        <p>Apakah Anda yakin ingin menghapus data hasil uji dengan No Lab: <strong id="hapus_no_lab"></strong>?</p>
                        <p class="text-danger">Tindakan ini juga akan menghapus semua detail parameter yang terkait dengan hasil uji ini!</p>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-danger">Hapus</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalDetail" tabindex="-1" aria-labelledby="modalDetailLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetailLabel">Detail Hasil Uji #<span id="detailNoLab"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detailParameterContainer" class="table-responsive border p-2 rounded">
                        <p class="text-info">Memuat detail hasil uji...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
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

    <script src="<?= BASE_URL ?>bootstrap/js/bootstrap.bundle.min.js"></script>
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
            // Inisialisasi DataTables
            $('#tabelLab').DataTable({
                responsive: true,
                scrollX: true,
                scrollY: '37vh',
                scrollCollapse: true,
                paging: false,
                info: false,
                lengthChange: false,
                language: {
                    search: "Cari:",
                    zeroRecords: "Data tidak ditemukan",
                }
            });

            // Variabel global untuk menyimpan parameter yang sudah ada di form
            let existingParameterIds = new Set();
            let parameterTableInitialized = false;

            // Fungsi untuk menginisialisasi tabel parameter
            function initializeParameterTable() {
                if (!parameterTableInitialized) {
                    $('#parameterContainer').html(`
                        <table id="tambahParameterTable" class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Parameter</th>
                                    <th>Satuan</th>
                                    <th>Kadar Maksimum</th>
                                    <th>Metode</th>
                                    <th>Kategori</th>
                                    <th>Hasil Uji</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    `);
                    parameterTableInitialized = true;
                    existingParameterIds.clear(); // Bersihkan set setiap kali tabel diinisialisasi ulang
                }
            }

            // Fungsi untuk menambahkan baris parameter ke tabel
            function addParameterRow(param) {
                if (existingParameterIds.has(param.id_parameter)) {
                    console.warn('Parameter "' + param.nama_parameter + '" sudah ada di daftar.');
                    alert('Parameter "' + param.nama_parameter + '" sudah ada di daftar.');
                    return;
                }

                initializeParameterTable();

                let $tbody = $('#tambahParameterTable tbody');
                let rowCount = $tbody.children('tr').length + 1;

                let newRow = `
                    <tr data-param-id="${param.id_parameter}">
                        <td>${rowCount}</td>
                        <td>${param.nama_parameter || ''}</td>
                        <td>${param.satuan || ''}</td>
                        <td>${param.kadar_maksimum || ''}</td>
                        <td>${param.metode_uji || ''}</td>
                        <td>${param.kategori || ''}</td>
                        <td>
                            <input type="text" class="form-control form-control-sm" name="hasil[${param.id_parameter}]" value="" required>
                            <input type="hidden" name="param_details[${param.id_parameter}][nama_parameter]" value="${param.nama_parameter || ''}">
                            <input type="hidden" name="param_details[${param.id_parameter}][satuan]" value="${param.satuan || ''}">
                            <input type="hidden" name="param_details[${param.id_parameter}][kadar_maksimum]" value="${param.kadar_maksimum || ''}">
                            <input type="hidden" name="param_details[${param.id_parameter}][metode_uji]" value="${param.metode_uji || ''}">
                            <input type="hidden" name="param_details[${param.id_parameter}][kategori]" value="${param.kategori || ''}">
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-param">
                                <i class="fa fa-times"></i> Hapus
                            </button>
                        </td>
                    </tr>
                `;
                $tbody.append(newRow);
                existingParameterIds.add(param.id_parameter); // Tambahkan ID ke set
                updateRowNumbers();
            }

            // Fungsi untuk memperbarui nomor urut di kolom pertama
            function updateRowNumbers() {
                $('#tambahParameterTable tbody tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
            }

            // Event listener untuk tombol hapus parameter
            $(document).on('click', '.btn-remove-param', function() {
                let $row = $(this).closest('tr');
                let paramId = $row.data('param-id');
                existingParameterIds.delete(paramId); // Hapus ID dari set
                $row.remove();
                updateRowNumbers();
                if ($('#tambahParameterTable tbody tr').length === 0) {
                    $('#parameterContainer').html('<p class="text-muted">Silakan pilih paket atau tambahkan parameter secara manual...</p>');
                    parameterTableInitialized = false;
                }
            });


            // AJAX untuk memuat parameter berdasarkan paket yang dipilih (untuk modal tambah)
            $('#paketSelect').change(function() {
                var id_paket = $(this).val();
                // Clear existing parameters AND reset the table
                existingParameterIds.clear(); // Bersihkan set ID parameter
                parameterTableInitialized = false; // Reset flag inisialisasi tabel
                $('#parameterContainer').empty(); // Kosongkan container sepenuhnya

                if (id_paket) {
                    $.ajax({
                        url: '<?= BASE_URL ?>admin/get_parameters.php', // Endpoint untuk ambil parameter paket
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            id_paket: id_paket
                        },
                        beforeSend: function() {
                            $('#parameterContainer').html('<p class="text-info">Memuat parameter paket...</p>');
                        },
                        success: function(response) {
                            if (response.success && response.parameters.length > 0) {
                                initializeParameterTable();
                                $('#tambahParameterTable tbody').empty(); // Pastikan tbody kosong sebelum mengisi
                                $.each(response.parameters, function(index, param) {
                                    addParameterRow(param);
                                });
                            } else {
                                $('#parameterContainer').html('<p class="text-muted">Tidak ada parameter untuk paket ini.</p>');
                                parameterTableInitialized = false;
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX Error: ", status, error, xhr.responseText);
                            $('#parameterContainer').html('<p class="text-danger">Gagal memuat parameter. Silakan coba lagi. (Error: ' + error + ')</p>');
                        }
                    });
                } else {
                    $('#parameterContainer').html('<p class="text-muted">Silakan pilih paket atau tambahkan parameter secara manual...</p>');
                }
            });

            // Inisialisasi Select2 untuk modalAddCustomParameter
            $('#modalTambah').on('shown.bs.modal', function() {
                if (!$('#selectParameterToAdd').data('select2')) {
                    $('#selectParameterToAdd').select2({
                        dropdownParent: $('#modalAddCustomParameter'),
                        placeholder: 'Cari parameter...',
                        allowClear: true,
                        ajax: {
                            url: '<?= BASE_URL ?>admin/get_all_parameters.php',
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    q: params.term,
                                    page: params.page
                                };
                            },
                            processResults: function(data, params) {
                                params.page = params.page || 1;
                                let filteredData = data.filter(param => !existingParameterIds.has(param.id_parameter));
                                return {
                                    results: $.map(filteredData, function(item) {
                                        return {
                                            id: item.id_parameter,
                                            text: item.nama_parameter,
                                            data: item
                                        };
                                    }),
                                    pagination: {
                                        more: (params.page * 10) < filteredData.length
                                    }
                                };
                            },
                            cache: true
                        },
                        minimumInputLength: 1
                    });
                }
                // Jika tidak ada paket yang dipilih dan tabel kosong, tampilkan pesan manual
                if ($('#paketSelect').val() === '' && $('#tambahParameterTable tbody tr').length === 0 && !parameterTableInitialized) {
                    $('#parameterContainer').html('<p class="text-muted">Silakan pilih paket atau tambahkan parameter secara manual...</p>');
                }
            });

            // Event listener untuk tombol "Tambah" di modal Tambah Parameter Kustom
            $('#addSelectedParameterBtn').on('click', function() {
                var selectedOption = $('#selectParameterToAdd').select2('data');
                if (selectedOption && selectedOption.length > 0) {
                    var param = selectedOption[0].data;
                    addParameterRow(param);
                    $('#modalAddCustomParameter').modal('hide');
                    $('#selectParameterToAdd').val(null).trigger('change');
                } else {
                    alert('Pilih parameter terlebih dahulu.');
                }
            });

            // Reset Select2 saat modal Tambah Parameter Kustom ditutup
            $('#modalAddCustomParameter').on('hidden.bs.modal', function() {
                $('#selectParameterToAdd').val(null).trigger('change');
            });


            // *** START BARIS BARU UNTUK NESTED MODAL FIX V2 ***
            // Saat modal pertama dibuka, pastikan body memiliki kelas modal-open
            $('#modalTambah').on('show.bs.modal', function() {
                $('body').addClass('modal-open');
            });

            // Saat modal pertama ditutup, bersihkan kelas modal-open dari body dan reset form
            $('#modalTambah').on('hidden.bs.modal', function() {
                $('body').removeClass('modal-open');
                $('#formTambah')[0].reset(); // Reset semua input form
                $('#parameterContainer').html('<p class="text-muted">Silakan pilih paket atau tambahkan parameter secara manual...</p>');
                existingParameterIds.clear(); // Bersihkan Set parameter yang ada
                parameterTableInitialized = false; // Reset flag tabel
                $('#selectParameterToAdd').val(null).trigger('change'); // Reset Select2
                $('#paketSelect').val(''); // Reset pilihan paket ke opsi default disabled
            });

            // Event listener untuk tombol "Tambah Parameter Lain" agar membuka modal kustom tanpa menutup modal utama
            $('#addParameterBtn').on('click', function(e) {
                e.preventDefault(); // Mencegah tindakan default jika ada
                var customParameterModal = new bootstrap.Modal(document.getElementById('modalAddCustomParameter'));
                customParameterModal.show();
            });

            // Saat modal kedua (anak) dibuka
            $('#modalAddCustomParameter').on('show.bs.modal', function() {
                // Sembunyikan backdrop modal utama jika ada untuk mencegah backdrop ganda
                // Pastikan backdrop modal utama adalah yang pertama dan tidak tersembunyi
                $('.modal-backdrop.show').first().addClass('d-none');
                // Atur z-index modal anak agar lebih tinggi dari modal utama
                $(this).css('z-index', 1055); // Nilai ini harus lebih tinggi dari modal utama (default 1050)
            });

            // Saat modal kedua (anak) ditutup
            $('#modalAddCustomParameter').on('hidden.bs.modal', function() {
                // Tampilkan kembali backdrop modal utama
                // Pastikan backdrop modal utama adalah yang pertama dan tidak tersembunyi
                $('.modal-backdrop.show').first().removeClass('d-none');
                // Pastikan fokus kembali ke modal utama agar bisa diinteraksi
                $('#modalTambah').focus();
            });
            // *** END BARIS BARU UNTUK NESTED MODAL FIX V2 ***


            // EVENT UNTUK MODAL DETAIL HASIL UJI
            $('.btn-detail').on('click', function() {
                var id_m_hasil_uji = $(this).data('id_m_hasil_uji');
                var no_lab = $(this).data('no_lab');

                $('#detailNoLab').text(no_lab);

                $.ajax({
                    url: '<?= BASE_URL ?>admin/get_detail_hasil.php',
                    type: 'POST',
                    data: {
                        id_m_hasil_uji: id_m_hasil_uji
                    },
                    beforeSend: function() {
                        $('#detailParameterContainer').html('<p class="text-info">Memuat detail hasil uji...</p>');
                    },
                    success: function(response) {
                        $('#detailParameterContainer').html(response);
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error: ", status, error, xhr.responseText);
                        $('#detailParameterContainer').html('<p class="text-danger">Gagal memuat detail hasil uji. Silakan coba lagi. (Error: ' + error + ')</p>');
                    }
                });
            });

            // EVENT UNTUK TOMBOL EDIT
            $('.btn-edit').on('click', function() {
                var id_m_hasil_uji = $(this).data('id_m_hasil_uji');

                $('#edit_id_m_hasil_uji_all').val(id_m_hasil_uji);

                $.ajax({
                    url: '<?= BASE_URL ?>admin/get_data_for_edit.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id_m_hasil_uji: id_m_hasil_uji
                    },
                    beforeSend: function() {
                        $('#editParameterContainer').html('<p class="text-info">Memuat data...</p>');
                        $('#edit_no_lab_all').val('');
                        $('#edit_jenis_air_all').val('');
                        $('#edit_pengirim_all').val('');
                        $('#edit_penguji_all').val('');
                        $('#edit_lokasi_uji_all').val('');
                        $('#edit_tanggal_uji_all').val('');
                        $('#editModalNoLabTitle').text('Memuat...');
                        $('#global_status_param_all').val('Proses');
                    },
                    success: function(response) {
                        if (response.success) {
                            var master = response.master_data;
                            $('#edit_no_lab_all').val(master.no_lab);
                            $('#edit_jenis_air_all').val(master.jenis_air);
                            $('#edit_pengirim_all').val(master.pengirim);
                            $('#edit_penguji_all').val(master.penguji);
                            $('#edit_lokasi_uji_all').val(master.lokasi_uji);
                            $('#edit_tanggal_uji_all').val(master.tanggal_uji);
                            $('#editModalNoLabTitle').text(master.no_lab);

                            var hasProses = response.detail_data.some(param => param.status === 'Proses');
                            $('#global_status_param_all').val(hasProses ? 'Proses' : 'Selesai');

                            var detail = response.detail_data;
                            var html_detail = '<table class="table table-bordered table-sm">';
                            html_detail += '<thead class="table-light"><tr><th>No</th><th>Parameter</th><th>Satuan</th><th>Kadar Maksimum</th><th>Metode</th><th>Kategori</th><th>Hasil Uji</th></tr></thead><tbody>';

                            if (detail.length > 0) {
                                $.each(detail, function(index, param) {
                                    html_detail += '<tr>';
                                    html_detail += '<td>' + (index + 1) + '</td>';
                                    html_detail += '<td>' + param.nama_parameter + '</td>';
                                    html_detail += '<td>' + param.satuan + '</td>';
                                    html_detail += '<td>' + param.kadar_maksimum + '</td>';
                                    html_detail += '<td>' + param.metode_uji + '</td>';
                                    html_detail += '<td>' + param.kategori + '</td>';
                                    html_detail += '<td><input type="text" class="form-control form-control-sm" name="hasil_uji[' + param.id + ']" value="' + param.hasil + '" required></td>';
                                    html_detail += '</tr>';
                                });
                            } else {
                                html_detail += '<tr><td colspan="7" class="text-center">Tidak ada parameter uji untuk master ini.</td></tr>';
                            }

                            html_detail += '</tbody></table>';
                            $('#editParameterContainer').html(html_detail);

                        } else {
                            $('#editParameterContainer').html('<p class="text-danger">Gagal memuat data: ' + response.message + '</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error: ", status, error, xhr.responseText);
                        $('#editParameterContainer').html('<p class="text-danger">Terjadi kesalahan saat memuat data. Silakan coba lagi. (Error: ' + error + ')</p>');
                    }
                });
            });

            // EVENT UNTUK TOMBOL HAPUS
            $('.btn-hapus').on('click', function() {
                var id_m_hasil_uji = $(this).data('id_m_hasil_uji');
                var no_lab = $(this).data('no_lab');

                $('#hapus_id_m_hasil_uji').val(id_m_hasil_uji);
                $('#hapus_no_lab').text(no_lab);
            });
        });
    </script>

</body>

</html>