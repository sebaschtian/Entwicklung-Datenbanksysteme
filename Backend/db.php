<?php
$servername = "localhost";
$username = "root";
$password = "3J8+dNy8i3#u";
$dbname = "gruppe6";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
?>
