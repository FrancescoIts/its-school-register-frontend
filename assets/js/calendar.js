document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.calendar-event').forEach(day => {
        day.addEventListener('click', function () {
            let eventText = this.getAttribute('data-event') || "";
            let creatorName = this.getAttribute('data-creator') || "Nessun creatore";
            let clickedDate = this.getAttribute('data-date');

            // Suddividiamo la data in anno, mese, giorno
            let parts = clickedDate.split('-');
            let year = parseInt(parts[0]);
            let month = parseInt(parts[1]) - 1; // Mesi 0-based in JS
            let dayNum = parseInt(parts[2]);

            // Creiamo la data in locale, a mezzogiorno, per evitare slittamenti
            let dateObject = new Date(year, month, dayNum, 12, 0, 0, 0);

            const opzioni = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
            const dataItaliana = dateObject.toLocaleDateString('it-IT', opzioni);

            // Se non c'è evento, mostriamo una gif
            let content = eventText.trim()
                ? `<p><strong>Creato da:</strong> ${creatorName}</p><p>${eventText}</p>` 
                : `<img src="https://media.giphy.com/media/d8lUKXD00IXSw/giphy.gif?cid=790b7611xn5dg1mlcc0g7hk6hdo94xtx3dqtpotmlk4uez7b&ep=v1_gifs_search&rid=giphy.gif&ct=g" width="250" alt="Nessun evento">`;

            Swal.fire({
                title: dataItaliana,
                html: content,
                icon: eventText.trim() ? 'info' : null,
                confirmButtonText: 'Chiudi',
                showCloseButton: true,
                background: '#fff',
                backdrop: 'rgba(0, 0, 0, 0.5)',
            });
        });
    });
});
