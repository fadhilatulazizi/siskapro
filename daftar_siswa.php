<?php
// =========================================================================
// INISIALISASI KONEKSI & AUTENTIKASI
// =========================================================================
include 'koneksi.php';
include 'auth.php';

if (function_exists('requireLogin')) {
    requireLogin();
}

// =========================================================================
// HANDLE BULK ACTIONS (POST REQUEST)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $ids = $_POST['ids'] ?? [];

    if (empty($ids) || !is_array($ids)) {
        echo json_encode(["status" => "error", "message" => "Tidak ada data yang dipilih."]);
        exit;
    }

    // Sanitasi ID menjadi integer
    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    if ($action === 'hapus') {
        $stmt = $conn->prepare("DELETE FROM siswa WHERE id IN ($placeholders)");
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Data siswa berhasil dihapus."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal menghapus data."]);
        }
        exit;
    } 
    elseif ($action === 'lulus') {
        $stmt = $conn->prepare("UPDATE siswa SET status = 'Lulus' WHERE id IN ($placeholders)");
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Status siswa berhasil diubah menjadi Lulus."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal memperbarui status."]);
        }
        exit;
    }    
    elseif ($action === 'keluar') {
        $stmt = $conn->prepare("UPDATE siswa SET status = 'Keluar' WHERE id IN ($placeholders)");
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Siswa Telah dikeluarkan."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal memperbarui status."]);
        }
        exit;
    }
    elseif ($action === 'naik') {
        $targetKelas = $_POST['target_kelas'] ?? '';
        if (empty($targetKelas)) {
            echo json_encode(["status" => "error", "message" => "Pilih kelas tujuan terlebih dahulu."]);
            exit;
        }
        // Bind parameter: target kelas (string) + list ID (integers)
        $types = 's' . str_repeat('i', count($ids));
        $params = array_merge([$targetKelas], $ids);
        
        $stmt = $conn->prepare("UPDATE siswa SET kelas = ? WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Siswa berhasil dipindahkan ke kelas $targetKelas."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal menaikkan kelas."]);
        }
        exit;
    }
    exit;
}

// =========================================================================
// HANDLE AJAX / API REQUEST (PENCARIAN & FILTER REALTIME)
// =========================================================================
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    
    $search = $_GET['search'] ?? '';
    $kelas  = $_GET['kelas'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $query = "SELECT * FROM siswa WHERE 1=1";
    $params = [];
    $types = "";

    // LOGIKA PERBAIKAN: Hak Akses Kelas (Mendukung Multi-Kelas untuk BK)
    if (!empty($_SESSION['kelas'])) {
        $allowed_kelas = array_map('trim', explode(',', $_SESSION['kelas']));
        
        // Jika user memfilter kelas dan kelas itu ada di dalam hak aksesnya
        if (!empty($kelas) && in_array($kelas, $allowed_kelas)) {
            $query .= " AND kelas = ?";
            $params[] = $kelas;
            $types .= "s";
        } else {
            // Tampilkan semua kelas yang menjadi hak aksesnya menggunakan IN (...)
            $placeholders = implode(',', array_fill(0, count($allowed_kelas), '?'));
            $query .= " AND kelas IN ($placeholders)";
            foreach ($allowed_kelas as $k) {
                $params[] = $k;
                $types .= "s";
            }
        }
    } elseif (!empty($kelas)) {
        // Logika untuk Admin (Session kelas kosong)
        $query .= " AND kelas = ?";
        $params[] = $kelas;
        $types .= "s";
    }

    if (!empty($status)) {
        $query .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }

    if (!empty($search)) {
        $query .= " AND (nama LIKE ? OR nis LIKE ? OR nisn LIKE ?)";
        $keyword = "%{$search}%";
        array_push($params, $keyword, $keyword, $keyword);
        $types .= "sss";
    }

    $query .= " ORDER BY kelas, nama";

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $siswaList = [];
    while ($row = $result->fetch_assoc()) {
        $siswaList[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "total" => count($siswaList),
        "data" => $siswaList
    ]);
    exit;
}

// =========================================================================
// DATA QUERY UTAMA UNTUK RENDER HALAMAN PERTAMA KALI
// =========================================================================
$search = $_GET['search'] ?? '';
$filterKelas = $_GET['filterKelas'] ?? '';
$filterStatus = $_GET['filterStatus'] ?? '';

