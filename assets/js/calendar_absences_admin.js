document.addEventListener('DOMContentLoaded', function () {
    attachAbsencesCalendarEvents();
});

// Funzione per attaccare il listener a tutte le celle del calendario delle assenze
function attachAbsencesCalendarEvents() {
    document.querySelectorAll('#calendar-absences-content .c-cal__cel').forEach(cell => {
        cell.addEventListener('click', function() {
            let dateStr  = this.getAttribute('data-date');
            if (!dateStr) return; // Se non c'è una data, non fare nulla
            
            let absencesJson = this.getAttribute('data-absence'); 
            // absencesJson conterrà una stringa JSON, es. '[{"id_user":12,"student_name":"Mario Rossi","hours":2.5}, ...]'

            let dateObj  = new Date(dateStr);
            let options  = { day: 'numeric', month: 'long', year: 'numeric' };
            let dateStrIta = dateObj.toLocaleDateString('it-IT', options);

            // Se per qualche motivo è vuoto o non parse-able, mostriamo la GIF
            if (!absencesJson) {
                Swal.fire({
                    title: `Dettagli: ${dateStrIta}`,
                    html: '<img src="https://media.giphy.com/media/d8lUKXD00IXSw/giphy.gif?width=250" alt="GIF">',
                    icon: 'info',
                    confirmButtonText: 'OK',
                    backdrop: 'rgba(0, 0, 0, 0.5)'
                });
                return;
            }

            let parsed;
            try {
                parsed = JSON.parse(absencesJson); 
            } catch(e) {
                parsed = [];
            }

            // Se l'array è vuoto, mostra GIF
            if (!parsed || !parsed.length) {
                Swal.fire({
                    title: `Dettagli: ${dateStrIta}`,
                    html: '<img src="https://media.giphy.com/media/d8lUKXD00IXSw/giphy.gif?width=250" alt="GIF">',
                    icon: 'info',
                    confirmButtonText: 'OK',
                    backdrop: 'rgba(0, 0, 0, 0.5)'
                });
                return;
            }
            
            // Costruiamo un elenco con studente e ore di assenza
            let htmlMsg = '<h4>Elenco assenze:</h4>';
            parsed.forEach(record => {
                let nomeStudente = record.student_name || 'Studente sconosciuto';
                let oreAssenza   = record.hours || 0;
                // Arrotonda o formatta ore se vuoi
                htmlMsg += `<p><strong>${nomeStudente}</strong>: ${oreAssenza.toFixed(1)} ore</p>`;
            });

            Swal.fire({
                title: `Dettagli: ${dateStrIta}`,
                html: htmlMsg,
                icon: 'info',
                confirmButtonText: 'OK',
                backdrop: 'rgba(0, 0, 0, 0.5)'
            });
        });
    });
}

// Funzione per caricare il calendario delle assenze tramite AJAX
function loadAbsencesCalendar(month, year) {
    fetch(`../utils/calendar_absences_admin.php?ajax=1&month2=${month}&year2=${year}`)
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
