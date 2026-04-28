<?php
// Autor: Rieg
$username = "gruppe6";
$password = "3J8+dNy8i3#u";
$host = "92.205.168.232";
$db_name = "gruppe6";
$serverdaten = "mysql:host=$host;dbname=$db_name;charset=utf8";

try {
    $verbindung = new PDO($serverdaten, $username, $password);
    $verbindung->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Verbindungsfehler: " . $e->getMessage());
}