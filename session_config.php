<?php
// Verificăm dacă sesiunea este deja pornită
if (session_status() == PHP_SESSION_NONE) {
    // Setări pentru cookie-uri de sesiune PHP
    ini_set('session.cookie_lifetime', 86400); // 24 ore
    ini_set('session.gc_maxlifetime', 86400); // 24 ore

    // Setează parametrii cookie-ului de sesiune
    session_set_cookie_params([
        'lifetime' => 86400, // 24 ore în secunde
        'path' => '/',      // Disponibil în tot site-ul
        'domain' => '',     // Domeniul curent
        'secure' => false,  // Setează true dacă folosești HTTPS
        'httponly' => true  // Previne accesul JavaScript la cookie
    ]);

    // Pornește sesiunea
    session_start();
}
?>