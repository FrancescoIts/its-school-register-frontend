<?php
require_once 'config.php';
require_once 'check_session.php';

// Controllo sessione e ruolo admin
$user = checkSession(true, ['admin']);
$id_admin = $user['id_user'] ?? 0;

$idRoleAdmin    = 3;
$idRoleStudente = 1;

/**
 * Funzione per ottenere gli orari di inizio/fine corso in base al giorno
 */
function getCourseTimes($conn, $id_course, $day_of_week) {
    $query = "
        SELECT start_time_{$day_of_week} AS start_time, end_time_{$day_of_week} AS end_time
        FROM courses
        WHERE id_course = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_course);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return [$result['start_time'] ?? null, $result['end_time'] ?? null];
}

// 1) Recupera TUTTI i corsi di competenza di questo admin
$sqlCourses = "
    SELECT c.id_course, c.name
    FROM courses c
    JOIN user_role_courses urc ON urc.id_course = c.id_course
    WHERE urc.id_user = ?
      AND urc.id_role = ?
";
$stmtC = $conn->prepare($sqlCourses);
$stmtC->bind_param("ii", $id_admin, $idRoleAdmin);
$stmtC->execute();
$resC = $stmtC->get_result();

$courseList = [];
while ($r = $resC->fetch_assoc()) {
    $courseList[$r['id_course']] = $r['name'];
}
$stmtC->close();

// Se l'admin non ha corsi associati, usciamo
if (empty($courseList)) {
    die("<h3>Nessun corso associato a questo admin</h3>");
}

// 2) Determiniamo quale corso visualizzare
if (isset($_GET['course_id']) && array_key_exists($_GET['course_id'], $courseList)) {
    $selectedCourse = (int)$_GET['course_id'];
} else {
    // Se non specificato, prendi il primo corso disponibile
    $selectedCourse = array_key_first($courseList);
}

// 3) Recupero tutti gli studenti del corso selezionato
$sqlStudents = "
    SELECT DISTINCT u.id_user, u.firstname, u.lastname
    FROM user_role_courses urc
    JOIN users u ON u.id_user = urc.id_user
    WHERE urc.id_course = ?
      AND urc.id_role = ?
";
$stmtS = $conn->prepare($sqlStudents);
$stmtS->bind_param("ii", $selectedCourse, $idRoleStudente);
$stmtS->execute();
$resS = $stmtS->get_result();

$studentIds   = [];
$studentsMap  = [];  // Per collegare id_user a "Nome Cognome"
while ($rowS = $resS->fetch_assoc()) {
    $idU = (int)$rowS['id_user'];
    $studentIds[]      = $idU;
    $studentsMap[$idU] = trim($rowS['firstname'].' '.$rowS['lastname']);
}
$stmtS->close();

// Se non ci sono studenti, usciamo
if (empty($studentIds)) {
    die("<h3>Non ci sono studenti iscritti a questo corso</h3>");
}

// 4) Recupero i parametri del calendario (in questo caso usiamo solo l'anno)
$currentYear  = isset($_GET['year2']) ? (int)$_GET['year2'] : date('Y');

// 5) Recupero le assenze di TUTTI gli studenti di quel corso per l'anno corrente
$inClauseStd = implode(',', array_fill(0, count($studentIds), '?'));

$sqlA = "
    SELECT id_user, date, entry_hour, exit_hour
    FROM attendance
    WHERE id_user IN ($inClauseStd)
      AND id_course = ?
      AND YEAR(date) = ?
";
$stmtA = $conn->prepare($sqlA);

// Costruiamo i parametri
$types = str_repeat('i', count($studentIds)) . 'ii'; 
$params = array_merge($studentIds, [$selectedCourse, $currentYear]);
$stmtA->bind_param($types, ...$params);

$stmtA->execute();
$resA = $stmtA->get_result();

/*
   Raggruppiamo le assenze per studente:
   $studentAbsences[id_user] = array di record
       [
         'date'       => data dell'assenza,
         'entry_hour' => ora di entrata,
         'exit_hour'  => ora di uscita,
         'hours'      => ore di assenza calcolate
       ]
*/
$studentAbsences = [];

while ($row = $resA->fetch_assoc()) {
    $date     = $row['date'];
    $id_user  = (int)$row['id_user'];
    $entry    = $row['entry_hour'] ?? null;
    $exit     = $row['exit_hour']  ?? null;

    // Otteniamo il giorno della settimana
    $dayOfWeek = strtolower(date('l', strtotime($date)));
    list($courseStart, $courseEnd) = getCourseTimes($conn, $selectedCourse, $dayOfWeek);

    // Se non ci sono orari impostati per quel giorno, saltiamo
    if (!$courseStart || !$courseEnd) {
        continue;
    }

    $courseStartSec = strtotime($courseStart);
    $courseEndSec   = strtotime($courseEnd);
    $courseDurationHours = ($courseEndSec - $courseStartSec) / 3600;

    // Calcoliamo le ore di assenza
    $absenceHours = 0;
    if (!$entry || !$exit) {
        // Assenza totale
        $absenceHours = $courseDurationHours;
    } else {
        $entrySec  = strtotime($entry);
        $exitSec   = strtotime($exit);

        // Limitiamo la presenza agli orari del corso
        $presenceStartSec = max($entrySec, $courseStartSec);
        $presenceEndSec   = min($exitSec, $courseEndSec);

        $presenceHours = 0;
        if ($presenceEndSec > $presenceStartSec) {
            $presenceHours = ($presenceEndSec - $presenceStartSec) / 3600;
        }
        $absenceHours = $courseDurationHours - $presenceHours;
    }

    if ($absenceHours > 0) {
        if (!isset($studentAbsences[$id_user])) {
            $studentAbsences[$id_user] = [];
        }
        $studentAbsences[$id_user][] = [
            'date'       => $date,
            'entry_hour' => $entry,
            'exit_hour'  => $exit,
            'hours'      => $absenceHours
        ];
    }
}
$stmtA->close();

