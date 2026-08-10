<?php
// =========================
// 1. ENDPOINT AJAX (HARUS DI PALING ATAS SEBELUM APA PUN)
// =========================
if (isset($_GET['get_riwayat'])) {
    @ini_set('display_errors', 0);
    
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    include_once 'koneksi.php';
    
    header('Content-Type: application/json; charset=utf-8');
    
    $sid = (int)$_GET['get_riwayat'];
    $riwayat_data = [];
    
    if (isset($conn) && $conn) {
        $stmt_r = $conn->prepare("SELECT tanggal, jenis_layanan, permasalahan, tindak_lanjut, status FROM catatan_bk WHERE siswa_id = ? ORDER BY tanggal DESC, id DESC");
        if ($stmt_r) {
            $stmt_r->bind_param("i", $sid);
            $stmt_r->execute();
            $res_r = $stmt_r->get_result();
            
            while ($row_r = $res_r->fetch_assoc()) {
                $row_r['tanggal'] = date('d/m/Y', strtotime($row_r['tanggal']));
                $riwayat_data[] = $row_r;
            }
        }
    }
    
    echo json_encode($riwayat_data);
    exit;
}

// Mulai output buffering untuk menangkap output tak terduga
ob_start();

include 'koneksi.php';
include 'auth.php';

if (function_exists('requireLogin')) {
    requireLogin();
}

// =========================
// HANDLER: TAMBAH CATATAN BK
// =========================
$msg_success = "";
$msg_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'tambah') {
        $tanggal       = $_POST['tanggal'] ?? date('Y-m-d');
        $siswa_id      = (int)($_POST['siswa_id'] ?? 0);
        $jenis_layanan = trim($_POST['jenis_layanan'] ?? '');
        $permasalahan  = trim($_POST['permasalahan'] ?? '');
        $tindak_lanjut = trim($_POST['tindak_lanjut'] ?? '');
        $status        = $_POST['status'] ?? 'Dalam Proses';

        if ($siswa_id > 0 && !empty($jenis_layanan) && !empty($permasalahan)) {
            $stmt_ins = $conn->prepare("INSERT INTO catatan_bk (tanggal, siswa_id, jenis_layanan, permasalahan, tindak_lanjut, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_ins->bind_param("sissss", $tanggal, $siswa_id, $jenis_layanan, $permasalahan, $tindak_lanjut, $status);
            
            if ($stmt_ins->execute()) {
                $msg_success = "Catatan BK berhasil disimpan!";
            } else {
                $msg_error = "Gagal menyimpan catatan BK: " . $conn->error;
            }
        } else {
            $msg_error = "Mohon lengkapi seluruh kolom yang wajib diisi.";
        }
    } elseif ($_POST['action'] === 'hapus') {
        $id_hapus = (int)($_POST['id_hapus'] ?? 0);
        if ($id_hapus > 0) {
            $stmt_del = $conn->prepare("DELETE FROM catatan_bk WHERE id = ?");
            $stmt_del->bind_param("i", $id_hapus);
            if ($stmt_del->execute()) {
                $msg_success = "Catatan BK berhasil dihapus.";
            }
        }
    } elseif ($_POST['action'] === 'edit_status') {
        $id_status = (int)($_POST['id_status'] ?? 0);
        $statusBaru = $_POST['status_baru'] ?? 'Dalam Proses';
        if ($id_status > 0) {
            $stmt_upd = $conn->prepare("UPDATE catatan_bk SET status = ? WHERE id = ?");
            $stmt_upd->bind_param("si", $statusBaru, $id_status);
            if ($stmt_upd->execute()) {
                $msg_success = "Status catatan berhasil diperbarui.";
            }
        }
    }
}

// =========================
// SESSION KELAS LOCK (Multi-Kelas Support)
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

// =========================
// FILTER PARAMS
// =========================
$selected_kelas  = $_GET['kelas'] ?? '';
$tgl_mulai       = $_GET['tgl_mulai'] ?? '';
$tgl_selesai     = $_GET['tgl_selesai'] ?? '';
$search_keyword  = trim($_GET['q'] ?? '');

if ($is_locked) {
    if (!empty($selected_kelas) && !in_array($selected_kelas, $allowed_kelas)) {
        $selected_kelas = $allowed_kelas[0];
    } elseif (empty($selected_kelas) && count($allowed_kelas) === 1) {
        $selected_kelas = $allowed_kelas[0];
    }
}

