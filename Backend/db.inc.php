//Autor: Sebastian Rieg

<?php
$host = "92.205.168.232"; //IP Adresse (Pingen funktioniert)
$username = "root";
$password = "3J8+dNy8i3#u";
$dbname = "gruppe6";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
?>