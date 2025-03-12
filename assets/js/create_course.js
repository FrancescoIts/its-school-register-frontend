// Funzione per applicare orari predefiniti a tutti i campi orario
function applyPresetTimes() {
    var presetEntry = document.getElementById('presetEntry').value;
    var presetExit = document.getElementById('presetExit').value;
    if (!presetEntry || !presetExit) {
        Swal.fire({title:'Errore!', text:'Inserisci entrambi gli orari predefiniti.', icon:'error'});
        return;
    }
    // Imposta gli orari per ogni giorno
    var entryFields = document.querySelectorAll('input[name^="start_time_"]');
    var exitFields = document.querySelectorAll('input[name^="end_time_"]');
    entryFields.forEach(function(field) {
        field.value = presetEntry;
    });
    exitFields.forEach(function(field) {
        field.value = presetExit;
    });
}

// Funzione per controllare gli orari e altri campi prima dell'invio del form
document.querySelector('.create-user-form').addEventListener('submit', function(e) {
    // Recupero i valori degli orari
    var startMonday = document.getElementById('start_time_monday').value;
    var endMonday   = document.getElementById('end_time_monday').value;
    var startTuesday = document.getElementById('start_time_tuesday').value;
    var endTuesday   = document.getElementById('end_time_tuesday').value;
    var startWednesday = document.getElementById('start_time_wednesday').value;
    var endWednesday   = document.getElementById('end_time_wednesday').value;
    var startThursday = document.getElementById('start_time_thursday').value;
    var endThursday   = document.getElementById('end_time_thursday').value;
    var startFriday   = document.getElementById('start_time_friday').value;
    var endFriday     = document.getElementById('end_time_friday').value;
    
    // Funzione di controllo orario: restituisce true se l'orario di uscita è maggiore dell'orario di ingresso
    function isValidTime(start, end) {
        return new Date('1970-01-01T' + end + 'Z') > new Date('1970-01-01T' + start + 'Z');
    }
    
    if (!isValidTime(startMonday, endMonday) ||
        !isValidTime(startTuesday, endTuesday) ||
        !isValidTime(startWednesday, endWednesday) ||
        !isValidTime(startThursday, endThursday) ||
        !isValidTime(startFriday, endFriday)) {
            e.preventDefault();
            Swal.fire({title:'Errore!', text:'Controlla che gli orari di uscita siano maggiori degli orari di ingresso per ogni giorno.', icon:'error'});
            return false;
    }
    
    // Controllo sul nome del corso (max 40 caratteri)
    var name = document.getElementById('name').value;
    if(name.length > 40){
        e.preventDefault();
        Swal.fire({title:'Errore!', text:'Il nome del corso non può superare 40 caratteri.', icon:'error'});
        return false;
    }
    
    // Controllo sul campo anno: deve essere numerico e non superare 4 caratteri
    var year = document.getElementById('year').value;
    if(isNaN(year) || year.toString().length > 4){
        e.preventDefault();
        Swal.fire({title:'Errore!', text:'L\'anno deve essere un numero e avere al massimo 4 caratteri (Es. 2024).', icon:'error'});
        return false;
    }
    
    // Controllo sul campo periodo: massimo 9 caratteri
    var period = document.getElementById('period').value;
    if(period.length > 9){
        e.preventDefault();
        Swal.fire({title:'Errore!', text:'Il periodo deve avere al massimo 9 caratteri (Es. 2024-2026).', icon:'error'});
        return false;
    }
});