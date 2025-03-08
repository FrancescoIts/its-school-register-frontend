<?php
require_once '../utils/config.php';
require_once '../utils/check_session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

$user = checkSession(true, ['docente', 'admin', 'sadmin']);

$oggi = date('Y-m-d');
$oggiIta = date('d/m/Y');

function userHasAnyRole($userRoles, $allowedRoles) {
    foreach ($userRoles as $r) {
        if (in_array(strtolower($r), $allowedRoles)) {
            return true;
        }
    }
    return false;
}

if (!userHasAnyRole($user['roles'], ['docente','admin','sadmin'])) {
    echo "<p>Non hai i permessi per accedere a questa pagina.</p>";
    exit;
}

$isAdmin = (in_array('admin', array_map('strtolower', $user['roles'])) || in_array('sadmin', array_map('strtolower', $user['roles'])));

// Determiniamo i corsi a cui può accedere l’utente
$corsiDisponibili = [];
$ruoliMinuscoli = array_map('strtolower', $user['roles']);

if (in_array('admin', $ruoliMinuscoli) || in_array('sadmin', $ruoliMinuscoli)) {
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

// Funzione per ottenere gli orari di inizio/fine del giorno corrente
function getDailyCourseTimes($conn, $idCourse, $oggi) {
    // Otteniamo il giorno in inglese (es. "monday", "tuesday", ...)
    $dayOfWeek = strtolower(date('l', strtotime($oggi)));
    // Se è sabato o domenica, restituiamo [null, null]
    if ($dayOfWeek === 'saturday' || $dayOfWeek === 'sunday') {
        return [null, null];
    }
    
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

    return [$res['start_day'], $res['end_day']];
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

    if (is_null($startTimeDay) || is_null($endTimeDay)) {
        echo "<p>Non sono previste lezioni sabato o domenica.</p>";
        exit;
    }
    
    $operationPerformed = false;
    if (!empty($_POST['students']) && is_array($_POST['students'])) {
        foreach ($_POST['students'] as $idStudente => $valori) {
            $idStudente = (int)$idStudente;
            // Se presente => orari (o default se vuoti)
            $isPresente = isset($valori['presente']) ? 1 : 0;
            
            if ($isPresente) {
                $entryHour = !empty($valori['entry_hour']) ? $valori['entry_hour'] : $startTimeDay;
                $exitHour  = !empty($valori['exit_hour'])  ? $valori['exit_hour']  : $endTimeDay;
            } else {
                $entryHour = null;
                $exitHour  = null;
            }

            // Verifica se esiste già un record per (id_user, id_course, date)
            if ($isAdmin) {
                $sqlCheck = "
                    SELECT id, created_by FROM attendance
                    WHERE id_user = ? AND id_course = ? AND date = ?
                    LIMIT 1
                ";
                $stmtCheck = $conn->prepare($sqlCheck);
                $stmtCheck->bind_param('iis', $idStudente, $idCorsoSelezionato, $oggi);
            } else {
                $sqlCheck = "
                    SELECT id, created_by FROM attendance
                    WHERE id_user = ? AND id_course = ? AND date = ? AND created_by = ?
                    LIMIT 1
                ";
                $stmtCheck = $conn->prepare($sqlCheck);
                $stmtCheck->bind_param('iisi', $idStudente, $idCorsoSelezionato, $oggi, $user['id_user']);
            }
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result();
            $rowAtt = $resCheck->fetch_assoc();
            $stmtCheck->close();

            if ($rowAtt) {
                // Se l'utente non è admin e il record non è stato creato da lui, non permette l'update
                if (!$isAdmin && $rowAtt['created_by'] != $user['id_user']) {
                    continue;
                }
                // UPDATE
                if ($isAdmin) {
                    $sqlUpdate = "
                        UPDATE attendance
                        SET entry_hour = ?, exit_hour = ?
                        WHERE id = ?
                    ";
                    $stmtU = $conn->prepare($sqlUpdate);
                    $stmtU->bind_param('ssi', $entryHour, $exitHour, $rowAtt['id']);
                } else {
                    $sqlUpdate = "
                        UPDATE attendance
                        SET entry_hour = ?, exit_hour = ?
                        WHERE id = ? AND created_by = ?
                    ";
                    $stmtU = $conn->prepare($sqlUpdate);
                    $stmtU->bind_param('ssii', $entryHour, $exitHour, $rowAtt['id'], $user['id_user']);
                }
                $stmtU->execute();
                $stmtU->close();
            } else {
                // INSERT
                $sqlInsert = "
                    INSERT INTO attendance (id_user, id_course, date, entry_hour, exit_hour, created_by)
                    VALUES (?, ?, ?, ?, ?, ?)
                ";
                $stmtI = $conn->prepare($sqlInsert);
                $stmtI->bind_param(
                    'iisssi',
                    $idStudente,
                    $idCorsoSelezionato,
                    $oggi,
                    $entryHour,
                    $exitHour,
                    $user['id_user'] 
                );
                $stmtI->execute();
                $stmtI->close();
            }
            $operationPerformed = true;
        }
    }
    
    if ($operationPerformed) {
        echo "<script>
            Swal.fire({
                title: 'Successo!',
                text: 'Modifiche salvate con successo.',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = '" . $_SERVER['PHP_SELF'] . "?sel_date=" . urlencode($oggi) . "&id_course=" . $idCorsoSelezionato . "';
            });
        </script>";
        exit;
    }
}

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
            SELECT id_user, entry_hour, exit_hour
            FROM attendance
            WHERE id_course = ?
              AND date = ?
        ";
        $stmtA = $conn->prepare($sqlAtt);
        $stmtA->bind_param('is', $idCorsoSelezionato, $oggi);
        $stmtA->execute();
        $resA = $stmtA->get_result();

        $mappaPresenze = [];
        while ($rowA = $resA->fetch_assoc()) {
            $mappaPresenze[$rowA['id_user']] = [
                'entry_hour' => $rowA['entry_hour'],
                'exit_hour'  => $rowA['exit_hour']
            ];
        }
        $stmtA->close();

        // Otteniamo gli orari per la data di oggi
        list($giornoStart, $giornoEnd) = getDailyCourseTimes($conn, $idCorsoSelezionato, $oggi);
        $lezioniPreviste = true;
        if (is_null($giornoStart) || is_null($giornoEnd)) {
            $lezioniPreviste = false;
            echo "<p>Non sono previste lezioni sabato o domenica.</p>";
        }
        ?>
                <?php if ($lezioniPreviste) { ?>
                    <div class="scrollable-table">
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
                                // Se entry ed exit non sono vuoti => presente
                                $isPresente = (!empty($entryH) && !empty($exitH));
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
                                           min="<?php echo $giornoStart; ?>"
                                           max="<?php echo $giornoEnd; ?>"
                                           value="<?php echo $entryH; ?>" />
                                </td>
                                <td>
                                    <input type="time"
                                           name="students[<?php echo $stId; ?>][exit_hour]"
                                           step="60"
                                           min="<?php echo $giornoStart; ?>"
                                           max="<?php echo $giornoEnd; ?>"
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
                                echo '<button class="back attendance" type="button" onclick="window.location.href=\'doc_panel.php\'">Indietro</button>';
                            } else {
                                echo '<button class="back attendance" type="button" onclick="window.location.href=\'admin_panel.php\'">Indietro</button>';
                            }
                        ?>
                    </div>
                </form>
                <?php } // fine if lezioniPreviste ?>
            </div>
        </div>
        <?php
    }
} else {
    // Se l’utente ha più corsi, mostra il form per la selezione corso
    if (count($corsiDisponibili) > 1) {
        ?>
        <div class="scrollable-table">
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
                <button type="submit" class="attendance">Vai</button>
            </form>
        </div>
        <?php
    } else {
        echo "<p>Nessun corso disponibile o non selezionato.</p>";
    }
}
?>
