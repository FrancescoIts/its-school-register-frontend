<?php
require_once 'config.php';
require_once 'check_session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$user = checkSession(true, ['studente']);
$id_user = $user['id_user'] ?? 0;


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
 * Funzione per recuperare gli orari dal database
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

// Recupero dei parametri per il calendario delle assenze
$currentMonth = isset($_GET['month2']) ? (int)$_GET['month2'] : date('n');
$currentYear  = isset($_GET['year2']) ? (int)$_GET['year2'] : date('Y');

// Recupero delle assenze
$stmtA = $conn->prepare("
  SELECT date, entry_hour, exit_hour
  FROM attendance
  WHERE id_user = ?
    AND id_course = ?
    AND YEAR(date) = ?
");
$stmtA->bind_param("iii", $id_user, $id_course, $currentYear);
$stmtA->execute();
$resA = $stmtA->get_result();

$absences = [];
while ($row = $resA->fetch_assoc()) {
    $date = $row['date'];
    $entry = $row['entry_hour'] ?? null;
    $exit  = $row['exit_hour'] ?? null;

    // Ottieni il giorno della settimana in inglese
    $dayOfWeek = strtolower(date('l', strtotime($date)));

    // Ottieni gli orari del corso per quel giorno
    list($course_start, $course_end) = getCourseTimes($conn, $id_course, $dayOfWeek);
    if (!$course_start || !$course_end) {
        continue;
    }

    $standard_entry_seconds = strtotime($course_start);
    $standard_exit_seconds  = strtotime($course_end);

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

// Dati per la generazione del calendario
$monthsIta = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
$monthName = $monthsIta[$currentMonth - 1];
$firstDay = date('N', strtotime("$currentYear-$currentMonth-01")); // 1 = Lun, ... 7 = Dom
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

// Costruzione dell'HTML del calendario delle assenze
$html = '';
$monthName = strtoupper($monthName);
// Header con navigazione
$html .= '<div class="calendar-header" style="text-align:center; margin-bottom:10px;">';
$html .= '<button type="button" id="prevAbsencesBtn" class="prev-month o-btn" onclick="loadAbsencesCalendar(' . ($currentMonth == 1 ? 12 : $currentMonth - 1) . ', ' . ($currentMonth == 1 ? $currentYear - 1 : $currentYear) . ')"><strong>&#8810;</strong></button>';
$html .= '<span class="current-month" style="font-size:1.2em; font-weight:bold; margin: 0 10px;">' . "$monthName $currentYear" . '</span>';
$html .= '<button type="button" id="nextAbsencesBtn" class="next-month o-btn" onclick="loadAbsencesCalendar(' . ($currentMonth == 12 ? 1 : $currentMonth + 1) . ', ' . ($currentMonth == 12 ? $currentYear + 1 : $currentYear) . ')"><strong>&#8811;</strong></button>';
$html .= '</div>';

// Calendario vero e proprio
$html .= '<div class="c-calendar__style"><div class="c-cal__container">';

// Intestazione dei giorni della settimana
$html .= '<div class="c-cal__row">';
foreach (['Lun','Mar','Mer','Gio','Ven','Sab','Dom'] as $dName) {
    $html .= "<div class='c-cal__col'>{$dName}</div>";
}
$html .= '</div>';

// Generazione delle celle
$totalCells = ($firstDay - 1) + $daysInMonth;
$totalRows = ceil($totalCells / 7);
$dayCounter = 1;
for ($r = 0; $r < $totalRows; $r++) {
    $html .= '<div class="c-cal__row">';
    for ($c = 0; $c < 7; $c++) {
        $cellIndex = $r * 7 + $c + 1;
        if ($cellIndex < $firstDay || $dayCounter > $daysInMonth) {
            $html .= '<div class="c-cal__cel"></div>';
        } else {
            $dayStr = str_pad($dayCounter, 2, '0', STR_PAD_LEFT);
            $monthStr = str_pad($currentMonth, 2, '0', STR_PAD_LEFT);
            $dateString = "$currentYear-$monthStr-$dayStr";
            $hasAbsence = isset($absences[$dateString]);
            $cellClass = "c-cal__cel" . ($hasAbsence ? " event" : "");
            $html .= '<div class="' . $cellClass . '" data-date="' . $dateString . '" data-absence="' . ($hasAbsence ? $absences[$dateString] : '') . '">';
            $html .= "<p>$dayCounter</p>";
            $html .= '</div>';
            $dayCounter++;
        }
    }
    $html .= '</div>';
}
$html .= '</div></div>';

echo $html;
exit;
