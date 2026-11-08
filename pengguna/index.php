
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
    exit("Akses ditolak. Halaman ini hanya untuk admin.");
}

require_once "../config/database.php";

// 3. Membuat CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];

// 4. Mengambil parameter pencarian
$search = isset($_GET["search"]) && is_string($_GET["search"])
    ? trim($_GET["search"])
    : "";

// 5. Mengambil dan memvalidasi filter role
$roleFilter = isset($_GET["role"]) && is_string($_GET["role"])
    ? $_GET["role"]
    : "all";

if (!in_array($roleFilter, ["all", "admin", "staff"], true)) {
    $roleFilter = "all";
}

// 6. Membuat pola pencarian
$searchPattern = "%" . $search . "%";

// 7. Menyiapkan query pencarian dan filter
$sql = "
    SELECT id, name, username, role, created_at
    FROM users
    WHERE (? = '' OR name LIKE ? OR username LIKE ?)
    AND (? = 'all' OR role = ?)
    ORDER BY created_at DESC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Gagal menyiapkan query pengguna.");
}

// 8. Mengirim parameter ke database
mysqli_stmt_bind_param(
    $stmt,
    "sssss",
    $search,
    $searchPattern,
    $searchPattern,
    $roleFilter,
    $roleFilter
);

// 9. Menjalankan query
if (!mysqli_stmt_execute($stmt)) {
    die("Gagal mengambil data pengguna.");
}

$query = mysqli_stmt_get_result($stmt);

if (!$query) {
    die("Gagal membaca hasil pencarian.");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Pengguna</title>

    <link rel="stylesheet" href="../assets/css/style.css">
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

    <!-- Main Content -->
    <main class="main-content">

        <!-- Header -->
        <header class="topbar">

            <h2>Data Pengguna</h2>

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

        <!-- Content -->
        <section class="content">

            <div class="page-heading">

                <h1>Pengelolaan Data Pengguna</h1>

                <p>
                    Kelola akun dan hak akses pengguna dalam sistem.
                </p>

            </div>

            <div class="panel">

                <!-- Judul dan tombol tambah -->
                <div style="display:flex;
                            justify-content:space-between;
                            align-items:center;
                            flex-wrap:wrap;
                            gap:12px;">

                    <h3>Daftar Pengguna</h3>

                    <a href="tambah.php"
                       style="background:#1d4f82;
                              color:white;
                              padding:10px 16px;
                              border-radius:6px;
                              text-decoration:none;
                              font-size:13px;">

                        + Tambah Pengguna

                    </a>

                </div>

                <br>

                <!-- Form Search dan Filter -->
                <form method="GET"
                      action="index.php"
                      style="display:flex;
                             gap:10px;
                             flex-wrap:wrap;
                             align-items:center;
                             margin-bottom:20px;">

                    <input
                        type="text"
                        name="search"
                        placeholder="Cari nama atau username..."
                        value="<?= htmlspecialchars($search) ?>"
                        style="padding:10px;
                               border:1px solid #d1d5db;
                               border-radius:6px;
                               min-width:220px;
                               flex:1;"
                    >

                    <select
                        name="role"
                        style="padding:10px;
                               border:1px solid #d1d5db;
                               border-radius:6px;"
                    >

                        <option value="all"
                            <?= $roleFilter === "all" ? "selected" : "" ?>>
                            Semua Role
                        </option>

                        <option value="admin"
                            <?= $roleFilter === "admin" ? "selected" : "" ?>>
                            Admin
                        </option>

                        <option value="staff"
                            <?= $roleFilter === "staff" ? "selected" : "" ?>>
                            Staff
                        </option>

                    </select>

                    <button
                        type="submit"
                        style="background:#1d4f82;
                               color:white;
                               border:none;
                               padding:10px 16px;
                               border-radius:6px;
                               cursor:pointer;"
                    >
                        Cari
                    </button>

                    <a
                        href="index.php"
                        style="background:#e5e7eb;
                               color:#333;
                               padding:10px 16px;
                               border-radius:6px;
                               text-decoration:none;"
                    >
                        Reset
                    </a>

                </form>

                <!-- Tabel Pengguna -->
                <table width="100%" cellpadding="12">

                    <thead>

                        <tr style="background:#edf2f8;">

                            <th align="left">No.</th>
                            <th align="left">Nama</th>
                            <th align="left">Username</th>
                            <th align="left">Role</th>
                            <th align="left">Tanggal Dibuat</th>
                            <th align="left">Aksi</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (mysqli_num_rows($query) > 0): ?>

                            <?php $no = 1; ?>

                            <?php while ($row = mysqli_fetch_assoc($query)): ?>

                                <tr>

                                    <td><?= $no++ ?></td>

                                    <td>
                                        <?= htmlspecialchars($row["name"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($row["username"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(ucfirst($row["role"])) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($row["created_at"]) ?>
                                    </td>

                                    <td>

                                        <div style="display:flex;
                                                    gap:10px;
                                                    align-items:center;">

                                            <a href="edit.php?id=<?= (int)$row["id"] ?>">
                                                Edit
                                            </a>

                                            <?php if ((int)$row["id"] !== (int)$_SESSION["user_id"]): ?>

                                                <form
                                                    method="POST"
                                                    action="delete.php"
                                                    onsubmit="return confirm('Apakah kamu yakin ingin menghapus akun pengguna ini?');"
                                                    style="margin:0;"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= (int)$row["id"] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= htmlspecialchars($csrfToken) ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        style="background:#dc3545;
                                                               color:white;
                                                               border:none;
                                                               padding:6px 10px;
                                                               border-radius:4px;
                                                               cursor:pointer;"
                                                    >
                                                        Hapus
                                                    </button>

                                                </form>

                                            <?php else: ?>

                                                <span style="color:#888; font-size:12px;">
                                                    Akun Anda
                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="6" align="center">
                                    Data pengguna tidak ditemukan.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

                <?php mysqli_stmt_close($stmt); ?>

            </div>

        </section>

    </main>

</div>

</body>
</html>
