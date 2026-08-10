<?php
include 'koneksi.php';

$siswa = null;
$grades = [];
$error = '';
$sem = 0.3; $uj = 0.7;

// Mendapatkan URL saat ini secara dinamis untuk QR Code
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$current_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

if (isset($_POST['cari']) || isset($_GET['nis'])) {
    $nis = isset($_POST['nis']) ? trim($_POST['nis']) : trim($_GET['nis']);
    
    if (empty($nis)) {
        $error = 'NIS harus diisi';
    } else {
        $stmt = $conn->prepare("SELECT * FROM siswa WHERE nis = ?");
        $stmt->bind_param("s", $nis);
        $stmt->execute();
        $siswa = $stmt->get_result()->fetch_assoc();
        
        if (!$siswa) {
            $error = 'NIS tidak ditemukan dalam database.';
        } else {
            // Ambil data nilai jika siswa ditemukan
            $stmt = $conn->prepare("
                SELECT 
                    m.nama_mapel,
                    n.s1, n.s2, n.s3, n.s4, n.s5, n.s6, n.nilai_ujian
                FROM nilai n
                JOIN mapel m ON n.mapel_id = m.id
                WHERE n.siswa_id = ?
                ORDER BY m.nama_mapel ASC
            ");
            $stmt->bind_param("i", $siswa['id']);
            $stmt->execute();
            $grades = $stmt->get_result();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portal Cek Nilai & Status Siswa - Akademik</title>
<script src="https://cdn.tailwindcss.com"></script>
<!-- Library QR Code Generator -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
    @media print {
        body { background: white; }
        .no-print { display: none !important; }
        .print-shadow { box-shadow: none !important; border: none !important; }
    }
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 20px; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen">

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Header / Form Pencarian -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8 mb-8 no-print">
        <div class="max-w-xl mx-auto text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-indigo-50 text-indigo-600 rounded-2xl mb-3 shadow-inner">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Portal Verifikasi & Cek Nilai Siswa</h1>
            <p class="text-slate-500 text-sm mt-1">Layanan publik untuk mengecek status keaktifan dan rekapitulasi nilai akademik siswa.</p>
        </div>

        <?php if ($error): ?>
            <div class="max-w-md mx-auto mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-700 text-sm rounded-2xl flex items-center space-x-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></path></svg>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="max-w-md mx-auto flex gap-3">
            <input type="text" name="nis" required value="<?= isset($_POST['nis']) ? htmlspecialchars($_POST['nis']) : (isset($_GET['nis']) ? htmlspecialchars($_GET['nis']) : '') ?>"
                   class="flex-1 bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3.5 text-sm focus:outline-none focus:border-indigo-500 focus:bg-white transition"
                   placeholder="Masukkan Nomor Induk Siswa (NIS)">
            <button type="submit" name="cari"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-3.5 rounded-2xl text-sm transition shadow-sm shadow-indigo-100 flex items-center gap-2">
                <span>Cek Data</span>
            </button>
        </form>
    </div>

    <?php if ($siswa): ?>
        <?php 
        // Pengecekan Status Keaktifan Siswa (Asumsi kolom di tabel siswa bernama 'status' dengan nilai 'aktif' atau 'lulus')
        // Jika tabel Anda menggunakan struktur berbeda, silakan disesuaikan.
        $status_siswa = isset($siswa['status']) ? strtolower(trim($siswa['status'])) : 'aktif';
        
        // Mengumpulkan dan menghitung data agregat jika ada grades
        $rows_data = [];
        $total_rata = 0;
        if ($grades && $grades->num_rows > 0) {
            while($r = $grades->fetch_assoc()) {
                $avg_sem = ($r['s1'] + $r['s2'] + $r['s3'] + $r['s4'] + $r['s5'] + $r['s6']) / 6;
                $r['rata'] = ($avg_sem * $sem) + ($r['nilai_ujian'] * $uj);
                $total_rata += $r['rata'];
                $rows_data[] = $r;
            }
            $overall_avg = $total_rata / count($rows_data);
        } else {
            $overall_avg = 0;
        }
        ?>

        <!-- Banner Status Kelulusan / Keaktifan Siswa -->
        <?php if ($status_siswa === 'lulus'): ?>
            <div class="bg-emerald-50 border border-emerald-200 rounded-3xl p-6 mb-8 flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-emerald-500 text-white rounded-2xl flex items-center justify-center font-bold text-xl shadow-sm">✓</div>
                    <div>
                        <h4 class="text-emerald-900 font-bold text-lg">Status: Telah Lulus</h4>
                        <p class="text-emerald-700 text-sm">Siswa ini telah dinyatakan lulus dari satuan pendidikan. Cek dokumentasi kelulusan? klik <a href="https://s.id/ArsipIjazah_SMPN1Guntur">https://s.id/ArsipIjazah_SMPN1Guntur</a></p>
                    </div>
                </div>
                <span class="text-xs bg-emerald-200 text-emerald-800 font-bold px-3 py-1.5 rounded-xl uppercase tracking-wider">Alumni / Lulus</span>
            </div>
        <?php elseif ($status_siswa === 'aktif'): ?>
            <div class="bg-sky-50 border border-sky-200 rounded-3xl p-6 mb-8 flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-sky-500 text-white rounded-2xl flex items-center justify-center font-bold text-xl shadow-sm">●</div>
                    <div>
                        <h4 class="text-sky-900 font-bold text-lg">Status: Siswa Aktif</h4>
                        <p class="text-sky-700 text-sm">Siswa terdaftar aktif dan mengikuti kegiatan akademik pada tahun ajaran ini.</p>
                    </div>
                </div>
                <span class="text-xs bg-sky-200 text-sky-800 font-bold px-3 py-1.5 rounded-xl uppercase tracking-wider">Aktif Bersekolah</span>
            </div>
        <?php else: ?>
            <div class="bg-amber-50 border border-amber-200 rounded-3xl p-6 mb-8 flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-amber-500 text-white rounded-2xl flex items-center justify-center font-bold text-xl shadow-sm">!</div>
                    <div>
                        <h4 class="text-amber-900 font-bold text-lg">Status: <?= ucfirst($status_siswa) ?></h4>
                        <p class="text-amber-700 text-sm">Siswa berada dalam status non-aktif / mutasi / keluar.</p>
                    </div>
                </div>
                <span class="text-xs bg-amber-200 text-amber-800 font-bold px-3 py-1.5 rounded-xl uppercase tracking-wider"><?= strtoupper($status_siswa) ?></span>
            </div>
        <?php endif; ?>

        <!-- Identitas Siswa & QR Code Share -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Info Siswa -->
            <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-slate-200 p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full">Identitas Akademik</span>
                        <button onclick="window.print()" class="no-print text-xs font-medium text-slate-500 hover:text-indigo-600 flex items-center gap-1 bg-slate-50 hover:bg-slate-100 px-3 py-1.5 rounded-xl transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Cetak Laporan
                        </button>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-900 mb-1"><?= htmlspecialchars($siswa['nama']) ?></h2>
                    <p class="text-sm text-slate-500">NIS: <span class="font-semibold text-slate-700"><?= htmlspecialchars($siswa['nis']) ?></span></p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <span class="text-xs text-slate-400 block">Kelas Akademik</span>
                        <span class="font-bold text-slate-800 text-base"><?= htmlspecialchars($siswa['kelas']) ?></span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">Rata-Rata Nilai Akhir</span>
                        <span class="font-bold text-indigo-600 text-base"><?= count($rows_data) > 0 ? number_format($overall_avg, 1) : '0.0' ?></span>
                    </div>
                </div>
            </div>

            <!-- QR Code Widget untuk Share Halaman Publik -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 flex flex-col items-center justify-center text-center">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">QR Code Halaman Ini</span>
                <div id="qrcode" class="p-2 bg-slate-50 rounded-2xl border border-slate-100 mb-3 inline-block"></div>
                <p class="text-xs text-slate-400">Scan untuk membuka halaman publik siswa ini</p>
            </div>
        </div>

        <!-- Tabel Nilai Rinci -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden print-shadow">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-lg text-slate-900">Rekapitulasi Nilai Mata Pelajaran</h3>
                <span class="text-xs text-slate-400">Perhitungan 30% S1 s.d. S6 & 70% Ujian</span>
            </div>

            <?php if (count($rows_data) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-slate-900 text-white uppercase text-xs tracking-wider">
                            <tr>
                                <th class="px-5 py-4 font-semibold text-center w-12">No</th>
                                <th class="px-5 py-4 font-semibold">Mata Pelajaran</th>
                                <th class="px-3 py-4 text-center">S1</th>
                                <th class="px-3 py-4 text-center">S2</th>
                                <th class="px-3 py-4 text-center">S3</th>
                                <th class="px-3 py-4 text-center">S4</th>
                                <th class="px-3 py-4 text-center">S5</th>
                                <th class="px-3 py-4 text-center">S6</th>
                                <th class="px-3 py-4 text-center bg-slate-800">Ujian</th>
                                <th class="px-5 py-4 text-center font-bold text-indigo-300">Nilai Akhir</th>
                                <th class="px-5 py-4 text-center">Predikat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php $no = 1; foreach($rows_data as $row): 
                                $f_rata = $row['rata'];
                                if($f_rata >= 85) {
                                    $badge_bg = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    $pred = 'A (Sangat Baik)';
                                } elseif($f_rata >= 75) {
                                    $badge_bg = 'bg-sky-50 text-sky-700 border-sky-200';
                                    $pred = 'B (Baik)';
                                } elseif($f_rata >= 65) {
                                    $badge_bg = 'bg-amber-50 text-amber-700 border-amber-200';
                                    $pred = 'C (Cukup)';
                                } else {
                                    $badge_bg = 'bg-rose-50 text-rose-700 border-rose-200';
                                    $pred = 'D (Kurang)';
                                }
                            ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-5 py-4 text-center text-slate-400 font-medium"><?= $no++ ?></td>
                                    <td class="px-5 py-4 font-semibold text-slate-800"><?= htmlspecialchars($row['nama_mapel']) ?></td>
                                    <td class="px-3 py-4 text-center text-slate-600"><?= $row['s1'] ?></td>
                                    <td class="px-3 py-4 text-center text-slate-600"><?= $row['s2'] ?></td>
                                    <td class="px-3 py-4 text-center text-slate-600"><?= $row['s3'] ?></td>
                                    <td class="px-3 py-4 text-center text-slate-600"><?= $row['s4'] ?></td>
                                    <td class="px-3 py-4 text-center text-slate-600"><?= $row['s5'] ?></td>
                                    <td class="px-3 py-4 text-center text-slate-600"><?= $row['s6'] ?></td>
                                    <td class="px-3 py-4 text-center font-semibold text-slate-700 bg-slate-50/50"><?= $row['nilai_ujian'] ?></td>
                                    <td class="px-5 py-4 text-center font-black text-indigo-600 text-base"><?= number_format($f_rata, 1) ?></td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold border <?= $badge_bg ?>">
                                            <?= $pred ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    </div>
                    <p class="text-slate-600 font-medium">Belum ada data nilai yang diinputkan untuk siswa ini.</p>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>

<!-- Script untuk Generate QR Code secara otomatis -->
<script>
    <?php if ($siswa): ?>
    // Buat URL lengkap untuk dibagikan, misal menyertakan parameter NIS agar langsung meload data siswa tersebut
    const shareUrl = "<?= $current_url . '?nis=' . urlencode($siswa['nis']) ?>";
    
    // Render QR Code menggunakan QRCode.js
    new QRCode(document.getElementById("qrcode"), {
        text: shareUrl,
        width: 110,
        height: 110,
        colorDark : "#1e293b",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });
    <?php endif; ?>
</script>

</body>
</html>