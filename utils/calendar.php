<?php
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
    $calendar .= '<h3 class="animated-box">Calendario di ' . $monthNameItaliano . ' ' . $year . '</h3>';    
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

    $calendar .= "<div class='popup' id='popup'>
                    <div class='popup__content'>
                        <h2>DETTAGLIO LEZIONE</h2>
                        <br>
                        <p id='eventDetails' class='popup__text'></p>
                        <a href='#' class='button close-popup'>❌</a>
                    </div>
                  </div>";

    return $calendar;
}

echo getCalendar(date('m'), date('Y'), $conn);
?>

<style>
    .calendar-day {
        position: relative;
        cursor: pointer;
        padding: 10px;
        text-align: center;
    }

    .calendar-day.has-event {
        font-weight: bold;
    }

    .event-dot {
        width: 6px;
        height: 6px;
        background-color: blue;
        border-radius: 50%;
        margin: 4px auto;
    }

    .popup {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.popup:target {
    opacity: 1;
    visibility: visible;
}

.popup__content {
    width: 90%; /* Imposta la larghezza relativa allo schermo */
    max-width: 500px; /* Imposta una larghezza massima */
    padding: 20px;
    background-color: white;
    box-shadow: 0 2rem 4rem rgba(0, 0, 0, 0.2);
    border-radius: 10px;
    text-align: center;
    overflow-y: auto; /* Per contenuti lunghi */
    color: rgb(0, 0, 0);
}

.popup__text {
    font-size: 1.2rem;
    margin-bottom: 2rem;
    word-wrap: break-word; /* Aggiunto per gestire testi lunghi */
    color: rgb(53, 44, 44);
}

.close-popup {
    display: inline-block;
    margin-top: 10px;
    text-decoration: none;
    font-size: 1.2rem;
    color: red;
    cursor: pointer;
}

/* Media Query per dispositivi molto piccoli */
@media (max-width: 600px) {
    .popup__content {
        width: 95%; /* Riduci la larghezza su schermi più piccoli */
        padding: 15px;
    }

    .popup__text {
        font-size: 1rem;
    }

    .close-popup {
        font-size: 1rem;
    }
}

</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.calendar-day.has-event').forEach(day => {
            day.addEventListener('click', function () {
                let eventText = this.getAttribute('data-event');
                document.getElementById('eventDetails').innerText = eventText;
                document.getElementById('popup').style.opacity = '1';
                document.getElementById('popup').style.visibility = 'visible';
            });
        });
        document.querySelector('.close-popup').addEventListener('click', function () {
            document.getElementById('popup').style.opacity = '0';
            document.getElementById('popup').style.visibility = 'hidden';
        });
    });
</script>