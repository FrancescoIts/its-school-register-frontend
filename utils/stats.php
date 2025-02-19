<?php
require_once 'config.php';
require_once 'check_session.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user']['id_user'])) {
    die(json_encode(["error" => "Utente non autenticato."]));
}

$id_user = $_SESSION['user']['id_user'];

// Orario standard di presenza (14:00 - 18:00)
$standard_entry = "14:00:00";
$standard_exit  = "18:00:00";
$max_hours = 900; // Ore massime previste nel periodo

// Query per ottenere tutte le presenze dell'utente nel 2025
$query = "
    SELECT date, entry_hour, exit_hour 
    FROM attendance 
    WHERE id_user = ? AND YEAR(date) = 2025
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();

$total_absences = 0;
$week_absences = [
    "Lunedì" => 0, "Martedì" => 0, "Mercoledì" => 0,
    "Giovedì" => 0, "Venerdì" => 0
];

$weekdaysMap = [
    1 => "Lunedì", 2 => "Martedì", 3 => "Mercoledì", 
    4 => "Giovedì", 5 => "Venerdì", 6 => "Sabato", 0 => "Domenica"
];

while ($row = $result->fetch_assoc()) {
    $date = $row['date'];
    $entry = $row['entry_hour'] ?? null;
    $exit  = $row['exit_hour'] ?? null;

    // Converto gli orari in secondi per fare i calcoli
    $entry_seconds = $entry ? strtotime($entry) : null;
    $exit_seconds  = $exit ? strtotime($exit) : null;
    $standard_entry_seconds = strtotime($standard_entry);
    $standard_exit_seconds  = strtotime($standard_exit);

    // Calcolo delle ore di assenza
    $absence_hours = 0;

    if (!$entry_seconds || !$exit_seconds) {
        // Se manca uno dei due, consideriamo 4 ore di assenza
        $absence_hours = 4;
    } else {
        if ($entry_seconds > $standard_entry_seconds) { 
            // Entrato in ritardo
            $absence_hours += ($entry_seconds - $standard_entry_seconds) / 3600;
        }
        if ($exit_seconds < $standard_exit_seconds) { 
            // Uscito prima del previsto
            $absence_hours += ($standard_exit_seconds - $exit_seconds) / 3600;
        }
    }

    // Arrotondiamo il valore
    $absence_hours = round($absence_hours, 2);

    // Aggiungiamo le ore di assenza totali
    $total_absences += $absence_hours;

    // Otteniamo il giorno della settimana e aggiorniamo il conteggio (escludendo Sabato e Domenica)
    $weekdayIndex = date('w', strtotime($date)); // 0 = Domenica, 6 = Sabato
    if ($weekdayIndex >= 1 && $weekdayIndex <= 5) { // Solo Lunedì-Venerdì
        $weekdayName = $weekdaysMap[$weekdayIndex];
        $week_absences[$weekdayName] += $absence_hours;
    }
}

$stmt->close();

// Assicuriamoci che il massimo sia sempre 900 ore
$total_max_hours = $max_hours;

echo json_encode([
    "total_absences" => $total_absences,
    "total_max_hours" => $total_max_hours,
    "week_absences" => $week_absences
]);
?>
