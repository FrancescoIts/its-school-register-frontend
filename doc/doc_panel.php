<?php
require_once '../utils/check_session.php'; 
require_once '../utils/course_image_map.php';

// Richiama checkSession() che effettuerà i controlli e ritorna i dati utente
$user = checkSession(); 
// Oppure, se non vuoi ritornare nulla, dopo checkSession() puoi fare:
// $user = $_SESSION['user'];

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    <title>Dashboard Studente (o Docente)</title>
    <link rel="stylesheet" href="../assets/css/student_panel.css"> 
    <link rel="stylesheet" href="../assets/css/dashboard_style.css"> 
    <link rel="stylesheet" href="../assets/css/calendar.css">
    <link rel="stylesheet" href="../assets/css/overflow.css">
    <link rel="stylesheet" href="../assets/css/stats_total.css">
    <link rel="shortcut icon" href="../assets/img/favicon.ico">
</head>
<body>
<script src="../assets/js/main.js"></script>
<script>
    // Barra di progresso e animazioni...
    document.addEventListener("scroll", function() {
        const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrollPercent = (scrollTop / scrollHeight) * 100;
        document.getElementById("scrollProgress").style.width = scrollPercent + "%";
    });

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".course-card.info p").forEach(function (element) {
            element.addEventListener("click", function () {
                this.classList.toggle("visible");
            });
        });

        // Effetto digitazione
        const userName = "Ciao <?php echo htmlspecialchars($user['firstname']); ?>"; 
        const dynamicSpan = document.getElementById("dynamicText");
        const cursorSpan = document.getElementById("cursor");
        let i = 0;

        function typeEffect() {
            if (i < userName.length) {
                dynamicSpan.innerHTML += userName.charAt(i);
                i++;
                setTimeout(typeEffect, 100);
            } else {
                cursorSpan.style.display = "inline-block";
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
    <h3>Statistiche Studenti</h3>
    <div class="courses">
        <div class="course-card">
            <?php require('../utils/stats_total.php'); ?>
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
