<?php
require_once 'config.php';
require_once 'check_session.php';

$user      = checkSession();
$id_user   = $user['id_user'];
$roles     = $user['roles'] ?? [];
$isAdmin   = in_array('admin', $roles) || in_array('sadmin', $roles);
$isDocente = in_array('docente', $roles);

if (!$isAdmin && !$isDocente) {
    die("Non autorizzato.");
}

// Recupera i corsi associati all'utente
$queryCourses = "
    SELECT DISTINCT c.id_course, c.name
    FROM courses c
    INNER JOIN user_role_courses urc ON c.id_course = urc.id_course
    WHERE urc.id_user = ?
";
$stmt = $conn->prepare($queryCourses);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($courses)) {
    die("Nessun corso assegnato.");
}

// Seleziona il corso attuale
$id_course = isset($_GET['id_course']) ? intval($_GET['id_course']) : $courses[0]['id_course'];

// Recupera mese e anno dalla query string (usando "cal_month" e "cal_year")
$month = isset($_GET['cal_month']) ? intval($_GET['cal_month']) : date('n');
$year  = isset($_GET['cal_year'])  ? intval($_GET['cal_year'])  : date('Y');

// Calcola il mese precedente e successivo
$prevMonth = $month - 1;
$prevYear  = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}
$nextMonth = $month + 1;
$nextYear  = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$mesiItaliani = [
    1 => "Gennaio", 2 => "Febbraio", 3 => "Marzo", 4 => "Aprile",
    5 => "Maggio", 6 => "Giugno", 7 => "Luglio", 8 => "Agosto",
    9 => "Settembre", 10 => "Ottobre", 11 => "Novembre", 12 => "Dicembre"
];

$nomeMeseCorrente   = strtoupper($mesiItaliani[intval($month)] . " " . $year);
$nomeMesePrecedente = $mesiItaliani[intval($prevMonth)];
$nomeMeseSuccessivo = $mesiItaliani[intval($nextMonth)];

/**
 * Funzione per generare l'HTML del calendario con lo stesso stile
 */
