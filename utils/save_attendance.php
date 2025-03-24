<?php
ob_start(); // Avvia il buffering per evitare output accidentali

require_once '../utils/config.php';
require_once '../utils/check_session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

$user = checkSession(true, ['docente','admin','sadmin']);
$oggi = date('Y-m-d');

// Funzione di utilità per controllare i ruoli
function userHasAnyRole($userRoles, $allowedRoles) {
    foreach ($userRoles as $r) {
        if (in_array(strtolower($r), $allowedRoles)) {
            return true;
        }
    }
    return false;
}

if (!userHasAnyRole($user['roles'], ['docente','admin'])) {
    // Restituisci errore JSON e termina
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Non hai i permessi per accedere a questa pagina.']);
    exit;
}

$isAdmin = (in_array('admin', array_map('strtolower', $user['roles'])) || in_array('sadmin', array_map('strtolower', $user['roles'])));

// Qui dovrai recuperare il corso selezionato
// Per semplicità, assumiamo che il parametro GET "id_course" sia passato all'endpoint
$idCorsoSelezionato = 0;
if (isset($_GET['id_course']) && !empty($_GET['id_course'])) {
    $idCorsoSelezionato = (int)$_GET['id_course'];
}
if ($idCorsoSelezionato == 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Corso non selezionato.']);
    exit;
}

// Funzione per recuperare gli orari del corso per il giorno corrente
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

list($giornoStart, $giornoEnd) = getDailyCourseTimes($conn, $idCorsoSelezionato, $oggi);

// Variabile per segnalare eventuali modifiche non permesse
$notAllowedModification = false;

// Salvataggio delle presenze (logica simile a quella già presente)
$moduleSelected = isset($_POST['id_module']) ? (int)$_POST['id_module'] : 0;
$lessonHours = (strtotime($giornoEnd) - strtotime($giornoStart)) / 3600;
$operationPerformed = false;

if (!empty($_POST['students']) && is_array($_POST['students'])) {
    foreach ($_POST['students'] as $idStudente => $valori) {
        $idStudente = (int)$idStudente;
        $isPresente = isset($valori['presente']) ? 1 : 0;
        if ($isPresente) {
            $entryHour = !empty($valori['entry_hour']) ? $valori['entry_hour'] : $giornoStart;
            $exitHour  = !empty($valori['exit_hour'])  ? $valori['exit_hour']  : $giornoEnd;
        } else {
            $entryHour = null;
            $exitHour  = null;
        }
        // Controlla eventuali presenze già inserite
        if ($isAdmin) {
            $sqlCheck = "SELECT id, created_by FROM attendance WHERE id_user = ? AND id_course = ? AND date = ? LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param('iis', $idStudente, $idCorsoSelezionato, $oggi);
        } else {
            $sqlCheck = "SELECT id, created_by FROM attendance WHERE id_user = ? AND id_course = ? AND date = ? LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param('iis', $idStudente, $idCorsoSelezionato, $oggi);
        }
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        $rowAtt = $resCheck->fetch_assoc();
        $stmtCheck->close();
        
        if ($rowAtt) {
            if (!$isAdmin && $rowAtt['created_by'] != $user['id_user']) {
                $notAllowedModification = true;
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
            if (!$isAdmin) {
                $sqlDupCheck = "SELECT id, created_by FROM attendance WHERE id_user = ? AND id_course = ? AND date = ? LIMIT 1";
                $stmtDup = $conn->prepare($sqlDupCheck);
                $stmtDup->bind_param('iis', $idStudente, $idCorsoSelezionato, $oggi);
                $stmtDup->execute();
                $resDup = $stmtDup->get_result();
                if ($resDup->num_rows > 0) {
                    $notAllowedModification = true;
                    $stmtDup->close();
                    continue;
                }
                $stmtDup->close();
            }
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

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

$response = [];
if ($notAllowedModification) {
    $response['status'] = 'warning';
    $response['message'] = 'Non hai il permesso di modificare le presenze inserite da altri docenti.';
} else {
    $response['status'] = 'success';
    $response['message'] = 'Registro salvato con successo.';
}

if ($isAjax) {
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else {
    echo "<script>
            Swal.fire({
                title: '" . ($notAllowedModification ? "Attenzione" : "Successo!") . "',
                text: '" . $response['message'] . "',
                icon: '" . ($notAllowedModification ? "warning" : "success") . "',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = '" . $_SERVER['PHP_SELF'] . "?id_course=$idCorsoSelezionato';
            });
          </script>";
    exit;
}
