<?php
require_once 'config.php';
require_once 'check_session.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user = checkSession(true, ['docente','admin','sadmin']);

$queryStats = "
    SELECT 
        u.id_user, 
        CONCAT(u.firstname, ' ', u.lastname) AS full_name,
        SUM(a.absence_hours) AS total_absences,
        (COUNT(DISTINCT a.date) * 8) AS total_max_hours
    FROM users u
    LEFT JOIN attendance a ON u.id_user = a.id_user
    INNER JOIN user_role_courses urc ON u.id_user = urc.id_user
    WHERE urc.id_role = 1 -- Solo studenti
    GROUP BY u.id_user
    ORDER BY full_name
";
$stmt = $conn->prepare($queryStats);
$stmt->execute();
$result = $stmt->get_result();
$students = [];

while ($row = $result->fetch_assoc()) {
    $students[] = [
        'id_user'          => $row['id_user'],
        'full_name'        => $row['full_name'],
        'total_absences'   => $row['total_absences'] ?? 0,
        'total_max_hours'  => $row['total_max_hours'] ?? 0
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statistiche Assenze Studenti</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h2>Statistiche Assenze Studenti</h2>

<div class="student-list">
  <?php if (!empty($students)): ?>
    <?php foreach ($students as $student): ?>
      <div class="student-item" data-userid="<?= $student['id_user'] ?>">
        <div class="student-title">
          <?= htmlspecialchars($student['full_name']) ?>
        </div>
        <p class="student-summary">
          Assenze totali: <?= htmlspecialchars($student['total_absences']) ?> ore
        </p>

        <div class="student-details" id="details-<?= $student['id_user'] ?>">
          <h4>Dettagli assenze per <?= htmlspecialchars($student['full_name']) ?></h4>

          <div class="charts-container">
              <div class="chart-container">
                  <h3>Percentuale di Assenza</h3>
                  <div class="chart-wrapper">
                      <canvas id="absenceChart-<?= $student['id_user'] ?>"></canvas>
                  </div>
                  <div class="absence-percentage" id="percent-<?= $student['id_user'] ?>"></div>
              </div>

              <div class="chart-container">
                  <h3>Assenze per giorno della settimana</h3>
                  <div class="chart-wrapper">
                      <canvas id="weekAbsencesChart-<?= $student['id_user'] ?>"></canvas>
                  </div>
              </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
      <p>Nessuno studente trovato.</p>
  <?php endif; ?>
</div>

<script>
// Variabile per tenere traccia del pannello aperto
let openStudentId = null;

document.addEventListener('DOMContentLoaded', function() {
    const items = document.querySelectorAll('.student-item');

    items.forEach(item => {
        item.addEventListener('click', function() {
            const userId = this.getAttribute('data-userid');
            const detailDiv = document.getElementById('details-' + userId);

            // Se è già aperto, chiudilo
            if (openStudentId === userId) {
                detailDiv.style.display = 'none';
                openStudentId = null;
                return;
            }

            // Chiudi tutti i dettagli aperti
            closeAllDetails();

            // Mostra il dettaglio selezionato
            detailDiv.style.display = 'block';
            openStudentId = userId;

            // Carica i dati via fetch
            loadStudentStats(userId);
        });
    });
});

// Funzione per chiudere tutti i dettagli
function closeAllDetails() {
    document.querySelectorAll('.student-details').forEach(det => {
        det.style.display = 'none';
    });
}

function loadStudentStats(userId) {
    fetch('../utils/stats_student.php?id_user=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }

            const totalAbsences = data.total_absences;
            const totalMaxHours = data.total_max_hours;
            const weekAbsences = data.week_absences || {};

            const percentDiv = document.getElementById('percent-' + userId);
            percentDiv.innerHTML = `<p><strong>Assenze: ${((totalAbsences / totalMaxHours) * 100).toFixed(1)}%</strong></p>`;

            const absenceChartId = 'absenceChart-' + userId;
            const weekChartId = 'weekAbsencesChart-' + userId;

            new Chart(document.getElementById(absenceChartId).getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Ore di Assenza', 'Ore Frequentate'],
                    datasets: [{
                        data: [totalAbsences, totalMaxHours - totalAbsences],
                        backgroundColor: ['#FF4B5C', '#4CAF50'],
                        hoverOffset: 10
                    }]
                }
            });

            // Traduzione e ordine dei giorni della settimana
            const giorniOrdinati = ["Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato", "Domenica"];
            const giorniMappa = {
                "Monday": "Lunedì",
                "Tuesday": "Martedì",
                "Wednesday": "Mercoledì",
                "Thursday": "Giovedì",
                "Friday": "Venerdì",
                "Saturday": "Sabato",
                "Sunday": "Domenica"
            };

            // Creiamo gli array dei giorni e delle assenze in ordine corretto
            const weekLabels = [];
            const weekData = [];

            giorniOrdinati.forEach(giorno => {
                const engDay = Object.keys(giorniMappa).find(key => giorniMappa[key] === giorno);
                weekLabels.push(giorno);
                weekData.push(weekAbsences[engDay] || 0);
            });

            if (weekLabels.length > 0) {
                new Chart(document.getElementById(weekChartId).getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: weekLabels,
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
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Errore nel caricamento delle statistiche:', error));
}

</script>

</body>
</html>
