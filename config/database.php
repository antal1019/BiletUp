<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_biletup";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexiunea la baza de date a eșuat: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>