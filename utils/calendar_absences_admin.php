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
    $studentIds[]             = $idU;
    $studentsMap[$idU]        = trim($rowS['firstname'].' '.$rowS['lastname']);
}
$stmtS->close();

// Se non ci sono studenti, usciamo
if (empty($studentIds)) {
    die("<h3>Non ci sono studenti iscritti a questo corso</h3>");
}

// 4) Recupero i parametri del calendario (mese/anno correnti o parametri GET)
$currentMonth = isset($_GET['month2']) ? (int)$_GET['month2'] : date('n');
$currentYear  = isset($_GET['year2'])  ? (int)$_GET['year2']  : date('Y');

// 5) Recupero le assenze di TUTTI gli studenti di quel corso per l'anno corrente
//    e le salviamo con il dettaglio per ogni studente
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

$absences = []; 
/*
   $absences sarà un array associativo con chiave = data (es. '2025-03-15'),
   e valore = array di record [ 'id_user' => ..., 'student_name' => ..., 'hours' => ... ].
   In questo modo teniamo traccia di TUTTI gli studenti che hanno assenze in quel giorno.
*/
while ($row = $resA->fetch_assoc()) {
    $date     = $row['date'];
    $id_user  = (int)$row['id_user'];
    $entry    = $row['entry_hour'] ?? null;
    $exit     = $row['exit_hour']  ?? null;

    // Ottieni il giorno della settimana in inglese minuscolo (monday, tuesday, ...)
    $dayOfWeek = strtolower(date('l', strtotime($date)));
    list($courseStart, $courseEnd) = getCourseTimes($conn, $selectedCourse, $dayOfWeek);

    // Se il corso non ha orari definiti per quel giorno, skip
    if (!$courseStart || !$courseEnd) {
        continue;
    }
    $courseStartSec = strtotime($courseStart);
    $courseEndSec   = strtotime($courseEnd);

    $absenceHours = 0;
    // Se mancano entry/exit => assenza totale per lo studente
    if (!$entry || !$exit) {
        $absenceHours = ($courseEndSec - $courseStartSec) / 3600;
    } else {
        $entrySec = strtotime($entry);
        $exitSec  = strtotime($exit);
        // Entrato in ritardo?
        if ($entrySec > $courseStartSec) {
            $absenceHours += ($entrySec - $courseStartSec) / 3600;
        }
        // Uscito prima?
        if ($exitSec < $courseEndSec) {
            $absenceHours += ($courseEndSec - $exitSec) / 3600;
        }
    }

    // Se ci sono ore di assenza (o ritardo/anticipo) da registrare
    if ($absenceHours > 0) {
        // Inizializziamo l'array se non esiste
        if (!isset($absences[$date])) {
            $absences[$date] = [];
        }
        // Aggiungiamo una voce di dettaglio per quello studente
        $absences[$date][] = [
            'id_user'      => $id_user,
            'student_name' => $studentsMap[$id_user] ?? "Studente #$id_user",
            'hours'        => $absenceHours
        ];
    }
}
$stmtA->close();

// 6) Creiamo il calendario in stile "c-calendar__style"
$monthsIta    = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
$monthName    = $monthsIta[$currentMonth - 1];
$firstDay     = date('N', strtotime("$currentYear-$currentMonth-01")); // 1 = Lun, 7 = Dom
$daysInMonth  = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
$monthNameUp  = strtoupper($monthName);

$html = '';

// Navigazione in alto
$html .= '<div class="calendar-header" style="text-align:center; margin-bottom:10px;">';
$html .= '<button type="button" class="prev-month o-btn" onclick="loadAbsencesCalendar('
        . ($currentMonth == 1 ? 12 : $currentMonth - 1) . ', '
        . ($currentMonth == 1 ? $currentYear - 1 : $currentYear)
        . ')"><strong>&#8810;</strong></button>';

$html .= '<span class="current-month" style="font-size:1.2em; font-weight:bold; margin: 0 10px;">'
       . "$monthNameUp $currentYear"
       . '</span>';

$html .= '<button type="button" class="next-month o-btn" onclick="loadAbsencesCalendar('
        . ($currentMonth == 12 ? 1 : $currentMonth + 1) . ', '
        . ($currentMonth == 12 ? $currentYear + 1 : $currentYear)
        . ')"><strong>&#8811;</strong></button>';
$html .= '</div>';

// Calendario con c-calendar__style
$html .= '<div class="c-cal__container c-calendar__style"><div class="c-cal__container">';
$html .= '<div class="c-cal__row">';
foreach (['Lun','Mar','Mer','Gio','Ven','Sab','Dom'] as $dName) {
    $html .= "<div class='c-cal__col'>{$dName}</div>";
}
$html .= '</div>';

// Celle dei giorni
$totalCells = ($firstDay - 1) + $daysInMonth;
$totalRows  = ceil($totalCells / 7);
$dayCounter = 1;

for ($r = 0; $r < $totalRows; $r++) {
    $html .= '<div class="c-cal__row">';
    for ($c = 0; $c < 7; $c++) {
        $cellIndex = $r * 7 + $c + 1;
        if ($cellIndex < $firstDay || $dayCounter > $daysInMonth) {
            // Celle vuote
            $html .= '<div class="c-cal__cel"></div>';
        } else {
            // Giorno reale
            $dayStr    = str_pad($dayCounter, 2, '0', STR_PAD_LEFT);
            $monthStr  = str_pad($currentMonth, 2, '0', STR_PAD_LEFT);
            $dateString = "$currentYear-$monthStr-$dayStr";
            
            // Recuperiamo i record di assenza
            $detailAbs = $absences[$dateString] ?? [];
            // Se c'è almeno un record di assenza
            $hasAbsence = (count($detailAbs) > 0);
            $cellClass  = "c-cal__cel" . ($hasAbsence ? " event" : "");

            // Convertiamo l'array di dettagli in JSON, così il JS può leggere tutti gli studenti
            $jsonDetails = htmlspecialchars(json_encode($detailAbs), ENT_QUOTES, 'UTF-8');
            
            $html .= "<div class='{$cellClass}' data-date='{$dateString}' data-absence='{$jsonDetails}'>";
            $html .= "<p>{$dayCounter}</p>";
            $html .= "</div>";
            $dayCounter++;
        }
    }
    $html .= '</div>';
}
$html .= '</div></div>';

// Se la richiesta è via AJAX, restituiamo solo il calendario
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    echo $html;
    exit;
}
?>
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
    </form>
</div>
<br>
<?php else: ?>
    <!-- Se c’è un solo corso -->
    <h3 style="text-align:center;">
        Corso: <?php echo htmlspecialchars($courseList[$selectedCourse]); ?>
    </h3>
<?php endif; ?>
<div class="wrapper">
<div id="calendar-absences-content">
    <?php echo $html; ?>
</div>
</div>
