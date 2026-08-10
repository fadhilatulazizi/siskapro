<?php
include 'koneksi.php';
include 'auth.php';

requireLogin();

$kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';

// Validate class access
if (!empty($_SESSION['kelas']) && $_SESSION['kelas'] !== $kelas) {
    echo json_encode([]);
    exit;
}

if (!empty($kelas)) {
    $stmt = $conn->prepare("SELECT id, nama, nis FROM siswa WHERE kelas = ? ORDER BY nama ASC");
    $stmt->bind_param("s", $kelas);
    $stmt->execute();
    $students = $stmt->get_result();
    
    $result = [];
    while ($row = $students->fetch_assoc()) {
        $result[] = $row;
    }
    
    echo json_encode($result);
} else {
    echo json_encode([]);
}
?>
