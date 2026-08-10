<?php
include 'koneksi.php';
include 'auth.php';
include 'csrf.php';

requireLogin();

require_once __DIR__ . '/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

set_time_limit(0);
ini_set('memory_limit', '512M');

$message = '';
$success_list = [];
$failed_list  = [];

// =========================
// GET KELAS LIST (Hanya siswa aktif)
// =========================
if (!empty($_SESSION['kelas'])) {
    $kelas_list = [$_SESSION['kelas']];
} else {
    $kelas_result = $conn->query("
        SELECT DISTINCT kelas 
        FROM siswa 
        WHERE status = 'Aktif' 
        ORDER BY kelas
    ");
    $kelas_list = [];
    while ($row = $kelas_result->fetch_assoc()) {
        $kelas_list[] = $row['kelas'];
    }
}

$active_tab = 'manual';
if (isset($_POST['import_excel'])) $active_tab = 'excel';
if (isset($_POST['tambah_siswa'])) $active_tab = 'manual';

// =========================
// PROSES TAMBAH SISWA MANUAL
// =========================
if (isset($_POST['tambah_siswa'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }

    $nis   = trim($_POST['nis'] ?? '');
    $nis   = preg_replace('/[^0-9]/', '', $nis);
    $nama  = trim($_POST['nama'] ?? '');
    $kelas = trim($_POST['kelas_siswa'] ?? '');

    if (empty($nis) || empty($nama) || empty($kelas)) {
        $message = "NIS, Nama, dan Kelas wajib diisi!";
    } else {
        $stmt_cek = $conn->prepare("SELECT id FROM siswa WHERE nis = ? LIMIT 1");
        $stmt_cek->bind_param("s", $nis);
        $stmt_cek->execute();
        $stmt_cek->store_result();

        if ($stmt_cek->num_rows > 0) {
            $message = "Gagal: Siswa dengan NIS $nis sudah terdaftar di database!";
        } else {
            $stmt_cek->close();

            $stmt_ins = $conn->prepare("INSERT INTO siswa (nis, nama, kelas, status) VALUES (?, ?, ?, 'Aktif')");
            $stmt_ins->bind_param("sss", $nis, $nama, $kelas);

            if ($stmt_ins->execute()) {
                $message = "Berhasil menambahkan siswa baru: $nama ($nis)";
                regenerateCsrfToken();
            } else {
                $message = "Gagal menyimpan data siswa ke database.";
            }
            $stmt_ins->close();
        }
    }
}

// =========================
// PROSES IMPORT BULK SISWA VIA EXCEL
// =========================
if (isset($_POST['import_excel'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }

    $active_tab = 'excel';

    if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
        $message = "File upload gagal";
    } else {
        $ext = strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION));

        if ($ext !== 'xlsx') {
            $message = "File harus format XLSX";
        } else {
            $xlsx = SimpleXLSX::parse($_FILES['file_excel']['tmp_name']);

            if (!$xlsx) {
                $message = "Gagal membaca XLSX: " . SimpleXLSX::parseError();
            } else {
                $rows = $xlsx->rows();
                $conn->begin_transaction();

                $success = 0;
                $failed = 0;

                try {
                    foreach ($rows as $i => $row) {
                        if ($i == 0) continue; // Lewati baris header

                        $nis   = trim($row[0] ?? '');
                        $nis   = preg_replace('/[^0-9]/', '', $nis);
                        $nama  = trim($row[1] ?? '');
                        $kelas = trim($row[2] ?? '');

                        if ($nis === '' || $nama === '' || $kelas === '') {
                            $failed++;
                            $failed_list[] = "Baris " . ($i + 1) . " - Data tidak lengkap (NIS/Nama/Kelas kosong)";
                            continue;
                        }

                        // Cek apakah NIS sudah ada
                        $stmt_cek = $conn->prepare("SELECT id FROM siswa WHERE nis = ? LIMIT 1");
                        $stmt_cek->bind_param("s", $nis);
                        $stmt_cek->execute();
                        $stmt_cek->store_result();

                        if ($stmt_cek->num_rows > 0) {
                            $failed++;
                            $failed_list[] = "$nis - $nama gagal: NIS sudah terdaftar";
                            $stmt_cek->close();
                            continue;
                        }
                        $stmt_cek->close();

                        // Insert siswa baru dengan status Aktif
                        $stmt_ins = $conn->prepare("INSERT INTO siswa (nis, nama, kelas, status) VALUES (?, ?,?, 'Aktif')");
                        $stmt_ins->bind_param("sss", $nis, $nama, $kelas);

                        if ($stmt_ins->execute()) {
                            $success++;
                            $success_list[] = "$nis - $nama berhasil ditambahkan";
                        } else {
                            $failed++;
                            $failed_list[] = "$nis - $nama gagal disimpan ke database";
                        }
                        $stmt_ins->close();
                    }

                    $conn->commit();
                    regenerateCsrfToken();
                    $message = "Import selesai. Berhasil: $success | Gagal: $failed";
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "ERROR: " . $e->getMessage();
                }
            }
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Data Siswa</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen pb-12">

<?php include 'nav.php'; ?>

<div class="max-w-3xl mx-auto py-10 px-4">
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
        
        <!-- HEADER -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Manajemen Data Siswa</h1>
            <p class="text-slate-500 text-sm mt-1">Tambah data siswa baru secara manual atau bulk import via file Excel</p>
        </div>

        <?php if($message): ?>
            <div class="mb-6 bg-indigo-50 border border-indigo-100 text-indigo-800 p-4 rounded-2xl text-sm font-medium">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- TAB NAVIGATION -->
        <div class="flex border-b border-slate-200 mb-6 gap-6">
            <button type="button" onclick="switchTab('manual')" id="btn-manual" class="pb-3 text-sm font-semibold border-b-2 transition <?= $active_tab == 'manual' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600' ?>">
                ✍️ Tambah Manual
            </button>
            <button type="button" onclick="switchTab('excel')" id="btn-excel" class="pb-3 text-sm font-semibold border-b-2 transition <?= $active_tab == 'excel' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600' ?>">
                📊 Import Excel (Bulk)
            </button>
        </div>

        <!-- FORM TAMBAH MANUAL -->
        <div id="tab-manual" class="<?= $active_tab == 'manual' ? '' : 'hidden' ?>">
            <form method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div>
                    <label class="block mb-2 text-sm font-semibold text-slate-700">NIS (Nomor Induk Siswa)</label>
                    <input type="text" name="nis" required placeholder="Contoh: 12345" class="w-full border border-slate-200 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 bg-slate-50/50">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-slate-700">Nama Lengkap Siswa</label>
                    <input type="text" name="nama" required placeholder="Masukkan nama lengkap" class="w-full border border-slate-200 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 bg-slate-50/50">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-slate-700">Kelas</label>
                        <select name="kelas_siswa" required class="w-full border border-slate-200 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 bg-slate-50/50">
                            <option value="">Pilih Kelas</option>
                            <?php foreach($kelas_list as $k): ?>
                                <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" name="tambah_siswa" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm px-6 py-3 rounded-2xl transition shadow-sm shadow-indigo-100">
                        Simpan Siswa Baru
                    </button>
                </div>
            </form>
        </div>

        <!-- FORM IMPORT EXCEL -->
        <div id="tab-excel" class="<?= $active_tab == 'excel' ? '' : 'hidden' ?>">
            <form method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-xs text-slate-600 space-y-1">
                    <p class="font-semibold text-slate-700">Format Kolom Excel (.xlsx):</p>
                    <p>Baris ke-1 adalah Header dengan urutan kolom:</p>
                    <p class="font-mono text-indigo-600">Kolom A: NIS | Kolom B: NAMA | Kolom C: KELAS | Kolom D: JENIS KELAMIN (L/P)</p>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-slate-700">File Excel (.xlsx)</label>
                    <input type="file" name="file_excel" accept=".xlsx" required class="w-full border border-slate-200 rounded-2xl px-4 py-2.5 text-sm bg-slate-50/50 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" name="import_excel" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm px-6 py-3 rounded-2xl transition shadow-sm shadow-indigo-100">
                        Proses Import Excel
                    </button>
                </div>
            </form>

            <?php if(!empty($success_list)): ?>
            <div class="mt-6 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl">
                <h3 class="font-bold text-emerald-700 text-sm mb-2">✔ Berhasil Ditambahkan (<?= count($success_list) ?>)</h3>
                <ul class="text-xs text-emerald-800 space-y-1 max-h-40 overflow-y-auto">
                    <?php foreach($success_list as $s): ?>
                        <li><?= htmlspecialchars($s) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if(!empty($failed_list)): ?>
            <div class="mt-6 p-4 bg-rose-50 border border-rose-100 rounded-2xl">
                <h3 class="font-bold text-rose-700 text-sm mb-2">✖ Gagal Ditambahkan (<?= count($failed_list) ?>)</h3>
                <ul class="text-xs text-rose-800 space-y-1 max-h-40 overflow-y-auto">
                    <?php foreach($failed_list as $f): ?>
                        <li><?= htmlspecialchars($f) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
function switchTab(tab) {
    document.getElementById('tab-manual').classList.add('hidden');
    document.getElementById('tab-excel').classList.add('hidden');
    
    document.getElementById('btn-manual').className = "pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-400 hover:text-slate-600 transition";
    document.getElementById('btn-excel').className = "pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-400 hover:text-slate-600 transition";

    document.getElementById('tab-' + tab).classList.remove('hidden');
    document.getElementById('btn-' + tab).className = "pb-3 text-sm font-semibold border-b-2 border-indigo-600 text-indigo-600 transition";
}
</script>

</body>
</html>