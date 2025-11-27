<?php
// admin/proses_tambah_bacteriology.php
include '../database/database.php';
include '../config.php';
session_start();

// 1. Cek CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Token CSRF tidak valid! Akses ditolak.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data master
    $nama_pelanggan = $_POST['nama_pelanggan'];
    $alamat = $_POST['alamat'];
    $status_pelanggan = $_POST['status_pelanggan'];
    $jenis_sampel = $_POST['jenis_sampel'];
    $jenis_pengujian = $_POST['jenis_pengujian'];
    $keterangan_sampel = $_POST['keterangan_sampel'];
    $nama_pengirim = $_POST['nama_pengirim'];
    $no_analisa = $_POST['no_analisa'];
    $wilayah = $_POST['wilayah'];
    $tanggal_pengambilan = $_POST['tanggal_pengambilan'];
    $tanggal_pengiriman = $_POST['tanggal_pengiriman'];
    $tanggal_penerimaan = $_POST['tanggal_penerimaan'];
    $tanggal_pengujian = $_POST['tanggal_pengujian'];
    $status_global = $_POST['status']; // Status global (Proses/Selesai)

    // Mulai transaksi
    mysqli_begin_transaction($con);

    try {
        // 2. Insert ke tabel master (GUNAKAN PREPARED STATEMENT)
        // Kolom ID auto-increment dan token NULL tidak perlu ditulis di VALUES jika kita sebutkan nama kolomnya
        $query_master = "INSERT INTO master_hasil_uji_bacteriology 
                        (nama_pelanggan, alamat, status_pelanggan, tanggal_pengambilan, tanggal_pengiriman, tanggal_penerimaan, tanggal_pengujian, nama_pengirim, jenis_sampel, jenis_pengujian, keterangan_sampel, no_analisa, wilayah, verification_token) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)";

        $stmt_master = mysqli_prepare($con, $query_master);
        if (!$stmt_master) {
            throw new Exception("Gagal prepare master: " . mysqli_error($con));
        }

        // Bind parameter (13 string 's')
        mysqli_stmt_bind_param(
            $stmt_master,
            "sssssssssssss",
            $nama_pelanggan,
            $alamat,
            $status_pelanggan,
            $tanggal_pengambilan,
            $tanggal_pengiriman,
            $tanggal_penerimaan,
            $tanggal_pengujian,
            $nama_pengirim,
            $jenis_sampel,
            $jenis_pengujian,
            $keterangan_sampel,
            $no_analisa,
            $wilayah
        );

        if (!mysqli_stmt_execute($stmt_master)) {
            throw new Exception("Gagal eksekusi master: " . mysqli_stmt_error($stmt_master));
        }

        // Ambil ID yang baru saja dibuat
        $id_m_hasil_uji = mysqli_insert_id($con);
        mysqli_stmt_close($stmt_master);

        // 3. Proses Detail Parameter
        if (isset($_POST['hasil']) && is_array($_POST['hasil'])) {
            $hasil_analisa = $_POST['hasil'];
            $penegasan_list = $_POST['penegasan'] ?? [];
            $keterangan_list = $_POST['keterangan'] ?? [];
            $param_details = $_POST['param_details'] ?? []; // Data hidden (nama, satuan, baku mutu, metode)

            $query_detail = "INSERT INTO hasil_uji_bacteriology 
                            (id_m_hasil_uji, nama_parameter, satuan, nilai_baku_mutu, metode_uji, hasil, penegasan, keterangan, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt_detail = mysqli_prepare($con, $query_detail);

            foreach ($hasil_analisa as $id_param => $hasil) {
                // Ambil detail parameter dari input hidden
                $nama_parameter = $param_details[$id_param]['nama_parameter'] ?? '';
                $satuan = $param_details[$id_param]['satuan'] ?? '';
                $nilai_baku_mutu = $param_details[$id_param]['nilai_baku_mutu'] ?? '';
                $metode_uji = $param_details[$id_param]['metode_uji'] ?? '';

                $penegasan = $penegasan_list[$id_param] ?? '';
                $keterangan = $keterangan_list[$id_param] ?? '';

                mysqli_stmt_bind_param(
                    $stmt_detail,
                    "issssssss",
                    $id_m_hasil_uji,
                    $nama_parameter,
                    $satuan,
                    $nilai_baku_mutu,
                    $metode_uji,
                    $hasil,
                    $penegasan,
                    $keterangan,
                    $status_global
                );

                if (!mysqli_stmt_execute($stmt_detail)) {
                    throw new Exception("Gagal simpan detail parameter ID: $id_param");
                }
            }
            mysqli_stmt_close($stmt_detail);
        }

        // Jika semua berhasil, commit transaksi
        mysqli_commit($con);
        header("location:../bacteriology.php?pesan=sukses_tambah");
    } catch (Exception $e) {
        // Jika ada kesalahan, rollback transaksi
        mysqli_rollback($con);
        $error_msg = urlencode($e->getMessage());
        header("location:../bacteriology.php?pesan=gagal&error_msg=" . $error_msg);
    } finally {
        mysqli_close($con);
    }
}
