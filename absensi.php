<?php
include 'koneksi.php';
include 'auth.php';

if (function_exists('requireLogin')) {
    requireLogin();
}

// 1. MEMUAT LIBRARY SIMPLEXLSX
if (file_exists('SimpleXLSX.php')) {
    require_once 'SimpleXLSX.php';
} else {
    $library_missing = true;
}

use Shuchkin\SimpleXLSX;

$message = '';
$import_summary = null;

// --- 2. FUNGSI HELPER PARSING EXCEL ---
function parse_excel_date($date_val) {
    if (empty($date_val)) return null;
    if (is_numeric($date_val)) {
        $unix_date = ($date_val - 25569) * 86400;
        return gmdate("Y-m-d", $unix_date);
    }
    $bulan_indo = [
        'Januari' => 'January', 'Februari' => 'February', 'Maret' => 'March',
        'April' => 'April', 'Mei' => 'May', 'Juni' => 'June', 'Juli' => 'July',
        'Agustus' => 'August', 'September' => 'September', 'Oktober' => 'October',
        'November' => 'November', 'Desember' => 'December'
    ];
    $date_val = strtr($date_val, $bulan_indo);
    return date("Y-m-d", strtotime($date_val));
}

function parse_excel_time($time_val) {
    if ($time_val === '' || $time_val === null) return null;
    if (is_numeric($time_val)) {
        $unix_time = $time_val * 86400;
        return gmdate("H:i:s", $unix_time);
    }
    return date("H:i:s", strtotime($time_val));
}

$message = '';
$log_results = [];

// --- 3. PROSES UPLOAD & IMPORT EXCEL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
    require_once 'SimpleXLSX.php'; 

    if ($xlsx = SimpleXLSX::parse($_FILES['file_excel']['tmp_name'])) {
        $rows = $xlsx->rows();
        $total_sukses = 0; $total_gagal = 0; $total_skip = 0;

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("
                INSERT INTO log_absensi (nip, nama, tanggal, jam_datang, jam_pulang, lokasi) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    nama = VALUES(nama),
                    jam_datang = VALUES(jam_datang),
                    jam_pulang = VALUES(jam_pulang),
                    lokasi = VALUES(lokasi)
            ");

            foreach ($rows as $r_idx => $row) {
                if ($r_idx === 0) continue; 

                $nip_raw        = isset($row[0]) ? trim($row[0]) : '';
                $nama           = isset($row[1]) ? trim($row[1]) : '';
                $tanggal_raw    = isset($row[2]) ? trim($row[2]) : '';
                $jam_datang_raw = isset($row[3]) ? trim($row[3]) : '';
                $jam_pulang_raw = isset($row[4]) ? trim($row[4]) : '';
                $lokasi         = isset($row[5]) ? trim($row[5]) : '';

                if (empty($nip_raw) || empty($tanggal_raw)) {
                    $total_skip++;
                    $log_results[] = "<tr class='bg-gray-50'><td colspan='4' class='px-4 py-2 text-sm text-gray-500'>Baris " . ($r_idx + 1) . " dilewati (Data kosong).</td></tr>";
                    continue;
                }

                $nip = str_pad($nip_raw, 6, '0', STR_PAD_LEFT); 
                $tanggal_parsed    = parse_excel_date($tanggal_raw);
                $jam_datang_parsed = parse_excel_time($jam_datang_raw);
                $jam_pulang_parsed = parse_excel_time($jam_pulang_raw);

                $stmt->bind_param("ssssss", $nip, $nama, $tanggal_parsed, $jam_datang_parsed, $jam_pulang_parsed, $lokasi);
                
                if ($stmt->execute()) {
                    $total_sukses++;
                    $log_results[] = "<tr class='border-b'><td class='px-4 py-2 text-sm'>$nip</td><td class='px-4 py-2 text-sm'>$nama</td><td class='px-4 py-2 text-sm'>$tanggal_parsed</td><td class='px-4 py-2 text-sm text-emerald-600 font-semibold'>Sukses</td></tr>";
                } else {
                    $total_gagal++;
                    $log_results[] = "<tr class='border-b bg-rose-50'><td class='px-4 py-2 text-sm'>$nip</td><td class='px-4 py-2 text-sm'>$nama</td><td class='px-4 py-2 text-sm'>$tanggal_parsed</td><td class='px-4 py-2 text-sm text-rose-600 font-semibold'>Gagal</td></tr>";
                }
            }

            $conn->commit();
            $stmt->close();
            
            $message = "<div class='bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-lg mb-6 shadow-sm'>
                            <h3 class='font-bold mb-1'>Import Berhasil!</h3>
                            <p class='text-sm'>Data Tersimpan: <strong>$total_sukses</strong> | Gagal: <strong>$total_gagal</strong> | Dilewati: <strong>$total_skip</strong></p>
                        </div>";

        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-lg mb-6 shadow-sm'>
                            <h3 class='font-bold mb-1'>Kesalahan Fatal</h3><p class='text-sm'>" . $e->getMessage() . "</p>
                        </div>";
        }
    } else {
        $message = "<div class='bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-lg mb-6 shadow-sm'>
                        <h3 class='font-bold mb-1'>Gagal Membaca File</h3><p class='text-sm'>" . SimpleXLSX::parseError() . "</p>
                    </div>";
    }
}

