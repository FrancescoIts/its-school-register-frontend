function confirmDelete(link) {
    event.preventDefault(); // Evita che il link venga seguito immediatamente

    // Mostra il messaggio di conferma usando SweetAlert2
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
            // Se confermato, segui il link
            window.location.href = link.href;
        }
    });

    // Ritorna false per bloccare il comportamento predefinito del link
    return false;
}

// Funzione per confermare l'attivazione
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
            window.location.href = link.href;
        }
    });
    return false;
}

// Funzione per confermare la disattivazione
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
            window.location.href = link.href;
        }
    });
    return false;
}