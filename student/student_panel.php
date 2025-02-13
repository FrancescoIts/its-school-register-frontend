<?php 
require_once '../utils/check_session.php'; 
require_once '../utils/course_image_map.php';

checkSession();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user = $_SESSION['user'];

$nome_completo = $user['firstname'] . " " . $user['lastname'];
$corsi = $user['courses'] ?? [];

if (count($corsi) > 0) {
    $corso = $corsi[0]['name'];
    $file_img = getCourseImage($corso);
    $corso_img = "../assets/img/courses/" . $file_img;
} else {
    $corso = "Nessun corso assegnato";
    $corso_img = "../assets/img/courses/default.jpg";
}

if (!file_exists($corso_img)) {
    $corso_img = "../assets/img/courses/default.jpg";
}   
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Studente</title>
    <link rel="stylesheet" href="../assets/css/student_panel.css"> 
    <link rel="stylesheet" href="../assets/css/dashboard_style.css"> 
    <link rel="stylesheet" href="../assets/css/calendar.css">
    <link rel="stylesheet" href="../assets/css/overflow.css">  
    <link rel="shortcut icon" href="../assets/img/favicon.ico">
</head>
<body>
<script src="../assets/js/main.js"></script>
<script>
    // Barra di progresso in base allo scroll
    document.addEventListener("scroll", function() {
        const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrollPercent = (scrollTop / scrollHeight) * 100;
        document.getElementById("scrollProgress").style.width = scrollPercent + "%";
    });
    // Dati personali blurati
    document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".course-card.info p").forEach(function (element) {
        element.addEventListener("click", function () {
            this.classList.toggle("visible");
        });
    });
});
document.addEventListener("DOMContentLoaded", function() {
    const userName = "Ciao <?php echo htmlspecialchars($user['firstname']); ?>"; // Nome utente
    const dynamicSpan = document.getElementById("dynamicText");
    const cursorSpan = document.getElementById("cursor");

    let i = 0;

    function typeEffect() {
        if (i < userName.length) {
            dynamicSpan.innerHTML += userName.charAt(i);
            i++;
            setTimeout(typeEffect, 100); // Velocità di scrittura
        } else {
            cursorSpan.style.display = "inline-block"; // Mantiene il cursore
        }
    }

    if (dynamicSpan) {
        typeEffect();
    }
});


</script>

<div class="scroll-progress" id="scrollProgress"></div>

<!-- Navbar -->
<div class="navbar">
    <div class="logo">
        <a href="https://www.itssmart.it/">
            <img src="../assets/img/logo.png" alt="Logo" id="rotateImage">
        </a>
    </div>
    <div class="navbar-title">
        <a class="type1">ITS@Registro</a>:<a class="type2">~</a>$ 
        <span id="dynamicText"></span><span id="cursor">|</span>
    </div>
    <a class="logout" href="../utils/logout.php">Logout</a>
    <button class="theme-toggle" id="theme-toggle">🌙</button>
</div>


<!-- Sezione Corso -->
<div class="dashboard">
    <h3 class="">Corso</h3>
    <div class="courses">
        <div class="course-card"><?php echo htmlspecialchars($corso); ?></div>
        <img src="<?php echo htmlspecialchars($corso_img); ?>" alt="Logo Corso"> 
    </div>
</div>

<!-- Sezione Calendario con Eventi -->
<div class="dashboard">
    <h3 class="">Calendario Eventi (Lezioni)</h3>
    <div class="courses">
        <div class="course-card">
        <?php
            require('../utils/calendar.php');
        ?>
        </div>
    </div>
</div>


<!-- Sezione Statistiche -->
<div class="dashboard">
    <h3 class="">Le tue statistiche</h3>
    <div class="courses">
        <div class="course-card">
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

            <!-- Grafico Percentuale Assenze -->
            <div class="chart-container">
                <h3>Percentuale di Assenza</h3>
                <div class="chart-wrapper">
                    <canvas id="absenceChart"></canvas>
                </div> <div class="absence-percentage" id="percent"></div>
               
            </div>

            <br>
            <br>

            <!-- Grafico Giorni della Settimana con più Assenze -->
            <div class="chart-container">
                <h3>Assenze per giorno della settimana</h3>
                <div class="chart-wrapper">
                    <canvas id="weekAbsencesChart"></canvas>
                </div>
            </div>  

            <!-- Script per caricare i dati -->
            <script>
            document.addEventListener("DOMContentLoaded", function () {
                fetch("../utils/stats.php")
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error(data.error);
                            return;
                        }

                        const totalAbsences = data.total_absences;
                        const totalMaxHours = data.total_max_hours;
                        const weekLabels = Object.keys(data.week_absences);
                        const weekData = Object.values(data.week_absences);
                        
                        // Percentuale di assenza
                        const absencePercentage = ((totalAbsences / totalMaxHours) * 100).toFixed(1);
                        const attendancePercentage = (100 - absencePercentage).toFixed(1);
                        document.querySelector('.absence-percentage').innerHTML = 
                            `<p><strong>Assenze: ${absencePercentage}%</strong> `;

                        // Grafico a torta
                        const ctx = document.getElementById('absenceChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Ore di Assenza', 'Ore Frequentate'],
                                datasets: [{
                                    data: [totalAbsences, totalMaxHours - totalAbsences],
                                    backgroundColor: ['#FF4B5C', '#4CAF50'],
                                    hoverOffset: 10
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    tooltip: {
                                        callbacks: {
                                            label: function(tooltipItem) {
                                                return tooltipItem.raw + " ore";
                                            }
                                        }
                                    }
                                }
                            }
                        });

                        // Grafico a barre
                        if (weekLabels.length > 0) {
                            const ctx2 = document.getElementById('weekAbsencesChart').getContext('2d');
                            new Chart(ctx2, {
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
            });
            </script>
        </div>
    </div>
</div>


<!-- Sezione Calendario con Assenze -->
<div class="dashboard">
    <h3 class="">Calendario Assenze</h3>
    <div class="courses">
        <div class="course-card" id="calendar-absences">
        <?php
            require('../utils/calendar_absences.php');
        ?>
        </div>
    </div>
</div>


<!-- Sezione Bacheca -->
<div class="dashboard">
    <h3 class="">Bacheca</h3>
    <div class="courses">
        <div class="course-card">Contenuto della bacheca</div>
    </div>
</div>

<!-- Sezione Informazioni personali -->
<div class="dashboard">
    <h3 class="">Le tue informazioni</h3>
    <div class="courses">
        <div class="course-card info">
            <p><strong>Nome:</strong> <?php echo htmlspecialchars($user['firstname']); ?></p>
            <p><strong>Cognome:</strong> <?php echo htmlspecialchars($user['lastname']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Telefono:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
        </div>
    </div>
</div>  
</body>
</html>
