<?php
require_once "config/database.php";

session_start();

// Obține utilizatorul admin
$admin_sql = "SELECT * FROM users WHERE email = 'admin@biletup.ro' LIMIT 1";
$result = $conn->query($admin_sql);

if($result->num_rows == 1) {
    $admin = $result->fetch_assoc();
    
    // Setează variabilele de sesiune
    $_SESSION['user_id'] = $admin['id'];
    $_SESSION['user_nume'] = $admin['nume'] . ' ' . $admin['prenume'];
    $_SESSION['user_email'] = $admin['email'];
    $_SESSION['user_role'] = $admin['rol'];
    
    echo "<p>Autentificare realizată cu succes! Acum ești logat ca admin.</p>";
    echo "<p><a href='admin/index.php'>Mergi la panoul de administrare</a></p>";
} else {
    echo "<p>Utilizatorul admin nu a fost găsit în baza de date.</p>";
}
?>