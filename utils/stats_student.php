<?php
require_once 'config.php';
require_once 'check_session.php';

checkSession(true, ['docente','admin','sadmin']);

header('Content-Type: application/json');

if (!isset($_GET['id_user'])) {
    echo json_encode(['error' => 'Manca il parametro id_user']);
    exit;
}

$id_user = intval($_GET['id_user']);

// Query aggiornata per il calcolo delle assenze totali
$query = "
    SELECT 
        COALESCE(SUM(
            CASE 
                WHEN (a.entry_hour IS NULL OR a.exit_hour IS NULL) THEN 4
                ELSE GREATEST(0, (TIME_TO_SEC(TIMEDIFF('18:00:00', a.exit_hour)) / 3600))
                     + GREATEST(0, (TIME_TO_SEC(TIMEDIFF(a.entry_hour, '14:00:00')) / 3600))
            END
        ), 0) AS total_absences
    FROM attendance a
    WHERE a.id_user = ? AND YEAR(a.date) = 2025
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id_user);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

$total_absences  = floatval($row['total_absences'] ?? 0);
$total_max_hours = 900;

// Query per le assenze per giorno della settimana
$queryDays = "
    SELECT 
        DAYOFWEEK(a.date) AS day_index,
        DAYNAME(a.date)   AS day_name,
        COALESCE(SUM(
            CASE 
                WHEN (a.entry_hour IS NULL OR a.exit_hour IS NULL) THEN 4
                ELSE GREATEST(0, (TIME_TO_SEC(TIMEDIFF('18:00:00', a.exit_hour)) / 3600))
                     + GREATEST(0, (TIME_TO_SEC(TIMEDIFF(a.entry_hour, '14:00:00')) / 3600))
            END
        ), 0) as sum_hours
    FROM attendance a
    WHERE a.id_user = ? AND YEAR(a.date) = 2025
    GROUP BY DAYOFWEEK(a.date), DAYNAME(a.date)
    ORDER BY day_index ASC
";
$stmt2 = $conn->prepare($queryDays);
$stmt2->bind_param('i', $id_user);
$stmt2->execute();
$res2 = $stmt2->get_result();

$weekAbsences = [];
while ($r = $res2->fetch_assoc()) {
    $dayname = $r['day_name'];
    $sum = floatval($r['sum_hours']);
    $weekAbsences[$dayname] = $sum;
}
$stmt2->close();

// Restituisci il JSON corretto
echo json_encode([
    'total_absences'  => $total_absences,
    'total_max_hours' => $total_max_hours,
    'week_absences'   => $weekAbsences
]);
exit;
?>