$siswaList = [];
$query = "SELECT * FROM siswa WHERE 1=1";
$params = [];
$types = "";

// LOGIKA PERBAIKAN: Hak Akses Kelas untuk Render Awal
if (!empty($_SESSION['kelas'])) {
    $allowed_kelas = array_map('trim', explode(',', $_SESSION['kelas']));
    
    if (!empty($filterKelas) && in_array($filterKelas, $allowed_kelas)) {
        $query .= " AND kelas = ?";
        $params[] = $filterKelas;
        $types .= "s";
    } else {
        $placeholders = implode(',', array_fill(0, count($allowed_kelas), '?'));
        $query .= " AND kelas IN ($placeholders)";
        foreach ($allowed_kelas as $k) {
            $params[] = $k;
            $types .= "s";
        }
    }
} elseif (!empty($filterKelas)) {
    $query .= " AND kelas = ?";
    $params[] = $filterKelas;
    $types .= "s";
}

if (!empty($filterStatus)) {
    $query .= " AND status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (nama LIKE ? OR nis LIKE ? OR nisn LIKE ?)";
    $keyword = "%{$search}%";
    array_push($params, $keyword, $keyword, $keyword);
    $types .= "sss";
}

$query .= " ORDER BY kelas, nama";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $siswaList[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Siswa - Sistem Akademik</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
::-webkit-scrollbar{width:8px;height:8px}
::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:20px}
body{background:#f8fafc}
</style>
</head>
<body>

<?php include 'nav.php'; ?>

<!-- CONTENT -->
<div class="max-w-7xl mx-auto px-6 py-8">

    <!-- HEADER -->
    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-6 mb-8">
        <div>
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Data Siswa</h2>
            <p class="text-gray-500 mt-1">Kelola seluruh data siswa, status, dan kenaikan kelas dalam satu tampilan interaktif.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="add-siswa.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-5 py-2.5 rounded-xl shadow-sm shadow-indigo-100 transition active:scale-95 inline-flex items-center justify-center">
                + Tambah Siswa
            </a>
            <a href="nilai.php" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-5 py-2.5 rounded-xl shadow-sm shadow-emerald-100 transition active:scale-95 inline-flex items-center justify-center">
                Import / Export Excel
            </a>
        </div>
    </div>

    <!-- STATISTIK -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 relative overflow-hidden">
            <div class="text-gray-500 text-sm font-medium">Total Siswa</div>
            <div id="totalSiswa" class="text-4xl font-extrabold text-indigo-600 mt-2"><?php echo count($siswaList); ?></div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 relative overflow-hidden">
            <div class="text-gray-500 text-sm font-medium">Aktif</div>
            <div id="totalAktif" class="text-4xl font-extrabold text-emerald-600 mt-2">
                <?php echo count(array_filter($siswaList, fn($s) => ($s['status'] ?? 'Aktif') == 'Aktif')); ?>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 relative overflow-hidden">
            <div class="text-gray-500 text-sm font-medium">Lulus</div>
            <div id="totalLulus" class="text-4xl font-extrabold text-blue-600 mt-2">
                <?php echo count(array_filter($siswaList, fn($s) => ($s['status'] ?? '') == 'Lulus')); ?>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 relative overflow-hidden">
            <div class="text-gray-500 text-sm font-medium">Keluar</div>
            <div id="totalKeluar" class="text-4xl font-extrabold text-rose-500 mt-2">
                <?php echo count(array_filter($siswaList, fn($s) => ($s['status'] ?? '') == 'Keluar')); ?>
            </div>
        </div>
    </div>

    <!-- TOOLBAR -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
        <div class="grid lg:grid-cols-5 gap-4">
            <div class="lg:col-span-2 relative">
                <svg class="absolute left-3.5 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.5-4.5m2-5A7.5 7.5 0 1 1 3 11.5a7.5 7.5 0 0 1 15 0z"/>
                </svg>
                <input id="search" type="text" placeholder="Cari nama, NIS atau NISN..." class="w-full border border-slate-200 rounded-xl py-2.5 pl-11 pr-4 text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            
            <!-- DROPDOWN DINAMIS SESUAI HAK AKSES -->
            <select id="filterKelas" class="border border-slate-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                <option value="">Semua Kelas</option>
                <?php
                if (!empty($_SESSION['kelas'])) {
                    // Jika BK atau Wali Kelas, tampilkan HANYA kelas yang dipegang
                    $opsi_kelas = array_map('trim', explode(',', $_SESSION['kelas']));
                } else {
                    // Jika Admin, tampilkan semua opsi kelas
                    $opsi_kelas = ['Belum ada kelas', '7 A', '7 B', '7 C', '7 D', '7 E', '7 F', '7 G', '8 A', '8 B', '8 C', '8 D', '8 E', '8 F', '8 G', '9 A', '9 B', '9 C', '9 D', '9 E', '9 F', '9 G'];
                }
                
                foreach ($opsi_kelas as $k) {
                    $selected = ($filterKelas === $k) ? 'selected' : '';
                    echo "<option value=\"$k\" $selected>$k</option>";
                }
                ?>
            </select>

            <select id="filterStatus" class="border border-slate-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                <option value="">Semua Status</option>
                <option>Aktif</option>
                <option>Lulus</option>
                <option>Keluar</option>
            </select>
        </div>
    </div>

    <!-- INFO & LOADING -->
    <div class="flex justify-between items-center mb-4 px-2">
        <div class="flex items-center gap-3">
            <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-gray-700 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-sm hover:bg-slate-50 transition">
                <input type="checkbox" id="selectAll" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 cursor-pointer">
                <span>Pilih Semua</span>
            </label>
            <div class="text-gray-500 text-sm font-medium">Menampilkan <span id="jumlahData" class="font-bold text-gray-800"><?php echo count($siswaList); ?></span> siswa</div>
        </div>
        <div id="loading" class="hidden text-indigo-600 font-semibold text-sm animate-pulse flex items-center gap-2">
            <svg class="animate-spin h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Memuat data...
        </div>
    </div>

    <!-- GRID CARD CONTAINER -->
    <div id="tableContainer" class="space-y-4">
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5" id="tableBody">
            <?php if (empty($siswaList)): ?>
                <div class="col-span-full py-16 text-center bg-white rounded-2xl border border-slate-100 shadow-sm">
                    <div class="text-5xl mb-3">📚</div>
                    <div class="text-lg font-bold text-gray-800">Data Tidak Ditemukan</div>
                    <div class="text-sm text-gray-400 mt-1">Coba ubah kata kunci atau filter pencarian Anda.</div>
                </div>
            <?php else: ?>
                <?php foreach ($siswaList as $row): 
                    $statusVal = $row['status'] ?? 'Aktif';
                    $statusColor = match($statusVal) {
                        'Aktif' => 'bg-emerald-50 text-emerald-700 border border-emerald-200/60',
                        'Lulus' => 'bg-blue-50 text-blue-700 border border-blue-200/60',
                        default => 'bg-rose-50 text-rose-700 border border-rose-200/60'
                    };
                    $siswaId = $row['id'] ?? '';
                ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 hover:shadow-md transition duration-200 flex flex-col justify-between group">
                    <div>
                        <div class="flex justify-between items-start mb-4">
                            <input type="checkbox" data-id="<?php echo $siswaId; ?>" class="rowCheck w-4 h-4 rounded cursor-pointer mt-1 text-indigo-600 focus:ring-indigo-500 border-slate-300">
                            <span class="px-3 py-1 rounded-full <?php echo $statusColor; ?> text-xs font-semibold">
                                <?php echo htmlspecialchars($statusVal); ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-3.5 mb-4">
                            <img src="https://ui-avatars.com/api/?background=4f46e5&color=fff&name=<?php echo urlencode($row['nama']); ?>" class="w-12 h-12 rounded-full object-cover shadow-sm">
                            <div class="overflow-hidden">
                                <h3 class="font-bold text-gray-800 text-base truncate"><?php echo htmlspecialchars($row['nama']); ?></h3>
                                <p class="text-xs text-gray-400 truncate"><?php echo htmlspecialchars($row['email'] ?? 'email@domain.com'); ?></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 py-3 border-y border-slate-100 text-center mb-4 bg-slate-50/50 rounded-xl">
                            <div>
                                <div class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">NIS</div>
                                <div class="text-xs font-bold text-gray-700 mt-0.5 truncate px-1"><?php echo htmlspecialchars($row['nis']); ?></div>
                            </div>
                            <div>
                                <div class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">NISN</div>
                                <div class="text-xs font-bold text-gray-700 mt-0.5 truncate px-1"><?php echo htmlspecialchars($row['nisn']); ?></div>
                            </div>
                            <div>
                                <div class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Kelas</div>
                                <div class="text-xs font-bold text-indigo-600 mt-0.5 truncate px-1"><?php echo htmlspecialchars($row['kelas']); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- DROPDOWN MENU LIHAT DETAIL -->
                    <div class="relative">
                        <button onclick="toggleDropdown(event, 'dropdown-<?php echo $siswaId; ?>')" class="w-full bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium py-2.5 px-4 rounded-xl transition shadow-sm active:scale-95 flex items-center justify-between">
                            <span>Lihat Detail</span>
                            <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        
                        <div id="dropdown-<?php echo $siswaId; ?>" class="dropdown-menu hidden absolute bottom-full left-0 right-0 mb-2 bg-white rounded-xl shadow-xl border border-slate-100 py-1.5 z-20">
                            <a href="print_transkrip.php?ids=<?php echo $siswaId; ?>" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-slate-50 transition">
                                📖 Transkrip
                            </a>
                             <a href="print_skl.php?ids=<?php echo $siswaId; ?>" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-slate-50 transition">
                                📖 SKL
                            </a>
                             <a href="print_sknr.php?ids=<?php echo $siswaId; ?>" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-slate-50 transition">
                                📖 SKNR
                            </a>
                            <a href="edit-siswa.php?id=<?php echo $siswaId; ?>" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-indigo-600 hover:bg-indigo-50 transition">
                                ✏️ Edit Data Siswa
                            </a>
                            <div class="border-t border-slate-100 my-1"></div>
                            <button onclick="hapusSiswaSingle(<?php echo $siswaId; ?>)" class="w-full text-left flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 transition">
                                🗑️ Hapus Siswa
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- BULK ACTION BAR -->
    <div id="bulkBar" class="hidden mt-6 border border-slate-200 bg-white rounded-2xl shadow-sm px-6 py-4 flex flex-col lg:flex-row justify-between items-center gap-4 transition-all">
        <div class="font-medium text-gray-700 text-sm">
            <span id="selectedCount" class="font-bold text-indigo-600">0</span> siswa dipilih
        </div>
        <div class="flex flex-wrap gap-3 items-center">
            <select id="bulkClass" class="border border-slate-200 rounded-lg px-4 py-2 text-sm outline-none bg-white">
                <option value="">Naik ke Kelas...</option> <option>Belum ada kelas</option>
                <option>7 A</option><option>7 B</option><option>7 C</option><option>7 D</option><option>7 E</option><option>7 F</option><option>7 G</option>
                <option>8 A</option><option>8 B</option><option>8 C</option><option>8 D</option><option>8 E</option><option>8 F</option><option>8 G</option>
                <option>9 A</option><option>9 B</option><option>9 C</option><option>9 D</option><option>9 E</option><option>9 F</option><option>9 G</option>
            </select>
            <button id="btnNaik" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg transition">Naik Kelas</button>
            <button id="btnLulus" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2 rounded-lg transition">Luluskan</button>
            <button id="btnHapus" class="bg-rose-600 hover:bg-rose-700 text-white text-sm px-4 py-2 rounded-lg transition">Hapus</button>
            <button id="btnKeluar" class="bg-rose-600 hover:bg-rose-700 text-white text-sm px-4 py-2 rounded-lg transition">Keluarkan</button>
        </div>
    </div>

</div>



<!-- JAVASCRIPT & AJAX CONTROLLER -->
<script>
const searchInput = document.getElementById("search");
const filterKelas = document.getElementById("filterKelas");
const filterStatus = document.getElementById("filterStatus");
const loading = document.getElementById("loading");
const tableBody = document.getElementById("tableBody");
const jumlahData = document.getElementById("jumlahData");
const bulkBar = document.getElementById("bulkBar");
const selectedCount = document.getElementById("selectedCount");
const selectAllCheckbox = document.getElementById("selectAll");

let timer;

function toggleDropdown(event, id) {
    event.stopPropagation();
    const dropdowns = document.querySelectorAll('.dropdown-menu');
    dropdowns.forEach(d => {
        if (d.id !== id) d.classList.add('hidden');
    });
    const target = document.getElementById(id);
    if (target) {
        target.classList.toggle('hidden');
    }
}

window.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu').forEach(d => d.classList.add('hidden'));
});

