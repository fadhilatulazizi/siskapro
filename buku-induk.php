<?php
include 'koneksi.php';
include 'auth.php';

if (function_exists('requireLogin')) {
    requireLogin();
}

// Ambil parameter filter dari GET
$kelas_filter = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : '1';

// Ambil daftar kelas untuk dropdown filter
$query_kelas = "SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas ASC";
$result_kelas = $conn->query($query_kelas);
$list_kelas = [];
while ($rk = $result_kelas->fetch_assoc()) {
    $list_kelas[] = $rk['kelas'];
}

// Ambil daftar seluruh mata pelajaran dari tabel mapel
$q_mapel = $conn->query("SELECT id, nama_mapel FROM mapel ORDER BY nama_mapel ASC");
$mapel_list_db = [];
$daftar_mapel = [];
while ($rm = $q_mapel->fetch_assoc()) {
    $mapel_list_db[$rm['id']] = $rm['nama_mapel'];
    $daftar_mapel[] = $rm['nama_mapel'];
}

// Ambil seluruh siswa aktif berdasarkan kelas yang dipilih beserta nilai mereka
$data_siswa_kelas = [];
if (!empty($kelas_filter) && !empty($semester_filter)) {
    // 1. Ambil data siswa
    $q_siswa = $conn->prepare("SELECT id, nis, nisn, nama, kelas FROM siswa WHERE kelas = ? AND status = 'Aktif' ORDER BY nama ASC");
    $q_siswa->bind_param("s", $kelas_filter);
    $q_siswa->execute();
    $res_siswa = $q_siswa->get_result();
    
    $kolom_semester = "s" . $semester_filter;

    while ($s = $res_siswa->fetch_assoc()) {
        $s_id = $s['id'];
        
        // 2. Ambil nilai per siswa untuk semester terpilih
        $q_nilai = $conn->prepare("SELECT mapel_id, $kolom_semester AS nilai_semester FROM nilai WHERE siswa_id = ?");
        $q_nilai->bind_param("i", $s_id);
        $q_nilai->execute();
        $res_nilai = $q_nilai->get_result();
        
        $nilai_mentah = [];
        while ($rn = $res_nilai->fetch_assoc()) {
            $nilai_mentah[$rn['mapel_id']] = $rn['nilai_semester'];
        }
        $q_nilai->close();

        // 3. Petakan nilai ke mapel
        $raport_mapel = [];
        foreach ($mapel_list_db as $m_id => $m_nama) {
            $n_val = isset($nilai_mentah[$m_id]) ? $nilai_mentah[$m_id] : 0;
            $raport_mapel[$m_nama] = [
                'nilai' => $n_val,
                'kkm' => 80
            ];
        }

        $s['raport'] = $raport_mapel;
        $data_siswa_kelas[] = $s;
    }
    $q_siswa->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Induk / Lembar Hasil Belajar Per Kelas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-after: always;
                break-after: page;
            }
            .sheet-a4 {
                box-shadow: none !important;
                margin: 0 !important;
                width: 100% !important;
                padding: 1.5cm !important;
                min-height: auto !important;
            }
        }
        .sheet-a4 {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 20px auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20mm;
            box-sizing: border-box;
        }
    </style>
