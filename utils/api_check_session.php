<?php
session_start();
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');

$checkRole = true;
$allowedRoles = ['studente', 'docente', 'admin', 'sadmin'];

if (!isset($conn)) {
    echo json_encode(["session_active" => false, "error" => "Connessione al database non disponibile."]);
    exit;
}

$session_id = session_id();

$sql = "SELECT id_user, session_id, data_scadenza, session_data 
        FROM sessions 
        WHERE session_id = ? 
        AND data_scadenza > NOW()
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $session_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    echo json_encode(["session_active" => false, "error" => "Sessione non valida."]);
    exit;
}
$stmt->close();

if ($row['session_id'] !== $session_id) {
    session_id($row['session_id']);
    session_start();
}

$userData = json_decode($row['session_data'], true);
if (!$userData) {
    echo json_encode(["session_active" => false, "error" => "Dati della sessione non validi."]);
    exit;
}

$_SESSION['user'] = $userData;

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

echo json_encode(["session_active" => true, "user" => $userData]);
exit;