function fetchData() {
    loading.classList.remove("hidden");
    const query = searchInput.value;
    const kelas = filterKelas.value;
    const status = filterStatus.value;

    fetch(`?ajax=1&search=${encodeURIComponent(query)}&kelas=${encodeURIComponent(kelas)}&status=${encodeURIComponent(status)}`)
        .then(response => response.json())
        .then(res => {
            loading.classList.add("hidden");
            jumlahData.innerText = res.total;
            
            if (res.data.length === 0) {
                tableBody.innerHTML = `
                    <div class="col-span-full py-16 text-center bg-white rounded-2xl border border-slate-100 shadow-sm">
                        <div class="text-5xl mb-3">📚</div>
                        <div class="text-lg font-bold text-gray-800">Data Tidak Ditemukan</div>
                        <div class="text-sm text-gray-400 mt-1">Coba ubah kata kunci atau filter pencarian Anda.</div>
                    </div>
                `;
                bulkBar.classList.add("hidden");
                return;
            }

            let html = "";
            res.data.forEach(row => {
                let statusVal = row.status || 'Aktif';
                let statusColor = statusVal === 'Aktif' 
                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' 
                    : (statusVal === 'Lulus' ? 'bg-blue-50 text-blue-700 border border-blue-200/60' : 'bg-rose-50 text-rose-700 border border-rose-200/60');
                let siswaId = row.id || '';
                
                html += `
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 hover:shadow-md transition duration-200 flex flex-col justify-between group">
                    <div>
                        <div class="flex justify-between items-start mb-4">
                            <input type="checkbox" data-id="${siswaId}" class="rowCheck w-4 h-4 rounded cursor-pointer mt-1 text-indigo-600 focus:ring-indigo-500 border-slate-300">
                            <span class="px-3 py-1 rounded-full ${statusColor} text-xs font-semibold">${statusVal}</span>
                        </div>
                        <div class="flex items-center gap-3.5 mb-4">
                            <img src="https://ui-avatars.com/api/?background=4f46e5&color=fff&name=${encodeURIComponent(row.nama)}" class="w-12 h-12 rounded-full object-cover shadow-sm">
                            <div class="overflow-hidden">
                                <h3 class="font-bold text-gray-800 text-base truncate">${row.nama}</h3>
                                <p class="text-xs text-gray-400 truncate">${row.email || 'email@domain.com'}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 py-3 border-y border-slate-100 text-center mb-4 bg-slate-50/50 rounded-xl">
                            <div>
                                <div class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">NIS</div>
                                <div class="text-xs font-bold text-gray-700 mt-0.5 truncate px-1">${row.nis}</div>
                            </div>
                            <div>
                                <div class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">NISN</div>
                                <div class="text-xs font-bold text-gray-700 mt-0.5 truncate px-1">${row.nisn}</div>
                            </div>
                            <div>
                                <div class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Kelas</div>
                                <div class="text-xs font-bold text-indigo-600 mt-0.5 truncate px-1">${row.kelas}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="relative">
                        <button onclick="toggleDropdown(event, 'dropdown-${siswaId}')" class="w-full bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium py-2.5 px-4 rounded-xl transition shadow-sm active:scale-95 flex items-center justify-between">
                            <span>Lihat Detail</span>
                            <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="dropdown-${siswaId}" class="dropdown-menu hidden absolute bottom-full left-0 right-0 mb-2 bg-white rounded-xl shadow-xl border border-slate-100 py-1.5 z-20">
                           <a href="print_transkrip.php?ids=${siswaId}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-slate-50 transition">
                                📖 Transkrip
                            </a>
                            <a href="print_skl.php?ids=${siswaId}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-slate-50 transition">
                                📖 SKL
                            </a>
                            <a href="print_sknr.php?ids=${siswaId}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-slate-50 transition">
                                📖 SKNR
                            </a>
                            <a href="edit-siswa.php?id=${siswaId}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-indigo-600 hover:bg-indigo-50 transition">
                                ✏️ Edit Data Siswa
                            </a>
                            <div class="border-t border-slate-100 my-1"></div>
                            <button onclick="hapusSiswaSingle(${siswaId})" class="w-full text-left flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 transition">
                                🗑️ Hapus Siswa
                            </button>
                        </div>
                    </div>
                </div>`;
            });
            tableBody.innerHTML = html;
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            updateSelected();
        })
        .catch(err => {
            loading.classList.add("hidden");
            console.error("Error fetching data:", err);
        });
}

