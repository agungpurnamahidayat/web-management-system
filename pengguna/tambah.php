
<?php
session_start();

// Memastikan pengguna sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

// Membatasi akses hanya untuk admin
if (strtolower($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Akses ditolak. Hanya admin yang dapat menambahkan pengguna.");
}

require_once "../config/database.php";

// Membuat CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";

$name = "";
$username = "";
$role = "staff";

// Memproses form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "";
    $csrfToken = $_POST["csrf_token"] ?? "";

    // Validasi CSRF
    if (
        !hash_equals($_SESSION["csrf_token"], $csrfToken)
    ) {
        $error = "Permintaan tidak valid. Silakan muat ulang halaman.";

    } elseif (
        $name === "" ||
        $username === "" ||
        $password === ""
    ) {
        $error = "Semua kolom wajib diisi.";

    } elseif (strlen($name) > 100) {
        $error = "Nama maksimal 100 karakter.";

    } elseif (strlen($username) > 50) {
        $error = "Username maksimal 50 karakter.";

    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter.";

    } elseif (!in_array($role, ["admin", "staff"], true)) {
        $error = "Role tidak valid.";

    } else {

        // Mengamankan password
        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        // Menyimpan pengguna baru
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO users (name, username, password, role)
             VALUES (?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssss",
            $name,
            $username,
            $hashedPassword,
            $role
        );

        if (mysqli_stmt_execute($stmt)) {

            mysqli_stmt_close($stmt);

            header("Location: index.php");
            exit;

        } else {

            if (mysqli_stmt_errno($stmt) === 1062) {
                $error = "Username sudah digunakan.";
            } else {
                $error = "Gagal menambahkan pengguna.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tambah Pengguna</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="app-container">

    <aside class="sidebar">

        <div class="brand">
            WEB<span>MANAGEMENT</span>
        </div>

        <div class="menu-label">MENU UTAMA</div>

        <ul class="nav-menu">
            <li>
                <a href="../dashboard.php">Dashboard</a>
            </li>
        </ul>

        <div class="menu-label">MANAJEMEN</div>

        <ul class="nav-menu">
            <li>
                <a href="index.php" class="active">Pengguna</a>
            </li>

            <li>
                <a href="../informasi/index.php">Data Informasi</a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <a href="../logout.php">Keluar dari Sistem</a>
        </div>

    </aside>

    <main class="main-content">

        <header class="topbar">

            <h2>Tambah Pengguna</h2>

            <div class="user-info">

                <div>
                    <div class="user-name">
                        <?= htmlspecialchars($_SESSION["name"]) ?>
                    </div>

                    <div class="user-role">
                        <?= htmlspecialchars(ucfirst($_SESSION["role"])) ?>
                    </div>
                </div>

                <div class="avatar">
                    <?= htmlspecialchars(
                        strtoupper(substr($_SESSION["name"], 0, 1))
                    ) ?>
                </div>

            </div>

        </header>

        <section class="content">

            <div class="page-heading">
                <h1>Tambah Pengguna Baru</h1>

                <p>
                    Tambahkan akun dan tentukan hak akses pengguna.
                </p>
            </div>

            <div class="panel">

                <?php if ($error !== ""): ?>

                    <p style="color:red;">
                        <?= htmlspecialchars($error) ?>
                    </p>

                <?php endif; ?>

                <form method="POST">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>"
                    >

                    <label>Nama Lengkap</label><br>

                    <input
                        type="text"
                        name="name"
                        maxlength="100"
                        required
                        value="<?= htmlspecialchars($name) ?>"
                        style="width:100%; padding:12px; margin:10px 0 20px;"
                    >

                    <label>Username</label><br>

                    <input
                        type="text"
                        name="username"
                        maxlength="50"
                        required
                        value="<?= htmlspecialchars($username) ?>"
                        style="width:100%; padding:12px; margin:10px 0 20px;"
                    >

                    <label>Password</label><br>

                    <input
                        type="password"
                        name="password"
                        minlength="8"
                        required
                        style="width:100%; padding:12px; margin:10px 0 20px;"
                    >

                    <label>Role Pengguna</label><br>

                    <select
                        name="role"
                        required
                        style="padding:12px; margin:10px 0 25px;"
                    >

                        <option value="staff"
                            <?= $role === "staff" ? "selected" : "" ?>>
                            Staff
                        </option>

                        <option value="admin"
                            <?= $role === "admin" ? "selected" : "" ?>>
                            Admin
                        </option>

                    </select>

                    <br>

                    <button type="submit">
                        Simpan Pengguna
                    </button>

                    <a href="index.php">
                        Kembali
                    </a>

                </form>

            </div>

        </section>

    </main>

</div>

</body>
</html>
