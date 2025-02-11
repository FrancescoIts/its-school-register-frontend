<?php
session_start();
require_once '../utils/config.php'; 

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Controllo sessione
if (!isset($_SESSION['user'])) {
    ob_end_clean();
    header("Location: ../utils/logout.php");
    exit;
}

// Controllo scadenza sessione
$id_user = $_SESSION['user']['id_user'];
$session_id = session_id();

if (!isset($conn)) {
    die("Errore: Connessione al database non disponibile.");
}

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
if (!is_array($ruoli)) {
    $ruoli = [$ruoli]; 
}
$ruoli = array_map('strtolower', $ruoli);

if (!in_array('studente', $ruoli)) {
    header("Location: ../index.php");
    exit;
}
?>
