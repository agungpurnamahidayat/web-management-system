
<?php
session_start();

// 1. Memastikan pengguna sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

// 2. Memastikan hanya admin yang boleh menghapus
if (strtolower($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Akses ditolak. Hanya admin yang dapat menghapus informasi.");
}

// 3. Memastikan request menggunakan POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

// 4. Validasi CSRF Token
$csrfToken = $_POST["csrf_token"] ?? "";

if (
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $csrfToken)
) {
    http_response_code(403);
    exit("Permintaan tidak valid. Silakan kembali dan coba lagi.");
}

// 5. Mengambil dan memvalidasi ID
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: index.php");
    exit;
}

// 6. Menghubungkan database
require_once "../config/database.php";

// 7. Menyiapkan query DELETE
$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM informasi WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);

// 8. Menjalankan query
if (mysqli_stmt_execute($stmt)) {

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);

        header("Location: index.php");
        exit;
    }
}

// 9. Menutup statement
mysqli_stmt_close($stmt);

// Jika gagal
http_response_code(500);
exit("Gagal menghapus informasi. Silakan coba kembali.");
