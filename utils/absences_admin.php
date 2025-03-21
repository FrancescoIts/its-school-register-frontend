<?php
require_once 'config.php';
require_once 'check_session.php';

checkSession(true, ['admin']);
$id_admin = $user['id_user'] ?? 0;

$idRoleAdmin    = 3;
$idRoleStudente = 1;

/**
 * Funzione per ottenere gli orari di inizio/fine corso in base al giorno.
 * Se il giorno richiesto non è tra quelli contemplati (lun-ven), restituisce [null, null].
 */
function getCourseTimes($conn, $id_course, $day_of_week) {
    // Definisci i giorni consentiti
    $allowedDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    if (!in_array($day_of_week, $allowedDays)) {
        return [null, null];
    }
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
    SELECT c.id_course, c.name, c.year, c.total_hour
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
$courseData = []; // Per conservare anche il campo year e total_hour
while ($r = $resC->fetch_assoc()) {
    $courseList[$r['id_course']] = $r['name'];
    $courseData[$r['id_course']] = $r;
}
$stmtC->close();

if (empty($courseList)) {
    die("<h3>Nessun corso associato a questo admin</h3>");
}

// 2) Determiniamo quale corso visualizzare
if (isset($_GET['course_id']) && array_key_exists($_GET['course_id'], $courseList)) {
    $selectedCourse = (int)$_GET['course_id'];
} else {
    $selectedCourse = array_key_first($courseList);
}
$selectedCourseName = $courseList[$selectedCourse] ?? "Corso #$selectedCourse";

// Recupero i dati del corso
$courseInfo = $courseData[$selectedCourse] ?? null;
if (!$courseInfo) {
    die("<h3>Errore: corso non trovato.</h3>");
}

/*
 Calcola l'anno accademico corrente.
 Supponiamo che il corso inizi il 1° ottobre dell'anno indicato in c.year.
 Se il mese corrente è >= ottobre, l'anno accademico corrente è l'anno in corso,
 altrimenti è l'anno corrente - 1.
*/
$currentMonth  = date('n');
$academicYear  = ($currentMonth >= 10) ? date('Y') : (date('Y') - 1);
$startAcademic = $academicYear . "-10-01";
$endAcademic   = ($academicYear + 1) . "-09-30";
$resetStats = ($academicYear > $courseInfo['year']);  // Se l'anno accademico corrente è maggiore dell'anno di inizio

$totalMaxHours = (int)$courseInfo['total_hour'];

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
$studentsMap  = [];
while ($rowS = $resS->fetch_assoc()) {
    $idU = (int)$rowS['id_user'];
    $studentIds[] = $idU;
    $studentsMap[$idU] = trim($rowS['firstname'].' '.$rowS['lastname']);
}
$stmtS->close();

if (empty($studentIds)) {
    die("<h3>Non ci sono studenti iscritti a questo corso</h3>");
}

// 4) Recupero le assenze degli studenti per l'anno accademico corrente
$inClauseStd = implode(',', array_fill(0, count($studentIds), '?'));
$sqlA = "
    SELECT id_user, date, entry_hour, exit_hour
    FROM attendance
    WHERE id_user IN ($inClauseStd)
      AND id_course = ?
      AND date BETWEEN ? AND ?
";
$stmtA = $conn->prepare($sqlA);
$types = str_repeat('i', count($studentIds)) . 'iss';
$params = array_merge($studentIds, [$selectedCourse, $startAcademic, $endAcademic]);
$stmtA->bind_param($types, ...$params);
$stmtA->execute();
$resA = $stmtA->get_result();

$studentAbsences = [];
while ($row = $resA->fetch_assoc()) {
    $date = $row['date'];
    $id_user = (int)$row['id_user'];
    $entry = $row['entry_hour'] ?? null;
    $exit  = $row['exit_hour'] ?? null;

    // Calcola il giorno della settimana in minuscolo (es. monday, tuesday, ...)
    $dayOfWeek = strtolower(date('l', strtotime($date)));
    
    // Se il giorno è sabato o domenica, salta il record
    if ($dayOfWeek === 'saturday' || $dayOfWeek === 'sunday') {
        continue;
    }
    
    list($courseStart, $courseEnd) = getCourseTimes($conn, $selectedCourse, $dayOfWeek);
    if (!$courseStart || !$courseEnd) {
        continue;
    }
    $courseStartSec = strtotime($courseStart);
    $courseEndSec   = strtotime($courseEnd);
    $courseDurationHours = ($courseEndSec - $courseStartSec) / 3600;

    $absenceHours = 0;
    if (!$entry || !$exit) {
        $absenceHours = $courseDurationHours;
    } else {
        $entrySec = strtotime($entry);
        $exitSec  = strtotime($exit);
        $presenceStartSec = max($entrySec, $courseStartSec);
        $presenceEndSec = min($exitSec, $courseEndSec);
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
            'date' => $date,
            'entry_hour' => $entry,
            'exit_hour' => $exit,
            'hours' => $absenceHours
        ];
    }
}
$stmtA->close();
?>
<div class="container">
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
                <input type="hidden" name="year2" value="<?php echo $academicYear; ?>">
            </form>
        </div>
        <br>
    <?php else: ?>
        <h3 style="text-align:center;">
            Corso: <?php echo htmlspecialchars($selectedCourseName); ?>
        </h3>
    <?php endif; ?>

    <h3 style="text-align:center;">Assenze anno <?php echo $academicYear . " - " . ($academicYear + 1); ?>
        <?php if ($resetStats): ?>
            <span style="font-size:0.8em; color:#007bff;">(Statistiche resettate per il nuovo anno)</span>
        <?php endif; ?>
    </h3>

    <div id="filter-container" style="text-align:center; margin-bottom:20px;">
        <input type="text" id="studentFilter" placeholder="Filtra per studente..." style="padding:5px; width:100%;">
    </div>

    <?php
    if (empty($studentAbsences)) {
        echo '<div style="text-align: center; margin-top:20px;">Nessuna assenza registrata</div>';
    } else {
        foreach ($studentAbsences as $id_user => $records) {
            $studentName = $studentsMap[$id_user] ?? "Studente #$id_user";
            usort($records, function($a, $b) {
                return strtotime($a['date']) <=> strtotime($b['date']);
            });
            $totalAbsences = 0;
            foreach ($records as $rec) {
                $totalAbsences += $rec['hours'];
            }
            echo '<div class="student-block">';
            echo '<h4 style="margin-top: 20px;">' . htmlspecialchars($studentName) . '</h4>';
            echo '<div class="responsive-table">';
            echo '  <div class="responsive-table__head responsive-table__row">';
            echo '      <div class="responsive-table__head__title">Data</div>';
            echo '      <div class="responsive-table__head__title">Entrata</div>';
            echo '      <div class="responsive-table__head__title">Uscita</div>';
            echo '      <div class="responsive-table__head__title">Ore di Assenza</div>';
            echo '  </div>';
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
            echo '<div class="responsive-table__row last-row" style="font-weight: bold;">';
            echo '  <div class="responsive-table__body__text" data-title="Totale" style="grid-column: 1 / 4;">Totale ore di assenza</div>';
            echo '  <div class="responsive-table__body__text" data-title="Ore di Assenza">'.number_format($totalAbsences, 2, ',', '').'</div>';
            echo '</div>';
            echo '</div>'; // Fine responsive-table
            echo '<button onclick="calcolaPercentuale('
                 . '\'' . addslashes($studentName) . '\', '
                 . '\'' . addslashes($selectedCourseName) . '\', '
                 . '\'' . number_format($totalAbsences, 2, '.', '') . '\')">
                 Calcola % assenze e copia
                </button>';
            echo '</div>';
        }
    }
    ?>
</div>

<script>
document.getElementById('studentFilter').addEventListener('keyup', function() {
    var filterValue = this.value.toLowerCase();
    var studentBlocks = document.querySelectorAll('.student-block');
    studentBlocks.forEach(function(block) {
        var studentName = block.querySelector('h4').textContent.toLowerCase();
        block.style.display = studentName.includes(filterValue) ? '' : 'none';
    });
});
</script>
