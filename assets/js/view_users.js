// Funzione per aggiornare le schede in modo asincrono
async function refreshUsers() {
    try {
        if (typeof showLoader === 'function') { showLoader(); }
        // Esegui una fetch all'endpoint che restituisce l'HTML aggiornato delle schede
        const response = await fetch('endpoint_users_list.php', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const html = await response.text();
        document.querySelector('.users-container').innerHTML = html;
        Swal.fire('Aggiornato!', 'Le schede sono state aggiornate.', 'success');
    } catch (error) {
        console.error('Errore:', error);
        Swal.fire('Errore!', 'Si è verificato un errore durante l\'aggiornamento.', 'error');
    }
}

// Funzione per eliminare un utente
function confirmDelete(link) {
    event.preventDefault();
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
            if (typeof showLoader === 'function') { showLoader(); }
            fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.action === 'delete') {
                    let card = link.closest('.user-card');
                    if (card) { card.remove(); }
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

// Funzione per attivare un utente
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
            if (typeof showLoader === 'function') { showLoader(); }
            fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.action === 'activate') {
                    let card = link.closest('.user-card');
                    if (card) {
                        let header = card.querySelector('.user-card-header span');
                        if (header) { header.textContent = 'Attivo'; }
                    }
                    link.textContent = 'Disattiva';
                    link.href = `endpoint_users.php?action=deactivate&id_user=${data.id_user}`;
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

// Funzione per disattivare un utente
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
            if (typeof showLoader === 'function') { showLoader(); }
            fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.action === 'deactivate') {
                    let card = link.closest('.user-card');
                    if (card) {
                        let header = card.querySelector('.user-card-header span');
                        if (header) { header.textContent = 'Inattivo'; }
                    }
                    link.textContent = 'Attiva';
                    link.href = `endpoint_users.php?action=activate&id_user=${data.id_user}`;
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

