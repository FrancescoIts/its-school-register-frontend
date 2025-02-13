<?php
require_once 'config.php';
require_once 'check_session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Assicura che l'utente sia loggato
if (!isset($_SESSION['user']['id_user'])) {
    die(json_encode(["error" => "Utente non autenticato."]));
}

$id_user = $_SESSION['user']['id_user'];

// Query per il totale delle assenze e ore totali disponibili
$queryTotal = "
    SELECT 
        SUM(absence_hours) AS total_absences, 
        (COUNT(DISTINCT date) * 8) AS total_max_hours 
    FROM attendance 
    WHERE id_user = ?
";
$stmt = $conn->prepare($queryTotal);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_absences = $row['total_absences'] ?? 0;
$total_max_hours = $row['total_max_hours'] ?? 1; // Evita divisione per zero
$stmt->close();

// Query per ottenere il numero di assenze per giorno della settimana
$queryWeekDays = "
    SELECT 
        DAYOFWEEK(date) AS weekday, 
        SUM(absence_hours) AS total_absences
    FROM attendance
    WHERE id_user = ?
    GROUP BY DAYOFWEEK(date)
    ORDER BY weekday
";
$stmt = $conn->prepare($queryWeekDays);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();

// Mappa i giorni della settimana (DAYOFWEEK inizia da Domenica=1)
$weekdaysMap = [
    1 => "Domenica", 
    2 => "Lunedì", 
    3 => "Martedì", 
    4 => "Mercoledì", 
    5 => "Giovedì", 
    6 => "Venerdì", 
    7 => "Sabato"
];

$week_absences = array_fill_keys(array_values($weekdaysMap), 0);

while ($row = $result->fetch_assoc()) {
    $dayName = $weekdaysMap[$row['weekday']];
    $week_absences[$dayName] = $row['total_absences'];
}
$stmt->close();

// Output JSON
echo json_encode([
    "total_absences" => $total_absences,
    "total_max_hours" => $total_max_hours,
    "week_absences" => $week_absences
]);
?>
