<?php
include 'koneksi.php';
include 'auth.php';

if (function_exists('requireLogin')) {
    requireLogin();
}

// =========================
// HANDLER: BUAT SURAT BK
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buat_surat') {
    $no_surat          = trim($_POST['no_surat']);
    $tanggal_surat     = $_POST['tanggal_surat'];
    $siswa_id          = (int)$_POST['siswa_id'];
    $tanggal_panggilan = $_POST['tanggal_panggilan'];
    $waktu_panggilan   = $_POST['waktu_panggilan'];
    $tempat            = trim($_POST['tempat']);
    $keperluan         = trim($_POST['keperluan']);

    $stmt = $conn->prepare("INSERT INTO surat_bk (no_surat, tanggal_surat, siswa_id, tanggal_panggilan, waktu_panggilan, tempat, keperluan) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssissss", $no_surat, $tanggal_surat, $siswa_id, $tanggal_panggilan, $waktu_panggilan, $tempat, $keperluan);
    $stmt->execute();
    
    header("Location: surat_panggilan_bk.php?cetak_id=" . $stmt->insert_id);
    exit;
}

// =========================
// MODE CETAK SINGLE SURAT
// =========================
$cetak_data = null;
if (isset($_GET['cetak_id'])) {
    $id = (int)$_GET['cetak_id'];
    $stmt_c = $conn->prepare("
        SELECT sb.*, s.nama AS nama_siswa, s.nis, s.nisn, s.kelas 
        FROM surat_bk sb 
        JOIN siswa s ON sb.siswa_id = s.id 
        WHERE sb.id = ?
    ");
    $stmt_c->bind_param("i", $id);
    $stmt_c->execute();
    $cetak_data = $stmt_c->get_result()->fetch_assoc();
}

// =========================
// FETCH DATA UNTUK DASHBOARD
// =========================
$siswa_list = $conn->query("SELECT id, nis, nama, kelas FROM siswa WHERE status = 'Aktif' ORDER BY kelas, nama ASC")->fetch_all(MYSQLI_ASSOC);
$surat_history = $conn->query("
    SELECT sb.*, s.nama AS nama_siswa, s.kelas 
    FROM surat_bk sb 
    JOIN siswa s ON sb.siswa_id = s.id 
    ORDER BY sb.id DESC
")->fetch_all(MYSQLI_ASSOC);

// Helper Format Tanggal Indonesia
function tgl_indo($tanggal){
    $bulan = array (
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $hari = array (
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
    );
    $split = explode('-', $tanggal);
    $num_hari = date('l', strtotime($tanggal));
    return $hari[$num_hari] . ', ' . $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}
function tgl_indo_i($tanggal){
    $bulan = array (
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    
    $split = explode('-', $tanggal);
    // Langsung mengembalikan: Tanggal Bulan Tahun
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $cetak_data ? "Surat Panggilan BK - " . htmlspecialchars($cetak_data['nama_siswa']) : "Manajemen Surat BK" ?></title>
<script src="https://cdn.tailwindcss.com"></script>

<style>
@page {
    size: A4;
    margin: 5mm 10mm 0mm 10mm;
}
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: Arial, Helvetica, sans-serif;
    color: #000;
}

/* KOP SURAT STYLING */
.page {
    width: 210mm;
    min-height: 297mm;
    margin: 20px auto;
    padding: 20px 30px;
    background: #fff;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
}
.watermark-logo {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    opacity: 0.06; z-index: 1; pointer-events: none;
}
.watermark-logo img { width: 480px; height: auto; }
.page > *:not(.watermark-logo) {
    position: relative; z-index: 2;
}

.header { position: relative; text-align: center; line-height: 1.2; }
.logo { width: 75px; height: 75px; object-fit: contain; position: absolute; top: 0; }
.logo.kiri { left: 0; }
.logo.kanan { right: 0; }

.header h1 { font-size: 15pt; font-weight: normal; text-transform: uppercase; }
.header h2 { font-size: 17pt; font-weight: bold; text-transform: uppercase; }
.header p { font-size: 11pt; }

.line { border-top: 4px solid #000; margin-top: 4px; }
.line2 { border-top: 2px solid #000; margin-top: 1.5px; }

.title { text-align: center; margin-top: 16px; }
.title h3 { font-size: 12pt; font-weight: bold; text-transform: uppercase; text-decoration: underline; }
.title p { font-size: 11pt; margin-top: 4px; }

.content { margin-top: 20px; font-size: 11pt; line-height: 1.5; text-align: justify; }
.info-table { width: 100%; border-collapse: collapse; margin: 8px 0 12px 15px; }
.info-table td { font-size: 11pt; padding: 2px 0; vertical-align: top; }
.label-col { width: 140px; }
.sep-col { width: 15px; }

.ttd-container { width: 100%; margin-top: 35px; display: flex; justify-content: space-between; font-size: 11pt; line-height: 1.4; }
.ttd-box { width: 230px; text-align: center; }
.space-ttd { height: 65px; }
.nama-ttd { font-weight: bold; text-decoration: underline; }

@media print {
    body { background: #fff !important; }
    .no-print { display: none !important; }
    .page { width: 100%; margin: 0; padding: 0; box-shadow: none; }
}
</style>
</head>
<body class="bg-slate-100 min-h-screen">

<?php if ($cetak_data): ?>
    <!-- ================= ACTION BAR (Hanya Tampil di Layar) ================= -->
    <div class="no-print bg-slate-800 text-white p-4 sticky top-0 z-50 shadow-md">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <a href="panggilan.php" class="text-sm font-semibold hover:underline flex items-center gap-1">
                &larr; Kembali ke Daftar Surat
            </a>
            <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm px-5 py-2 rounded-xl transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Surat (A4)
            </button>
        </div>
    </div>

    <!-- ================= DOKUMEN CETAK A4 ================= -->
    <div class="page">
        <!-- WATERMARK LOGO -->
        <div class="watermark-logo">
            <img src="https://smpn1guntur.sch.id/wp-content/uploads/2023/06/logo-SMP-1-Guntur.png" alt="Watermark">
        </div>

        <!-- HEADER / KOP SURAT -->
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

        <!-- JUDUL SURAT -->
        <div class="title">
            <h3>SURAT PEMANGGILAN ORANG TUA / WALI SISWA</h3>
            <p>Nomor : <?= htmlspecialchars($cetak_data['no_surat']) ?></p>
        </div>

        <!-- CONTENT -->
        <div class="content">
            <p>Kepada Yth.</p>
            <p><strong>Bapak / Ibu Orang Tua / Wali Siswa dari <?= htmlspecialchars($cetak_data['nama_siswa']) ?></strong></p>
            <p>di Tempat</p>

            <br>
            <p>Dengan hormat,</p>
            <p>Sehubungan dengan upaya pembinaan kedisiplinan dan perkembangan belajar siswa di sekolah, kami mengharapkan kehadiran Bapak/Ibu Orang Tua/Wali dari siswa tersebut di bawah ini:</p>

            <table class="info-table">
                <tr>
                    <td class="label-col">Nama Siswa</td>
                    <td class="sep-col">:</td>
                    <td><strong><?= htmlspecialchars($cetak_data['nama_siswa']) ?></strong></td>
                </tr>
                <tr>
                    <td class="label-col">NIS / NISN</td>
                    <td class="sep-col">:</td>
                    <td><?= htmlspecialchars($cetak_data['nis']) ?> / <?= htmlspecialchars($cetak_data['nisn'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="label-col">Kelas</td>
                    <td class="sep-col">:</td>
                    <td>Kelas <?= htmlspecialchars($cetak_data['kelas']) ?></td>
                </tr>
            </table>

            <p>Untuk hadir pada pertemuan Bimbingan & Konseling (BK) yang akan dilaksanakan pada:</p>

            <table class="info-table">
                <tr>
                    <td class="label-col">Hari, Tanggal</td>
                    <td class="sep-col">:</td>
                    <td><strong><?= tgl_indo($cetak_data['tanggal_panggilan']) ?></strong></td>
                </tr>
                <tr>
                    <td class="label-col">Waktu</td>
                    <td class="sep-col">:</td>
                    <td>Pukul <?= date('H:i', strtotime($cetak_data['waktu_panggilan'])) ?> WIB</td>
                </tr>
                <tr>
                    <td class="label-col">Tempat</td>
                    <td class="sep-col">:</td>
                    <td><?= htmlspecialchars($cetak_data['tempat']) ?></td>
                </tr>
                <tr>
                    <td class="label-col">Keperluan</td>
                    <td class="sep-col">:</td>
                    <td><?= htmlspecialchars($cetak_data['keperluan']) ?></td>
                </tr>
            </table>

            <p>Mengingat pentingnya hal tersebut, kami sangat mengharapkan kehadiran Bapak/Ibu tepat pada waktunya. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.</p>

            <!-- TANDA TANGAN -->
            <div class="ttd-container">
                <div class="ttd-box">
                    <p>Mengetahui,</p>
                    <p>Guru Bimbingan Konseling</p>
                    <div class="space-ttd"></div>
                    <div class="nama-ttd">( ____________________ )</div>
                    <p>NIP. .........................................</p>
                </div>

                <div class="ttd-box">
                    <p>Demak, <?= tgl_indo_i(date("Y-m-d")) ?></p>
                    <p>Kepala Sekolah</p>
                    <div class="space-ttd"></div>
                    <div class="nama-ttd">Rina Yuniati, S.Pd., M.Pd.</div>
                    <p>NIP. 197406051998022001</p>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>

    <!-- ================= DASHBOARD SURAT BK ================= -->
    <div class="no-print">
        <?php include 'nav.php'; ?>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-200/60 mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <span class="inline-block px-3 py-1 bg-indigo-50 text-indigo-600 font-semibold text-xs rounded-full mb-2">Administrasi BK</span>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Surat Pemanggilan Orang Tua</h1>
                    <p class="text-sm text-gray-500 mt-1">Penerbitan & riwayat cetak surat panggilan orang tua siswa.</p>
                </div>
                <button onclick="document.getElementById('modalSurat').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm px-5 py-2.5 rounded-xl shadow-md transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Buat Surat Panggilan
                </button>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 overflow-hidden">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="font-bold text-gray-800">Riwayat Surat Panggilan</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-semibold border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-4">No. Surat</th>
                                <th class="px-6 py-4">Siswa</th>
                                <th class="px-6 py-4">Tgl Panggilan</th>
                                <th class="px-6 py-4">Keperluan</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-gray-600">
                        <?php if (empty($surat_history)): ?>
                            <tr><td colspan="5" class="text-center py-8 text-gray-400">Belum ada riwayat surat panggilan.</td></tr>
                        <?php else: foreach($surat_history as $sh): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-mono text-xs"><?= htmlspecialchars($sh['no_surat']) ?></td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-800"><?= htmlspecialchars($sh['nama_siswa']) ?></div>
                                    <div class="text-xs text-gray-400">Kelas: <?= htmlspecialchars($sh['kelas']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-xs font-medium">
                                    <?= date('d/m/Y', strtotime($sh['tanggal_panggilan'])) ?> - <?= date('H:i', strtotime($sh['waktu_panggilan'])) ?> WIB
                                </td>
                                <td class="px-6 py-4 max-w-xs truncate"><?= htmlspecialchars($sh['keperluan']) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <a href="surat_panggilan_bk.php?cetak_id=<?= $sh['id'] ?>" class="bg-indigo-50 text-indigo-600 hover:bg-indigo-100 font-semibold text-xs px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        Cetak A4
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL BUAT SURAT -->
    <div id="modalSurat" class="no-print fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-xl max-w-lg w-full p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Buat Surat Panggilan Baru</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="buat_surat">
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">No. Surat</label>
                        <input type="text" name="no_surat" value="400.3.11.1 / BK / <?= date('Y') ?>" required class="w-full border text-sm p-2.5 rounded-xl bg-slate-50">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal Surat</label>
                        <input type="date" name="tanggal_surat" value="<?= date('Y-m-d') ?>" required class="w-full border text-sm p-2.5 rounded-xl bg-slate-50">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Pilih Siswa</label>
                    <select name="siswa_id" required class="w-full border text-sm p-2.5 rounded-xl bg-slate-50">
                        <option value="">-- Pilih Siswa --</option>
                        <?php foreach($siswa_list as $sl): ?>
                            <option value="<?= $sl['id'] ?>">[Kelas <?= htmlspecialchars($sl['kelas']) ?>] <?= htmlspecialchars($sl['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tgl Panggilan</label>
                        <input type="date" name="tanggal_panggilan" required class="w-full border text-sm p-2.5 rounded-xl bg-slate-50">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Waktu</label>
                        <input type="time" name="waktu_panggilan" value="08:00" required class="w-full border text-sm p-2.5 rounded-xl bg-slate-50">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tempat Pertemuan</label>
                    <input type="text" name="tempat" value="Ruang Bimbingan & Konseling (BK)" required class="w-full border text-sm p-2.5 rounded-xl bg-slate-50">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Keperluan / Alasan</label>
                    <textarea name="keperluan" rows="2" required placeholder="Koordinasi penanganan kedisiplinan dan absensi siswa..." class="w-full border text-sm p-2.5 rounded-xl bg-slate-50"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('modalSurat').classList.add('hidden')" class="px-4 py-2 text-sm text-slate-600">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm bg-indigo-600 text-white rounded-xl shadow-md">Simpan & Cetak</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

</body>
</html>