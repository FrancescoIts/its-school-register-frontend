<?php
require_once 'config.php';
require_once 'check_session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$user = checkSession(true, ['studente']);
$id_user = $user['id_user'] ?? 0;

// Recupero del corso associato all'utente
$stmtCourse = $conn->prepare("
  SELECT id_course FROM user_role_courses
  WHERE id_user = ? LIMIT 1
");
$stmtCourse->bind_param("i", $id_user);
$stmtCourse->execute();
$resultCourse = $stmtCourse->get_result()->fetch_assoc();
$stmtCourse->close();

$id_course = $resultCourse['id_course'] ?? null;

if (!$id_course) {
    die("Nessun corso associato all'utente.");
}

// Funzione per recuperare gli orari dal database
function getCourseTimes($conn, $id_course, $day_of_week) {
    $query = "
        SELECT start_time_{$day_of_week} AS start_time, end_time_{$day_of_week} AS end_time
        FROM courses
        WHERE id_course = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_course);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return [$result['start_time'], $result['end_time']];
}

// Recupero delle assenze
$currentYear = date('Y'); // Ottiene l'anno corrente
$stmtA = $conn->prepare("
  SELECT date, entry_hour, exit_hour
  FROM attendance
  WHERE id_user = ?
    AND id_course = ?
    AND YEAR(date) = ?
");

// Binding del parametro dell'anno corrente
$stmtA->bind_param("iii", $id_user, $id_course, $currentYear);

$stmtA->execute();
$resA = $stmtA->get_result();

$absences = [];
while ($row = $resA->fetch_assoc()) {
    $date = $row['date'];
    $entry = $row['entry_hour'] ?? null;
    $exit  = $row['exit_hour'] ?? null;

    // Ottieni il giorno della settimana in inglese (monday, tuesday, ecc.)
    $dayOfWeek = strtolower(date('l', strtotime($date)));

    // Ottieni gli orari di inizio e fine dal corso
    list($course_start, $course_end) = getCourseTimes($conn, $id_course, $dayOfWeek);

    // Se non ci sono orari definiti per quel giorno, salta
    if (!$course_start || !$course_end) {
        continue;
    }

    $standard_entry_seconds = strtotime($course_start);
    $standard_exit_seconds  = strtotime($course_end);

    // Se mancano i dati di ingresso o uscita, consideriamo l'intera fascia oraria come assente
    if (!$entry || !$exit) {
        $absence_hours = ($standard_exit_seconds - $standard_entry_seconds) / 3600;
        $absences[$date] = round($absence_hours, 2);
        continue;
    }

    // Converto gli orari in secondi per fare i calcoli
    $entry_seconds = strtotime($entry);
    $exit_seconds  = strtotime($exit);

    // Calcolo le ore di assenza
    $absence_hours = 0;

    if ($entry_seconds > $standard_entry_seconds) {
        // Entrato in ritardo
        $absence_hours += ($entry_seconds - $standard_entry_seconds) / 3600;
    }
    if ($exit_seconds < $standard_exit_seconds) {
        // Uscito prima
        $absence_hours += ($standard_exit_seconds - $exit_seconds) / 3600;
    }

    // Se ci sono ore di assenza, le registriamo
    if ($absence_hours > 0) {
        $absences[$date] = round($absence_hours, 2);
    }
}
$stmtA->close();
?>

<body>
<div id="calendar"></div>
<script>
// Dati delle assenze e degli eventi in formato JSON
const userAbsences = <?php echo json_encode($absences); ?>; 

function renderCalendar(month, year) {
    const firstDayJS   = new Date(year, month - 1, 1).getDay();
    const daysInMonth  = new Date(year, month, 0).getDate();

    const daysOfWeek = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];
    const monthsIta = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno',
                       'Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];

    let monthName = monthsIta[month - 1] || '??';
    let html = `<div class="calendar-container scrollable-table">
                  <div class="calendar-header"><h3>${monthName} ${year}</h3></div>
                  <table class="calendar-table"><thead><tr>`;
    
    for (let d of daysOfWeek) {
        html += `<th>${d}</th>`;
    }
    html += `</tr></thead><tbody><tr>`;
    
    let firstDayIndex = (firstDayJS === 0) ? 7 : firstDayJS;
    
    for (let i = 1; i < firstDayIndex; i++) {
        html += '<td></td>';
    }

    for (let day = 1; day <= daysInMonth; day++) {
        let dayString = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        let hasAbsence = userAbsences[dayString] !== undefined;

        let cellClasses = 'calendar-day';
        if (hasAbsence) cellClasses += ' absence-day';

        let dotHTML = hasAbsence ? '<div class="absence-dot"></div>' : '';

        html += `<td class="${cellClasses}" data-date="${dayString}" data-absence="${hasAbsence ? userAbsences[dayString] : ''}">
                    <strong>${day}</strong>
                    ${dotHTML}
                </td>`;

        let totalCellsSoFar = (firstDayIndex - 1) + day;
        if (totalCellsSoFar % 7 === 0) {
            html += `</tr><tr>`;
        }
    }

    html += '</tr></tbody></table>';
    
    html += `<div class="navigation">
               <button class="month-nav" data-month="${(month === 1) ? 12 : (month-1)}"> ${monthsIta[(month === 1) ? 11 : (month-2)]}</button>
               <button class="month-nav" data-month="${(month === 12) ? 1 : (month+1)}"> ${monthsIta[(month === 12) ? 0 : month]}</button>
             </div>`;
    html += '</div>';

    document.getElementById('calendar').innerHTML = html;
    setupListeners();
}

function setupListeners() {
    document.querySelectorAll('.calendar-day').forEach(cell => {
        cell.addEventListener('click', function() {
            let dateStr    = this.getAttribute('data-date');
            let absenceH   = this.getAttribute('data-absence');

            let dateObj = new Date(dateStr.replace(/-/g, '/'));
            let opzioniFormattazione = { day: 'numeric', month: 'long', year: 'numeric' };
            let dateStrIta = dateObj.toLocaleDateString('it-IT', opzioniFormattazione);

            let msg = absenceH 
            ? `Ore di assenza: ${absenceH}` 
            : `<img src="https://media.giphy.com/media/d8lUKXD00IXSw/giphy.gif?cid=790b7611xn5dg1mlcc0g7hk6hdo94xtx3dqtpotmlk4uez7b&ep=v1_gifs_search&rid=giphy.gif&ct=g" width="250" alt="GIF">`;

            Swal.fire({
                title: `Dettagli: ${dateStrIta}`,
                html: msg,  // Usa "html" invece di "text" per visualizzare immagini e contenuti HTML
                icon: 'info',
                confirmButtonText: 'OK',
                backdrop: 'rgba(0, 0, 0, 0.5)',
            });

        });
    });

    document.querySelectorAll('.month-nav').forEach(btn => {
        btn.addEventListener('click', function() {
            let newM = parseInt(this.getAttribute('data-month'));
            currentMonth = newM;
            renderCalendar(currentMonth, currentYear);
        });
    });
}

// Avvio
let currentYear  = new Date().getFullYear();
let currentMonth = new Date().getMonth() + 1;
renderCalendar(currentMonth, currentYear);

</script>
</body>
</html>
