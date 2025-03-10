function confirmDelete(link) {
    event.preventDefault(); // Previeni il comportamento di default
    Swal.fire({
        title: 'Sei sicuro?',
        text: "Questa azione non può essere annullata.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sì, elimina!',
        cancelButtonText: 'Annulla'
    }).then((result) => {
        if (result.isConfirmed) {
            if (typeof showLoader === 'function') {
                showLoader();
            }
            fetch(link.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.action === 'delete') {
                    // Rimuovi la riga corrispondente dal DOM
                    let row = link.closest('tr');
                    if (row) {
                        row.remove();
                    }
                    Swal.fire('Eliminato!', "L'utente è stato eliminato.", 'success');
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                Swal.fire('Errore!', 'Si è verificato un errore durante l\'operazione.', 'error');
            });
        }
    });
    return false;
}

function confirmActivate(link) {
    event.preventDefault();
    Swal.fire({
        title: 'Attiva Utente',
        text: "Sei sicuro di voler attivare questo utente?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sì, attiva!',
        cancelButtonText: 'Annulla'
    }).then((result) => {
        if (result.isConfirmed) {
            if (typeof showLoader === 'function') {
                showLoader();
            }
            fetch(link.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.action === 'activate') {
                    // Aggiorna lo stato dell'utente e il testo del pulsante
                    let row = link.closest('tr');
                    if (row) {
                        let statusCell = row.querySelector('td:nth-child(7)');
                        if (statusCell) {
                            statusCell.textContent = 'Attivo';
                        }
                        // Cambia il pulsante da "Attiva" a "Disattiva"
                        link.textContent = 'Disattiva';
                        // Aggiorna l'attributo href per l'azione opposta
                        link.href = `?action=deactivate&id_user=${data.id_user}`;
                    }
                    Swal.fire('Attivato!', "L'utente è stato attivato.", 'success');
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                Swal.fire('Errore!', 'Si è verificato un errore durante l\'operazione.', 'error');
            });
        }
    });
    return false;
}

function confirmDeactivate(link) {
    event.preventDefault();
    Swal.fire({
        title: 'Disattiva Utente',
        text: "Sei sicuro di voler disattivare questo utente?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sì, disattiva!',
        cancelButtonText: 'Annulla'
    }).then((result) => {
        if (result.isConfirmed) {
            if (typeof showLoader === 'function') {
                showLoader();
            }
            fetch(link.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.action === 'deactivate') {
                    // Aggiorna lo stato dell'utente e il testo del pulsante
                    let row = link.closest('tr');
                    if (row) {
                        let statusCell = row.querySelector('td:nth-child(7)');
                        if (statusCell) {
                            statusCell.textContent = 'Inattivo';
                        }
                        // Cambia il pulsante da "Disattiva" a "Attiva"
                        link.textContent = 'Attiva';
                        // Aggiorna l'attributo href per l'azione opposta
                        link.href = `?action=activate&id_user=${data.id_user}`;
                    }
                    Swal.fire('Disattivato!', "L'utente è stato disattivato.", 'success');
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                Swal.fire('Errore!', 'Si è verificato un errore durante l\'operazione.', 'error');
            });
        }
    });
    return false;
}
