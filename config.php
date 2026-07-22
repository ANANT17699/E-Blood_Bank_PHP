<?php
// Configuration & Database Connection for E-Blood Bank

$host = 'localhost';
$dbname = 'e_blood_bank';
$username = 'root'; 
$password = ''; 

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // If database does not exist yet, attempt to create it automatically
    if ($e->getCode() == 1049) {
        try {
            $pdo_init = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
            $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            
            // Execute database.sql schema if available
            $sql_file = __DIR__ . '/database.sql';
            if (file_exists($sql_file)) {
                $sql = file_get_contents($sql_file);
                $pdo->exec($sql);
            }
        } catch (PDOException $ex) {
            die("Database setup failed: " . $ex->getMessage());
        }
    } else {
        die("Connection failed: " . $e->getMessage() . "<br>Please ensure Apache & MySQL (XAMPP/phpMyAdmin) are running.");
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>