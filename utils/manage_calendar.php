<?php
require_once 'config.php';
require_once 'check_session.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user = checkSession();
$id_user = $user['id_user'];
$roles = $user['roles'] ?? [];
$isAdmin = in_array('admin', $roles) || in_array('sadmin', $roles);
$isDocente = in_array('docente', $roles);


$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['action'])) {
    echo json_encode(["success" => false, "message" => "Richiesta non valida (parametri mancanti)."]);
    exit;
}

$action     = $data['action'];
$event_id   = $data['event_id']   ?? null;
$date       = $data['date']       ?? null;
$event      = $data['event']      ?? null;
$id_course  = $data['id_course']  ?? null;

// Controlla formato data
if ($date && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
    echo json_encode(["success" => false, "message" => "Formato data non valido (deve essere YYYY-MM-DD)."]);
    exit;
}

function userCanModifyEvent($conn, $event_id, $id_user, $isAdmin) {
    $created_by = null; // Inizializziamo la variabile

    $stmt = $conn->prepare("SELECT created_by FROM calendar WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->bind_result($created_by);

    // Assicuriamoci che fetch() restituisca un risultato
    if (!$stmt->fetch()) {
        $stmt->close();
        return false; // L'evento non esiste, quindi non può essere modificato
    }

    $stmt->close();

    // Controlli sui permessi
    if (is_null($created_by)) {
        return true; // Se created_by è NULL, chiunque può modificarlo
    }
    if ($isAdmin) {
        return true; // L'admin può sempre modificare
    }
    if ($created_by == $id_user) {
        return true; // Il creatore può modificare
    }

    return false; // Nessun permesso per modificare
}

// Switch sull'azione
switch ($action) {
    case "add":
        // Aggiunge nuovo evento
        if (!$date || !$event || !$id_course) {
            echo json_encode(["success" => false, "message" => "Dati mancanti per l'aggiunta dell'evento."]);
            exit;
        }
        // Inserisce riga
        $stmt = $conn->prepare("
            INSERT INTO calendar (date, event, created_by, id_course)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssii", $date, $event, $id_user, $id_course);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Evento aggiunto con successo."]);
        } else {
            echo json_encode(["success" => false, "message" => "Errore durante l'aggiunta dell'evento."]);
        }
        $stmt->close();
        break;

    case "edit":
        // Modifica evento esistente
        if (!$event_id || !$event) {
            echo json_encode(["success" => false, "message" => "Parametri mancanti per la modifica (event_id, event)."]);
            exit;
        }
        // Verifica permessi
        if (!userCanModifyEvent($conn, $event_id, $id_user, $isAdmin)) {
            echo json_encode(["success" => false, "message" => "Non hai i permessi per modificare questo evento o non esiste."]);
            exit;
        }
        // Aggiorna solo il campo 'event' (se vuoi, puoi aggiungere date e id_course)
        $stmt = $conn->prepare("UPDATE calendar SET event = ? WHERE id = ?");
        $stmt->bind_param("si", $event, $event_id);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Evento modificato con successo."]);
        } else {
            echo json_encode(["success" => false, "message" => "Errore nella modifica dell'evento."]);
        }
        $stmt->close();
        break;

    case "delete":
        // Eliminazione evento
        if (!$event_id) {
            echo json_encode(["success" => false, "message" => "Parametri mancanti per l'eliminazione (event_id)."]);
            exit;
        }
        // Verifica permessi
        if (!userCanModifyEvent($conn, $event_id, $id_user, $isAdmin)) {
            echo json_encode(["success" => false, "message" => "Non hai i permessi per eliminare questo evento o non esiste."]);
            exit;
        }
        // Cancella
        $stmt = $conn->prepare("DELETE FROM calendar WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Evento eliminato con successo."]);
        } else {
            echo json_encode(["success" => false, "message" => "Errore durante l'eliminazione dell'evento."]);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(["success" => false, "message" => "Azione non riconosciuta."]);
        break;
}

exit;
