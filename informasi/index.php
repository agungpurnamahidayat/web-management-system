
<?php
session_start();

// 1. Memastikan pengguna sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";

// 2. Membuat CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];

// 3. Mengambil kata kunci pencarian
$search = isset($_GET["search"]) && is_string($_GET["search"])
    ? trim($_GET["search"])
    : "";

// 4. Mengambil filter kategori
$categoryFilter = isset($_GET["kategori"]) && is_string($_GET["kategori"])
    ? $_GET["kategori"]
    : "all";

// 5. Mengambil filter status
$statusFilter = isset($_GET["status"]) && is_string($_GET["status"])
    ? $_GET["status"]
    : "all";

// Memvalidasi status
if (!in_array($statusFilter, ["all", "Draft", "Published"], true)) {
    $statusFilter = "all";
}

// 6. Mengambil daftar kategori dari database
$categoryQuery = mysqli_query(
    $conn,
    "SELECT DISTINCT kategori
     FROM informasi
     WHERE kategori <> ''
     ORDER BY kategori ASC"
);

if (!$categoryQuery) {
    die("Gagal mengambil kategori informasi.");
}

// 7. Membuat pola pencarian
$searchPattern = "%" . $search . "%";

// 8. Menyiapkan query pencarian dan filter
$sql = "
    SELECT id, judul, kategori, isi, status, created_at
    FROM informasi
    WHERE (? = '' OR judul LIKE ?)
    AND (? = 'all' OR kategori = ?)
    AND (? = 'all' OR status = ?)
    ORDER BY created_at DESC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Gagal menyiapkan query informasi.");
}

// 9. Mengikat parameter
mysqli_stmt_bind_param(
    $stmt,
    "ssssss",
    $search,
    $searchPattern,
    $categoryFilter,
    $categoryFilter,
    $statusFilter,
    $statusFilter
);

// 10. Menjalankan query
if (!mysqli_stmt_execute($stmt)) {
    die("Gagal mengambil data informasi.");
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

    <title>Data Informasi</title>

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
                <a href="../pengguna/index.php">Pengguna</a>
            </li>

            <li>
                <a href="index.php" class="active">Data Informasi</a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <a href="../logout.php">Keluar dari Sistem</a>
        </div>

    </aside>

    <!-- Main Content -->
    <main class="main-content">

        <header class="topbar">

            <h2>Data Informasi</h2>

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

                <h1>Pengelolaan Data Informasi</h1>

                <p>
                    Kelola informasi dan pengumuman dalam sistem.
                </p>

            </div>

            <div class="panel">

                <!-- Judul dan tombol tambah -->
                <div style="display:flex;
                            justify-content:space-between;
                            align-items:center;
                            flex-wrap:wrap;
                            gap:12px;">

                    <h3>Daftar Informasi</h3>

                    <a href="tambah.php"
                       style="background:#1d4f82;
                              color:white;
                              padding:10px 16px;
                              border-radius:6px;
                              text-decoration:none;
                              font-size:13px;">

                        + Tambah Informasi

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

                    <!-- Pencarian judul -->
                    <input
                        type="text"
                        name="search"
                        placeholder="Cari judul informasi..."
                        value="<?= htmlspecialchars($search) ?>"
                        style="padding:10px;
                               border:1px solid #d1d5db;
                               border-radius:6px;
                               min-width:200px;
                               flex:1;"
                    >

                    <!-- Filter kategori -->
                    <select
                        name="kategori"
                        style="padding:10px;
                               border:1px solid #d1d5db;
                               border-radius:6px;"
                    >

                        <option value="all"
                            <?= $categoryFilter === "all" ? "selected" : "" ?>>
                            Semua Kategori
                        </option>

                        <?php while ($category = mysqli_fetch_assoc($categoryQuery)): ?>

                            <option
                                value="<?= htmlspecialchars($category["kategori"]) ?>"
                                <?= $categoryFilter === $category["kategori"] ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars($category["kategori"]) ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                    <!-- Filter status -->
                    <select
                        name="status"
                        style="padding:10px;
                               border:1px solid #d1d5db;
                               border-radius:6px;"
                    >

                        <option value="all"
                            <?= $statusFilter === "all" ? "selected" : "" ?>>
                            Semua Status
                        </option>

                        <option value="Draft"
                            <?= $statusFilter === "Draft" ? "selected" : "" ?>>
                            Draft
                        </option>

                        <option value="Published"
                            <?= $statusFilter === "Published" ? "selected" : "" ?>>
                            Published
                        </option>

                    </select>

                    <!-- Tombol cari -->
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

                    <!-- Tombol reset -->
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

                <!-- Tabel Informasi -->
                <table width="100%" cellpadding="12">

                    <thead>

                        <tr style="background:#edf2f8;">

                            <th align="left">No.</th>
                            <th align="left">Judul</th>
                            <th align="left">Kategori</th>
                            <th align="left">Status</th>
                            <th align="left">Tanggal</th>
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
                                        <?= htmlspecialchars($row["judul"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($row["kategori"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($row["status"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($row["created_at"]) ?>
                                    </td>

                                    
<td>
    <div style="display:flex;
                gap:10px;
                align-items:center;">

        <!-- Tombol Edit: Admin dan Staff -->
        <a href="edit.php?id=<?= (int)$row["id"] ?>">
            Edit
        </a>

        <!-- Tombol Hapus: Khusus Admin -->
        <?php if ($_SESSION["role"] === "admin"): ?>

            <form
                method="POST"
                action="delete.php"
                onsubmit="return confirm('Apakah kamu yakin ingin menghapus informasi ini?');"
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

        <?php endif; ?>

    </div>
</td>


                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6" align="center">
                                    Data informasi tidak ditemukan.
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
