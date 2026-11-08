
<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "web_management_system";

$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database
);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

?>
