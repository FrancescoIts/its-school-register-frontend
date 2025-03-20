<?php
require_once 'config.php';
require_once 'check_session.php';

// Disabilita l'output HTML di errori per evitare conflitti con il JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (!isset($_SESSION['user']['id_user'])) {
    echo json_encode(["error" => "Utente non autenticato."]);
    exit;
}

$id_user = $_SESSION['user']['id_user'];

// Query per recuperare il corso dell'utente (ogni utente ha solo 1 corso)
$queryCourse = "
    SELECT c.id_course, c.name, 
           c.start_time_monday, c.end_time_monday,
           c.start_time_tuesday, c.end_time_tuesday,
           c.start_time_wednesday, c.end_time_wednesday,
           c.start_time_thursday, c.end_time_thursday,
           c.start_time_friday, c.end_time_friday,
           c.total_hour
    FROM courses c
    JOIN user_role_courses urc ON c.id_course = urc.id_course
    WHERE urc.id_user = ?
    LIMIT 1
";

$stmt = $conn->prepare($queryCourse);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();

$course = $result->fetch_assoc();
$stmt->close();

if (!$course) {
    echo json_encode(['error' => 'Nessun corso associato trovato.']);
    exit;
}

// Il totale massimo delle ore viene preso dal campo total_hour del corso
$max_hours = (int)$course['total_hour'];

// Ottengo l'anno corrente
$currentYear = date('Y');

// Query per ottenere tutte le presenze dell'utente nell'anno corrente
$queryAttendance = "
    SELECT id_course, date, entry_hour, exit_hour 
    FROM attendance 
    WHERE id_user = ? AND YEAR(date) = ?
";

$stmt = $conn->prepare($queryAttendance);
if ($stmt === false) {
    die(json_encode(['error' => 'Errore nella preparazione della query: ' . $conn->error]));
}
$stmt->bind_param("ii", $id_user, $currentYear);
$stmt->execute();
$result = $stmt->get_result();

$total_absences = 0;
$week_absences = [
    "Lunedì" => 0, "Martedì" => 0, "Mercoledì" => 0,
    "Giovedì" => 0, "Venerdì" => 0
];

$weekdaysMap = [
    1 => "monday", 2 => "tuesday", 3 => "wednesday",
    4 => "thursday", 5 => "friday"
];

while ($row = $result->fetch_assoc()) {
    // Poiché l'utente ha solo 1 corso, utilizziamo direttamente il corso recuperato
    $date = $row['date'];
    $entry = $row['entry_hour'] ?? null;
    $exit = $row['exit_hour'] ?? null;

    $weekdayIndex = date('w', strtotime($date)); // 0 = Domenica, 6 = Sabato

    if ($weekdayIndex >= 1 && $weekdayIndex <= 5) {
        $day = $weekdaysMap[$weekdayIndex];
        $start_time_key = "start_time_" . $day; 
        $end_time_key = "end_time_" . $day;

        $standard_entry = $course[$start_time_key];
        $standard_exit = $course[$end_time_key];

        if (!$standard_entry || !$standard_exit) {
            continue;
        }

        $entry_seconds = $entry ? strtotime($entry) : null;
        $exit_seconds = $exit ? strtotime($exit) : null;
        $standard_entry_seconds = strtotime($standard_entry);
        $standard_exit_seconds = strtotime($standard_exit);

        $absence_hours = 0;
        if (!$entry_seconds || !$exit_seconds) {
            $absence_hours = ($standard_exit_seconds - $standard_entry_seconds) / 3600;
        } else {
            if ($entry_seconds > $standard_entry_seconds) {
                $absence_hours += ($entry_seconds - $standard_entry_seconds) / 3600;
            }
            if ($exit_seconds < $standard_exit_seconds) {
                $absence_hours += ($standard_exit_seconds - $exit_seconds) / 3600;
            }
        }

        $absence_hours = round($absence_hours, 2);
        $total_absences += $absence_hours;

        $dayName = ucfirst($day);
        if (!isset($week_absences[$dayName])) {
            $week_absences[$dayName] = 0;
        }
        $week_absences[$dayName] += $absence_hours;
    }
}

$stmt->close();

echo json_encode([
    "total_absences" => $total_absences,
    "total_max_hours" => $max_hours,
    "week_absences" => $week_absences
]);
exit;
