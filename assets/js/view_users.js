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

// Funzione per modificare la password
function confirmEditPassword(link) {
    event.preventDefault();
    Swal.fire({
        title: 'Modifica Password',
        input: 'password',
        inputLabel: 'Nuova password',
        inputPlaceholder: 'Inserisci la nuova password',
        showCancelButton: true,
        confirmButtonText: 'Modifica',
        cancelButtonText: 'Annulla',
        preConfirm: (newPassword) => {
            if (!newPassword) { Swal.showValidationMessage('Inserisci una nuova password'); }
            return newPassword;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            if (typeof showLoader === 'function') { showLoader(); }
            fetch(link.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `newPassword=${encodeURIComponent(result.value)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.action === 'editPassword') {
                    Swal.fire('Modificata!', 'La password è stata aggiornata.', 'success');
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

// Funzione per modificare nome e cognome
function confirmEditName(link) {
    event.preventDefault();
    Swal.fire({
        title: 'Modifica Nome e Cognome',
        html:
            '<input id="swal-input1" class="swal2-input" placeholder="Nome">' +
            '<input id="swal-input2" class="swal2-input" placeholder="Cognome">',
        focusConfirm: false,
        showCancelButton: true,
        preConfirm: () => {
            const firstname = document.getElementById('swal-input1').value;
            const lastname = document.getElementById('swal-input2').value;
            if (!firstname || !lastname) {
                Swal.showValidationMessage('Inserisci sia il nome che il cognome');
            }
            return { firstname: firstname, lastname: lastname };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            if (typeof showLoader === 'function') { showLoader(); }
            const params = new URLSearchParams();
            params.append('firstname', result.value.firstname);
            params.append('lastname', result.value.lastname);
            fetch(link.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.action === 'editName') {
                    Swal.fire('Modificato!', 'Nome e cognome aggiornati.', 'success');
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

// Funzione per modificare il corso
function confirmEditCourse(link) {
    event.preventDefault();
    let card = link.closest('.user-card');
    // Recupera il testo dei corsi dalla scheda (rimuove "Corsi:" e divide per virgola)
    let coursesParagraph = card.querySelector('.user-card-body p:nth-child(3)');
    let currentCourses = [];
    if (coursesParagraph) {
        let text = coursesParagraph.textContent.replace('Corsi:', '').trim();
        if (text !== 'Nessun corso') {
            currentCourses = text.split(',').map(s => s.trim());
        }
    }
    // Se l'utente ha più di un corso, assumiamo sia docente
    let isTeacher = currentCourses.length > 1;
    if (isTeacher) {
        // Multi-select per docente
        let options = '';
        for (let id in courses) {
            let selected = currentCourses.indexOf(courses[id]) !== -1 ? 'selected' : '';
            options += `<option value="${id}" ${selected}>${courses[id]}</option>`;
        }
        Swal.fire({
            title: 'Modifica Corsi',
            html: `<select id="swal-select-courses" class="swal2-input" multiple>${options}</select>
                   <small>Usa Ctrl/Cmd per selezionare più opzioni</small>`,
            showCancelButton: true,
            confirmButtonText: 'Modifica',
            cancelButtonText: 'Annulla',
            preConfirm: () => {
                const select = document.getElementById('swal-select-courses');
                let selected = Array.from(select.selectedOptions).map(opt => opt.value);
                if (selected.length === 0) { Swal.showValidationMessage('Seleziona almeno un corso'); }
                return selected.join(',');
            }
        }).then((result) => {
            if (result.isConfirmed) {
                if (typeof showLoader === 'function') { showLoader(); }
                fetch(link.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `courses=${encodeURIComponent(result.value)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.action === 'editCourse') {
                        Swal.fire('Modificato!', 'I corsi sono stati aggiornati.', 'success');
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                    Swal.fire('Errore!', 'Si è verificato un errore durante l\'operazione.', 'error');
                });
            }
        });
    } else {
        // Single select per studente
        let options = '<option value="">Seleziona il corso</option>';
        for (let id in courses) {
            let selected = '';
            if (currentCourses.length === 1 && currentCourses[0] === courses[id]) {
                selected = 'selected';
            }
            options += `<option value="${id}" ${selected}>${courses[id]}</option>`;
        }
        Swal.fire({
            title: 'Modifica Corso',
            html: `<select id="swal-select-course" class="swal2-input">${options}</select>`,
            showCancelButton: true,
            confirmButtonText: 'Modifica',
            cancelButtonText: 'Annulla',
            preConfirm: () => {
                const course = document.getElementById('swal-select-course').value;
                if (!course) { Swal.showValidationMessage('Seleziona un corso'); }
                return course;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                if (typeof showLoader === 'function') { showLoader(); }
                fetch(link.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `course=${encodeURIComponent(result.value)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.action === 'editCourse') {
                        Swal.fire('Modificato!', 'Il corso è stato aggiornato.', 'success');
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                    Swal.fire('Errore!', 'Si è verificato un errore durante l\'operazione.', 'error');
                });
            }
        });
    }
    return false;
}

// Funzione per modificare il ruolo (dropdown dinamico)
function confirmEditRole(link) {
    event.preventDefault();
    if (typeof roles === 'undefined') {
        console.error('roles non definito');
        Swal.fire('Errore!', 'Dati dei ruoli non disponibili.', 'error');
        return false;
    }
    let options = '<option value="">Seleziona il ruolo</option>';
    for (let id in roles) {
        options += `<option value="${id}">${roles[id]}</option>`;
    }
    Swal.fire({
        title: 'Modifica Ruolo',
        html: `<select id="swal-select-role" class="swal2-input">${options}</select>`,
        showCancelButton: true,
        confirmButtonText: 'Modifica',
        cancelButtonText: 'Annulla',
        preConfirm: () => {
            const role = document.getElementById('swal-select-role').value;
            if (!role) { Swal.showValidationMessage('Seleziona un ruolo'); }
            return role;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            if (typeof showLoader === 'function') { showLoader(); }
            fetch(link.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `role=${encodeURIComponent(result.value)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.action === 'editRole') {
                    Swal.fire('Modificato!', 'Il ruolo è stato aggiornato.', 'success');
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

// Funzione per modificare l'email (azione separata)
function confirmEditEmail(link) {
    event.preventDefault();
    Swal.fire({
        title: 'Modifica Email',
        input: 'email',
        inputLabel: 'Nuova Email',
        inputPlaceholder: 'Inserisci la nuova email',
        showCancelButton: true,
        confirmButtonText: 'Modifica',
        cancelButtonText: 'Annulla',
        preConfirm: (email) => {
            if (!email) { Swal.showValidationMessage('Inserisci una email valida'); }
            return email;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            if (typeof showLoader === 'function') { showLoader(); }
            fetch(link.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `email=${encodeURIComponent(result.value)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.action === 'editEmail') {
                    Swal.fire('Modificato!', 'L\'email è stata aggiornata.', 'success');
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

// Funzione per modificare il telefono (azione separata)
function confirmEditPhone(link) {
    event.preventDefault();
    Swal.fire({
        title: 'Modifica Telefono',
        input: 'text',
        inputLabel: 'Nuovo Telefono',
        inputPlaceholder: 'Inserisci il nuovo numero di telefono',
        showCancelButton: true,
        confirmButtonText: 'Modifica',
        cancelButtonText: 'Annulla',
        preConfirm: (phone) => {
            if (!phone) { Swal.showValidationMessage('Inserisci un numero di telefono'); }
            return phone;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            if (typeof showLoader === 'function') { showLoader(); }
            fetch(link.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `phone=${encodeURIComponent(result.value)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.action === 'editPhone') {
                    Swal.fire('Modificato!', 'Il numero di telefono è stato aggiornato.', 'success');
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
