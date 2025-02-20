<?php
require_once 'config.php';
require_once 'check_session.php';

// Verifica che l'utente loggato abbia ruolo "admin"
$user = checkSession(true, ['admin']);

// ID dell'utente che è (o include) admin
$id_admin = $user['id_user'] ?? 0;

// Imposta gli id_role corrispondenti
$idRoleAdmin    = 3;
$idRoleStudente = 1;

// Orario standard di presenza
$standard_entry = "14:00:00";
$standard_exit  = "18:00:00";

// 1) Recupera TUTTI i corsi di competenza di questo admin (id e nome)
$sqlCourses = "
    SELECT c.id_course, c.name
    FROM courses c
    JOIN user_role_courses urc ON urc.id_course = c.id_course
    WHERE urc.id_user = ?
      AND urc.id_role = ?
";
$stmtC = $conn->prepare($sqlCourses);
$stmtC->bind_param("ii", $id_admin, $idRoleAdmin);
$stmtC->execute();
$resC = $stmtC->get_result();

$courseList = []; // Array che conterrà [id_course => course_name]
while ($r = $resC->fetch_assoc()) {
    $courseList[$r['id_course']] = $r['name'];
}
$stmtC->close();

// Se l'admin non ha corsi associati, usciamo
if (empty($courseList)) {
    echo "<h3>Nessun corso associato a questo admin</h3>";
    exit;
}

// 2) Determiniamo quale corso visualizzare: 
//    se è stato passato via GET e appartiene davvero all'admin, usiamo quello;
//    altrimenti prendiamo il primo.
if (isset($_GET['course_id']) && array_key_exists($_GET['course_id'], $courseList)) {
    $selectedCourse = (int)$_GET['course_id'];
} else {
    // Prendi il primo id_course (chiave dell’array $courseList)
    $selectedCourse = array_key_first($courseList);
}

// 3) Recupero tutti gli studenti iscritti al corso selezionato
$sqlStudents = "
    SELECT DISTINCT u.id_user, u.firstname, u.lastname
    FROM user_role_courses urc
    JOIN users u ON u.id_user = urc.id_user
    WHERE urc.id_course = ?
      AND urc.id_role = ?
";
$stmtS = $conn->prepare($sqlStudents);
$stmtS->bind_param("ii", $selectedCourse, $idRoleStudente);
$stmtS->execute();
$resS = $stmtS->get_result();

$studentIds     = [];
$studentDetails = [];  // per mappare id_user -> "Nome Cognome"
while ($rowS = $resS->fetch_assoc()) {
    $studentIds[] = $rowS['id_user'];
    $nomeCompleto = $rowS['firstname'] . " " . $rowS['lastname'];
    $studentDetails[$rowS['id_user']] = $nomeCompleto;
}
$stmtS->close();

// Se non ci sono studenti, mostriamo messaggio e usciamo
if (empty($studentIds)) {
    echo "<h3>Non ci sono studenti iscritti a questo corso</h3>";
    exit;
}

// 4) Recupero le assenze per questo elenco di studenti, limitate all'anno corrente
$year = date('Y'); 
$inClauseStd = implode(',', array_fill(0, count($studentIds), '?'));
$sqlA = "
    SELECT id_user, date, entry_hour, exit_hour
    FROM attendance
    WHERE id_user IN ($inClauseStd)
      AND YEAR(date) = ?
";
$stmtA = $conn->prepare($sqlA);

// Costruisco parametri per la query su attendance
$typesA = str_repeat('i', count($studentIds)) . 'i'; // "i" per ogni id_user + "i" per l'anno
$paramsA = array_merge($studentIds, [$year]);
$stmtA->bind_param($typesA, ...$paramsA);

$stmtA->execute();
$resA = $stmtA->get_result();

// Array delle assenze per data
$absencesAvanzate = [];
while ($row = $resA->fetch_assoc()) {
    $idUser = $row['id_user'];
    $date   = $row['date'];
    $entry  = $row['entry_hour'] ?? null;
    $exit   = $row['exit_hour']  ?? null;

    // Converto gli orari in secondi
    $standard_entry_seconds = strtotime($standard_entry);
    $standard_exit_seconds  = strtotime($standard_exit);

    // Se non c'è entry o exit => 4 ore di assenza (14-18)
    if (!$entry || !$exit) {
        $absence_hours = 4;
    } else {
        $entry_seconds = strtotime($entry);
        $exit_seconds  = strtotime($exit);

        $absence_hours = 0;
        // Entrato in ritardo
        if ($entry_seconds > $standard_entry_seconds) {
            $absence_hours += ($entry_seconds - $standard_entry_seconds) / 3600;
        }
        // Uscito prima
        if ($exit_seconds < $standard_exit_seconds) {
            $absence_hours += ($standard_exit_seconds - $exit_seconds) / 3600;
        }
        $absence_hours = round($absence_hours, 2);
    }

    // Salvo nel mio array solo se c’è assenza > 0
    if ($absence_hours > 0) {
        $absencesAvanzate[$date][] = [
            'id_user'       => $idUser,
            'student_name'  => $studentDetails[$idUser] ?? ("Studente #$idUser"),
            'absence_hours' => $absence_hours
        ];
    }
}
$stmtA->close();
?>
<body>

<!-- 5) Form per la scelta del corso (compare se c’è più di un corso) -->
<?php if (count($courseList) > 1): ?>
<div id="course-select-form">
    <form method="GET" action="">
        <label for="course_id">Seleziona il corso:</label>
        <select name="course_id" id="course_id" onchange="this.form.submit()">
            <?php
            foreach ($courseList as $id_corso => $nome_corso) {
                $selectedAttr = ($id_corso == $selectedCourse) ? 'selected' : '';
                echo "<option value='$id_corso' $selectedAttr>$nome_corso</option>";
            }
            ?>
        </select>
        <!-- Se vuoi un pulsante, toglie l’onchange e decommenta: 
        <button type="submit">Mostra</button> 
        -->
    </form>
