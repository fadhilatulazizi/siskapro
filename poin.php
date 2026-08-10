<?php
include 'koneksi.php';
include 'auth.php';

if (function_exists('requireLogin')) {
    requireLogin();
}

$msg_success = "";
$msg_error = "";

// =========================
// HANDLER: POST REQUESTS
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // 1. Tambah Catatan Poin Siswa
    if ($_POST['action'] === 'tambah_poin') {
        $tanggal        = $_POST['tanggal'] ?? date('Y-m-d');
        $siswa_id       = (int)($_POST['siswa_id'] ?? 0);
        $master_poin_id = (int)($_POST['master_poin_id'] ?? 0);
        $catatan        = trim($_POST['catatan'] ?? '');

        if ($siswa_id > 0 && $master_poin_id > 0) {
            $stmt = $conn->prepare("INSERT INTO poin_siswa (tanggal, siswa_id, master_poin_id, catatan) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siis", $tanggal, $siswa_id, $master_poin_id, $catatan);
            if ($stmt->execute()) {
                $msg_success = "Poin siswa berhasil dicatat!";
            } else {
                $msg_error = "Gagal mencatat poin.";
            }
        }
    } 
    // 2. Hapus Catatan Poin Siswa
    elseif ($_POST['action'] === 'hapus') {
        $id_hapus = (int)($_POST['id_hapus'] ?? 0);
        if ($id_hapus > 0) {
            $stmt_del = $conn->prepare("DELETE FROM poin_siswa WHERE id = ?");
            $stmt_del->bind_param("i", $id_hapus);
            $stmt_del->execute();
            $msg_success = "Data poin berhasil dihapus.";
        }
    }
    // 3. Tambah Master Poin Baru (Pelanggaran/Prestasi) oleh Admin
    elseif ($_POST['action'] === 'tambah_master_poin') {
        $nama_tindakan = trim($_POST['nama_tindakan'] ?? '');
        $kategori      = $_POST['kategori'] ?? 'Pelanggaran';
        $poin          = (int)($_POST['poin'] ?? 0);

        if (!empty($nama_tindakan) && $poin > 0) {
            $stmt_mp = $conn->prepare("INSERT INTO master_poin (nama_tindakan, kategori, poin) VALUES (?, ?, ?)");
            $stmt_mp->bind_param("ssi", $nama_tindakan, $kategori, $poin);
            if ($stmt_mp->execute()) {
                $msg_success = "Jenis pelanggaran/prestasi baru berhasil ditambahkan ke database!";
            } else {
                $msg_error = "Gagal menambahkan jenis poin.";
            }
        } else {
            $msg_error = "Nama tindakan dan jumlah poin harus diisi dengan benar.";
        }
    }
}

// =========================
// SESSION KELAS LOCK
// =========================
$session_kelas_raw = $_SESSION['kelas'] ?? '';
$is_locked = false;
$allowed_kelas = [];

if (!empty($session_kelas_raw)) {
    $is_locked = true;
    $allowed_kelas = array_map('trim', explode(',', $session_kelas_raw));
    $kelas_list = $allowed_kelas;
} else {
    $kelas_result = $conn->query("SELECT DISTINCT kelas FROM siswa WHERE status = 'Aktif' ORDER BY kelas");
    $kelas_list = [];
    while ($row = $kelas_result->fetch_assoc()) {
        $kelas_list[] = $row['kelas'];
    }
}

$selected_kelas = $_GET['kelas'] ?? '';
if ($is_locked && (empty($selected_kelas) || !in_array($selected_kelas, $allowed_kelas))) {
    $selected_kelas = $allowed_kelas[0];
}

// Option Master Poin
$master_poin_opt = $conn->query("SELECT * FROM master_poin ORDER BY kategori DESC, nama_tindakan ASC")->fetch_all(MYSQLI_ASSOC);

// Option Siswa
$sql_siswa = "SELECT id, nis, nama, kelas FROM siswa WHERE status = 'Aktif'";
if ($is_locked) {
    $placeholders = implode(',', array_fill(0, count($allowed_kelas), '?'));
    $sql_siswa .= " AND kelas IN ($placeholders)";
}
$sql_siswa .= " ORDER BY kelas, nama ASC";
$stmt_s = $conn->prepare($sql_siswa);
if ($is_locked) {
    $types_s = str_repeat("s", count($allowed_kelas));
    $stmt_s->bind_param($types_s, ...$allowed_kelas);
}
$stmt_s->execute();
$siswa_options = $stmt_s->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Riwayat Poin
$where = "WHERE s.status = 'Aktif'";
$params = [];
$types = "";