function getCalendar($month, $year, $conn, $id_course) {
    // Recupera eventi dal DB
    $stmt = $conn->prepare("
        SELECT c.id, c.date, c.event, c.created_by, u.firstname, u.lastname
        FROM calendar c
        LEFT JOIN users u ON c.created_by = u.id_user
        WHERE MONTH(c.date) = ?
          AND YEAR(c.date) = ?
          AND c.id_course = ?
    ");
    $stmt->bind_param("iii", $month, $year, $id_course);
    $stmt->execute();
    $result = $stmt->get_result();

    $eventData = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['event'])) {
            $creatorName = (!empty($row['firstname']) && !empty($row['lastname']))
                ? trim($row['firstname'] . " " . $row['lastname'])
                : "Sconosciuto";
            $eventData[$row['date']][] = [
                "id"           => $row['id'],
                "event"        => $row['event'],
                "creator_name" => $creatorName,
                "created_by"   => $row['created_by']
            ];
        }
    }
    $stmt->close();

    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $firstDayTimestamp = strtotime("$year-$month-01");
    $firstWeekday = date('N', $firstDayTimestamp) - 1;
    $dayNames = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];
    $html = "";

    // Header dei giorni
    $html .= '<div class="c-cal__row">';
    foreach ($dayNames as $dName) {
        $html .= "<div class='c-cal__col'>{$dName}</div>";
    }
    $html .= '</div>';

    // Prima riga: celle vuote
    $html .= '<div class="c-cal__row">';
    for ($i = 0; $i < $firstWeekday; $i++){
        $html .= '<div class="c-cal__cel"></div>';
    }
    
    $currentDay = 1;
    for ($i = $firstWeekday; $i < 7; $i++){
        if($currentDay <= $daysInMonth) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
            $hasEvent = isset($eventData[$dateStr]);
            $extraClass = $hasEvent ? ' event' : '';
            $eventAttributes = '';
            if ($hasEvent) {
                $eventList = array_map(function($e) {
                    return htmlspecialchars($e['event'] . " (creato da: " . $e['creator_name'] . ")", ENT_QUOTES, 'UTF-8');
                }, $eventData[$dateStr]);
                $allEvents   = implode(" | ", $eventList);
                $eventAttributes = " data-event='{$allEvents}'";
            }
            $html .= "<div class='c-cal__cel{$extraClass}' data-day='{$dateStr}'{$eventAttributes}><p>{$currentDay}</p></div>";
            $currentDay++;
        } else {
            $html .= '<div class="c-cal__cel"></div>';
        }
    }
    $html .= '</div>';

    // Settimane successive
    while($currentDay <= $daysInMonth) {
        $html .= '<div class="c-cal__row">';
        for($i = 0; $i < 7; $i++){
            if($currentDay <= $daysInMonth){
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
                $hasEvent = isset($eventData[$dateStr]);
                $extraClass = $hasEvent ? ' event' : '';
                $eventAttributes = '';
                if ($hasEvent) {
                    $eventList = array_map(function($e) {
                        return htmlspecialchars($e['event'] . " (creato da: " . $e['creator_name'] . ")", ENT_QUOTES, 'UTF-8');
                    }, $eventData[$dateStr]);
                    $allEvents   = implode(" | ", $eventList);
                    $eventAttributes = " data-event='{$allEvents}'";
                }
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

$calendarHtml = getCalendar($month, $year, $conn, $id_course);

// Prepara i dati per il calendario in formato JSON (solo eventi non vuoti)
$stmt = $conn->prepare("
    SELECT c.id, c.date, c.event, c.created_by, 
           CONCAT(IFNULL(u.firstname, ''), ' ', IFNULL(u.lastname, '')) AS creator_name
    FROM calendar c
    LEFT JOIN users u ON c.created_by = u.id_user
    WHERE MONTH(c.date) = ? 
      AND YEAR(c.date) = ? 
      AND c.id_course = ?
");
$stmt->bind_param("iii", $month, $year, $id_course);
$stmt->execute();
$result = $stmt->get_result();
$calendarData = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['event'])) {
        $calendarData[] = $row;
    }
}
$stmt->close();
?>
<!-- Selettore del corso -->
<div class="calendar-header" style="margin-bottom: 10px;" data-month="<?php echo $month; ?>" data-year="<?php echo $year; ?>">  
    <label for="course-select">Seleziona Corso:</label>
    <select id="course-select" 
            style="padding: 5px; border-radius: 4px; border: 1px solid #ccc; background-color: #2090C9; color: #FFF;"
            onchange="changeCourse(this.value)">
        <?php foreach ($courses as $course) : ?>
            <option value="<?= $course['id_course'] ?>" <?= ($course['id_course'] == $id_course) ? "selected" : "" ?>>
                <?= htmlspecialchars($course['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<!-- Header del calendario con navigazione -->
<div class="calendar-header">
    <form method="GET" style="display: inline;">
        <input type="hidden" name="id_course" value="<?php echo $id_course; ?>">
        <input type="hidden" name="cal_year" value="<?php echo $prevYear; ?>">
        <button type="submit" name="cal_month" value="<?php echo $prevMonth; ?>" class="o-btn">
            <strong>&#8810;</strong>
        </button>
    </form>
    <span id="currentMonth" class="current-month" style="font-size:1.2em; font-weight:bold; margin: 0 10px;">
        <?php echo $nomeMeseCorrente; ?>
    </span>
    <form method="GET" style="display: inline;">
        <input type="hidden" name="id_course" value="<?php echo $id_course; ?>">
        <input type="hidden" name="cal_year" value="<?php echo $nextYear; ?>">
        <button type="submit" name="cal_month" value="<?php echo $nextMonth; ?>" class="o-btn">
            <strong>&#8811;</strong>
        </button>
    </form>
</div>
<!-- Contenitore del calendario -->
<div class="wrapper">
    <div class="c-calendar">
        <div id="calendarContent" class="c-cal__container c-calendar__style">
            <?php echo $calendarHtml; ?>
        </div>
    </div>
</div>
<script>
    function changeCourse(courseId) {
        let url = new URL(window.location.href);
        url.searchParams.set('id_course', courseId);
        window.location.href = url.toString();
    }
    // Variabili per il JS
    let phpYear    = <?= json_encode($year) ?>;
    let phpMonth   = <?= json_encode($month) ?>;
    let idCourse   = <?= json_encode($id_course) ?>;
    let userId     = <?= json_encode($id_user) ?>;
    let isAdmin    = <?= json_encode($isAdmin) ?>;
    let isDocente  = <?= json_encode($isDocente) ?>;
    let calendarData = <?= json_encode($calendarData) ?>;
</script>
