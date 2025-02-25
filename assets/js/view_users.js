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