<?php
// admin/proses_edit_bacteriology.php

include '../database/database.php'; // Sesuaikan path jika perlu
session_start(); // Jika perlu validasi login

// Pastikan request adalah POST dan ID Master ada
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_m_hasil_uji'])) {

    $id_m_hasil_uji = intval($_POST['id_m_hasil_uji']);

    // Ambil data master dari POST (Gunakan htmlspecialchars untuk keamanan dasar)
    $no_analisa = htmlspecialchars($_POST['no_analisa']);
    $nama_pelanggan = htmlspecialchars($_POST['nama_pelanggan']);
    $alamat = htmlspecialchars($_POST['alamat']);
    $status_pelanggan = htmlspecialchars($_POST['status_pelanggan']);
    $wilayah = htmlspecialchars($_POST['wilayah']);
    $jenis_sampel = htmlspecialchars($_POST['jenis_sampel']);
    $jenis_pengujian = htmlspecialchars($_POST['jenis_pengujian']);
    $keterangan_sampel = htmlspecialchars($_POST['keterangan_sampel']);
    $nama_pengirim = htmlspecialchars($_POST['nama_pengirim']);

    // --- AMBIL NILAI TANGGAL DARI POST ---
    $tanggal_pengambilan = $_POST['tanggal_pengambilan'];
    $tanggal_pengiriman = $_POST['tanggal_pengiriman'];
    $tanggal_penerimaan = $_POST['tanggal_penerimaan'];
    $tanggal_pengujian = $_POST['tanggal_pengujian'];
    // --- AKHIR AMBIL TANGGAL ---


    // Ambil data detail dari POST
    $detail_ids = $_POST['detail_ids'] ?? []; // Array ID detail yang ada di form
    $hasil_list = $_POST['hasil'] ?? [];
    $penegasan_list = $_POST['penegasan'] ?? [];
    $keterangan_list = $_POST['keterangan'] ?? [];
    $global_status = htmlspecialchars($_POST['global_status']); // Status global (Proses/Selesai)

    mysqli_begin_transaction($con); // Mulai transaksi

    try {
        // 1. Update Master Data
        // --- PERBARUI QUERY UPDATE MASTER ---
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
        // --- AKHIR PERBARUI QUERY ---

        $stmt_master = mysqli_prepare($con, $query_master_update);
        if (!$stmt_master) {
            throw new Exception("Prepare statement master gagal: " . mysqli_error($con));
        }

        // --- SESUAIKAN bind_param (tambahkan 4 's' untuk tanggal) ---
        mysqli_stmt_bind_param(
            $stmt_master,
            'sssssssssssssi', // Total 13 's' + 1 'i'
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
            $tanggal_pengujian, // Tambahkan variabel tanggal
            $id_m_hasil_uji
        );
        // --- AKHIR SESUAIKAN bind_param ---

        if (!mysqli_stmt_execute($stmt_master)) {
            throw new Exception("Eksekusi update master gagal: " . mysqli_stmt_error($stmt_master));
        }
        mysqli_stmt_close($stmt_master);

        // 2. Update Detail Data (Kode ini tetap sama)
        if (!empty($detail_ids)) {
            $query_detail_update = "UPDATE hasil_uji_bacteriology SET
                                        hasil = ?,
                                        penegasan = ?,
                                        keterangan = ?,
                                        status = ?
                                    WHERE id = ? AND id_m_hasil_uji = ?";
            $stmt_detail = mysqli_prepare($con, $query_detail_update);
            if (!$stmt_detail) {
                throw new Exception("Prepare statement detail gagal: " . mysqli_error($con));
            }

            foreach ($detail_ids as $detail_id_str) {
                $detail_id = intval($detail_id_str);
                $hasil = isset($hasil_list[$detail_id]) ? htmlspecialchars($hasil_list[$detail_id]) : '';
                $penegasan = isset($penegasan_list[$detail_id]) ? htmlspecialchars($penegasan_list[$detail_id]) : '';
                $keterangan = isset($keterangan_list[$detail_id]) ? htmlspecialchars($keterangan_list[$detail_id]) : '';

                mysqli_stmt_bind_param(
                    $stmt_detail,
                    'ssssii',
                    $hasil,
                    $penegasan,
                    $keterangan,
                    $global_status,
                    $detail_id,
                    $id_m_hasil_uji
                );
                if (!mysqli_stmt_execute($stmt_detail)) {
                    throw new Exception("Eksekusi update detail (ID: $detail_id) gagal: " . mysqli_stmt_error($stmt_detail));
                }
            }
            mysqli_stmt_close($stmt_detail);
        }

        // Jika semua berhasil
        mysqli_commit($con);
        header("location:../admin/bacteriology.php?pesan=sukses_edit");
        exit();
    } catch (Exception $e) {
        // Jika ada error
        mysqli_rollback($con);
        $error_msg = urlencode($e->getMessage());
        error_log("Error proses_edit_bacteriology: " . $e->getMessage());
        header("location:../admin/bacteriology.php?pesan=gagal&error_msg=" . $error_msg);
        exit();
    }
} else {
    // Jika request tidak valid atau ID tidak ada
    header("location:../admin/bacteriology.php?pesan=gagal&error_msg=" . urlencode("Permintaan tidak valid."));
    exit();
}

if (isset($con)) {
    mysqli_close($con);
}
