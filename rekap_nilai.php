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
    // Jika Admin, ambil semua kelas
    $kelas_result = $conn->query("SELECT DISTINCT kelas FROM siswa WHERE status = 'Aktif' ORDER BY kelas");
    $kelas_list = [];
    while ($row = $kelas_result->fetch_assoc()) {
        $kelas_list[] = $row['kelas'];
    }
}

// =========================
// MAPEL
// =========================
$mapel = $conn->query("SELECT * FROM mapel ORDER BY nama_mapel ASC");

// =========================
// FILTER
// =========================
$selected_kelas = $_GET['kelas'] ?? '';
$selected_mapel_id = (int)($_GET['mapel_id'] ?? 0);

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
// QUERY BUILDER (Hanya siswa aktif)
// =========================
$where = "WHERE s.status = 'Aktif'";
$params = [];
$types = "";

// Kelas filter
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

// Mapel filter
if ($selected_mapel_id > 0) {
    $where .= " AND n.mapel_id = ?";
    $params[] = $selected_mapel_id;
    $types .= "i";
}

// =========================
// QUERY UTAMA
// =========================
$sql = "
SELECT 
    s.id,
    s.nis,
    s.nama,
    s.kelas,
    m.nama_mapel,
    COALESCE(n.s1,0) AS s1,
    COALESCE(n.s2,0) AS s2,
    COALESCE(n.s3,0) AS s3,
    COALESCE(n.s4,0) AS s4,
    COALESCE(n.s5,0) AS s5,
    COALESCE(n.s6,0) AS s6,
    COALESCE(n.nilai_ujian,0) AS nilai_ujian,
    n.id AS has_nilai
FROM siswa s
LEFT JOIN nilai n ON s.id = n.siswa_id
LEFT JOIN mapel m ON n.mapel_id = m.id
$where
ORDER BY s.kelas, s.nama ASC
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// =========================
// COPY DATA (untuk statistik + tabel)
// =========================
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// =========================
// STATISTIK
// =========================
$total_siswa = count($data);
$total_rata = 0;
$jumlah = 0;

$sem = 0.3;
$uj  = 0.7;

foreach ($data as $d) {
    $r = (
        (
            ($d['s1'] + $d['s2'] + $d['s3'] + $d['s4'] + $d['s5'] + $d['s6']) / 6
        ) * $sem
    ) + (
        $d['nilai_ujian'] * $uj
    );

    // Perbaikan: Hanya hitung siswa yang tabel nilainya ada (tidak NULL)
    if (!empty($d['has_nilai'])) {
        $total_rata += $r;
        $jumlah++;
    }
}

