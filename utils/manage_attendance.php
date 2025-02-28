<?php
require_once '../utils/config.php';
require_once '../utils/check_session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

$user = checkSession(true, ['docente', 'admin', 'sadmin']);

$oggi = date('Y-m-d');
$oggiIta = date('d/m/Y');

// ----------------------------------------------------
// Funzione di utilità per verificare se l'utente
// possiede almeno uno dei ruoli passati.
//
function userHasAnyRole($userRoles, $allowedRoles) {
    foreach ($userRoles as $r) {
        if (in_array(strtolower($r), $allowedRoles)) {
            return true;
        }
    }
    return false;
}

// Verifica ruolo
if (!userHasAnyRole($user['roles'], ['docente','admin','sadmin'])) {
    echo "<p>Non hai i permessi per accedere a questa pagina.</p>";
    exit;
}

// ----------------------------------------------------
// Determiniamo i corsi a cui può accedere l’utente
// ----------------------------------------------------
$corsiDisponibili = [];
$ruoliMinuscoli = array_map('strtolower', $user['roles']);

if (in_array('admin', $ruoliMinuscoli) || in_array('sadmin', $ruoliMinuscoli)) {
    // Admin / sadmin: prende i corsi con ruolo 3 o 4
    $stmt = $conn->prepare("
        SELECT c.*
        FROM courses c
        JOIN user_role_courses urc ON c.id_course = urc.id_course
        JOIN users u ON urc.id_user = u.id_user
        WHERE u.id_user = ?
          AND (urc.id_role = 3 OR urc.id_role = 4)
        ORDER BY c.name
    ");
    $stmt->bind_param('i', $user['id_user']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($rowC = $res->fetch_assoc()) {
        $corsiDisponibili[] = $rowC;
    }
    $stmt->close();
} else {
    // Docente: prende i corsi con ruolo=2
    $stmt = $conn->prepare("
        SELECT c.*
        FROM courses c
        JOIN user_role_courses urc ON c.id_course = urc.id_course
        JOIN users u ON urc.id_user = u.id_user
        WHERE u.id_user = ?
          AND urc.id_role = 2
        ORDER BY c.name
    ");
    $stmt->bind_param('i', $user['id_user']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($rowC = $res->fetch_assoc()) {
        $corsiDisponibili[] = $rowC;
    }
    $stmt->close();
}

// Selezione corso
$idCorsoSelezionato = 0;
if (isset($_POST['id_course'])) {
    $idCorsoSelezionato = (int)$_POST['id_course'];
}
// Se c’è un solo corso disponibile, usiamo quello automaticamente
if (count($corsiDisponibili) == 1) {
    $idCorsoSelezionato = $corsiDisponibili[0]['id_course'];
}

// Funzione per ottenere gli orari di inizio/fine del giorno corrente (es. Monday -> start_time_monday)
function getDailyCourseTimes($conn, $idCourse, $oggi) {
    $dayOfWeek = strtolower(date('l', strtotime($oggi))); // monday, tuesday, ...
    $startColumn = 'start_time_' . $dayOfWeek;
    $endColumn   = 'end_time_' . $dayOfWeek;

    $sql = "SELECT $startColumn AS start_day, $endColumn AS end_day
            FROM courses
            WHERE id_course = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $idCourse);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Se NULL, di default 14:00 - 18:00
    $start = $res['start_day'] ?? '14:00:00';
    $end   = $res['end_day']   ?? '18:00:00';

    return [$start, $end];
}

// --------------------------
// Salvataggio presenze
// --------------------------
if (isset($_POST['salva_presenze']) && $idCorsoSelezionato > 0) {
    if (!userHasAnyRole($user['roles'], ['docente','admin','sadmin'])) {
        echo "<p>Non hai i permessi per modificare le presenze.</p>";
        exit;
    }

    // Orari effettivi del giorno
    list($startTimeDay, $endTimeDay) = getDailyCourseTimes($conn, $idCorsoSelezionato, $oggi);

    if (!empty($_POST['students']) && is_array($_POST['students'])) {
        foreach ($_POST['students'] as $idStudente => $valori) {
            $idStudente = (int)$idStudente;

            // Se presente => orari (o default se vuoti)
            $isPresente = isset($valori['presente']) ? 1 : 0;
            
            // Se la spunta è presente ma non ci sono orari, è presenza per TUTTE le ore
            if ($isPresente) {
                $entryHour = !empty($valori['entry_hour']) ? $valori['entry_hour'] : $startTimeDay;
                $exitHour  = !empty($valori['exit_hour'])  ? $valori['exit_hour']  : $endTimeDay;
            } else {
                $entryHour = null;
                $exitHour  = null;
            }

            // Cerca record esistente per (id_user, id_course, date=oggi)
            $sqlCheck = "
                SELECT id, created_by FROM attendance
                WHERE id_user = ? AND id_course = ? AND date = ?
                LIMIT 1
            ";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param('iis', $idStudente, $idCorsoSelezionato, $oggi);
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result();
            $rowAtt = $resCheck->fetch_assoc();
            $stmtCheck->close();

            if ($rowAtt) {
                // UPDATE solo se il record è stato creato dal docente corrente
                if ($rowAtt['created_by'] == $user['id_user']) {
                    $sqlUpdate = "
                        UPDATE attendance
                        SET entry_hour = ?, exit_hour = ?
                        WHERE id = ? AND created_by = ?
                    ";
                    $stmtU = $conn->prepare($sqlUpdate);
                    $stmtU->bind_param('ssii', $entryHour, $exitHour, $rowAtt['id'], $user['id_user']);
                    $stmtU->execute();
                    $stmtU->close();
                }
            } else {
                // INSERT: salviamo anche chi ha creato il record
                $sqlInsert = "
                    INSERT INTO attendance (id_user, id_course, date, entry_hour, exit_hour, created_by)
                    VALUES (?, ?, ?, ?, ?, ?)
                ";
                $stmtI = $conn->prepare($sqlInsert);
                $stmtI->bind_param('iisssi', $idStudente, $idCorsoSelezionato, $oggi, $entryHour, $exitHour, $user['id_user']);
                $stmtI->execute();
                $stmtI->close();
            }
        }
    }
}

// Selezionato un corso?
if ($idCorsoSelezionato > 0) {
    // Recupera gli studenti di quel corso (id_role=1)
    $sqlStud = "
        SELECT u.id_user, u.firstname, u.lastname
        FROM users u
        JOIN user_role_courses urc ON u.id_user = urc.id_user
        WHERE urc.id_course = ?
          AND urc.id_role = 1
        ORDER BY u.lastname, u.firstname
    ";
    $stmtS = $conn->prepare($sqlStud);
    $stmtS->bind_param('i', $idCorsoSelezionato);
    $stmtS->execute();
    $resS = $stmtS->get_result();

    $studenti = [];
    while ($r = $resS->fetch_assoc()) {
        $studenti[] = $r;
    }
    $stmtS->close();

    if (!$studenti) {
        echo "<p>Nessuno studente trovato per questo corso.</p>";
    } else {
        // Presenze già salvate per la data di oggi
        $sqlAtt = "
            SELECT id_user, entry_hour, exit_hour, created_by
            FROM attendance
            WHERE id_course = ? AND date = ?
        ";
        $stmtA = $conn->prepare($sqlAtt);
        $stmtA->bind_param('is', $idCorsoSelezionato, $oggi);
        $stmtA->execute();
        $resA = $stmtA->get_result();

        $mappaPresenze = [];
        while ($rowA = $resA->fetch_assoc()) {
            $mappaPresenze[$rowA['id_user']] = [
                'entry_hour' => $rowA['entry_hour'],
                'exit_hour'  => $rowA['exit_hour'],
                'created_by' => $rowA['created_by']
            ];
        }
        $stmtA->close();

        // Orari per HTML (passiamo min, max e step="60")
        list($giornoStart, $giornoEnd) = getDailyCourseTimes($conn, $idCorsoSelezionato, $oggi);
        ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Docente</title>
    <link rel="stylesheet" href="../assets/css/doc_panel.css"> 
    <link rel="stylesheet" href="../assets/css/dashboard_style.css"> 
    <link rel="stylesheet" href="../assets/css/calendar.css">
    <link rel="stylesheet" href="../assets/css/overflow.css">
    <link rel="stylesheet" href="../assets/css/stats_total.css">
    <link rel="stylesheet" href="../assets/css/manage_attendance.css">
    <link rel="stylesheet" href="../assets/css/checkbox.css">
    <link rel="shortcut icon" href="../assets/img/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/main.js" defer></script>
<div class="container">
    <h3>Presenze di oggi (<?php echo $oggiIta; ?>)</h3>
    <?php
    // Nome corso
    $nomeCorsoSelezionato = 'Nessun corso selezionato';
    if ($idCorsoSelezionato > 0) {
        foreach ($corsiDisponibili as $c) {
            if ($c['id_course'] == $idCorsoSelezionato) {
                $nomeCorsoSelezionato = $c['name'];
                break;
            }
        }
    }
    ?>
    <p><strong><?php echo htmlspecialchars($nomeCorsoSelezionato); ?></strong></p>
    <br>
    <button class="theme-toggle" id="theme-toggle">🌙</button>
    <form method="post" class="styled-form" id="attendanceForm">
        <input type="hidden" name="id_course" value="<?php echo $idCorsoSelezionato; ?>" />

        <div class="table-container">
            <table class="attendance-table">
                <tr>
                    <th>Studente</th>
                    <th>Presente</th>
                    <th>Ora Ingresso</th>
                    <th>Ora Uscita</th>
                </tr>
                <?php foreach ($studenti as $stud):
                    $stId   = $stud['id_user'];
                    $entryH = $mappaPresenze[$stId]['entry_hour'] ?? '';
                    $exitH  = $mappaPresenze[$stId]['exit_hour']  ?? '';

                    // Se l'entry e exit NON vuoti => check (anche solo se il record è stato creato dal docente)
                    $isPresente = (!empty($entryH) && !empty($exitH) && ($mappaPresenze[$stId]['created_by'] == $user['id_user']));
                    
                    // Impostiamo min, max e step per consentire "14:00"
                    $min = $giornoStart ?: '14:00:00';
                    $max = $giornoEnd   ?: '18:00:00';
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($stud['lastname'] . " " . $stud['firstname']); ?></td>
                    <td>
                        <label class="checkbox">
                            <input type="checkbox"
                                   name="students[<?php echo $stId; ?>][presente]"
                                   value="1"
                                   class="checkbox__input"
                                   <?php echo $isPresente ? 'checked' : ''; ?> />
                            <svg class="checkbox__icon" viewBox="0 0 24 24" aria-hidden="true">
                                <rect width="24" height="24" fill="#e0e0e0" rx="4"></rect>
                                <path class="tick" fill="none" stroke="#007bff" stroke-width="3"
                                      stroke-linecap="round" stroke-linejoin="round"
                                      d="M6 12l4 4 8-8"></path>
                            </svg>
                            <span class="checkbox__label"></span>
                        </label>
                    </td>
                    <td>
                        <input type="time"
                               name="students[<?php echo $stId; ?>][entry_hour]"
                               step="60"
                               min="<?php echo $min; ?>"
                               max="<?php echo $max; ?>"
                               value="<?php echo $entryH; ?>" />
                    </td>
                    <td>
                        <input type="time"
                               name="students[<?php echo $stId; ?>][exit_hour]"
                               step="60"
                               min="<?php echo $min; ?>"
                               max="<?php echo $max; ?>"
                               value="<?php echo $exitH; ?>" />
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <br>
        <div class="button-container">
            <button type="submit" name="salva_presenze">Salva Presenze</button>
            <?php
                // Tasto Indietro
                if (in_array('docente', $user['roles'])) {
                    echo '<button class="back" type="button" onclick="window.location.href=\'../doc/doc_panel.php\'">Indietro</button>';
                } else {
                    echo '<button class="back" type="button" onclick="window.location.href=\'../admin/admin_panel.php\'">Indietro</button>';
                }
            ?>
        </div>
    </form>
</div>
</body>
<?php
    }
} else {
    // Se l’utente ha più corsi, mostra il form per la selezione corso
    if (count($corsiDisponibili) > 1) {
        ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Docente</title>
    <link rel="stylesheet" href="../assets/css/doc_panel.css"> 
    <link rel="stylesheet" href="../assets/css/dashboard_style.css"> 
    <link rel="stylesheet" href="../assets/css/calendar.css">
    <link rel="stylesheet" href="../assets/css/overflow.css">
    <link rel="stylesheet" href="../assets/css/stats_total.css">
    <link rel="stylesheet" href="../assets/css/manage_attendance.css">
    <link rel="stylesheet" href="../assets/css/checkbox.css">
    <link rel="shortcut icon" href="../assets/img/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/main.js" defer></script>
        <body>
            
        <form method="post">
            <p>Seleziona il corso per cui inserire le presenze di oggi: <?php echo $oggiIta; ?></p><br>
            <select name="id_course" required>
                <option value="">-- scegli un corso --</option>
                <?php foreach ($corsiDisponibili as $c): ?>
                    <option value="<?php echo $c['id_course']; ?>">
                        <?php echo htmlspecialchars($c['name'] . " (" . $c['period'] . ")"); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Vai</button>
        </form>
        </body>
        </html>
        <?php
    } else {
        echo "<p>Nessun corso disponibile o non selezionato.</p>";
    }
}
?>
