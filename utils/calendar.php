<?php
require_once 'config.php';
require_once 'check_session.php';

// Impostiamo il fuso orario (Europa/Rome)
date_default_timezone_set('Europe/Rome');

// Controllo sessione (assicurandoci che session_start() sia già partito in check_session.php)
$user = checkSession();
$id_user = $user['id_user'];
$role = $user['roles'][0] ?? 'studente';

// Preleva il corso dello studente
$stmt = $conn->prepare("
    SELECT id_course 
    FROM user_role_courses 
    WHERE id_user = ? 
      AND id_role = 1 
    LIMIT 1
");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
$studentCourse = $result->fetch_assoc()['id_course'] ?? null;
$stmt->close();

// Se lo studente non ha un corso, interrompiamo: 
if (!$studentCourse) {
    die("Nessun corso assegnato.");
}

/**
 * Funzione per ottenere gli eventi con il nome del creatore e generare il calendario HTML
 */
function getCalendar($month, $year, $conn, $id_course)
{
    $stmt = $conn->prepare("
        SELECT 
            c.date, 
            c.event, 
            c.created_by, 
            IFNULL(u.firstname, '') AS firstname, 
            IFNULL(u.lastname, '') AS lastname
        FROM calendar c
        LEFT JOIN users u ON c.created_by = u.id_user
        WHERE MONTH(c.date) = ? 
          AND YEAR(c.date) = ? 
          AND c.id_course = ?
          AND c.event IS NOT NULL
    ");
    $stmt->bind_param("iii", $month, $year, $id_course);
    $stmt->execute();
    $result = $stmt->get_result();

    $eventData = [];
    while ($row = $result->fetch_assoc()) {
        $creator_name = trim($row['firstname'] . ' ' . $row['lastname']);
        if (empty($creator_name)) {
            $creator_name = "Sconosciuto";
        }

        if (!empty($row['event'])) {
            $eventData[$row['date']] = [
                'event'        => $row['event'],  
                'creator_name' => $creator_name
            ];
        }
    }
    $stmt->close();

    // Quanti giorni ha il mese
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    // Inizio tabella del calendario
    $calendar = '<div class="dashboard"><h3>Calendario Lezioni</h3>';
    $calendar .= '<table class="calendar-table"><thead><tr>';
    
    $daysOfWeek = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
    foreach ($daysOfWeek as $day) {
        $calendar .= "<th>{$day}</th>";
    }
    $calendar .= '</tr></thead><tbody><tr>';

    // Giorno della settimana del primo giorno del mese (1 = Lun, ... 7 = Dom)
    $firstDayOfMonth = date('N', strtotime("$year-$month-01"));

    // Celle vuote prima del giorno 1
    for ($i = 1; $i < $firstDayOfMonth; $i++) {
        $calendar .= '<td></td>';
    }

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

        $hasEvent    = isset($eventData[$date]);
        $eventText   = $hasEvent ? htmlspecialchars($eventData[$date]['event'], ENT_QUOTES, 'UTF-8') : "";
        $creatorName = $hasEvent ? htmlspecialchars($eventData[$date]['creator_name'], ENT_QUOTES, 'UTF-8') : "Nessun creatore";

        $calendar .= "<td class='calendar-event'
                           data-date='{$date}' 
                           data-event='{$eventText}' 
                           data-creator='{$creatorName}'>";

        $calendar .= "<strong>{$day}</strong>";
        if ($hasEvent) {
            // Aggiunge un pallino per indicare la presenza di un evento
            $calendar .= "<div class='event-dot'></div>";
        }
        $calendar .= "</td>";

        // Se è domenica, si va a capo
        if (date('N', strtotime($date)) == 7) {
            $calendar .= '</tr><tr>';
        }
    }

    $calendar .= '</tr></tbody></table></div>';

    return $calendar;
}

// Prepara l'HTML del calendario
$calendarHtml = getCalendar(date('m'), date('Y'), $conn, $studentCourse);

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
</head>
<body>
    <div class="calendar-container">
        <?php
            // Qui stampiamo il calendario
            echo $calendarHtml;
        ?>
    </div>
</body>
</html>
