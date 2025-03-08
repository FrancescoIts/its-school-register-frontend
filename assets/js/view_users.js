function confirmDelete(link) {
    event.preventDefault(); // Evita che il link venga seguito immediatamente

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
            // Mostra il loader se la funzione showLoader è definita
            if (typeof showLoader === 'function') {
                showLoader();
            }
            window.location.href = link.href;
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
            window.location.href = link.href;
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
            window.location.href = link.href;
        }
    });
    return false;
}
