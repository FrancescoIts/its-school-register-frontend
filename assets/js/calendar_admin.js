document.addEventListener('DOMContentLoaded', function () {
    let events = calendarData.filter(e => e.event != null && e.created_by != null);

    // Render del calendario
    function renderCalendar() {
        let tbody = document.querySelector('.calendar-table tbody');
        tbody.innerHTML = '';

        // Usiamo i valori passati da PHP per anno e mese.
        let jsYear  = phpYear;           
        let jsMonth = phpMonth - 1;    

        // Primo giorno del mese
        let firstDayDate = new Date(jsYear, jsMonth, 1);
        let firstDay = firstDayDate.getDay(); // Domenica=0, Lunedì=1, etc.

        // Numero di giorni nel mese
        let daysInMonth = new Date(jsYear, jsMonth + 1, 0).getDate();

        let weekRow = document.createElement('tr');

        // Calcola quanti giorni "vuoti" prima del Lunedì (colonna 1).
        // Se firstDay=0 => Domenica => mettiamo 6 blank; se firstDay=1 => 0 blank...
        let blankDays = (firstDay === 0) ? 6 : (firstDay - 1);

        for (let i = 0; i < blankDays; i++) {
            let emptyTd = document.createElement('td');
            emptyTd.style.border = "1px solid #ccc";
            emptyTd.style.padding = "8px";
            weekRow.appendChild(emptyTd);
        }

        // Creiamo le celle con i giorni
        for (let day = 1; day <= daysInMonth; day++) {
            let currentDate = new Date(jsYear, jsMonth, day);

            // Costruiamo la stringa YYYY-MM-DD senza usare toISOString()
            let yyyy = currentDate.getFullYear();
            let mm   = String(currentDate.getMonth() + 1).padStart(2, '0');
            let dd   = String(currentDate.getDate()).padStart(2, '0');
            let dateString = `${yyyy}-${mm}-${dd}`;

            // Trova se c'è un evento esistente in calendarData
            let existingEvent = events.find(e => e.date === dateString);

            let td = document.createElement('td');
            td.style.border = "1px solid #ccc";
            td.style.padding = "8px";

            // Se esiste un evento, mostriamo un "pallino"
            let content = `<strong>${day}</strong>`;
            if (existingEvent) {
                content += ' <div class="event-dot" style="display:inline-block; width:8px; height:8px; background-color:red; border-radius:50%; margin-left:5px;"></div>';
            }

            td.innerHTML = content;

            // Aggiungiamo dataset utili
            td.dataset.date      = dateString;
            td.dataset.eventId   = existingEvent ? existingEvent.id : '';
            td.dataset.createdBy = existingEvent ? existingEvent.created_by : '';

            // Click sul giorno
            td.addEventListener('click', () => manageEvent(td));

            weekRow.appendChild(td);

            // Se Domenica (getDay()=0), chiudiamo la riga
            if (currentDate.getDay() === 0) {
                tbody.appendChild(weekRow);
                weekRow = document.createElement('tr');
            }
        }

        // Se rimangono celle nell'ultima riga, la completiamo
        if (weekRow.children.length > 0) {
            tbody.appendChild(weekRow);
        }
    }


    function manageEvent(td) {
        let dateString = td.dataset.date;             // es. "2025-04-10"
        let [year, month, day] = dateString.split('-'); 
        let dateItalianFormat = `${day}/${month}/${year}`;
        let eventId   = td.dataset.eventId;
        let createdBy = td.dataset.createdBy;

        // Troviamo l'evento corrispondente
        let existingEvent = calendarData.find(e => e.id == eventId) || null;

        let creatorName = "Sconosciuto";
        let eventText   = "Nessun dettaglio disponibile.";

        if (existingEvent) {
            creatorName = existingEvent.creator_name || "Sconosciuto";
            eventText   = existingEvent.event || "Nessun dettaglio disponibile.";
        }

        // Se non è admin e non è creatore dell'evento, mostra solo lettura
        if (existingEvent && !isAdmin && parseInt(createdBy) !== parseInt(userId)) {
            Swal.fire({
                title: `Evento del ${dateItalianFormat}`,
                html: `<p><strong>Creato da:</strong> ${creatorName}</p><p>${eventText}</p>`,
                icon: "info",
                confirmButtonText: "Chiudi"
            });
            return;
        }

        // Per admin o creatore, gestiamo aggiunta/modifica
        let title      = existingEvent ? 'Gestione Evento' : 'Aggiungi Nuovo Evento';
        let inputValue = existingEvent ? existingEvent.event : '';

        let swalOptions = {
            title: title,
            html: existingEvent ? `<p><strong>Creato da:</strong> ${creatorName}</p>` : '',
            input: 'text',
            inputValue: inputValue,
            showCancelButton: true,
            confirmButtonText: existingEvent ? 'Salva' : 'Crea',
            cancelButtonText: 'Annulla'
        };

        // Se l'evento esiste, mostriamo anche il bottone di eliminazione
        if (existingEvent) {
            swalOptions.showDenyButton = true;
            swalOptions.denyButtonText = 'Elimina';
        }

        Swal.fire(swalOptions).then((result) => {
            if (result.isConfirmed) {
                // Salvataggio/aggiornamento evento
                saveEvent(eventId, dateString, result.value);
            } else if (result.isDenied && existingEvent) {
                // Eliminazione evento
                deleteEvent(eventId);
            }
        });
    }

    /**
     * Salva o modifica l'evento su manage_calendar.php
     */
    function saveEvent(eventId, date, eventText) {
        let action = eventId ? 'edit' : 'add';

        fetch('../utils/manage_calendar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: action,
                event_id: eventId,
                date: date,        
                event: eventText,
                id_course: idCourse
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Successo', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Errore', data.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Errore', 'Si è verificato un errore inaspettato.', 'error');
        });
    }

    /**
     * Elimina l'evento
     */
    function deleteEvent(eventId) {
        fetch('../utils/manage_calendar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete',
                event_id: eventId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Successo', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Errore', data.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Errore', 'Si è verificato un errore inaspettato.', 'error');
        });
    }

    renderCalendar();

    document.getElementById('course-select').addEventListener('change', function() {
        window.location.href = "?id_course=" + this.value + "#calendarAdmin";
    });
});
