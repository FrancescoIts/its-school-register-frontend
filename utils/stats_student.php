<?php
require_once 'config.php';
require_once 'check_session.php';

checkSession(true, ['docente', 'admin', 'sadmin']);

header('Content-Type: application/json');

if (!isset($_GET['id_user'])) {
    echo json_encode(['error' => 'Manca il parametro id_user']);
    exit;
}

$id_user = intval($_GET['id_user']);

// Recupera tutti i corsi dell'utente con i rispettivi orari e il campo total_hour
$queryCourses = "
    SELECT c.id_course, 
           c.start_time_monday, c.end_time_monday,
           c.start_time_tuesday, c.end_time_tuesday,
           c.start_time_wednesday, c.end_time_wednesday,
           c.start_time_thursday, c.end_time_thursday,
           c.start_time_friday, c.end_time_friday,
           c.total_hour
    FROM courses c
    JOIN user_role_courses urc ON c.id_course = urc.id_course
    WHERE urc.id_user = ?
";

$stmt = $conn->prepare($queryCourses);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();

$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[$row['id_course']] = $row;
}
$stmt->close();

if (empty($courses)) {
    echo json_encode(['error' => 'Nessun corso associato trovato.']);
    exit;
}

// Imposta il totale massimo delle ore come somma dei campi total_hour di tutti i corsi associati
$total_max_hours = 0;
foreach ($courses as $course) {
    $total_max_hours += (int)$course['total_hour'];
}

// Ottengo l'anno corrente
$currentYear = date('Y');

// Preparazione della query con l'anno corrente come parametro
$queryAttendance = "
    SELECT a.id_course, a.date, a.entry_hour, a.exit_hour
    FROM attendance a
    WHERE a.id_user = ? AND YEAR(a.date) = ?
";

$stmt = $conn->prepare($queryAttendance);
$stmt->bind_param('ii', $id_user, $currentYear);
$stmt->execute();
$result = $stmt->get_result();

$total_absences = 0;
$weekAbsences = [];

// Mappa dei giorni della settimana
$weekdaysMap = [
    1 => "monday", 2 => "tuesday", 3 => "wednesday",
    4 => "thursday", 5 => "friday"
];

while ($row = $result->fetch_assoc()) {
    $id_course = $row['id_course'];
    $date = $row['date'];
    $entry = $row['entry_hour'] ?? null;
    $exit = $row['exit_hour'] ?? null;

    if (!isset($courses[$id_course])) {
        continue;
    }

    $course = $courses[$id_course];
    $weekdayIndex = date('w', strtotime($date)); // 0 = Domenica, 6 = Sabato

    if ($weekdayIndex >= 1 && $weekdayIndex <= 5) {
        $day = $weekdaysMap[$weekdayIndex];

        $start_time_key = "start_time_" . $day;
        $end_time_key = "end_time_" . $day;

        $standard_entry = $course[$start_time_key];
        $standard_exit = $course[$end_time_key];

        if (!$standard_entry || !$standard_exit) {
            continue; // Se non ci sono orari per quel giorno, salta
        }

        // Converto gli orari in secondi
        $entry_seconds = $entry ? strtotime($entry) : null;
        $exit_seconds = $exit ? strtotime($exit) : null;
        $standard_entry_seconds = strtotime($standard_entry);
        $standard_exit_seconds = strtotime($standard_exit);

        // Calcolo delle ore di assenza
        $absence_hours = 0;

        if (!$entry_seconds || !$exit_seconds) {
            $absence_hours = ($standard_exit_seconds - $standard_entry_seconds) / 3600; // Assenza totale
        } else {
            if ($entry_seconds > $standard_entry_seconds) { // Entrata in ritardo
                $absence_hours += ($entry_seconds - $standard_entry_seconds) / 3600;
            }
            if ($exit_seconds < $standard_exit_seconds) { // Uscita anticipata
                $absence_hours += ($standard_exit_seconds - $exit_seconds) / 3600;
            }
        }

        $absence_hours = round($absence_hours, 2); // Arrotonda a due decimali

        $total_absences += $absence_hours;

        $dayName = ucfirst($day);
        if (!isset($weekAbsences[$dayName])) {
            $weekAbsences[$dayName] = 0;
        }
        $weekAbsences[$dayName] += $absence_hours;
    }
}

$stmt->close();

echo json_encode([
    'total_absences'  => $total_absences,
    'total_max_hours' => $total_max_hours,
    'week_absences'   => $weekAbsences
]);
exit;
