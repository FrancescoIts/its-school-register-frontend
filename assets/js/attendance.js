function checkAllStudents() {
    var checkboxes = document.querySelectorAll('.checkbox__input');
    var allChecked = true;
    checkboxes.forEach(function(checkbox) {
        if (!checkbox.checked) {
            allChecked = false;
        }
    });
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = !allChecked;
    });
}

function fillTimes() {
    var defaultStart = document.getElementById('defaultStartTime').value;
    var defaultEnd = document.getElementById('defaultEndTime').value;
    var timeInputs = document.querySelectorAll('input[type="time"]');
    
    var allFilled = true;
    timeInputs.forEach(function(input) {
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
    
    timeInputs.forEach(function(input) {
        if (allFilled) {
            // Se tutti hanno già il valore di default, svuota il campo
            input.value = "";
        } else {
            // Altrimenti, se il campo è vuoto, riempilo con il valore di default
            if (input.name.indexOf('entry_hour') !== -1 && !input.value) {
                input.value = defaultStart;
            } else if (input.name.indexOf('exit_hour') !== -1 && !input.value) {
                input.value = defaultEnd;
            }
        }
    });
}
document.getElementById('studentFilter').addEventListener('keyup', function() {
    var filterValue = this.value.toLowerCase();
    var rows = document.querySelectorAll('.attendance-table tr.student-row');
    rows.forEach(function(row) {
        var studentName = row.querySelector('td').textContent.toLowerCase();
        if (studentName.indexOf(filterValue) > -1) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});