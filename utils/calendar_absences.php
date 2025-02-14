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
    const firstDayJS   = new Date(year, month - 1, 1).getDay(); 
    const daysInMonth  = new Date(year, month, 0).getDate();    


    const daysOfWeek = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];

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
    const monthNames = [
        "Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno",
        "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"
      ];
    // Pulsanti next/prev
    let prevM = (month === 1)? 12 : (month-1);
    let nextM = (month === 12)? 1  : (month+1);
    
    html += `<div class="navigation">
               <button class="month-nav" data-month="${prevM}"> ${monthNames[prevM - 1]}</button>
               <button class="month-nav" data-month="${nextM}"> ${monthNames[nextM - 1]}</button>
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

      let dateObj = new Date(dateStr.replace(/-/g, '/'));

      // Definiamo le opzioni di formattazione: in italiano, con giorno, mese (nome esteso), e anno
      let opzioniFormattazione = { 
        day: 'numeric', 
        month: 'long', 
        year: 'numeric'
      };

      // Con toLocaleDateString("it-IT") otteniamo la data in italiano
      let dateStrIta = dateObj.toLocaleDateString('it-IT', opzioniFormattazione);

      let msg = '';
      if (eventTitle)  msg += `Evento: ${eventTitle}\n`;
      if (absenceH)    msg += `Ore di assenza: ${absenceH}\n`;
      if (!msg) {
        Swal.fire({
          title: `Dettagli ${dateStrIta}`,
          html: '<img src="https://media.giphy.com/media/1l7GT4n3CGTzW/giphy.gif" style="width:100%; max-width:300px; border-radius:10px;">',
          showConfirmButton: false,
          timer: 3000,
          backdrop: 'rgba(0, 0, 0, 0.5)',
        });
        return;
      }

      Swal.fire({
        title: `Dettagli: ${dateStrIta}`,
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
