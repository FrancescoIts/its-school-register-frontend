
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

// Filtro per nome/cognome
document.getElementById('Filter').addEventListener('keyup', function() {
  const filterValue = this.value.toLowerCase();
  const rows = document.querySelectorAll('.attendance-table tr.student-row');
  rows.forEach(row => {
    const studentName = row.querySelector('td').textContent.toLowerCase();
    row.style.display = studentName.indexOf(filterValue) > -1 ? "" : "none";
  });
});
