<?php

include 'koneksi.php';
include 'auth.php';

if (function_exists('requireLogin')) {
    requireLogin();
}

$pesan_sukses = "";
$pesan_error = "";
$preview_data = [];
$kelas_asal_terpilih = [];
$kelas_tujuan_terpilih = [];
$max_siswa_per_kelas = isset($_POST['max_siswa']) ? (int)$_POST['max_siswa'] : 30;

// Ambil daftar kelas yang unik dari database untuk pilihan checklist
$query_kelas = "SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas ASC";
$result_kelas = $conn->query($query_kelas);
$list_kelas_db = [];
while ($row_k = $result_kelas->fetch_assoc()) {
    $list_kelas_db[] = $row_k['kelas'];
}

// 1. PROSES PREVIEW (PRATINJAU DISTRIBUSI KELAS)
if (isset($_POST['preview'])) {
    $kelas_asal_terpilih = isset($_POST['kelas_asal']) ? $_POST['kelas_asal'] : [];
    $kelas_tujuan_terpilih = isset($_POST['kelas_tujuan']) ? $_POST['kelas_tujuan'] : [];
    $max_siswa_per_kelas = (int)$_POST['max_siswa'];

    if (empty($kelas_asal_terpilih)) {
        $pesan_error = "Pilih minimal satu kelas asal terlebih dahulu!";
    } elseif (empty($kelas_tujuan_terpilih)) {
        $pesan_error = "Pilih minimal satu kelas tujuan terlebih dahulu!";
    } elseif ($max_siswa_per_kelas <= 0) {
        $pesan_error = "Maksimal siswa per kelas harus lebih dari 0!";
    } else {
        // Buat placeholder dinamis untuk query IN (...) kelas asal
        $placeholders_asal = implode(',', array_fill(0, count($kelas_asal_terpilih), '?'));
        
        /* 
          PERHITUNGAN RATA-RATA BERDASARKAN STRUKTUR TABEL NILAI ANDA:
          Menghitung rata-rata dari nilai_ujian dan kolom s1 s/d s5 per baris nilai, 
          lalu dirata-rata kembali per siswa (jika siswa punya banyak baris mapel).
        */
        $query = "SELECT s.id, s.nama, s.nis, s.kelas as kelas_lama,
                         COALESCE(AVG((COALESCE(n.nilai_ujian, 0) + COALESCE(n.s1, 0) + COALESCE(n.s2, 0) + COALESCE(n.s3, 0) + COALESCE(n.s4, 0) + COALESCE(n.s5, 0)) / 6), 0) as rata_rata
                  FROM siswa s
                  LEFT JOIN nilai n ON s.id = n.siswa_id
                  WHERE s.status = 'Aktif' AND s.kelas IN ($placeholders_asal)
                  GROUP BY s.id
                  ORDER BY rata_rata DESC, s.nama ASC";
                  
        $stmt = $conn->prepare($query);
        $types = str_repeat('s', count($kelas_asal_terpilih));
        $stmt->bind_param($types, ...$kelas_asal_terpilih);
        $stmt->execute();
        $result = $stmt->get_result();

        $data_siswa = [];
        while ($row = $result->fetch_assoc()) {
            $data_siswa[] = $row;
        }
        $stmt->close();

        if (count($data_siswa) == 0) {
            $pesan_error = "Tidak ada data siswa aktif pada kelas asal yang dipilih!";
        } else {
            $jml_kelas = count($kelas_tujuan_terpilih);
            $total_kapasitas = $jml_kelas * $max_siswa_per_kelas;

            if (count($data_siswa) > $total_kapasitas) {
                $pesan_error = "Peringatan: Total siswa (" . count($data_siswa) . " orang) melebihi batas total kapasitas kelas tujuan (" . $total_kapasitas . " orang)!";
            } else {
                // Inisialisasi kuota terisi
                $kuota_terisi = [];
                foreach ($kelas_tujuan_terpilih as $kt) {
                    $kuota_terisi[$kt] = 0;
                }

                // Pola Distribusi Snake (Zig-Zag) untuk meratakan siswa berprestasi
                $index_kelas = 0;
                $direction = 1; 

                foreach ($data_siswa as $siswa) {
                    $assigned = false;
                    $attempts = 0;

                    while (!$assigned && $attempts < $jml_kelas) {
                        $kelas_pilih = $kelas_tujuan_terpilih[$index_kelas];

                        if ($kuota_terisi[$kelas_pilih] < $max_siswa_per_kelas) {
                            $kuota_terisi[$kelas_pilih]++;
                            
                            $preview_data[] = [
                                'id' => $siswa['id'],
                                'nama' => $siswa['nama'],
                                'nis' => $siswa['nis'],
                                'kelas_lama' => $siswa['kelas_lama'],
                                'rata_rata' => round($siswa['rata_rata'], 2),
                                'kelas_simulasi' => $kelas_pilih
                            ];

                            $assigned = true;
                        }

                        if ($direction == 1) {
                            if ($index_kelas + 1 < $jml_kelas) {
                                $index_kelas++;
                            } else {
                                $direction = -1;
                            }
                        } else {
                            if ($index_kelas - 1 >= 0) {
                                $index_kelas--;
                            } else {
                                $direction = 1;
                            }
                        }

                        $attempts++;
                    }

                    if (!$assigned) {
                        $pesan_error = "Kapasitas kelas tujuan tidak mencukupi untuk menampung seluruh siswa.";
                        break;
                    }
                }
            }
        }
    }
}

