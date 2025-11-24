<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dokumen</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
        }

        .container {
            max-width: 600px;
            margin-top: 50px;
        }

        .card-header {
            font-size: 1.5rem;
        }

        .icon {
            font-size: 3rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card text-center shadow-sm">
            <?php
            include 'database/database.php';
            include 'config.php';

            $token = $_GET['token'] ?? '';
            $master_data = null;
            $verifiers = [];
            $tipe_uji = '';
            $id_hasil = 0;

            if (!empty($token)) {
                // Cek tabel fisika
                $stmt_f = mysqli_prepare($con, "SELECT id_m_hasil_uji, no_analisa FROM master_hasil_uji WHERE verification_token = ?");
                mysqli_stmt_bind_param($stmt_f, "s", $token);
                mysqli_stmt_execute($stmt_f);
                $master_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_f));
                if ($master_data) {
                    $tipe_uji = 'fisika';
                    $id_hasil = $master_data['id_m_hasil_uji'];
                }

                if (!$master_data) {
                    // Cek tabel bakteriologi
                    $stmt_b = mysqli_prepare($con, "SELECT id_m_hasil_uji, no_analisa FROM master_hasil_uji_bacteriology WHERE verification_token = ?");
                    mysqli_stmt_bind_param($stmt_b, "s", $token);
                    mysqli_stmt_execute($stmt_b);
                    $master_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_b));
                    if ($master_data) {
                        $tipe_uji = 'bakteri';
                        $id_hasil = $master_data['id_m_hasil_uji'];
                    }
                }

                // Jika data master ditemukan, cari siapa saja verifikatornya
                if ($master_data) {
                    $query_log = "
                    SELECT u.nama, lv.verification_timestamp
                    FROM log_verifikasi lv
                    JOIN user u ON lv.id_user_verifier = u.id_user
                    WHERE lv.id_hasil_uji = ? AND lv.tipe_uji = ?
                    ORDER BY lv.verification_timestamp ASC
                ";
                    $stmt_log = mysqli_prepare($con, $query_log);
                    mysqli_stmt_bind_param($stmt_log, "is", $id_hasil, $tipe_uji);
                    mysqli_stmt_execute($stmt_log);
                    $result_log = mysqli_stmt_get_result($stmt_log);
                    while ($row = mysqli_fetch_assoc($result_log)) {
                        $verifiers[] = $row;
                    }
                }
                mysqli_close($con);
            }

            if ($master_data && !empty($verifiers)):
            ?>
                <div class="card-header bg-success text-white">
                    <i class="icon fas fa-check-circle d-block mt-3 mb-2"></i>
                    Dokumen Terverifikasi
                </div>
                <div class="card-body p-4">
                    <p class="lead">Dokumen hasil uji dengan rincian:</p>
                    <ul class="list-group list-group-flush mb-3 text-start">
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>Nomor Analisa:</strong>
                            <span><?php echo htmlspecialchars($master_data['no_analisa']); ?></span>
                        </li>
                    </ul>

                    <p class="lead mt-4">Telah Diverifikasi Oleh:</p>
                    <ul class="list-group list-group-flush mb-3 text-start">
                        <?php foreach ($verifiers as $v): ?>
                            <li class="list-group-item">
                                <i class="fa fa-check-circle text-success me-2"></i>
                                <strong><?php echo htmlspecialchars($v['nama']); ?></strong>
                                <br>
                                <small class="text-muted">Pada: <?php echo date('d F Y, H:i', strtotime($v['verification_timestamp'])); ?> WIB</small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="text-muted small mt-4">PDAM Toya Wening Surakarta</p>
                </div>
            <?php else: ?>
                <div class="card-header bg-danger text-white">
                    <i class="icon fas fa-times-circle d-block mt-3 mb-2"></i>
                    Verifikasi Gagal
                </div>
                <div class="card-body p-4">
                    <p class="lead text-danger">Token verifikasi tidak valid atau data belum diverifikasi.</p>
                    <p>Pastikan Anda memindai QR Code yang benar dari dokumen resmi.</p>
                    <p class="text-muted small mt-4">PDAM Toya Wening Surakarta</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>