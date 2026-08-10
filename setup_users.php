<?php
include 'koneksi.php';

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    kelas VARCHAR(10) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'users' created successfully or already exists.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Check if admin user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = 'admin'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Create default admin user (no class assigned - can see all)
    // Default password: admin123
    $username = 'admin';
    $kelas = NULL;
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, kelas) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $kelas);
    
    if ($stmt->execute()) {
        echo "Default admin user created successfully.<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
        echo "Class: All (admin can see all classes)<br>";
        echo "<strong>Please change the default password after first login!</strong><br>";
    } else {
        echo "Error creating admin user: " . $stmt->error . "<br>";
    }
} else {
    echo "Admin user already exists.<br>";
}

// Add option to create teacher with class assignment
echo "<br><hr><br>";
echo "<h3>Create Teacher User</h3>";
echo "<form method='POST'>";
echo "Username: <input type='text' name='new_username' required><br>";
echo "Password: <input type='password' name='new_password' required><br>";
echo "Class (e.g., 9A, 9B, 8A, etc.): <input type='text' name='new_kelas'><br>";
echo "<small>Leave class empty to allow viewing all classes</small><br>";
echo "<input type='submit' name='create_teacher' value='Create Teacher'>";
echo "</form>";

if (isset($_POST['create_teacher'])) {
    $new_username = trim($_POST['new_username']);
    $new_password = $_POST['new_password'];
    $new_kelas = !empty($_POST['new_kelas']) ? trim($_POST['new_kelas']) : NULL;
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $new_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<br><strong>Error: Username already exists!</strong><br>";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, kelas) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $new_username, $hashed_password, $new_kelas);
        
        if ($stmt->execute()) {
            echo "<br><strong>Teacher user created successfully!</strong><br>";
            echo "Username: " . htmlspecialchars($new_username) . "<br>";
            echo "Class: " . ($new_kelas ? htmlspecialchars($new_kelas) : "All classes") . "<br>";
        } else {
            echo "<br><strong>Error creating teacher user: " . $stmt->error . "</strong><br>";
        }
    }
}

echo "<br><a href='login.php'>Go to login page</a>";
?>
