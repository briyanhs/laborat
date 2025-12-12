<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Laporan Hasil Uji Bakteriologi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 10mm 15mm;
            font-size: 10pt;
        }

        .header {
            text-align: center;
            margin-bottom: 8px;
            line-height: 1.1;
        }

        .header img {
            width: 65px;
            float: left;
            margin-right: 10px;
        }

        .header h4 {
            font-size: 12pt;
            margin-top: 0;
            margin-bottom: 2px;
        }

        .header p {
            font-size: 7.5pt;
            margin: 0;
        }

        .line {
            border-bottom: 0.5px solid #000;
            margin-top: 3px;
            margin-bottom: 12px;
        }

        .title-container h3 {
            font-size: 14pt;
            margin: 0;
            padding-bottom: 2px;
            text-align: center;
            width: 100%;
        }

        .title-container h4 {
            font-size: 10pt;
            margin: 5px 0 5px 0;
            font-weight: bold;
            text-align: left;
            text-decoration: underline;
        }

        /* Style untuk tabel informasi yang presisi */
        .info-container-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 2px;
        }

        .info-container-table td {
            vertical-align: top;
            width: 50%;
        }

        .nested-info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .nested-info-table td {
            padding: 1.5px 0;
        }

        .nested-info-table .label {
            width: 140px;
            /* Atur lebar tetap untuk label */
        }

        .nested-info-table .separator {
            width: 10px;
            /* Atur lebar tetap untuk titik dua */
        }

        .parameter-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1px;
        }

        .parameter-table th,
        .parameter-table td {
            border: 1px solid #000;
            padding: 2px;
            text-align: center;
            font-size: 9pt;
        }

        .parameter-table th {
            background-color: #f2f2f2;
        }

        .parameter-table td.text-left {
            text-align: left;
            padding-left: 5px;
        }

        .notes {
            font-size: 7pt;
            margin-top: 1px;
            line-height: 1.2;
        }

        .notes p {
            font-size: 7pt;
            margin-top: 1px;
            line-height: 1.2;
            font-weight: bold;
        }

        .notes ol {
            margin-left: 12px;
            padding-left: 0;
        }

        .notes li {
            margin-bottom: 1px;
        }

        .signature-section {
            margin-top: 60px;
            font-size: 8pt;
            text-align: center;
        }

        .signature-section .name-line {
            border-bottom: 1px solid black;
            display: inline-block;
            padding: 0 10px;
            margin-bottom: 1px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <table style="width:100%; border-collapse: collapse;">
            <tr>
                <td style="text-align: center; vertical-align: top;">
                    <?php
                    
                    // 1. Logo Pemkot (Kiri)
                    // Pastikan path ini benar relatif dari file generate_pdf.php (di folder admin)
                    $path_pemkot = '../image/logo_pemkot.png'; 
                    $data_pemkot = file_get_contents($path_pemkot);
                    $base64_pemkot = 'data:image/png;base64,' . base64_encode($data_pemkot);

                    // 2. Logo Toya Wening (Kanan)
                    $path_toya = '../image/logo_toyawening.png';
                    $data_toya = file_get_contents($path_toya);
                    $base64_toya = 'data:image/png;base64,' . base64_encode($data_toya);
                    ?>

                    <img src="<?= $base64_pemkot ?>" style="width: 90px; float: left; margin-right: 10px;">
                    
                    <img src="<?= $base64_toya ?>" style="width: 90px; float: right; margin-left: 10px;">
                    <div style="overflow: hidden;">
                        <h4>PEMERINTAH KOTA SURAKARTA</h4>
                        <h4>PERUSAHAAN UMUM DAERAH AIR MINUM</h4>
                        <p>Jl. LUU, Adi Sucipto No. 143 Telp. (0271) 712465, 723093, Fax. (0271) 712536</p>
                        <p>E-mail: pdamSolo@indo.net.id | pdam@toyaweningsolo.co.id</p>
                        <p>Website: www.toyaweningsolo.co.id</p>
                        <p>SURAKARTA 57145</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="line"></div>

    <div class="title-container">
        <h3>LAPORAN HASIL UJI</h3>
        <h4>Pengujian Laboratorium Mikrobiologi</h4>
    </div>

    <table class="info-container-table">
        <tr>
            <td>
                <table class="nested-info-table">
                    <tr>
                        <td class="label">Nama Pelanggan</td>
                        <td class="separator">:</td>
                        <td><?= htmlspecialchars($master_data['nama_pelanggan']) ?></td>
                    </tr>
                    <tr>
                        <td class="label">Alamat</td>
                        <td class="separator">:</td>
                        <td><?= htmlspecialchars($master_data['alamat']) ?></td>
                    </tr>
                    <tr>
                        <td class="label">Jenis Sampel</td>
                        <td class="separator">:</td>
                        <td><?= htmlspecialchars($master_data['jenis_sampel']) ?></td>
                    </tr>
                    <tr>
                        <td class="label">Keterangan Sampel</td>
                        <td class="separator">:</td>
                        <td><?= htmlspecialchars($master_data['keterangan_sampel']) ?></td>
                    </tr>
                    <tr>
                        <td class="label">Nama Pengirim</td>
                        <td class="separator">:</td>
                        <td><?= htmlspecialchars($master_data['nama_pengirim']) ?></td>
                    </tr>
                    <tr>
                        <td class="label">Nomer Analisa</td>
                        <td class="separator">:</td>
                        <td><?= htmlspecialchars($master_data['no_analisa']) ?></td>
                    </tr>
                    <tr>
                        <td class="label">Jenis Pengujian</td>
                        <td class="separator">:</td>
                        <td><?= htmlspecialchars($master_data['jenis_pengujian']) ?></td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="nested-info-table">
                    <tr>
                        <td class="label">Tanggal Pengambilan</td>
                        <td class="separator">:</td>
                        <td><?= !empty($master_data['tanggal_pengambilan']) ? date('d-m-Y', strtotime($master_data['tanggal_pengambilan'])) : '-' ?></td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal Pengiriman</td>
                        <td class="separator">:</td>
                        <td><?= !empty($master_data['tanggal_pengiriman']) ? date('d-m-Y', strtotime($master_data['tanggal_pengiriman'])) : '-' ?></td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal Penerimaan</td>
                        <td class="separator">:</td>
                        <td><?= !empty($master_data['tanggal_penerimaan']) ? date('d-m-Y', strtotime($master_data['tanggal_penerimaan'])) : '-' ?></td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal Pengujian</td>
                        <td class="separator">:</td>
                        <td><?= !empty($master_data['tanggal_pengujian']) ? date('d-m-Y', strtotime($master_data['tanggal_pengujian'])) : '-' ?></td>
                    </tr>
                </table>   
            </td>
        </tr>
    </table>

    <table class="parameter-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 5%;">No</th>
                <th rowspan="2" style="width: 20%;">Hasil Uji</th>
                <th rowspan="2" style="width: 10%;">Satuan</th>
                <th rowspan="2" style="width: 10%;">Nilai Baku Mutu</th>
                <th colspan="2" style="width: 20%;">Hasil Pengujian</th>
                <th rowspan="2" style="width: 15%;">Ket.</th>
                <th rowspan="2" style="width: 20%;">Metode Uji</th>
            </tr>
            <tr>
                <th>Hasil Analisa</th>
                <th>Penegasan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($detail_data)) : ?>
                <?php foreach ($detail_data as $index => $detail) : ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td class="text-left"><?= htmlspecialchars($detail['nama_parameter']) ?></td>
                        <td><?= htmlspecialchars($detail['satuan']) ?></td>
                        <td><?= htmlspecialchars($detail['nilai_baku_mutu']) ?></td>
                        <td><?= htmlspecialchars($detail['hasil']) ?></td>
                        <td><?= htmlspecialchars($detail['penegasan']) ?></td>
                        <td><?= htmlspecialchars($detail['keterangan'] ?? '') // Tampilkan string kosong jika null 
                            ?></td>
                        <td class="text-left"><?= htmlspecialchars($detail['metode_uji']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 10px;">Data hasil uji tidak ditemukan.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="notes">
        <p>Persyaratan Kualitas Air Untuk Keperluan Higiene Sanitasi Per. Men. Kes. RI. No. 2 Tahun 2023 <br>
            Catatan: </p>
        <ol>
            <li>Hasil uji ini hanya berlaku untuk contoh yang diuji.</li>
            <li>Laporan Hasil Uji ini tidak boleh digandakan tanpa izin Laboratorium PERUMDA Air Minum Kota Surakarta, kecuali secara lengkap.</li>
        </ol>
    </div>

    <div class="signature-section mt-2">
        <?php
        // Variabel $verifiers dan $qrCodeBase64 dikirim dari generate_pdf.php
        // Kita cek apakah QR Code sudah dibuat (artinya min 1 verifikasi)
        if (empty($qrCodeBase64)):
        ?>

            <table style="width:100%;" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="width:50%; text-align: center; vertical-align: top;">
                        <br><br>Diteliti<br>Manajer Perencanaan dan<br>Pengembangan<br><br><br><br>
                        <span class="name-line">Harry Arifian Muam, A.Md</span>
                        <br>NPP. 577 120 384
                    </td>
                    <td style="width:50%; text-align: center; vertical-align: top;">
                        <p>Surakarta, <?= !empty($master_data['tanggal_pengujian']) ? date('d F Y', strtotime($master_data['tanggal_pengujian'])) : '' ?></p>
                        Diperiksa<br>Asisten Manajer Laboratorium<br><br><br><br>
                        <span class="name-line">Ratih Hastuti, S.Si</span>
                        <br>NPP. 348 290 970
                    </td>
                </tr>
            </table>
            <div style="text-align: center; margin-top: 5px;">
                <p>Mengetahui:<br>Direksi PERUMDA Air Minum Kota Surakarta<br>Direktur Teknik,<br><br><br></p>
                <p style="margin-top: 5px;">
                    <span class="name-line">Sarwoko Priyo Saptono, S.H</span>
                    <br>NPP. 450 190 269
                </p>
            </div>

        <?php else: ?>

            <table style="width:100%;" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="width:50%; text-align: center; vertical-align: top;">
                        <?php
                        // PASTIKAN STRING INI SAMA PERSIS DENGAN ISI DI KOLOM 'nama' DATABASE ANDA
                        if (isset($verifiers['Harry Arifian Muam, A.Md'])):
                        ?>
                            <p style="font-size: 8pt; margin-top: 38px; margin-bottom: 2px;">Telah Diverifikasi Secara Digital</p>
                            <img src="<?php echo $qrCodeBase64; ?>" alt="Verifikasi" style="width: 50px; height: 50px; margin-bottom: 5px;">
                            <br><span class="name-line">Harry Arifian Muam, A.Md</span>
                            <br>NPP. 577 120 384
                        <?php else: ?>
                            <br><br>Diteliti<br>Manajer Perencanaan dan<br>Pengembangan<br><br><br><br>
                            <span class="name-line">Harry Arifian Muam, A.Md</span>
                            <br>NPP. 577 120 384
                        <?php endif; ?>
                    </td>

                    <td style="width:50%; text-align: center; vertical-align: top;">
                        <p>Surakarta, <?= !empty($master_data['tanggal_pengujian']) ? date('d F Y', strtotime($master_data['tanggal_pengujian'])) : '' ?></p>
                        <?php
                        // PASTIKAN STRING INI SAMA PERSIS DENGAN ISI DI KOLOM 'nama' DATABASE ANDA
                        if (isset($verifiers['Ratih Hastuti, S.Si'])):
                        ?>
                            <p style="font-size: 8pt; margin-top: 14px; margin-bottom: 2px;">Telah Diverifikasi Secara Digital</p>
                            <img src="<?php echo $qrCodeBase64; ?>" alt="Verifikasi" style="width: 50px; height: 50px; margin-bottom: 5px;">
                            <br><span class="name-line">Ratih Hastuti, S.Si</span>
                            <br>NPP. 348 290 970
                        <?php else: ?>
                            Diperiksa<br>Asisten Manajer Laboratorium<br><br><br><br>
                            <span class="name-line">Ratih Hastuti, S.Si</span>
                            <br>NPP. 348 290 970
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <div style="text-align: center; margin-top: 5px;">
                <?php
                // PASTIKAN STRING INI SAMA PERSIS DENGAN ISI DI KOLOM 'nama' DATABASE ANDA
                if (isset($verifiers['Sarwoko Priyo Saptono, S.H'])):
                ?>
                    <p>Mengetahui:<br>Direksi PERUMDA Air Minum Kota Surakarta<br>Direktur Teknik,</p>
                    <p style="font-size: 8pt; margin-top: 14px; margin-bottom: 2px;">Telah Diverifikasi Secara Digital</p>
                    <img src="<?php echo $qrCodeBase64; ?>" alt="Verifikasi" style="width: 50px; height: 50px; margin-bottom: 5px;">
                    <p style="margin-top: 2px;">
                        <span class="name-line">Sarwoko Priyo Saptono, S.H</span>
                        <br>NPP. 450 190 269
                    </p>
                <?php else: ?>
                    <p>Mengetahui:<br>Direksi PERUMDA Air Minum Kota Surakarta<br>Direktur Teknik,<br><br><br></p>
                    <p style="margin-top: 5px;">
                        <span class="name-line">Sarwoko Priyo Saptono, S.H</span>
                        <br>NPP. 450 190 269
                    </p>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </div>
</body>

</html>