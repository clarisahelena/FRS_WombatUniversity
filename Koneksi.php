<?php

$serverName = "localhost\\SQLEXPRESS01";
$database   = "app_db";

try {
    $conn = new PDO(
        "sqlsrv:Server=$serverName;Database=$database;TrustServerCertificate=true"
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>