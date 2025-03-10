<?php
ob_start();
require_once '../utils/config.php';
require_once '../utils/check_session.php';

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Accesso non consentito']);
    exit;
}

$user = checkSession(true, ['admin', 'sadmin']);
$id_admin = $user['id_user'];

// Verifica che siano stati passati action e id_user
if (!isset($_GET['action']) || !isset($_GET['id_user'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Parametri mancanti']);
    exit;
}

$id_user = (int)$_GET['id_user'];

// Gestione delle operazioni in base al parametro action
if ($_GET['action'] == 'deactivate') {
    $conn->query("UPDATE users SET active = 0 WHERE id_user = $id_user");
}
elseif ($_GET['action'] == 'activate') {
    $conn->query("UPDATE users SET active = 1 WHERE id_user = $id_user");
}
elseif ($_GET['action'] == 'delete') {
    $conn->query("DELETE FROM users WHERE id_user = $id_user");
}
else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Azione non valida o parametri mancanti']);
    exit;
}

// Restituisco la risposta JSON
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success', 
    'action' => $_GET['action'], 
    'id_user' => $id_user
]);
exit;
