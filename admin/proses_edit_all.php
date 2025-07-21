<?php
// lab/proses_edit_all.php
// Memproses update untuk master_hasil_uji dan semua hasil_uji (detail parameter)

// error_reporting(E_ALL); // Aktifkan ini untuk debugging
// ini_set('display_errors', 1);
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/edit_all_error.log');

include '../database/database.php';
include '../config.php';
session_start();

if ($_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($con) || !$con) {
        header("Location: laporan.php?pesan=gagal&error=db_connect_failed");
        exit();
    }

    // Ambil data master_hasil_uji
    $id_m_hasil_uji = isset($_POST['id_m_hasil_uji']) ? intval($_POST['id_m_hasil_uji']) : 0;
    $no_lab         = mysqli_real_escape_string($con, $_POST['no_lab'] ?? '');
    $jenis_air      = mysqli_real_escape_string($con, $_POST['jenis_air'] ?? '');
    $pengirim       = mysqli_real_escape_string($con, $_POST['pengirim'] ?? '');
    $penguji        = mysqli_real_escape_string($con, $_POST['penguji'] ?? '');
    $lokasi_uji     = mysqli_real_escape_string($con, $_POST['lokasi_uji'] ?? '');
    $tanggal_uji    = mysqli_real_escape_string($con, $_POST['tanggal_uji'] ?? '');

    // Ambil status global untuk semua parameter
    $global_status_param = mysqli_real_escape_string($con, $_POST['global_status_param'] ?? '');

    // Ambil data detail hasil_uji (array of hasil values)
    $hasil_uji_details = $_POST['hasil_uji'] ?? []; // Ini akan menjadi array seperti [id_hasil_uji => 'hasil_value']

    // Validasi dasar master data
    if (empty($id_m_hasil_uji) || empty($no_lab) || empty($jenis_air) || empty($pengirim) || empty($penguji) || empty($lokasi_uji) || empty($tanggal_uji) || empty($global_status_param)) {
        header("Location: laporan.php?pesan=gagal&error=invalid_master_or_global_status_data");
        exit();
    }

    // Memulai transaksi
    mysqli_begin_transaction($con);

    try {
        // 1. Update data master_hasil_uji
        // Kolom 'status' tidak ada di master_hasil_uji, jadi tidak diupdate di sini.
        $query_update_master = "UPDATE master_hasil_uji SET
                                no_lab = '$no_lab',
                                jenis_air = '$jenis_air',
                                pengirim = '$pengirim',
                                penguji = '$penguji',
                                lokasi_uji = '$lokasi_uji',
                                tanggal_uji = '$tanggal_uji'
                                WHERE id_m_hasil_uji = $id_m_hasil_uji";

        if (!mysqli_query($con, $query_update_master)) {
            throw new Exception("Error updating master_hasil_uji: " . mysqli_error($con));
        }

        // 2. Update data detail hasil_uji
        if (!empty($hasil_uji_details)) {
            foreach ($hasil_uji_details as $id_hasil_uji => $hasil_value) {
                $id_hasil_uji_clean = intval($id_hasil_uji);

                // Bug Fix 1: Mengizinkan nilai 0. Cek apakah variabel ada, bukan apakah empty.
                // Gunakan is_null untuk membedakan antara string kosong/null dan angka 0
                // Pastikan hasil_value diambil langsung dari array
                $hasil_clean = mysqli_real_escape_string($con, $hasil_value);

                // Validasi: id_hasil_uji harus valid, dan hasil_value tidak boleh NULL (0 boleh)
                if ($id_hasil_uji_clean > 0 && !is_null($hasil_value)) { // Perbaikan bug 1
                    $query_update_detail = "UPDATE hasil_uji SET
                                            hasil = '$hasil_clean',
                                            status = '$global_status_param' -- Menggunakan status global
                                            WHERE id = $id_hasil_uji_clean
                                            AND id_m_hasil_uji = $id_m_hasil_uji"; // Pastikan id_m_hasil_uji juga cocok

                    if (!mysqli_query($con, $query_update_detail)) {
                        throw new Exception("Error updating detail for ID $id_hasil_uji_clean: " . mysqli_error($con));
                    }
                } else {
                    // Opsional: Log atau tangani jika ada detail yang tidak valid
                    error_log("Invalid detail data for id_hasil_uji: $id_hasil_uji_clean, hasil: $hasil_value");
                }
            }
        }

        // Commit transaksi jika semua berhasil
        mysqli_commit($con);
        header("Location: laporan.php?pesan=sukses_edit");
        exit();
    } catch (Exception $e) {
        // Rollback jika ada kesalahan
        mysqli_rollback($con);
        error_log("Proses edit all data gagal: " . $e->getMessage());
        header("Location: laporan.php?pesan=gagal&error_msg=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: laporan.php?pesan=gagal&error=invalid_request_method");
    exit();
}
