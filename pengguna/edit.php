
<?php
session_start();

// 1. Memastikan pengguna sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

// 2. Membatasi akses hanya untuk admin
if (strtolower($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Akses ditolak. Hanya admin yang dapat mengedit pengguna.");
}

require_once "../config/database.php";

// 3. Membuat CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// 4. Mengambil ID pengguna dari URL
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: index.php");
    exit;
}

// 5. Mengambil data pengguna berdasarkan ID
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, username, role FROM users WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data) {
    http_response_code(404);
    exit("Pengguna tidak ditemukan.");
}

$error = "";

// 6. Memproses form ketika disubmit
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
        $error = "Permintaan tidak valid. Muat ulang halaman.";

    } elseif (
        $name === "" ||
        $username === ""
    ) {
        $error = "Nama dan username wajib diisi.";

    } elseif (strlen($name) > 100) {
        $error = "Nama maksimal 100 karakter.";

    } elseif (strlen($username) > 50) {
        $error = "Username maksimal 50 karakter.";

    } elseif (
        $password !== "" && strlen($password) < 8
    ) {
        $error = "Password baru minimal 8 karakter.";

    } elseif (!in_array($role, ["admin", "staff"], true)) {
        $error = "Role tidak valid.";

    } elseif (
        (int)$id === (int)$_SESSION["user_id"] &&
        $role !== "admin"
    ) {
        $error = "Kamu tidak dapat mengubah role akun sendiri menjadi Staff.";

    } else {

        // Mencegah admin terakhir diturunkan menjadi Staff
        if ($data["role"] === "admin" && $role === "staff") {

            $adminCountQuery = mysqli_query(
                $conn,
                "SELECT COUNT(*) AS total FROM users WHERE role = 'admin'"
            );

            if (!$adminCountQuery) {
                $error = "Gagal memeriksa jumlah admin.";
            } else {
                $adminCount = mysqli_fetch_assoc($adminCountQuery)["total"];

                if ((int)$adminCount <= 1) {
                    $error = "Admin terakhir tidak dapat diubah menjadi Staff.";
                }
            }
        }

        // Jika validasi berhasil
        if ($error === "") {

            // Jika password diisi, perbarui password juga
            if ($password !== "") {

                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE users
                     SET name = ?, username = ?, password = ?, role = ?
                     WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssi",
                    $name,
                    $username,
                    $hashedPassword,
                    $role,
                    $id
                );

            } else {

                // Jika password kosong, password lama dipertahankan
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE users
                     SET name = ?, username = ?, role = ?
                     WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "sssi",
                    $name,
                    $username,
                    $role,
                    $id
                );
            }

            // Menjalankan UPDATE
            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                // Memperbarui session jika admin mengedit akunnya sendiri
                if ((int)$id === (int)$_SESSION["user_id"]) {
                    $_SESSION["name"] = $name;
                    $_SESSION["username"] = $username;
                    $_SESSION["role"] = $role;
                }

                header("Location: index.php");
                exit;

            } else {

                if (mysqli_stmt_errno($stmt) === 1062) {
                    $error = "Username sudah digunakan.";
                } else {
                    $error = "Gagal memperbarui data pengguna.";
                }

                mysqli_stmt_close($stmt);
            }
        }
    }

    // Mempertahankan input jika validasi gagal
    $data["name"] = $name;
    $data["username"] = $username;
    $data["role"] = $role;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Pengguna</title>

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

            <h2>Edit Pengguna</h2>

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

                <h1>Edit Data Pengguna</h1>

                <p>
                    Perbarui informasi akun dan hak akses pengguna.
                </p>

            </div>

            <div class="panel">

                <?php if ($error !== ""): ?>

                    <p style="color:red;">
                        <?= htmlspecialchars($error) ?>
                    </p>

                <?php endif; ?>

                <form method="POST"
                      action="edit.php?id=<?= (int)$id ?>">

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
                        value="<?= htmlspecialchars($data["name"]) ?>"
                        style="width:100%; padding:12px; margin:10px 0 20px;"
                    >

                    <label>Username</label><br>

                    <input
                        type="text"
                        name="username"
                        maxlength="50"
                        required
                        value="<?= htmlspecialchars($data["username"]) ?>"
                        style="width:100%; padding:12px; margin:10px 0 20px;"
                    >

                    <label>Password Baru</label><br>

                    <input
                        type="password"
                        name="password"
                        minlength="8"
                        autocomplete="new-password"
                        placeholder="Kosongkan jika tidak ingin mengubah password"
                        style="width:100%; padding:12px; margin:10px 0 20px;"
                    >

                    <label>Role Pengguna</label><br>

                    <select
                        name="role"
                        required
                        style="padding:12px; margin:10px 0 25px;"
                    >

                        <option value="staff"
                            <?= $data["role"] === "staff" ? "selected" : "" ?>>
                            Staff
                        </option>

                        <option value="admin"
                            <?= $data["role"] === "admin" ? "selected" : "" ?>>
                            Admin
                        </option>

                    </select>

                    <br>

                    <button type="submit">
                        Simpan Perubahan
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
