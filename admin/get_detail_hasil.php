<?php
// admin/get_detail_hasil.php

// 1. Output Buffering untuk mencegah error header/whitespace
ob_start();

include '../database/database.php';
include '../config.php';

// 2. Cek Session (Keamanan Wajib)
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    ob_end_clean();
    echo '<div class="alert alert-danger">Akses ditolak. Silakan login.</div>';
    exit;
}

// Pastikan koneksi database tersedia
if (!isset($con) || !$con) {
    ob_end_clean();
    echo '<div class="alert alert-danger">Koneksi database terputus.</div>';
    exit;
}

// 3. Validasi Input
if (isset($_POST['id_m_hasil_uji'])) {
    $id_m_hasil_uji = intval($_POST['id_m_hasil_uji']);

    // Query menggunakan Prepared Statement
    // Mengambil semua kolom yang diperlukan, termasuk 'keterangan'
    $query = "SELECT nama_parameter, satuan, kadar_maksimum, metode_uji, kategori, hasil, status, keterangan
              FROM hasil_uji
              WHERE id_m_hasil_uji = ?
              ORDER BY id ASC"; // Urutkan berdasarkan ID agar sesuai urutan input

    $stmt = mysqli_prepare($con, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_m_hasil_uji);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Tampung data ke array dulu agar logika tampilan terpisah dari query
        $data_detail = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data_detail[] = $row;
        }
        mysqli_stmt_close($stmt);

        // 4. Output HTML
        ob_end_clean(); // Bersihkan buffer sebelum echo HTML

        if (count($data_detail) > 0) {
?>
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-striped table-hover">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th>No</th>
                            <th>Parameter</th>
                            <th>Satuan</th>
                            <th>Kadar Maksimum</th>
                            <th>Metode</th>
                            <th>Kategori</th>
                            <th>Hasil Uji</th>
                            <th>Keterangan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($data_detail as $row) :
                            // Logika Warna Status
                            $badgeClass = ($row['status'] === 'Selesai') ? 'bg-success' : 'bg-warning text-dark';

                            // Logika Warna Keterangan (Opsional visual enhancement)
                            $ketClass = '';
                            if ($row['keterangan'] === 'Tidak Memenuhi') {
                                $ketClass = 'text-danger fw-bold';
                            } elseif ($row['keterangan'] === 'Memenuhi') {
                                $ketClass = 'text-success';
                            }
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['nama_parameter']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['satuan']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['kadar_maksimum']) ?></td>
                                <td><?= htmlspecialchars($row['metode_uji']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['kategori']) ?></td>
                                <td class="text-center fw-bold"><?= htmlspecialchars($row['hasil']) ?></td>
                                <td class="text-center <?= $ketClass ?>"><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
<?php
        } else {
            echo '<div class="alert alert-warning"><i class="fas fa-info-circle"></i> Belum ada data detail hasil uji untuk sampel ini.</div>';
        }
    } else {
        // Error Query
        error_log("Database Error get_detail_hasil: " . mysqli_error($con));
        ob_end_clean();
        echo '<div class="alert alert-danger">Terjadi kesalahan saat mengambil data.</div>';
    }
} else {
    ob_end_clean();
    echo '<div class="alert alert-danger">Permintaan tidak valid (ID tidak ditemukan).</div>';
}

if (isset($con)) {
    mysqli_close($con);
}
?>