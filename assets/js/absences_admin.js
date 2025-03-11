/**
 * Funzione che chiede tramite swal le ore totali (inserite dall'utente),
 * calcola la percentuale di assenza e copia il risultato in clipboard.
 */
function calcolaPercentuale(studente, corso, totalAbsencesStr) {
    const totalAbsences = parseFloat(totalAbsencesStr);

    Swal.fire({
      title: 'Ore totali del corso?',
      text: 'Inserisci il numero totale di ore (intero o decimale)',
      input: 'number',
      inputAttributes: {
        step: '0.1'
      },
      showCancelButton: true,
      confirmButtonText: 'Calcola',
      cancelButtonText: 'Annulla'
    }).then((result) => {
      if (result.isConfirmed) {
        const totOre = parseFloat(result.value);
        if (isNaN(totOre) || totOre <= 0) {
          Swal.fire('Valore non valido', '', 'error');
          return;
        }
        const perc = (totalAbsences / totOre) * 100;

        // Testo finale da copiare
        const textToCopy = 
            'Studente: ' + studente + ' - ' +
            'Corso: ' + corso + ' - ' +
            'Ore di Assenza: ' + totalAbsences + ' - ' +
            'Percentuale di assenza: ' + perc.toFixed(2) + '%';

        // Copia il testo negli appunti
        copyToClipboard(textToCopy);

        // Avviso di conferma
        Swal.fire('Copiato!', 'Risultato copiato negli appunti', 'success');
      }
    });
}

/**
 * Copia una stringa negli appunti.
 */
function copyToClipboard(text) {
    const temp = document.createElement('textarea');
    temp.value = text;
    document.body.appendChild(temp);
    temp.select();
    document.execCommand('copy');
    document.body.removeChild(temp);
}