<?php
include 'koneksi.php';
include 'auth.php';

requireLogin();

// Get filter parameters
$filter_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$filter_nis = isset($_GET['nis']) ? $_GET['nis'] : '';

// Build query with filters
$where_conditions = [];
$params = [];
$types = '';

if (!empty($filter_kelas)) {
    $where_conditions[] = "kelas = ?";
    $params[] = $filter_kelas;
    $types .= 's';
}

if (!empty($filter_nis)) {
    $where_conditions[] = "nis = ?";
    $params[] = $filter_nis;
    $types .= 's';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get students with filters
if (!empty($params)) {
    $stmt = $conn->prepare("SELECT * FROM siswa $where_clause ORDER BY kelas, nama ASC");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $students = $stmt->get_result();
} else {
    $students = $conn->query("SELECT * FROM siswa ORDER BY kelas, nama ASC");
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="contoh_nilai.csv"');

// Create file pointer
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header row with semicolon delimiter
fwrite($output, "NIS;Nama Siswa;Kelas;S1;S2;S3;S4;S5;S6;Nilai_Ujian\n");

// Write data rows with semicolon delimiter
while ($row = $students->fetch_assoc()) {
    // Add tab before class to force Excel to treat as text
    $kelas_text = "\t" . $row['kelas'];
    
    fwrite($output, $row['nis'] . ';' . $row['nama'] . ';' . $kelas_text . ';' . 
                   '0.0;0.0;0.0;0.0;0.0;0.0;0.0' . "\n");
}

fclose($output);
?>
