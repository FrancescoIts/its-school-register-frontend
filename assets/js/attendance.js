    // Funzione per "Seleziona tutti"
    function checkAllStudents() {
      const checkboxes = document.querySelectorAll('.checkbox__input');
      let allChecked = true;
      checkboxes.forEach(checkbox => {
        if (!checkbox.checked) {
          allChecked = false;
        }
      });
      checkboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
      });
    }

    // Funzione "Riempie orari"
    function fillTimes() {
      const defaultStart = document.getElementById('defaultStartTime').value;
      const defaultEnd = document.getElementById('defaultEndTime').value;
      const timeInputs = document.querySelectorAll('input[type="time"]');

      let allFilled = true;
      timeInputs.forEach(input => {
        if (input.name.indexOf('entry_hour') !== -1) {
          if (input.value !== defaultStart) {
            allFilled = false;
          }
        } else if (input.name.indexOf('exit_hour') !== -1) {
          if (input.value !== defaultEnd) {
            allFilled = false;
          }
        }
      });

      timeInputs.forEach(input => {
        if (allFilled) {
          input.value = "";
        } else {
          if (input.name.indexOf('entry_hour') !== -1 && !input.value) {
            input.value = defaultStart;
          } else if (input.name.indexOf('exit_hour') !== -1 && !input.value) {
            input.value = defaultEnd;
          }
        }
      });
    }

    const filterInput = document.getElementById('Filter');
    if (filterInput) {
      filterInput.addEventListener('keyup', function() {
        const filterValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('.attendance-table tr.student-row');
        rows.forEach(row => {
          const studentName = row.querySelector('td').textContent.toLowerCase();
          row.style.display = studentName.indexOf(filterValue) > -1 ? "" : "none";
        });
      });
    }

    // Intercetta il submit del form per inviarlo via AJAX
    $(document).on('submit', '#attendanceForm', function(e) {
      e.preventDefault(); // Impedisci il submit tradizionale
      var form = $(this);
      $.ajax({
          url: form.attr('action'),
          type: form.attr('method'),
          data: form.serialize(),
          dataType: 'json',
          success: function(response) {
              Swal.fire({
                  title: response.status === 'success' ? 'Successo!' : 'Attenzione!',
                  text: response.message,
                  icon: response.status,
                  confirmButtonText: 'OK'
              });
              // Se necessario, aggiorna la sezione delle presenze, ad es. ricarica i dati
          },
          error: function(xhr, status, error) {
              Swal.fire({
                  title: 'Errore!',
                  text: 'Si è verificato un errore durante il salvataggio.',
                  icon: 'error',
                  confirmButtonText: 'OK'
              });
              console.log("Status: " + status + " Error: " + error);
          }
      });
    });

    document.getElementById('id_module').addEventListener('change', function() {
      var selectElement = this;
      var selected = selectElement.value;
      if(selected === "0") {
        // Nessuna azione 
      } else {
        Swal.fire({
          title: 'Conferma scelta modulo',
          text: 'Sei sicuro di voler selezionare questo modulo?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Si, conferma',
          cancelButtonText: 'Annulla'
        }).then((result) => {
          if (!result.isConfirmed) {
            selectElement.value = "0";
          }
        });
      }
    });

    function tutorial(){
      Swal.fire({
        title: 'Tutorial Presenze',
        html: '<p>Se l\'alunno è <strong>presente</strong> bisogna segnare la checkbox corrispondente.</p>' +
              '<p>Se l\'alunno è <strong>assente</strong>, lasciare la checkbox deselezionata.</p>' +
              '<p>Se l\'alunno è presente ma ha effettuato l\'ingresso o l\'uscita fuori orario, segna la presenza tramite la checkbox e poi modifica gli orari usando gli appositi campi.</p>',
        icon: 'info',
        confirmButtonText: 'OK'
    });
    }