if (!empty($selected_kelas)) {
    $where .= " AND s.kelas = ?";
    $params[] = $selected_kelas;
    $types .= "s";
}

$sql_list = "
SELECT 
    ps.id, ps.tanggal, ps.catatan,
    s.nis, s.nama, s.kelas,
    mp.nama_tindakan, mp.kategori, mp.poin
FROM poin_siswa ps
JOIN siswa s ON ps.siswa_id = s.id
JOIN master_poin mp ON ps.master_poin_id = mp.id
$where
ORDER BY ps.tanggal DESC, ps.id DESC
";

$stmt_l = $conn->prepare($sql_list);
if (!empty($params)) {
    $stmt_l->bind_param($types, ...$params);
}
$stmt_l->execute();
$riwayat_poin = $stmt_l->get_result()->fetch_all(MYSQLI_ASSOC);

// =========================
// FETCH REKAP POIN PER SISWA
// =========================
$where_rekap = "WHERE s.status = 'Aktif'";
$params_rekap = [];
$types_rekap = "";

if (!empty($selected_kelas)) {
    $where_rekap .= " AND s.kelas = ?";
    $params_rekap[] = $selected_kelas;
    $types_rekap .= "s";
}

$sql_rekap = "
SELECT 
    s.id, s.nis, s.nama, s.kelas,
    COALESCE(SUM(CASE WHEN mp.kategori = 'Pelanggaran' THEN mp.poin ELSE 0 END), 0) AS total_pelanggaran,
    COALESCE(SUM(CASE WHEN mp.kategori = 'Prestasi' THEN mp.poin ELSE 0 END), 0) AS total_prestasi
FROM siswa s
LEFT JOIN poin_siswa ps ON s.id = ps.siswa_id
LEFT JOIN master_poin mp ON ps.master_poin_id = mp.id
$where_rekap
GROUP BY s.id, s.nis, s.nama, s.kelas
ORDER BY s.kelas ASC, s.nama ASC
";

$stmt_r = $conn->prepare($sql_rekap);
if (!empty($params_rekap)) {
    $stmt_r->bind_param($types_rekap, ...$params_rekap);
}
$stmt_r->execute();
$rekap_siswa = $stmt_r->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Poin Pelanggaran & Prestasi Siswa</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    function switchTab(tabId) {
        document.getElementById('sectionRiwayat').classList.add('hidden');
        document.getElementById('sectionRekap').classList.add('hidden');
        document.getElementById('tabRiwayat').className = "px-4 py-2 font-medium text-sm rounded-xl text-slate-600 hover:bg-slate-100 transition";
        document.getElementById('tabRekap').className = "px-4 py-2 font-medium text-sm rounded-xl text-slate-600 hover:bg-slate-100 transition";
        
        document.getElementById(tabId).classList.remove('hidden');
        if(tabId === 'sectionRiwayat') {
            document.getElementById('tabRiwayat').className = "px-4 py-2 font-medium text-sm rounded-xl bg-indigo-600 text-white shadow-sm transition";
        } else {
            document.getElementById('tabRekap').className = "px-4 py-2 font-medium text-sm rounded-xl bg-indigo-600 text-white shadow-sm transition";
        }
    }
