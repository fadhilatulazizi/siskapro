<?php
include 'koneksi.php';
include 'auth.php';

if (function_exists('requireLogin')) {
    requireLogin();
}
// --- 2. PERSIAPAN DATA ---
// Default ke bulan berjalan jika tidak ada filter
$filter_bulan = $_GET['bulan'] ?? date('Y-m'); 

// Array untuk mengubah angka bulan menjadi nama bulan bahasa Indonesia
$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$pecah_bulan = explode('-', $filter_bulan);
$teks_bulan = $nama_bulan[$pecah_bulan[1]] . ' ' . $pecah_bulan[0];

// Menghitung Hari Kerja Aktif
$stmt_hari = $conn->prepare("SELECT DISTINCT tanggal FROM log_absensi WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?");
$stmt_hari->bind_param("s", $filter_bulan);
$stmt_hari->execute();
$res_hari = $stmt_hari->get_result();
$total_hari_kerja = $res_hari->num_rows;

// Mengambil Data Rekap (Asumsi dasar: Hadir dan TK/Alpa)
// Catatan: Kolom Cuti, Sakit, Ijin, Dinas Luar diisi 0/kosong sementara karena membutuhkan tabel khusus perizinan
$stmt_rekap = $conn->prepare("
    SELECT 
        p.nip, 
        p.nama,
        'Guru' as jabatan, -- Bisa diganti query dari tabel master jika ada
        COUNT(a.tanggal) as total_hadir
    FROM (SELECT nip, MAX(nama) as nama FROM log_absensi GROUP BY nip) p
    LEFT JOIN log_absensi a ON p.nip = a.nip AND DATE_FORMAT(a.tanggal, '%Y-%m') = ?
    GROUP BY p.nip, p.nama
    ORDER BY p.nama ASC
");
$stmt_rekap->bind_param("s", $filter_bulan);
$stmt_rekap->execute();
$rekap_karyawan = $stmt_rekap->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rekap Kehadiran - <?= $teks_bulan ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Pengaturan khusus untuk mode cetak (Print) */
        @media print {
            @page {
                size: A4 landscape; /* Format kertas A4 memanjang agar kolom muat */
                margin: 15mm;
            }
            body {
                background-color: white !important;
                -webkit-print-color-adjust: exact; 
            }
            .no-print {
                display: none !important; /* Sembunyikan tombol saat dicetak */
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body class="bg-gray-100 text-black text-sm">

    <!-- Tombol Aksi (Sembunyi saat dicetak) -->
    <div class="no-print max-w-6xl mx-auto mt-8 mb-4 flex justify-between items-center bg-white p-4 rounded-lg shadow">
        <div class="text-gray-600 font-medium">Pratinjau Cetak Rekapitulasi</div>
        <div class="flex gap-3">
            <a href="rekap_absensi_bulanan.php?bulan=<?= $filter_bulan ?>" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded">Kembali</a>
            <button onclick="window.print()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-medium flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Cetak Dokumen
            </button>
        </div>
    </div>

    <!-- KERTAS CETAK -->
    <div class="max-w-[297mm] mx-auto bg-white min-h-[210mm] p-8 shadow-lg print:shadow-none print:p-0">
        
        <!-- HEADER KOP -->
        <div class="text-center font-bold text-base leading-tight mb-6">
            <p>PEMERINTAH KABUPATEN DEMAK</p>
            <p>SMP NEGERI 1 GUNTUR</p>
            <p class="underline mt-1">REKAPITULASI KEHADIRAN MASUK KERJA</p>
            <p>APARATUR SIPIL NEGARA (ASN)</p>
        </div>

        <!-- INFO OPD & BULAN -->
        <div class="mb-2 font-semibold text-sm flex flex-col gap-1">
            <div class="flex"><span class="w-32">NAMA OPD</span><span>: SMPN 1 Guntur</span></div>
            <div class="flex"><span class="w-32">Bulan Pelaporan</span><span>: <?= $teks_bulan ?></span></div>
        </div>

        <!-- TABEL REKAP -->
        <table class="w-full border-collapse border border-black mb-6 text-center text-[12px]">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-black p-2 align-middle w-10">NO</th>
                    <th class="border border-black p-2 align-middle w-36">NIP</th>
                    <th class="border border-black p-2 align-middle text-left">NAMA</th>
                    <th class="border border-black p-2 align-middle">JABATAN</th>
                    <th class="border border-black p-2 align-middle w-16">JUMLAH<br>HARI<br>KERJA</th>
                    <th class="border border-black p-2 align-middle w-16">JUMLAH<br>ASN</th>
                    <th class="border border-black p-2 align-middle w-12">CUTI</th>
                    <th class="border border-black p-2 align-middle w-12">SAKIT</th>
                    <th class="border border-black p-2 align-middle w-12">IJIN</th>
                    <th class="border border-black p-2 align-middle w-14">DINAS<br>LUAR</th>
                    <th class="border border-black p-2 align-middle w-12">TK</th>
                    <th class="border border-black p-2 align-middle w-24">KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rekap_karyawan) > 0): ?>
                    <?php $no = 1; foreach ($rekap_karyawan as $row): 
                        $nip = $row['nip'];
                        
                        // Perhitungan
                        $hadir = $row['total_hadir'];
                        $tk = $total_hari_kerja - $hadir; // Alpa diasumsikan sebagai TK
                        
                        // Konversi angka 0 menjadi strip "-" sesuai referensi dokumen
                        $format_hadir = $hadir > 0 ? $hadir : '-';
                        $format_tk = $tk > 0 ? $tk : '-';
                    ?>
                    <tr>
                        <td class="border border-black p-1.5"><?= $no++ ?></td>
                        <!-- Format NIP dipisah spasi jika diperlukan (Opsional) -->
                        <td class="border border-black p-1.5 tracking-wider"><?= htmlspecialchars($nip) ?></td>
                        <td class="border border-black p-1.5 text-left font-medium whitespace-nowrap"><?= htmlspecialchars($row['nama']) ?></td>
                        <td class="border border-black p-1.5"><?= htmlspecialchars($row['jabatan']) ?></td>
                        <td class="border border-black p-1.5"><?= $total_hari_kerja ?></td>
                        <td class="border border-black p-1.5"><?= $format_hadir ?></td>
                        
                        <!-- Data Perizinan (Sementara diset strip "-" karena belum ada di skema DB) -->
                        <td class="border border-black p-1.5">-</td>
                        <td class="border border-black p-1.5">-</td>
                        <td class="border border-black p-1.5">-</td>
                        <td class="border border-black p-1.5">-</td>
                        
                        <!-- Tanpa Keterangan (Alpa) -->
                        <td class="border border-black p-1.5"><?= $format_tk ?></td>
                        <td class="border border-black p-1.5"></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" class="border border-black p-4 text-center">Data tidak ditemukan untuk bulan ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- FOOTER & TANDA TANGAN -->
        <div class="flex justify-between text-sm mt-4">
            <!-- Kiri: Keterangan Tambahan -->
            <div class="w-1/2">
                <p>keterengan :</p>
                <p>29 Juni - 11 Juli 2026 Guru dan Karyawan Berangkat Sesuai Jadwal Terlampir</p>
            </div>
            
            <!-- Kanan: Blok Tanda Tangan -->
            <div class="w-1/3 text-center">
                <p>Demak, <?= date('d') ?> <?= $nama_bulan[date('m')] ?> <?= date('Y') ?></p>
                <p>Kepala Sekolah</p>
                
                <!-- Ruang Kosong untuk Tanda Tangan Asli / Stempel -->
                <div class="h-24"></div> 
                
                <p class="font-bold underline mb-0.5">Rina Yuniati, S. Pd.,M. Pd.</p>
                <p>NIP. 19740605 199802 2 001</p>
            </div>
        </div>

    </div>

</body>
</html>