<?php
require_once '../utils/config.php';
require_once '../utils/check_session.php';

$user = checkSession(true, ['sadmin']);


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(["status" => "error", "message" => "Metodo non consentito"]));
}

// Verifica che la richiesta provenga da view_courses.php tramite l'header HTTP_REFERER
if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'sadmin_panel.php') === false) {
    http_response_code(403);
    die(json_encode(["status" => "error", "message" => "Accesso non autorizzato"]));
}

// Recupera i dati inviati in formato JSON
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['course_id'])) {
    http_response_code(400);
    die(json_encode(["status" => "error", "message" => "Dati mancanti"]));
}

$course_id = intval($data['course_id']);

// Esegui l'eliminazione del corso
$stmt = $conn->prepare("DELETE FROM courses WHERE id_course = ?");
if (!$stmt) {
    http_response_code(500);
    die(json_encode(["status" => "error", "message" => "Errore del server"]));
}

$stmt->bind_param("i", $course_id);
if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Corso eliminato correttamente"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Errore durante l'eliminazione del corso"]);
}
$stmt->close();

