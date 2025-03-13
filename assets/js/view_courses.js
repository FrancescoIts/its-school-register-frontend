function showDetails(courseData) {
    var htmlContent = "<p><strong>Corso:</strong> " + courseData.name + " (" + courseData.period + ")</p>" +
                      "<p><strong>Anno:</strong> " + courseData.year + "</p>" +
                      "<p><strong>Admin (" + courseData.admin_count + "):</strong><br>" + (courseData.admin_names || "Nessuno") + "</p>" +
                      "<p><strong>Docenti (" + courseData.teacher_count + "):</strong><br>" + (courseData.teacher_names || "Nessuno") + "</p>" +
                      "<p><strong>Studenti (" + courseData.student_count + "):</strong><br>" + (courseData.student_names || "Nessuno") + "</p>";
    
    Swal.fire({
        title: 'Dettagli Utenti',
        html: htmlContent,
        icon: 'info',
        allowOutsideClick: false,
        showCloseButton: true,
        confirmButtonText: 'Chiudi'
    });
}

function deleteCourse(courseData) {
    var courseName = courseData.name;
    var coursePeriod = courseData.period;
    Swal.fire({
        title: 'Conferma Eliminazione',
        text: 'Sei sicuro di voler eliminare il corso "' + courseName + ' (' + coursePeriod + ')"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sì, elimina!',
        cancelButtonText: 'Annulla',
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('delete_course.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ course_id: courseData.id_course })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Successo',
                        text: data.message,
                        icon: 'success'
                    }).then(() => {
                        // Ricarica la pagina per aggiornare l'elenco dei corsi
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Errore',
                        text: data.message,
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Errore',
                    text: 'Si è verificato un errore: ' + error,
                    icon: 'error'
                });
            });
        }
    });
}