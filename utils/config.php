<?php // credenziali per connesione a db
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
$servername = "178.218.144.201";
$username = "Cesco";
$password = "bXC&QMSKavJHQH78sgL%";
$dbname = "testcesco";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connessione al database fallita: " . $conn->connect_error);
}
?>
    