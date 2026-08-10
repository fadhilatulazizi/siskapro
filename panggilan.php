<?php
include 'koneksi.php';
include 'auth.php';

if (function_exists('requireLogin')) {
    requireLogin();
}

// HANDLER BUAT SURAT
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
    header("Location: surat-panggilan.php?cetak_id=" . $stmt->insert_id);
    exit;
}

// MODE CETAK SURAT SINGLE
$cetak_data = null;
if (isset($_GET['cetak_id'])) {
    $id = (int)$_GET['cetak_id'];
    $stmt_c = $conn->prepare("
        SELECT sb.*, s.nama AS nama_siswa, s.nis, s.kelas 
        FROM surat_bk sb 
        JOIN siswa s ON sb.siswa_id = s.id 
        WHERE sb.id = ?
    ");
    $stmt_c->bind_param("i", $id);
    $stmt_c->execute();
    $cetak_data = $stmt_c->get_result()->fetch_assoc();
}

// LIST SURAT & SISWA
$siswa_list = $conn->query("SELECT id, nis, nama, kelas FROM siswa WHERE status = 'Aktif' ORDER BY kelas, nama")->fetch_all(MYSQLI_ASSOC);
$surat_history = $conn->query("
    SELECT sb.*, s.nama AS nama_siswa, s.kelas 
    FROM surat_bk sb 
    JOIN siswa s ON sb.siswa_id = s.id 
    ORDER BY sb.id DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Surat Pemanggilan Orang Tua BK</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    @media print {
        body { background: white !important; color: black !important; }
        .print\:hidden { display: none !important; }
        .print\:block { display: block !important; }
        .print\:shadow-none { box-shadow: none !important; border: none !important; }
    }
</style>
</head>
<body class="bg-slate-50 min-h-screen print:bg-white">

<?php if ($cetak_data): ?>
    <!-- ================= DISPLAY SURAT RESMI (PRINT VIEW) ================= -->
    <div class="max-w-3xl mx-auto p-8 bg-white my-6 shadow-md border rounded-xl print:shadow-none print:border-none print:m-0 print:p-0">
        <div class="print:hidden mb-6 flex justify-between items-center bg-slate-100 p-4 rounded-xl">
            <a href="surat_panggilan_bk.php" class="text-sm font-semibold text-slate-600">&larr; Kembali ke Daftar Surat</a>
            <button onclick="window.print()" class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-xl font-medium shadow">Cetak Surat Ini</button>
        </div>

        <!-- KOP SURAT -->
        <div class="border-b-4 border-double border-gray-900 pb-4 mb-6 text-center">
            <h2 class="text-xl font-bold uppercase tracking-wide">Pemerintah Daerah Provinsi / Kab.</h2>
            <h1 class="text-2xl font-black uppercase tracking-wider">SMA / SMK NEGERI 1 UTAMA</h1>
            <p class="text-xs text-gray-600">Jl. Pendidikan No. 123, Telp. (024) 1234567, Website: www.sekolah.sch.id</p>
        </div>

        <!-- ISI SURAT -->
        <div class="text-sm leading-relaxed text-gray-900 space-y-4">
            <div class="flex justify-between">
                <table>
                    <tr><td class="w-24 font-medium">Nomor</td><td>: <?= htmlspecialchars($cetak_data['no_surat']) ?></td></tr>
                    <tr><td class="font-medium">Lampiran</td><td>: -</td></tr>
                    <tr><td class="font-medium">Hal</td><td>: <strong>Pemanggilan Orang Tua / Wali Siswa</strong></td></tr>
                </table>
                <div><?= date('d F Y', strtotime($cetak_data['tanggal_surat'])) ?></div>
            </div>

            <div class="pt-4">
                <p>Kepada Yth.</p>
                <p><strong>Bapak / Ibu Orang Tua / Wali dari <?= htmlspecialchars($cetak_data['nama_siswa']) ?></strong></p>
                <p>di Tempat</p>
            </div>

            <p>Dengan hormat,</p>
            <p>Sehubungan dengan kelancaran proses kegiatan belajar mengajar dan perkembangan kedisiplinan siswa di sekolah, maka melalui surat ini kami mengundang Bapak/Ibu Orang Tua/Wali dari:</p>

            <div class="pl-6 border-l-2 border-gray-300 space-y-1 my-2">
                <p><strong>Nama:</strong> <?= htmlspecialchars($cetak_data['nama_siswa']) ?></p>
                <p><strong>NIS:</strong> <?= htmlspecialchars($cetak_data['nis']) ?></p>
                <p><strong>Kelas:</strong> <?= htmlspecialchars($cetak_data['kelas']) ?></p>
            </div>

            <p>Untuk dapat hadir menemui Guru Bimbingan & Konseling (BK) pada:</p>

            <table class="ml-6 space-y-1">
                <tr><td class="w-32 font-medium">Hari, Tanggal</td><td>: <strong><?= date('l, d F Y', strtotime($cetak_data['tanggal_panggilan'])) ?></strong></td></tr>
                <tr><td class="font-medium">Waktu</td><td>: <?= date('H:i', strtotime($cetak_data['waktu_panggilan'])) ?> WIB</td></tr>
                <tr><td class="font-medium">Tempat</td><td>: <?= htmlspecialchars($cetak_data['tempat']) ?></td></tr>
                <tr><td class="font-medium">Keperluan</td><td>: <?= htmlspecialchars($cetak_data['keperluan']) ?></td></tr>
            </table>

            <p>Demikian surat pemanggilan ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.</p>

            <!-- TANDA TANGAN -->
            <div class="pt-12 grid grid-cols-2 text-center">
                <div>
                    <p>Mengetahui,</p>
                    <p>Guru BK / Konselor</p>
                    <div class="h-20"></div>
                    <p class="font-bold underline">( ______________________ )</p>
                </div>
                <div>
                    <p>Kepala Sekolah,</p>
                    <div class="h-24"></div>
                    <p class="font-bold underline">( ______________________ )</p>
                    <p class="text-xs">NIP. .........................................</p>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>

    <!-- ================= DASHBOARD SURAT BK ================= -->
    <div class="print:hidden">
        <?php include 'nav.php'; ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-200/60 mb-6 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Surat Pemanggilan Orang Tua</h1>
                    <p class="text-sm text-gray-500">Generator & riwayat pembuatan surat resmi bimbingan konseling.</p>
                </div>
                <button onclick="document.getElementById('modalSurat').classList.remove('hidden')" class="bg-indigo-600 text-white text-sm px-5 py-2.5 rounded-xl font-medium shadow">
                    + Buat Surat Baru
                </button>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 overflow-hidden">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-semibold border-b">
                        <tr>
                            <th class="px-6 py-4">No. Surat</th>
                            <th class="px-6 py-4">Siswa</th>
                            <th class="px-6 py-4">Tgl Panggilan</th>
                            <th class="px-6 py-4">Keperluan</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php foreach($surat_history as $sh): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-mono text-xs"><?= htmlspecialchars($sh['no_surat']) ?></td>
                            <td class="px-6 py-4 font-semibold"><?= htmlspecialchars($sh['nama_siswa']) ?> (<?= htmlspecialchars($sh['kelas']) ?>)</td>
                            <td class="px-6 py-4 text-xs"><?= date('d/m/Y', strtotime($sh['tanggal_panggilan'])) ?> - <?= date('H:i', strtotime($sh['waktu_panggilan'])) ?></td>
                            <td class="px-6 py-4 max-w-xs truncate"><?= htmlspecialchars($sh['keperluan']) ?></td>
                            <td class="px-6 py-4 text-center">
                                <a href="surat-panggilan.php?cetak_id=<?= $sh['id'] ?>" class="bg-slate-800 text-white text-xs px-3 py-1.5 rounded-lg">Cetak Surat</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL FORM BUAT SURAT -->
    <div id="modalSurat" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-xl max-w-lg w-full p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Buat Surat Panggilan Baru</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="buat_surat">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">No. Surat</label>
                        <input type="text" name="no_surat" value="421.5/BK/<?= date('Y/m') ?>/001" required class="w-full border text-sm p-2.5 rounded-xl bg-slate-50">
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
                            <option value="<?= $sl['id'] ?>">[<?= htmlspecialchars($sl['kelas']) ?>] <?= htmlspecialchars($sl['nama']) ?></option>
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
                        <input type="time" name="waktu_panggilan" value="09:00" required class="w-full border text-sm p-2.5 rounded-xl bg-slate-50">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tempat Pertemuan</label>
                    <input type="text" name="tempat" value="Ruang Bimbingan Konseling (BK)" required class="w-full border text-sm p-2.5 rounded-xl bg-slate-50">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Keperluan / Alasan</label>
                    <textarea name="keperluan" rows="2" required placeholder="Konsultasi kedisiplinan dan absensi siswa..." class="w-full border text-sm p-2.5 rounded-xl bg-slate-50"></textarea>
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