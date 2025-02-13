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
    <h3>Statistiche studenti</h3>
    <div class="courses">
        <div class="course-card">
            <!-- Elenco studenti -->
            <div id="student-list">
                <!-- Verrà popolato via PHP o AJAX -->
            </div>

            <!-- Contenitore per le statistiche di uno studente -->
            <div id="stats-container">
                <p>Seleziona uno studente per vedere le statistiche.</p>
            </div>
        </div>
    </div>
</div>

<!-- Carichiamo jQuery e Chart.js (se serve) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function(){
    // 1. Recuperiamo elenco degli studenti
    $.ajax({
        url: "stats_total.php",     // Usa lo stesso file "unificato" o un endpoint dedicato
        method: "GET",
        dataType: "html",           // in "stats_total.php" potresti restituire l'elenco in HTML
        data: { mode: "listOnly" }, // un parametro per differenziare la richiesta
        success: function(htmlList) {
            // Mettiamo l'elenco dentro #student-list
            $("#student-list").html(htmlList);

            // Agganciamo l'evento di click
            $(".student-item").click(function() {
                var studentId = $(this).data("id");

                $.ajax({
                    url: "stats_total.php",
                    method: "POST",
                    dataType: "json",
                    data: { id_user: studentId },
                    success: function(response) {
                        if (response.error) {
                            $("#stats-container").html(
                                "<p style='color: red;'>" + response.error + "</p>"
                            );
                        } else {
                            var statsHtml = "<p><strong>Ore di assenza totali:</strong> " 
                                            + response.total_absences + "</p>";
                            statsHtml += "<p><strong>Ore massime disponibili:</strong> " 
                                         + response.total_max_hours + "</p>";
                            statsHtml += "<h3>Assenze per giorno della settimana:</h3><ul>";
                            
                            $.each(response.week_absences, function(day, hours) {
                                statsHtml += "<li>" + day + ": " + hours + " ore</li>";
                            });
                            
                            statsHtml += "</ul>";
                            $("#stats-container").html(statsHtml);
                            // Se vuoi disegnare un grafico con Chart.js, puoi farlo qui
                        }
                    },
                    error: function() {
                        $("#stats-container").html("<p style='color: red;'>Errore nel recupero delle statistiche.</p>");
                    }
                });
            });
        },
        error: function() {
            $("#student-list").html("<p style='color:red'>Impossibile caricare la lista studenti</p>");
        }
    });
});
</script>



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
