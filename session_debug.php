<?php
session_start();

echo "<h1>Informații Sesiune</h1>";
echo "<h2>Status autentificare</h2>";

if(isset($_SESSION['user_id'])) {
    echo "<p style='color:green;'>Utilizator autentificat! ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>Nume: " . $_SESSION['user_nume'] . "</p>";
    echo "<p>Email: " . $_SESSION['user_email'] . "</p>";
    echo "<p>Rol: " . $_SESSION['user_role'] . "</p>";
} else {
    echo "<p style='color:red;'>Utilizator neautentificat!</p>";
}

echo "<h2>Date sesiune complete</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Informații Cookie-uri</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>Informații PHP</h2>";
echo "<p>Session save path: " . session_save_path() . "</p>";
echo "<p>Session name: " . session_name() . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>PHP version: " . phpversion() . "</p>";

echo "<h2>Acțiuni</h2>";
echo "<a href='login.php'>Mergi la Pagina de Login</a><br>";
echo "<a href='index.php'>Mergi la Pagina Principală</a><br>";
?>