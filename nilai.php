<?php
include 'koneksi.php';
include 'auth.php';
include 'csrf.php';

requireLogin();

require_once __DIR__ . '/SimpleXLSX.php';
require_once __DIR__ . '/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

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

// =========================
// GET MAPEL LIST
// =========================
$mapel = $conn->query("SELECT * FROM mapel ORDER BY nama_mapel ASC");

// =========================
// PROSES EXPORT EXCEL (Hanya siswa aktif)
// =========================
if (isset($_POST['export'])) {
    $selected_kelas = trim($_POST['kelas'] ?? '');
    $selected_mapel_id = (int)($_POST['mapel_id'] ?? 0);

    if (empty($selected_kelas) || $selected_mapel_id <= 0) {
        die("Data tidak lengkap");
    }

    if (!empty($_SESSION['kelas']) && $_SESSION['kelas'] !== $selected_kelas) {
        die("Anda tidak memiliki akses ke kelas ini");
    }

    $stmt_mapel = $conn->prepare("SELECT nama_mapel FROM mapel WHERE id = ? LIMIT 1");
    $stmt_mapel->bind_param("i", $selected_mapel_id);
    $stmt_mapel->execute();
    $mapel_data = $stmt_mapel->get_result()->fetch_assoc();
    $stmt_mapel->close();

    if (!$mapel_data) {
        die("Mapel tidak ditemukan");
    }

    $mapel_name = $mapel_data['nama_mapel'];

    $stmt = $conn->prepare("
        SELECT 
            s.nis, s.nama, s.kelas,
            COALESCE(n.s1, 0) AS s1,
            COALESCE(n.s2, 0) AS s2,
            COALESCE(n.s3, 0) AS s3,
            COALESCE(n.s4, 0) AS s4,
            COALESCE(n.s5, 0) AS s5,
            COALESCE(n.s6, 0) AS s6,
            COALESCE(n.nilai_ujian, 0) AS nilai_ujian
        FROM siswa s
        LEFT JOIN nilai n ON s.id = n.siswa_id AND n.mapel_id = ?
        WHERE s.kelas = ? AND s.status = 'Aktif'
        ORDER BY s.nama ASC
    ");
    $stmt->bind_param("is", $selected_mapel_id, $selected_kelas);
    $stmt->execute();
    $students = $stmt->get_result();

    $data = [];
    $data[] = ['NIS', 'NAMA', 'KELAS', 'S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'UJIAN'];

    while ($row = $students->fetch_assoc()) {
        $data[] = [
            "\0" . $row['nis'],
            $row['nama'],
            "\0" . $row['kelas'],
            (int)$row['s1'],
            (int)$row['s2'],
            (int)$row['s3'],
            (int)$row['s4'],
            (int)$row['s5'],
            (int)$row['s6'],
            (int)$row['nilai_ujian']
        ];
    }

    $safe_mapel = preg_replace('/[^A-Za-z0-9\-]/', '_', strtolower($mapel_name));
    $filename = 'nilai_' . $selected_kelas . '_' . $safe_mapel . '.xlsx';

    SimpleXLSXGen::fromArray($data)->downloadAs($filename);
    exit;
}

// =========================
// PROSES IMPORT EXCEL (Hanya siswa aktif)
// =========================
if (isset($_POST['import'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }

    if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
        $message = "File upload gagal";
    } else {
        $ext = strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION));

        if ($ext !== 'xlsx') {
            $message = "File harus format XLSX";
        } else {
            $mapel_id = (int)($_POST['mapel_id'] ?? 0);

            if ($mapel_id <= 0) {
                $message = "Pilih mata pelajaran terlebih dahulu";
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
                            if ($i == 0) continue;

                            $nis = trim($row[0] ?? '');
                            $nis = preg_replace('/[^0-9]/', '', $nis);

                            if ($nis === '') {
                                $failed++;
                                $failed_list[] = "Baris " . ($i + 1) . " - NIS kosong";
                                continue;
                            }

                            // Cek hanya siswa dengan status Aktif
                            $stmt = $conn->prepare("SELECT id, nama FROM siswa WHERE nis = ? AND status = 'Aktif' LIMIT 1");
                            $stmt->bind_param("s", $nis);
                            $stmt->execute();
                            $siswa = $stmt->get_result()->fetch_assoc();
                            $stmt->close();

                            if (!$siswa) {
                                $failed++;
                                $failed_list[] = "$nis - siswa tidak ditemukan atau tidak aktif";
                                continue;
                            }

                            $siswa_id = $siswa['id'];
                            $nama = $siswa['nama'];

                            $s1 = (int)($row[3] ?? 0);
                            $s2 = (int)($row[4] ?? 0);
                            $s3 = (int)($row[5] ?? 0);
                            $s4 = (int)($row[6] ?? 0);
                            $s5 = (int)($row[7] ?? 0);
                            $s6 = (int)($row[8] ?? 0);
                            $ujian = (int)($row[9] ?? 0);

                            $grades = [$s1, $s2, $s3, $s4, $s5, $s6, $ujian];
                            $invalid = false;
                            $error_detail = '';

                            foreach ($grades as $key => $g) {
                                if ($g < 0 || $g > 100) {
                                    $invalid = true;
                                    $map = [0 => 'S1', 1 => 'S2', 2 => 'S3', 3 => 'S4', 4 => 'S5', 5 => 'S6', 6 => 'Ujian'];
                                    $kolom = $map[$key];
                                    $error_detail = "$kolom = $g (maks 100)";
                                    break;
                                }
                            }

                            if ($invalid) {
                                $failed++;
                                $failed_list[] = "$nis - $nama gagal: $error_detail";
                                continue;
                            }

                            $stmt = $conn->prepare("
                                INSERT INTO nilai (siswa_id, mapel_id, s1, s2, s3, s4, s5, s6, nilai_ujian)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE
                                    s1=VALUES(s1), s2=VALUES(s2), s3=VALUES(s3), s4=VALUES(s4), s5=VALUES(s5), s6=VALUES(s6), nilai_ujian=VALUES(nilai_ujian)
                            ");
                            $stmt->bind_param("iiiiiiiii", $siswa_id, $mapel_id, $s1, $s2, $s3, $s4, $s5, $s6, $ujian);

                            if ($stmt->execute()) {
                                $success++;
                                $success_list[] = "$nis - $nama berhasil diupdate";
                            } else {
                                $failed++;
                                $failed_list[] = "$nis - $nama gagal DB";
                            }
                            $stmt->close();
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
}

$csrfToken = generateCsrfToken();
$active_tab = isset($_POST['export']) ? 'export' : 'import';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Import & Export Nilai</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen pb-12">

<?php include 'nav.php'; ?>

<div class="max-w-4xl mx-auto py-10 px-4">
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
        
        <!-- HEADER -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Manajemen Data Nilai Excel</h1>
            <p class="text-slate-500 text-sm mt-1">Import dan export data nilai khusus untuk siswa dengan status aktif</p>
        </div>

        <?php if($message && $active_tab == 'import'): ?>
            <div class="mb-6 bg-indigo-50 border border-indigo-100 text-indigo-800 p-4 rounded-2xl text-sm font-medium">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- TAB NAVIGATION -->
        <div class="flex border-b border-slate-200 mb-6 gap-6">
            <button onclick="switchTab('import')" id="btn-import" class="pb-3 text-sm font-semibold border-b-2 transition <?= $active_tab == 'import' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600' ?>">
                📥 Import Nilai
            </button>
            <button onclick="switchTab('export')" id="btn-export" class="pb-3 text-sm font-semibold border-b-2 transition <?= $active_tab == 'export' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600' ?>">
                📤 Export Nilai
            </button>
        </div>

        <!-- FORM IMPORT -->
        <div id="tab-import" class="<?= $active_tab == 'import' ? '' : 'hidden' ?>">
            <form method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div>
                    <label class="block mb-2 text-sm font-semibold text-slate-700">Mata Pelajaran</label>
                    <select name="mapel_id" required class="w-full border border-slate-200 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 bg-slate-50/50">
                        <option value="0">Pilih Mata Pelajaran</option>
                        <?php 
                        $mapel->data_seek(0);
                        while($m = $mapel->fetch_assoc()): 
                        ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_mapel']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-slate-700">File Excel (.xlsx)</label>
                    <input type="file" name="file_excel" accept=".xlsx" required class="w-full border border-slate-200 rounded-2xl px-4 py-2.5 text-sm bg-slate-50/50 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" name="import" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm px-6 py-3 rounded-2xl transition shadow-sm shadow-indigo-100">
                        Proses Import
                    </button>
                </div>
            </form>

            <?php if(!empty($success_list)): ?>
            <div class="mt-6 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl">
                <h3 class="font-bold text-emerald-700 text-sm mb-2">✔ Berhasil Diupdate (<?= count($success_list) ?>)</h3>
                <ul class="text-xs text-emerald-800 space-y-1 max-h-40 overflow-y-auto">
                    <?php foreach($success_list as $s): ?>
                        <li><?= htmlspecialchars($s) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if(!empty($failed_list)): ?>
            <div class="mt-6 p-4 bg-rose-50 border border-rose-100 rounded-2xl">
                <h3 class="font-bold text-rose-700 text-sm mb-2">✖ Gagal Import (<?= count($failed_list) ?>)</h3>
                <ul class="text-xs text-rose-800 space-y-1 max-h-40 overflow-y-auto">
                    <?php foreach($failed_list as $f): ?>
                        <li><?= htmlspecialchars($f) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- FORM EXPORT -->
        <div id="tab-export" class="<?= $active_tab == 'export' ? '' : 'hidden' ?>">
            <form method="POST" class="space-y-5">
                <div>
                    <label class="block mb-2 text-sm font-semibold text-slate-700">Kelas</label>
                    <select name="kelas" required class="w-full border border-slate-200 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 bg-slate-50/50">
                        <option value="">Pilih Kelas</option>
                        <?php foreach($kelas_list as $k): ?>
                            <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-slate-700">Mata Pelajaran</label>
                    <select name="mapel_id" required class="w-full border border-slate-200 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 bg-slate-50/50">
                        <option value="">Pilih Mata Pelajaran</option>
                        <?php 
                        $mapel->data_seek(0);
                        while($m = $mapel->fetch_assoc()): 
                        ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_mapel']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" name="export" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm px-6 py-3 rounded-2xl transition shadow-sm shadow-emerald-100">
                        Download Excel (.xlsx)
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
function switchTab(tab) {
    document.getElementById('tab-import').classList.add('hidden');
    document.getElementById('tab-export').classList.add('hidden');
    document.getElementById('btn-import').className = "pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-400 hover:text-slate-600 transition";
    document.getElementById('btn-export').className = "pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-400 hover:text-slate-600 transition";

    document.getElementById('tab-' + tab).classList.remove('hidden');
    document.getElementById('btn-' + tab).className = "pb-3 text-sm font-semibold border-b-2 border-indigo-600 text-indigo-600 transition";
}
</script>

</body>
</html>