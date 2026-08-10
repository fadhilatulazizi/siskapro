<?php
include 'koneksi.php';
include 'auth.php';
include 'csrf.php';

requireLogin();

// Get available classes (Hanya siswa aktif)
if (!empty($_SESSION['kelas'])) {
    $kelas_list = [$_SESSION['kelas']];
} else {
    $kelas_result = $conn->query("SELECT DISTINCT kelas FROM siswa WHERE status = 'Aktif' ORDER BY kelas");
    $kelas_list = [];
    while ($row = $kelas_result->fetch_assoc()) {
        $kelas_list[] = $row['kelas'];
    }
}

// Get subjects
$mapel = $conn->query("SELECT * FROM mapel ORDER BY nama_mapel ASC");

$success_message = '';
$error_message = '';

if(isset($_POST['simpan'])){
    
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
    
    $selected_kelas = $_POST['kelas'];
    $selected_mapel_id = (int)$_POST['mapel_id'];
    $selected_semester = $_POST['semester'];
    
    // Validate class access
    if (!empty($_SESSION['kelas']) && $_SESSION['kelas'] !== $selected_kelas) {
        die("Anda tidak memiliki akses ke kelas ini");
    }
    
    // Get all active students in the selected class
    $stmt = $conn->prepare("SELECT id FROM siswa WHERE kelas = ? AND status = 'Aktif' ORDER BY nama ASC");
    $stmt->bind_param("s", $selected_kelas);
    $stmt->execute();
    $students = $stmt->get_result();
    
    $success_count = 0;
    $error_count = 0;
    
    while ($student = $students->fetch_assoc()) {
        $siswa_id = $student['id'];
        $nilai = isset($_POST['nilai'][$siswa_id]) ? (int)$_POST['nilai'][$siswa_id] : 0;
        
        // Validate grade range
        if ($nilai < 0 || $nilai > 100) {
            $error_count++;
            continue;
        }
        
        // Determine which column to update based on semester
        $column_map = [
            's1' => 's1',
            's2' => 's2',
            's3' => 's3',
            's4' => 's4',
            's5' => 's5',
            's6' => 's6',
            'ujian' => 'nilai_ujian'
        ];
        
        $column = $column_map[$selected_semester] ?? 's1';
        
        // Check if record exists
        $stmt_check = $conn->prepare("SELECT id FROM nilai WHERE siswa_id = ? AND mapel_id = ?");
        $stmt_check->bind_param("ii", $siswa_id, $selected_mapel_id);
        $stmt_check->execute();
        $existing = $stmt_check->get_result()->fetch_assoc();
        
        if ($existing) {
            // Update existing record
            $stmt_update = $conn->prepare("UPDATE nilai SET $column = ? WHERE siswa_id = ? AND mapel_id = ?");
            $stmt_update->bind_param("iii", $nilai, $siswa_id, $selected_mapel_id);
            if ($stmt_update->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
        } else {
            // Insert new record with default values for other semesters
            $stmt_insert = $conn->prepare("INSERT INTO nilai (siswa_id, mapel_id, s1, s2, s3, s4, s5, s6, nilai_ujian) VALUES (?, ?, 0, 0, 0, 0, 0, 0, 0)");
            $stmt_insert->bind_param("ii", $siswa_id, $selected_mapel_id);
            if ($stmt_insert->execute()) {
                // Now update the specific column
                $stmt_update = $conn->prepare("UPDATE nilai SET $column = ? WHERE siswa_id = ? AND mapel_id = ?");
                $stmt_update->bind_param("iii", $nilai, $siswa_id, $selected_mapel_id);
                if ($stmt_update->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            } else {
                $error_count++;
            }
        }
    }
    
    // Regenerate CSRF token after successful submission
    regenerateCsrfToken();
    
    if ($error_count > 0) {
        $error_message = "Berhasil disimpan: $success_count, Gagal: $error_count";
    } else {
        $success_message = "Semua nilai berhasil disimpan ($success_count siswa)";
    }
}

$csrfToken = generateCsrfToken();

// Get students for display if class is selected (Hanya siswa aktif)
$students = [];
if (isset($_POST['kelas']) && !isset($_POST['simpan'])) {
    $selected_kelas = $_POST['kelas'];
    $stmt = $conn->prepare("SELECT * FROM siswa WHERE kelas = ? AND status = 'Aktif' ORDER BY nama ASC");
    $stmt->bind_param("s", $selected_kelas);
    $stmt->execute();
    $students = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Input Nilai Per Kelas - Sistem Kelola Nilai</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

<?php include 'nav.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="bg-white rounded-2xl shadow p-6">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                Input Nilai Per Kelas
            </h1>

            <p class="text-gray-500">
                Input nilai untuk semua siswa aktif dalam satu kelas sekaligus
            </p>
        </div>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= $success_message ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">

            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <div>
                    <label class="block mb-2 font-medium">
                        Kelas
                    </label>

                    <select name="kelas"
                            required
                            class="w-full border rounded-xl px-4 py-3">

                        <option value="">
                            Pilih Kelas
                        </option>

                        <?php foreach ($kelas_list as $kelas): ?>

                            <option value="<?= $kelas ?>" 
                                    <?= (isset($_POST['kelas']) && $_POST['kelas'] === $kelas) ? 'selected' : '' ?>>
                                <?= $kelas ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div>
                    <label class="block mb-2 font-medium">
                        Mata Pelajaran
                    </label>

                    <select name="mapel_id"
                            required
                            class="w-full border rounded-xl px-4 py-3">

                        <option value="">
                            Pilih Mata Pelajaran
                        </option>

                        <?php 
                        $mapel->data_seek(0);
                        while($m = $mapel->fetch_assoc()): 
                        ?>

                            <option value="<?= $m['id'] ?>"
                                    <?= (isset($_POST['mapel_id']) && $_POST['mapel_id'] == $m['id']) ? 'selected' : '' ?>>
                                <?= $m['nama_mapel'] ?>
                            </option>

                        <?php endwhile; ?>

                    </select>
                </div>

                <div>
                    <label class="block mb-2 font-medium">
                        Semester
                    </label>

                    <select name="semester"
                            required
                            class="w-full border rounded-xl px-4 py-3">

                        <option value="">
                            Pilih Semester
                        </option>

                        <option value="s1" <?= (isset($_POST['semester']) && $_POST['semester'] === 's1') ? 'selected' : '' ?>>
                            Semester 1
                        </option>

                        <option value="s2" <?= (isset($_POST['semester']) && $_POST['semester'] === 's2') ? 'selected' : '' ?>>
                            Semester 2
                        </option>

                        <option value="s3" <?= (isset($_POST['semester']) && $_POST['semester'] === 's3') ? 'selected' : '' ?>>
                            Semester 3
                        </option>

                        <option value="s4" <?= (isset($_POST['semester']) && $_POST['semester'] === 's4') ? 'selected' : '' ?>>
                            Semester 4
                        </option>

                        <option value="s5" <?= (isset($_POST['semester']) && $_POST['semester'] === 's5') ? 'selected' : '' ?>>
                            Semester 5
                        </option>

                        <option value="s6" <?= (isset($_POST['semester']) && $_POST['semester'] === 's6') ? 'selected' : '' ?>>
                            Semester 6
                        </option>

                        <option value="ujian" <?= (isset($_POST['semester']) && $_POST['semester'] === 'ujian') ? 'selected' : '' ?>>
                            Nilai Ujian
                        </option>

                    </select>
                </div>

            </div>

            <button type="submit"
                    name="tampil"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl">
                Tampilkan Siswa
            </button>

        </form>

        <?php if (!empty($students) && isset($_POST['kelas']) && !isset($_POST['simpan'])): ?>

            <form method="POST" class="mt-8">

                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="kelas" value="<?= htmlspecialchars($_POST['kelas']) ?>">
                <input type="hidden" name="mapel_id" value="<?= (int)$_POST['mapel_id'] ?>">
                <input type="hidden" name="semester" value="<?= htmlspecialchars($_POST['semester']) ?>">

                <div class="mb-4">
                    <h2 class="text-xl font-bold text-gray-800">
                        Input Nilai - Kelas <?= htmlspecialchars($_POST['kelas']) ?> (Siswa Aktif)
                    </h2>
                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full text-sm text-left">

                        <thead class="bg-gray-800 text-white">
                            <tr>
                                <th class="px-4 py-3">No</th>
                                <th class="px-4 py-3">Nama Siswa</th>
                                <th class="px-4 py-3">NIS</th>
                                <th class="px-4 py-3">Nilai (0-100)</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php
                            $no = 1;
                            while($row = $students->fetch_assoc()):
                                
                                // Get existing grade if any
                                $stmt_nilai = $conn->prepare("SELECT * FROM nilai WHERE siswa_id = ? AND mapel_id = ?");
                                $stmt_nilai->bind_param("ii", $row['id'], $_POST['mapel_id']);
                                $stmt_nilai->execute();
                                $nilai_data = $stmt_nilai->get_result()->fetch_assoc();
                                
                                $column_map = [
                                    's1' => 's1',
                                    's2' => 's2',
                                    's3' => 's3',
                                    's4' => 's4',
                                    's5' => 's5',
                                    's6' => 's6',
                                    'ujian' => 'nilai_ujian'
                                ];
                                
                                $column = $column_map[$_POST['semester']] ?? 's1';
                                $existing_nilai = $nilai_data[$column] ?? 0;
                            ?>

                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-3"><?= $no++ ?></td>
                                    <td class="px-4 py-3 font-medium">
                                        <?= htmlspecialchars($row['nama']) ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?= htmlspecialchars($row['nis']) ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number"
                                               name="nilai[<?= $row['id'] ?>]"
                                               value="<?= $existing_nilai ?>"
                                               min="0"
                                               max="100"
                                               class="w-24 border rounded px-3 py-2">
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

                <div class="mt-6 flex gap-3">

                    <button type="submit"
                            name="simpan"
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl">
                        Simpan Semua Nilai
                    </button>

                    <a href="input_nilai_kelas.php"
                       class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl">
                        Batal
                    </a>

                </div>

            </form>

        <?php endif; ?>

        <div class="mt-6">
            <a href="index.php"
               class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl">
                Kembali ke Dashboard
            </a>
        </div>

    </div>

</div>

</body>
</html>