// --- 4. LOGIKA REKAPITULASI (Dieksekusi setelah import agar data selalu up-to-date) ---
$filter_bulan = $_GET['bulan'] ?? date('Y-m'); 

// Ambil Hari Kerja Aktif di bulan tersebut
$stmt_hari = $conn->prepare("SELECT DISTINCT tanggal FROM log_absensi WHERE DATE_FORMAT(tanggal, '%Y-%m') = ? ORDER BY tanggal ASC");
$stmt_hari->bind_param("s", $filter_bulan);
$stmt_hari->execute();
$res_hari = $stmt_hari->get_result();

$hari_kerja_aktif = [];
while ($row = $res_hari->fetch_assoc()) $hari_kerja_aktif[] = $row['tanggal'];
$total_hari_kerja = count($hari_kerja_aktif);

// Ambil Total Kehadiran per Pegawai
$stmt_rekap = $conn->prepare("
    SELECT p.nip, p.nama, COUNT(a.tanggal) as total_hadir
    FROM (SELECT nip, MAX(nama) as nama FROM log_absensi GROUP BY nip) p
    LEFT JOIN log_absensi a ON p.nip = a.nip AND DATE_FORMAT(a.tanggal, '%Y-%m') = ?
    GROUP BY p.nip, p.nama ORDER BY p.nama ASC
");
$stmt_rekap->bind_param("s", $filter_bulan);
$stmt_rekap->execute();
$rekap_karyawan = $stmt_rekap->get_result()->fetch_all(MYSQLI_ASSOC);

// Ambil Deteksi Alpa (Pegawai yang tidak ada log di hari kerja aktif)
$stmt_alpa = $conn->prepare("
    SELECT p.nip, d.tanggal
    FROM (SELECT nip FROM log_absensi GROUP BY nip) p
    CROSS JOIN (SELECT DISTINCT tanggal FROM log_absensi WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?) d
    LEFT JOIN log_absensi a ON p.nip = a.nip AND d.tanggal = a.tanggal
    WHERE a.nip IS NULL ORDER BY d.tanggal ASC
");
$stmt_alpa->bind_param("s", $filter_bulan);
$stmt_alpa->execute();
$detail_alpa_raw = $stmt_alpa->get_result()->fetch_all(MYSQLI_ASSOC);

$data_alpa = [];
foreach ($detail_alpa_raw as $alpa) {
    $data_alpa[$alpa['nip']][] = date('d/m/Y', strtotime($alpa['tanggal']));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Absensi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen p-6 md:p-8 text-slate-800">
    <?php include 'nav.php'; ?>
    <div class="max-w-6xl mx-auto space-y-8">
        
        <!-- Header Utama -->
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Manajemen Absensi Terpadu</h1>
            <p class="text-slate-500 mt-1">Import file dari mesin absensi dan pantau rekap kehadiran pegawai secara otomatis.</p>
        </div>

        <?= $message ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- BAGIAN KIRI: Form Upload & Log -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Box Upload -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4">1. Import Log Baru</h2>
                    <form action="?bulan=<?= $filter_bulan ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="file" name="file_excel" accept=".xlsx" required
                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                            Unggah & Proses
                        </button>
                    </form>
                </div>

                <!-- Box Log Eksekusi (Muncul jika ada upload) -->
                <?php if (!empty($log_results)): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-[400px]">
                    <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
                        <h3 class="font-semibold text-sm text-slate-800">Log Eksekusi Terakhir</h3>
                    </div>
                    <div class="overflow-y-auto flex-1">
                        <table class="w-full text-left border-collapse">
                            <tbody class="divide-y divide-slate-100">
                                <?= implode('', $log_results) ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- BAGIAN KANAN: Filter & Tabel Rekap -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Filter Bar -->
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="flex-1">
                        <h2 class="text-lg font-bold text-slate-800">2. Rekapitulasi & Anomali</h2>
                    </div>
                    
                    <form method="GET" class="flex items-center gap-2">
                        <input type="month" name="bulan" value="<?= $filter_bulan ?>" 
                            class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                            Cek
                        </button>
                    </form>
                </div>

                <!-- Info Hari Kerja -->
                <div class="bg-indigo-50 px-4 py-3 rounded-xl border border-indigo-100 flex items-center justify-between">
                    <span class="text-sm font-medium text-indigo-900">Total Hari Aktif Tercatat di Bulan Ini:</span>
                    <span class="text-lg font-bold text-indigo-700"><?= $total_hari_kerja ?> Hari</span>
                </div>

                <!-- Tabel Rekap -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500 tracking-wider">
                                    <th class="px-4 py-3 font-semibold">Pegawai</th>
                                    <th class="px-4 py-3 font-semibold text-center">Hadir</th>
                                    <th class="px-4 py-3 font-semibold text-center">Alpa</th>
                                    <th class="px-4 py-3 font-semibold">Tanggal Kosong</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (count($rekap_karyawan) > 0): ?>
                                    <?php foreach ($rekap_karyawan as $row): 
                                        $nip = $row['nip'];
                                        $total_hadir = $row['total_hadir'];
                                        $total_alpa = $total_hari_kerja - $total_hadir;
                                        $list_tgl_alpa = isset($data_alpa[$nip]) ? implode(', ', $data_alpa[$nip]) : '-';
                                    ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-4 py-3">
                                            <div class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($row['nama']) ?></div>
                                            <div class="text-xs text-slate-400 font-mono mt-0.5"><?= htmlspecialchars($nip) ?></div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center justify-center bg-emerald-100 text-emerald-700 font-bold px-2.5 py-0.5 rounded-full text-xs">
                                                <?= $total_hadir ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <?php if ($total_alpa > 0): ?>
                                                <span class="inline-flex items-center justify-center bg-rose-100 text-rose-700 font-bold px-2.5 py-0.5 rounded-full text-xs">
                                                    <?= $total_alpa ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center justify-center bg-slate-100 text-slate-500 font-bold px-2.5 py-0.5 rounded-full text-xs">
                                                    0
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td class="px-4 py-3 text-xs text-rose-600 font-medium leading-relaxed max-w-xs break-words" title="<?= $list_tgl_alpa ?>"> 
    <?= $list_tgl_alpa ?> 
</td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-4 py-12 text-center text-slate-400">
                                            <p class="font-medium text-slate-500 mb-1">Data tidak ditemukan</p>
                                            <p class="text-xs">Belum ada rekap data absensi untuk periode ini.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>