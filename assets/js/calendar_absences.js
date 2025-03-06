document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.c-cal__cel').forEach(cell => {
        cell.addEventListener('click', function() {
          let dateStr = this.getAttribute('data-date');
          if (!dateStr) return; // Se non c'è una data, non fare nulla
      
          let absenceH = this.getAttribute('data-absence');
          let dateObj  = new Date(dateStr);
          let options  = { day: 'numeric', month: 'long', year: 'numeric' };
          let dateStrIta = dateObj.toLocaleDateString('it-IT', options);
          let msg = absenceH 
                      ? `Ore di assenza: ${absenceH}` 
                      : `<img src="https://media.giphy.com/media/d8lUKXD00IXSw/giphy.gif?width=250" alt="GIF">`;
          Swal.fire({
            title: `Dettagli: ${dateStrIta}`,
            html: msg,
            icon: 'info',
            confirmButtonText: 'OK',
            backdrop: 'rgba(0, 0, 0, 0.5)',
          });
        });
      });
      
});
function loadAbsencesCalendar(month, year) {
  fetch(`../utils/calendar_absences_partial.php?month2=${month}&year2=${year}`)
    .then(response => {
      if (!response.ok) throw new Error("Errore di rete");
      return response.text();
    })
    .then(html => {
      const container = document.getElementById('calendar-absences-content');
      if (container) {
        container.innerHTML = html;
        attachAbsencesCalendarEvents();
      } else {
        console.error("Elemento 'calendar-absences-content' non trovato.");
      }
    })
    .catch(error => console.error("Errore nel caricamento del calendario delle assenze:", error));
}


    
    
    function attachAbsencesCalendarEvents() {
        document.querySelectorAll('#calendar-absences-content .c-cal__cel').forEach(cell => {
          cell.addEventListener('click', function() {
            let dateStr = this.getAttribute('data-date');
            if (!dateStr) return;
            let absenceH = this.getAttribute('data-absence');
            let dateObj  = new Date(dateStr);
            let options  = { day: 'numeric', month: 'long', year: 'numeric' };
            let dateStrIta = dateObj.toLocaleDateString('it-IT', options);
            let msg = absenceH 
                      ? `Ore di assenza: ${absenceH}` 
                      : `<img src="https://media.giphy.com/media/d8lUKXD00IXSw/giphy.gif?width=250" alt="GIF">`;
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