$overall_average = $jumlah > 0 ? $total_rata / $jumlah : 0;
$overall_average = round($overall_average, 2);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rekap Nilai Siswa Aktif</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    /* Merapikan tampilan saat dicetak (Print) */
    @media print {
        body { background: white !important; }
        .print\:hidden { display: none !important; }
        .print\:shadow-none { box-shadow: none !important; }
        .print\:p-0 { padding: 0 !important; }
        .print\:m-0 { margin: 0 !important; }
        table { border-collapse: collapse !important; width: 100% !important; }
        th, td { border: 1px solid #e5e7eb !important; padding: 8px !important; }
        thead th { background-color: #f3f4f6 !important; color: #111827 !important; }
    }
</style>
</head>

<body class="bg-gray-50">

<div class="print:hidden">
    <?php include 'nav.php'; ?>
</div>

<div class="max-w-7xl mx-auto px-4 py-8 print:p-0">

<!-- HEADER -->
<div class="bg-white p-6 rounded-2xl shadow mb-6 print:shadow-none print:p-0 print:mb-4">
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-2xl font-bold">Rekap Nilai Siswa Aktif</h1>
            <p class="text-gray-500">Ringkasan nilai khusus siswa dengan status aktif</p>
        </div>
        
        <!-- Tombol Cetak -->
        <button onclick="window.print()" class="print:hidden bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-xl flex items-center gap-2 transition active:scale-95 shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Cetak Rekap
        </button>
    </div>

    <!-- FILTER -->
    <form method="GET" class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 print:hidden">
        
        <!-- Jika dikunci pada 1 kelas spesifik, buat dropdown terlihat disabled dan pass value via hidden input -->
        <?php $single_lock = ($is_locked && count($allowed_kelas) === 1); ?>
        
        <div class="lg:col-span-2">
            <select name="<?= $single_lock ? '' : 'kelas' ?>" class="w-full border border-gray-300 p-3 rounded-xl outline-none focus:ring-2 focus:ring-blue-500 <?= $single_lock ? 'bg-gray-100 cursor-not-allowed' : 'bg-white' ?>" <?= $single_lock ? 'disabled' : '' ?>>
                <?php if (!$single_lock): ?>
                    <option value="">Semua Kelas</option>
                <?php endif; ?>
                
                <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k ?>" <?= $selected_kelas == $k ? 'selected' : '' ?>>
                        <?= $k ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <?php if ($single_lock): ?>
                <input type="hidden" name="kelas" value="<?= htmlspecialchars($selected_kelas) ?>">
            <?php endif; ?>
        </div>

        <div class="lg:col-span-2">
            <select name="mapel_id" class="w-full border border-gray-300 p-3 rounded-xl outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <option value="0">Semua Mapel</option>
                <?php
                $mapel->data_seek(0);
                while($m = $mapel->fetch_assoc()):
                ?>
                    <option value="<?= $m['id'] ?>" <?= $selected_mapel_id == $m['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['nama_mapel']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <button class="bg-blue-600 hover:bg-blue-700 text-white font-medium p-3 rounded-xl shadow-sm transition active:scale-95">
            Tampilkan
        </button>

    </form>
</div>

<!-- STATISTIK -->
<div class="grid md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white p-5 rounded-xl shadow print:border print:shadow-none">
        <p class="text-gray-500 text-sm font-medium">Total Siswa Aktif</p>
        <h2 class="text-2xl font-bold text-gray-800 mt-1"><?= $total_siswa ?></h2>
    </div>

    <div class="bg-white p-5 rounded-xl shadow print:border print:shadow-none">
        <p class="text-gray-500 text-sm font-medium">Siswa Bernilai</p>
        <h2 class="text-2xl font-bold text-gray-800 mt-1"><?= $jumlah ?></h2>
    </div>

    <div class="bg-white p-5 rounded-xl shadow print:border print:shadow-none">
        <p class="text-gray-500 text-sm font-medium">Rata-rata Keseluruhan</p>
        <h2 class="text-2xl font-bold text-blue-600 mt-1"><?= number_format($overall_average, 1) ?></h2>
    </div>
</div>

<!-- TABLE -->
<div class="bg-white p-6 rounded-2xl shadow overflow-x-auto print:shadow-none print:p-0">

<table class="min-w-full text-sm">
<thead class="bg-gray-800 text-white">
<tr>
    <th class="px-3 py-3 text-left rounded-tl-lg print:rounded-none">No</th>
    <th class="px-3 py-3 text-left">NIS</th>
    <th class="px-3 py-3 text-left">Nama</th>
    <th class="px-3 py-3 text-left">Kelas</th>
    <th class="px-3 py-3 text-left">Mapel</th>
    <th class="px-3 py-3 text-center">S1</th>
    <th class="px-3 py-3 text-center">S2</th>
    <th class="px-3 py-3 text-center">S3</th>
    <th class="px-3 py-3 text-center">S4</th>
    <th class="px-3 py-3 text-center">S5</th>
    <th class="px-3 py-3 text-center">S6</th>
    <th class="px-3 py-3 text-center">Ujian</th>
    <th class="px-3 py-3 text-center rounded-tr-lg print:rounded-none">Rata-rata</th>
</tr>
</thead>

<tbody>

<?php 
if (empty($data)): 
?>
<tr>
    <td colspan="13" class="text-center py-8 text-gray-500 border-b border-gray-100">
        <div class="text-3xl mb-2">📄</div>
        Tidak ada data untuk ditampilkan.
    </td>
</tr>
<?php 
else:
    $no=1; 
    foreach($data as $d):  
        $r = (
            (
                ($d['s1'] + $d['s2'] + $d['s3'] + $d['s4'] + $d['s5'] + $d['s6']) / 6
            ) * $sem
        ) + (
            $d['nilai_ujian'] * $uj
        );
?>

<tr class="border-b border-gray-100 hover:bg-gray-50 transition">
    <td class="px-3 py-3"><?= $no++ ?></td>
    <td class="px-3 py-3"><?= htmlspecialchars($d['nis']) ?></td>
    <td class="px-3 py-3 font-medium text-gray-800"><?= htmlspecialchars($d['nama']) ?></td>
    <td class="px-3 py-3 text-gray-600"><?= htmlspecialchars($d['kelas']) ?></td>
    <td class="px-3 py-3 text-gray-600"><?= htmlspecialchars($d['nama_mapel'] ?? '-') ?></td>
    <td class="px-3 py-3 text-center"><?= $d['s1'] ?></td>
    <td class="px-3 py-3 text-center"><?= $d['s2'] ?></td>
    <td class="px-3 py-3 text-center"><?= $d['s3'] ?></td>
    <td class="px-3 py-3 text-center"><?= $d['s4'] ?></td>
    <td class="px-3 py-3 text-center"><?= $d['s5'] ?></td>
    <td class="px-3 py-3 text-center"><?= $d['s6'] ?></td>
    <td class="px-3 py-3 text-center"><?= $d['nilai_ujian'] ?></td>
    <td class="px-3 py-3 text-center font-bold text-blue-600"><?= number_format($r, 2) ?></td>
</tr>

<?php 
    endforeach; 
endif;
?>

</tbody>
</table>

</div>
</div>

</body>
</html>