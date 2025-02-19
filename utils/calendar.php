<?php
require_once 'config.php';
require_once 'check_session.php';

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

        if (!empty($row['event'])) {  // Filtra solo eventi reali
            $eventData[$row['date']] = [
                'event' => $row['event'],  
                'creator_name' => $creator_name
            ];
        }
    }
    $stmt->close();

    // Genera il calendario
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $calendar = '<div class="dashboard"><h3>Calendario Lezioni</h3>';
    $calendar .= '<table class="calendar-table"><thead><tr>';
    $daysOfWeek = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
    foreach ($daysOfWeek as $day) {
        $calendar .= "<th>$day</th>";
    }
    $calendar .= '</tr></thead><tbody><tr>';

    $firstDayOfMonth = date('N', strtotime("$year-$month-01"));
    for ($i = 1; $i < $firstDayOfMonth; $i++) {
        $calendar .= '<td></td>';
    }

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
        $hasEvent = isset($eventData[$date]) && !empty($eventData[$date]['event']);  // Verifica che ci sia un evento reale
        $eventText = $hasEvent ? htmlspecialchars($eventData[$date]['event'], ENT_QUOTES, 'UTF-8') : "";
        $creatorName = $hasEvent ? htmlspecialchars($eventData[$date]['creator_name'], ENT_QUOTES, 'UTF-8') : "Nessun creatore";

        $calendar .= "<td class='calendar-event" . ($hasEvent ? " has-event" : "") . "' 
                        data-date='$date' 
                        data-event='$eventText' 
                        data-creator='$creatorName'>";
        $calendar .= "<strong>$day</strong>";
        if ($hasEvent) {
            $calendar .= "<div class='event-dot'></div>";
        }
        $calendar .= "</td>";

        if (date('N', strtotime($date)) == 7) {
            $calendar .= '</tr><tr>';
        }
    }

    $calendar .= '</tr></tbody></table></div>';
    return $calendar;
}

echo getCalendar(date('m'), date('Y'), $conn, $studentCourse);
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.calendar-event.has-event').forEach(day => {
            day.addEventListener('click', function () {
                let eventText = this.getAttribute('data-event');
                let creatorName = this.getAttribute('data-creator');
                let clickedDate = this.getAttribute('data-date');

                // Converti la data cliccata in formato leggibile
                let dateObject = new Date(clickedDate);
                const opzioni = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
                const dataItaliana = dateObject.toLocaleDateString('it-IT', opzioni);

                Swal.fire({
                    title: `${dataItaliana}`,
                    html: `<p><strong>Creato da:</strong> ${creatorName}</p><p>${eventText}</p>`,
                    icon: 'info',
                    confirmButtonText: 'Chiudi',
                    showCloseButton: true,
                    background: '#fff',
                    backdrop: 'rgba(0, 0, 0, 0.5)',
                });
            });
        });
    });
</script>
