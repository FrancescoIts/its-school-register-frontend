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

    // Se la sessione è già attiva in PHP, usiamola direttamente
    if (isset($_SESSION['user'])) {
        // Se devi controllare i ruoli anche quando la sessione è già caricata
        if ($checkRole) {
            $ruoli = $_SESSION['user']['roles'] ?? [];
            if (!is_array($ruoli)) {
                $ruoli = [$ruoli];
            }
            $ruoli = array_map('strtolower', $ruoli);

            if (!array_intersect($ruoli, $allowedRoles)) {
                header("Location: ../index.php");
                exit;
            }
        }
        return $_SESSION['user'];
    }

    // Recupera l'ID di sessione attuale
    $session_id = session_id();

    // Controlla nel database se c'è una sessione valida per questo utente
    $sql = "SELECT id_user, session_id, data_scadenza, session_data 
            FROM sessions 
            WHERE id_user = (SELECT id_user FROM sessions WHERE session_id = ? LIMIT 1)
            AND data_scadenza > NOW()
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $session_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Se non troviamo una sessione valida, disconnettiamo l'utente
    if (!$row = $result->fetch_assoc()) {
        header("Location: ../utils/logout.php");
        exit;
    }

    $stmt->close();

    // Se l'ID della sessione corrente è diverso da quello nel database, riprendiamo quella attiva
    if ($row['session_id'] !== $session_id) {
        session_id($row['session_id']);
        session_start();
    }

    // Decodifica i dati della sessione
    $userData = json_decode($row['session_data'], true);
    if (!$userData) {
        header("Location: ../utils/logout.php");
        exit;
    }

    // Memorizza i dati nella sessione corrente
    $_SESSION['user'] = $userData;

    // Controllo ruoli, se richiesto
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
