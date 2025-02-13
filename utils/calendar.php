<?php
ob_start();
require_once 'config.php';

function getCalendar($month, $year, $conn)
{
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    $stmt = $conn->prepare("SELECT date, event FROM calendar WHERE MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->bind_param("ii", $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    $eventData = [];
    while ($event = $result->fetch_assoc()) {
        $eventData[$event['date']] = $event['event'];
    }
    $stmt->close();

    $calendar = '<div class="dashboard">';
    $mesi = [
        'January' => 'Gennaio', 'February' => 'Febbraio', 'March' => 'Marzo', 'April' => 'Aprile',
        'May' => 'Maggio', 'June' => 'Giugno', 'July' => 'Luglio', 'August' => 'Agosto',
        'September' => 'Settembre', 'October' => 'Ottobre', 'November' => 'Novembre', 'December' => 'Dicembre'
    ];
    
    $monthName = date('F', strtotime("$year-$month-01")); 
    $monthNameItaliano = $mesi[$monthName] ?? $monthName; 
    $calendar .= '<h3 class="animated-box"> ' . $monthNameItaliano . ' ' . $year . '</h3>';    
    $calendar .= '<div class="courses"><div class="course-card animated-box">';
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
        $hasEvent = isset($eventData[$date]);
        $eventText = $hasEvent ? htmlspecialchars($eventData[$date]) : "";

        $calendar .= "<td class='calendar-day" . ($hasEvent ? " has-event" : "") . "' data-date='$date' data-event='$eventText'>";
        $calendar .= "<strong>$day</strong>";
        if ($hasEvent) {
            $calendar .= "<div class='event-dot'></div>";
        }
        $calendar .= "</td>";

        if (date('N', strtotime($date)) == 7) {
            $calendar .= '</tr><tr>';
        }
    }

    $calendar .= '</tr></tbody></table>';
    $calendar .= '</div></div></div>';

    return $calendar;
}

echo getCalendar(date('m'), date('Y'), $conn);

?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.calendar-day.has-event').forEach(day => {
            day.addEventListener('click', function () {
                let eventText = this.getAttribute('data-event');

                // Mostra il popup con SweetAlert2
                Swal.fire({
                    title: 'Dettagli lezione',
                    text: eventText,
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