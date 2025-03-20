<?php
require_once '../utils/config.php';
require_once '../utils/check_session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);


$user = checkSession(true, ['docente', 'admin']);


$isAdmin = (in_array('admin', array_map('strtolower', $user['roles'])));

// Funzione per verificare se l'utente possiede almeno uno dei ruoli indicati.
function hasAnyRole($userRoles, $allowedRoles) {
    foreach ($userRoles as $r) {
        if (in_array(strtolower($r), $allowedRoles)) {
            return true;
        }
    }
    return false;
}

// Funzione per recuperare gli orari di inizio/fine giornata dal DB in base al giorno
// della settimana e al corso selezionato.
function dailyCourseTimes($conn, $idCourse, $date) {
    // Ricava il giorno della settimana dalla data (es. monday, tuesday, etc.)
    $dayOfWeek = strtolower(date('l', strtotime($date)));
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

    $start = $res['start_day'];
    $end   = $res['end_day'];

    return [$start, $end];
}

// Verifica dei ruoli per l'accesso.
if (!hasAnyRole($user['roles'], ['docente', 'admin'])) {
    echo "<script>Swal.fire('Accesso Negato', 'Non hai i permessi per modificare le presenze.', 'error');</script>";
    exit;
}

$oggi = date('Y-m-d');
$dataSelezionata = $_POST['sel_date'] ?? '';
$idCorsoSelezionato = (int) ($_POST['id_course'] ?? 0);

// -----------------------------------------------------------------------------------
// 1) Recupera date e corsi disponibili per le assenze passate
if ($isAdmin) {
    // Se l'utente è admin o sadmin: visualizza solo i corsi a lui assegnati.
    $sql = "
        SELECT DISTINCT a.date, a.id_course, c.name AS course_name
        FROM attendance a
        JOIN courses c ON a.id_course = c.id_course
        WHERE a.date < ?
          AND a.id_course IN (
              SELECT id_course 
              FROM user_role_courses 
              WHERE id_user = ? 
                AND id_role IN (3,4)
          )
        ORDER BY a.date DESC, c.name
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $oggi, $user['id_user']);
} else {
    // Se l'utente è docente: visualizza solo le presenze create da lui.
    $sql = "
        SELECT DISTINCT a.date, a.id_course, c.name AS course_name
        FROM attendance a
        JOIN courses c ON a.id_course = c.id_course
        WHERE a.created_by = ?
          AND a.date < ?
        ORDER BY a.date DESC, c.name
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $user['id_user'], $oggi);
}
$stmt->execute();
$res = $stmt->get_result();

$opzioni = [];
while ($row = $res->fetch_assoc()) {
    $opzioni[] = $row;
}
$stmt->close();

?>
<!-- Form per selezionare la data e un corso -->
<form method="post">
    <label>Seleziona una data e un corso:</label><br><br>
    <select name="sel_date" required>
        <option value="">Seleziona</option>
        <?php foreach ($opzioni as $opt): 
            $valCombo = htmlspecialchars($opt['date'] . '|' . $opt['id_course']);
            $labelCombo = htmlspecialchars($opt['date'] . ' - ' . $opt['course_name']);
        ?>
            <option value="<?= $valCombo; ?>"><?= $labelCombo; ?></option>
        <?php endforeach; ?>
    </select>
    <br><br>
    <button type="submit" name="mostra_presenze">Mostra Presenze</button>
</form>

