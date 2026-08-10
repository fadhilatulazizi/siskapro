<?php
// 1. PANGGIL KONEKSI DATABASE
require_once 'koneksi.php';

$tabel = 'siswa'; // <-- Sesuaikan dengan nama tabel Anda
$pesan = "";
$tipe_pesan = "";

// 2. CEK PARAMETER ID DI URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Jika tidak ada ID, kembalikan ke halaman daftar data
    header("Location: data-siswa.php");
    exit;
}

$id = intval($_GET['id']);

// 3. PROSES UPDATE DATA SAAT FORM DISUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil input & bersihkan
    $nama           = trim($_POST['nama'] ?? '');
    $nis            = trim($_POST['nis'] ?? '');
    $nisn           = trim($_POST['nisn'] ?? '');
    $kelas          = trim($_POST['kelas'] ?? '');
    
    // Field opsional (bisa NULL)
    $tempat_lahir   = !empty($_POST['tempat_lahir']) ? trim($_POST['tempat_lahir']) : NULL;
    $tanggal_lahir  = !empty($_POST['tanggal_lahir']) ? trim($_POST['tanggal_lahir']) : NULL;
    $nama_orang_tua = !empty($_POST['nama_orang_tua']) ? trim($_POST['nama_orang_tua']) : NULL;
    $nomor_ijazah   = !empty($_POST['nomor_ijazah']) ? trim($_POST['nomor_ijazah']) : NULL;
    $no_surat       = !empty($_POST['no_surat']) ? trim($_POST['no_surat']) : NULL;

    // Validasi field wajib
    if (empty($nama) || empty($nis) || empty($nisn) || empty($kelas)) {
        $pesan = "Kolom Nama, NIS, NISN, dan Kelas wajib diisi!";
        $tipe_pesan = "error";
    } else {
        try {
            // Query UPDATE dengan Prepared Statement
            $sql_update = "UPDATE $tabel SET 
                            nama = ?, 
                            nis = ?, 
                            nisn = ?, 
                            kelas = ?, 
                            tempat_lahir = ?, 
                            tanggal_lahir = ?, 
                            nama_orang_tua = ?, 
                            nomor_ijazah = ?, 
                            no_surat = ? 
                           WHERE id = ?";
            
            $stmt = $conn->prepare($sql_update);
            // "sssssssssi" -> 9 string, 1 integer (id)
            $stmt->bind_param("sssssssssi", $nama, $nis, $nisn, $kelas, $tempat_lahir, $tanggal_lahir, $nama_orang_tua, $nomor_ijazah, $no_surat, $id);

            if ($stmt->execute()) {
                $pesan = "Data berhasil diperbarui!";
                $tipe_pesan = "success";
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $pesan = "Gagal memperbarui data: " . $e->getMessage();
            $tipe_pesan = "error";
        }
    }
}

// 4. AMBIL DATA TERBARU DARI DATABASE UNTUK DITAMPILKAN DI FORM
try {
    $sql_fetch = "SELECT * FROM $tabel WHERE id = ?";
    $stmt_fetch = $conn->prepare($sql_fetch);
    $stmt_fetch->bind_param("i", $id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();

    if ($result->num_rows === 0) {
        die("<div class='p-5 text-red-600 font-bold'>Data siswa dengan ID " . htmlspecialchars($id) . " tidak ditemukan.</div>");
    }

    $data = $result->fetch_assoc();
    $stmt_fetch->close();
} catch (mysqli_sql_exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Siswa</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen py-10 px-4">

    <div class="max-w-3xl mx-auto bg-white rounded-xl shadow-md overflow-hidden p-6 md:p-8">
        
        <!-- Header & Tombol Kembali -->
        <div class="flex items-center justify-between border-b pb-3 mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Edit Data Siswa (ID: <?php echo htmlspecialchars($data['id']); ?>)</h2>
            <a href="data_siswa.php" class="text-sm text-blue-600 hover:underline">&larr; Kembali ke Daftar</a>
        </div>

        <!-- Notifikasi Status -->
        <?php if (!empty($pesan)): ?>
            <div class="mb-6 p-4 rounded-lg text-sm font-medium <?php echo $tipe_pesan === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
                <?php echo $pesan; ?>
            </div>
        <?php endif; ?>

        <form action="edit-siswa.php?id=<?php echo $id; ?>" method="POST" class="space-y-6">
            
            <!-- Data Utama -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" maxlength="100" required 
                           value="<?php echo htmlspecialchars($data['nama'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">NIS <span class="text-red-500">*</span></label>
                    <input type="text" name="nis" maxlength="30" required 
                           value="<?php echo htmlspecialchars($data['nis'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">NISN <span class="text-red-500">*</span></label>
                    <input type="text" name="nisn" maxlength="30" required 
                           value="<?php echo htmlspecialchars($data['nisn'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Kelas <span class="text-red-500">*</span></label>
                    <input type="text" name="kelas" maxlength="20" required 
                           value="<?php echo htmlspecialchars($data['kelas'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <hr class="border-gray-200 my-4">

            <!-- Data Tambahan (Opsional) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" maxlength="100" 
                           value="<?php echo htmlspecialchars($data['tempat_lahir'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" 
                           value="<?php echo htmlspecialchars($data['tanggal_lahir'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Orang Tua</label>
                    <input type="text" name="nama_orang_tua" maxlength="100" 
                           value="<?php echo htmlspecialchars($data['nama_orang_tua'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nomor Ijazah</label>
                    <input type="text" name="nomor_ijazah" maxlength="100" 
                           value="<?php echo htmlspecialchars($data['nomor_ijazah'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">No. Surat</label>
                    <input type="text" name="no_surat" maxlength="50" 
                           value="<?php echo htmlspecialchars($data['no_surat'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <!-- Informasi Waktu Otomatis -->
            <div class="text-xs text-gray-400 pt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
                <p>Dibuat pada: <?php echo htmlspecialchars($data['created_at'] ?? '-'); ?></p>
                <p>Terakhir diubah: <?php echo htmlspecialchars($data['updated_at'] ?? '-'); ?></p>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-4 flex items-center justify-end space-x-3">
                <a href="daftar_siswa.php" 
                   class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium transition">
                    Batal
                </a>
                <button type="submit" 
                        class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium shadow-sm transition">
                    Simpan Perubahan
                </button>
            </div>

        </form>
    </div>

</body>
</html>