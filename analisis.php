<?php
include 'koneksi.php';
include 'auth.php';

requireLogin();

if ($_SESSION['username'] !== 'admin') {
    die("❌ Hanya admin yang bisa akses halaman ini");
}

$kelas = $_GET['kelas'] ?? '';
$mapel_id = (int)($_GET['mapel_id'] ?? 0);

/**
 * =========================
 * DATA KELAS (Hanya Siswa Aktif)
 * =========================
 */
$kelas_list = $conn->query("SELECT DISTINCT kelas FROM siswa WHERE status = 'Aktif' ORDER BY kelas");

/**
 * =========================
 * DATA MAPEL
 * =========================
 */
$mapel = $conn->query("SELECT * FROM mapel ORDER BY nama_mapel ASC");

/**
 * =========================
 * FILTER WHERE (Wajib Siswa Aktif)
 * =========================
 */
$where = "WHERE s.status = 'Aktif'";
$params = [];
$types = "";

if ($kelas !== '') {
    $where .= " AND s.kelas = ?";
    $params[] = $kelas;
    $types .= "s";
}

if ($mapel_id > 0) {
    $where .= " AND n.mapel_id = ?";
    $params[] = $mapel_id;
    $types .= "i";
}

/**
 * =========================
 * RATA-RATA PER KELAS
 * =========================
 */
