<?php
session_start();
require_once 'config.php'; 

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

function checkSession($checkRole = true, $allowedRoles = ['studente', 'docente']) {
    global $conn;

    if (!isset($conn)) {
        die("Errore: Connessione al database non disponibile.");
    }

    // Recupera l'id di sessione corrente
    $session_id = session_id();

    // Vai a prendere dal DB la sessione corrispondente
    $sql = "SELECT id_user, data_scadenza, session_data 
            FROM sessions 
            WHERE session_id = ? 
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $session_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Se non trovo nulla, utente non loggato / sessione inesistente
    if (!$row = $result->fetch_assoc()) {
        header("Location: ../utils/logout.php");
        exit;
    }

    $stmt->close();

    // Controllo scadenza
    $data_scadenza = new DateTime($row['data_scadenza']);
    $adesso = new DateTime();
    if ($adesso > $data_scadenza) {
        // Sessione scaduta
        header("Location: ../utils/logout.php");
        exit;
    }

    // Decodifico i dati utente (salvati come JSON)
    $userData = json_decode($row['session_data'], true);
    if (!$userData) {
        // Se per qualche ragione non riesco a decodificare
        header("Location: ../utils/logout.php");
        exit;
    }

    // *** (Facoltativo) *** 
    // Aggiorno la variabile di sessione locale,
    // così nei file che la usano esiste di nuovo:
    $_SESSION['user'] = $userData;

    // Se devo controllare il ruolo, lo faccio adesso
    if ($checkRole) {
        // $userData['roles'] dovrebbe essere un array di ruoli 
        // (es. ["studente","docente","admin"]).
        // Se per caso è una stringa, normalizzo
        $ruoli = $userData['roles'] ?? [];
        if (!is_array($ruoli)) {
            $ruoli = [$ruoli];
        }
        // Converto in minuscolo
        $ruoli = array_map('strtolower', $ruoli);

        // Se non c'è nessuna intersezione con i ruoli ammessi, blocco
        if (!array_intersect($ruoli, $allowedRoles)) {
            header("Location: ../index.php");
            exit;
        }
    }

    // Se arrivo qui, la sessione è valida e l’utente è nel ruolo giusto
    // Restituisco (o rendo disponibile) i dati utente completi
    return $userData;
}
?>
