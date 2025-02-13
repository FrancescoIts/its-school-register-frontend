<?php
require_once 'config.php';
require_once 'check_session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Assumiamo che l'utente sia loggato e $user definito in check_session
$id_user = $user['id_user'] ?? 0;

/**
 * 1) Carichiamo le ASSENZE dell'anno 2025 (tabella `attendance`).
 */
$stmtA = $conn->prepare("
  SELECT date, absence_hours
  FROM attendance
  WHERE id_user = ?
    AND YEAR(date) = 2025
");
$stmtA->bind_param("i", $id_user);
$stmtA->execute();
$resA = $stmtA->get_result();

$absences = [];  // Esempio: [ '2025-02-12' => 3, ... ]
while ($row = $resA->fetch_assoc()) {
    $absences[$row['date']] = $row['absence_hours'];
}
$stmtA->close();

/**
 * 2) Carichiamo gli EVENTI dell'anno 2025 (tabella `calendar`).
 *    Esempio: [ '2025-02-14' => 'San Valentino', ... ]
 */
$stmtE = $conn->prepare("
  SELECT date, event
  FROM calendar
  WHERE YEAR(date) = 2025
");
$stmtE->execute();
$resE = $stmtE->get_result();

$events = [];
while ($row = $resE->fetch_assoc()) {
    $events[$row['date']] = $row['event'];
}
$stmtE->close();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Calendario Anno 2025 (Eventi + Assenze)</title>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>

    .navigation { text-align:center; margin-top:10px; }
    .month-nav {
      background-color: #3498db;
      color: #ffffff;
      border: none;
      padding: 8px 12px;
      margin: 0 5px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      transition: background-color 0.3s ease;
    }
    .month-nav:hover {
      background-color: #2980b9;
    }
    .month-nav:active {
      background-color: #1f6390;
    }

    /* Dot per assenze (rosso) */
    .absence-dot {
      display: block;
      margin: 4px auto;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background-color: red; /* rosso */
    }

    /* Giorno che ha un'assenza */
    .absence-day {
      background-color: #ffe5e5; /* leggero rosino */
    }

  </style>
</head>
<body>

<div id="calendar"></div>

<script>
// Dati delle assenze e degli eventi in formato JSON
const userAbsences = <?php echo json_encode($absences); ?>; 
const userEvents   = <?php echo json_encode($events); ?>;   

let currentYear  = new Date().getFullYear();
let currentMonth = new Date().getMonth() + 1; // 1..12 (oggi)

function renderCalendar(month, year) {
    // Calcola quanti giorni nel mese e che giorno della settimana è il 1°
    const firstDayJS   = new Date(year, month - 1, 1).getDay(); // 0=Dom,1=Lun,...
    const daysInMonth  = new Date(year, month, 0).getDate();    // es. 30,31,...

    // Label giorni
    const daysOfWeek = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];
    // Label mesi
    const monthsIta = [
      'Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno',
      'Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'
    ];
    let monthName = monthsIta[month - 1] || '??';

    let html = `<div class="calendar-container">`;
    html += `<div class="calendar-header"><h3>${monthName} ${year}</h3></div>`;

    // Inizio tabella
    html += `<table class="calendar-table"><thead><tr>`;
    for (let d of daysOfWeek) {
      html += `<th>${d}</th>`;
    }
    html += `</tr></thead><tbody><tr>`;
    let firstDayIndex = (firstDayJS === 0) ? 7 : firstDayJS;
    
    // Celle vuote prima del giorno 1
    for (let i = 1; i < firstDayIndex; i++) {
      html += '<td></td>';
    }

    // Riempio i giorni
    for (let day = 1; day <= daysInMonth; day++) {
      let dayString = `${year}-${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`;

      // Controllo se c'è un evento e/o un'assenza
      let hasAbsence = (userAbsences[dayString] !== undefined);

      // Determino la classe per la cella (per colorare lo sfondo)
      let cellClasses = 'calendar-day';
      if (hasAbsence) cellClasses += ' absence-day';

      // Costruisco i dot
      let dotHTML = '';
      if (hasAbsence) dotHTML += '<div class="absence-dot"></div>';

      html += `<td class="${cellClasses}"
                    data-date="${dayString}"
                    data-absence="${hasAbsence ? userAbsences[dayString] : ''}"
                 <strong>${day}</strong>
                 ${dotHTML}
                </td>`;

      // Se arrivo a Domenica, vado a capo
      let totalCellsSoFar = (firstDayIndex - 1) + day;
      if (totalCellsSoFar % 7 === 0) {
        html += `</tr><tr>`;
      }
    }

    html += '</tr></tbody></table>';

    // Pulsanti next/prev
    let prevM = (month === 1)? 12 : (month-1);
    let nextM = (month === 12)? 1  : (month+1);
    html += `<div class="navigation">
               <button class="month-nav" data-month="${prevM}">⬅</button>
               <button class="month-nav" data-month="${nextM}">➡</button>
             </div>`;

    html += '</div>'; // fine container

    // Inserisco l'HTML
    document.getElementById('calendar').innerHTML = html;

    // Aggiungo i listener
    setupListeners();
}

function setupListeners() {
  // Click su giorno
  document.querySelectorAll('.calendar-day').forEach(cell => {
    cell.addEventListener('click', function() {
      let dateStr    = this.getAttribute('data-date');
      let absenceH   = this.getAttribute('data-absence'); // stringa
      let eventTitle = this.getAttribute('data-event');   // stringa

      let msg = '';
      if (eventTitle)  msg += `Evento: ${eventTitle}\n`;
      if (absenceH)    msg += `Ore di assenza: ${absenceH}\n`;
      if (!msg) {
        Swal.fire({
          title: `Dettagli ${dateStr}`,
          html: '<img src="https://media.giphy.com/media/1l7GT4n3CGTzW/giphy.gif" style="width:100%; max-width:300px; border-radius:10px;">',
          showConfirmButton: false,
          timer: 3000,
          backdrop: 'rgba(0, 0, 0, 0.5)',
        });
        return;
      }

      Swal.fire({
        title: `Dettagli: ${dateStr}`,
        text: msg.trim(),
        icon: 'info',
        confirmButtonText: 'OK',
        backdrop: 'rgba(0, 0, 0, 0.5)',
      });
    });
  });

  // Click sulle frecce
  document.querySelectorAll('.month-nav').forEach(btn => {
    btn.addEventListener('click', function() {
      let newM = parseInt(this.getAttribute('data-month'));
      currentMonth = newM;
      // Se vuoi restare su anno 2025 fisso, ok
      renderCalendar(currentMonth, currentYear);
    });
  });
}

// Avvio con il mese corrente
renderCalendar(currentMonth, currentYear);
</script>

</body>
</html>