// =========================
// QUERY SISWA (Untuk Dropdown Form Tambah)
// =========================
$sql_siswa_opt = "SELECT id, nis, nama, kelas FROM siswa WHERE status = 'Aktif'";
if ($is_locked) {
    $placeholders = implode(',', array_fill(0, count($allowed_kelas), '?'));
    $sql_siswa_opt .= " AND kelas IN ($placeholders)";
}
$sql_siswa_opt .= " ORDER BY kelas, nama ASC";

$stmt_opt = $conn->prepare($sql_siswa_opt);
if ($is_locked) {
    $types_opt = str_repeat("s", count($allowed_kelas));
    $stmt_opt->bind_param($types_opt, ...$allowed_kelas);
}
$stmt_opt->execute();
$siswa_options = $stmt_opt->get_result()->fetch_all(MYSQLI_ASSOC);

// =========================
// QUERY REKAP PER SISWA
// =========================
$where_rekap = "WHERE s.status = 'Aktif'";
$params_rekap = [];
$types_rekap = "";

if (!empty($selected_kelas)) {
    $where_rekap .= " AND s.kelas = ?";
    $params_rekap[] = $selected_kelas;
    $types_rekap .= "s";
} elseif ($is_locked && count($allowed_kelas) > 1) {
    $placeholders = implode(',', array_fill(0, count($allowed_kelas), '?'));
    $where_rekap .= " AND s.kelas IN ($placeholders)";
    foreach ($allowed_kelas as $k) {
        $params_rekap[] = $k;
        $types_rekap .= "s";
    }
}

$sql_rekap = "
SELECT 
    s.id AS siswa_id,
    s.nis,
    s.nama,
    s.kelas,
    COUNT(cb.id) AS total_catatan
FROM siswa s
LEFT JOIN catatan_bk cb ON s.id = cb.siswa_id
$where_rekap
GROUP BY s.id, s.nis, s.nama, s.kelas
HAVING total_catatan > 0
ORDER BY total_catatan DESC, s.kelas ASC, s.nama ASC
";

$stmt_rekap = $conn->prepare($sql_rekap);
if (!empty($params_rekap)) {
    $stmt_rekap->bind_param($types_rekap, ...$params_rekap);
}
$stmt_rekap->execute();
$result_rekap = $stmt_rekap->get_result();
$rekap_list = [];
while ($row = $result_rekap->fetch_assoc()) {
    $rekap_list[] = $row;
}

// =========================
// QUERY LIST CATATAN BK (Tabel Utama)
// =========================
$where = "WHERE s.status = 'Aktif'";
$params = [];
$types = "";

if (!empty($selected_kelas)) {
    $where .= " AND s.kelas = ?";
    $params[] = $selected_kelas;
    $types .= "s";
} elseif ($is_locked && count($allowed_kelas) > 1) {
    $placeholders = implode(',', array_fill(0, count($allowed_kelas), '?'));
    $where .= " AND s.kelas IN ($placeholders)";
    foreach ($allowed_kelas as $k) {
        $params[] = $k;
        $types .= "s";
    }
}

if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
    $where .= " AND cb.tanggal BETWEEN ? AND ?";
    $params[] = $tgl_mulai;
    $params[] = $tgl_selesai;
    $types .= "ss";
}

if (!empty($search_keyword)) {
    $where .= " AND (s.nama LIKE ? OR s.nis LIKE ? OR cb.permasalahan LIKE ?)";
    $kw = "%" . $search_keyword . "%";
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
    $types .= "sss";
}

$sql = "
SELECT 
    cb.id,
    cb.tanggal,
    cb.jenis_layanan,
    cb.permasalahan,
    cb.tindak_lanjut,
    cb.status,
    s.nis,
    s.nama,
    s.kelas,
    s.id AS siswa_id
