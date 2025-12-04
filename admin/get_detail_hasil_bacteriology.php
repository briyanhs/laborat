<?php
// admin/get_detail_hasil_bacteriology.php

// 1. Mulai Output Buffering
ob_start();

include '../database/database.php';
include '../config.php';

// 2. Cek Session (PENTING)
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    ob_end_clean();
    echo '<div class="alert alert-danger">Akses ditolak. Silakan login.</div>';
    exit();
}

// Pastikan ID diterima dan valid
if (isset($_POST['id_m_hasil_uji']) && is_numeric($_POST['id_m_hasil_uji'])) {
    $id_m_hasil_uji = intval($_POST['id_m_hasil_uji']);

    // 3. Ambil Data Master (Hanya untuk validasi keberadaan data)
    $query_master = "SELECT id_m_hasil_uji FROM master_hasil_uji_bacteriology WHERE id_m_hasil_uji = ?";
    $stmt_master = mysqli_prepare($con, $query_master);
    mysqli_stmt_bind_param($stmt_master, 'i', $id_m_hasil_uji);
    mysqli_stmt_execute($stmt_master);
    $result_master = mysqli_stmt_get_result($stmt_master);
    $master_exists = mysqli_num_rows($result_master) > 0;
    mysqli_stmt_close($stmt_master);

    if ($master_exists) {
        // 4. Ambil Data Detail Parameter
        $query_detail = "SELECT * FROM hasil_uji_bacteriology WHERE id_m_hasil_uji = ? ORDER BY id ASC";
        $stmt_detail = mysqli_prepare($con, $query_detail);
        mysqli_stmt_bind_param($stmt_detail, 'i', $id_m_hasil_uji);
        mysqli_stmt_execute($stmt_detail);
        $result_detail = mysqli_stmt_get_result($stmt_detail);

        $detail_data = [];
        while ($row = mysqli_fetch_assoc($result_detail)) {
            $detail_data[] = $row;
        }
        mysqli_stmt_close($stmt_detail);

        // 5. Format Output HTML
        // Bersihkan buffer sebelum output HTML
        ob_end_clean();
?>
        <div class="container-fluid">
            <h5 class="mb-3 text-info border-bottom pb-2">Detail Parameter Hasil Uji</h5>

            <?php if (!empty($detail_data)) : ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm table-striped table-hover">
                        <thead class="table-light">
                            <tr class="text-center align-middle">
                                <th>#</th>
                                <th>Parameter</th>
                                <th>Satuan</th>
                                <th>Baku Mutu</th>
                                <th>Metode Uji</th>
                                <th>Hasil</th>
                                <th>Penegasan</th>
                                <th>Keterangan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detail_data as $index => $detail) : ?>
                                <tr>
                                    <td class="text-center"><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($detail['nama_parameter']) ?></td>
                                    <td class="text-center"><?= htmlspecialchars($detail['satuan']) ?></td>
                                    <td class="text-center"><?= htmlspecialchars($detail['nilai_baku_mutu']) ?></td>
                                    <td><?= htmlspecialchars($detail['metode_uji']) ?></td>
                                    <td class="text-center fw-bold"><?= htmlspecialchars($detail['hasil']) ?></td>
                                    <td class="text-center"><?= htmlspecialchars($detail['penegasan']) ?></td>
                                    <td><?= htmlspecialchars($detail['keterangan'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <?php
                                        $status = $detail['status'] ?? 'Proses';
                                        $badgeClass = ($status === 'Selesai') ? 'bg-success' : 'bg-warning text-dark';
                                        echo "<span class='badge {$badgeClass}'>" . htmlspecialchars($status) . "</span>";
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div>Belum ada detail parameter hasil uji untuk data ini.</div>
                </div>
            <?php endif; ?>
        </div>
<?php
    } else {
        ob_end_clean();
        echo '<div class="alert alert-danger">Data master tidak ditemukan.</div>';
    }
} else {
    ob_end_clean();
    echo '<div class="alert alert-danger">Permintaan tidak valid. ID Hasil Uji diperlukan.</div>';
}

if (isset($con)) {
    mysqli_close($con);
}
?>