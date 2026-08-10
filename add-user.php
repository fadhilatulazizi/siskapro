<?php
include 'koneksi.php';
include 'auth.php';
requireLogin();

// Hanya admin yang boleh mengakses halaman ini
if ($_SESSION['username'] !== 'admin') {
    die("Akses ditolak.");
}

$pesan = '';
$edit_data = null;

// Proses Hapus User
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    // Cegah admin menghapus akunnya sendiri
    $stmt_cek = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt_cek->bind_param("i", $id_hapus);
    $stmt_cek->execute();
    $res_cek = $stmt_cek->get_result()->fetch_assoc();

    if ($res_cek && $res_cek['username'] === 'admin') {
        $pesan = "Gagal: Akun administrator utama tidak dapat dihapus!";
    } else {
        $stmt_del = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt_del->bind_param("i", $id_hapus);
        if ($stmt_del->execute()) {
            $pesan = "Akun berhasil dihapus!";
        } else {
            $pesan = "Gagal menghapus akun: " . $conn->error;
        }
    }
}

// Jika ada parameter edit=id, ambil data untuk diedit
if (isset($_GET['edit'])) {
    $id_edit = $_GET['edit'];
    $stmt_edit = $conn->prepare("SELECT id, username, kelas FROM users WHERE id = ?");
    $stmt_edit->bind_param("i", $id_edit);
    $stmt_edit->execute();
    $edit_data = $stmt_edit->get_result()->fetch_assoc();
}

// Proses Simpan (Tambah atau Update)
if (isset($_POST['submit'])) {
    $id = $_POST['id'] ?? '';
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $tipe_akses = $_POST['tipe_akses'] ?? '';
    $kelas = NULL;

    if ($tipe_akses === 'bk') {
        if (!empty($_POST['bk_kelas']) && is_array($_POST['bk_kelas'])) {
            $kelas = implode(', ', $_POST['bk_kelas']);
        } else {
            $kelas = '';
        }
    } elseif ($tipe_akses === 'walikelas') {
        $kelas = $_POST['kelas_walikelas'] ?? NULL;
    } else {
        $kelas = NULL;
    }

    if (!empty($id)) {
        // --- PROSES UPDATE ---
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt_check->bind_param("si", $username, $id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $pesan = "Gagal: Username '$username' sudah digunakan akun lain!";
        } else {
            if (!empty($password)) {
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt_update = $conn->prepare("UPDATE users SET username = ?, password = ?, kelas = ? WHERE id = ?");
                $stmt_update->bind_param("sssi", $username, $password_hashed, $kelas, $id);
            } else {
                $stmt_update = $conn->prepare("UPDATE users SET username = ?, kelas = ? WHERE id = ?");
                $stmt_update->bind_param("ssi", $username, $kelas, $id);
            }

            if ($stmt_update->execute()) {
                header("Location: add-user.php?status=sukses_update");
                exit;
            } else {
                $pesan = "Gagal memperbarui: " . $conn->error;
            }
        }
    } else {
        // --- PROSES TAMBAH BARU ---
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $pesan = "Gagal: Username '$username' sudah terdaftar!";
        } else {
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, kelas) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $password_hashed, $kelas);
            
            if ($stmt->execute()) {
                header("Location: add-user.php?status=sukses_tambah");
                exit;
            } else {
                $pesan = "Gagal menambahkan: " . $conn->error;
            }
        }
    }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'sukses_tambah') $pesan = "Akun berhasil ditambahkan!";
    if ($_GET['status'] === 'sukses_update') $pesan = "Akun berhasil diperbarui!";
}

// Daftar kelas untuk checkbox BK
$daftar_kelas = [
    'Tingkat 7' => ['7 A', '7 B', '7 C', '7 D', '7 E', '7 F', '7 G'],
    'Tingkat 8' => ['8 A', '8 B', '8 C', '8 D', '8 E', '8 F', '8 G'],
    'Tingkat 9' => ['9 A', '9 B', '9 C', '9 D', '9 E', '9 F', '9 G']
];

