<?php
require_once 'config.php';
require_once 'check_session.php';

// Controlla la sessione e che l'utente abbia i ruoli ammessi
checkSession(true, ['docente','admin','sadmin']);

header('Content-Type: application/json');

if (!isset($_GET['id_user'])) {
    echo json_encode(['error'=>'Manca il parametro id_user']);
    exit;
}

$id_user = intval($_GET['id_user']);

// 1) Calcolo assenze totali, ore massime, ecc.
$query = "
    SELECT 
        SUM(a.absence_hours) AS total_absences,
        (COUNT(DISTINCT a.date) * 8) AS total_max_hours
    FROM attendance a
    WHERE a.id_user = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id_user);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['error'=> 'Studente non trovato.']);
    exit;
}

// Estraggo le variabili
$total_absences  = floatval($row['total_absences'] ?? 0);
$total_max_hours = floatval($row['total_max_hours'] ?? 0);

// 2) Calcolo distribuzione delle assenze per giorno della settimana
$queryDays = "
    SELECT 
        DAYOFWEEK(a.date) AS day_index,
        DAYNAME(a.date)   AS day_name,
        SUM(a.absence_hours) as sum_hours
    FROM attendance a
    WHERE a.id_user = ?
    GROUP BY DAYOFWEEK(a.date), DAYNAME(a.date)
    ORDER BY day_index ASC
";
$stmt2 = $conn->prepare($queryDays);
$stmt2->bind_param('i', $id_user);
$stmt2->execute();
$res2 = $stmt2->get_result();

$weekAbsences = [];
while ($r = $res2->fetch_assoc()) {
    // day_name di solito esce in inglese (Sunday, Monday...) 
    // Se il server MySQL è configurato in italiano, potrebbe uscire in italiano. 
    // Eventualmente potresti mappare a mano i nomi nei tuoi preferiti.
    $dayname = $r['day_name'];
    $sum = floatval($r['sum_hours']);
    $weekAbsences[$dayname] = $sum;
}
$stmt2->close();

// 3) Rispondo in JSON
echo json_encode([
    'total_absences'  => $total_absences,
    'total_max_hours' => $total_max_hours,
    'week_absences'   => $weekAbsences
]);
exit;