searchInput.addEventListener("keyup", () => {
    clearTimeout(timer);
    timer = setTimeout(fetchData, 300);
});

filterKelas.addEventListener("change", fetchData);
filterStatus.addEventListener("change", fetchData);

selectAllCheckbox.addEventListener("change", function() {
    const rowCheckboxes = document.querySelectorAll(".rowCheck");
    rowCheckboxes.forEach(cb => {
        cb.checked = selectAllCheckbox.checked;
    });
    updateSelected();
});

function updateSelected() {
    const rowCheckboxes = document.querySelectorAll(".rowCheck");
    const checked = document.querySelectorAll(".rowCheck:checked");
    
    selectedCount.innerText = checked.length;
    
    if (rowCheckboxes.length > 0 && checked.length === rowCheckboxes.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else if (checked.length > 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    }

    if (checked.length > 0) {
        bulkBar.classList.remove("hidden");
    } else {
        bulkBar.classList.add("hidden");
    }
}

document.addEventListener("change", e => {
    if (e.target.classList.contains("rowCheck")) {
        updateSelected();
    }
});

// Eksekusi Aksi Massal via AJAX
function executeBulkAction(actionData) {
    const checkedBoxes = document.querySelectorAll(".rowCheck:checked");
    const ids = Array.from(checkedBoxes).map(cb => cb.getAttribute("data-id"));

    if (ids.length === 0) {
        alert("Pilih minimal satu siswa.");
        return;
    }

    const formData = new URLSearchParams();
    formData.append('action', actionData.action);
    if (actionData.target_kelas) {
        formData.append('target_kelas', actionData.target_kelas);
    }
    ids.forEach(id => formData.append('ids[]', id));

    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            alert(res.message);
            fetchData();
        } else {
            alert(res.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Terjadi kesalahan sistem.");
    });
}

