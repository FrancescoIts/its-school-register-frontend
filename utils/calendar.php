<?php
require_once 'config.php';
require_once 'check_session.php';

// Impostiamo il fuso orario correttamente (Europa/Roma)
date_default_timezone_set('Europe/Rome');

$user = checkSession();
$id_user = $user['id_user'];
$role = $user['roles'][0] ?? 'studente';

// Prendiamo il corso dello studente
$stmt = $conn->prepare("SELECT id_course FROM user_role_courses WHERE id_user = ? AND id_role = 1 LIMIT 1");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
$studentCourse = $result->fetch_assoc()['id_course'] ?? null;
$stmt->close();

// Se lo studente non ha un corso, non può vedere nulla
if (!$studentCourse) {
    die("Nessun corso assegnato.");
}

// Funzione per ottenere gli eventi con il nome del creatore
function getCalendar($month, $year, $conn, $id_course)
{
    $stmt = $conn->prepare("
        SELECT c.date, c.event, c.created_by, 
               IFNULL(u.firstname, '') AS firstname, 
               IFNULL(u.lastname, '') AS lastname
        FROM calendar c
        LEFT JOIN users u ON c.created_by = u.id_user
        WHERE MONTH(c.date) = ? AND YEAR(c.date) = ? AND c.id_course = ?
          AND c.event IS NOT NULL
    ");
    $stmt->bind_param("iii", $month, $year, $id_course);
    $stmt->execute();
    $result = $stmt->get_result();

    $eventData = [];
    while ($row = $result->fetch_assoc()) {
        $creator_name = trim($row['firstname'] . " " . $row['lastname']);
        if (empty($creator_name)) {
            $creator_name = "Sconosciuto";
        }

        if (!empty($row['event'])) {
            $eventData[$row['date']] = [
                'event' => $row['event'],  
                'creator_name' => $creator_name
            ];
        }
    }
    $stmt->close();

    // Genera il calendario in HTML
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $calendar = '<div class="dashboard"><h3>Calendario Lezioni</h3>';
    $calendar .= '<table class="calendar-table"><thead><tr>';
    $daysOfWeek = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
    foreach ($daysOfWeek as $day) {
        $calendar .= "<th>$day</th>";
    }
    $calendar .= '</tr></thead><tbody><tr>';

    // Calcoliamo il giorno della settimana del primo giorno del mese (1=Lun ... 7=Dom)
    $firstDayOfMonth = date('N', strtotime("$year-$month-01"));
    // Inseriamo le celle vuote prima del giorno 1
    for ($i = 1; $i < $firstDayOfMonth; $i++) {
        $calendar .= '<td></td>';
    }

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);

        // Verifichiamo se esiste davvero un evento
        $hasEvent = isset($eventData[$date]) && !empty($eventData[$date]['event']);
        $eventText = $hasEvent ? htmlspecialchars($eventData[$date]['event'], ENT_QUOTES, 'UTF-8') : "";
        $creatorName = $hasEvent ? htmlspecialchars($eventData[$date]['creator_name'], ENT_QUOTES, 'UTF-8') : "Nessun creatore";

        $calendar .= "<td class='calendar-event' 
                        data-date='$date' 
                        data-event='$eventText' 
                        data-creator='$creatorName'>";

        $calendar .= "<strong>$day</strong>";
        if ($hasEvent) {
            $calendar .= "<div class='event-dot'></div>";
        }
        $calendar .= "</td>";

        // Se è domenica, andiamo a capo
        if (date('N', strtotime($date)) == 7) {
            $calendar .= '</tr><tr>';
        }
    }

    $calendar .= '</tr></tbody></table></div>';
    return $calendar;
}

// Mostriamo il mese e l'anno correnti
echo getCalendar(date('m'), date('Y'), $conn, $studentCourse);
?>

<!-- Inclusione di SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Al caricamento della pagina
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.calendar-event').forEach(day => {
        day.addEventListener('click', function () {
            let eventText = this.getAttribute('data-event') || "";
            let creatorName = this.getAttribute('data-creator') || "Nessun creatore";
            let clickedDate = this.getAttribute('data-date');

            // Suddividiamo la data in anno, mese, giorno
            let parts = clickedDate.split('-');
            let year = parseInt(parts[0]);
            let month = parseInt(parts[1]) - 1; // Mesi 0-based in JS
            let dayNum = parseInt(parts[2]);

            // FIX: Creiamo la data in locale, a mezzogiorno, per evitare slittamenti
            let dateObject = new Date(year, month, dayNum, 12, 0, 0, 0);

            const opzioni = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
            const dataItaliana = dateObject.toLocaleDateString('it-IT', opzioni);

            // Se non c'è evento, mostriamo una gif
            let content = eventText.trim()
                ? `<p><strong>Creato da:</strong> ${creatorName}</p><p>${eventText}</p>` 
                : `<img src="https://media.giphy.com/media/d8lUKXD00IXSw/giphy.gif?cid=790b7611xn5dg1mlcc0g7hk6hdo94xtx3dqtpotmlk4uez7b&ep=v1_gifs_search&rid=giphy.gif&ct=g" width="250" alt="Nessun evento">`;

            Swal.fire({
                title: dataItaliana,
                html: content,
                icon: eventText.trim() ? 'info' : null,
                confirmButtonText: 'Chiudi',
                showCloseButton: true,
                background: '#fff',
                backdrop: 'rgba(0, 0, 0, 0.5)',
            });
        });
    });
});
</script>
