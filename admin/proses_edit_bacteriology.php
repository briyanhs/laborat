<?php
// admin/proses_edit_bacteriology.php

include '../database/database.php';
include '../config.php';
session_start();

// 1. Cek Token CSRF (Keamanan)
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Akses Ditolak: Token CSRF tidak valid.");
}

// 2. Cek Session Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../index.php?pesan=belum_login");
    exit();
}

// Pastikan request POST dan ID ada
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_m_hasil_uji'])) {

    $id_m_hasil_uji = intval($_POST['id_m_hasil_uji']);

    // --- AMBIL DATA MASTER (TANPA htmlspecialchars) ---
    // Kita simpan data mentah agar format di database bersih. 
    // Sanitasi SQL sudah dihandle oleh Prepared Statement.
    $no_analisa        = $_POST['no_analisa'] ?? '';
    $nama_pelanggan    = $_POST['nama_pelanggan'] ?? '';
    $alamat            = $_POST['alamat'] ?? '';
    $status_pelanggan  = $_POST['status_pelanggan'] ?? '';
    $wilayah           = $_POST['wilayah'] ?? '';
    $jenis_sampel      = $_POST['jenis_sampel'] ?? '';
    $jenis_pengujian   = $_POST['jenis_pengujian'] ?? '';
    $keterangan_sampel = $_POST['keterangan_sampel'] ?? '';
    $nama_pengirim     = $_POST['nama_pengirim'] ?? '';

    // --- HANDLING TANGGAL (Agar bisa NULL jika kosong) ---
    $tanggal_pengambilan = !empty($_POST['tanggal_pengambilan']) ? $_POST['tanggal_pengambilan'] : NULL;
    $tanggal_pengiriman  = !empty($_POST['tanggal_pengiriman']) ? $_POST['tanggal_pengiriman'] : NULL;
    $tanggal_penerimaan  = !empty($_POST['tanggal_penerimaan']) ? $_POST['tanggal_penerimaan'] : NULL;
    $tanggal_pengujian   = !empty($_POST['tanggal_pengujian']) ? $_POST['tanggal_pengujian'] : NULL;

    // Ambil data detail
    $detail_ids      = $_POST['detail_ids'] ?? [];
    $hasil_list      = $_POST['hasil'] ?? [];
    $penegasan_list  = $_POST['penegasan'] ?? [];
    $keterangan_list = $_POST['keterangan'] ?? [];
    $global_status   = $_POST['global_status'] ?? 'Proses';

    // Mulai Transaksi Database
    mysqli_begin_transaction($con);

    try {
        // ==========================================
        // 1. UPDATE DATA MASTER
        // ==========================================
        $query_master_update = "UPDATE master_hasil_uji_bacteriology SET
                                    no_analisa = ?,
                                    nama_pelanggan = ?,
                                    alamat = ?,
                                    status_pelanggan = ?,
                                    wilayah = ?,
                                    jenis_sampel = ?,
                                    jenis_pengujian = ?,
                                    keterangan_sampel = ?,
                                    nama_pengirim = ?,
                                    tanggal_pengambilan = ?,
                                    tanggal_pengiriman = ?,
                                    tanggal_penerimaan = ?,
                                    tanggal_pengujian = ?
                                WHERE id_m_hasil_uji = ?";

        $stmt_master = mysqli_prepare($con, $query_master_update);
        if (!$stmt_master) {
            throw new Exception("Prepare master gagal: " . mysqli_error($con));
        }

        // Bind Param: 13 String ('s') dan 1 Integer ('i')
        mysqli_stmt_bind_param(
            $stmt_master,
            'sssssssssssssi',
            $no_analisa,
            $nama_pelanggan,
            $alamat,
            $status_pelanggan,
            $wilayah,
            $jenis_sampel,
            $jenis_pengujian,
            $keterangan_sampel,
            $nama_pengirim,
            $tanggal_pengambilan,
            $tanggal_pengiriman,
            $tanggal_penerimaan,
            $tanggal_pengujian,
            $id_m_hasil_uji
        );

        if (!mysqli_stmt_execute($stmt_master)) {
            throw new Exception("Gagal update master: " . mysqli_stmt_error($stmt_master));
        }
        mysqli_stmt_close($stmt_master);

        // ==========================================
        // 2. UPDATE DATA DETAIL
        // ==========================================
        if (!empty($detail_ids) && is_array($detail_ids)) {
            $query_detail_update = "UPDATE hasil_uji_bacteriology SET
                                        hasil = ?,
                                        penegasan = ?,
                                        keterangan = ?,
                                        status = ?
                                    WHERE id = ? AND id_m_hasil_uji = ?";

            $stmt_detail = mysqli_prepare($con, $query_detail_update);
            if (!$stmt_detail) {
                throw new Exception("Prepare detail gagal: " . mysqli_error($con));
            }

            foreach ($detail_ids as $detail_id_str) {
                $detail_id = intval($detail_id_str);

                // Ambil nilai dari array input, default string kosong jika tidak ada
                $hasil      = $hasil_list[$detail_id] ?? '';
                $penegasan  = $penegasan_list[$detail_id] ?? '';
                $keterangan = $keterangan_list[$detail_id] ?? '';

                mysqli_stmt_bind_param(
                    $stmt_detail,
                    'ssssii',
                    $hasil,
                    $penegasan,
                    $keterangan,
                    $global_status, // Menggunakan status global untuk semua parameter
                    $detail_id,
                    $id_m_hasil_uji
                );

                if (!mysqli_stmt_execute($stmt_detail)) {
                    throw new Exception("Gagal update detail ID $detail_id: " . mysqli_stmt_error($stmt_detail));
                }
            }
            mysqli_stmt_close($stmt_detail);
        }

        // Jika semua sukses, Commit perubahan
        mysqli_commit($con);

        // Redirect Sukses
        header("location:../admin/bacteriology.php?pesan=sukses_edit");
        exit();
    } catch (Exception $e) {
        // Jika ada error, batalkan semua perubahan
        mysqli_rollback($con);

        // Log error di server (untuk admin)
        error_log("Error Edit Bacteriology: " . $e->getMessage());

        // Redirect Gagal dengan pesan
        header("location:../admin/bacteriology.php?pesan=gagal&error_msg=" . urlencode("Gagal memperbarui data."));
        exit();
    }
} else {
    // Akses invalid
    header("location:../admin/bacteriology.php?pesan=gagal&error_msg=" . urlencode("Request tidak valid."));
    exit();
}

if (isset($con)) {
    mysqli_close($con);
}
