<?php
require_once "config/database.php";

// Definește parola de test
$test_password = "admin123";

echo "<h2>Verificare algoritmi de hash</h2>";

// Arată cum arată hash-ul cu algoritmul DEFAULT (bcrypt)
echo "<h3>Hash cu PASSWORD_DEFAULT (bcrypt):</h3>";
$default_hash = password_hash($test_password, PASSWORD_DEFAULT);
echo $default_hash . "<br>";
echo "Lungime: " . strlen($default_hash) . " caractere<br>";
echo "Verificare cu password_verify(): " . (password_verify($test_password, $default_hash) ? "SUCCES" : "EȘEC") . "<br><br>";

// Arată cum arată hash-ul cu MD5 (metodă veche)
echo "<h3>Hash cu MD5 (metodă veche):</h3>";
$md5_hash = md5($test_password);
echo $md5_hash . "<br>";
echo "Lungime: " . strlen($md5_hash) . " caractere<br>";
echo "Verificare cu password_verify(): " . (password_verify($test_password, $md5_hash) ? "SUCCES" : "EȘEC") . "<br><br>";

// Arată cum arată hash-ul cu SHA1 (metodă veche)
echo "<h3>Hash cu SHA1 (metodă veche):</h3>";
$sha1_hash = sha1($test_password);
echo $sha1_hash . "<br>";
echo "Lungime: " . strlen($sha1_hash) . " caractere<br>";
echo "Verificare cu password_verify(): " . (password_verify($test_password, $sha1_hash) ? "SUCCES" : "EȘEC") . "<br><br>";

// Verifică hash-ul din baza de date
echo "<h3>Hash din baza de date:</h3>";
$result = $conn->query("SELECT parola FROM users WHERE email = 'admin@biletup.ro'");
if($result->num_rows > 0) {
    $hash_din_bd = $result->fetch_assoc()['parola'];
    echo $hash_din_bd . "<br>";
    echo "Lungime: " . strlen($hash_din_bd) . " caractere<br>";
    echo "Verificare cu password_verify(): " . (password_verify($test_password, $hash_din_bd) ? "SUCCES" : "EȘEC") . "<br>";
    echo "Este hash MD5: " . (strlen($hash_din_bd) == 32 ? "POSIBIL" : "NU") . "<br>";
    echo "Este hash SHA1: " . (strlen($hash_din_bd) == 40 ? "POSIBIL" : "NU") . "<br>";
    echo "Este hash bcrypt: " . (strpos($hash_din_bd, '$2y$') === 0 ? "DA" : "NU") . "<br>";
} else {
    echo "Utilizatorul admin@biletup.ro nu există în baza de date.";
}
?>