<?php
// admin/proses_tambah_bacteriology.php
include '../database/database.php';
include '../config.php';
session_start();

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
        // 1. Insert ke tabel master
        $query_master = "INSERT INTO master_hasil_uji_bacteriology (
            nama_pelanggan, alamat, status_pelanggan, jenis_sampel, jenis_pengujian, keterangan_sampel, 
            nama_pengirim, no_analisa, wilayah, tanggal_pengambilan, tanggal_pengiriman, 
            tanggal_penerimaan, tanggal_pengujian
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt_master = mysqli_prepare($con, $query_master);
        mysqli_stmt_bind_param(
            $stmt_master,
            'sssssssssssss',
            $nama_pelanggan,
            $alamat,
            $status_pelanggan,
            $jenis_sampel,
            $jenis_pengujian,
            $keterangan_sampel,
            $nama_pengirim,
            $no_analisa,
            $wilayah,
            $tanggal_pengambilan,
            $tanggal_pengiriman,
            $tanggal_penerimaan,
            $tanggal_pengujian
        );

        mysqli_stmt_execute($stmt_master);
        $id_m_hasil_uji = mysqli_insert_id($con); // Dapatkan ID master yang baru saja di-insert

        if ($id_m_hasil_uji == 0) {
            throw new Exception("Gagal mendapatkan ID master baru.");
        }

        // 2. Insert ke tabel detail (hasil_uji)
        if (isset($_POST['param_details']) && is_array($_POST['param_details'])) {
            $param_details = $_POST['param_details'];
            $hasil_analisa = $_POST['hasil'];
            $penegasan_list = $_POST['penegasan'];
            $keterangan_list = $_POST['keterangan'];

            $query_detail = "INSERT INTO hasil_uji_bacteriology (
                id_m_hasil_uji, nama_parameter, satuan, nilai_baku_mutu, metode_uji, 
                hasil, penegasan, keterangan, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_detail = mysqli_prepare($con, $query_detail);

            foreach ($param_details as $id_param => $details) {
                $nama_parameter = $details['nama_parameter'];
                $satuan = $details['satuan'];
                $nilai_baku_mutu = $details['nilai_baku_mutu'];
                $metode_uji = $details['metode_uji'];

                $hasil = isset($hasil_analisa[$id_param]) ? $hasil_analisa[$id_param] : '';
                $penegasan = isset($penegasan_list[$id_param]) ? $penegasan_list[$id_param] : '';
                $keterangan = isset($keterangan_list[$id_param]) ? $keterangan_list[$id_param] : '';

                mysqli_stmt_bind_param(
                    $stmt_detail,
                    'issssssss',
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
                mysqli_stmt_execute($stmt_detail);
            }
            mysqli_stmt_close($stmt_detail);
        } else {
            throw new Exception("Tidak ada parameter yang dikirim.");
        }

        // Jika semua berhasil, commit transaksi
        mysqli_commit($con);
        header("location:../admin/bacteriology.php?pesan=sukses_tambah");
    } catch (Exception $e) {
        // Jika ada kesalahan, rollback transaksi
        mysqli_rollback($con);
        $error_msg = urlencode($e->getMessage());
        header("location:../admin/bacteriology.php?pesan=gagal&error_msg=" . $error_msg);
    } finally {
        if (isset($stmt_master)) mysqli_stmt_close($stmt_master);
        mysqli_close($con);
    }
} else {
    header("location:../admin/bacteriology.php?pesan=gagal");
}