// 2. PROSES SIMPAN PERMANEN KE DATABASE
if (isset($_POST['simpan_permanen'])) {
    $data_simpan = json_decode($_POST['data_json'], true);

    if (!empty($data_simpan)) {
        $conn->begin_transaction();
        try {
            $update = $conn->prepare("UPDATE siswa SET kelas = ? WHERE id = ?");
            foreach ($data_simpan as $item) {
                $update->bind_param("si", $item['kelas_simulasi'], $item['id']);
                $update->execute();
            }
            $update->close();
            $conn->commit();
            $pesan_sukses = "Berhasil! Rombel siswa telah diperbarui secara permanen di database.";
        } catch (Exception $e) {
            $conn->rollback();
            $pesan_error = "Gagal memperbarui data: " . $e->getMessage();
        }
    } else {
        $pesan_error = "Tidak ada data simulasi untuk disimpan.";
    }
}

// Ambil total siswa aktif untuk informasi awal
$result_siswa = $conn->query("SELECT COUNT(*) as total FROM siswa WHERE status = 'Aktif'");
$data_siswa_count = $result_siswa->fetch_assoc();

// Kelompokkan data preview berdasarkan kelas simulasi (tujuan)
$grouped_preview = [];
$total_rata_global = 0;
if (!empty($preview_data)) {
    $jumlah_total_siswa = count($preview_data);
    foreach ($preview_data as $row) {
        $grouped_preview[$row['kelas_simulasi']][] = $row;
        $total_rata_global += $row['rata_rata'];
    }
    $rata_rata_global = $jumlah_total_siswa > 0 ? round($total_rata_global / $jumlah_total_siswa, 2) : 0;

    // Urutkan nama siswa A-Z di setiap kelompok kelas tujuan
    foreach ($grouped_preview as $kls_tujuan => &$arr_s) {
        usort($arr_s, function($a, $b) {
            return strcasecmp($a['nama'], $b['nama']);
        });
    }
    unset($arr_s);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengacakan Rombel & Distribusi Prestasi Siswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-6">
    <?php include 'nav.php'; ?>
    <div class="max-w-6xl mx-auto bg-white p-6 rounded-xl shadow-md mt-6">
        <h2 class="text-2xl font-bold mb-2 text-gray-800">Distribusi Otomatis Rombel Siswa</h2>
        <p class="text-gray-600 mb-6 text-sm">Sistem menghitung nilai rata-rata dari komponen nilai ujian, s1, s2, s3, s4, dan s5 secara otomatis.</p>
        
        <!-- Notifikasi -->
        <?php if (!empty($pesan_sukses)): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                <span class="text-sm text-green-700 font-medium"><?php echo $pesan_sukses; ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($pesan_error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <span class="text-sm text-red-700 font-medium"><?php echo $pesan_error; ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 flex justify-between items-center flex-wrap gap-2">
            <span class="text-sm text-blue-700">Total Siswa Aktif Keseluruhan: <strong><?php echo $data_siswa_count['total']; ?> Siswa</strong></span>
            <?php if (!empty($preview_data)): ?>
                <span class="text-sm text-blue-700">Rata-Rata Keseluruhan (Diproses): <strong><?php echo $rata_rata_global; ?></strong></span>
            <?php endif; ?>
        </div>

        <!-- Form Input Kelas & Preview -->
        <form action="" method="POST" class="mb-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">1. Pilih Kelas Asal (Checklist):</label>
                <?php if (empty($list_kelas_db)): ?>
                    <p class="text-sm text-red-500">Belum ada data kelas di dalam tabel siswa.</p>
                <?php else: ?>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-3 border rounded-lg bg-gray-50">
                        <?php foreach ($list_kelas_db as $kls): ?>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="kelas_asal[]" value="<?php echo htmlspecialchars($kls); ?>" 
                                    <?php echo (in_array($kls, $kelas_asal_terpilih)) ? 'checked' : ''; ?>
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                                <span class="text-sm text-gray-700 font-medium"><?php echo htmlspecialchars($kls); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">2. Pilih Kelas Tujuan (Checklist):</label>
                <?php if (empty($list_kelas_db)): ?>
                    <p class="text-sm text-red-500">Belum ada data kelas.</p>
                <?php else: ?>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-3 border rounded-lg bg-gray-50">
                        <?php foreach ($list_kelas_db as $kls): ?>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="kelas_tujuan[]" value="<?php echo htmlspecialchars($kls); ?>" 
                                    <?php echo (in_array($kls, $kelas_tujuan_terpilih)) ? 'checked' : ''; ?>
                                    class="rounded border-gray-300 text-green-600 focus:ring-green-500 h-4 w-4">
                                <span class="text-sm text-gray-700 font-medium"><?php echo htmlspecialchars($kls); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">3. Maksimal Siswa per Kelas:</label>
                <input type="number" name="max_siswa" value="<?php echo $max_siswa_per_kelas; ?>" min="1" required 
                       class="w-full md:w-1/3 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Batasan kuota jumlah siswa untuk masing-masing kelas tujuan.</p>
            </div>

            <button type="submit" name="preview" 
                    class="bg-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:bg-blue-700 transition duration-200">
                Pratinjau (Preview) Hasil Pembagian
            </button>
        </form>

        <!-- BAGIAN TABEL PREVIEW LIST PER KELAS -->
        <?php if (!empty($grouped_preview)): ?>
            <hr class="my-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Hasil Pratinjau Pembagian Kelas</h3>
                    <p class="text-xs text-gray-500">Total Keseluruhan: <?php echo count($preview_data); ?> Siswa</p>
                </div>
                
                <!-- Form untuk Simpan Permanen -->
                <form action="" method="POST">
                    <input type="hidden" name="data_json" value='<?php echo htmlspecialchars(json_encode($preview_data), ENT_QUOTES, 'UTF-8'); ?>'>
                    <button type="submit" name="simpan_permanen" 
                            class="bg-green-600 text-white font-semibold py-2 px-6 rounded-lg hover:bg-green-700 transition duration-200">
                        Konfirmasi & Simpan ke Database
                    </button>
                </form>
            </div>

            <div class="space-y-6">
                <?php foreach ($grouped_preview as $nama_kelas_tujuan => $arr_siswa_kelas): ?>
                    <?php 
                        // Hitung rerata nilai kelas simulasi ini
                        $total_nilai_kelas = array_sum(array_column($arr_siswa_kelas, 'rata_rata'));
                        $avg_kelas = count($arr_siswa_kelas) > 0 ? round($total_nilai_kelas / count($arr_siswa_kelas), 2) : 0;
                    ?>
                    <div class="border rounded-xl p-4 bg-white shadow-sm">
                        <div class="flex justify-between items-center mb-3 pb-2 border-b flex-wrap gap-2">
                            <h4 class="font-bold text-lg text-blue-600">Kelas: <?php echo htmlspecialchars($nama_kelas_tujuan); ?></h4>
                            <div class="flex space-x-2">
                                <span class="text-xs font-semibold bg-green-50 text-green-700 px-3 py-1 rounded-full">
                                    Rerata Nilai Kelas: <?php echo $avg_kelas; ?>
                                </span>
                                <span class="text-xs font-semibold bg-blue-50 text-blue-700 px-3 py-1 rounded-full">
                                    Jumlah Siswa: <?php echo count($arr_siswa_kelas); ?> Orang
                                </span>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 border-b text-gray-700 text-xs uppercase tracking-wider">
                                        <th class="p-2.5">No</th>
                                        <th class="p-2.5">NIS</th>
                                        <th class="p-2.5">Nama Siswa</th>
                                        <th class="p-2.5">Kelas Asal</th>
                                        <th class="p-2.5">Nilai Rata-rata</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm text-gray-600 divide-y">
                                    <?php $no = 1; foreach ($arr_siswa_kelas as $row): ?>
                                    <tr>
                                        <td class="p-2.5"><?php echo $no++; ?></td>
                                        <td class="p-2.5"><?php echo htmlspecialchars($row['nis'] ?? '-'); ?></td>
                                        <td class="p-2.5 font-medium text-gray-800"><?php echo htmlspecialchars($row['nama']); ?></td>
                                        <td class="p-2.5"><?php echo htmlspecialchars($row['kelas_lama'] ?? '-'); ?></td>
                                        <td class="p-2.5 font-semibold text-gray-700"><?php echo $row['rata_rata']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>