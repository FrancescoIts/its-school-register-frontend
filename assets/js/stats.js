document.addEventListener("DOMContentLoaded", () => {
    fetch("../utils/stats.php")
        .then(res => res.json())
        .then(data => {
            console.log("DEBUG data:", data); // <--- Controlla i dati ricevuti
            if (data.error) return console.error(data.error);

            // Dati generali
            const totalAbsences = data.total_absences || 0;
            const totalMaxHours = data.total_max_hours || 0;
            const weekAbsences = data.week_absences || {};

            // Calcolo percentuale di assenze
            const absencePercentage = totalMaxHours > 0
                ? ((totalAbsences / totalMaxHours) * 100).toFixed(1)
                : 0;

            document.querySelector('.absence-percentage').innerHTML =
                `<p><strong>Assenze: ${absencePercentage}%</strong></p>`;
                const dayTranslation = {
                    "monday": "Lunedì",
                    "tuesday": "Martedì",
                    "wednesday": "Mercoledì",
                    "thursday": "Giovedì",
                    "friday": "Venerdì"
                };


            // Chart 1 (doughnut) - Percentuale di Assenza
            const ctx1 = document.getElementById('absenceChart');
            if (ctx1) {
                new Chart(ctx1, {
                    type: 'doughnut',
                    data: {
                        labels: ['Ore di Assenza', 'Ore Frequentate'],
                        datasets: [{
                            data: [totalAbsences, totalMaxHours - totalAbsences],
                            backgroundColor: ['#FF4B5C', '#4CAF50']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }

            // Chart 2 (bar) - Assenze per giorno della settimana
            const ctx2 = document.getElementById('weekAbsencesChart');
            if (ctx2) {
                const giorniFeriali = ["Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì"];

            //Array dei dati per il grafico, gestendo eventuali errori di giorni
            const weekData = giorniFeriali.map(giorno => {
                const englishDay = Object.keys(dayTranslation).find(key => dayTranslation[key] === giorno);
                return weekAbsences[englishDay] || 0;
            });

                new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: giorniFeriali,
                        datasets: [{
                            label: 'Ore di assenza per giorno',
                            data: weekData,
                            backgroundColor: '#FFA500'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }

        })
        .catch(err => console.error("Errore nel caricamento delle statistiche:", err));
});