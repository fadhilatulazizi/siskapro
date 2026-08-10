<?php
include 'koneksi.php';
include 'auth.php';

requireLogin();

/**
 * =========================
 * HELP FUNCTION SAFE QUERY
 * =========================
 */
function singleValue($conn, $sql, $type = null, $param = null) {
    if ($type) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($type, $param);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res['count'] ?? 0;
    } else {
        $res = $conn->query($sql)->fetch_assoc();
        return $res['count'] ?? 0;
    }
}

/**
 * =========================
 * TOTAL SISWA
 * =========================
 */
if (!empty($_SESSION['kelas'])) {
    $total_siswa = singleValue(
        $conn,
        "SELECT COUNT(*) as count FROM siswa WHERE kelas = ?",
        "s",
        $_SESSION['kelas']
    );
} else {
    $total_siswa = singleValue($conn, "SELECT COUNT(*) as count FROM siswa");
}

/**
 * =========================
 * TOTAL MAPEL
 * =========================
 */
$total_mapel = singleValue($conn, "SELECT COUNT(*) as count FROM mapel");

/**
 * =========================
 * TOTAL NILAI
 * =========================
 */
if (!empty($_SESSION['kelas'])) {
    $total_nilai = singleValue(
        $conn,
        "SELECT COUNT(*) as count 
         FROM nilai 
         JOIN siswa ON nilai.siswa_id = siswa.id 
         WHERE siswa.kelas = ?",
        "s",
        $_SESSION['kelas']
    );
} else {
    $total_nilai = singleValue($conn, "SELECT COUNT(*) as count FROM nilai");
}

/**
 * =========================
 * KELAS STATS
 * =========================
 */
if (!empty($_SESSION['kelas'])) {
    $stmt = $conn->prepare("
        SELECT kelas, COUNT(*) as count 
        FROM siswa 
        WHERE kelas = ? AND status = 'Aktif'
        GROUP BY kelas 
        ORDER BY kelas
    ");
    $stmt->bind_param("s", $_SESSION['kelas']);
    $stmt->execute();
    $kelas_stats = $stmt->get_result();
} else {
    $kelas_stats = $conn->query("
        SELECT kelas, COUNT(*) as count 
        FROM siswa 
        WHERE status = 'Aktif'
        GROUP BY kelas 
        ORDER BY kelas
    ");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="author" content="Fadhilatul Azizi">
<meta name="description" content="Aplikasi untuk menginput nilai SKNR, SKL, dan Transkrip Ijazah kelas 9 di SMP Negeri 1 Guntur">
<title>INPUT NILAI SKNR, SKL DAN TRANSKRIP KELAS IX - SMP NEGERI 1 GUNTUR</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-50 text-slate-800 antialiased min-h-screen pb-12">

<?php include 'nav.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- WELCOME / HEADER BANNER -->
    <div class="mb-8 bg-gradient-to-r from-indigo-600 to-violet-600 rounded-3xl p-6 sm:p-8 text-white shadow-lg shadow-indigo-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <span class="bg-white/20 text-white text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider">SMP Negeri 1 Guntur</span>
            <h1 class="text-2xl sm:text-3xl font-bold mt-2">Dashboard Pengelolaan Nilai</h1>
            <p class="text-indigo-100 text-sm mt-1">Kelola data nilai SKNR, SKL, dan Transkrip Ijazah siswa kelas IX dengan cepat dan akurat.</p>
        </div>
        <div class="bg-white/10 backdrop-blur-md px-4 py-3 rounded-2xl border border-white/20 text-xs sm:text-sm">
            <span class="block text-indigo-200">Status Akses:</span>
            <span class="font-semibold text-white"><?= !empty($_SESSION['kelas']) ? 'Wali Kelas ' . htmlspecialchars($_SESSION['kelas']) : 'Administrator Sistem' ?></span>
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

        <!-- Card Total Siswa -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between transition hover:shadow-md">
            <div>
                <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider">Total Siswa</p>
                <p class="text-3xl font-extrabold text-slate-800 mt-2"><?= number_format($total_siswa) ?></p>
                <p class="text-emerald-600 text-xs font-medium mt-1 flex items-center gap-1">✓ Data terverifikasi</p>
            </div>
            <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 text-2xl shadow-sm">
                👥
            </div>
        </div>

        <!-- Card Total Mapel -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between transition hover:shadow-md">
            <div>
                <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider">Total Mata Pelajaran</p>
                <p class="text-3xl font-extrabold text-slate-800 mt-2"><?= number_format($total_mapel) ?></p>
                <p class="text-indigo-600 text-xs font-medium mt-1 flex items-center gap-1">📚 Kurikulum aktif</p>
            </div>
            <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 text-2xl shadow-sm">
                📖
            </div>
        </div>

        <!-- Card Total Nilai -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between transition hover:shadow-md">
            <div>
                <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider">Total Rekap Nilai</p>
                <p class="text-3xl font-extrabold text-slate-800 mt-2"><?= number_format($total_nilai) ?></p>
                <p class="text-amber-600 text-xs font-medium mt-1 flex items-center gap-1">⚡ Data entry database</p>
            </div>
            <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 text-2xl shadow-sm">
                📊
            </div>
        </div>

    </div>

    <!-- QUICK ACTIONS -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 mb-8">
        <h2 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span>⚡</span> Aksi Cepat
        </h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="rekap_nilai.php" class="group bg-gradient-to-br from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white p-4 rounded-xl text-center shadow-sm hover:shadow-md transition transform hover:-translate-y-0.5">
                <div class="text-2xl mb-1">📋</div>
                <div class="font-semibold text-sm">Rekap Nilai</div>
                <div class="text-[11px] text-orange-100 mt-0.5">Lihat rekapitulasi</div>
            </a>

            <a href="daftar_siswa.php" class="group bg-gradient-to-br from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white p-4 rounded-xl text-center shadow-sm hover:shadow-md transition transform hover:-translate-y-0.5">
                <div class="text-2xl mb-1">🎓</div>
                <div class="font-semibold text-sm">Data Siswa</div>
                <div class="text-[11px] text-blue-100 mt-0.5">Kelola daftar siswa</div>
            </a>

            <a href="input_nilai_kelas.php" class="group bg-gradient-to-br from-purple-500 to-violet-600 hover:from-purple-600 hover:to-violet-700 text-white p-4 rounded-xl text-center shadow-sm hover:shadow-md transition transform hover:-translate-y-0.5">
                <div class="text-2xl mb-1">✍️</div>
                <div class="font-semibold text-sm">Input Nilai</div>
                <div class="text-[11px] text-purple-100 mt-0.5">Masukkan nilai kelas</div>
            </a>

            <a href="nilai.php" class="group bg-gradient-to-br from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white p-4 rounded-xl text-center shadow-sm hover:shadow-md transition transform hover:-translate-y-0.5">
                <div class="text-2xl mb-1">📥</div>
                <div class="font-semibold text-sm">Import Data</div>
                <div class="text-[11px] text-emerald-100 mt-0.5">Unggah file Excel</div>
            </a>
        </div>
    </div>

    <!-- KELAS STATS -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h2 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span>🏫</span> Statistik Siswa per Kelas
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 uppercase text-[11px] font-semibold tracking-wider">
                        <th class="p-3.5 rounded-l-xl">Kelas</th>
                        <th class="p-3.5 rounded-r-xl">Jumlah Siswa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php while ($row = $kelas_stats->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="p-3.5 font-semibold text-slate-700 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            <?= htmlspecialchars($row['kelas']) ?>
                        </td>
                        <td class="p-3.5">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
                                <?= $row['count'] ?> Siswa
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>