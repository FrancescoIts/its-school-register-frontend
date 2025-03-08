<?php
require_once 'config.php';
require_once 'check_session.php';

// Impostiamo il fuso orario (Europa/Rome)
date_default_timezone_set('Europe/Rome');

// Controllo sessione
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
 * Funzione per ottenere il codice HTML del calendario eventi
 */
function getCalendar($month, $year, $conn, $id_course) {
    // Recupera gli eventi dal database per il mese
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

    // Calcola il numero di giorni nel mese e il giorno della settimana del 1°
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $firstDayTimestamp = strtotime("$year-$month-01");
    // Utilizziamo date('N') per ottenere 1 = Lun, 7 = Dom; poi sottraiamo 1 per avere 0-index
    $firstWeekday = date('N', $firstDayTimestamp) - 1;

    $html = "";
    $dayNames = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];
    $html .= '<div class="c-cal__row">';
    foreach ($dayNames as $dName) {
        $html .= "<div class='c-cal__col'>{$dName}</div>";
    }
    $html .= '</div>';

    // Inizia la prima riga dei giorni
    $html .= '<div class="c-cal__row">';
    // Celle vuote se il mese non inizia di lunedì
    for ($i = 0; $i < $firstWeekday; $i++){
        $html .= '<div class="c-cal__cel"></div>';
    }
    
    $currentDay = 1;
    // Prima settimana
    for ($i = $firstWeekday; $i < 7; $i++){
        if($currentDay <= $daysInMonth) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
            $hasEvent = isset($eventData[$dateStr]);
            $eventAttributes = "";
            if ($hasEvent) {
                $eventText = htmlspecialchars($eventData[$dateStr]['event'], ENT_QUOTES, 'UTF-8');
                $creatorName = htmlspecialchars($eventData[$dateStr]['creator_name'], ENT_QUOTES, 'UTF-8');
                $eventAttributes = " data-event='{$eventText}' data-creator='{$creatorName}'";
            }
            $extraClass = $hasEvent ? ' event' : '';
            $html .= "<div class='c-cal__cel{$extraClass}' data-day='{$dateStr}'{$eventAttributes}><p>{$currentDay}</p></div>";
            $currentDay++;
        } else {
            $html .= '<div class="c-cal__cel"></div>';
        }
    }
    $html .= '</div>';

    // Le righe successive
    while($currentDay <= $daysInMonth) {
        $html .= '<div class="c-cal__row">';
        for($i = 0; $i < 7; $i++){
            if($currentDay <= $daysInMonth){
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
                $hasEvent = isset($eventData[$dateStr]);
                $eventAttributes = "";
                if ($hasEvent) {
                    $eventText = htmlspecialchars($eventData[$dateStr]['event'], ENT_QUOTES, 'UTF-8');
                    $creatorName = htmlspecialchars($eventData[$dateStr]['creator_name'], ENT_QUOTES, 'UTF-8');
                    $eventAttributes = " data-event='{$eventText}' data-creator='{$creatorName}'";
                }
                $extraClass = $hasEvent ? ' event' : '';
                $html .= "<div class='c-cal__cel{$extraClass}' data-day='{$dateStr}'{$eventAttributes}><p>{$currentDay}</p></div>";
                $currentDay++;
            } else {
                $html .= '<div class="c-cal__cel"></div>';
            }
        }
        $html .= '</div>';
    }
    return $html;
}

if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    
    $month = isset($_GET['month1']) ? (int)$_GET['month1'] : date('n');
    $year  = isset($_GET['year1'])  ? (int)$_GET['year1']  : date('Y');
    echo getCalendar($month, $year, $conn, $studentCourse);
    exit;
}


// Se non ci sono parametri GET, usiamo il mese e l'anno attuali
$month = isset($_GET['month1']) ? (int)$_GET['month1'] : date('n');
$year  = isset($_GET['year1'])  ? (int)$_GET['year1']  : date('Y');

// Calcola mese successivo e precedente
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

// Array dei nomi dei mesi in italiano
$mesiItaliani = [
    1 => "Gennaio", 2 => "Febbraio", 3 => "Marzo", 4 => "Aprile",
    5 => "Maggio", 6 => "Giugno", 7 => "Luglio", 8 => "Agosto",
    9 => "Settembre", 10 => "Ottobre", 11 => "Novembre", 12 => "Dicembre"
];
$nomeMeseCorrente = strtoupper($mesiItaliani[$month] . " " . $year);
?>
<!-- Header del calendario con navigazione asincrona -->
<div id="calendarHeader" class="calendar-header" style="text-align:center; margin-bottom:10px;" data-month="<?php echo $month; ?>" data-year="<?php echo $year; ?>">
  <button id="prevBtn" class="prev-month o-btn">
    <strong>&#8810;</strong>
  </button>
  <span id="currentMonth" class="current-month" style="font-size:1.2em; font-weight:bold; margin: 0 10px;"><?php echo $nomeMeseCorrente; ?></span>
  <button id="nextBtn" class="next-month o-btn">
    <strong>&#8811;</strong>
  </button>
</div>

  <div class="wrapper">
    <div class="c-calendar">
      <!-- Contenitore in cui inserire il calendario -->
      <div id="calendarContent" class="c-cal__container c-calendar__style">
        <?php echo getCalendar($month, $year, $conn, $studentCourse); ?>
      </div>
    </div>
  </div>
  <script>
  const currentMonth = <?php echo $month; ?>;
  const currentYear  = <?php echo $year; ?>;
  </script>


