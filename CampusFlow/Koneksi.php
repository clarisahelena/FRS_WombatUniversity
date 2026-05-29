<?php
// ============================================================
// Koneksi.php
// Membuat koneksi ke database SQL Server menggunakan PDO.
// File ini di-include oleh semua halaman yang membutuhkan
// akses database (dashboard, frs, history, dll).
// ============================================================

$serverName = "localhost";   // Nama/alamat SQL Server instance
$database   = "app_db";      // Nama database yang digunakan
$username   = "app_user";    // Username SQL Server
$password   = "PasswordKuat_123!"; // Password SQL Server

try {
    // Buat koneksi PDO ke SQL Server menggunakan driver sqlsrv.
    // TrustServerCertificate=true agar tidak perlu sertifikat SSL valid
    // (cocok untuk environment lokal/development).
    $conn = new PDO(
        "sqlsrv:Server=$serverName;Database=$database;TrustServerCertificate=true",
        $username,
        $password
    );

    // Set mode error PDO ke EXCEPTION agar setiap query gagal
    // langsung melempar exception (lebih mudah di-debug)
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Hentikan eksekusi dan tampilkan pesan error jika koneksi gagal
    die("Koneksi gagal: " . $e->getMessage());
}
