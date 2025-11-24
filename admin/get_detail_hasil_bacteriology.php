<?php
// admin/get_detail_hasil_bacteriology.php

include '../database/database.php';
include '../config.php'; // Mungkin diperlukan untuk BASE_URL, jika tidak, bisa dihapus

// Pastikan ID diterima
if (isset($_POST['id_m_hasil_uji'])) {
    $id_m_hasil_uji = intval($_POST['id_m_hasil_uji']);

    // 1. Ambil Data Master
    $query_master = "SELECT * FROM master_hasil_uji_bacteriology WHERE id_m_hasil_uji = ?";
    $stmt_master = mysqli_prepare($con, $query_master);
    mysqli_stmt_bind_param($stmt_master, 'i', $id_m_hasil_uji);
    mysqli_stmt_execute($stmt_master);
    $result_master = mysqli_stmt_get_result($stmt_master);
    $master_data = mysqli_fetch_assoc($result_master);
    mysqli_stmt_close($stmt_master);

    if ($master_data) {
        // 2. Ambil Data Detail Parameter
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

        // 3. Format Output sebagai HTML
?>
        <div class="container-fluid">
            
            <h5 class="mb-3 text-info border-bottom pb-2">Detail Parameter Hasil Uji</h5>
            <?php if (!empty($detail_data)) : ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Parameter</th>
                                <th>Satuan</th>
                                <th>Baku Mutu</th>
                                <th>Metode Uji</th>
                                <th>Hasil</th>
                                <th>Penegasan</th>
                                <th>Keterangan</th>
                                <th>Status Uji</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detail_data as $index => $detail) : ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($detail['nama_parameter']) ?></td>
                                    <td><?= htmlspecialchars($detail['satuan']) ?></td>
                                    <td><?= htmlspecialchars($detail['nilai_baku_mutu']) ?></td>
                                    <td><?= htmlspecialchars($detail['metode_uji']) ?></td>
                                    <td><?= htmlspecialchars($detail['hasil']) ?></td>
                                    <td><?= htmlspecialchars($detail['penegasan']) ?></td>
                                    <td><?= htmlspecialchars($detail['keterangan'] ?? '-') ?></td>
                                    <td>
                                        <?php
                                        $status_class = $detail['status'] == 'Selesai' ? 'badge bg-success' : 'badge bg-warning text-dark';
                                        echo "<span class='{$status_class}'>" . htmlspecialchars($detail['status']) . "</span>";
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="alert alert-warning" role="alert">
                    Belum ada detail parameter hasil uji untuk data ini.
                </div>
            <?php endif; ?>
        </div>
<?php
    } else {
        echo '<div class="alert alert-danger">Data master tidak ditemukan.</div>';
    }
    mysqli_close($con);
} else {
    echo '<div class="alert alert-danger">ID tidak valid atau tidak diterima.</div>';
}
?>