</script>
</head>
<body class="bg-slate-50 min-h-screen">
<?php include 'nav.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <?php if(!empty($msg_success)): ?>
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-2xl flex justify-between">
            <span>✅ <?= htmlspecialchars($msg_success) ?></span>
            <button onclick="this.parentElement.remove()" class="font-bold">&times;</button>
        </div>
    <?php endif; ?>

    <?php if(!empty($msg_error)): ?>
        <div class="mb-4 p-4 bg-rose-50 border border-rose-200 text-rose-700 text-sm rounded-2xl flex justify-between">
            <span>❌ <?= htmlspecialchars($msg_error) ?></span>
            <button onclick="this.parentElement.remove()" class="font-bold">&times;</button>
        </div>
    <?php endif; ?>

    <!-- HEADER & ACTION -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-200/60 mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <span class="inline-block px-3 py-1 bg-amber-50 text-amber-600 font-semibold text-xs rounded-full mb-2">Kedisiplinan</span>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Poin Pelanggaran & Prestasi</h1>
            <p class="text-sm text-gray-500 mt-1">Pencatatan poin kedisiplinan serta penghargaan prestasi siswa.</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-2">
            <button onclick="document.getElementById('modalMasterPoin').classList.remove('hidden')" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm px-4 py-2.5 rounded-xl shadow-md transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Tambah Jenis Poin
            </button>
            <button onclick="document.getElementById('modalPoin').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm px-4 py-2.5 rounded-xl shadow-md transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Catat Poin Siswa
            </button>
        </div>
    </div>

    <!-- FILTER KELAS & NAVIGASI TAB -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/60 mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
        <form method="GET" class="flex items-center gap-3 w-full sm:w-auto">
            <label class="text-xs font-semibold text-gray-600 uppercase">Filter Kelas:</label>
            <select name="kelas" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 text-sm p-2 rounded-xl">
                <option value="">Semua Kelas</option>
                <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k ?>" <?= $selected_kelas == $k ? 'selected' : '' ?>>Kelas <?= htmlspecialchars($k) ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- Tab Switcher -->
        <div class="flex items-center gap-2 bg-slate-50 p-1 rounded-2xl border border-slate-200/60 w-full sm:w-auto justify-center">
            <button id="tabRiwayat" onclick="switchTab('sectionRiwayat')" class="px-4 py-2 font-medium text-sm rounded-xl bg-indigo-600 text-white shadow-sm transition">Riwayat Poin</button>
            <button id="tabRekap" onclick="switchTab('sectionRekap')" class="px-4 py-2 font-medium text-sm rounded-xl text-slate-600 hover:bg-slate-100 transition">Rekap per Siswa</button>
        </div>
    </div>

    <!-- SECTION: RIWAYAT CATATAN POIN -->
    <div id="sectionRiwayat" class="bg-white rounded-3xl shadow-sm border border-slate-200/60 overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h3 class="font-bold text-gray-800">Riwayat Catatan Poin</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Tgl</th>
                        <th class="px-6 py-4">Siswa</th>
                        <th class="px-6 py-4">Tindakan / Kejadian</th>
                        <th class="px-6 py-4 text-center">Kategori</th>
                        <th class="px-6 py-4 text-center">Poin</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-gray-600">
                <?php if (empty($riwayat_poin)): ?>
                    <tr><td colspan="6" class="text-center py-8 text-gray-400">Belum ada catatan poin.</td></tr>
                <?php else: foreach($riwayat_poin as $r): ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-800"><?= htmlspecialchars($r['nama']) ?></div>
                            <div class="text-xs text-gray-400">Kelas: <?= htmlspecialchars($r['kelas']) ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-700"><?= htmlspecialchars($r['nama_tindakan']) ?></div>
                            <?php if(!empty($r['catatan'])): ?>
                                <div class="text-xs text-slate-400 italic">"<?= htmlspecialchars($r['catatan']) ?>"</div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2.5 py-1 text-xs rounded-lg font-semibold <?= $r['kategori'] === 'Pelanggaran' ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'bg-emerald-50 text-emerald-600 border border-emerald-200' ?>">
                                <?= $r['kategori'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center font-bold font-mono <?= $r['kategori'] === 'Pelanggaran' ? 'text-rose-600' : 'text-emerald-600' ?>">
                            <?= $r['kategori'] === 'Pelanggaran' ? '-' : '+' ?><?= $r['poin'] ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <form method="POST" onsubmit="return confirm('Hapus poin ini?');">
                                <input type="hidden" name="action" value="hapus">
                                <input type="hidden" name="id_hapus" value="<?= $r['id'] ?>">
                                <button type="submit" class="text-rose-500 hover:text-rose-700 font-medium text-xs">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECTION: REKAP POIN PER SISWA -->
    <div id="sectionRekap" class="bg-white rounded-3xl shadow-sm border border-slate-200/60 overflow-hidden hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Rekapitulasi Total Poin Siswa</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">No</th>
                        <th class="px-6 py-4">NIS</th>
                        <th class="px-6 py-4">Nama Siswa</th>
                        <th class="px-6 py-4">Kelas</th>
                        <th class="px-6 py-4 text-center">Total Pelanggaran</th>
                        <th class="px-6 py-4 text-center">Total Prestasi</th>
                        <th class="px-6 py-4 text-center">Akumulasi Poin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-gray-600">
                <?php if (empty($rekap_siswa)): ?>
                    <tr><td colspan="7" class="text-center py-8 text-gray-400">Tidak ada data siswa.</td></tr>
                <?php else: $no = 1; foreach($rekap_siswa as $rs): 
                    $akumulasi = $rs['total_prestasi'] - $rs['total_pelanggaran'];
                ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500"><?= $no++ ?></td>
                        <td class="px-6 py-4 whitespace-nowrap font-mono text-xs"><?= htmlspecialchars($rs['nis'] ?? '-') ?></td>
                        <td class="px-6 py-4 font-semibold text-gray-800"><?= htmlspecialchars($rs['nama']) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($rs['kelas']) ?></td>
                        <td class="px-6 py-4 text-center font-bold font-mono text-rose-600">
                            <?= $rs['total_pelanggaran'] > 0 ? '-' . $rs['total_pelanggaran'] : '0' ?>
                        </td>
                        <td class="px-6 py-4 text-center font-bold font-mono text-emerald-600">
                            <?= $rs['total_prestasi'] > 0 ? '+' . $rs['total_prestasi'] : '0' ?>
                        </td>
                        <td class="px-6 py-4 text-center font-bold font-mono">
                            <span class="px-3 py-1 rounded-xl text-xs <?= $akumulasi < 0 ? 'bg-rose-50 text-rose-700 border border-rose-200' : ($akumulasi > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600') ?>">
                                <?= $akumulasi > 0 ? '+' . $akumulasi : $akumulasi ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL TAMBAH MASTER POIN (JENIS PELANGGARAN / PRESTASI BARU) -->
<div id="modalMasterPoin" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Tambah Jenis Pelanggaran / Prestasi Baru</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="tambah_master_poin">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Kategori</label>
                <select name="kategori" required class="w-full border text-sm p-3 rounded-xl bg-slate-50">
                    <option value="Pelanggaran">Pelanggaran</option>
                    <option value="Prestasi">Prestasi</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Tindakan / Kejadian</label>
                <input type="text" name="nama_tindakan" placeholder="Contoh: Terlambat hadir / Menjuarai lomba..." required class="w-full border text-sm p-3 rounded-xl bg-slate-50">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Jumlah Poin</label>
                <input type="number" name="poin" min="1" placeholder="Contoh: 10" required class="w-full border text-sm p-3 rounded-xl bg-slate-50">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modalMasterPoin').classList.add('hidden')" class="px-4 py-2 text-sm text-slate-600">Batal</button>
                <button type="submit" class="px-5 py-2 text-sm bg-emerald-600 text-white rounded-xl shadow-md">Simpan ke Database</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL INPUT POIN SISWA -->
<div id="modalPoin" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Input Poin Siswa</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="tambah_poin">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal</label>
                <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full border text-sm p-3 rounded-xl bg-slate-50">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Siswa</label>
                <select name="siswa_id" required class="w-full border text-sm p-3 rounded-xl bg-slate-50">
                    <option value="">-- Pilih Siswa --</option>
                    <?php foreach($siswa_options as $s): ?>
                        <option value="<?= $s['id'] ?>">[<?= htmlspecialchars($s['kelas']) ?>] <?= htmlspecialchars($s['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Jenis Pelanggaran / Prestasi</label>
                <select name="master_poin_id" required class="w-full border text-sm p-3 rounded-xl bg-slate-50">
                    <option value="">-- Pilih Tindakan --</option>
                    <?php foreach($master_poin_opt as $mp): ?>
                        <option value="<?= $mp['id'] ?>">[<?= $mp['kategori'] ?>] <?= htmlspecialchars($mp['nama_tindakan']) ?> (<?= $mp['poin'] ?> Poin)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Catatan Tambahan</label>
                <textarea name="catatan" rows="2" placeholder="Keterangan singkat..." class="w-full border text-sm p-3 rounded-xl bg-slate-50"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modalPoin').classList.add('hidden')" class="px-4 py-2 text-sm text-slate-600">Batal</button>
                <button type="submit" class="px-5 py-2 text-sm bg-indigo-600 text-white rounded-xl shadow-md">Simpan</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>