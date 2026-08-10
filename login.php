<?php
include 'koneksi.php';
include 'auth.php';
include 'csrf.php';

$error = '';

if (isset($_POST['login'])) {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (login($username, $password)) {
        // Regenerate CSRF token after successful login
        regenerateCsrfToken();
        header('Location: index.php');
        exit;
    } else {
        $error = 'Username atau password salah';
    }
}

$csrfToken = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="max-w-md w-full mx-auto p-6">

    <div class="bg-white rounded-2xl shadow p-8">

        <h1 class="text-3xl font-bold text-gray-800 mb-2 text-center">
            Login
        </h1>

        <p class="text-gray-500 mb-6 text-center">
            Masuk untuk mengelola data siswa
        </p>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">

            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label class="block mb-2 font-medium">
                    Username
                </label>

                <input type="text"
                       name="username"
                       required
                       class="w-full border rounded-xl px-4 py-3">
            </div>

            <div>
                <label class="block mb-2 font-medium">
                    Password
                </label>

                <input type="password"
                       name="password"
                       required
                       class="w-full border rounded-xl px-4 py-3">
            </div>

            <button type="submit"
                    name="login"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl">
                Login
            </button>

        </form>

    </div>

</div>

</body>
</html>
