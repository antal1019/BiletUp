<?php
require_once "config/database.php";

// Eliminăm orice sesiune anterioară pentru a avea un test curat
session_start();
session_destroy();
session_start();

// Informații pentru admin
$email = "admin@biletup.ro";
$password = "admin123";

// Afișăm informații despre parola din baza de date
echo "<h3>Informații despre utilizatorul admin din baza de date:</h3>";
$sql = "SELECT * FROM users WHERE email = '$email'";
$result = $conn->query($sql);

if($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    echo "ID: " . $user['id'] . "<br>";
    echo "Nume: " . $user['nume'] . " " . $user['prenume'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Hash parola: " . $user['parola'] . "<br>";
    echo "Rol: " . $user['rol'] . "<br><br>";
    
    // Testăm verificarea parolei
    echo "<h3>Test verificare parolă:</h3>";
    
    // Metoda 1: password_verify (bcrypt)
    echo "Verificare cu password_verify(): ";
    if(password_verify($password, $user['parola'])) {
        echo "<span style='color:green'>SUCCES</span><br>";
    } else {
        echo "<span style='color:red'>EȘEC</span><br>";
        
        // Verificăm formatul hash-ului din baza de date
        echo "Format hash: ";
        if(strpos($user['parola'], '$2y$') === 0) {
            echo "bcrypt (format corect pentru password_verify)<br>";
        } else {
            echo "alt format (nu este compatibil cu password_verify)<br>";
        }
    }
    
    // Metoda 2: md5 (metodă veche)
    echo "Verificare cu MD5: ";
    if(md5($password) === $user['parola']) {
        echo "<span style='color:green'>SUCCES</span><br>";
    } else {
        echo "<span style='color:red'>EȘEC</span><br>";
    }
    
    // Metoda 3: sha1 (metodă veche)
    echo "Verificare cu SHA1: ";
    if(sha1($password) === $user['parola']) {
        echo "<span style='color:green'>SUCCES</span><br>";
    } else {
        echo "<span style='color:red'>EȘEC</span><br>";
    }
    
    // Simulare login manual
    echo "<h3>Creare sesiune de admin manual:</h3>";
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nume'] = $user['nume'] . ' ' . $user['prenume'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['rol'];
    
    echo "Sesiune creată! Acum ești logat ca admin.<br>";
    echo "<a href='admin/index.php' class='btn btn-primary'>Mergi la panoul de administrare</a>";
    
} else {
    echo "Utilizatorul cu email '$email' nu a fost găsit în baza de date.";
}
?>