<?php
require_once 'koneksi.php';
include 'auth.php';

$tabel = "siswa";
$pesan_notifikasi = "";

// ==========================================
// 2. PROSES KETIKA FORM DISUBMIT
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kelas_baru = trim($_POST['kelas_baru']);
    $raw_nis    = $_POST['daftar_nis'];

    // Memecah teks dari textarea berdasarkan baris baru (Enter)
    $lines = explode("\n", $raw_nis);
    $array_nis = [];

    foreach ($lines as $line) {
        $clean_nis = trim($line);
        if (!empty($clean_nis)) {
            $array_nis[] = $clean_nis;
        }
    }

    if (!empty($array_nis) && !empty($kelas_baru)) {
        $total_nis = count($array_nis);
        $placeholders = implode(',', array_fill(0, $total_nis, '?'));

        // Query update dengan prepared statement (kolom 'nis' dan 'kelas' sesuai tabel Anda)
        $query = "UPDATE $tabel SET kelas = ? WHERE nis IN ($placeholders)";
        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {
            $types = 's' . str_repeat('s', $total_nis);
            $params = array_merge([$types, $kelas_baru], $array_nis);
            
            $tmp = [];
            foreach($params as $key => $value) {
                $tmp[$key] = &$params[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], $tmp);

            if (mysqli_stmt_execute($stmt)) {
                $jumlah_update = mysqli_stmt_affected_rows($stmt);
                $pesan_notifikasi = "<div class='mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg'>Berhasil! Sebanyak <b>$jumlah_update</b> data siswa berhasil dipindahkan ke kelas <b>$kelas_baru</b>.</div>";
            } else {
                $pesan_notifikasi = "<div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg'>Error saat eksekusi: " . mysqli_stmt_error($stmt) . "</div>";
            }

            mysqli_stmt_close($stmt);
        } else {
            $pesan_notifikasi = "<div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg'>Error pada query: " . mysqli_error($conn) . "</div>";
        }
    } else {
        $pesan_notifikasi = "<div class='mb-4 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg'>Gagal: Pastikan kelas dipilih dan daftar NIS tidak kosong!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Kelas Siswa Massal</title>
    <!-- Menggunakan Tailwind CSS untuk tampilan -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
<?php include 'nav.php'; ?>
    <div class="bg-white p-6 rounded-xl shadow-md w-full max-w-lg">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Form Update Kelas Masal</h2>
        
        <!-- Menampilkan Pesan Notifikasi -->
        <?php echo $pesan_notifikasi; ?>

        <form action="" method="POST">
            <!-- Pilihan Kelas -->
            <!-- Pilihan Kelas -->
            <div class="mb-4">
                <label for="kelas_baru" class="block text-sm font-medium text-gray-700 mb-1">Pilih Kelas Tujuan:</label>
                <select name="kelas_baru" id="kelas_baru" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">-- Pilih Kelas --</option>
                    <?php
                    // Daftar semua kelas
                    $daftar_kelas = [
                        "7 A", "7 B", "7 C", "7 D", "7 E", "7 F", "7 G",
                        "8 A", "8 B", "8 C", "8 D", "8 E", "8 F", "8 G",
                        "9 A", "9 B", "9 C", "9 D", "9 E", "9 F", "9 G"
                    ];

                    // Loop untuk membuat option secara dinamis sekaligus menjaga pilihan terakhir tetap terpilih
                    foreach ($daftar_kelas as $k) {
                        $selected = (isset($_POST['kelas_baru']) && $_POST['kelas_baru'] == $k) ? 'selected' : '';
                        echo "<option value=\"$k\" $selected>$k</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Textarea NIS -->
            <div class="mb-4">
                <label for="daftar_nis" class="block text-sm font-medium text-gray-700 mb-1">Masukkan Daftar NIS (Pisahkan dengan baris baru / enter):</label>
                <textarea name="daftar_nis" id="daftar_nis" rows="10" required 
                    class="w-full border border-gray-300 rounded-lg p-2.5 font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    placeholder="Contoh:&#10;7131&#10;7165&#10;7135"></textarea>
                <p class="text-xs text-gray-500 mt-1">Daftar NIS di atas sudah otomatis terisi dan siap di-update.</p>
            </div>

            <!-- Tombol Submit -->
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition duration-200">
                Update Kelas Sekarang
            </button>
        </form>
    </div>

</body>
</html>
<?php 
mysqli_close($conn); 
?>