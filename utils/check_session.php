<?php
session_start();
require_once 'config.php'; 

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

function checkSession($checkRole = true, $allowedRoles = ['studente', 'docente']) {
    if (!isset($_SESSION['user'])) {
        header("Location: ../utils/logout.php");
        exit;
    }

    global $conn;
    if (!isset($conn)) {
        die("Errore: Connessione al database non disponibile.");
    }

    $id_user = $_SESSION['user']['id_user'];
    $session_id = session_id();

    // Controllo validità della sessione
    $sql = "SELECT data_scadenza FROM sessions WHERE session_id = ? AND id_user = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $session_id, $id_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $data_scadenza = new DateTime($row['data_scadenza']);
        $adesso = new DateTime();

        if ($adesso > $data_scadenza) {
            header("Location: ../utils/logout.php");
            exit;
        }
    } else {
        header("Location: ../utils/logout.php");
        exit;
    }

  
    if ($checkRole) {
        $ruoli = $_SESSION['user']['roles'] ?? [];
        if (!is_array($ruoli)) {
            $ruoli = [$ruoli]; 
        }
        $ruoli = array_map('strtolower', $ruoli);

        // Se l'utente non ha un ruolo consentito, reindirizza
        if (!array_intersect($ruoli, $allowedRoles)) {
            header("Location: ../index.php");
            exit;
        }
    }
}
?>
