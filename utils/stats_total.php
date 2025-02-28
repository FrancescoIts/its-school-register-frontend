<?php
require_once 'config.php';
require_once 'check_session.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user = checkSession(true, ['docente', 'admin', 'sadmin']);
$corsi = $user['courses'] ?? [];
$docCourseIds = array_column($corsi, 'id_course');

if (empty($docCourseIds)) {
    echo json_encode(['error' => 'Nessun corso associato.']);
    exit;
}

// Ottengo l'anno corrente
$currentYear = date('Y');

// Query orari reali dei corsi con l'anno corrente
$queryStats = "
SELECT 
    u.id_user, 
    CONCAT(u.firstname, ' ', u.lastname) AS full_name,
    c.name AS course_name,
    900 AS total_max_hours,
    COALESCE(SUM(
        CASE 
            WHEN (a.entry_hour IS NULL OR a.exit_hour IS NULL) THEN 
                TIMESTAMPDIFF(HOUR, 
                    CASE DAYNAME(a.date)
                        WHEN 'Monday' THEN c.start_time_monday
                        WHEN 'Tuesday' THEN c.start_time_tuesday
                        WHEN 'Wednesday' THEN c.start_time_wednesday
                        WHEN 'Thursday' THEN c.start_time_thursday
                        WHEN 'Friday' THEN c.start_time_friday
                    END, 
                    CASE DAYNAME(a.date)
                        WHEN 'Monday' THEN c.end_time_monday
                        WHEN 'Tuesday' THEN c.end_time_tuesday
                        WHEN 'Wednesday' THEN c.end_time_wednesday
                        WHEN 'Thursday' THEN c.end_time_thursday
                        WHEN 'Friday' THEN c.end_time_friday
                    END
                )
            ELSE GREATEST(0, 
                TIME_TO_SEC(
                    TIMEDIFF(
                        CASE DAYNAME(a.date)
                            WHEN 'Monday' THEN c.start_time_monday
                            WHEN 'Tuesday' THEN c.start_time_tuesday
                            WHEN 'Wednesday' THEN c.start_time_wednesday
                            WHEN 'Thursday' THEN c.start_time_thursday
                            WHEN 'Friday' THEN c.start_time_friday
                        END, 
                        a.entry_hour
                    )
                ) / 3600
            ) + GREATEST(0, 
                TIME_TO_SEC(
                    TIMEDIFF(
                        a.exit_hour, 
                        CASE DAYNAME(a.date)
                            WHEN 'Monday' THEN c.end_time_monday
                            WHEN 'Tuesday' THEN c.end_time_tuesday
                            WHEN 'Wednesday' THEN c.end_time_wednesday
                            WHEN 'Thursday' THEN c.end_time_thursday
                            WHEN 'Friday' THEN c.end_time_friday
                        END
                    )
                ) / 3600
            )
        END
    ), 0) AS total_absences
FROM users u
INNER JOIN user_role_courses urc ON u.id_user = urc.id_user
INNER JOIN courses c ON urc.id_course = c.id_course
LEFT JOIN attendance a ON u.id_user = a.id_user AND a.id_course = urc.id_course AND YEAR(a.date) = ?
WHERE urc.id_role = 1 -- Solo studenti
AND urc.id_course IN (" . implode(',', array_fill(0, count($docCourseIds), '?')) . ")
GROUP BY u.id_user, full_name, c.name;
";

// Prepariamo la query
$stmt = $conn->prepare($queryStats);
if ($stmt === false) {
    die(json_encode(['error' => 'Errore nella preparazione della query: ' . $conn->error]));
}

// Bind dei parametri dinamici (anno corrente + id dei corsi)
$types = 'i' . str_repeat('i', count($docCourseIds)); // Aggiungo 'i' per l'anno corrente
$params = array_merge([$currentYear], $docCourseIds);
$stmt->bind_param($types, ...$params);

// Esecuzione della query
$stmt->execute();
$result = $stmt->get_result();


$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = [
        'id_user'         => $row['id_user'],
        'full_name'       => $row['full_name'],
        'course'          => $row['course_name'],
        'total_absences'  => $row['total_absences'],
        'total_max_hours' => $row['total_max_hours'],
        'absence_percentage' => round(($row['total_absences'] / $row['total_max_hours']) * 100, 2) . '%'
    ];
}

$stmt->close();
?>