</div>
<?php else: ?>
    <!-- Se c’è un solo corso, mostriamone il nome -->
    <h3 style="text-align:center;">
        Corso: <?php echo htmlspecialchars($courseList[$selectedCourse]); ?>
    </h3>
<?php endif; ?>

<div id="calendar"></div>

<script>
const userAbsences = <?php echo json_encode($absencesAvanzate); ?>;

// Funzione per renderizzare il calendario
function renderCalendar(month, year) {
    const firstDayJS   = new Date(year, month - 1, 1).getDay();
    const daysInMonth  = new Date(year, month, 0).getDate();

    const daysOfWeek = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];
    const monthsIta = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno',
                       'Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];

    let monthName = monthsIta[month - 1] || '??';
    let html = `<div class="calendar-container">
                  <div class="calendar-header"><h3>${monthName} ${year}</h3></div>
                  <table class="calendar-table"><thead><tr>`;

    for (let d of daysOfWeek) {
        html += `<th>${d}</th>`;
    }
    html += `</tr></thead><tbody><tr>`;

    // Calcolo l'indice del primo giorno (in cui 0 = Domenica in JS)
    let firstDayIndex = (firstDayJS === 0) ? 7 : firstDayJS;

    // Celle vuote prima del primo giorno
    for (let i = 1; i < firstDayIndex; i++) {
        html += '<td></td>';
    }

    // Celle dei giorni del mese
    for (let day = 1; day <= daysInMonth; day++) {
        let dayString = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        let hasAbsences = (userAbsences[dayString] !== undefined);

        let cellClasses = 'calendar-day';
        if (hasAbsences) cellClasses += ' absence-day';

        // Metto un dot se c'è almeno un'assenza
        let dotHTML = hasAbsences ? '<div class="absence-dot"></div>' : '';

        html += `<td class="${cellClasses}" data-date="${dayString}">
                    <strong>${day}</strong>
                    ${dotHTML}
                </td>`;

        let totalCellsSoFar = (firstDayIndex - 1) + day;
        if (totalCellsSoFar % 7 === 0) {
            html += `</tr><tr>`;
        }
    }

    html += '</tr></tbody></table>';
    // Bottoni navigazione mese
    let prevMonth = (month === 1) ? 12 : (month - 1);
    let nextMonth = (month === 12) ? 1 : (month + 1);
    let prevMonthName = monthsIta[prevMonth - 1];
    let nextMonthName = monthsIta[nextMonth - 1];

    html += `<div class="navigation">
               <button class="month-nav" data-month="${prevMonth}">${prevMonthName}</button>
               <button class="month-nav" data-month="${nextMonth}">${nextMonthName}</button>
             </div>`;
    html += '</div>';

    document.getElementById('calendar').innerHTML = html;
    setupListeners();
}

// Gestione degli eventi di click
function setupListeners() {
    // Click su una cella del calendario
    document.querySelectorAll('.calendar-day').forEach(cell => {
        cell.addEventListener('click', function() {
            let dateStr = this.getAttribute('data-date');
            if (!userAbsences[dateStr]) {
                // Nessuna assenza => GIF
                showNoAbsences(dateStr);
            } else {
                // Mostriamo la lista assenti
                showAbsences(dateStr, userAbsences[dateStr]);
            }
        });
    });

    // Navigazione mesi
    document.querySelectorAll('.month-nav').forEach(btn => {
        btn.addEventListener('click', function() {
            let newM = parseInt(this.getAttribute('data-month'));
            currentMonth = newM;
            renderCalendar(currentMonth, currentYear);
        });
    });
}

function showAbsences(dateStr, absencesList) {
    // Formattazione data in italiano
    let dateObj = new Date(dateStr.replace(/-/g, '/'));
    let opzioniFormattazione = { day: 'numeric', month: 'long', year: 'numeric' };
    let dateStrIta = dateObj.toLocaleDateString('it-IT', opzioniFormattazione);

    // Creo un elenco HTML di studenti e ore assenza
    let listHTML = '<ul style="text-align:left;">';
    absencesList.forEach(item => {
        listHTML += `<li><strong>${item.student_name}</strong>: ${item.absence_hours}h di assenza</li>`;
    });
    listHTML += '</ul>';

    Swal.fire({
        title: `Dettagli assenze: ${dateStrIta}`,
        html: listHTML,
        icon: 'info',
        confirmButtonText: 'OK',
        backdrop: 'rgba(0, 0, 0, 0.5)'
    });
}

function showNoAbsences(dateStr) {
    let dateObj = new Date(dateStr.replace(/-/g, '/'));
    let opzioniFormattazione = { day: 'numeric', month: 'long', year: 'numeric' };
    let dateStrIta = dateObj.toLocaleDateString('it-IT', opzioniFormattazione);

    let msg = `<img src="https://media.giphy.com/media/d8lUKXD00IXSw/giphy.gif?cid=790b7611xn5dg1mlcc0g7hk6hdo94xtx3dqtpotmlk4uez7b&ep=v1_gifs_search&rid=giphy.gif&ct=g"
               width="250" alt="GIF">
               <p>Nessuna assenza registrata in questa data</p>`;

    Swal.fire({
        title: `Dettagli: ${dateStrIta}`,
        html: msg,
        icon: 'info',
        confirmButtonText: 'OK',
        backdrop: 'rgba(0, 0, 0, 0.5)'
    });
}

// Avvio
let currentYear  = new Date().getFullYear();
let currentMonth = new Date().getMonth() + 1;
renderCalendar(currentMonth, currentYear);
</script>
</body>

