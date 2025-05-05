<?php
session_start();

// Distruge toate datele sesiunii
$_SESSION = array();

// Distruge cookie-ul de sesiune
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Distruge sesiunea
session_destroy();

// Setează un mesaj de logout
session_start();
$_SESSION['mesaj'] = "Te-ai deconectat cu succes!";
$_SESSION['tip_mesaj'] = "success";

// Redirecționează către pagina principală
header("Location: index.php");
exit;
?>