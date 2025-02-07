<?php
require_once 'config.php'; 

function getCalendar($month, $year, $conn) {
    // Ottieni il numero di giorni nel mese
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    // Recupera gli eventi dal database
    $stmt = $conn->prepare("SELECT date, event FROM calendar WHERE MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->bind_param("ii", $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Creiamo un array associativo per cercare gli eventi più velocemente
    $eventData = [];
    while ($event = $result->fetch_assoc()) {
        $eventData[$event['date']] = $event['event'];
    }

    $stmt->close();

    // Intestazione del calendario
    $calendar = '<div class="dashboard">';
    $calendar .= '<h3 class="animated-box">Calendario di ' . date('F Y', strtotime("$year-$month-01")) . '</h3>';
    $calendar .= '<div class="courses"><div class="course-card animated-box">';
    $calendar .= '<table class="calendar-table"><thead><tr>';
    
    // Giorni della settimana
    $daysOfWeek = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
    foreach ($daysOfWeek as $day) {
        $calendar .= "<th>$day</th>";
    }
    $calendar .= '</tr></thead><tbody><tr>';

    // Primo giorno del mese e il suo indice nella settimana
    $firstDayOfMonth = date('N', strtotime("$year-$month-01"));
    
    // Spazi vuoti prima del primo giorno del mese
    for ($i = 1; $i < $firstDayOfMonth; $i++) {
        $calendar .= '<td></td>';
    }

    // Riempimento del calendario con i giorni del mese
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
        $eventText = isset($eventData[$date]) ? "<br><span class='event'>" . htmlspecialchars($eventData[$date]) . "</span>" : "";

        $calendar .= "<td><strong>$day</strong>$eventText</td>";

        // Vai a capo alla fine della settimana
        if (date('N', strtotime($date)) == 7) {
            $calendar .= '</tr><tr>';
        }
    }

    $calendar .= '</tr></tbody></table>';
    $calendar .= '</div></div></div>';
    
    return $calendar;
}

// Mostra il calendario del mese corrente
echo getCalendar(date('m'), date('Y'), $conn);
?>
