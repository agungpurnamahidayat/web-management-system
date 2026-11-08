
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM informasi WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $judul = trim($_POST["judul"] ?? "");
    $kategori = trim($_POST["kategori"] ?? "");
    $isi = trim($_POST["isi"] ?? "");
    $status = $_POST["status"] ?? "";

    if ($judul === "" || $kategori === "" || $isi === "") {
        $error = "Semua kolom wajib diisi.";
    } elseif (!in_array($status, ["Draft", "Published"], true)) {
        $error = "Status tidak valid.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE informasi
             SET judul = ?, kategori = ?, isi = ?, status = ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssssi",
            $judul,
            $kategori,
            $isi,
            $status,
            $id
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: index.php");
            exit;
        } else {
            $error = "Gagal memperbarui informasi.";
        }

        mysqli_stmt_close($stmt);
    }

    $data["judul"] = $judul;
    $data["kategori"] = $kategori;
    $data["isi"] = $isi;
    $data["status"] = $status;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Informasi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-container">

    <aside class="sidebar">
        <div class="brand">WEB<span>MANAGEMENT</span></div>

        <div class="menu-label">MENU UTAMA</div>
        <ul class="nav-menu">
            <li><a href="../dashboard.php">Dashboard</a></li>
        </ul>

        <div class="menu-label">MANAJEMEN</div>
        <ul class="nav-menu">
            <li><a href="index.php" class="active">Data Informasi</a></li>
        </ul>

        <div class="sidebar-footer">
            <a href="../logout.php">Keluar dari Sistem</a>
        </div>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <h2>Edit Informasi</h2>

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
                    <?= htmlspecialchars(strtoupper(substr($_SESSION["name"], 0, 1))) ?>
                </div>
            </div>
        </header>

        <section class="content">

            <div class="page-heading">
                <h1>Edit Data Informasi</h1>
                <p>Perbarui informasi yang sudah tersimpan.</p>
            </div>

            <div class="panel">

                <?php if ($error !== ""): ?>
                    <p style="color:red;">
                        <?= htmlspecialchars($error) ?>
                    </p>
                <?php endif; ?>

                <form method="POST">

                    <label>Judul Informasi</label><br>
                    <input type="text" name="judul"
                        maxlength="150" required
                        value="<?= htmlspecialchars($data["judul"]) ?>"
                        style="width:100%; padding:12px; margin:10px 0 20px;">

                    <label>Kategori</label><br>
                    <input type="text" name="kategori"
                        maxlength="100" required
                        value="<?= htmlspecialchars($data["kategori"]) ?>"
                        style="width:100%; padding:12px; margin:10px 0 20px;">

                    <label>Isi Informasi</label><br>
                    <textarea name="isi" rows="6" required
                        style="width:100%; padding:12px; margin:10px 0 20px;"><?= htmlspecialchars($data["isi"]) ?></textarea>

                    <label>Status</label><br>
                    <select name="status"
                        style="padding:12px; margin:10px 0 25px;">
                        <option value="Draft"
                            <?= $data["status"] === "Draft" ? "selected" : "" ?>>
                            Draft
                        </option>

                        <option value="Published"
                            <?= $data["status"] === "Published" ? "selected" : "" ?>>
                            Published
                        </option>
                    </select>

                    <br>

                    <button type="submit">Simpan Perubahan</button>
                    <a href="index.php">Kembali</a>

                </form>

            </div>
        </section>

    </main>
</div>

</body>
</html>
