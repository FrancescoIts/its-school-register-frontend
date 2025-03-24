<?php
require_once 'config.php';
require_once 'check_session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$user = checkSession(true, ['studente']);
$id_user = $user['id_user'] ?? 0;

// Recupero del corso associato allo studente
$stmtCourse = $conn->prepare("
  SELECT id_course FROM user_role_courses
  WHERE id_user = ? LIMIT 1
");
$stmtCourse->bind_param("i", $id_user);
$stmtCourse->execute();
$resultCourse = $stmtCourse->get_result()->fetch_assoc();
$stmtCourse->close();

$id_course = $resultCourse['id_course'] ?? null;

if (!$id_course) {
    die("Nessun corso associato all'utente.");
}

/**
 * Funzione per recuperare gli orari dal database in base al giorno della settimana
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
    return [$result['start_time'], $result['end_time']];
}

/*
 * Calcolo dell'anno accademico corrente.
 * Se il mese corrente è >= ottobre (10) l'anno accademico inizia nell'anno corrente,
 * altrimenti (mese da gennaio a luglio) si considera l'anno precedente.
 * L'anno di studi va dal 1° ottobre al 31 luglio.
 */
$currentMonth = (int)date('n');
$currentYear  = (int)date('Y');
$academicYear = ($currentMonth >= 10) ? $currentYear : $currentYear - 1;
$startAcademic = $academicYear . "-10-01";
$endAcademic   = ($academicYear + 1) . "-07-31";

// Recupero delle assenze per l'anno accademico corrente
$stmtA = $conn->prepare("
  SELECT date, entry_hour, exit_hour
  FROM attendance
  WHERE id_user = ?
    AND id_course = ?
    AND date BETWEEN ? AND ?
");
$stmtA->bind_param("iiss", $id_user, $id_course, $startAcademic, $endAcademic);
$stmtA->execute();
$resA = $stmtA->get_result();

$absences = [];
while ($row = $resA->fetch_assoc()) {
    $date = $row['date'];
    $entry = $row['entry_hour'] ?? null;
    $exit  = $row['exit_hour'] ?? null;

    // Ottieni il giorno della settimana in inglese minuscolo
    $dayOfWeek = strtolower(date('l', strtotime($date)));

    // Recupera gli orari standard per il giorno
    list($course_start, $course_end) = getCourseTimes($conn, $id_course, $dayOfWeek);
    
    // Se non ci sono orari per quel giorno, salta il record
    if (!$course_start || !$course_end) {
        continue;
    }

    $standard_entry_seconds = strtotime($course_start);
    $standard_exit_seconds  = strtotime($course_end);

    // Se manca l'entrata o l'uscita, consideriamo l'assenza totale della lezione
    if (!$entry || !$exit) {
        $absence_hours = ($standard_exit_seconds - $standard_entry_seconds) / 3600;
        $absences[$date] = round($absence_hours, 2);
        continue;
    }

    $entry_seconds = strtotime($entry);
    $exit_seconds  = strtotime($exit);

    $absence_hours = 0;
    if ($entry_seconds > $standard_entry_seconds) {
        $absence_hours += ($entry_seconds - $standard_entry_seconds) / 3600;
    }
    if ($exit_seconds < $standard_exit_seconds) {
        $absence_hours += ($standard_exit_seconds - $exit_seconds) / 3600;
    }
    if ($absence_hours > 0) {
        $absences[$date] = round($absence_hours, 2);
    }
}
$stmtA->close();

// Ordino le date in ordine crescente
ksort($absences);
?>
    <div class="container">
        <!-- Intestazione con l'anno accademico corrente -->
        <div style="text-align:center; margin-bottom:10px;" class="calendar-header">
            <span style="font-size:1.2em; font-weight:bold; margin: 0 10px;">
                Assenze anno <?php echo $academicYear . " - " . ($academicYear + 1); ?>
            </span>
        </div>

        <!-- Tabella Responsive -->
        <div class="responsive-table">
            <div class="responsive-table__head responsive-table__row">
                <div class="responsive-table__head__title">Data</div>
                <div class="responsive-table__head__title">Ore di Assenza</div>
            </div>
            <div class="responsive-table__body">
                <?php if (empty($absences)) : ?>
                    <div class="responsive-table__row">
                        <div class="responsive-table__body__text" data-title="Messaggio" style="grid-column: 1 / -1; text-align:center;">
                            Nessuna assenza registrata per questo anno accademico.
                        </div>
                    </div>
                <?php else : ?>
                    <?php foreach ($absences as $date => $hours) : ?>
                        <div class="responsive-table__row">
                            <div class="responsive-table__body__text" data-title="Data"><?php echo htmlspecialchars(date('d/m/Y', strtotime($date))); ?></div>
                            <div class="responsive-table__body__text" data-title="Ore di Assenza"><?php echo number_format($hours, 2, ',', ''); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

