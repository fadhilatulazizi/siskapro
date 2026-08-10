<?php
include 'koneksi.php';
include 'auth.php';
include 'csrf.php';

requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// =====================
// DATA SISWA
// =====================
$stmt = $conn->prepare("SELECT * FROM siswa WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();

if (!$siswa) {
    die("Siswa tidak ditemukan");
}

// Akses kelas
if (!empty($_SESSION['kelas']) && $_SESSION['kelas'] !== $siswa['kelas']) {
    die("Anda tidak memiliki akses ke siswa ini");
}

// =====================
// MAPEL
// =====================
$mapel = mysqli_query($conn, "SELECT * FROM mapel ORDER BY nama_mapel ASC");

// =====================
// NILAI LAMA (JIKA ADA)
// =====================
$nilai_lama = [];
$selected_mapel = 0;

if (isset($_GET['mapel_id'])) {
    $selected_mapel = (int)$_GET['mapel_id'];

    $stmt = $conn->prepare("
        SELECT * FROM nilai 
        WHERE siswa_id = ? AND mapel_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $id, $selected_mapel);
    $stmt->execute();
    $nilai_lama = $stmt->get_result()->fetch_assoc() ?? [];
}

// =====================
// SIMPAN / UPDATE
// =====================
if (isset($_POST['simpan'])) {

    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }

    $mapel_id = (int)$_POST['mapel_id'];

    $s1 = (int)$_POST['s1'];
    $s2 = (int)$_POST['s2'];
    $s3 = (int)$_POST['s3'];
    $s4 = (int)$_POST['s4'];
    $s5 = (int)$_POST['s5'];
    $s6 = (int)$_POST['s6'];
    $ujian = (int)$_POST['nilai_ujian'];

    // validasi
    $grades = [$s1,$s2,$s3,$s4,$s5,$s6,$ujian];
    foreach ($grades as $g) {
        if ($g < 0 || $g > 100) {
            die("<script>alert('Nilai harus 0-100 (contoh: 103 tidak valid karena maksimal 100)'); window.history.back();</script>");
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO nilai (
            siswa_id, mapel_id,
            s1,s2,s3,s4,s5,s6,
            nilai_ujian
        )
        VALUES (?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            s1=VALUES(s1),
            s2=VALUES(s2),
            s3=VALUES(s3),
            s4=VALUES(s4),
            s5=VALUES(s5),
            s6=VALUES(s6),
            nilai_ujian=VALUES(nilai_ujian)
    ");

    $stmt->bind_param(
        "iiiiiiiii",
        $id, $mapel_id,
        $s1,$s2,$s3,$s4,$s5,$s6,$ujian
    );

    $stmt->execute();

    regenerateCsrfToken();

    echo "<script>
        alert('Nilai berhasil disimpan');
        window.location.href='input_nilai.php?id=$id&mapel_id=$mapel_id';
    </script>";
}

$csrfToken = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Input Nilai</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">

<?php include 'nav.php'; ?>

<div class="max-w-5xl mx-auto py-10 px-4">

    <div class="bg-white rounded-2xl shadow p-6">

        <h1 class="text-2xl font-bold mb-4">Input / Update Nilai</h1>

        <div class="mb-4 text-sm text-gray-600">
            <p><b>Nama:</b> <?= $siswa['nama'] ?></p>
            <p><b>NIS:</b> <?= $siswa['nis'] ?></p>
            <p><b>Kelas:</b> <?= $siswa['kelas'] ?></p>
        </div>

        <!-- PILIH MAPEL -->
        <form method="GET" class="mb-6">
            <input type="hidden" name="id" value="<?= $id ?>">

            <label class="font-medium">Pilih Mata Pelajaran</label>

            <select name="mapel_id" onchange="this.form.submit()"
                class="w-full border rounded-xl px-4 py-3 mt-2">

                <option value="">-- Pilih Mapel --</option>

                <?php while($m = mysqli_fetch_assoc($mapel)): ?>
                    <option value="<?= $m['id'] ?>"
                        <?= $selected_mapel == $m['id'] ? 'selected' : '' ?>>
                        <?= $m['nama_mapel'] ?>
                    </option>
                <?php endwhile; ?>

            </select>
        </form>

        <!-- FORM NILAI -->
        <?php if ($selected_mapel > 0): ?>
        <form method="POST" class="space-y-5">

            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="mapel_id" value="<?= $selected_mapel ?>">

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">

                <?php
                function val($nilai_lama, $key){
                    return isset($nilai_lama[$key]) ? $nilai_lama[$key] : '';
                }
                ?>

                <input type="number" name="s1" min="0" max="100"
                    value="<?= val($nilai_lama,'s1') ?>"
                    class="border rounded-xl p-3" placeholder="S1">

                <input type="number" name="s2" min="0" max="100"
                    value="<?= val($nilai_lama,'s2') ?>"
                    class="border rounded-xl p-3" placeholder="S2">

                <input type="number" name="s3" min="0" max="100"
                    value="<?= val($nilai_lama,'s3') ?>"
                    class="border rounded-xl p-3" placeholder="S3">

                <input type="number" name="s4" min="0" max="100"
                    value="<?= val($nilai_lama,'s4') ?>"
                    class="border rounded-xl p-3" placeholder="S4">

                <input type="number" name="s5" min="0" max="100"
                    value="<?= val($nilai_lama,'s5') ?>"
                    class="border rounded-xl p-3" placeholder="S5">

                <input type="number" name="s6" min="0" max="100"
                    value="<?= val($nilai_lama,'s6') ?>"
                    class="border rounded-xl p-3" placeholder="S6">

            </div>

            <div>
                <label class="block mb-2 font-medium">Nilai Ujian</label>
                <input type="number" name="nilai_ujian" min="0" max="100"
                    value="<?= val($nilai_lama,'nilai_ujian') ?>"
                    class="w-full border rounded-xl p-3">
            </div>

            <button class="bg-blue-600 text-white px-6 py-3 rounded-xl">
                Simpan / Update
            </button>

        </form>
        <?php endif; ?>

    </div>

</div>

</body>
</html>