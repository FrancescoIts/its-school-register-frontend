<?php
require_once 'config.php';
require_once 'check_session.php';

$user      = checkSession();
$id_user   = $user['id_user'];
$roles     = $user['roles'] ?? [];
$isAdmin   = in_array('admin', $roles) || in_array('sadmin', $roles);
$isDocente = in_array('docente', $roles);

// Se l’utente non è admin né docente
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

// Recupera il mese e l'anno dalla query string, altrimenti usa il mese attuale
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year  = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

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

// Array dei nomi dei mesi in italiano
$mesiItaliani = [
    1 => "Gennaio", 2 => "Febbraio", 3 => "Marzo", 4 => "Aprile",
    5 => "Maggio", 6 => "Giugno", 7 => "Luglio", 8 => "Agosto",
    9 => "Settembre", 10 => "Ottobre", 11 => "Novembre", 12 => "Dicembre"
];

$nomeMeseCorrente  = $mesiItaliani[intval($month)] . " " . $year;
$nomeMesePrecedente = $mesiItaliani[intval($prevMonth)];
$nomeMeseSuccessivo = $mesiItaliani[intval($nextMonth)];

// Funzione per recuperare gli eventi
function getCalendar($month, $year, $conn, $id_course) {
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

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            "id"           => $row['id'],
            "date"         => $row['date'],
            "event"        => $row['event'],
            "created_by"   => $row['created_by'],
            "creator_name" => !empty($row['firstname']) && !empty($row['lastname']) 
                              ? $row['firstname'] . " " . $row['lastname'] 
                              : "Sconosciuto"
        ];
    }
    $stmt->close();

    return json_encode($events);
}

$calendarData = getCalendar($month, $year, $conn, $id_course);
?>

<!-- Selettore del corso -->
<div class="calendar-header" style="margin-bottom: 10px;">  
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
<strong><?php echo $nomeMeseCorrente; ?></strong>
<!-- Tabella del calendario -->
<div class="scrollable-table">
    <table class="calendar-table" style="border-collapse: collapse; width: 100%;">
        <thead>
            <tr>
                <th>Lun</th><th>Mar</th><th>Mer</th><th>Gio</th><th>Ven</th><th>Sab</th><th>Dom</th>
            </tr>
        </thead>
        <tbody>
            <!-- Generato da JavaScript -->
        </tbody>
    </table>
</div>

<!-- Navigazione tra i mesi -->
<div class="navigation">
    <form method="GET" style="display: inline;">
        <input type="hidden" name="id_course" value="<?php echo $id_course; ?>">
        <button type="submit" name="month" value="<?php echo $prevMonth; ?>" 
                formaction="?year=<?php echo $prevYear; ?>#calendarAdmin" class="month-nav">
            <?php echo $nomeMesePrecedente; ?>
        </button>
    </form>
<form method="GET" style="display: inline;">
        <input type="hidden" name="id_course" value="<?php echo $id_course; ?>">
        <button type="submit" name="month" value="<?php echo $nextMonth; ?>" 
                formaction="?year=<?php echo $nextYear; ?>#calendarAdmin" class="month-nav">
            <?php echo $nomeMeseSuccessivo; ?>
        </button>
    </form>
</div>
<script>
    function changeCourse(courseId) {
        let url = new URL(window.location.href);
        url.searchParams.set('id_course', courseId);
        window.location.href = url.toString();
    }

    let calendarData = <?= $calendarData ?>;
    let userId       = <?= json_encode($id_user) ?>;
    let isAdmin      = <?= json_encode($isAdmin) ?>;
    let isDocente    = <?= json_encode($isDocente) ?>;
    let idCourse     = <?= json_encode($id_course) ?>;

</script>

