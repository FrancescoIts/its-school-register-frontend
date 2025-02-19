<?php
require_once 'config.php';
require_once 'check_session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Assumiamo che l'utente sia loggato e $user definito in check_session
$id_user = $user['id_user'] ?? 0;

// Orario standard di presenza (esempio: 14:00 - 18:00)
$standard_entry = "14:00:00";
$standard_exit  = "18:00:00";

/**
 * 1) Carichiamo gli orari di ingresso e uscita per il 2025
 */
$stmtA = $conn->prepare("
  SELECT date, entry_hour, exit_hour
  FROM attendance
  WHERE id_user = ?
    AND YEAR(date) = 2025
");
$stmtA->bind_param("i", $id_user);
$stmtA->execute();
$resA = $stmtA->get_result();

$absences = []; 
while ($row = $resA->fetch_assoc()) {
    $date = $row['date'];
    $entry = $row['entry_hour'] ?? null;
    $exit  = $row['exit_hour'] ?? null;

    // Se mancano i dati di ingresso o uscita, consideriamo l'intera fascia oraria come assente
    if (!$entry || !$exit) {
        $absences[$date] = 4; 
        continue;
    }

    // Converto gli orari in secondi per fare i calcoli
    $entry_seconds = strtotime($entry);
    $exit_seconds  = strtotime($exit);
    $standard_entry_seconds = strtotime($standard_entry);
    $standard_exit_seconds  = strtotime($standard_exit);

    // Calcolo le ore di assenza
    $absence_hours = 0;

    if ($entry_seconds > $standard_entry_seconds) { 
        // Entrato in ritardo
        $absence_hours += ($entry_seconds - $standard_entry_seconds) / 3600;
    }
    if ($exit_seconds < $standard_exit_seconds) { 
        // Uscito prima del previsto
        $absence_hours += ($standard_exit_seconds - $exit_seconds) / 3600;
    }

    // Se ci sono ore di assenza, le registriamo
    if ($absence_hours > 0) {
        $absences[$date] = round($absence_hours, 2);
    }
}
$stmtA->close();
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Calendario Anno 2025 (Eventi + Assenze)</title>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>

  </style>
</head>
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
    let html = `<div class="calendar-container">
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

            let msg = absenceH ? `Ore di assenza: ${absenceH}` : 'Nessuna assenza registrata';

            Swal.fire({
                title: `Dettagli: ${dateStrIta}`,
                text: msg.trim(),
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
