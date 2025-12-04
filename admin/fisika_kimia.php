<!DOCTYPE html>
<?php
include '../database/database.php';
include '../config.php';

// --- SECURITY: Konfigurasi Session Aman ---
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

// --- QUERY UTAMA (Optimized) ---
$query_master_data = "
    SELECT
        m.id_m_hasil_uji,
        m.nama_pelanggan,
        m.alamat,
        m.status_pelanggan,
        m.tanggal_pengambilan,
        m.tanggal_pengiriman,
        m.tanggal_penerimaan,
        m.tanggal_pengujian,
        m.nama_pengirim,
        m.jenis_sampel,
        m.keterangan_sampel,
        m.no_analisa,
        m.wilayah,
        
        -- Logika Status UJI
        CASE
            WHEN SUM(CASE WHEN h.status = 'Proses' THEN 1 ELSE 0 END) > 0 THEN 'Proses'
            WHEN COUNT(h.id) > 0 THEN 'Selesai'
            ELSE 'Belum Ada Detail'
        END AS status_display,

        -- Logika Status VERIFIKASI
        COUNT(DISTINCT lv.id_user_verifier) as total_verifikasi
        
    FROM
        master_hasil_uji m
    LEFT JOIN
        hasil_uji h ON m.id_m_hasil_uji = h.id_m_hasil_uji
    LEFT JOIN 
        log_verifikasi lv ON m.id_m_hasil_uji = lv.id_hasil_uji AND lv.tipe_uji = 'fisika'
    GROUP BY
        m.id_m_hasil_uji
    ORDER BY
        m.id_m_hasil_uji DESC
";

$sql_master_data = mysqli_query($con, $query_master_data);

