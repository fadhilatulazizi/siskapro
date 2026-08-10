<?php
include 'koneksi.php';
include 'auth.php';

requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sem = 0.3;
$uj  = 0.7;

// 1. AMBIL DATA SISWA
$stmt = $conn->prepare("SELECT * FROM siswa WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();

if (!$siswa) {
    die("Siswa tidak ditemukan");
}

// VALIDASI AKSES KELAS
if (!empty($_SESSION['kelas']) && $_SESSION['kelas'] !== $siswa['kelas']) {
    die("Anda tidak memiliki akses ke siswa ini");
}

// 2. AMBIL SEMUA NILAI DAN SIMPAN KE ARRAY BERDASARKAN NAMA MAPEL
$stmt = $conn->prepare("
    SELECT 
        mapel.nama_mapel,
        nilai.s1, nilai.s2, nilai.s3, nilai.s4, nilai.s5, nilai.s6, nilai.nilai_ujian
    FROM nilai
    JOIN mapel ON nilai.mapel_id = mapel.id
    WHERE nilai.siswa_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result_nilai = $stmt->get_result();

$list_nilai = [];
while ($row = $result_nilai->fetch_assoc()) {
    // Hitung rata-rata semesternya langsung
    $rata_rata =  ((($row['s1'] + $row['s2'] + $row['s3'] + $row['s4'] + $row['s5'] + $row['s6']) / 6) * $sem) + (
        $row['nilai_ujian'] * $uj );
    
    // Simpan ke array dengan kunci nama mapel (huruf kecil semua agar pencarian aman)
    $key = strtolower(trim($row['nama_mapel']));
    $list_nilai[$key] = $rata_rata;
}

// Fungsi bantu untuk mengambil nilai dari array secara aman dan menghitung total rata-rata besar
$total_nilai_seluruhnya = 0;
$jumlah_mapel_terhitung = 0;

function ambilNilai($nama_mapel, $list_nilai) {
    global $total_nilai_seluruhnya, $jumlah_mapel_terhitung;
    $key = strtolower(trim($nama_mapel));
    
    $nilai = isset($list_nilai[$key]) ? $list_nilai[$key] : 0;
    
    $total_nilai_seluruhnya += $nilai;
    $jumlah_mapel_terhitung++;
    
    return number_format($nilai, 2, ',', '.');
}

function tanggalIndonesia($tanggal){
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $pecah = explode('-', $tanggal);
    if(count($pecah) !== 3) return $tanggal;
    return $pecah[2].' '.$bulan[(int)$pecah[1]].' '.$pecah[0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SKL - <?= htmlspecialchars($siswa['nama']); ?></title>
<style>
@page{
    size:A4;
    margin:0mm 5mm 0mm 5mm;
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
    opacity:0.05; z-index:1; pointer-events:none;
}
.watermark-logo img{ width:320px; }
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
.label{ width:210px; }
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
        <h3>SURAT KETERANGAN LULUS</h3>
        <p>Nomor: 400.3.11.1 / 152.<?= htmlspecialchars($siswa['no_surat'] ?? '........'); ?></p>
    </div>

    <!-- CONTENT -->
    <div class="content">
        Yang bertanda tangan di bawah ini, Kepala SMP Negeri 1 Guntur menerangkan bahwa:

        <table class="info">
            <tr>
                <td class="label">Nama</td>
                <td class="separator">:</td>
                <td style="font-weight: bold; text-transform: uppercase;"><?= htmlspecialchars($siswa['nama']); ?></td>
            </tr>
            <tr>
                <td class="label">Tempat / Tanggal Lahir</td>
                <td class="separator">:</td>
                <td><p>
    <span style="text-transform: capitalize;">
        <?= strtolower(htmlspecialchars($siswa['tempat_lahir'] ?? '')); ?>
    </span>, 
    <?= tanggalIndonesia($siswa['tanggal_lahir'] ?? ''); ?>
</p></td>
            </tr>
            <tr>
                <td class="label">Nomor Induk Siswa</td>
                <td class="separator">:</td>
                <td><?= htmlspecialchars($siswa['nis']); ?></td>
            </tr>
            <tr>
                <td class="label">NISN</td>
                <td class="separator">:</td>
                <td><?= htmlspecialchars($siswa['nisn']); ?></td>
            </tr>
        </table>

        Berdasarkan Surat Keputusan Kepala SMP Negeri 1 Guntur Nomor:
        400.3.11.1 / 151 / 2026 tentang Penetapan Kelulusan Peserta Didik
        SMP Negeri 1 Guntur Tahun Ajaran 2025/2026,
        maka yang bersangkutan dinyatakan:

        <div class="lulus">LULUS</div>
    </div>

    <!-- TABLE MAPEL SESUAI KODE KEDUA -->
    <table class="nilai-table">
        <thead>
            <tr>
                <th class="no">No.</th>
                <th>Mata Pelajaran</th>
                <th class="nilai">Nilai</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="no">1.</td>
                <td>Pendidikan Agama dan Budi Pekerti</td>
                <td class="nilai"><?= ambilNilai('Pendidikan Agama dan Budi Pekerti', $list_nilai); ?></td>
            </tr>
            <tr>
                <td class="no">2.</td>
                <td>Pendidikan Pancasila</td>
                <!-- Mengantisipasi jika di database namanya masih 'PKn' atau 'Pendidikan Pancasila' -->
                <td class="nilai"><?= isset($list_nilai['pendidikan pancasila']) ? ambilNilai('Pendidikan Pancasila', $list_nilai) : ambilNilai('PKn', $list_nilai); ?></td>
            </tr>
            <tr>
                <td class="no">3.</td>
                <td>Bahasa Indonesia</td>
                <td class="nilai"><?= ambilNilai('Bahasa Indonesia', $list_nilai); ?></td>
            </tr>
            <tr>
                <td class="no">4.</td>
                <td>Matematika</td>
                <td class="nilai"><?= ambilNilai('Matematika', $list_nilai); ?></td>
            </tr>
            <tr>
                <td class="no">5.</td>
                <td>Ilmu Pengetahuan Alam</td>
                <td class="nilai"><?= ambilNilai('Ilmu Pengetahuan Alam', $list_nilai); ?></td>
            </tr>
            <tr>
                <td class="no">6.</td>
                <td>Ilmu Pengetahuan Sosial</td>
                <td class="nilai"><?= ambilNilai('Ilmu Pengetahuan Sosial', $list_nilai); ?></td>
            </tr>
            <tr>
                <td class="no">7.</td>
                <td>Bahasa Inggris</td>
                <td class="nilai"><?= ambilNilai('Bahasa Inggris', $list_nilai); ?></td>
            </tr>
            <tr>
                <td class="no">8.</td>
                <td>Pendidikan Jasmani Olahraga dan Kesehatan</td>
                <!-- Mengantisipasi nama singkat Penjasorkes -->
                <td class="nilai"><?= isset($list_nilai['pendidikan jasmani olahraga dan kesehatan']) ? ambilNilai('Pendidikan Jasmani Olahraga dan Kesehatan', $list_nilai) : ambilNilai('Penjasorkes', $list_nilai); ?></td>
            </tr>
            <tr>
                <td class="no">9.</td>
                <td>Informatika</td>
                <td class="nilai"><?= ambilNilai('Informatika', $list_nilai); ?></td>
            </tr>
            <tr>
                <td class="no">10.</td>
                <td>Seni, Budaya, dan Prakarya</td>
                <td class="nilai"><?= isset($list_nilai['seni, budaya, dan prakarya']) ? ambilNilai('Seni, Budaya, dan Prakarya', $list_nilai) : ambilNilai('Seni Budaya', $list_nilai); ?></td>
            </tr>
            <tr>
                <td class="no" valign="top">11.</td>
                <td>
                    Muatan Lokal
                    <br>
                    <span class="sub">a. Bahasa Jawa</span>
                    <br>
                    <span class="sub">b. BTQ / BTA</span>   
                </td>
                <td class="nilai">
                    <br>
                    <?= ambilNilai('Bahasa Jawa', $list_nilai); ?>
                    <br>
                    <?= ambilNilai('BTQ/BTA', $list_nilai); ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:center; font-weight:bold;">Rata-rata</td>
                <td class="nilai" style="font-weight:bold;">
                    <?= number_format($total_nilai_seluruhnya / max($jumlah_mapel_terhitung, 1), 2, ',', '.'); ?>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- KETERANGAN -->
    <div class="keterangan">
        Surat Keterangan Lulus ini bersifat sementara, sampai diterbitkannya ijazah
        untuk peserta didik yang bersangkutan. Demikian surat keterangan ini dibuat
        untuk digunakan sebagaimana mestinya.
    </div>

    <!-- TTD -->
    <div class="ttd">
        Demak, 2 Juni 2026.
        <br>
        Kepala Sekolah,
        <div class="space"></div>
        <div class="nama">Rina Yuniati, S.Pd., M.Pd.</div>
        NIP 197406051998022001
    </div>

</div>
</body>
</html>