FROM catatan_bk cb
JOIN siswa s ON cb.siswa_id = s.id
$where
ORDER BY cb.tanggal DESC, cb.id DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$catatan_list = [];
while ($row = $result->fetch_assoc()) {
    $catatan_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Catatan Harian BK</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    @media print {
        body { background: white !important; }
        .print\:hidden { display: none !important; }
        .print\:shadow-none { box-shadow: none !important; border: none !important; }
        .print\:p-0 { padding: 0 !important; }
        table { border-collapse: collapse !important; width: 100% !important; }
        th, td { border: 1px solid #e5e7eb !important; padding: 8px !important; }
        thead th { background-color: #f8fafc !important; color: #334155 !important; }
    }
</style>
</head>

<body class="bg-slate-50 min-h-screen print:bg-white">

<div class="print:hidden">
    <?php include 'nav.php'; ?>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 print:p-0">

    <!-- NOTIFIKASI -->
    <?php if(!empty($msg_success)): ?>
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-2xl flex justify-between items-center print:hidden">
            <span>✅ <?= htmlspecialchars($msg_success) ?></span>
            <button onclick="this.parentElement.remove()" class="font-bold">&times;</button>
        </div>
    <?php endif; ?>

    <?php if(!empty($msg_error)): ?>
        <div class="mb-4 p-4 bg-rose-50 border border-rose-200 text-rose-700 text-sm rounded-2xl flex justify-between items-center print:hidden">
            <span>⚠️ <?= htmlspecialchars($msg_error) ?></span>
            <button onclick="this.parentElement.remove()" class="font-bold">&times;</button>
        </div>
    <?php endif; ?>

    <!-- HEADER & ACTION -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-200/65 mb-6 print:shadow-none print:border-none print:mb-4 print:p-0">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 print:mb-2">
            <div>
                <span class="inline-block px-3 py-1 bg-indigo-50 text-indigo-600 font-semibold text-xs rounded-full mb-2 print:hidden">Bimbingan Konseling</span>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Catatan Harian BK</h1>
                <p class="text-sm text-gray-500 mt-1">Jurnal dan rekam jejak penanganan kedisiplinan serta bimbingan siswa.</p>
            </div>
            
            <div class="flex items-center gap-3 print:hidden">
                <button onclick="openModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm px-5 py-2.5 rounded-xl shadow-md shadow-indigo-100 transition flex items-center gap-2 active:scale-95">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Tambah Catatan
                </button>
                <button onclick="window.print()" class="bg-slate-800 hover:bg-slate-900 text-white font-medium text-sm px-4 py-2.5 rounded-xl shadow-md transition flex items-center gap-2 active:scale-95">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Cetak Jurnal
                </button>
            </div>
        </div>

        <!-- FILTER FORM -->
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-4 border-t border-slate-100 print:hidden">
            <?php $single_lock = ($is_locked && count($allowed_kelas) === 1); ?>
            
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Kelas</label>
                <select name="<?= $single_lock ? '' : 'kelas' ?>" class="w-full border border-slate-200 text-gray-700 text-sm p-2.5 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20 <?= $single_lock ? 'bg-slate-100 cursor-not-allowed' : 'bg-slate-50' ?>" <?= $single_lock ? 'disabled' : '' ?>>
                    <?php if (!$single_lock): ?>
                        <option value="">Semua Kelas</option>
                    <?php endif; ?>
                    <?php foreach ($kelas_list as $k): ?>
                        <option value="<?= $k ?>" <?= $selected_kelas == $k ? 'selected' : '' ?>>
                            Kelas <?= htmlspecialchars($k) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($single_lock): ?>
                    <input type="hidden" name="kelas" value="<?= htmlspecialchars($selected_kelas) ?>">
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Tgl Mulai</label>
                <input type="date" name="tgl_mulai" value="<?= htmlspecialchars($tgl_mulai) ?>" class="w-full border border-slate-200 text-gray-700 text-sm p-2.5 rounded-xl bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Tgl Selesai</label>
                <input type="date" name="tgl_selesai" value="<?= htmlspecialchars($tgl_selesai) ?>" class="w-full border border-slate-200 text-gray-700 text-sm p-2.5 rounded-xl bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div class="flex items-end gap-2">
                <input type="text" name="q" placeholder="Cari Nama / NIS..." value="<?= htmlspecialchars($search_keyword) ?>" class="w-full border border-slate-200 text-gray-700 text-sm p-2.5 rounded-xl bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-500/20">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm px-4 py-2.5 rounded-xl shadow-sm transition">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- REKAP CATATAN PER SISWA -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 p-6 mb-6 print:hidden">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="font-bold text-gray-800 text-base">Rekapitulasi Catatan per Siswa</h3>
                <p class="text-xs text-gray-500">Klik jumlah catatan pada siswa untuk melihat pop-up riwayat penanganan.</p>
            </div>
            <span class="text-xs font-semibold bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full"><?= count($rekap_list) ?> Siswa Memiliki Catatan</span>
        </div>

        <?php if (empty($rekap_list)): ?>
            <div class="text-center py-6 text-gray-400 text-sm italic border border-dashed border-slate-200 rounded-2xl">
                Belum ada data rekap siswa yang tercatat.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-h-60 overflow-y-auto pr-1">
                <?php foreach ($rekap_list as $rk): ?>
                    <div class="border border-slate-200/80 rounded-2xl p-3.5 hover:border-indigo-300 hover:shadow-sm transition bg-slate-50/50 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start gap-1">
                                <span class="font-semibold text-sm text-gray-900 truncate"><?= htmlspecialchars($rk['nama']) ?></span>
                                <span class="text-[10px] font-mono bg-slate-200 px-1.5 py-0.5 rounded text-slate-700 shrink-0">Kelas <?= htmlspecialchars($rk['kelas']) ?></span>
                            </div>
                            <div class="text-xs text-gray-400 font-mono mt-0.5">NIS: <?= htmlspecialchars($rk['nis']) ?></div>
                        </div>
                        <div class="mt-3 pt-2 border-t border-slate-200/60 flex justify-between items-center">
                            <span class="text-xs text-gray-600 font-medium">Total Catatan:</span>
                            <button onclick="openRiwayatModal(<?= $rk['siswa_id'] ?>, '<?= htmlspecialchars($rk['nama'], ENT_QUOTES) ?>')" class="bg-indigo-100 hover:bg-indigo-600 hover:text-white text-indigo-700 text-xs font-bold px-2.5 py-1 rounded-lg transition">
                                <?= $rk['total_catatan'] ?> Kasus ↗
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- TABLE CATATAN BK -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 overflow-hidden print:shadow-none print:border-none print:rounded-none">
        <div class="p-6 border-b border-slate-100 print:hidden flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Daftar Bimbingan & Konseling</h3>
            <span class="text-xs font-semibold bg-slate-100 px-3 py-1 rounded-full text-slate-600">Total: <?= count($catatan_list) ?> Catatan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-semibold tracking-wider border-b border-slate-100 print:bg-gray-100">
                    <tr>
                        <th class="px-4 py-3.5 text-center w-12">No</th>
                        <th class="px-4 py-3.5 w-28">Tanggal</th>
                        <th class="px-4 py-3.5">Siswa</th>
                        <th class="px-4 py-3.5 w-36">Layanan</th>
                        <th class="px-4 py-3.5">Permasalahan / Catatan</th>
                        <th class="px-4 py-3.5">Tindak Lanjut</th>
                        <th class="px-4 py-3.5 text-center w-28">Status</th>
                        <th class="px-4 py-3.5 text-center w-16 print:hidden">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-gray-600">
                <?php if (empty($catatan_list)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-12 text-gray-400 font-medium">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <span class="text-3xl">📋</span>
                                <p>Belum ada catatan bimbingan konseling yang ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: 
                    $no = 1;
                    foreach($catatan_list as $row):
                        $status_badge = "bg-slate-100 text-slate-700";
                        if ($row['status'] === 'Selesai') {
                            $status_badge = "bg-emerald-50 text-emerald-700 border border-emerald-200";
                        } elseif ($row['status'] === 'Dalam Proses') {
                            $status_badge = "bg-amber-50 text-amber-700 border border-amber-200";
                        } elseif ($row['status'] === 'Perlu Penanganan Khusus') {
                            $status_badge = "bg-rose-50 text-rose-700 border border-rose-200";
                        }
                ?>
                    <tr class="hover:bg-slate-50/80 transition print:hover:bg-transparent">
                        <td class="px-4 py-3.5 text-center font-mono text-xs"><?= $no++ ?></td>
                        <td class="px-4 py-3.5 text-xs font-medium text-slate-700 whitespace-nowrap">
                            <?= date('d/m/Y', strtotime($row['tanggal'])) ?>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="font-semibold text-gray-900 cursor-pointer hover:text-indigo-600" onclick="openRiwayatModal(<?= $row['siswa_id'] ?>, '<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>')">
                                <?= htmlspecialchars($row['nama']) ?> ↗
                            </div>
                            <div class="text-xs text-gray-400 font-mono">NIS: <?= htmlspecialchars($row['nis']) ?> | Kelas: <?= htmlspecialchars($row['kelas']) ?></div>
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="inline-block px-2.5 py-1 bg-slate-100 text-slate-700 text-xs font-medium rounded-lg whitespace-nowrap">
                                <?= htmlspecialchars($row['jenis_layanan']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3.5 max-w-xs leading-relaxed text-slate-700">
                            <?= nl2br(htmlspecialchars($row['permasalahan'])) ?>
                        </td>
                        <td class="px-4 py-3.5 max-w-xs leading-relaxed text-slate-600">
                            <?= !empty($row['tindak_lanjut']) ? nl2br(htmlspecialchars($row['tindak_lanjut'])) : '<span class="text-gray-300 italic">-</span>' ?>
                        </td>
                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                            <!-- Form Inline untuk Ubah Status Cepat -->
                            <form method="POST" class="inline-flex items-center gap-1.5">
                                <input type="hidden" name="action" value="edit_status">
                                <input type="hidden" name="id_status" value="<?= $row['id'] ?>">
                                <select name="status_baru" onchange="this.form.submit()" class="text-xs font-semibold rounded-xl px-2.5 py-1.5 outline-none cursor-pointer transition <?= $status_badge ?>">
                                    <option value="Dalam Proses" <?= $row['status'] === 'Dalam Proses' ? 'selected' : '' ?>>Dalam Proses</option>
                                    <option value="Selesai" <?= $row['status'] === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                    <option value="Perlu Penanganan Khusus" <?= $row['status'] === 'Perlu Penanganan Khusus' ? 'selected' : '' ?>>Penanganan Khusus</option>
                                </select>
                            </form>
                        </td>
                        <td class="px-4 py-3.5 text-center print:hidden">
                            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan ini?');">
                                <input type="hidden" name="action" value="hapus">
                                <input type="hidden" name="id_hapus" value="<?= $row['id'] ?>">
                                <button type="submit" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="Hapus Catatan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php 
                    endforeach; 
                endif; 
                ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL TAMBAH CATATAN BK -->
<div id="modalBK" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4 print:hidden">
    <div class="bg-white rounded-3xl shadow-xl border border-slate-100 max-w-lg w-full p-6 sm:p-8 animate-in fade-in zoom-in-95 duration-200">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-900">Tambah Catatan BK baru</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="tambah">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Tanggal</label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full border border-slate-200 text-sm p-3 rounded-xl bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Status Penanganan</label>
                    <select name="status" class="w-full border border-slate-200 text-sm p-3 rounded-xl bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="Dalam Proses">Dalam Proses</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Perlu Penanganan Khusus">Penanganan Khusus</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Pilih Siswa *</label>
                <select name="siswa_id" required class="w-full border border-slate-200 text-sm p-3 rounded-xl bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">-- Pilih Siswa --</option>
                    <?php foreach($siswa_options as $s): ?>
                        <option value="<?= $s['id'] ?>">
                            [<?= htmlspecialchars($s['kelas']) ?>] <?= htmlspecialchars($s['nama']) ?> (NIS: <?= htmlspecialchars($s['nis']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Jenis Layanan / Bimbingan *</label>
                <select name="jenis_layanan" required class="w-full border border-slate-200 text-sm p-3 rounded-xl bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <option value="Konseling Individual">Konseling Individual</option>
                    <option value="Bimbingan Kelompok">Bimbingan Kelompok</option>
                    <option value="Permasalahan Kedisiplinan">Permasalahan Kedisiplinan</option>
                    <option value="Home Visit (Kunjungan)">Home Visit (Kunjungan Rumah)</option>
                    <option value="Konsultasi Orang Tua">Konsultasi Orang Tua</option>
                    <option value="Bimbingan Karir / Akademik">Bimbingan Karir / Akademik</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Permasalahan / Deskripsi *</label>
                <textarea name="permasalahan" rows="3" required placeholder="Jelaskan kronologi/masalah siswa..." class="w-full border border-slate-200 text-sm p-3 rounded-xl bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-500/20"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Tindak Lanjut / Solusi</label>
                <textarea name="tindak_lanjut" rows="2" placeholder="Rencana atau tindakan yang dilakukan..." class="w-full border border-slate-200 text-sm p-3 rounded-xl bg-slate-50 outline-none focus:ring-2 focus:ring-indigo-500/20"></textarea>
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-md shadow-indigo-100 transition">
                    Simpan Catatan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL POP-UP RIWAYAT SISWA -->
<div id="modalRiwayat" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4 print:hidden">
    <div class="bg-white rounded-3xl shadow-xl border border-slate-100 max-w-2xl w-full p-6 sm:p-8 animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
            <div>
                <span class="text-xs font-semibold bg-indigo-50 text-indigo-600 px-2.5 py-0.5 rounded-full">Riwayat BK Siswa</span>
                <h3 id="riwayatNamaSiswa" class="text-lg font-bold text-gray-900 mt-1">Nama Siswa</h3>
            </div>
            <button onclick="closeRiwayatModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="riwayatContent" class="overflow-y-auto space-y-3 flex-1 pr-1">
            <div class="text-center py-8 text-gray-400 text-sm">Memuat data riwayat...</div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="closeRiwayatModal()" class="px-5 py-2 text-sm font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('modalBK').classList.remove('hidden');
    }
    function closeModal() {
        document.getElementById('modalBK').classList.add('hidden');
    }

    function openRiwayatModal(siswaId, namaSiswa) {
        document.getElementById('riwayatNamaSiswa').innerText = namaSiswa;
        document.getElementById('modalRiwayat').classList.remove('hidden');
        document.getElementById('riwayatContent').innerHTML = '<div class="text-center py-8 text-gray-400 text-sm">Memuat data riwayat...</div>';

        // Menggunakan URL relatif (karena berada dalam satu file yang sama)
        let fetchUrl = 'harian.php?get_riwayat=' + siswaId;

        fetch(fetchUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Respons jaringan bermasalah');
                }
                return response.json();
            })
            .then(data => {
                let container = document.getElementById('riwayatContent');
                if (!Array.isArray(data) || data.length === 0) {
                    container.innerHTML = '<div class="text-center py-8 text-gray-400 text-sm">Tidak ada riwayat catatan untuk siswa ini.</div>';
                    return;
                }

                let html = '';
                data.forEach((item, index) => {
                    let badgeColor = 'bg-slate-100 text-slate-700';
                    if (item.status === 'Selesai') badgeColor = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                    else if (item.status === 'Dalam Proses') badgeColor = 'bg-amber-50 text-amber-700 border border-amber-200';
                    else if (item.status === 'Perlu Penanganan Khusus') badgeColor = 'bg-rose-50 text-rose-700 border border-rose-200';

                   html += `
                        <div class="border border-slate-200/80 rounded-2xl p-4 bg-slate-50/50">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-mono font-bold bg-slate-200 px-2 py-0.5 rounded text-slate-700">#${index + 1}</span>
                                    <span class="text-xs font-semibold text-slate-600">${item.tanggal}</span>
                                    <span class="text-xs font-medium px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-lg">${item.jenis_layanan}</span>
                                </div>
                                <form method="POST" class="inline-flex items-center">
                                    <input type="hidden" name="action" value="edit_status">
                                    <input type="hidden" name="id_status" value="${item.id}">
                                    <select name="status_baru" onchange="this.form.submit()" class="text-xs font-semibold rounded-xl px-2.5 py-1 outline-none cursor-pointer transition ${badgeColor}">
                                        <option value="Dalam Proses" ${item.status === 'Dalam Proses' ? 'selected' : ''}>Dalam Proses</option>
                                        <option value="Selesai" ${item.status === 'Selesai' ? 'selected' : ''}>Selesai</option>
                                        <option value="Perlu Penanganan Khusus" ${item.status === 'Perlu Penanganan Khusus' ? 'selected' : ''}>Penanganan Khusus</option>
                                    </select>
                                </form>
                            </div>
                            <div class="text-sm text-gray-800 mt-2">
                                <strong class="text-xs text-gray-500 uppercase block mb-0.5">Permasalahan:</strong>
                                <p class="whitespace-pre-line">${item.permasalahan}</p>
                            </div>
                            <div class="text-sm text-gray-700 mt-2 pt-2 border-t border-slate-200/60">
                                <strong class="text-xs text-gray-500 uppercase block mb-0.5">Tindak Lanjut:</strong>
                                <p class="whitespace-pre-line">${item.tindak_lanjut ? item.tindak_lanjut : '<span class="text-gray-400 italic">Belum ada tindak lanjut</span>'}</p>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('riwayatContent').innerHTML = '<div class="text-center py-8 text-rose-500 text-sm">Gagal memuat data riwayat dari server.</div>';
            });
    }
    function closeRiwayatModal() {
        document.getElementById('modalRiwayat').classList.add('hidden');
    }
</script>

</body>
</html>