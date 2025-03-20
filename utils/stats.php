<?php
require_once 'config.php';
require_once 'check_session.php';


ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (!isset($_SESSION['user']['id_user'])) {
    echo json_encode(["error" => "Utente non autenticato."]);
    exit;
}

$id_user = $_SESSION['user']['id_user'];

// Recupera il corso dell'utente (ogni utente ha solo 1 corso)
$queryCourse = "
    SELECT c.id_course, c.name, c.year, 
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

// Le ore totali si riferiscono a un anno (per ogni anno accademico)
$max_hours = (int)$course['total_hour'];

// Calcola l'anno accademico corrente:
// Se il mese corrente è ottobre (10) o successivo, l'anno accademico è l'anno corrente;
// altrimenti è l'anno corrente - 1.
$currentMonth = date('n');
$academicYear = ($currentMonth >= 10) ? date('Y') : (date('Y') - 1);
$startAcademic = $academicYear . "-10-01";
$endAcademic   = ($academicYear + 1) . "-09-30";

// Query per ottenere le presenze dell'utente nell'anno accademico corrente
$queryAttendance = "
    SELECT id_course, date, entry_hour, exit_hour 
    FROM attendance 
    WHERE id_user = ? AND date BETWEEN ? AND ?
";
$stmt = $conn->prepare($queryAttendance);
if ($stmt === false) {
    die(json_encode(['error' => 'Errore nella preparazione della query: ' . $conn->error]));
}
$stmt->bind_param("iss", $id_user, $startAcademic, $endAcademic);
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
    $date = $row['date'];
    $entry = $row['entry_hour'] ?? null;
    $exit  = $row['exit_hour'] ?? null;
    
    $weekdayIndex = date('w', strtotime($date)); // 0 = Domenica, 6 = Sabato
    if ($weekdayIndex >= 1 && $weekdayIndex <= 5) {
        $day = $weekdaysMap[$weekdayIndex];
        $start_key = "start_time_" . $day;
        $end_key   = "end_time_" . $day;
        
        $standard_entry = $course[$start_key];
        $standard_exit  = $course[$end_key];
        if (!$standard_entry || !$standard_exit) {
            continue;
        }
        $entry_sec = $entry ? strtotime($entry) : null;
        $exit_sec  = $exit  ? strtotime($exit)  : null;
        $std_entry_sec = strtotime($standard_entry);
        $std_exit_sec  = strtotime($standard_exit);
        
        $absence_hours = 0;
        if (!$entry_sec || !$exit_sec) {
            $absence_hours = ($std_exit_sec - $std_entry_sec) / 3600;
        } else {
            if ($entry_sec > $std_entry_sec) {
                $absence_hours += ($entry_sec - $std_entry_sec) / 3600;
            }
            if ($exit_sec < $std_exit_sec) {
                $absence_hours += ($std_exit_sec - $exit_sec) / 3600;
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
    "week_absences" => $week_absences,
    "academic_year" => $academicYear
]);
exit;
