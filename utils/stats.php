<?php
require_once 'check_session.php';
require_once 'config.php'; 
require_once 'course_image_map.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$user = $_SESSION['user'];
$id_user = $user['id_user'];

// Ore massime consentite
$max_hours_per_year = 900;
$total_max_hours = 1800;

// Query per ottenere le ore di assenza totali
$query = "SELECT SUM(absence_hours) AS total_absences FROM attendance WHERE id_user = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
$absence_data = $result->fetch_assoc();
$total_absences = $absence_data['total_absences'] ?? 0;

// Calcolo percentuale di assenza
$absence_percentage = 0;
if ($total_absences > 0) {
    $absence_percentage = round(($total_absences / $total_max_hours) * 100, 2);
}


?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Statistiche Assenze</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/calendar.css">
</head>
<body>

    <div class="chart-container">
        <h3>Percentuale di Assenza</h3>
        <div class="chart-wrapper">
            <canvas id="absenceChart"></canvas>
        </div>
        <div class="absence-percentage"><strong><?php echo $absence_percentage; ?>%</strong></div>
    </div>
    <br>
    <div class="chart-container">
        <h3>Giorni con più assenze</h3>
        <div class="chart-wrapper">
            <canvas id="dayAbsencesChart"></canvas>
        </div>
    </div>  


    <!-- Script per i grafici con Chart.js -->
    <script>
        const ctx = document.getElementById('absenceChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Ore di Assenza', 'Ore Frequentate'],
                datasets: [{
                    data: [
                        <?php echo $total_absences; ?>, 
                        <?php echo $total_max_hours - $total_absences; ?>
                    ],
                    backgroundColor: ['#FF6384', '#36A2EB']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        const ctx2 = document.getElementById('dayAbsencesChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($days_absence)); ?>,
                datasets: [{
                    label: 'Numero di assenze',
                    data: <?php echo json_encode(array_values($days_absence)); ?>,
                    backgroundColor: '#FFCE56'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: <?php echo ($max_absences + 2); ?>
                    }
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.calendar-day.has-event').forEach(day => {
                day.addEventListener('click', function () {
                    let hours = this.getAttribute('data-hours');
                    document.getElementById('absenceDetails').innerText = "Ore di assenza: " + hours;
                    document.getElementById('popup').style.display = 'block';
                });
            });

            document.querySelector('.close-popup').addEventListener('click', function (event) {
                event.preventDefault();
                document.getElementById('popup').style.display = 'none';
            });
        });

    </script>
</body>
</html>
