document.addEventListener('DOMContentLoaded', function () {
  let calendarHeader = document.getElementById('calendarHeader');
  if (!calendarHeader) {
      console.error("Elemento con id 'calendarHeader' non trovato.");
      return;
  }
  let currentMonth = parseInt(calendarHeader.dataset.month);
  let currentYear  = parseInt(calendarHeader.dataset.year);

  // Funzione per aggiornare il testo dell'intestazione del calendario
  function updateHeader() {
    const mesiItaliani = ["Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"];
    document.getElementById('currentMonth').textContent = mesiItaliani[currentMonth - 1] + " " + currentYear;
    // Aggiorna anche gli attributi data del blocco header
    calendarHeader.dataset.month = currentMonth;
    calendarHeader.dataset.year = currentYear;
  }

  function loadCalendar(month, year) {
      fetch(`../utils/calendar_partial.php?month1=${month}&year1=${year}`)
        .then(response => {
          if (!response.ok) throw new Error("Errore di rete");
          return response.text();
        })
        .then(html => {
          document.getElementById('calendarContent').innerHTML = html;
          attachCalendarEvents();
        })
        .catch(error => console.error("Errore nel caricamento del calendario:", error));
    }
    
  // Funzione per gestire il click sulle celle del calendario
  function attachCalendarEvents() {
    document.querySelectorAll('.c-cal__cel').forEach(cell => {
      cell.addEventListener('click', function() {
        let dateStr   = this.getAttribute('data-day');
        let eventData = this.getAttribute('data-event');
        let creator   = this.getAttribute('data-creator');
        let dateObj   = new Date(dateStr);
        let options   = { day: 'numeric', month: 'long', year: 'numeric' };
        let dateStrIta = dateObj.toLocaleDateString('it-IT', options);
        let msg = eventData 
                  ? `<strong>Evento:</strong> ${eventData}<br><strong>Creato da:</strong> ${creator}` 
                  : `<img src="https://media.giphy.com/media/d8lUKXD00IXSw/giphy.gif?width=250" alt="GIF"">`;
        Swal.fire({
          title: `Dettagli: ${dateStrIta}`,
          html: msg,
          icon: 'info',
          confirmButtonText: 'OK',
          backdrop: 'rgba(0, 0, 0, 0.5)',
        });
      });
    });
  }

  // Gestione dei pulsanti di navigazione
  document.getElementById('prevBtn').addEventListener('click', function() {
    let newMonth = currentMonth - 1;
    let newYear  = currentYear;
    if (newMonth < 1) {
      newMonth = 12;
      newYear--;
    }
    currentMonth = newMonth;
    currentYear  = newYear;
    updateHeader();
    loadCalendar(currentMonth, currentYear);
  });

  document.getElementById('nextBtn').addEventListener('click', function() {
    let newMonth = currentMonth + 1;
    let newYear  = currentYear;
    if (newMonth > 12) {
      newMonth = 1;
      newYear++;
    }
    currentMonth = newMonth;
    currentYear  = newYear;
    updateHeader();
    loadCalendar(currentMonth, currentYear);
  });

  // Aggiorna l'intestazione inizialmente e collega gli eventi
  updateHeader();
  attachCalendarEvents();
});
