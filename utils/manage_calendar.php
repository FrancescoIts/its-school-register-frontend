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

$action = $data['action'];

// Se in futuro volessi passare day, month, year separati
$day   = $data['day']   ?? null;
$month = $data['month'] ?? null;
$year  = $data['year']  ?? null;

// Recupero la data già pronta, se presente
$date       = $data['date']       ?? null;
$event_id   = $data['event_id']   ?? null;
$event      = $data['event']      ?? null;
$id_course  = $data['id_course']  ?? null;

// Se la data non è stata passata in formato YYYY-MM-DD ma abbiamo day, month, year, la costruiamo
if (empty($date) && !empty($day) && !empty($month) && !empty($year)) {
    // Ricostruisco la data come YYYY-MM-DD
    // (esempio: 2025-03-09)
    $date = sprintf("%04d-%02d-%02d", $year, $month, $day);
}

// Se $date è presente, controlliamo il formato (YYYY-MM-DD)
if ($date && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
    echo json_encode(["success" => false, "message" => "Formato data non valido (deve essere YYYY-MM-DD)."]);
    exit;
}

/**
 * Verifica se l'utente può modificare o eliminare l'evento
 */
function userCanModifyEvent($conn, $event_id, $id_user, $isAdmin) {
    $created_by = null;

    $stmt = $conn->prepare("SELECT created_by FROM calendar WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->bind_result($created_by);

    // Se l'evento non esiste, fetch() non restituisce nulla
    if (!$stmt->fetch()) {
        $stmt->close();
        return false; 
    }
    $stmt->close();

    // Controlli sui permessi
    if (is_null($created_by)) {
        // Se created_by è NULL, chiunque può modificarlo
        return true;
    }
    if ($isAdmin) {
        return true; 
    }
    if ($created_by == $id_user) {
        return true; 
    }
    return false; 
}

switch ($action) {
    case "add":
        // Aggiunge un nuovo evento
        if (!$date || !$event || !$id_course) {
            echo json_encode([
                "success" => false, 
                "message" => "Dati mancanti per l'aggiunta dell'evento (date, event, id_course)."
            ]);
            exit;
        }

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
        // Modifica di un evento esistente
        if (!$event_id || !$event) {
            echo json_encode([
              "success" => false, 
              "message" => "Parametri mancanti per la modifica (event_id, event)."
            ]);
            exit;
        }

        // Verifica permessi
        if (!userCanModifyEvent($conn, $event_id, $id_user, $isAdmin)) {
            echo json_encode([
              "success" => false, 
              "message" => "Non hai i permessi per modificare questo evento o non esiste."
            ]);
            exit;
        }

        // Aggiorno solo il campo 'event'.
        // Se vuoi aggiornare anche la data, aggiungi: ", date = ?" e relativa bind.
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
            echo json_encode([
              "success" => false, 
              "message" => "Parametri mancanti per l'eliminazione (event_id)."
            ]);
            exit;
        }

        // Verifica permessi
        if (!userCanModifyEvent($conn, $event_id, $id_user, $isAdmin)) {
            echo json_encode([
              "success" => false, 
              "message" => "Non hai i permessi per eliminare questo evento o non esiste."
            ]);
            exit;
        }

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
