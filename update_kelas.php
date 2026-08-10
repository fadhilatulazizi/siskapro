<?php
// 1. PANGGIL KONEKSI DATABASE
require_once 'koneksi.php';

$tabel = 'siswa'; // <-- Sesuaikan dengan nama tabel Anda
$pesan = "";
$tipe_pesan = "";

// 2. PROSES UPDATE KELAS MASSAL SAAT FORM DISUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_kelas') {
    $ids = $_POST['ids'] ?? [];
    $kelas_baru = trim($_POST['kelas_baru'] ?? '');

    if (empty($ids)) {
        $pesan = "Pilih minimal satu siswa yang ingin diubah kelasnya!";
        $tipe_pesan = "error";
    } elseif (empty($kelas_baru)) {
        $pesan = "Nama kelas baru wajib diisi!";
        $tipe_pesan = "error";
    } else {
        try {
            // Konversi ID ke angka untuk keamanan
            $ids = array_map('intval', $ids);
            
            // Buat placeholder (?) sebanyak jumlah ID yang dipilih
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql_update = "UPDATE $tabel SET kelas = ? WHERE id IN ($placeholders)";
            
            $stmt = $conn->prepare($sql_update);

            // Tipe data parameter: "s" untuk kelas_baru (string), diikuti "i" sebanyak jumlah ID (integer)
            $types = "s" . str_repeat("i", count($ids));
            $params = array_merge([$kelas_baru], $ids);

            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $jumlah_diubah = $stmt->affected_rows;
                $pesan = "Berhasil memperbarui kelas untuk $jumlah_diubah siswa menjadi <strong>" . htmlspecialchars($kelas_baru) . "</strong>!";
                $tipe_pesan = "success";
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $pesan = "Gagal memperbarui kelas: " . $e->getMessage();
            $tipe_pesan = "error";
        }
    }
}

// 3. AMBIL DAFTAR KELAS SAAT INI UNTUK FILTER (OPSIONAL)
$filter_kelas = $_GET['filter_kelas'] ?? '';
$daftar_kelas_result = $conn->query("SELECT DISTINCT kelas FROM $tabel ORDER BY kelas ASC");

// 4. QUERY AMBIL DATA SISWA DENGAN FILTER
$sql_siswa = "SELECT id, nis, nisn, nama, kelas FROM $tabel";
if (!empty($filter_kelas)) {
    $sql_siswa .= " WHERE kelas = '" . $conn->real_escape_string($filter_kelas) . "'";
}
$sql_siswa .= " ORDER BY kelas ASC, nama ASC";

$siswa_result = $conn->query($sql_siswa);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Kelas Siswa (Checklist)</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen py-8 px-4">

    <div class="max-w-6xl mx-auto space-y-6">

        <!-- Header -->
        <div class="bg-white rounded-xl shadow-md p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Update Kelas Massal (Checklist)</h2>
                <p class="text-sm text-gray-500">Pilih siswa menggunakan centang, lalu tentukan kelas barunya.</p>
            </div>
            <a href="daftar_siswa.php" class="inline-flex items-center text-sm font-medium text-blue-600 hover:underline">
                &larr; Kembali ke Daftar Siswa
            </a>
        </div>

        <!-- Notifikasi Status -->
        <?php if (!empty($pesan)): ?>
            <div class="p-4 rounded-lg text-sm font-medium <?php echo $tipe_pesan === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
                <?php echo $pesan; ?>
            </div>
        <?php endif; ?>

        <!-- Filter & Form Aksi -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Card 1: Filter Kelas Asal -->
            <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100">
                <h3 class="text-md font-semibold text-gray-700 mb-3">1. Filter Kelas Asal</h3>
                <form method="GET" action="" class="space-y-3">
                    <select name="filter_kelas" onchange="this.form.submit()" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">-- Tampilkan Semua Kelas --</option>
                        <?php while ($row_k = $daftar_kelas_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row_k['kelas']); ?>" <?php echo $filter_kelas === $row_k['kelas'] ? 'selected' : ''; ?>>
                                Kelas <?php echo htmlspecialchars($row_k['kelas']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <?php if (!empty($filter_kelas)): ?>
                        <a href="update_kelas.php" class="text-xs text-red-600 hover:underline inline-block">Reset Filter</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Card 2: Ubah Ke Kelas Baru -->
            <div class="md:col-span-2 bg-white p-5 rounded-xl shadow-md border border-gray-100">
                <h3 class="text-md font-semibold text-gray-700 mb-3">2. Set Kelas Baru</h3>
                
                <!-- Form gabungan aksi update -->
                <form id="formUpdateKelas" action="" method="POST" class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                    <input type="hidden" name="action" value="update_kelas">
                    
                    <div class="w-full sm:w-auto flex-1">
                        <input type="text" name="kelas_baru" required placeholder="Ketik nama kelas baru (misal: XII RPL 1)" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>

                    <button type="submit" 
                            onclick="return confirm('Apakah Anda yakin ingin mengubah kelas siswa yang dicentang?')"
                            class="w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
                        Update Kelas Terpilih
                    </button>
                </form>
            </div>

        </div>

        <!-- Tabel Daftar Siswa dengan Checklist -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
            <div class="p-4 bg-gray-50 border-b flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Daftar Siswa</span>
                <span class="text-xs text-gray-500">Total: <strong><?php echo $siswa_result->num_rows; ?></strong> siswa</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                        <tr>
                            <th class="p-4 w-10 text-center">
                                <input type="checkbox" id="checkAll" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500 cursor-pointer">
                            </th>
                            <th class="p-4">NIS / NISN</th>
                            <th class="p-4">Nama Lengkap</th>
                            <th class="p-4">Kelas Saat Ini</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($siswa_result->num_rows > 0): ?>
                            <?php while ($siswa = $siswa_result->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="p-4 text-center">
                                        <!-- Checkbox dengan form attribute merujuk ke #formUpdateKelas -->
                                        <input type="checkbox" name="ids[]" value="<?php echo $siswa['id']; ?>" 
                                               form="formUpdateKelas"
                                               class="siswa-checkbox w-4 h-4 text-blue-600 rounded focus:ring-blue-500 cursor-pointer">
                                    </td>
                                    <td class="p-4">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($siswa['nis']); ?></div>
                                        <div class="text-xs text-gray-400"><?php echo htmlspecialchars($siswa['nisn']); ?></div>
                                    </td>
                                    <td class="p-4 font-semibold text-gray-800">
                                        <?php echo htmlspecialchars($siswa['nama']); ?>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-medium rounded-full text-xs">
                                            <?php echo htmlspecialchars($siswa['kelas']); ?>
                                        </span> &nbsp;
                            <a href="edit-siswa.php?id=<?= $siswa['id'] ?>"
                               class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-lg text-xs">
                                Ubah Data
                            </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="p-8 text-center text-gray-400">
                                    Data siswa tidak ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- JavaScript untuk Pilih Semua Checkbox -->
    <script>
        const checkAll = document.getElementById('checkAll');
        const checkboxes = document.querySelectorAll('.siswa-checkbox');

        if (checkAll) {
            checkAll.addEventListener('change', function() {
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
            });
        }
    </script>

</body>
</html>