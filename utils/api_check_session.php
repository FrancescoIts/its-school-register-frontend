<?php
session_start();
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json'); // IMPORTANTE: rispondiamo in JSON

// Imposta i parametri di default
$checkRole = true;
$allowedRoles = ['studente', 'docente', 'admin', 'sadmin'];

// 1) Controllo connessione DB
if (!isset($conn)) {
    echo json_encode(["session_active" => false, "error" => "Errore: Connessione al database non disponibile."]);
    exit;
}

// 2) Se la sessione è già attiva in PHP
if (isset($_SESSION['user'])) {
    $userData = $_SESSION['user'];

    // Controllo ruoli
    if ($checkRole) {
        $ruoli = $userData['roles'] ?? [];
        if (!is_array($ruoli)) {
            $ruoli = [$ruoli];
        }
        $ruoli = array_map('strtolower', $ruoli);

        if (!array_intersect($ruoli, $allowedRoles)) {
            // Invece di fare redirect, restituiamo JSON
            echo json_encode(["session_active" => false, "error" => "Ruolo non autorizzato."]);
            exit;
        }
    }

    // Se i ruoli vanno bene, sessione attiva
    echo json_encode(["session_active" => true, "user" => $userData]);
    exit;
}

// 3) Recupera l'ID di sessione attuale
$session_id = session_id();

// 4) Query per controllare la sessione
$sql = "SELECT id_user, session_id, data_scadenza, session_data 
        FROM sessions 
        WHERE id_user = (SELECT id_user FROM sessions WHERE session_id = ? LIMIT 1)
        AND data_scadenza > NOW()
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $session_id);
$stmt->execute();
$result = $stmt->get_result();

// 5) Se non troviamo la sessione nel DB
if (!$row = $result->fetch_assoc()) {
    echo json_encode(["session_active" => false, "error" => "Sessione non valida."]);
    exit;
}

$stmt->close();

// 6) Se l'ID nel DB è diverso dalla sessione attuale, ricarichiamo la sessione
if ($row['session_id'] !== $session_id) {
    session_id($row['session_id']);
    session_start();
}

// 7) Decodifica i dati utente
$userData = json_decode($row['session_data'], true);
if (!$userData) {
    echo json_encode(["session_active" => false, "error" => "Dati della sessione non validi."]);
    exit;
}

// 8) Salva in $_SESSION['user']
$_SESSION['user'] = $userData;

// 9) Controllo ruoli, se richiesto
if ($checkRole) {
    $ruoli = $userData['roles'] ?? [];
    if (!is_array($ruoli)) {
        $ruoli = [$ruoli];
    }
    $ruoli = array_map('strtolower', $ruoli);

    if (!array_intersect($ruoli, $allowedRoles)) {
        echo json_encode(["session_active" => false, "error" => "Ruolo non autorizzato."]);
        exit;
    }
}

// 10) Tutto OK, restituisci JSON con i dati
echo json_encode(["session_active" => true, "user" => $userData]);
exit;
