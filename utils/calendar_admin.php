<?php
require_once 'config.php';
require_once 'check_session.php';

$user     = checkSession();
$id_user  = $user['id_user'];
$roles    = $user['roles'] ?? [];
$isAdmin  = in_array('admin', $roles) || in_array('sadmin', $roles);
$isDocente = in_array('docente', $roles);

// Se l’utente non è admin né docente
if (!$isAdmin && !$isDocente) {
    die("Non autorizzato.");
}

// Se l'utente è docente o admin, recupera i corsi associati
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

// Seleziona corso
$id_course = isset($_GET['id_course']) ? intval($_GET['id_course']) : $courses[0]['id_course'];

// Funzione per recuperare gli eventi del mese/anno corrente
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

    // Ritorniamo in formato JSON per l'uso in JS
    return json_encode($events);
}

$currentMonth  = date('m');
$currentYear   = date('Y');
$calendarData  = getCalendar($currentMonth, $currentYear, $conn, $id_course);
?>

<!-- HTML -->
<div class="calendar-header scrollable-table" style="margin-bottom: 10px;">  
    <label for="course-select">Seleziona Corso:</label>
    <select id="course-select" 
            style="padding: 5px; border-radius: 4px; border: 1px solid #ccc; margin-left: 10px; background-color: #2090C9; color: #FFF;">
        <?php foreach ($courses as $course) : ?>
            <option value="<?= $course['id_course'] ?>"
                <?= ($course['id_course'] == $id_course) ? "selected" : "" ?>>
                <?= htmlspecialchars($course['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="scrollable-table">
<table class="calendar-table" style="border-collapse: collapse; width: 100%;">
    <thead>
        <tr>
            <th style="border: 1px solid #ccc; padding: 8px;">Lun</th>
            <th style="border: 1px solid #ccc; padding: 8px;">Mar</th>
            <th style="border: 1px solid #ccc; padding: 8px;">Mer</th>
            <th style="border: 1px solid #ccc; padding: 8px;">Gio</th>
            <th style="border: 1px solid #ccc; padding: 8px;">Ven</th>
            <th style="border: 1px solid #ccc; padding: 8px;">Sab</th>
            <th style="border: 1px solid #ccc; padding: 8px;">Dom</th>
        </tr>
    </thead>
    <tbody>
        <!-- Viene generata via JavaScript -->
    </tbody>
</table>
</div>
<script>
        let calendarData = <?= $calendarData ?>;
    let userId       = <?= json_encode($id_user) ?>;
    let isAdmin      = <?= json_encode($isAdmin) ?>;
    let isDocente    = <?= json_encode($isDocente) ?>;
    let idCourse     = <?= json_encode($id_course) ?>;

</script>
