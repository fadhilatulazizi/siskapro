<?php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

// Require login - redirect to login page if not logged in
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
}

// Login user
if (!function_exists('login')) {
    function login($username, $password) {
        global $conn;
        
        $stmt = $conn->prepare("SELECT id, username, password, kelas FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password (assuming password_hash was used)
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['kelas'] = $user['kelas']; // Store class assignment
                return true;
            }
        }
        
        return false;
    }
}

// Logout user
if (!function_exists('logout')) {
    function logout() {
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    }
}
?>