<div class="scrollable-table">
<div class="student-list">
  <?php if (!empty($students)): ?>
    <?php foreach ($students as $student): ?>
      <div class="student-item" data-userid="<?= $student['id_user'] ?>">
          <div class="student-title">
              <?= htmlspecialchars($student['full_name']) ?> (Corso: <?= htmlspecialchars($student['course']) ?>)
          </div>

          <div class="student-details" id="details-<?= $student['id_user'] ?>">
            <h4>Dettagli assenze per <?= htmlspecialchars($student['full_name']) ?> 
            (Corso: <?= htmlspecialchars($student['course']) ?>, Anno: <?= htmlspecialchars(date('Y')) ?>)</h4>
            <br>
            <div class="charts-container">
                <!-- Grafico a torta -->
                <div class="chart-container">
                    <h3>Percentuale di Assenza</h3>
                    <div class="chart-wrapper">
                        <canvas id="absenceChart-<?= $student['id_user'] ?>"></canvas>
                    </div>
                    <div class="absence-percentage" id="percent-<?= $student['id_user'] ?>">
                        <strong>Assenze: <?= $student['absence_percentage'] ?> (<?= $student['total_absences'] ?> ore su 900)</strong>
                    </div>
                </div>

                <!-- Grafico a barre -->
                <div class="chart-container">
                    <h3>Assenze per Giorno</h3>
                    <div class="chart-wrapper">
                        <canvas id="absenceDaysChart-<?= $student['id_user'] ?>"></canvas>
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
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const items = document.querySelectorAll('.student-item');
    let openStudentId = null;

    items.forEach(item => {
        item.addEventListener('click', function() {
            const userId = this.getAttribute('data-userid');
            const detailDiv = document.getElementById('details-' + userId);

            if (openStudentId === userId) {
                detailDiv.classList.remove('show');
                setTimeout(() => { detailDiv.style.display = 'none'; }, 500);
                openStudentId = null;
                return;
            }

            closeAllDetails();
            detailDiv.style.display = 'block';
            setTimeout(() => { detailDiv.classList.add('show'); }, 10);
            openStudentId = userId;

            loadStudentStats(userId);
        });
    });
});

function closeAllDetails() {
    document.querySelectorAll('.student-details.show').forEach(det => {
        det.classList.remove('show');
        setTimeout(() => { det.style.display = 'none'; }, 500);
    });
}

function loadStudentStats(userId) {
    // Prima richiesta per il grafico a torta (percentuale di assenza)
    fetch('../utils/stats_student.php?id_user=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }

            const totalAbsences = data.total_absences;
            const totalMaxHours = 900;
            const absencePercentage = ((totalAbsences / totalMaxHours) * 100).toFixed(1);

            document.getElementById('percent-' + userId).innerHTML =
                `<p><strong>Assenze: ${absencePercentage}% (${totalAbsences} ore su 900)</strong></p>`;

            const ctxDoughnut = document.getElementById('absenceChart-' + userId).getContext('2d');
            if (document.getElementById('absenceChart-' + userId).chart) {
                document.getElementById('absenceChart-' + userId).chart.destroy();
            }
            const doughnutChart = new Chart(ctxDoughnut, {
                type: 'doughnut',
                data: {
                    labels: ['Assenze', 'Presenze'],
                    datasets: [{
                        data: [totalAbsences, totalMaxHours - totalAbsences],
                        backgroundColor: ['#FF4B5C', '#4CAF50']
                    }]
                },
                options: {
                    responsive: true,
                }
            });
            document.getElementById('absenceChart-' + userId).chart = doughnutChart;
        })
        .catch(error => console.error('Errore nel caricamento delle statistiche:', error));

    // Seconda richiesta per il grafico a barre (assenze per giorno)
    fetch('../utils/stats_student.php?id_user=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }
            
            // Mappatura dei giorni: dall'inglese all'italiano
            const invertedMapping = {
                'Lunedì': 'Monday',
                'Martedì': 'Tuesday',
                'Mercoledì': 'Wednesday',
                'Giovedì': 'Thursday',
                'Venerdì': 'Friday'
            };
            // Etichette in italiano in ordine
            const labels = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì'];
            const weekAbsences = data.week_absences || {};
            const daysData = labels.map(italianDay => {
                const englishDay = invertedMapping[italianDay];
                return weekAbsences[englishDay] || 0;
            });

            const ctxBar = document.getElementById('absenceDaysChart-' + userId).getContext('2d');
            if (document.getElementById('absenceDaysChart-' + userId).chart) {
                document.getElementById('absenceDaysChart-' + userId).chart.destroy();
            }
            const barChart = new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Ore di assenza per giorno',
                        data: daysData,
                        backgroundColor: '#FFD700'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                        }
                    }
                }
            });
            document.getElementById('absenceDaysChart-' + userId).chart = barChart;
        })
        .catch(error => console.error('Errore nel caricamento delle statistiche per i giorni:', error));
}
</script>

</body>
</html>
