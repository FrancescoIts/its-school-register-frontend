<?php
require_once 'config.php';
require_once 'check_session.php';

date_default_timezone_set('Europe/Rome');

$user = checkSession();
$id_user = $user['id_user'];

// Corso dello studente
$stmt = $conn->prepare("
    SELECT id_course 
    FROM user_role_courses 
    WHERE id_user = ? AND id_role = 1 LIMIT 1
");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
$studentCourse = $result->fetch_assoc()['id_course'] ?? null;
$stmt->close();

if (!$studentCourse) {
    die("Nessun corso assegnato.");
}

/**
 * Funzione per ottenere il codice HTML del calendario eventi
 */
function getCalendar($month, $year, $conn, $id_course) {

    // Validazione dei parametri mese e anno
    if ($month < 1 || $month > 12) {
        $month = (int)date('n');
    }
    if ($year < 1) {
        $year = (int)date('Y');
    }
    
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

$month = isset($_GET['month1']) ? (int)$_GET['month1'] : date('n');
$year  = isset($_GET['year1'])  ? (int)$_GET['year1']  : date('Y');
echo getCalendar($month, $year, $conn, $studentCourse);
exit;
