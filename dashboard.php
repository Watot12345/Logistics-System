<?php
session_start();

// Hardcoded username & password for demo
$valid_username = "admin";
$valid_password = "admin123";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username === $valid_username && $password === $valid_password) {
        $_SESSION['username'] = $username;
        header("Location: dashboard.php");
        exit;
    } else {
        $_SESSION['error'] = "Invalid username or password!";
        header("Location: index.php");
        exit;
    }
}

// If already logged in, show dashboard
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Logistics System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <header class="bg-gray-800 text-white p-4">
        <h1 class="text-xl font-bold">Logistics System Dashboard</h1>
        <p>Welcome, <?php echo $_SESSION['username']; ?> | <a href="logout.php" class="underline">Logout</a></p>
    </header>

    <main class="p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-4 rounded shadow">Warehouse Management</div>
            <div class="bg-white p-4 rounded shadow">Fleet Management</div>
            <div class="bg-white p-4 rounded shadow">Procurement</div>
            <div class="bg-white p-4 rounded shadow">Project Tracker</div>
        </div>
    </main>

</body>
</html>