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
    <link rel="stylesheet" href="../assets/css/overflow.css">  
    <link rel="shortcut icon" href="../assets/img/favicon.ico">
</head>
<body>

<script>
    // Barra di progresso in base allo scroll
    document.addEventListener("scroll", function() {
        const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrollPercent = (scrollTop / scrollHeight) * 100;
        document.getElementById("scrollProgress").style.width = scrollPercent + "%";
    });

</script>

<div class="scroll-progress" id="scrollProgress"></div>

<!-- Navbar -->
<div class="navbar">
    <div class="logo"><a href="https://www.itssmart.it/">
        <img src="../assets/img/logo.png" alt="Logo" id="rotateImage">
        </a>
    </div>
    <div>
        <a class="logout" href="../utils/logout.php">Logout</a>
        <button class="theme-toggle" id="theme-toggle">🌙</button>
    </div>
</div>

<!-- Sezione Corso -->
<div class="dashboard">
    <h3 class="">Corso</h3>
    <div class="courses">
        <div class="course-card"><?php echo htmlspecialchars($corso); ?></div>
        <img src="<?php echo htmlspecialchars($corso_img); ?>" alt="Logo Corso"> 
    </div>
</div>

<!-- Sezione Calendario -->
<div class="dashboard">
    <h3 class="">Calendario</h3>
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
        <div class="">
            <?php include '../utils/stats.php'; ?>
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
<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".course-card.info p").forEach(function (element) {
        element.addEventListener("click", function () {
            this.classList.toggle("visible");
        });
    });
});
</script>

<script src="../assets/js/main.js"></script>
</body>
</html>
