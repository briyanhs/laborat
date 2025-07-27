<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Laporan Hasil Uji</title>
    <style>
        /* Seluruh CSS Anda dari sebelumnya */
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

        h3 {
            font-size: 12.5pt;
            margin: 5px 0 10px 0;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8.5pt;
        }

        .info-table td {
            padding: 1px 0;
            vertical-align: top;
        }

        .info-table td.label {
            width: 120px;
        }

        .info-table td.value {
            width: calc(50% - 120px);
        }

        .parameter-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .parameter-table th,
        .parameter-table td {
            border: 1px solid #000;
            padding: 2.5px 2px;
            text-align: center;
            font-size: 8pt;
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
            margin-top: 8px;
            line-height: 1.2;
        }

        .notes ol {
            margin-left: 12px;
            padding-left: 0;
        }

        .notes li {
            margin-bottom: 2px;
        }

        .footer-signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .footer-signatures td {
            width: 33%;
            text-align: center;
            vertical-align: top;
            padding: 8px 0;
            font-size: 8.5pt;
        }

        .footer-signatures .date-place {
            text-align: right;
            margin-bottom: 10px;
            font-size: 8.5pt;
        }

        .name {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 10px;
        }

        .npp {
            font-size: 7pt;
        }
    </style>
</head>

<body>
    <div class="header">
        <table style="width:100%; border-collapse: collapse;">
            <tr>
                <td style="text-align: center; vertical-align: top;">
                    <img src="http://localhost/WEBLAB/image/logo_pemkot.png" style="width: 70px; float: left; margin-right: 10px;">
                    <img src="http://localhost/WEBLAB/image/logo_toyawening.png" style="width: 70px; float: right; margin-left: 10px;">
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

    <h3 style="text-align: center; margin: 10px 0;">LAPORAN HASIL UJI</h3>

    <table class="info-table">
        <tr>
            <td class="label">1. Jenis Air</td>
            <td>: <?= htmlspecialchars($master_data['jenis_air']) ?></td>
            <td class="label right-align">Dikirim/Diambil</td>
            <td class="value">: <?= htmlspecialchars($master_data['pengirim']) ?></td>
        </tr>
        <tr>
            <td class="label">2. Berasal dari</td>
            <td>: <?= htmlspecialchars($master_data['lokasi_uji']) ?></td>
            <td class="label right-align">Diterima</td>
            <td>: <?= htmlspecialchars($master_data['penguji']) ?></td>
        </tr>
        <tr>
            <td class="label">3. No. Lab.</td>
            <td>: <?= htmlspecialchars($master_data['no_lab']) ?></td>
            <td class="label right-align">Tanggal Uji</td>
            <td class="value">: <?= date('d F Y', strtotime($master_data['tanggal_uji'])) ?></td>
        </tr>
    </table>

    <table class="parameter-table">
        <thead>
            <tr>
                <th>No.</th>
                <th>Parameter</th>
                <th>Satuan</th>
                <th>Kadar Maksimum *)</th>
                <th>Hasil Uji</th>
                <th>Metode Uji</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no_global = 1; // Nomor urut global
            $kategori_index = 0; // Untuk I., II.
            $kategori_labels = ['I.', 'II.', 'III.', 'IV.', 'V.']; // Bisa ditambahkan jika ada lebih dari 2 kategori

            foreach ($grouped_parameters as $kategori => $params) {
                if (isset($kategori_labels[$kategori_index])) {
                    echo '<tr>
                        <td class="text-left" colspan="6"><b>' . $kategori_labels[$kategori_index] . ' ' . htmlspecialchars(strtoupper($kategori)) . '</b></td>
                    </tr>';
                    $kategori_index++;
                } else {
                    echo '<tr>
                        <td class="text-left" colspan="6"><b>' . htmlspecialchars(strtoupper($kategori)) . '</b></td>
                    </tr>';
                }

                foreach ($params as $param) {
                    echo '
                    <tr>
                        <td>' . $no_global++ . '</td>
                        <td class="text-left">' . htmlspecialchars($param['nama_parameter']) . '</td>
                        <td>' . htmlspecialchars($param['satuan']) . '</td>
                        <td>' . htmlspecialchars($param['kadar_maksimum']) . '</td>
                        <td>' . htmlspecialchars($param['hasil']) . '</td>
                        <td>' . htmlspecialchars($param['metode_uji']) . '</td>
                    </tr>';
                }
            }
            ?>
        </tbody>
    </table>

    <div class="notes">
        <p>*) Persyaratan Kualitas Air Minum menurut Per.Men.Kes RI No. 2 Tahun 2023</p>
        <p>Catatan:</p>
        <ol>
            <li>Hasil Uji ini hanya berlaku untuk contoh yang diuji.</li>
            <li>Laporan Hasil Uji ini tidak boleh digandakan tanpa izin Laboratorium PERUMDA Air Minum Kota Surakarta, kecuali secara lengkap.</li>
        </ol>
    </div>

    <div class="footer-signatures">
        <p class="date-place">Surakarta, <?= date('d F Y', strtotime($master_data['tanggal_uji'])) ?></p>
        <table style="width:100%;">
            <tr>
                <td style="width:33%; text-align: center;">Diteliti<br>Manajer Perencanaan dan Pengembangan</td>
                <td style="width:33%;"></td>
                <td style="width:33%; text-align: center;">Diperiksa<br>Asisten Manajer Laboratorium</td>
            </tr>
            <tr>
                <td style="width:33%; text-align: center;">
                    <div class="name">Harry Arifian Muam, A.Md</div>
                    <div class="npp">NPP. 577 120 384</div>
                </td>
                <td style="width:33%;"></td>
                <td style="width:33%; text-align: center;">
                    <div class="name">Sri Moro, A.Md</div>
                    <div class="npp">NPP. 502 160 174</div>
                </td>
            </tr>
        </table>
        <div style="text-align: center; margin-top: 20px;">
            <p>Mengetahui:<br>Direksi PERUMDA Air Minum Kota Surakarta<br>Direktur Teknik<br><br><br></p>
            <div class="name">Sarwoko Priyo Saptono, SH</div>
            <div class="npp">NPP. 450 190 269</div>
        </div>
    </div>
</body>

</html>