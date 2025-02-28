<?php
session_start();
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

function checkSession($checkRole = true, $allowedRoles = ['studente', 'docente', 'admin', 'sadmin'])
{
    global $conn;

    if (!isset($conn)) {
        die("Errore: Connessione al database non disponibile.");
    }

    // Recupera l'ID di sessione attuale
    $session_id = session_id();

    // Query per controllare la sessione nel DB
    $sql = "SELECT id_user, session_id, data_scadenza, session_data 
            FROM sessions 
            WHERE session_id = ? 
            AND data_scadenza > NOW()
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $session_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Se non troviamo una sessione valida nel DB, disconnettiamo l'utente
    if (!$row = $result->fetch_assoc()) {
        header("Location: ../utils/logout.php");
        exit;
    }
    $stmt->close();

    // Se l'ID della sessione nel DB è diverso da quello attuale, aggiorniamo la sessione
    if ($row['session_id'] !== $session_id) {
        session_id($row['session_id']);
        session_start();
    }

    // Decodifica i dati della sessione memorizzati nel DB
    $userData = json_decode($row['session_data'], true);
    if (!$userData) {
        header("Location: ../utils/logout.php");
        exit;
    }

    // Imposta la sessione utente in PHP (aggiornata dal DB)
    $_SESSION['user'] = $userData;

    // Se richiesto, controlla che l'utente abbia uno dei ruoli consentiti
    if ($checkRole) {
        $ruoli = $userData['roles'] ?? [];
        if (!is_array($ruoli)) {
            $ruoli = [$ruoli];
        }
        $ruoli = array_map('strtolower', $ruoli);

        if (!array_intersect($ruoli, $allowedRoles)) {
            header("Location: ../index.php");
            exit;
        }
    }

    return $userData;
}
?>
