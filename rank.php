<?php
include 'koneksi.php';
include 'auth.php';

if (function_exists('requireLogin')) {
    requireLogin();
}

// =========================
// SESSION KELAS LOCK (Mendukung Multi-Kelas)
// =========================
$session_kelas_raw = $_SESSION['kelas'] ?? '';
$is_locked = false;
$allowed_kelas = [];

if (!empty($session_kelas_raw)) {
    $is_locked = true;
    // Pecah berdasarkan koma jika memegang banyak kelas
    $allowed_kelas = array_map('trim', explode(',', $session_kelas_raw));
    $kelas_list = $allowed_kelas;
} else {
    // Jika Admin, ambil semua kelas yang ada siswa aktifnya
    $kelas_result = $conn->query("SELECT DISTINCT kelas FROM siswa WHERE status = 'Aktif' ORDER BY kelas");
    $kelas_list = [];
    while ($row = $kelas_result->fetch_assoc()) {
        $kelas_list[] = $row['kelas'];
    }
}

// =========================
// FILTER KELAS
// =========================
$selected_kelas = $_GET['kelas'] ?? '';

// Validasi Keamanan Filter Kelas
if ($is_locked) {
    if (!empty($selected_kelas) && !in_array($selected_kelas, $allowed_kelas)) {
        // Jika mencoba filter kelas di luar hak akses, paksa ke kelas default-nya
        $selected_kelas = $allowed_kelas[0];
    } elseif (empty($selected_kelas) && count($allowed_kelas) === 1) {
        // Jika hanya pegang 1 kelas dan filter kosong, otomatis pilih kelas tersebut
        $selected_kelas = $allowed_kelas[0];
    }
}

// =========================
// QUERY RANKING (Hanya Siswa Aktif)
// =========================
$where = "WHERE s.status = 'Aktif'";
$params = [];
$types = "";

if (!empty($selected_kelas)) {
    $where .= " AND s.kelas = ?";
    $params[] = $selected_kelas;
    $types .= "s";
} elseif ($is_locked && count($allowed_kelas) > 1) {
    // Tampilkan semua kelas hak aksesnya jika filter 'Semua Kelas' dipilih
    $placeholders = implode(',', array_fill(0, count($allowed_kelas), '?'));
    $where .= " AND s.kelas IN ($placeholders)";
    foreach ($allowed_kelas as $k) {
        $params[] = $k;
        $types .= "s";
    }
}

$sql = "
SELECT 
    s.id,
    s.nis,
    s.nama,
    s.kelas,
    AVG(
        (
            (COALESCE(n.s1,0) + COALESCE(n.s2,0) + COALESCE(n.s3,0) + COALESCE(n.s4,0) + COALESCE(n.s5,0) + COALESCE(n.s6,0)) / 6
        ) * 0.3 + (COALESCE(n.nilai_ujian,0) * 0.7)
    ) AS rata_rata_total
