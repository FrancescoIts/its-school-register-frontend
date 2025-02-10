<?php
session_start();
require_once '../utils/config.php'; // Connessione al database

// Verifica se la sessione PHP è attiva
if (!isset($_SESSION['user'])) {
    header("Location: ../utils/logout.php");
    exit;
}

$id_user = $_SESSION['user']['id_user'];
$session_id = session_id();

// Controlla se la sessione esiste nel database e non è scaduta
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


$ruoli = $_SESSION['user']['roles'] ?? [];

// Se è una stringa lo trasformo in array, altrimenti T^T
if (!is_array($ruoli)) {
    $ruoli = [$ruoli]; 
}

$ruoli = array_map('strtolower', $ruoli);

if (!in_array('studente', $ruoli)) {
    echo "Accesso negato: Non sei autorizzato a visualizzare questa pagina.";
    exit;
}
?>
