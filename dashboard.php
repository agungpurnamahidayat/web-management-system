
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/database.php";

// Menghitung jumlah seluruh pengguna
$queryTotal = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$totalUsers = mysqli_fetch_assoc($queryTotal)["total"];

// Menghitung jumlah admin
$queryAdmin = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users WHERE role = 'admin'"
);
$totalAdmin = mysqli_fetch_assoc($queryAdmin)["total"];

// Menghitung jumlah staff
$queryStaff = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users WHERE role = 'staff'"
);
$totalStaff = mysqli_fetch_assoc($queryStaff)["total"];

$name = $_SESSION["name"];
$role = $_SESSION["role"];
$initial = strtoupper(substr($name, 0, 1));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Web Management System</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="app-container">

    <!-- Sidebar -->
    
<aside class="sidebar">

    <div class="brand">
        WEB<span>MANAGEMENT</span>
    </div>

    <div class="menu-label">MENU UTAMA</div>

    <ul class="nav-menu">
        <li>
            <a href="dashboard.php" class="active">
                Dashboard
            </a>
        </li>
    </ul>

    <div class="menu-label">MANAJEMEN</div>

    <ul class="nav-menu">
        <li>
            <a href="pengguna/index.php">
                Pengguna
            </a>
        </li>

        <li>
            <a href="informasi/index.php">
                Data Informasi
            </a>
        </li>
    </ul>

    <!-- Logout -->
    <div class="sidebar-footer">
        <a href="logout.php">
            Keluar dari Sistem
        </a>
    </div>

</aside>


    <!-- Main Content -->
    <main class="main-content">

        <!-- Header -->
        <header class="topbar">

            <h2>Dashboard</h2>

            <div class="user-info">

                <div>
                    <div class="user-name">
                        <?= htmlspecialchars($name) ?>
                    </div>

                    <div class="user-role">
                        <?= htmlspecialchars(ucfirst($role)) ?>
                    </div>
                </div>

                <div class="avatar">
                    <?= htmlspecialchars($initial) ?>
                </div>

            </div>

        </header>

        <!-- Dashboard Content -->
        <section class="content">

            <div class="page-heading">
                <h1>Selamat Datang, <?= htmlspecialchars($name) ?>!</h1>

                <p>
                    Berikut adalah ringkasan sistem pengelolaan website
                    dan informasi.
                </p>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">

                <div class="stat-card">
                    <p>Total Pengguna</p>
                    <h2><?= $totalUsers ?></h2>
                </div>

                <div class="stat-card">
                    <p>Total Admin</p>
                    <h2><?= $totalAdmin ?></h2>
                </div>

                <div class="stat-card">
                    <p>Total Staff</p>
                    <h2><?= $totalStaff ?></h2>
                </div>

            </div>

            <!-- Information Panel -->
            <div class="panel">

                <h3>Informasi Sistem</h3>

                <p>
                    Web Management System merupakan aplikasi untuk
                    mengelola pengguna dan informasi secara terpusat.
                    Sistem ini dikembangkan menggunakan PHP dan MySQL.
                </p>

            </div>

            <!-- Modules -->
            <div class="panel">

                <h3>Modul Pengelolaan</h3>

                <br>

                <div class="module-grid">

                    <div class="module-card" id="modul-pengguna">
                        <h4>Manajemen Pengguna</h4>

                        <p>
                            Modul untuk mengelola akun, username,
                            dan hak akses pengguna sistem.
                        </p>
                    </div>

                    <div class="module-card" id="modul-informasi">
                        <h4>Pengelolaan Data Informasi</h4>

                        <p>
                            Modul untuk menambahkan, melihat,
                            mengubah, dan menghapus data informasi.
                        </p>
                    </div>

                </div>

            </div>

        </section>

        <footer class="footer">
            Web Management System &copy; <?= date("Y") ?>
        </footer>

    </main>

</div>

</body>
</html>
