<?php
// Autor: Sebastian Rieg
// Datenbankverbindung via PDO

$host     = "92.205.168.232";
$username = "root";
$password = "3J8+dNy8i3#u";
$dbname   = "gruppe6";

try {
    $verbindung = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}