if (!$sql_master_data) {
    // Log error di server, jangan tampilkan detail ke user
    error_log("Database Error: " . mysqli_error($con));
    die("Terjadi kesalahan sistem. Silakan hubungi administrator.");
}
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fisika dan Kimia</title>
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
            <a href="fisika_kimia.php" class="active"><i class="fas fa-fw fa-microscope"></i> <span>Fisika dan Kimia</span></a>
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
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Hasil Uji Fisika dan Kimia</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        Tambah Data
                    </button>
                </div>

                <div class="table-responsive">
                    <table id="tabelLab" class="table table-striped table-bordered dt-responsive" style="width:100%">
                        <thead class="table-primary">
                            <tr>
                                <th>No Analisa</th>
                                <th>Jenis Sample</th>
                                <th>Pelanggan</th>
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
                                        <a href="<?= BASE_URL ?>admin/generate_pdf.php?id_m_hasil_uji=<?= htmlspecialchars($result_master['id_m_hasil_uji']); ?>" target="_blank" class="btn btn-primary btn-sm">
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
            <form action="<?= BASE_URL ?>admin/proses_tambah.php" method="POST" id="formTambah">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
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
                                    // Pastikan koneksi $con valid
                                    if ($con) {
                                        $paket_query = mysqli_query($con, "SELECT id_paket, nama_paket FROM paket_pengujian_fisika_kimia");
                                        while ($p = mysqli_fetch_assoc($paket_query)) {
                                            echo "<option value='{$p['id_paket']}'>" . htmlspecialchars($p['nama_paket']) . "</option>";
                                        }
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
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditLabel">Edit Data Hasil Uji Lengkap (<span id="editModalNoAnalisaTitle"></span>)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_m_hasil_uji" id="edit_id_m_hasil_uji_all">

                        <div class="row">
                            <div class="col-md-4">
                                <h6>Data Umum Pengujian:</h6>
                                <label class="form-label">Nama Pelanggan</label>
                                <input type="text" name="nama_pelanggan" id="edit_nama_pelanggan_all" class="form-control mb-3" required>

                                <label class="form-label">Alamat</label>
                                <input type="text" name="alamat" id="edit_alamat_all" class="form-control mb-3" required>

                                <label class="form-label">Status Pelanggan</label>
                                <select name="status_pelanggan" id="edit_status_pelanggan_all" class="form-select mb-3" required>
                                    <option value="Pelanggan">Pelanggan</option>
                                    <option value="Non Pelanggan">Non Pelanggan</option>
                                </select>

                                <label class="form-label">Jenis Sampel:</label>
                                <input type="text" name="jenis_sampel" id="edit_jenis_sampel_all" class="form-control mb-3" required>

                                <label class="form-label">Keterangan Sampel:</label>
                                <input type="text" name="keterangan_sampel" id="edit_keterangan_sampel_all" class="form-control mb-3" required>

                                <label class="form-label">Nama Pengirim:</label>
                                <input type="text" name="nama_pengirim" id="edit_nama_pengirim_all" class="form-control mb-3" required>

                                <label class="form-label">No Analisa:</label>
                                <input type="text" name="no_analisa" id="edit_no_analisa_all" class="form-control mb-3" required>

                                <label class="form-label">Wilayah:</label>
                                <select name="wilayah" id="edit_wilayah_all" class="form-select mb-3" required>
                                    <option value="Wilayah Utara">Wilayah Utara</option>
                                    <option value="Wilayah Tengah">Wilayah Tengah</option>
                                    <option value="Wilayah Selatan">Wilayah Selatan</option>
                                </select>

                                <label class="form-label">Tanggal Pengambilan</label>
                                <input type="date" name="tanggal_pengambilan" id="edit_tanggal_pengambilan_all" class="form-control mb-3" required>

                                <label class="form-label">Tanggal Pengiriman</label>
                                <input type="date" name="tanggal_pengiriman" id="edit_tanggal_pengiriman_all" class="form-control mb-3" required>

                                <label class="form-label">Tanggal Penerimaan</label>
                                <input type="date" name="tanggal_penerimaan" id="edit_tanggal_penerimaan_all" class="form-control mb-3" required>

                                <label class="form-label">Tanggal Pengujian</label>
                                <input type="date" name="tanggal_pengujian" id="edit_tanggal_pengujian_all" class="form-control mb-3" required>
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
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalHapusLabel">Konfirmasi Hapus Data</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_m_hasil_uji" id="hapus_id_m_hasil_uji">
                        <p>Apakah Anda yakin ingin menghapus data hasil uji dengan No Analisa: <strong id="hapus_no_analisa"></strong>?</p>
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
                    <h5 class="modal-title" id="modalDetailLabel">Detail Hasil Uji #<span id="detailNoAnalisa"></span></h5>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= BASE_URL ?>bootstrap/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>datatables/datatables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <script>
        // --- FUNGSI CEK KEPATUHAN (PERBAIKAN KOMPREHENSIF) ---
        function cekKepatuhan(hasil, standar) {
            if (hasil === null || standar === null || hasil === undefined || standar === undefined) {
                return '';
            }

            let standarStr = standar.toString().trim();
            let hasilStr = hasil.toString().trim();

            if (hasilStr === '' || standarStr === '') {
                return '';
            }

            if (standarStr.toLowerCase().includes('suhu udara')) {
                return '';
            }

            // Ganti koma dengan titik untuk perhitungan matematika
            let standarClean = standarStr.replace(/,/g, '.');
            let hasilClean = hasilStr.replace(/,/g, '.');
            let hasilNum = parseFloat(hasilClean);

            // Kasus Rentang (e.g., "6.5 - 8.5")
            if (standarClean.includes('-')) {
                let parts = standarClean.split('-').map(p => p.trim());
                if (parts.length === 2) {
                    let min = parseFloat(parts[0]);
                    let max = parseFloat(parts[1]);

                    if (!isNaN(min) && !isNaN(max) && !isNaN(hasilNum)) {
                        let isMemenuhi = (hasilNum >= min) && (hasilNum <= max);
                        return isMemenuhi ? 'Memenuhi' : 'Tidak Memenuhi';
                    }
                }
            }

            // Kasus Kurang Dari (e.g., "< 10")
            if (standarClean.startsWith('<')) {
                let max = parseFloat(standarClean.substring(1).trim());
                if (!isNaN(max) && !isNaN(hasilNum)) {
                    return hasilNum < max ? 'Memenuhi' : 'Tidak Memenuhi';
                }
            }

            // Kasus Lebih Dari (e.g., "> 1")
            if (standarClean.startsWith('>')) {
                let min = parseFloat(standarClean.substring(1).trim());
                if (!isNaN(min) && !isNaN(hasilNum)) {
                    return hasilNum > min ? 'Memenuhi' : 'Tidak Memenuhi';
                }
            }

            // Kasus Maksimum Sederhana (anggap angka tunggal adalah batas maksimum, e.g., "500")
            let standarNum = parseFloat(standarClean);
            if (!isNaN(standarNum) && !isNaN(hasilNum)) {
                return hasilNum <= standarNum ? 'Memenuhi' : 'Tidak Memenuhi';
            }

            // Fallback string comparison
            return hasilStr.toLowerCase() === standarStr.toLowerCase() ? 'Memenuhi' : 'Tidak Memenuhi';
        }

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
            var tabel = $('#tabelLab').DataTable({
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

            var tabelBody = $('#tabelLab tbody');

            $("#menu-toggle").click(function(e) {
                e.preventDefault();
                $("#wrapper").toggleClass("toggled");
            });

            // Variabel global untuk menyimpan parameter
            let existingParameterIds = new Set();
            let parameterTableInitialized = false;

            function initializeParameterTable() {
                if (!parameterTableInitialized) {
                    $('#parameterContainer').html(`
                    <table id="tambahParameterTable" class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>No</th><th>Parameter</th><th>Satuan</th><th>Kadar Maksimum</th>
                                <th>Metode</th><th>Kategori</th><th>Hasil Uji</th><th>Keterangan</th><th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>`);
                    parameterTableInitialized = true;
                    existingParameterIds.clear();
                }
            }

            function addParameterRow(param) {
                if (existingParameterIds.has(param.id_parameter)) {
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
                    <td class="kadar-maksimum">${param.kadar_maksimum || ''}</td>
                    <td>${param.metode_uji || ''}</td>
                    <td>${param.kategori || ''}</td>
                    <td>
                        <input type="text" class="form-control form-control-sm hasil-uji-input" name="hasil[${param.id_parameter}]" value="" required>
                        <input type="hidden" name="param_details[${param.id_parameter}][nama_parameter]" value="${param.nama_parameter || ''}">
                        <input type="hidden" name="param_details[${param.id_parameter}][satuan]" value="${param.satuan || ''}">
                        <input type="hidden" name="param_details[${param.id_parameter}][kadar_maksimum]" value="${param.kadar_maksimum || ''}">
                        <input type="hidden" name="param_details[${param.id_parameter}][metode_uji]" value="${param.metode_uji || ''}">
                        <input type="hidden" name="param_details[${param.id_parameter}][kategori]" value="${param.kategori || ''}">
                    </td>
                    <td><input type="text" class="form-control form-control-sm keterangan-status" name="keterangan[${param.id_parameter}]" readonly></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-param"><i class="fa fa-times"></i> Hapus</button></td>
                </tr>`;
                $tbody.append(newRow);
                existingParameterIds.add(param.id_parameter);
                updateRowNumbers();
            }

            function updateRowNumbers() {
                $('#tambahParameterTable tbody tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
            }

            $(document).on('click', '.btn-remove-param', function() {
                let $row = $(this).closest('tr');
                let paramId = $row.data('param-id');
                existingParameterIds.delete(paramId);
                $row.remove();
                updateRowNumbers();
                if ($('#tambahParameterTable tbody tr').length === 0) {
                    $('#parameterContainer').html('<p class="text-muted">Silakan pilih paket atau tambahkan parameter secara manual...</p>');
                    parameterTableInitialized = false;
                }
            });

            // Validasi Real-time (Tambah)
            $('#parameterContainer').on('input', '.hasil-uji-input', function() {
                let $row = $(this).closest('tr');
                let hasilUji = $(this).val();
                let kadarMaksimumStr = $row.find('.kadar-maksimum').text();
                let $keteranganInput = $row.find('.keterangan-status');
                let $hasilUjiInput = $(this);

                $keteranganInput.val('').removeClass('text-bg-success text-bg-danger');
                $hasilUjiInput.removeClass('is-invalid');

                let status = cekKepatuhan(hasilUji, kadarMaksimumStr);
                if (status === 'Memenuhi') {
                    $keteranganInput.val('Memenuhi').addClass('text-bg-success');
                } else if (status === 'Tidak Memenuhi') {
                    $keteranganInput.val('Tidak Memenuhi').addClass('text-bg-danger');
                    $hasilUjiInput.addClass('is-invalid');
                }
            });

            // Validasi Real-time (Edit)
            $('#editParameterContainer').on('input', '.hasil-uji-input', function() {
                let $row = $(this).closest('tr');
                let hasilUji = $(this).val();
                let kadarMaksimumStr = $row.find('.kadar-maksimum').text();
                let $keteranganInput = $row.find('.keterangan-status');
                let $hasilUjiInput = $(this);

                $keteranganInput.val('').removeClass('text-bg-success text-bg-danger');
                $hasilUjiInput.removeClass('is-invalid');

                let status = cekKepatuhan(hasilUji, kadarMaksimumStr);
                if (status === 'Memenuhi') {
                    $keteranganInput.val('Memenuhi').addClass('text-bg-success');
                } else if (status === 'Tidak Memenuhi') {
                    $keteranganInput.val('Tidak Memenuhi').addClass('text-bg-danger');
                    $hasilUjiInput.addClass('is-invalid');
                }
            });

            // Logic Select Paket
            $('#paketSelect').change(function() {
                var id_paket = $(this).val();
                existingParameterIds.clear();
                parameterTableInitialized = false;
                $('#parameterContainer').empty();

                if (id_paket) {
                    $.ajax({
                        url: '<?= BASE_URL ?>admin/get_parameters.php',
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
                                $('#tambahParameterTable tbody').empty();
                                $.each(response.parameters, function(index, param) {
                                    addParameterRow(param);
                                });
                            } else {
                                $('#parameterContainer').html('<p class="text-muted">Tidak ada parameter untuk paket ini.</p>');
                                parameterTableInitialized = false;
                            }
                        },
                        error: function() {
                            $('#parameterContainer').html('<p class="text-danger">Gagal memuat parameter.</p>');
                        }
                    });
                } else {
                    $('#parameterContainer').html('<p class="text-muted">Silakan pilih paket...</p>');
                }
            });

            // Nested Modal Handling
            $('#addParameterBtn').on('click', function(e) {
                e.preventDefault();
                var customParameterModal = new bootstrap.Modal(document.getElementById('modalAddCustomParameter'));
                customParameterModal.show();
            });

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
                                    })
                                };
                            },
                            cache: true
                        },
                        minimumInputLength: 1
                    });
                }
            });

            $('#addSelectedParameterBtn').on('click', function() {
                var selectedOption = $('#selectParameterToAdd').select2('data');
                if (selectedOption && selectedOption.length > 0) {
                    addParameterRow(selectedOption[0].data);
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modalAddCustomParameter'));
                    modal.hide();
                    $('#selectParameterToAdd').val(null).trigger('change');
                } else {
                    alert('Pilih parameter terlebih dahulu.');
                }
            });

            // Modal Detail
            tabelBody.on('click', '.btn-detail', function() {
                var id_m_hasil_uji = $(this).data('id_m_hasil_uji');
                var no_analisa = $(this).data('no_analisa');
                $('#detailNoAnalisa').text(no_analisa);
                $.ajax({
                    url: '<?= BASE_URL ?>admin/get_detail_hasil.php',
                    type: 'POST',
                    data: {
                        id_m_hasil_uji: id_m_hasil_uji
                    },
                    beforeSend: function() {
                        $('#detailParameterContainer').html('<p class="text-info">Memuat detail...</p>');
                    },
                    success: function(response) {
                        $('#detailParameterContainer').html(response);
                    },
                    error: function() {
                        $('#detailParameterContainer').html('<p class="text-danger">Error memuat data.</p>');
                    }
                });
            });

            // --- SECURITY FIX: MODAL EDIT DENGAN DOM MANIPULATION ---
            tabelBody.on('click', '.btn-edit', function() {
                var id_m_hasil_uji = $(this).data('id_m_hasil_uji');

                $.ajax({
                    url: '<?= BASE_URL ?>admin/get_data_for_edit.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id_m_hasil_uji: id_m_hasil_uji
                    },
                    beforeSend: function() {
                        $('#formEditAll')[0].reset();
                        $('#editParameterContainer').html('<p class="text-info">Memuat data...</p>');
                        $('#editModalNoAnalisaTitle').text('Memuat...');
                    },
                    success: function(response) {
                        if (response.success) {
                            var master = response.master_data;
                            var detail = response.detail_data;

                            // Gunakan .val() untuk mengisi data (Aman dari XSS)
                            $('#edit_id_m_hasil_uji_all').val(master.id_m_hasil_uji);
                            $('#edit_nama_pelanggan_all').val(master.nama_pelanggan);
                            $('#edit_alamat_all').val(master.alamat);
                            $('#edit_status_pelanggan_all').val(master.status_pelanggan);
                            $('#edit_jenis_sampel_all').val(master.jenis_sampel);
                            $('#edit_keterangan_sampel_all').val(master.keterangan_sampel);
                            $('#edit_nama_pengirim_all').val(master.nama_pengirim);
                            $('#edit_no_analisa_all').val(master.no_analisa);
                            $('#edit_wilayah_all').val(master.wilayah);
                            $('#edit_tanggal_pengambilan_all').val(master.tanggal_pengambilan);
                            $('#edit_tanggal_pengiriman_all').val(master.tanggal_pengiriman);
                            $('#edit_tanggal_penerimaan_all').val(master.tanggal_penerimaan);
                            $('#edit_tanggal_pengujian_all').val(master.tanggal_pengujian);
                            $('#editModalNoAnalisaTitle').text(master.no_analisa);

                            var hasProses = detail.some(param => param.status === 'Proses');
                            $('#global_status_param_all').val(hasProses ? 'Proses' : 'Selesai');

                            // Membangun Tabel Menggunakan jQuery Object (Aman)
                            var $table = $('<table class="table table-bordered table-sm">');
                            var $thead = $('<thead class="table-light"><tr><th>No</th><th>Parameter</th><th>Satuan</th><th>Kadar Maksimum</th><th>Metode</th><th>Kategori</th><th>Hasil Uji</th><th>Keterangan</th></tr></thead>');
                            var $tbody = $('<tbody>');

                            if (detail.length > 0) {
                                $.each(detail, function(index, param) {
                                    let status = cekKepatuhan(param.hasil, param.kadar_maksimum);
                                    let classAwal = (status === 'Memenuhi') ? 'text-bg-success' : (status === 'Tidak Memenuhi' ? 'text-bg-danger' : '');

                                    var $tr = $('<tr>');
                                    $tr.append($('<td>').text(index + 1));
                                    $tr.append($('<td>').text(param.nama_parameter || ''));
                                    $tr.append($('<td>').text(param.satuan || ''));
                                    $tr.append($('<td class="kadar-maksimum">').text(param.kadar_maksimum || ''));
                                    $tr.append($('<td>').text(param.metode_uji || ''));
                                    $tr.append($('<td>').text(param.kategori || ''));

                                    // Input Hasil (Name menggunakan ID dari DB)
                                    var $tdHasil = $('<td>');
                                    var $inputHasil = $('<input>').attr({
                                        type: 'text',
                                        class: 'form-control form-control-sm hasil-uji-input',
                                        name: 'hasil_uji[' + param.id + ']',
                                        required: true
                                    }).val(param.hasil || '');
                                    $tdHasil.append($inputHasil);
                                    $tr.append($tdHasil);

                                    // Input Keterangan (Readonly)
                                    var $tdKet = $('<td>');
                                    var $inputKet = $('<input>').attr({
                                        type: 'text',
                                        class: 'form-control form-control-sm keterangan-status ' + classAwal,
                                        readonly: true
                                    }).val(status);
                                    $tdKet.append($inputKet);
                                    $tr.append($tdKet);

                                    $tbody.append($tr);
                                });
                            } else {
                                $tbody.append('<tr><td colspan="8" class="text-center">Tidak ada parameter uji.</td></tr>');
                            }

                            $table.append($thead).append($tbody);
                            $('#editParameterContainer').empty().append($table);

                        } else {
                            $('#editParameterContainer').html(`<p class="text-danger">Gagal memuat data: ${response.message}</p>`);
                        }
                    },
                    error: function(xhr) {
                        console.error("AJAX Error: ", xhr.responseText);
                        $('#editParameterContainer').html('<p class="text-danger">Terjadi kesalahan AJAX.</p>');
                    }
                });
            });

            // Tombol Hapus
            tabelBody.on('click', '.btn-hapus', function() {
                var id_m_hasil_uji = $(this).data('id_m_hasil_uji');
                var no_analisa = $(this).data('no_analisa');
                $('#hapus_id_m_hasil_uji').val(id_m_hasil_uji);
                $('#hapus_no_analisa').text(no_analisa);
            });
        });
    </script>

    <?php
    if (isset($con) && is_object($con) && method_exists($con, 'close')) {
        mysqli_close($con);
    }
    ?>
</body>

</html>