// Otteniamo il nome del corso selezionato
$selectedCourseName = $courseList[$selectedCourse] ?? "Corso #$selectedCourse";
?>
<div class="container">
    <!-- Form per la scelta del corso -->
    <?php if (count($courseList) > 1): ?>
        <div id="course-select-form">
            <form method="GET" action="">
                <label for="course_id">Seleziona il corso:</label>
                <select name="course_id" id="course_id" onchange="this.form.submit()">
                    <?php
                    foreach ($courseList as $id_corso => $nome_corso) {
                        $selectedAttr = ($id_corso == $selectedCourse) ? 'selected' : '';
                        echo "<option value='$id_corso' $selectedAttr>$nome_corso</option>";
                    }
                    ?>
                </select>
                <input type="hidden" name="year2" value="<?php echo $currentYear; ?>">
            </form>
        </div>
        <br>
    <?php else: ?>
        <!-- Se c’è un solo corso -->
        <h3 style="text-align:center;">
            Corso: <?php echo htmlspecialchars($selectedCourseName); ?>
        </h3>
    <?php endif; ?>

    <h3 style="text-align:center;">Assenze anno <?php echo $currentYear; ?></h3>

    <?php
    // Se nessuno studente ha assenze, stampiamo un messaggio
    if (empty($studentAbsences)) {
        echo '<div style="text-align: center; margin-top:20px;">Nessuna assenza registrata</div>';
    } else {
        // Per ogni studente che ha assenze
        foreach ($studentAbsences as $id_user => $records) {
            // Nome dello studente
            $studentName = $studentsMap[$id_user] ?? "Studente #$id_user";

            // Ordiniamo i record di assenza per data (facoltativo)
            usort($records, function($a, $b) {
                return strtotime($a['date']) <=> strtotime($b['date']);
            });

            // Calcoliamo il totale ore di assenza
            $totalAbsences = 0;
            foreach ($records as $rec) {
                $totalAbsences += $rec['hours'];
            }

            // Stampa un titolo per lo studente
            echo '<h4 style="margin-top: 20px;">'.$studentName.'</h4>';

            // Costruiamo la tabella
            echo '<div class="responsive-table">';
            echo '  <div class="responsive-table__head responsive-table__row">';
            echo '      <div class="responsive-table__head__title">Data</div>';
            echo '      <div class="responsive-table__head__title">Entrata</div>';
            echo '      <div class="responsive-table__head__title">Uscita</div>';
            echo '      <div class="responsive-table__head__title">Ore di Assenza</div>';
            echo '  </div>';
            
            // Riga di dati
            foreach ($records as $rec) {
                $entry = $rec['entry_hour'] ? htmlspecialchars($rec['entry_hour']) : '/';
                $exit  = $rec['exit_hour']  ? htmlspecialchars($rec['exit_hour'])  : '/';
                echo '<div class="responsive-table__row">';
                echo '  <div class="responsive-table__body__text" data-title="Data">'.htmlspecialchars($rec['date']).'</div>';
                echo '  <div class="responsive-table__body__text" data-title="Entrata">'.$entry.'</div>';
                echo '  <div class="responsive-table__body__text" data-title="Uscita">'.$exit.'</div>';
                echo '  <div class="responsive-table__body__text" data-title="Ore di Assenza">'.number_format($rec['hours'], 2, ',', '').'</div>';
                echo '</div>';
            }

            // Ultima riga per il totale ore di assenza (in grassetto)
            echo '<div class="responsive-table__row last-row" style="font-weight: bold;">';
            echo '  <div class="responsive-table__body__text" data-title="Totale" style="grid-column: 1 / 4;">Totale ore di assenza</div>';
            echo '  <div class="responsive-table__body__text" data-title="Ore di Assenza">'.number_format($totalAbsences, 2, ',', '').'</div>';
            echo '</div>';

            echo '</div>'; // Fine .responsive-table

            // Pulsante per il calcolo della percentuale e copia in clipboard
            echo '<button onclick="calcolaPercentuale('
                 . '\'' . addslashes($studentName) . '\', '
                 . '\'' . addslashes($selectedCourseName) . '\', '
                 . '\'' . number_format($totalAbsences, 2, '.', '') . '\')">
                 Calcola % assenze e copia
                </button>';
        }
    }
    ?>

</div>