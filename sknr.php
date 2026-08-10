<?php
include 'koneksi.php';

$id = $_GET['id'];

$siswa = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT * FROM siswa
    WHERE id='$id'
"));

$nilai = mysqli_query($conn,"
    SELECT 
        mapel.nama_mapel,
        nilai.s1,
        nilai.s2,
        nilai.s3,
        nilai.s4,
        nilai.s5
    FROM nilai
    JOIN mapel 
    ON nilai.mapel_id = mapel.id
    WHERE nilai.siswa_id='$id'
    AND mapel.id <= 7
    ORDER BY mapel.id ASC
");

$total_s1 = 0;
$total_s2 = 0;
$total_s3 = 0;
$total_s4 = 0;
$total_s5 = 0;
$total_rata = 0;

$jumlah = 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>SKNR</title>

<style>
@page{
    size:A4;
    margin:5mm 10mm 0mm 10mm;
}
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}
html, body{
    width:210mm;
    height:297mm;
    font-family: Arial, Helvetica, sans-serif;
    background:#dcdcdc;
    color:#000;
}
.page{
    width:210mm;
    min-height:297mm;
    margin:auto;
    padding:20px 30px;
    background:#fff;
    position:relative;
    overflow:hidden;
}
/* ================= WATERMARK ================= */
.watermark{
    position:absolute;
    top:50%; left:50%;
    transform: translate(-50%, -50%) rotate(-35deg);
    font-size:85px; font-weight:bold;
    color:rgba(0,0,0,0.05);
    white-space:nowrap; z-index:1; pointer-events:none;
}
.watermark-logo{
    position:absolute; top:50%; left:50%;
    transform:translate(-50%, -50%);
    opacity:0.07; z-index:1; pointer-events:none;
}
.watermark-logo img{ width:480px; height:600; }
.page > *:not(.watermark):not(.watermark-logo){
    position:relative; z-index:2;
}
/* ================= HEADER ================= */
.header{
    position:relative;
    text-align:center;
    line-height:1.2;
}
.logo{
    width:75px; height:75px;
    object-fit:contain;
    position:absolute; top:0;
}
.kiri{ left:0; }
.kanan{ right:0; }
.header h1{ font-size:16pt; font-weight:normal; text-transform:uppercase; }
.header h2{ font-size:18pt; font-weight:bold; text-transform:uppercase; }
.header p{ font-size:12pt; }
.footer p{ font-size:12pt; margin-top:15px; }
.line{ border-top:4px solid #000; margin-top:4px; }
.line2{ border-top:2px solid #000; margin-top:1.5px; }
/* ================= TITLE ================= */
.title{ text-align:center; margin-top:16px; }
.title h3{ font-size:12pt; font-weight:bold; text-transform:uppercase; text-decoration:underline; }
.title p{ font-size:12pt; margin-top:4px; }
/* ================= CONTENT ================= */
.content{ margin-top:15px; font-size:12pt; line-height:1.45; text-align:justify; }
.info{ width:100%; border-collapse:collapse; margin-top:6px; margin-bottom:10px; }
.info td{ font-size:12pt; padding:1px 0; vertical-align:top; }
.label{ width:100px; }
.separator{ width:12px; }
.lulus{ text-align:center; font-size:16pt; font-weight:bold; margin:12px 0 14px; letter-spacing:1px; }
/* ================= TABLE ================= */
.nilai-table{ width:100%; border-collapse:collapse; margin-top:8px; }
.nilai-table th, .nilai-table td{ border:1px solid #555; padding:2px 6px; font-size:11pt; line-height:1.25; }
.nilai-table th{ text-align:center; font-weight:bold; }
.no{ width:42px; text-align:center; }
.nilai{ width:82px; text-align:center; }
.sub{ padding-left:18px; }
/* ================= FOOTER ================= */
.keterangan{ margin-top:14px; font-size:12pt; line-height:1.45; text-align:justify; }
.ttd{ width:260px; margin-left:auto; margin-top:24px; font-size:12pt; line-height:1.5; }
.space{ height:70px; }
.nama{ font-weight:bold; text-decoration:underline; }
@media print{
    body{ background:#fff; }
    .page{ width:100%; margin:0; }
}
</style>
</head>
<body>

<div class="page">

    <!-- WATERMARK LOGO -->
    <div class="watermark-logo">
        <img src="https://smpn1guntur.sch.id/wp-content/uploads/2023/06/logo-SMP-1-Guntur.png">
    </div>
    <!-- HEADER -->
    <div class="header">
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/06/Lambang_Kabupaten_Demak.png/500px-Lambang_Kabupaten_Demak.png" class="logo kiri">
        <img src="https://smpn1guntur.sch.id/wp-content/uploads/2023/06/logo-SMP-1-Guntur.png" class="logo kanan">
        <h1>PEMERINTAH KABUPATEN DEMAK</h1>
        <h2>SMP NEGERI 1 GUNTUR</h2>
        <p>Ds. Bogosari, Kec. Guntur, Kabupaten Demak ✉ 59565</p>
        <p>http://www.demakkab.go.id | email: smpn1guntur@gmail.com</p>
    </div>

    <div class="line"></div>
        <div class="line2"></div>

    <!-- TITLE -->
    <div class="title">

        <h3>SURAT KETERANGAN NILAI RAPORT</h3>

        <p>
            Nomor : 400.3.11.1 / 150.<?= $siswa['no_surat'] ?> / 2026
        </p>

    </div>

    <!-- CONTENT -->
    <div class="content">

        Yang bertanda tangan dibawah ini :

        <table class="info">

            <tr>
                <td class="label">Nama</td>
                <td class="separator">:</td>
                <td>Rina Yuniati, S.Pd., M.Pd.</td>
            </tr>

            <tr>
                <td class="label">NIP</td>
                <td class="separator">:</td>
                <td>197406051998022001</td>
            </tr>

            <tr>
                <td class="label">JABATAN</td>
                <td class="separator">:</td>
                <td>Kepala Sekolah</td>
            </tr>

            <tr>
                <td class="label">NPSN</td>
                <td class="separator">:</td>
                <td>20319362</td>
            </tr>

        </table>

        <br>

        Menerangkan Nilai Raport :

        <table class="info">

            <tr>
                <td class="label">Nama</td>
                <td class="separator">:</td>
                <td><?= $siswa['nama'] ?></td>
            </tr>

            <tr>
                <td class="label">NISN</td>
                <td class="separator">:</td>
                <td><?= $siswa['nisn'] ?></td>
            </tr>

        </table>

    </div>

    <!-- TABLE -->
    <table class="nilai-table">

        <thead>

            <tr>

                <th rowspan="2" class="no">
                    NO
                </th>

                <th rowspan="2" class="mapel">
                    MATA PELAJARAN
                </th>

                <th colspan="5">
                    NILAI RAPOR SEMESTER
                </th>

                <th rowspan="2" class="nilai">
                    Nilai rata-rata nilai semester I - V
                </th>

            </tr>

            <tr>

                <th class="nilai">SMT I</th>
                <th class="nilai">SMT II</th>
                <th class="nilai">SMT III</th>
                <th class="nilai">SMT IV</th>
                <th class="nilai">SMT V</th>

            </tr>

        </thead>

        <tbody>

        <?php
        $no = 1;

        while($n = mysqli_fetch_assoc($nilai)):

            $rata =
            (
                $n['s1'] +
                $n['s2'] +
                $n['s3'] +
                $n['s4'] +
                $n['s5']
            ) / 5;

            $total_s1 += $n['s1'];
            $total_s2 += $n['s2'];
            $total_s3 += $n['s3'];
            $total_s4 += $n['s4'];
            $total_s5 += $n['s5'];

            $total_rata += $rata;

            $jumlah++;
        ?>

            <tr>

                <td class="no">
                    <?= $no ?>
                </td>

                <td>
                    <?php
$mapel = $n['nama_mapel'];

$mapel_tampil = ($mapel == "Pendidikan Pancasila")
    ? "PPKn/Pendidikan Kewarganegaraan/Pendidikan Pancasila"
    : $mapel;

echo $mapel_tampil;
?>
                </td>

                <td class="nilai">
                    <?= number_format($n['s1'],2,',','.') ?>
                </td>

                <td class="nilai">
                    <?= number_format($n['s2'],2,',','.') ?>
                </td>

                <td class="nilai">
                    <?= number_format($n['s3'],2,',','.') ?>
                </td>

                <td class="nilai">
                    <?= number_format($n['s4'],2,',','.') ?>
                </td>

                <td class="nilai">
                    <?= number_format($n['s5'],2,',','.') ?>
                </td>

                <td class="nilai" style="font-weight:bold;">
                    <?= number_format($rata,2,',','.') ?>
                </td>

            </tr>

        <?php
        $no++;
        endwhile;
        ?>

            <tr>

                <td colspan="2" style="text-align:center;font-weight:bold;">
                    Rerata Total
                </td>

                <td class="nilai" style="font-weight:bold;">
                    <?= number_format($total_s1 / $jumlah,2,',','.') ?>
                </td>

                <td class="nilai" style="font-weight:bold;">
                    <?= number_format($total_s2 / $jumlah,2,',','.') ?>
                </td>

                <td class="nilai" style="font-weight:bold;">
                    <?= number_format($total_s3 / $jumlah,2,',','.') ?>
                </td>

                <td class="nilai" style="font-weight:bold;">
                    <?= number_format($total_s4 / $jumlah,2,',','.') ?>
                </td>

                <td class="nilai" style="font-weight:bold;">
                    <?= number_format($total_s5 / $jumlah,2,',','.') ?>
                </td>

                <td class="nilai" style="font-weight:bold;">
                    <?= number_format($total_rata / $jumlah,2,',','.') ?>
                </td>

            </tr>

        </tbody>

    </table>

    <!-- FOOTER -->
    <div class="footer">

        <p>Demikian Surat Keterangan ini dibuat untuk dapat digunakan sebagaimana semestinya.</p>

    </div>

    <div class="ttd">

        Demak, 2 Juni 2026
        <br>
        Kepala Sekolah

        <div class="space"></div>

        <div class="nama">
            Rina Yuniati, S.Pd., M.Pd.
        </div>
        NIP 197406051998022001

    </div>

</div>

</body>
</html>