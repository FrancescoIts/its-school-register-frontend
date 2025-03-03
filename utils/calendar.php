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

// Se lo studente non ha un corso, interrompiamo
if (!$studentCourse) {
    die("Nessun corso assegnato.");
}

/**
 * Funzione per ottenere il codice HTML del calendario
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

    // Numero di giorni nel mese
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    // Costruzione tabella del calendario
    $calendar = '<table class="calendar-table"><thead><tr>';
    
    $daysOfWeek = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
    foreach ($daysOfWeek as $day) {
        $calendar .= "<th>{$day}</th>";
    }
    $calendar .= '</tr></thead><tbody><tr>';

    // Giorno della settimana del primo giorno (1 = Lun, ... 7 = Dom)
    $firstDayOfMonth = date('N', strtotime("$year-$month-01"));

    // Celle vuote prima del giorno 1
    for ($i = 1; $i < $firstDayOfMonth; $i++) {
        $calendar .= '<td></td>';
    }

    // Creazione delle celle del calendario
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
            // Mostra un pallino se c’è un evento
            $calendar .= "<div class='event-dot'></div>";
        }
        $calendar .= "</td>";

        // A capo dopo la domenica
        if (date('N', strtotime($date)) == 7) {
            $calendar .= '</tr><tr>';
        }
    }

    $calendar .= '</tr></tbody></table>';

    return $calendar;
}


// Se non ci sono parametri GET, prendiamo il mese e l'anno attuali
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');

// Calcoliamo il prossimo e il precedente mese
$nextMonth = $month + 1;
$nextYear  = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$prevMonth = $month - 1;
$prevYear  = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

// Prepariamo l'HTML del calendario per il mese/anno richiesto
$calendarHtml = getCalendar($month, $year, $conn, $studentCourse);

// Formattiamo il nome del mese e anno in italiano (es. marzo 2025)
$nomeMeseCorrente = date('F Y', strtotime("$year-$month-01"));

?>




<?php
// Array dei nomi dei mesi in italiano
$mesiItaliani = [
    1 => "Gennaio", 2 => "Febbraio", 3 => "Marzo", 4 => "Aprile",
    5 => "Maggio", 6 => "Giugno", 7 => "Luglio", 8 => "Agosto",
    9 => "Settembre", 10 => "Ottobre", 11 => "Novembre", 12 => "Dicembre"
];

// Recuperiamo i nomi dei mesi precedente e successivo
$nomeMeseCorrente = $mesiItaliani[$month] . " " . $year;
$nomeMesePrecedente = $mesiItaliani[$prevMonth];
$nomeMeseSuccessivo = $mesiItaliani[$nextMonth];
?>

<div class="calendar-container scrollable-table">
<strong><?php echo $nomeMeseCorrente; ?></strong>
    <?php echo $calendarHtml; ?>
</div>
<div class="navigation">
    <form method="GET" style="display: inline;">
        <button type="submit" name="month" value="<?php echo $prevMonth; ?>" 
                formaction="?year=<?php echo $prevYear; ?>" class="month-nav">
             <?php echo $nomeMesePrecedente; ?>
        </button>
    </form>

    <form method="GET" style="display: inline;">
        <button type="submit" name="month" value="<?php echo $nextMonth; ?>" 
                formaction="?year=<?php echo $nextYear; ?>" class="month-nav">
            <?php echo $nomeMeseSuccessivo; ?> 
        </button>
    </form>
</div>


