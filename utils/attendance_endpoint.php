<?php
require_once '../utils/config.php';
require_once '../utils/check_session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');

$user = checkSession(true, ['docente', 'admin', 'sadmin']);

function userHasAnyRole($userRoles, $allowedRoles) {
    foreach ($userRoles as $r) {
        if (in_array(strtolower($r), $allowedRoles)) {
            return true;
        }
    }
    return false;
}

if (!userHasAnyRole($user['roles'], ['docente','admin'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Non hai i permessi per accedere a questa risorsa.'
    ]);
    exit;
}

$isAdmin = (in_array('admin', array_map('strtolower', $user['roles'])) || in_array('sadmin', array_map('strtolower', $user['roles'])));
$oggi = date('Y-m-d');

function getDailyCourseTimes($conn, $idCourse, $oggi) {
    $dayOfWeek = strtolower(date('l', strtotime($oggi)));
    if ($dayOfWeek === 'saturday' || $dayOfWeek === 'sunday') {
        return [null, null];
    }
    
    $startColumn = 'start_time_' . $dayOfWeek;
    $endColumn   = 'end_time_' . $dayOfWeek;

    $sql = "SELECT $startColumn AS start_day, $endColumn AS end_day FROM courses WHERE id_course = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $idCourse);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return [$res['start_day'], $res['end_day']];
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['salva_presenze'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Dati non validi o mancanti.'
    ]);
    exit;
}

$idCorsoSelezionato = isset($data['id_course']) ? (int)$data['id_course'] : 0;
$moduleSelected     = isset($data['id_module']) ? (int)$data['id_module'] : 0;

list($startTimeDay, $endTimeDay) = getDailyCourseTimes($conn, $idCorsoSelezionato, $oggi);
if (is_null($startTimeDay) || is_null($endTimeDay)) {
    echo json_encode([
        'success' => false,
        'message' => 'Non sono previste lezioni sabato o domenica.'
    ]);
    exit;
}

$lessonHours = (strtotime($endTimeDay) - strtotime($startTimeDay)) / 3600;

if (!userHasAnyRole($user['roles'], ['docente','admin'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Non hai i permessi per modificare le presenze.'
    ]);
    exit;
}

$operationPerformed = false;
if (!empty($data['students']) && is_array($data['students'])) {
    foreach ($data['students'] as $idStudente => $valori) {
        $idStudente = (int)$idStudente;
        $isPresente = isset($valori['presente']) && $valori['presente'] ? 1 : 0;
        
        if ($isPresente) {
            $entryHour = !empty($valori['entry_hour']) ? $valori['entry_hour'] : $startTimeDay;
            $exitHour  = !empty($valori['exit_hour'])  ? $valori['exit_hour']  : $endTimeDay;
        } else {
            $entryHour = null;
            $exitHour  = null;
        }
        
        if ($isAdmin) {
            $sqlCheck = "SELECT id, created_by FROM attendance WHERE id_user = ? AND id_course = ? AND date = ? LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param('iis', $idStudente, $idCorsoSelezionato, $oggi);
        } else {
            $sqlCheck = "SELECT id, created_by FROM attendance WHERE id_user = ? AND id_course = ? AND date = ? AND created_by = ? LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param('iisi', $idStudente, $idCorsoSelezionato, $oggi, $user['id_user']);
        }
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        $rowAtt = $resCheck->fetch_assoc();
        $stmtCheck->close();
        
        if ($rowAtt) {
            if (!$isAdmin && $rowAtt['created_by'] != $user['id_user']) {
                continue;
            }
            if ($isAdmin) {
                $sqlUpdate = "UPDATE attendance SET entry_hour = ?, exit_hour = ? WHERE id = ?";
                $stmtU = $conn->prepare($sqlUpdate);
                $stmtU->bind_param('ssi', $entryHour, $exitHour, $rowAtt['id']);
            } else {
                $sqlUpdate = "UPDATE attendance SET entry_hour = ?, exit_hour = ? WHERE id = ? AND created_by = ?";
                $stmtU = $conn->prepare($sqlUpdate);
                $stmtU->bind_param('ssii', $entryHour, $exitHour, $rowAtt['id'], $user['id_user']);
            }
            $stmtU->execute();
            $stmtU->close();
        } else {
            $sqlInsert = "INSERT INTO attendance (id_user, id_course, date, entry_hour, exit_hour, created_by) VALUES (?, ?, ?, ?, ?, ?)";
            $stmtI = $conn->prepare($sqlInsert);
            $stmtI->bind_param('iisssi', $idStudente, $idCorsoSelezionato, $oggi, $entryHour, $exitHour, $user['id_user']);
            $stmtI->execute();
            $stmtI->close();
        }
        $operationPerformed = true;
    }
}

if ($operationPerformed && $moduleSelected > 0) {
    $sqlCheckModule = "SELECT id, hours_accumulated FROM module_attendance WHERE id_course = ? AND id_module = ? AND date = ? LIMIT 1";
    $stmtM = $conn->prepare($sqlCheckModule);
    $stmtM->bind_param('iis', $idCorsoSelezionato, $moduleSelected, $oggi);
    $stmtM->execute();
    $resM = $stmtM->get_result();
    if ($rowM = $resM->fetch_assoc()) {
        $sqlUpdateModule = "UPDATE module_attendance SET hours_accumulated = ? WHERE id = ?";
        $stmtUM = $conn->prepare($sqlUpdateModule);
        $stmtUM->bind_param('di', $lessonHours, $rowM['id']);
        $stmtUM->execute();
        $stmtUM->close();
    } else {
        $sqlInsertModule = "INSERT INTO module_attendance (id_course, id_module, date, hours_accumulated) VALUES (?, ?, ?, ?)";
        $stmtIM = $conn->prepare($sqlInsertModule);
        $stmtIM->bind_param('iisd', $idCorsoSelezionato, $moduleSelected, $oggi, $lessonHours);
        $stmtIM->execute();
        $stmtIM->close();
    }
    $stmtM->close();
}

if ($operationPerformed) {
    echo json_encode([
        'success' => true,
        'message' => 'Registro salvato con successo.',
        'redirect' => $_SERVER['PHP_SELF'] . "?sel_date=" . urlencode($oggi) . "&id_course=" . $idCorsoSelezionato
    ]);
    exit;
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Nessuna operazione effettuata.'
    ]);
    exit;
}