$rata_kelas = $conn->query("
    SELECT 
        s.kelas,
        AVG(
            (
                (COALESCE(n.s1,0) + COALESCE(n.s2,0) + COALESCE(n.s3,0) + COALESCE(n.s4,0) + COALESCE(n.s5,0) + COALESCE(n.s6,0)) / 6
            ) * 0.3 + (COALESCE(n.nilai_ujian,0) * 0.7)
        ) as rata
    FROM siswa s
    JOIN nilai n ON s.id = n.siswa_id
    WHERE s.status = 'Aktif'
    GROUP BY s.kelas
    ORDER BY rata DESC
");

/**
 * =========================
 * TOP 10 (Prepared Statement)
 * =========================
 */
$sql_top10 = "
    SELECT s.nama, s.kelas,
    AVG(
        (
            (COALESCE(n.s1,0) + COALESCE(n.s2,0) + COALESCE(n.s3,0) + COALESCE(n.s4,0) + COALESCE(n.s5,0) + COALESCE(n.s6,0)) / 6
        ) * 0.3 + (COALESCE(n.nilai_ujian,0) * 0.7)
    ) as rata
    FROM siswa s
    JOIN nilai n ON s.id = n.siswa_id
    $where
    GROUP BY s.id, s.nama, s.kelas
    HAVING rata > 0
    ORDER BY rata DESC
    LIMIT 10
";

$stmt_top10 = $conn->prepare($sql_top10);
if (!empty($params)) {
    $stmt_top10->bind_param($types, ...$params);
}
$stmt_top10->execute();
$top10 = $stmt_top10->get_result();

/**
 * =========================
 * RATA GLOBAL (Prepared Statement)
 * =========================
 */
$sql_avg = "
    SELECT AVG(
        (
            (COALESCE(n.s1,0) + COALESCE(n.s2,0) + COALESCE(n.s3,0) + COALESCE(n.s4,0) + COALESCE(n.s5,0) + COALESCE(n.s6,0)) / 6
        ) * 0.3 + (COALESCE(n.nilai_ujian,0) * 0.7)
    ) as rata
    FROM siswa s
    JOIN nilai n ON s.id = n.siswa_id
    $where
";

$stmt_avg = $conn->prepare($sql_avg);
if (!empty($params)) {
    $stmt_avg->bind_param($types, ...$params);
}
$stmt_avg->execute();
$avg = $stmt_avg->get_result()->fetch_assoc();
$avg_rata = $avg['rata'] ?? 0;

/**
 * =========================
 * DISTRIBUSI NILAI
 * =========================
 */
$range_0_59 = 0;
$range_60_69 = 0;
$range_70_79 = 0;
$range_80_89 = 0;
$range_90_100 = 0;

$sql_dist = "
    SELECT (
        (
            (COALESCE(n.s1,0) + COALESCE(n.s2,0) + COALESCE(n.s3,0) + COALESCE(n.s4,0) + COALESCE(n.s5,0) + COALESCE(n.s6,0)) / 6
        ) * 0.3 + (COALESCE(n.nilai_ujian,0) * 0.7)
    ) as rata
    FROM siswa s
    JOIN nilai n ON s.id = n.siswa_id
    $where
";

$stmt_dist = $conn->prepare($sql_dist);
if (!empty($params)) {
    $stmt_dist->bind_param($types, ...$params);
}
$stmt_dist->execute();
$dist = $stmt_dist->get_result();

while($d = $dist->fetch_assoc()){
    $r = $d['rata'];
    if ($r < 60) $range_0_59++;
    elseif ($r < 70) $range_60_69++;
    elseif ($r < 80) $range_70_79++;
    elseif ($r < 90) $range_80_89++;
    else $range_90_100++;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analisis Nilai Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-slate-50 min-h-screen">

<?php include 'nav.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- HEADER -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-200/60 mb-6">
        <span class="inline-block px-3 py-1 bg-indigo-50 text-indigo-600 font-semibold text-xs rounded-full mb-2">Panel Administrator</span>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Analisis Nilai Sekolah</h1>
        <p class="text-sm text-gray-500 mt-1">Evaluasi performa akademik khusus untuk siswa berstatus aktif.</p>
    </div>

    <!-- FILTER -->
    <form method="GET" class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200/60 mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Filter Kelas</label>
            <select name="kelas" class="w-full bg-slate-50 border border-slate-200 text-gray-700 text-sm p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                <option value="">Semua Kelas Aktif</option>
                <?php while($k = $kelas_list->fetch_assoc()): ?>
                    <option value="<?= $k['kelas'] ?>" <?= $kelas == $k['kelas'] ? 'selected' : '' ?>>
                        Kelas <?= $k['kelas'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Filter Mata Pelajaran</label>
            <select name="mapel_id" class="w-full bg-slate-50 border border-slate-200 text-gray-700 text-sm p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                <option value="0">Semua Mapel</option>
                <?php while($m = $mapel->fetch_assoc()): ?>
                    <option value="<?= $m['id'] ?>" <?= $mapel_id == $m['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['nama_mapel']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm p-3 rounded-xl shadow-md shadow-indigo-100 transition flex items-center justify-center gap-2">
                🔍 Terapkan Filter
            </button>
        </div>
    </form>

    <!-- KPI -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200/60 mb-6">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Rata-rata Sekolah (Siswa Aktif)</p>
        <h2 class="text-3xl font-extrabold text-indigo-600 mt-1">
            <?= number_format($avg_rata, 2) ?>
        </h2>
    </div>

    <!-- GRAFIK DISTRIBUSI -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-200/60 mb-6">
        <h2 class="font-bold text-gray-800 mb-4 flex items-center gap-2">📊 Distribusi Nilai Siswa</h2>
        <div class="relative h-72 w-full">
            <canvas id="chart"></canvas>
        </div>
    </div>

    <!-- INSIGHT -->
    <div class="bg-amber-50 border border-amber-200/80 p-5 rounded-2xl mb-6 flex items-start gap-3">
        <span class="text-xl">🧠</span>
        <div>
            <h2 class="font-bold text-amber-900 text-sm">Insight Otomatis</h2>
            <p class="text-xs sm:text-sm text-amber-800 mt-1">
                <?php
                if ($avg_rata < 70) {
                    echo "⚠️ Rata-rata sekolah masih rendah, perlu peningkatan metode pembelajaran dan evaluasi remidi.";
                } elseif ($avg_rata < 85) {
                    echo "📈 Kinerja nilai cukup baik, namun masih ada ruang optimalisasi prestasi.";
                } else {
                    echo "🏆 Performa akademik siswa sangat baik dan memuaskan secara keseluruhan.";
                }
                ?>
            </p>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">

        <!-- TOP 10 -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h2 class="font-bold text-gray-800">🏆 Top 10 Siswa Aktif</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-semibold tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="p-4">Nama Siswa</th>
                            <th class="p-4">Kelas</th>
                            <th class="p-4 text-center">Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-gray-600">
                        <?php if ($top10->num_rows === 0): ?>
                            <tr><td colspan="3" class="text-center p-6 text-gray-400">Belum ada data nilai.</td></tr>
                        <?php else: while($t = $top10->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-4 font-semibold text-gray-800"><?= htmlspecialchars($t['nama']) ?></td>
                                <td class="p-4"><span class="px-2.5 py-1 bg-slate-100 text-slate-700 text-xs rounded-lg"><?= htmlspecialchars($t['kelas']) ?></span></td>
                                <td class="p-4 text-center font-bold text-indigo-600"><?= number_format($t['rata'], 2) ?></td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RATA PER KELAS -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h2 class="font-bold text-gray-800">📚 Rata-rata per Kelas</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-semibold tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="p-4">Kelas</th>
                            <th class="p-4 text-center">Rata-rata Kelas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-gray-600">
                        <?php if ($rata_kelas->num_rows === 0): ?>
                            <tr><td colspan="2" class="text-center p-6 text-gray-400">Belum ada data kelas.</td></tr>
                        <?php else: while($k = $rata_kelas->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-4 font-semibold text-gray-800">Kelas <?= htmlspecialchars($k['kelas']) ?></td>
                                <td class="p-4 text-center font-bold text-indigo-600"><?= number_format($k['rata'], 2) ?></td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<script>
const ctx = document.getElementById('chart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['0 - 59', '60 - 69', '70 - 79', '80 - 89', '90 - 100'],
        datasets: [{
            label: 'Jumlah Siswa',
            data: [
                <?= $range_0_59 ?>,
                <?= $range_60_69 ?>,
                <?= $range_70_79 ?>,
                <?= $range_80_89 ?>,
                <?= $range_90_100 ?>
            ],
            backgroundColor: '#6366f1',
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});
</script>

</body>
</html>