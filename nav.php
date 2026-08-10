<?php
$username = $_SESSION['username'] ?? '';
$kelas = $_SESSION['kelas'] ?? null;

// daftar admin (bisa ditambah)
$admin_users = ['admin'];
$is_admin = in_array($username, $admin_users);
$is_bk = str_ends_with($username, '_bk');

function activeMenu($file){
    return basename($_SERVER['PHP_SELF']) === $file
        ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-100'
        : 'text-gray-600 hover:text-indigo-600 hover:bg-indigo-50/50 font-medium';
}
?>

<!-- Tombol Toggle Mobile (Muncul di layar kecil) -->
<div class="lg:hidden flex items-center justify-between bg-white border-b border-slate-200 px-4 py-3 sticky top-0 z-50">
    <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-md">
            🎓
        </div>
        <span class="font-bold text-gray-800 text-base tracking-tight">Sistem Nilai</span>
    </div>
    <button onclick="toggleSidebar()" class="p-2 rounded-xl bg-slate-100 text-gray-600 hover:bg-slate-200 focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>
</div>

<!-- Backdrop untuk Mobile -->
<div id="sidebarBackdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-gray-900/50 z-40 hidden lg:hidden transition-opacity"></div>

<!-- Sidebar Utama -->
<aside id="appSidebar" class="fixed inset-y-0 left-0 w-64 bg-white border-r border-slate-200 flex flex-col justify-between z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
    
    <!-- Bagian Atas: Brand & Menu Navigasi -->
    <div class="p-5 overflow-y-auto">
        <!-- Logo Desktop -->
        <div class="hidden lg:flex items-center gap-3 mb-8">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-base shadow-md shadow-indigo-100">
                🎓
            </div>
            <div>
                <span class="font-bold text-gray-800 text-base tracking-tight block leading-tight">Sistem Nilai</span>
                <span class="text-[11px] text-gray-400 font-medium">Panel Akademik</span>
            </div>
        </div>

        <!-- Group Navigasi -->
        <div class="space-y-1.5 text-xs">
            <div class="px-3 pb-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Menu Utama</div>
            
            <!-- Menu yang bisa diakses oleh Admin & BK (serta user biasa jika relevan) -->
            <?php if(!$is_bk): ?>
            <a href="index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= activeMenu('index.php') ?>">
                📊 Dashboard
            </a>
            <?php endif; ?>
            <a href="daftar_siswa.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= activeMenu('daftar_siswa.php') ?>">
                👥 Daftar Siswa
            </a>            

            <!-- Menu Input/Edit Nilai (DISEMBUNYIKAN dari BK) -->
            <?php if(!$is_bk): ?>
            <a href="bulk-update.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= activeMenu('bulk-update.php') ?>">
                👥 Bulk Update
            </a>
            <?php endif; ?>

            <a href="rekap_nilai.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= activeMenu('rekap_nilai.php') ?>">
                📋 Rekap Nilai
            </a>

            <!-- Menu Input/Edit Nilai (DISEMBUNYIKAN dari BK) -->
            <?php if(!$is_bk): ?>
            <a href="input_nilai_kelas.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= activeMenu('input_nilai_kelas.php') ?>">
                ✍️ Input Nilai
            </a>
            <a href="nilai.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= activeMenu('nilai.php') ?>">
                📝 Nilai Import/Export
            </a>
            <a href="print_manager.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= activeMenu('print_manager.php') ?>">
                🖨️ PrintDokumen
            </a>
            <?php endif; ?>

            <a href="student_login.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= activeMenu('student_login.php') ?>">
                👤 Siswa Public Check
            </a>
            <a href="rank.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= activeMenu('rank.php') ?>">
                🏆 Rangking Siswa
            </a>
            
            <!-- Menu Khusus Administrator -->
            <?php if($is_admin): ?>
            <div class="pt-3 pb-2 px-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Administrator</div>
            <a href="analisis.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= activeMenu('analisis.php') ?>">
                📈 Analisis
            </a>
            <a href="add-user.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= activeMenu('add-user.php') ?>">
                👤 Add Admin
            </a>
            <a href="absensi.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= activeMenu('absensi.php') ?>">
                📊 Laporan Absensi
            </a>
            <a href="rombel.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= activeMenu('rombel.php') ?>">
                📝 Pembagian Kelas
            </a>
            <div class="pt-3 pb-2 px-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Akademik</div>
            <a href="buku-induk.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= activeMenu('buku-induk.php') ?>">
                📖 Buku Induk Siswa
            </a>
            <?php endif; ?>

            <!-- Menu Khusus Bimbingan Konseling (BK) -->
            <?php if($is_bk): ?>
            <div class="pt-3 pb-2 px-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Bimbingan Konseling</div>
            <a href="harian.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= activeMenu('harian.php') ?>">
                📋 Catatan Harian
            </a> 
            <a href="poin.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= activeMenu('poin.php') ?>">
                👉 Points BK
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bagian Bawah: Profil Pengguna & Keluar -->
    <div class="p-4 border-t border-slate-100 bg-slate-50/50">
        <div class="relative">
            <!-- Tombol Dropdown Profil -->
            <button onclick="toggleDropdown(event, 'userDropdown')" class="w-full flex items-center justify-between focus:outline-none bg-white hover:bg-slate-50 p-2.5 rounded-xl border border-slate-200/60 transition shadow-sm">
                <div class="flex items-center gap-3">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($username); ?>&background=4f46e5&color=fff" class="w-8 h-8 rounded-full shadow-sm object-cover">
                    <div class="text-left">
                        <div class="font-semibold text-xs text-gray-800 leading-tight truncate w-28"><?= htmlspecialchars($username) ?></div>
                        <div class="text-[10px] text-gray-500 font-medium">
                            <?php 
                                if ($is_admin) {
                                    echo 'Administrator';
                                } elseif ($is_bk) {
                                    echo 'Guru BK';
                                } else {
                                    echo $kelas ? 'Kelas: ' . htmlspecialchars($kelas) : 'User';
                                }
                            ?>
                        </div>
                    </div>
                </div>
                <svg class="w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <!-- Dropdown Menu Profil -->
            <div id="userDropdown" class="dropdown-menu hidden absolute bottom-full left-0 mb-2 w-full bg-white rounded-xl shadow-xl border border-slate-100 py-1.5 z-50">
                <a href="logout.php" class="flex items-center gap-2 px-4 py-2.5 text-xs font-medium text-rose-600 hover:bg-rose-50 transition">
                    🚪 Keluar (Logout)
                </a>
            </div>
        </div>
    </div>

</aside>

<!-- Skrip JavaScript Interaktif -->
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    sidebar.classList.toggle('-translate-x-full');
    backdrop.classList.toggle('hidden');
}

function toggleDropdown(event, id) {
    event.stopPropagation();
    const dropdowns = document.querySelectorAll('.dropdown-menu');
    dropdowns.forEach(d => {
        if (d.id !== id) d.classList.add('hidden');
    });
    const target = document.getElementById(id);
    if (target) {
        target.classList.toggle('hidden');
    }
}

window.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu').forEach(d => d.classList.add('hidden'));
});
</script>

<!-- Penyesuaian Layout Konten Utama agar tidak tertutup Sidebar -->
<style>
@media (min-width: 1024px) {
    body {
        padding-left: 16rem; /* Lebar w-64 (256px) */
    }
}
</style>