<?php
// -----------------------------------------------------------------------------------
// 2) Se è stata selezionata una data e un corso, mostra l'elenco delle presenze
if (isset($_POST['mostra_presenze']) && !empty($_POST['sel_date'])) {
    list($dataSelezionata, $idCorsoSelezionato) = explode('|', $_POST['sel_date']);

    if ($isAdmin) {
        // Per l'admin mostriamo solo le presenze dei corsi a lui assegnati.
        $sql = "
            SELECT a.id, a.id_user, a.entry_hour, a.exit_hour,
                   u.firstname, u.lastname
            FROM attendance a
            JOIN users u ON a.id_user = u.id_user
            WHERE a.id_course = ?
              AND a.date = ?
              AND a.id_course IN (
                  SELECT id_course 
                  FROM user_role_courses 
                  WHERE id_user = ? 
                    AND id_role IN (3,4)
              )
            ORDER BY u.lastname, u.firstname
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isi', $idCorsoSelezionato, $dataSelezionata, $user['id_user']);
    } else {
        // Se docente, mostra solo le presenze create da lui.
        $sql = "
            SELECT a.id, a.id_user, a.entry_hour, a.exit_hour,
                   u.firstname, u.lastname
            FROM attendance a
            JOIN users u ON a.id_user = u.id_user
            WHERE a.created_by = ?
              AND a.id_course = ?
              AND a.date = ?
            ORDER BY u.lastname, u.firstname
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $user['id_user'], $idCorsoSelezionato, $dataSelezionata);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $presenze = [];
    while ($row = $res->fetch_assoc()) {
        $presenze[] = $row;
    }
    $stmt->close();

    if (!$presenze) {
        echo "<script>Swal.fire('Errore', 'Nessuna presenza trovata per questa data.', 'warning');</script>";
        exit;
    }

    // Recupera gli orari "min" e "max" dal DB per il giorno selezionato.
    list($giornoStart, $giornoEnd) = dailyCourseTimes($conn, $idCorsoSelezionato, $dataSelezionata);
    ?>
    <h3>Modifica Presenze del <?= htmlspecialchars($dataSelezionata) ?></h3>
    <form method="post">
        <!-- Ripassiamo data e corso -->
        <input type="hidden" name="sel_date" value="<?= htmlspecialchars($dataSelezionata) ?>" />
        <input type="hidden" name="id_course" value="<?= (int) $idCorsoSelezionato ?>" />

        <table class="attendance-table">
            <tr>
                <th>Studente</th>
                <th>Presente</th>
                <th>Ora Ingresso</th>
                <th>Ora Uscita</th>
            </tr>
            <?php foreach ($presenze as $p):
                $idAtt       = $p['id'];
                $cognomeNome = $p['lastname'] . " " . $p['firstname'];
                $entryH      = $p['entry_hour'];
                $exitH       = $p['exit_hour'];
                $isPresente  = !empty($entryH) && !empty($exitH);
            ?>
            <tr>
                <td><?= htmlspecialchars($cognomeNome) ?></td>
                <label class="checkbox">
                <td>
                    <input type="checkbox"
                           class="checkbox__input"
                           name="presenze[<?= $idAtt ?>][presente]"
                           value="1"
                           <?= $isPresente ? 'checked' : '' ?>
                    />
                    <svg class="checkbox__icon" viewBox="0 0 24 24" aria-hidden="true">
                    <rect width="24" height="24" fill="#e0e0e0" rx="4"></rect>
                    <path class="tick" fill="none" stroke="#007bff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M6 12l4 4 8-8"></path>
                  </svg>
                </td>
                </label>
                <td>
                    <input type="time"
                           name="presenze[<?= $idAtt ?>][entry]"
                           value="<?= htmlspecialchars($entryH) ?>"
                           step="60"
                           min="<?= $giornoStart ?>"
                           max="<?= $giornoEnd ?>"
                    />
                </td>
                <td>
                    <input type="time"
                           name="presenze[<?= $idAtt ?>][exit]"
                           value="<?= htmlspecialchars($exitH) ?>"
                           step="60"
                           min="<?= $giornoStart ?>"
                           max="<?= $giornoEnd ?>"
                    />
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <br>
    <div class="button-container">
        <button type="submit" class="save" name="salva_modifiche">Salva Modifiche</button>
    </form>
        <?php
            if (in_array('docente', $user['roles'])) {
                echo '<button class="back" type="button" onclick="window.location.href=\'doc_panel.php\'">Indietro</button>';
            } else {
                echo '<button class="back" type="button" onclick="window.location.href=\'admin_panel.php\'">Indietro</button>';
            }
        ?>
    </div>
    <?php
}


// 3) Salvataggio modifiche
if (isset($_POST['salva_modifiche'])) {
    $dataSelezionata = $_POST['sel_date'] ?? '';
    $idCorsoSelezionato = (int) ($_POST['id_course'] ?? 0);

    if (empty($dataSelezionata) || !$idCorsoSelezionato || empty($_POST['presenze'])) {
        echo "<script>Swal.fire('Errore', 'Nessuna modifica inviata.', 'error');</script>";
        exit;
    }
    
    // Verifica che, in caso di admin, il corso sia assegnato a lui.
    if ($isAdmin) {
        $sqlCheck = "
            SELECT id_course 
            FROM user_role_courses 
            WHERE id_course = ? 
              AND id_user = ? 
              AND id_role IN (3,4)
            LIMIT 1
        ";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param('ii', $idCorsoSelezionato, $user['id_user']);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        if ($resCheck->num_rows === 0) {
            echo "<script>Swal.fire('Errore', 'Non hai i permessi per modificare le presenze di questo corso.', 'error');</script>";
            exit;
        }
        $stmtCheck->close();
    }

    foreach ($_POST['presenze'] as $idAttendance => $info) {
        $idAttendance = (int)$idAttendance;
        $isPresente   = isset($info['presente']) ? 1 : 0;
        // Prendiamo gli orari inviati (se stringa vuota => null)
        $entryHour = !empty($info['entry']) ? $info['entry'] : null;
        $exitHour  = !empty($info['exit'])  ? $info['exit']  : null;

        if (!$isPresente) {
            // Assente: orari a NULL
            $entryHour = null;
            $exitHour  = null;
        } else {
            // Presente: se i campi sono vuoti, assegniamo i default dal DB
            if (empty($entryHour) && empty($exitHour)) {
                list($startDay, $endDay) = dailyCourseTimes($conn, $idCorsoSelezionato, $dataSelezionata);
                $entryHour = $startDay;
                $exitHour  = $endDay;
            }
        }

        if ($isAdmin) {
            $sqlUpdate = "
                UPDATE attendance
                SET entry_hour = ?, exit_hour = ?
                WHERE id = ? 
                  AND id_course IN (
                      SELECT id_course 
                      FROM user_role_courses 
                      WHERE id_user = ? 
                        AND id_role IN (3,4)
                  )
            ";
            $stmtU = $conn->prepare($sqlUpdate);
            $stmtU->bind_param('ssii', $entryHour, $exitHour, $idAttendance, $user['id_user']);
        } else {
            // Per il docente, aggiornamento solo delle presenze create da lui.
            $sqlUpdate = "
                UPDATE attendance
                SET entry_hour = ?, exit_hour = ?
                WHERE id = ? 
                  AND created_by = ?
            ";
            $stmtU = $conn->prepare($sqlUpdate);
            $stmtU->bind_param('ssii', $entryHour, $exitHour, $idAttendance, $user['id_user']);
        }
        $stmtU->execute();
        $stmtU->close();
    }

    echo "<script>
        Swal.fire({
            title: 'Successo!',
            text: 'Registro salvato con successo.',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location.href = '" . $_SERVER['PHP_SELF'] . "?sel_date=" . urlencode($dataSelezionata)
                . "&id_course=" . $idCorsoSelezionato . "';
        });
    </script>";
    exit;
}
?>
