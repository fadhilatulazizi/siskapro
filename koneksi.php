<?php
$conn = mysqli_connect(
    "localhost",
    "bdtitlga_nilai",
    "Azizi0311!!",
    "bdtitlga_nilai"
);

if(!$conn){
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset to utf8mb4 for proper handling of special characters
if (!mysqli_set_charset($conn, "utf8mb4")) {
    die("Error setting charset: " . mysqli_error($conn));
}

// Enable error reporting for development (disable in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>
