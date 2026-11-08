
<?php
session_start();

// 1. Memastikan pengguna sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

// 2. Memastikan hanya admin yang dapat menghapus pengguna
if (strtolower($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Akses ditolak. Hanya admin yang dapat menghapus pengguna.");
}

// 3. Memastikan request menggunakan POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Metode request tidak diizinkan.");
}

// 4. Validasi CSRF Token
$csrfToken = $_POST["csrf_token"] ?? "";

if (
    !is_string($csrfToken) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $csrfToken)
) {
    http_response_code(403);
    exit("Permintaan tidak valid. Silakan kembali dan coba lagi.");
}

// 5. Mengambil dan memvalidasi ID pengguna
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(400);
    exit("ID pengguna tidak valid.");
}

// 6. Mencegah admin menghapus akunnya sendiri
if ((int)$id === (int)$_SESSION["user_id"]) {
    http_response_code(403);
    exit("Kamu tidak dapat menghapus akun sendiri.");
}

// 7. Koneksi database
require_once "../config/database.php";

// 8. Memulai transaksi database
if (!mysqli_begin_transaction($conn)) {
    http_response_code(500);
    exit("Gagal memulai transaksi database.");
}

try {

    // 9. Mengambil data pengguna dan mengunci baris
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, role FROM users WHERE id = ? FOR UPDATE"
    );

    if (!$stmt) {
        throw new Exception("Gagal menyiapkan query pengguna.");
    }

    mysqli_stmt_bind_param($stmt, "i", $id);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Gagal mengambil data pengguna.");
    }

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    // Memastikan pengguna tersedia
    if (!$user) {
        throw new Exception("Pengguna tidak ditemukan.");
    }

    // 10. Memastikan admin terakhir tidak dihapus
    if ($user["role"] === "admin") {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE role = 'admin' FOR UPDATE"
        );

        if (!$stmt) {
            throw new Exception("Gagal memeriksa jumlah admin.");
        }

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal menghitung jumlah admin.");
        }

        $adminResult = mysqli_stmt_get_result($stmt);
        $adminCount = mysqli_num_rows($adminResult);

        mysqli_stmt_close($stmt);

        if ($adminCount <= 1) {
            throw new Exception(
                "Admin terakhir tidak dapat dihapus."
            );
        }
    }

    // 11. Menghapus pengguna berdasarkan ID
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM users WHERE id = ?"
    );

    if (!$stmt) {
        throw new Exception("Gagal menyiapkan query DELETE.");
    }

    mysqli_stmt_bind_param($stmt, "i", $id);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Gagal menghapus pengguna.");
    }

    // Memastikan satu baris berhasil dihapus
    $affectedRows = mysqli_stmt_affected_rows($stmt);

    mysqli_stmt_close($stmt);

    if ($affectedRows !== 1) {
        throw new Exception("Data pengguna tidak berhasil dihapus.");
    }

    // 12. Menyimpan perubahan transaksi
    if (!mysqli_commit($conn)) {
        throw new Exception("Gagal menyimpan perubahan database.");
    }

    // Berhasil, kembali ke halaman pengguna
    header("Location: index.php");
    exit;

} catch (Throwable $e) {

    // Membatalkan transaksi jika terjadi kegagalan
    mysqli_rollback($conn);

    http_response_code(400);

    exit(htmlspecialchars($e->getMessage()));

}
