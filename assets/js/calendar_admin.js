document.addEventListener('DOMContentLoaded', function () {
    // Filtra gli eventi presenti nei dati JSON
    let events = calendarData.filter(e => e.event && e.created_by);

    function renderCalendar() {
        let container = document.getElementById('calendarContent');
        container.innerHTML = '';

        let jsYear  = phpYear;
        let jsMonth = phpMonth - 1;

        let firstDayDate = new Date(jsYear, jsMonth, 1);
        let firstDay = firstDayDate.getDay(); // Domenica=0, Lunedi=1, etc.
        let daysInMonth = new Date(jsYear, jsMonth + 1, 0).getDate();
        let blankDays = (firstDay === 0) ? 6 : (firstDay - 1);

        // Header dei giorni della settimana
        let headerRow = document.createElement('div');
        headerRow.className = 'c-cal__row';
        let dayNames = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
        dayNames.forEach(dayName => {
            let col = document.createElement('div');
            col.className = 'c-cal__col';
            col.innerText = dayName;
            headerRow.appendChild(col);
        });
        container.appendChild(headerRow);

        let weekRow = document.createElement('div');
        weekRow.className = 'c-cal__row';
        for (let i = 0; i < blankDays; i++) {
            let emptyCell = document.createElement('div');
            emptyCell.className = 'c-cal__cel';
            weekRow.appendChild(emptyCell);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            let currentDate = new Date(jsYear, jsMonth, day);
            let yyyy = currentDate.getFullYear();
            let mm   = String(currentDate.getMonth() + 1).padStart(2, '0');
            let dd   = String(currentDate.getDate()).padStart(2, '0');
            let dateString = `${yyyy}-${mm}-${dd}`;

            // Cerca eventuali eventi in questa data
            let existingEvent = events.find(e => e.date === dateString);

            let cell = document.createElement('div');
            cell.className = 'c-cal__cel';
            if (existingEvent) {
                cell.classList.add('event');
                cell.setAttribute('data-event', existingEvent.event);
            }
            cell.setAttribute('data-day', dateString);
            cell.dataset.eventId = existingEvent ? existingEvent.id : '';
            cell.dataset.createdBy = existingEvent ? existingEvent.created_by : '';

            cell.innerHTML = `<p><strong>${day}</strong></p>`;
            cell.addEventListener('click', () => manageEvent(cell));

            weekRow.appendChild(cell);

            if (currentDate.getDay() === 0) {
                container.appendChild(weekRow);
                weekRow = document.createElement('div');
                weekRow.className = 'c-cal__row';
            }
        }

        while (weekRow.childElementCount < 7) {
            let emptyCell = document.createElement('div');
            emptyCell.className = 'c-cal__cel';
            weekRow.appendChild(emptyCell);
        }
        container.appendChild(weekRow);
    }

    function manageEvent(cell) {
        let dateString = cell.dataset.day;
        let [year, month, day] = dateString.split('-');
        let dateItalianFormat = `${day}/${month}/${year}`;
        let eventId = cell.dataset.eventId;
        let createdBy = cell.dataset.createdBy;

        let existingEvent = calendarData.find(e => e.id == eventId) || null;
        let creatorName = "Sconosciuto";
        let eventText = "Nessun dettaglio disponibile.";

        if (existingEvent) {
            creatorName = existingEvent.creator_name || "Sconosciuto";
            eventText = existingEvent.event || "Nessun dettaglio disponibile.";
        }

        /* 
           Se esiste un evento e l'utente NON ha i permessi per gestirlo 
           (ossia, se NON è admin E l'evento non è stato creato da lui),
           mostriamo un popup in sola lettura con i dettagli.
        */
        if (existingEvent && (!isAdmin && parseInt(createdBy) !== parseInt(userId))) {
            Swal.fire({
                title: `Dettagli: ${dateItalianFormat}`,
                html: `<strong>Evento:</strong> ${eventText}<br><strong>Creato da:</strong> ${creatorName}`,
                icon: "info",
                confirmButtonText: "OK",
                backdrop: 'rgba(0, 0, 0, 0.5)'
            });
            return;
        }

        // Se non esiste l'evento oppure l'utente può gestirlo, permettiamo l'aggiunta/modifica
        let title = existingEvent ? 'Gestione Evento' : 'Aggiungi Nuovo Evento';
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

        if (existingEvent) {
            swalOptions.showDenyButton = true;
            swalOptions.denyButtonText = 'Elimina';
        }

        Swal.fire(swalOptions).then((result) => {
            if (result.isConfirmed) {
                saveEvent(eventId, dateString, result.value);
            } else if (result.isDenied && existingEvent) {
                deleteEvent(eventId);
            }
        });
    }

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