// Ambil semua data user untuk ditampilkan di tabel bawah
$result_users = $conn->query("SELECT id, username, kelas FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Akun - Sistem Akademik</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
::-webkit-scrollbar{width:8px;height:8px}
::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:20px}
body{background:#f8fafc}
</style>
<script>
function toggleAksesForm() {
    const tipe = document.getElementById('tipe_akses').value;
    const boxWalikelas = document.getElementById('box_walikelas');
    const boxBk = document.getElementById('box_bk');

    boxWalikelas.classList.add('hidden');
    boxBk.classList.add('hidden');

    if (tipe === 'walikelas') {
        boxWalikelas.classList.remove('hidden');
    } else if (tipe === 'bk') {
        boxBk.classList.remove('hidden');
    }
}
</script>
</head>
<body onload="toggleAksesForm()">
    <?php include 'nav.php'; ?>

<div class="max-w-4xl mx-auto px-4 py-8 space-y-8">
    
    <!-- FORM TAMBAH / EDIT AKUN -->
    <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-xl font-bold text-slate-800">
                <?= $edit_data ? 'Edit Akun: ' . htmlspecialchars($edit_data['username']) : 'Tambah Akun Pengelola / Wali Kelas / BK' ?>
            </h1>
            <?php if($edit_data): ?>
                <a href="add-user.php" class="text-xs bg-slate-100 text-slate-600 px-3 py-1.5 rounded-xl hover:bg-slate-200 transition font-medium">Batal Edit</a>
            <?php endif; ?>
        </div>

        <?php if($pesan): ?><div class="mb-4 p-3 bg-indigo-50 text-indigo-700 text-sm rounded-xl font-medium"><?= $pesan ?></div><?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>">
            
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Username</label>
                <input type="text" name="username" required value="<?= htmlspecialchars($edit_data['username'] ?? '') ?>" placeholder="Contoh: eka_bk atau walikelas_7a" class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl text-sm focus:outline-none focus:border-indigo-500">
                <p class="text-xs text-slate-400 mt-1">Jika membuat akun BK, pastikan berakhiran <b>_bk</b> (Contoh: <code>eka_bk</code>).</p>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Password</label>
                <input type="password" name="password" <?= $edit_data ? '' : 'required' ?> placeholder="••••••••" class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl text-sm focus:outline-none focus:border-indigo-500">
                <?php if($edit_data): ?><p class="text-xs text-slate-400 mt-1">Kosongkan jika password tidak ingin diubah.</p><?php endif; ?>
            </div>

            <?php
            // Tentukan status default form saat mode edit
            $current_kelas = $edit_data['kelas'] ?? '';
            $default_tipe = 'admin';
            if ($edit_data) {
                if ($edit_data['username'] === 'admin') {
                    $default_tipe = 'admin';
                } elseif (str_ends_with($edit_data['username'], '_bk') || str_contains($current_kelas, ',')) {
                    $default_tipe = 'bk';
                } elseif (!empty($current_kelas)) {
                    $default_tipe = 'walikelas';
                }
            }
            $saved_classes = explode(', ', $current_kelas);
            ?>

            <div>
                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Tipe Hak Akses</label>
                <select id="tipe_akses" name="tipe_akses" onchange="toggleAksesForm()" class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl text-sm focus:outline-none focus:border-indigo-500">
                    <option value="admin" <?= $default_tipe === 'admin' ? 'selected' : '' ?>>-- Administrator Pusat (Semua Kelas) --</option>
                    <option value="bk" <?= $default_tipe === 'bk' ? 'selected' : '' ?>>Bimbingan Konseling (BK) - Pilih Kelas Binaan</option>
                    <option value="walikelas" <?= $default_tipe === 'walikelas' ? 'selected' : '' ?>>Wali Kelas - Pilih Satu Kelas</option>
                </select>
            </div>

            <!-- Pilihan untuk Wali Kelas -->
            <div id="box_walikelas" class="hidden">
                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Pilih Kelas Wali Kelas</label>
                <select name="kelas_walikelas" class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl text-sm focus:outline-none focus:border-indigo-500">
                    <?php 
                    $all_classes = [
                        'Tingkat 7' => ['7 A', '7 B', '7 C', '7 D', '7 E', '7 F', '7 G'],
                        'Tingkat 8' => ['8 A', '8 B', '8 C', '8 D', '8 E', '8 F', '8 G'],
                        'Tingkat 9' => ['9 A', '9 B', '9 C', '9 D', '9 E', '9 F', '9 G']
                    ];
                    foreach($all_classes as $optgroup => $classes):
                    ?>
                        <optgroup label="<?= $optgroup ?>">
                            <?php foreach($classes as $c): ?>
                                <option value="<?= $c ?>" <?= ($current_kelas === $c) ? 'selected' : '' ?>>Kelas <?= $c ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Pilihan untuk BK (Checkbox) -->
            <div id="box_bk" class="hidden space-y-3">
                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Centang Kelas Binaan BK</label>
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-4 max-h-60 overflow-y-auto">
                    <?php foreach($daftar_kelas as $tingkat => $list_kelas): ?>
                        <div>
                            <span class="text-xs font-bold text-indigo-600 block mb-2"><?= $tingkat ?></span>
                            <div class="grid grid-cols-4 gap-2">
                                <?php foreach($list_kelas as $kls): ?>
                                    <label class="flex items-center gap-2 text-xs bg-white p-2 border border-slate-200 rounded-xl cursor-pointer hover:border-indigo-400">
                                        <input type="checkbox" name="bk_kelas[]" value="<?= $kls ?>" <?= in_array($kls, $saved_classes) ? 'checked' : '' ?> class="rounded text-indigo-600 focus:ring-indigo-500">
                                        <span><?= $kls ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" name="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white p-3 rounded-xl font-medium text-sm transition shadow-sm shadow-indigo-100">
                <?= $edit_data ? 'Simpan Perubahan' : 'Simpan Akun' ?>
            </button>
        </form>
    </div>

    <!-- TABEL DAFTAR AKUN DI BAGIAN BAWAH -->
    <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
        <h2 class="text-lg font-bold text-slate-800 mb-4">Daftar Akun Terdaftar</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider">
                        <th class="py-3 px-3">No</th>
                        <th class="py-3 px-3">Username</th>
                        <th class="py-3 px-3">Hak Akses / Kelas Binaan</th>
                        <th class="py-3 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    <?php 
                    $no = 1;
                    if ($result_users->num_rows > 0):
                        while($row = $result_users->fetch_assoc()):
                            // Tentukan label peran
                            $role_label = 'Administrator Pusat';
                            if (str_ends_with($row['username'], '_bk') || str_contains($row['kelas'], ',')) {
                                $role_label = 'Guru BK (Bina: ' . ($row['kelas'] ? $row['kelas'] : 'Semua') . ')';
                            } elseif (!empty($row['kelas'])) {
                                $role_label = 'Wali Kelas ' . $row['kelas'];
                            }
                    ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-3"><?= $no++ ?></td>
                            <td class="py-3 px-3 font-semibold text-indigo-600"><?= htmlspecialchars($row['username']) ?></td>
                            <td class="py-3 px-3"><?= htmlspecialchars($role_label) ?></td>
                            <td class="py-3 px-3 text-center space-x-2">
                                <a href="add-user.php?edit=<?= $row['id'] ?>" class="text-indigo-600 hover:underline bg-indigo-50 px-2.5 py-1 rounded-lg">Edit</a>
                                <?php if($row['username'] !== 'admin'): ?>
                                    <a href="add-user.php?hapus=<?= $row['id'] ?>" onclick="return confirm('Yakin ingin menghapus akun ini?')" class="text-rose-600 hover:underline bg-rose-50 px-2.5 py-1 rounded-lg">Hapus</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-slate-400">Belum ada akun lain yang terdaftar.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>