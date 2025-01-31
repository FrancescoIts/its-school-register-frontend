<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
session_start();
require_once "../config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupero dati dal form
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Inizializza array sessione per errori e successi
    $_SESSION['errors'] = [];
    $_SESSION['success'] = [];

    // Validazione email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['errors'][] = "Formato email non valido.";
    } elseif (!str_ends_with($email, "@itssmartacademy.it")) {
        $_SESSION['errors'][] = "Formato email non valido!";
    }

    // Se ci sono errori 
    if (!empty($_SESSION['errors'])) {
        header("Location: ../index.php");
        exit();
    }

    // Query al DB
    $stmt = $conn->prepare("SELECT id, email, psw, utente FROM utenti_registro_elettronico WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // Se l'email esiste
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $db_email, $db_password, $utente);
        $stmt->fetch();

        // Verifica password
        if (hash('sha256', $password) === $db_password) {
            // salvo dati nel sess.
            $_SESSION['user_id'] = $id;
            $_SESSION['user_email'] = $db_email;
            $_SESSION['user_role'] = $utente;

            // OUTPUT PROVVISORIO
            $_SESSION['success'][] = "Login effettuato con successo come $utente!";
            header("Location: ../index.php");
            exit();
        } else {
            // Password sbagliata
            $_SESSION['errors'][] = "Password errata.";
        }
    } else {
        // L'email non c'è
        $_SESSION['errors'][] = "Email non registrata.";
    }

    $stmt->close();
    $conn->close();

    header("Location: ../index.php");
    exit();
}
?>
