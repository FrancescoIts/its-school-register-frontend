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

// Recupero corsi disponibili
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
if (count($corsiDisponibili) == 1) {
    $idCorsoSelezionato = $corsiDisponibili[0]['id_course'];
}

// Funzione per ottenere gli orari di inizio/fine del giorno corrente
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

// --------------------------
// Salvataggio presenze e aggiornamento modulo
// --------------------------
if (isset($_POST['salva_presenze']) && $idCorsoSelezionato > 0) {
    if (!userHasAnyRole($user['roles'], ['docente','admin','sadmin'])) {
        echo "<p>Non hai i permessi per modificare le presenze.</p>";
        exit;
    }

    // Recupera gli orari effettivi del giorno
    list($startTimeDay, $endTimeDay) = getDailyCourseTimes($conn, $idCorsoSelezionato, $oggi);
    if (is_null($startTimeDay) || is_null($endTimeDay)) {
        echo "<p>Non sono previste lezioni sabato o domenica.</p>";
        exit;
    }
    
    // Calcola in automatico la durata della lezione in ore
    $lessonHours = (strtotime($endTimeDay) - strtotime($startTimeDay)) / 3600;
    
    // Recupera il modulo selezionato (senza campo ore, perché la durata si prende dalla lezione)
    $moduleSelected = isset($_POST['id_module']) ? (int)$_POST['id_module'] : 0;
    
    $operationPerformed = false;
    if (!empty($_POST['students']) && is_array($_POST['students'])) {
        foreach ($_POST['students'] as $idStudente => $valori) {
            $idStudente = (int)$idStudente;
            // Se presente => orari (oppure di default se vuoti)
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
    
    // Se è stato selezionato un modulo, aggiorniamo la tabella module_attendance usando la durata dell'intera lezione
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

// Recupera gli studenti del corso e le presenze già salvate...
if ($idCorsoSelezionato > 0) {
    $sqlStud = "
        SELECT u.id_user, u.firstname, u.lastname
        FROM users u
        JOIN user_role_courses urc ON u.id_user = urc.id_user
        WHERE urc.id_course = ? AND urc.id_role = 1
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

    // Recupera le presenze già salvate per oggi
    $sqlAtt = "SELECT id_user, entry_hour, exit_hour FROM attendance WHERE id_course = ? AND date = ?";
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
            // Recupera il nome del corso selezionato
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
            
            <!-- Inserisco due hidden per trasmettere gli orari standard -->
            <input type="hidden" id="defaultStartTime" value="<?php echo $giornoStart; ?>">
            <input type="hidden" id="defaultEndTime" value="<?php echo $giornoEnd; ?>">
            
            <!-- Form per inserire le presenze e selezionare il modulo -->
            <form method="post" class="styled-form" id="attendanceForm">
                <input type="hidden" name="id_course" value="<?php echo $idCorsoSelezionato; ?>" />
                <div>
                    <label for="id_module">Seleziona Modulo (per questa lezione):</label>
                    <select name="id_module" id="id_module">
                        <option value="0">Nessun modulo</option>
                        <?php
                        // Recupera i moduli per il corso selezionato
                        $sqlMod = "SELECT id_module, module_name, module_duration FROM modules WHERE id_course = ? ORDER BY module_name";
                        $stmtM = $conn->prepare($sqlMod);
                        $stmtM->bind_param('i', $idCorsoSelezionato);
                        $stmtM->execute();
                        $resM = $stmtM->get_result();
                        while ($mod = $resM->fetch_assoc()) {
                            echo '<option value="' . $mod['id_module'] . '">' . htmlspecialchars($mod['module_name']) . ' (' . $mod['module_duration'] . ' ore)</option>';
                        }
                        $stmtM->close();
                        ?>
                    </select>
                </div>
                <!-- Pulsanti per facilitare l'uso -->
                <div class="button-utilities" style="margin-bottom: 15px; text-align: center;">
                    <button type="button" onclick="checkAllStudents()">Seleziona tutti</button>
                    <button type="button" onclick="fillTimes()">Riempie orari</button>
                </div>
                            <!-- Campo filtro per nome/cognome -->
            <div style="text-align: center; margin-bottom: 15px;">
                <input type="text" id="studentFilter" placeholder="Filtra per nome o cognome..." style="padding: 5px; width: 50%;">
            </div>
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
                            $isPresente = (!empty($entryH) && !empty($exitH));
                        ?>
                        <tr class="student-row">
                            <td><?php echo htmlspecialchars($stud['lastname'] . " " . $stud['firstname']); ?></td>
                            <td>
                                <label class="checkbox">
                                    <input type="checkbox" name="students[<?php echo $stId; ?>][presente]" value="1" class="checkbox__input" <?php echo $isPresente ? 'checked' : ''; ?> />
                                    <!-- SVG per la checkbox -->
                                    <svg class="checkbox__icon" viewBox="0 0 24 24" aria-hidden="true">
                                        <rect width="24" height="24" fill="#e0e0e0" rx="4"></rect>
                                        <path class="tick" fill="none" stroke="#007bff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M6 12l4 4 8-8"></path>
                                    </svg>
                                    <span class="checkbox__label"></span>
                                </label>
                            </td>
                            <td>
                                <input type="time" name="students[<?php echo $stId; ?>][entry_hour]" step="60" min="<?php echo $giornoStart; ?>" max="<?php echo $giornoEnd; ?>" value="<?php echo $entryH; ?>" />
                            </td>
                            <td>
                                <input type="time" name="students[<?php echo $stId; ?>][exit_hour]" step="60" min="<?php echo $giornoStart; ?>" max="<?php echo $giornoEnd; ?>" value="<?php echo $exitH; ?>" />
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <br>
                <div class="button-container">
                    <button type="submit" name="salva_presenze">Salva Presenze</button>
                    <?php
                        if (in_array('docente', $user['roles'])) {
                            echo '<button class="back attendance" type="button" onclick="window.location.href=\'doc_panel.php\'">Indietro</button>';
                        } else {
                            echo '<button class="back attendance" type="button" onclick="window.location.href=\'admin_panel.php\'">Indietro</button>';
                        }
                    ?>
                </div>
            </form>
        </div>
    </div>
    <?php
    }
} else {
    if (count($corsiDisponibili) > 1) {
        ?>
        <div class="scrollable-table">
            <form method="post">
                <p>Seleziona il corso per cui inserire le presenze di oggi: <?php echo $oggiIta; ?></p><br>
                <select name="id_course" required>
                    <option value="">Scegli un corso</option>
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