// Tombol Aksi Massal
document.getElementById("btnHapus").onclick = () => {
    if (confirm("Yakin ingin menghapus siswa yang dipilih?")) {
        executeBulkAction({ action: 'hapus' });
    }
};

document.getElementById("btnLulus").onclick = () => {
    if (confirm("Ubah status siswa terpilih menjadi Lulus?")) {
        executeBulkAction({ action: 'lulus' });
    }
};

document.getElementById("btnNaik").onclick = () => {
    const targetKelas = document.getElementById("bulkClass").value;
    if (!targetKelas) {
        alert("Silakan pilih kelas tujuan terlebih dahulu pada dropdown di sebelah kiri tombol Naik Kelas!");
        return;
    }
    if (confirm(`Pindahkan siswa terpilih ke kelas ${targetKelas}?`)) {
        executeBulkAction({ action: 'naik', target_kelas: targetKelas });
    }
};

document.getElementById("btnKeluar").onclick = () => {
    if (confirm(`Siswa terpilih dikeluarkan`)) {
        executeBulkAction({ action: 'keluar' });
    }
};

// Hapus Single dari Dropdown
function hapusSiswaSingle(id) {
    if (confirm("Yakin ingin menghapus siswa ini?")) {
        const formData = new URLSearchParams();
        formData.append('action', 'hapus');
        formData.append('ids[]', id);

        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                alert(res.message);
                fetchData();
            } else {
                alert(res.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Terjadi kesalahan sistem.");
        });
    }
}
</script>
</body>
</html>