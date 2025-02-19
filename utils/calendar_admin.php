<?php
require_once 'config.php';
require_once 'check_session.php';

$user     = checkSession();
$id_user  = $user['id_user'];
$roles    = $user['roles'] ?? [];
$isAdmin  = in_array('admin', $roles) || in_array('sadmin', $roles);
$isDocente = in_array('docente', $roles);

if ($isAdmin) {
    // L'admin vede tutti i corsi
    $queryCourses = "SELECT id_course, name FROM courses";
    $stmt = $conn->prepare($queryCourses);
} elseif ($isDocente) {
    // Il docente vede i corsi a cui è assegnato
    $queryCourses = "
        SELECT DISTINCT c.id_course, c.name
        FROM courses c
        INNER JOIN user_role_courses urc ON c.id_course = urc.id_course
        WHERE urc.id_user = ?
    ";
    $stmt = $conn->prepare($queryCourses);
    $stmt->bind_param("i", $id_user);
} else {
    // Se non è admin né docente
    die("Non autorizzato.");
}

$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($courses)) {
    die("Nessun corso assegnato.");
}

// Seleziona corso
$id_course = isset($_GET['id_course']) ? intval($_GET['id_course']) : $courses[0]['id_course'];

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
            "id" => $row['id'],
            "date" => $row['date'],
            "event" => $row['event'],
            "created_by" => $row['created_by'],
            "creator_name" => !empty($row['firstname']) && !empty($row['lastname']) 
                ? $row['firstname'] . " " . $row['lastname'] 
                : "Sconosciuto"
        ];
    }
    $stmt->close();

    return json_encode($events);
}


$currentMonth  = date('m');
$currentYear   = date('Y');
$calendarData  = getCalendar($currentMonth, $currentYear, $conn, $id_course);
?>

<!-- HTML -->
<div class="calendar-header" style="margin-bottom: 10px;">
    <h3>Calendario per il corso selezionato</h3>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    let calendarData = <?= $calendarData ?>;
    let userId       = <?= json_encode($id_user) ?>;
    let isAdmin      = <?= json_encode($isAdmin) ?>;
    let isDocente    = <?= json_encode($isDocente) ?>;
    let idCourse     = <?= json_encode($id_course) ?>;
    
    // Converte string -> data di "oggi" in formato YYYY-MM-DD (per i docenti)
    let today = new Date();
    let YYYY  = today.getFullYear();
    let MM    = String(today.getMonth() + 1).padStart(2, '0');
    let DD    = String(today.getDate()).padStart(2, '0');
    let todayString = `${YYYY}-${MM}-${DD}`;
    
    let events = calendarData.filter(e => e.event != null && e.created_by != null);

    function renderCalendar() {
        let tbody = document.querySelector('.calendar-table tbody');
        tbody.innerHTML = '';

        // Data del primo giorno del mese corrente
        let firstDayDate = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
        let firstDay = firstDayDate.getDay(); // 0=DOM, 1=LUN, 2=MAR,...
        
        // Numero di giorni del mese
        let daysInMonth = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).getDate();
        
        let weekRow = document.createElement('tr');
        
        // Calcola i giorni "vuoti" prima del primo lunedì
        // (se firstDay=0 => 6 blank, se firstDay=1 => 0 blank, ecc.)
        let blankDays = (firstDay === 0) ? 6 : (firstDay - 1);
        for (let i = 0; i < blankDays; i++) {
            let emptyTd = document.createElement('td');
            emptyTd.style.border = "1px solid #ccc";
            emptyTd.style.padding = "8px";
            weekRow.appendChild(emptyTd);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            let currentDate = new Date(new Date().getFullYear(), new Date().getMonth(), day);
            let dateString  = currentDate.toISOString().split('T')[0];
            
            // Trova se c'è un evento effettivo in "events" (dove event != null e created_by != null)
            let existingEvent = events.find(e => e.date === dateString);
            
            let td = document.createElement('td');
            td.style.border = "1px solid #ccc";
            td.style.padding = "8px";
            
            // Se esiste un evento, mostra il pallino
            let content = `<strong>${day}</strong>`;
            if (existingEvent) {
                content += ' <div class="event-dot" style="display:inline-block; width:8px; height:8px; background-color:red; border-radius:50%; margin-left:5px;"></div>';
            }
            
            td.innerHTML = content;
            // Salviamo nei dataset le informazioni
            td.dataset.date      = dateString;
            // Se esiste un evento, memorizza i campi, altrimenti null
            td.dataset.eventId   = existingEvent ? existingEvent.id : '';
            td.dataset.createdBy = existingEvent ? existingEvent.created_by : '';

            // Gestisci clic sul giorno
            td.addEventListener('click', () => manageEvent(td));

            weekRow.appendChild(td);

            // Se Domenica (getDay()=0), andiamo a capo
            if (currentDate.getDay() === 0) {
                tbody.appendChild(weekRow);
                weekRow = document.createElement('tr');
            }
        }
        if (weekRow.children.length > 0) {
            tbody.appendChild(weekRow);
        }
    }

    function manageEvent(td) {
    let dateString = td.dataset.date;
    let eventId = td.dataset.eventId;
    let createdBy = td.dataset.createdBy;

    let existingEvent = calendarData.find(e => e.id == eventId);

    if (!existingEvent) {
        return; // Nessun evento in questa data
    }

    let creatorName = existingEvent.creator_name || "Sconosciuto";
    let eventText = existingEvent.event || "Nessun dettaglio disponibile.";

    // Se l'utente non è admin e non è il creatore, mostra il popup con il nome del creatore
    if (existingEvent && !isAdmin && parseInt(createdBy) !== parseInt(userId)) {
        Swal.fire({
            title: `Evento del ${dateString}`,
            html: `<p><strong>Creato da:</strong> ${creatorName}</p><p>${eventText}</p>`,
            icon: "info",
            confirmButtonText: "Chiudi"
        });
        return;
    }

    let title = existingEvent ? 'Gestione Evento' : 'Aggiungi Nuovo Evento';
    let inputValue = existingEvent ? existingEvent.event : '';

    let swalOptions = {
        title: title,
        input: 'text',
        inputValue: inputValue,
        showCancelButton: true,
        confirmButtonText: existingEvent ? 'Salva' : 'Crea',
        cancelButtonText: 'Annulla'
    };

    if (existingEvent) {
        swalOptions.showDenyButton = true;
        swalOptions.denyButtonText = 'Elimina';
    }

    Swal.fire(swalOptions).then((result) => {
        if (result.isConfirmed) {
            saveEvent(eventId, dateString, result.value);
        } else if (result.isDenied) {
            deleteEvent(eventId);
        }
    });
}


    function saveEvent(eventId, date, eventText) {
        // Se eventId non esiste, => "add"
        // Se eventId esiste, => "edit"
        let action = eventId ? 'edit' : 'add';
        
        fetch('../utils/manage_calendar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: action,
                event_id: eventId,
                date: date,
                event: eventText,
                id_course: idCourse
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Successo', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Errore', data.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Errore', 'Si è verificato un errore inaspettato.', 'error');
        });
    }

    function deleteEvent(eventId) {
        fetch('../utils/manage_calendar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete',
                event_id: eventId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Successo', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Errore', data.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Errore', 'Si è verificato un errore inaspettato.', 'error');
        });
    }
    renderCalendar();

    // Cambio corso
    document.getElementById('course-select').addEventListener('change', function() {
        window.location.href = "?id_course=" + this.value;
    });
});
</script>