</head>
<body class="bg-gray-100 py-6">

    <!-- PANEL KONTROL FILTER & CETAK -->
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-xl shadow-md mb-6 no-print">
        <?php include 'nav.php'; ?>
        <h2 class="text-2xl font-bold mb-2 text-gray-800 mt-4">Cetak Buku Induk Nilai Per Kelas</h2>
        <p class="text-gray-600 text-sm mb-4">Pilih kelas dan semester untuk menampilkan seluruh lembar hasil belajar siswa dalam satu tampilan.</p>

        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Pilih Kelas:</label>
                <select name="kelas" class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($list_kelas as $kls): ?>
                        <option value="<?php echo $kls; ?>" <?php echo ($kelas_filter == $kls) ? 'selected' : ''; ?>><?php echo $kls; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Pilih Semester:</label>
                <select name="semester" class="w-full px-3 py-2 border rounded-lg text-sm">
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($semester_filter == $i) ? 'selected' : ''; ?>>Semester <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="flex space-x-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">Tampilkan</button>
                <?php if (!empty($data_siswa_kelas)): ?>
                    <button type="button" onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-green-700 transition">Cetak Semua A4</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- LEMBAR CETAK PER SISWA DALAM KELAS -->
    <?php if (!empty($kelas_filter) && empty($data_siswa_kelas)): ?>
        <div class="max-w-4xl mx-auto bg-yellow-50 border-l-4 border-yellow-500 p-4 text-yellow-700 text-sm no-print">
            Tidak ada siswa aktif ditemukan pada kelas ini atau belum ada data semester yang sesuai.
        </div>
    <?php endif; ?>

    <?php if (!empty($data_siswa_kelas)): ?>
        <?php foreach ($data_siswa_kelas as $index => $profil_siswa): ?>
            <div class="sheet-a4 text-gray-800 text-sm font-serif page-break">
                
                <!-- KOP SURAT -->
                <div class="text-center border-b-2 border-black pb-4 mb-6">
                    <h3 class="text-lg font-bold uppercase tracking-wider">PEMERINTAH KABUPATEN DEMAK</h3>
                    <h2 class="text-xl font-extrabold uppercase">DINAS PENDIDIKAN DAN KEBUDAYAAN</h2>
                    <h1 class="text-2xl font-black uppercase">SEKOLAH DASAR / MENENGAH NEGERI</h1>
                    <p class="text-xs text-gray-600 mt-1">Alamat: Jl. Pendidikan No. 1 Telp. (0291) XXXXXX Kabupaten Demak</p>
                </div>

                <!-- JUDUL LEMBAR -->
                <div class="text-center mb-6">
                    <h4 class="font-bold text-base underline uppercase">KUMPULAN HASIL BELAJAR (BUKU INDUK)</h4>
                    <p class="text-sm font-semibold mt-1">LAPORAN HASIL PENILAIAN SEMESTER <?php echo $semester_filter; ?></p>
                </div>

                <!-- IDENTITAS SISWA -->
                <table class="w-full mb-6 text-xs font-sans">
                    <tr>
                        <td class="py-1 font-semibold" style="width: 20%;">Nama Peserta Didik</td>
                        <td style="width: 2%;">:</td>
                        <td class="py-1 font-bold uppercase" style="width: 28%;"><?php echo htmlspecialchars($profil_siswa['nama']); ?></td>
                        
                        <td class="py-1 font-semibold" style="width: 20%;">Kelas / Semester</td>
                        <td style="width: 2%;">:</td>
                        <td class="py-1" style="width: 28%;"><?php echo htmlspecialchars($profil_siswa['kelas']); ?> / Semester <?php echo $semester_filter; ?></td>
                    </tr>
                    <tr>
                        <td class="py-1 font-semibold">Nomor Induk / NISN</td>
                        <td>:</td>
                        <td class="py-1"><?php echo htmlspecialchars($profil_siswa['nis']); ?> / <?php echo htmlspecialchars($profil_siswa['nisn'] ?? '-'); ?></td>
                        
                        <td class="py-1 font-semibold">Tahun Pelajaran</td>
                        <td>:</td>
                        <td class="py-1"><?php echo date('Y') . "/" . (date('Y') + 1); ?></td>
                    </tr>
                </table>

                <!-- TABEL NILAI MATA PELAJARAN -->
                <table class="w-full border-collapse border border-black mb-6 text-xs font-sans">
                    <thead>
                        <tr class="bg-gray-200 text-center font-bold">
                            <th class="border border-black py-2 px-2" style="width: 8%;">No</th>
                            <th class="border border-black py-2 px-4 text-left">Mata Pelajaran</th>
                            <th class="border border-black py-2 px-2" style="width: 12%;">KKM</th>
                            <th class="border border-black py-2 px-2" style="width: 15%;">Nilai Angka</th>
                            <th class="border border-black py-2 px-2" style="width: 18%;">Predikat / Ket</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $raport_siswa = $profil_siswa['raport'];
                        if (empty($daftar_mapel)) {
                            echo '<tr><td colspan="5" class="border border-black text-center py-4 text-gray-500 italic">Belum ada data mata pelajaran.</td></tr>';
                        } else {
                            $no = 1;
                            foreach ($daftar_mapel as $mapel) {
                                $n_val = isset($raport_siswa[$mapel]) ? $raport_siswa[$mapel]['nilai'] : 0;
                                $kkm_val = isset($raport_siswa[$mapel]) ? $raport_siswa[$mapel]['kkm'] : 80;

                                $ket = "-";
                                if ($n_val >= 85) { $ket = "Sangat Baik (A)"; }
                                elseif ($n_val >= 75) { $ket = "Baik (B)"; }
                                elseif ($n_val > 0) { $ket = "Cukup (C)"; }
                        ?>
                            <tr>
                                <td class="border border-black text-center py-2"><?php echo $no++; ?></td>
                                <td class="border border-black px-3 py-2 font-medium"><?php echo htmlspecialchars($mapel); ?></td>
                                <td class="border border-black text-center py-2"><?php echo $kkm_val; ?></td>
                                <td class="border border-black text-center py-2 font-bold"><?php echo $n_val > 0 ? $n_val : '-'; ?></td>
                                <td class="border border-black text-center py-2"><?php echo $ket; ?></td>
                            </tr>
                        <?php 
                            } 
                        }
                        ?>
                    </tbody>
                </table>

                <!-- CATATAN -->
                <div class="border border-black p-3 mb-8 text-xs font-sans">
                    <p class="font-bold mb-1">Keterangan / Catatan Wali Kelas:</p>
                    <div class="h-12 border-b border-dashed border-gray-400"></div>
                </div>

                <!-- TANDA TANGAN -->
                <table class="w-full text-xs font-sans mt-4">
                    <tr>
                        <td class="text-center" style="width: 33%;">
                            <p>Mengetahui,</p>
                            <p>Orang Tua / Wali Murid</p>
                            <br><br><br>
                            <p class="font-bold underline">( ................................... )</p>
                        </td>
                        <td style="width: 34%;"></td>
                        <td class="text-center" style="width: 33%;">
                            <p>Demak, <?php echo date('d F Y'); ?></p>
                            <p>Wali Kelas</p>
                            <br><br><br>
                            <p class="font-bold underline">___________________________</p>
                            <p>NIP. ...................................</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-center pt-6">
                            <p>Mengetahui,</p>
                            <p>Kepala Sekolah</p>
                            <br><br><br>
                            <p class="font-bold underline"><b>( Nama Kepala Sekolah, S.Pd. )</b></p>
                            <p>NIP. 19700101 199003 1 001</p>
                        </td>
                    </tr>
                </table>

            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
