<?php
// lab/proses_tambah.php

// error_reporting(E_ALL); // Aktifkan ini untuk debugging
// ini_set('display_errors', 1);
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/tambah_error.log');

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
    $id_paket    = isset($_POST['id_paket']) ? intval($_POST['id_paket']) : 0;
    $lokasi_uji  = mysqli_real_escape_string($con, $_POST['lokasi_uji'] ?? '');
    $penguji     = mysqli_real_escape_string($con, $_POST['penguji'] ?? '');
    $pengirim    = mysqli_real_escape_string($con, $_POST['pengirim'] ?? '');
    $jenis_air   = mysqli_real_escape_string($con, $_POST['jenis_air'] ?? '');
    $no_lab      = mysqli_real_escape_string($con, $_POST['no_lab'] ?? '');
    $tanggal_uji = mysqli_real_escape_string($con, $_POST['tanggal_uji'] ?? '');
    $status      = mysqli_real_escape_string($con, $_POST['status'] ?? '');

    // Ambil data detail hasil_uji (array of hasil values)
    $hasil_parameters = $_POST['hasil'] ?? []; // Ini akan menjadi array seperti [id_parameter => 'hasil_value']

    // Validasi dasar master data
    if (empty($id_paket) || empty($lokasi_uji) || empty($penguji) || empty($pengirim) || empty($jenis_air) || empty($no_lab) || empty($tanggal_uji) || empty($status)) {
        header("Location: laporan.php?pesan=gagal&error=invalid_master_data_form");
        exit();
    }

    // Memulai transaksi
    mysqli_begin_transaction($con);

    try {
        // 1. Insert data ke master_hasil_uji
        $query_insert_master = "INSERT INTO master_hasil_uji (no_lab, jenis_air, pengirim, penguji, lokasi_uji, tanggal_uji) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_master = mysqli_prepare($con, $query_insert_master);
        if (!$stmt_master) {
            throw new Exception("Prepare statement master failed: " . mysqli_error($con));
        }
        mysqli_stmt_bind_param($stmt_master, "ssssss", $no_lab, $jenis_air, $pengirim, $penguji, $lokasi_uji, $tanggal_uji);

        if (!mysqli_stmt_execute($stmt_master)) {
            throw new Exception("Execute master query failed: " . mysqli_stmt_error($stmt_master));
        }
        $id_m_hasil_uji = mysqli_insert_id($con); // Dapatkan ID master yang baru saja di-generate
        mysqli_stmt_close($stmt_master);

        if ($id_m_hasil_uji == 0) {
            throw new Exception("Failed to get master ID after insertion.");
        }

        // 2. Ambil detail parameter dari database berdasarkan id_paket
        $query_param_details = "SELECT p.id_parameter, p.nama_parameter, p.satuan, p.kadar_maksimum, p.metode_uji, p.kategori 
                                FROM detail_paket_pengujian_fisika_kimia dp
                                JOIN parameter_uji p ON dp.id_parameter = p.id_parameter
                                WHERE dp.id_paket = ? ORDER BY p.id_parameter ASC";
        $stmt_param = mysqli_prepare($con, $query_param_details);
        if (!$stmt_param) {
            throw new Exception("Prepare statement param details failed: " . mysqli_error($con));
        }
        mysqli_stmt_bind_param($stmt_param, "i", $id_paket);
        mysqli_stmt_execute($stmt_param);
        $result_param = mysqli_stmt_get_result($stmt_param);

        if (!$result_param) {
            throw new Exception("Get param details result failed: " . mysqli_error($con));
        }

        // Siapkan untuk insert hasil_uji
        $query_insert_hasil = "INSERT INTO hasil_uji (id_m_hasil_uji, nama_parameter, satuan, kadar_maksimum, metode_uji, kategori, hasil, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_hasil = mysqli_prepare($con, $query_insert_hasil);
        if (!$stmt_hasil) {
            throw new Exception("Prepare statement hasil failed: " . mysqli_error($con));
        }

        $inserted_count = 0;
        while ($param_detail = mysqli_fetch_assoc($result_param)) {
            $current_param_id = $param_detail['id_parameter'];

            // Periksa apakah parameter ini ada dalam data POST dan memiliki nilai
            // Jika ada di POST dan tidak kosong, atau nilainya adalah 0 (angka), maka simpan
            if (isset($hasil_parameters[$current_param_id]) && ($hasil_parameters[$current_param_id] !== '')) { // Memungkinkan string kosong jika Anda ingin menyimpannya
                $hasil_value = mysqli_real_escape_string($con, $hasil_parameters[$current_param_id]);

                // Bind parameters dan eksekusi
                mysqli_stmt_bind_param(
                    $stmt_hasil,
                    "isssssss",
                    $id_m_hasil_uji,
                    $param_detail['nama_parameter'],
                    $param_detail['satuan'],
                    $param_detail['kadar_maksimum'],
                    $param_detail['metode_uji'],
                    $param_detail['kategori'],
                    $hasil_value, // Menggunakan nilai dari form POST
                    $status // Menggunakan status dari form master
                );

                if (!mysqli_stmt_execute($stmt_hasil)) {
                    throw new Exception("Execute hasil query for parameter ID " . $current_param_id . " failed: " . mysqli_stmt_error($stmt_hasil));
                }
                $inserted_count++;
            }
            // Jika parameter tidak ada di $hasil_parameters atau kosong, maka parameter ini diabaikan
        }

        mysqli_stmt_close($stmt_param);
        mysqli_stmt_close($stmt_hasil);

        if ($inserted_count === 0 && !empty($hasil_parameters)) {
            // Jika ada parameter yang dikirim tapi tidak ada yang disimpan
            // Ini bisa terjadi jika semua yang dikirim adalah string kosong (kalau Anda ingin string kosong diabaikan)
            // Atau bisa juga semua parameter dihapus di client-side
            throw new Exception("No valid detail parameters were submitted or saved.");
        }

        // Commit transaksi jika semua berhasil
        mysqli_commit($con);
        header("Location: laporan.php?pesan=sukses_tambah");
        exit();
    } catch (Exception $e) {
        // Rollback jika ada kesalahan
        mysqli_rollback($con);
        error_log("Proses tambah data gagal: " . $e->getMessage());
        header("Location: laporan.php?pesan=gagal&error_msg=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: laporan.php?pesan=gagal&error=invalid_request_method");
    exit();
}
