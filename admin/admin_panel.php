<?php
require_once '../utils/check_session.php'; 
require_once '../utils/course_image_map.php';

$user = checkSession();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$nome_completo = $user['firstname'] . " " . $user['lastname'];
$corsi = $user['courses'] ?? [];

$corso_html = "";
$corso_img = "../assets/img/courses/default.jpg"; 

if (count($corsi) > 0) {
    foreach ($corsi as $corso) {
        $corso_nome = htmlspecialchars($corso['name']);
        $file_img = getCourseImage($corso_nome);
        $img_path = "../assets/img/courses/" . $file_img;

        if (!file_exists($img_path)) {
            $img_path = "../assets/img/courses/default.jpg";
        }

        $corso_html .= "<div class='course-card'>";
        $corso_html .= "<p>{$corso_nome}</p>";
        $corso_html .= "<img src='{$img_path}' alt='Logo Corso'>";
        $corso_html .= "</div>";
    }
} else {
    $corso_html = "<div class='course-card'>Nessun corso assegnato</div>";
}
?>


<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Coordinatore</title>
    <link rel="stylesheet" href="../assets/css/student_panel.css"> 
    <link rel="stylesheet" href="../assets/css/dashboard_style.css"> 
    <link rel="stylesheet" href="../assets/css/calendar.css">
    <link rel="stylesheet" href="../assets/css/overflow.css">
    <link rel="stylesheet" href="../assets/css/stats_total.css">
    <link rel="stylesheet" href="../assets/css/manage_attendance.css">

    <link rel="stylesheet" href="../assets/css/checkbox.css">
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
    document.addEventListener("DOMContentLoaded", function() {
            if (localStorage.getItem('scrollPosition')) {
                window.scrollTo(0, localStorage.getItem('scrollPosition'));
                localStorage.removeItem('scrollPosition');
            }
            document.querySelector("form").addEventListener("submit", function() {
                localStorage.setItem('scrollPosition', window.scrollY);
            });
        });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

<!-- Sezione Corsi -->
<div class="dashboard">
    <h3 class="">Corsi</h3>
    <div class="courses">
        <?php echo $corso_html; ?>
    </div>
</div>

<!-- Sezione Calendario con Eventi -->
<div class="dashboard">
    <h3 class="">Calendario Eventi (Lezioni)</h3>
    <div class="courses">
        <div class="course-card">
            <?php
                require('../utils/calendar_admin.php');
            ?>
        </div>
    </div>
</div>

<!-- Sezione Lezione -->
<div class="dashboard">
    <h3>Lezione di oggi</h3>
    <div class="courses">
        <div class="course-card">
        <?php require('../utils/manage_attendance.php'); ?>
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

<!-- Sezione Calendario Assenze Studenti -->
<div class="dashboard" id="abencesAdmin">
    <h3>Calendario Assenze Studenti</h3>
    <div class="courses">
        <div class="course-card" id="calendar-absences">
            <?php
                require_once '../utils/calendar_absences_admin.php';
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
