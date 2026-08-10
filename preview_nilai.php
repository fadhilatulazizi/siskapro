<?php
include 'koneksi.php';
include 'auth.php';
include 'csrf.php';

requireLogin();

// Get available classes
$kelas_result = $conn->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas");
$kelas_list = [];
while ($row = $kelas_result->fetch_assoc()) {
    $kelas_list[] = $row['kelas'];
}

// Get subjects
$mapel = $conn->query("SELECT * FROM mapel ORDER BY nama_mapel ASC");

$selected_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query based on filters
$where_conditions = [];
$params = [];
$types = '';

if (!empty($selected_kelas)) {
    $where_conditions[] = "s.kelas = ?";
    $params[] = $selected_kelas;
    $types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(s.nama LIKE ? OR s.nis LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get students with their grades
$query = "
    SELECT 
        s.id,
        s.nis,
        s.nama,
        s.kelas,
        s.nisn,
        s.tempat_lahir,
        s.tanggal_lahir
    FROM siswa s
    $where_clause
    ORDER BY s.kelas, s.nama ASC
";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $students = $stmt->get_result();
} else {
    $students = $conn->query($query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Preview Nilai - Sistem Kelola Nilai</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

<?php include 'nav.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Header -->
    <div class="bg-white rounded-2xl shadow p-6 mb-6">

        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                Preview Nilai Siswa
            </h1>
            <p class="text-gray-500">
                SMP Negeri 1 Guntur
            </p>
        </div>

        <!-- Filter Form -->
        <form method="GET" class="space-y-4">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="block mb-2 font-medium">
                        Filter Kelas
                    </label>

                    <select name="kelas"
                            class="w-full border rounded-xl px-4 py-3">

                        <option value="">
                            Semua Kelas
                        </option>

                        <?php foreach ($kelas_list as $kelas): ?>

                            <option value="<?= $kelas ?>" 
                                    <?= $selected_kelas === $kelas ? 'selected' : '' ?>>
                                <?= $kelas ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div>
                    <label class="block mb-2 font-medium">
                        Cari Siswa (Nama/NIS)
                    </label>

                    <input type="text"
                           name="search"
                           value="<?= htmlspecialchars($search) ?>"
                           placeholder="Masukkan nama atau NIS"
                           class="w-full border rounded-xl px-4 py-3">
                </div>

            </div>

            <div class="flex gap-3">

                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl">
                    Cari
                </button>

                <a href="preview_nilai.php"
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl">
                    Reset
                </a>

            </div>

        </form>

    </div>

    <!-- Results -->
    <?php if ($students->num_rows > 0): ?>

        <?php while($student = $students->fetch_assoc()): ?>

            <div class="bg-white rounded-2xl shadow p-6 mb-6">

                <!-- Student Info -->
                <div class="mb-4 pb-4 border-b">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        <div>
                            <span class="text-gray-500 text-sm">Nama</span>
                            <p class="font-medium text-gray-800">
                                <?= htmlspecialchars($student['nama']) ?>
                            </p>
                        </div>

                        <div>
                            <span class="text-gray-500 text-sm">NIS</span>
                            <p class="font-medium text-gray-800">
                                <?= htmlspecialchars($student['nis']) ?>
                            </p>
                        </div>

                        <div>
                            <span class="text-gray-500 text-sm">Kelas</span>
                            <p class="font-medium text-gray-800">
                                <?= htmlspecialchars($student['kelas']) ?>
                            </p>
                        </div>

                    </div>

                </div>

                <!-- Grades Table -->
                <div class="overflow-x-auto">

                    <table class="min-w-full text-sm text-left">

                        <thead class="bg-gray-800 text-white">
                            <tr>
                                <th class="px-4 py-3">No</th>
                                <th class="px-4 py-3">Mata Pelajaran</th>
                                <th class="px-4 py-3">S1</th>
                                <th class="px-4 py-3">S2</th>
                                <th class="px-4 py-3">S3</th>
                                <th class="px-4 py-3">S4</th>
                                <th class="px-4 py-3">S5</th>
                                <th class="px-4 py-3">S6</th>
                                <th class="px-4 py-3">Ujian</th>
                                <th class="px-4 py-3">Rata-rata</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php
                            // Get grades for this student
                            $stmt_nilai = $conn->prepare("
                                SELECT 
                                    m.nama_mapel,
                                    n.s1,
                                    n.s2,
                                    n.s3,
                                    n.s4,
                                    n.s5,
                                    n.s6,
                                    n.nilai_ujian
                                FROM mapel m
                                LEFT JOIN nilai n ON m.id = n.mapel_id AND n.siswa_id = ?
                                ORDER BY m.nama_mapel ASC
                            ");
                            $stmt_nilai->bind_param("i", $student['id']);
                            $stmt_nilai->execute();
                            $grades = $stmt_nilai->get_result();

                            $no = 1;
                            $total_rata = 0;
                            $jumlah_mapel = 0;

                            while($grade = $grades->fetch_assoc()):
                                
                                $s1 = $grade['s1'] ?? 0;
                                $s2 = $grade['s2'] ?? 0;
                                $s3 = $grade['s3'] ?? 0;
                                $s4 = $grade['s4'] ?? 0;
                                $s5 = $grade['s5'] ?? 0;
                                $s6 = $grade['s6'] ?? 0;
                                $ujian = $grade['nilai_ujian'] ?? 0;

                                $rata = ($s1 + $s2 + $s3 + $s4 + $s5 + $s6 + $ujian) / 7;
                                $total_rata += $rata;
                                $jumlah_mapel++;
                            ?>

                                <tr class="border-b hover:bg-gray-50">

                                    <td class="px-4 py-3"><?= $no++ ?></td>

                                    <td class="px-4 py-3 font-medium">
                                        <?= htmlspecialchars($grade['nama_mapel']) ?>
                                    </td>

                                    <td class="px-4 py-3 text-center"><?= $s1 ?></td>
                                    <td class="px-4 py-3 text-center"><?= $s2 ?></td>
                                    <td class="px-4 py-3 text-center"><?= $s3 ?></td>
                                    <td class="px-4 py-3 text-center"><?= $s4 ?></td>
                                    <td class="px-4 py-3 text-center"><?= $s5 ?></td>
                                    <td class="px-4 py-3 text-center"><?= $s6 ?></td>
                                    <td class="px-4 py-3 text-center"><?= $ujian ?></td>

                                    <td class="px-4 py-3 text-center font-bold">
                                        <?= number_format($rata, 1) ?>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                            <?php if ($jumlah_mapel > 0): ?>

                                <tr class="bg-gray-100 font-bold">

                                    <td colspan="9" class="px-4 py-3 text-center">
                                        Rata-rata Keseluruhan
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <?= number_format($total_rata / $jumlah_mapel, 1) ?>
                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <!-- Action Buttons -->
                <div class="mt-4 flex gap-3">

                    <a href="transkrip.php?id=<?= $student['id'] ?>"
                       target="_blank"
                       class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
                        Cetak Transkrip
                    </a>

                    <a href="sknr.php?id=<?= $student['id'] ?>"
                       target="_blank"
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                        Cetak SKNR
                    </a>

                    <a href="skl.php?id=<?= $student['id'] ?>"
                       target="_blank"
                       class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm">
                        Cetak SKL
                    </a>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="bg-white rounded-2xl shadow p-6 text-center">

            <p class="text-gray-500">
                Tidak ada data siswa yang ditemukan.
            </p>

        </div>

    <?php endif; ?>

</div>

</body>
</html>
