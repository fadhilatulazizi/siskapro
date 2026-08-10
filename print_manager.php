<?php
include 'koneksi.php';
include 'auth.php';

requireLogin();

// =========================
// LIST KELAS (Hanya Siswa Aktif)
// =========================
if (!empty($_SESSION['kelas'])) {
    $kelas_list = [$_SESSION['kelas']];
} else {
    $kelas_result = $conn->query("SELECT DISTINCT kelas FROM siswa WHERE status = 'Aktif' ORDER BY kelas");
    $kelas_list = [];
    while ($row = $kelas_result->fetch_assoc()) {
        $kelas_list[] = $row['kelas'];
    }
}

// =========================
// HANDLE PRINT REQUEST
// =========================
if (isset($_POST['print'])) {
    $doc_type = $_POST['doc_type'];
    $scope = $_POST['scope'];
    $selected_kelas = $_POST['kelas'] ?? '';
    $selected_student = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    
    // Validate class access
    if (!empty($_SESSION['kelas']) && $_SESSION['kelas'] !== $selected_kelas) {
        die("Anda tidak memiliki akses ke kelas ini");
    }
    
    // Determine which students to print (Wajib Status Aktif)
    $student_ids = [];
    
    if ($scope === 'single' && $selected_student > 0) {
        $stmt = $conn->prepare("SELECT id FROM siswa WHERE id = ? AND status = 'Aktif'");
        $stmt->bind_param("i", $selected_student);
        $stmt->execute();
        if ($res = $stmt->get_result()->fetch_assoc()) {
            $student_ids[] = $res['id'];
        }
    } elseif ($scope === 'class' && !empty($selected_kelas)) {
        $stmt = $conn->prepare("SELECT id FROM siswa WHERE kelas = ? AND status = 'Aktif' ORDER BY nama ASC");
        $stmt->bind_param("s", $selected_kelas);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $student_ids[] = $row['id'];
        }
    } elseif ($scope === 'all') {
        if (!empty($_SESSION['kelas'])) {
            $stmt = $conn->prepare("SELECT id FROM siswa WHERE kelas = ? AND status = 'Aktif' ORDER BY kelas, nama ASC");
            $stmt->bind_param("s", $_SESSION['kelas']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $student_ids[] = $row['id'];
            }
        } else {
            $result = $conn->query("SELECT id FROM siswa WHERE status = 'Aktif' ORDER BY kelas, nama ASC");
            while ($row = $result->fetch_assoc()) {
                $student_ids[] = $row['id'];
            }
        }
    }
    
    // Redirect to print page with student IDs
    if (!empty($student_ids)) {
        $ids_param = implode(',', $student_ids);
        header("Location: print_$doc_type.php?ids=$ids_param");
        exit;
    } else {
        $error = "Tidak ada siswa aktif yang dipilih untuk dicetak.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Print Manager - Sistem Kelola Nilai</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">

<?php include 'nav.php'; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 p-6 sm:p-8">

        <!-- HEADER -->
        <div class="mb-6 pb-6 border-b border-slate-100">
            <span class="inline-block px-3 py-1 bg-indigo-50 text-indigo-600 font-semibold text-xs rounded-full mb-2">Pencetakan Dokumen</span>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Print Manager</h1>
            <p class="text-sm text-gray-500 mt-1">Cetak SKL, SKNR, atau Transkrip khusus untuk siswa berstatus aktif.</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2">
                <span>⚠️</span> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">

            <!-- JENIS DOKUMEN -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                    Jenis Dokumen
                </label>
                <select name="doc_type"
                        required
                        class="w-full bg-slate-50 border border-slate-200 text-gray-700 text-sm p-3.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                    <option value="">Pilih Jenis Dokumen</option>
                    <option value="skl">Surat Keterangan Lulus (SKL)</option>
                    <option value="sknr">Surat Keterangan Nilai Raport (SKNR)</option>
                    <option value="transkrip">Transkrip Nilai</option>
                </select>
            </div>

            <!-- SCOPE CETAK -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                    Scope Cetak
                </label>
                <select name="scope"
                        required
                        id="scopeSelect"
                        onchange="updateForm()"
                        class="w-full bg-slate-50 border border-slate-200 text-gray-700 text-sm p-3.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                    <option value="">Pilih Scope</option>
                    <option value="single">Per Siswa</option>
                    <option value="class">Per Kelas</option>
                    <option value="all">Semua Siswa Aktif</option>
                </select>
            </div>

            <!-- KELAS SECTION -->
            <div id="kelasSection" style="display: none;">
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                    Kelas
                </label>
                <select name="kelas"
                        id="kelasSelect"
                        onchange="loadStudents()"
                        class="w-full bg-slate-50 border border-slate-200 text-gray-700 text-sm p-3.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                    <option value="">Pilih Kelas</option>
                    <?php foreach ($kelas_list as $kelas): ?>
                        <option value="<?= $kelas ?>"><?= $kelas ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- SISWA SECTION -->
            <div id="studentSection" style="display: none;">
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                    Siswa Aktif
                </label>
                <select name="student_id"
                        id="studentSelect"
                        class="w-full bg-slate-50 border border-slate-200 text-gray-700 text-sm p-3.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                    <option value="">Pilih Siswa</option>
                </select>
            </div>

            <!-- TOMBOL AKSI -->
            <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                <button type="submit"
                        name="print"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm px-6 py-3.5 rounded-xl shadow-md shadow-indigo-100 transition flex items-center justify-center gap-2">
                    🖨️ Cetak Dokumen
                </button>
                <a href="index.php"
                   class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-sm px-6 py-3.5 rounded-xl transition">
                    Kembali
                </a>
            </div>

        </form>

    </div>

</div>

<script>
function updateForm() {
    const scope = document.getElementById('scopeSelect').value;
    const kelasSection = document.getElementById('kelasSection');
    const studentSection = document.getElementById('studentSection');
    
    kelasSection.style.display = 'none';
    studentSection.style.display = 'none';
    
    if (scope === 'single') {
        kelasSection.style.display = 'block';
        studentSection.style.display = 'block';
    } else if (scope === 'class') {
        kelasSection.style.display = 'block';
    }
}

function loadStudents() {
    const kelas = document.getElementById('kelasSelect').value;
    const studentSelect = document.getElementById('studentSelect');
    
    studentSelect.innerHTML = '<option value="">Pilih Siswa</option>';
    
    if (!kelas) return;
    
    // AJAX mengambil data siswa yang aktif saja
    fetch('get_students_active.php?kelas=' + encodeURIComponent(kelas))
        .then(response => response.json())
        .then(data => {
            data.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = student.nama + ' (' + student.nis + ')';
                studentSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error:', error));
}

updateForm();
</script>

</body>
</html>