FROM siswa s
LEFT JOIN nilai n ON s.id = n.siswa_id
$where
GROUP BY s.id, s.nis, s.nama, s.kelas
HAVING rata_rata_total > 0
ORDER BY rata_rata_total DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$rankings = [];
while ($row = $result->fetch_assoc()) {
    $rankings[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rangking Siswa Aktif</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    /* CSS Khusus untuk Cetak (Print) */
    @media print {
        body { background: white !important; }
        .print\:hidden { display: none !important; }
        .print\:shadow-none { box-shadow: none !important; border: none !important; }
        .print\:p-0 { padding: 0 !important; }
        .print\:border-none { border: none !important; }
        table { border-collapse: collapse !important; width: 100% !important; }
        th, td { border: 1px solid #e5e7eb !important; padding: 12px 8px !important; }
        thead th { background-color: #f8fafc !important; color: #334155 !important; }
    }
</style>
</head>

<body class="bg-slate-50 min-h-screen print:bg-white">

<div class="print:hidden">
    <?php include 'nav.php'; ?>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 print:p-0">

    <!-- HEADER & FILTER -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-200/60 mb-6 print:shadow-none print:border-none print:mb-4 print:p-0">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 print:mb-2">
            <div>
                <span class="inline-block px-3 py-1 bg-indigo-50 text-indigo-600 font-semibold text-xs rounded-full mb-2 print:hidden">Prestasi</span>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Rangking Siswa Aktif</h1>
                <p class="text-sm text-gray-500 mt-1">Daftar peringkat prestasi akademik berdasarkan rata-rata nilai akhir.</p>
                <?php if(!empty($selected_kelas)): ?>
                    <p class="text-sm font-semibold text-gray-800 mt-1 hidden print:block">Kelas: <?= htmlspecialchars($selected_kelas) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Tombol Cetak -->
            <button onclick="window.print()" class="print:hidden bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm px-5 py-2.5 rounded-xl shadow-md shadow-indigo-100 transition flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Cetak Rangking
            </button>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-slate-100 print:hidden">
            
            <?php $single_lock = ($is_locked && count($allowed_kelas) === 1); ?>
            
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Filter Kelas</label>
                <select name="<?= $single_lock ? '' : 'kelas' ?>" class="w-full border border-slate-200 text-gray-700 text-sm p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition <?= $single_lock ? 'bg-slate-100 cursor-not-allowed' : 'bg-slate-50' ?>" <?= $single_lock ? 'disabled' : '' ?>>
                    <?php if (!$single_lock): ?>
                        <option value="">Semua Kelas Aktif</option>
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

            <div class="flex items-end">
                <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-medium text-sm p-3 rounded-xl shadow-md transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Filter Rangking
                </button>
            </div>
        </form>
    </div>

    <!-- TABLE RANKING -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 overflow-hidden print:shadow-none print:border-none print:rounded-none">
        <div class="p-6 border-b border-slate-100 print:hidden">
            <h3 class="font-bold text-gray-800">Peringkat Nilai Siswa</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-semibold tracking-wider border-b border-slate-100 print:bg-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-center w-20">Rank</th>
                        <th class="px-6 py-4">NIS</th>
                        <th class="px-6 py-4">Nama Siswa</th>
                        <th class="px-6 py-4">Kelas</th>
                        <th class="px-6 py-4 text-center">Nilai Rata-rata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-gray-600">
                <?php 
                if (empty($rankings)): 
                ?>
                    <tr>
                        <td colspan="5" class="text-center py-12 text-gray-400 font-medium">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <span class="text-2xl">🏆</span>
                                <p>Belum ada data rangking untuk siswa aktif.</p>
                            </div>
                        </td>
                    </tr>
                <?php 
                else:
                    $rank = 1;
                    foreach($rankings as $row):
                        // Warna badge untuk juara 1, 2, 3 (Dihilangkan warnanya saat dicetak agar rapi)
                        $rank_badge = "bg-slate-100 text-slate-700 print:bg-transparent print:border print:border-gray-300";
                        if ($rank === 1) $rank_badge = "bg-amber-100 text-amber-700 font-bold print:bg-transparent print:border print:border-gray-300";
                        elseif ($rank === 2) $rank_badge = "bg-slate-200 text-slate-700 font-bold print:bg-transparent print:border print:border-gray-300";
                        elseif ($rank === 3) $rank_badge = "bg-orange-100 text-orange-700 font-bold print:bg-transparent print:border print:border-gray-300";
                ?>
                    <tr class="hover:bg-slate-50/80 transition print:hover:bg-transparent">
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-xs <?= $rank_badge ?>">
                                <?= $rank++ ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 font-mono text-xs"><?= htmlspecialchars($row['nis']) ?></td>
                        <td class="px-6 py-4 font-semibold text-gray-800"><?= htmlspecialchars($row['nama']) ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 bg-slate-100 text-slate-700 text-xs font-medium rounded-lg print:bg-transparent print:p-0">
                                <?= htmlspecialchars($row['kelas']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 bg-indigo-50 text-indigo-600 font-bold text-xs rounded-xl print:bg-transparent print:p-0 print:text-black">
                                <?= number_format($row['rata_rata_total'], 2) ?>
                            </span>
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

</body>
</html>