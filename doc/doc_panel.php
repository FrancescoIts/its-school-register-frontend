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
    <title>Dashboard Docente</title>
    <link rel="stylesheet" href="../assets/css/doc_panel.css"> 
    <link rel="stylesheet" href="../assets/css/dashboard_style.css"> 
    <link rel="stylesheet" href="../assets/css/calendar.css">
    <link rel="stylesheet" href="../assets/css/overflow.css">
    <link rel="stylesheet" href="../assets/css/stats_total.css">
    <link rel="stylesheet" href="../assets/css/manage_attendance.css">
    <link rel="stylesheet" href="../assets/css/checkbox.css">
    <link rel="shortcut icon" href="../assets/img/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/main.js" defer></script>
<script src="../assets/js/calendar_admin.js" defer></script>
<div class="scroll-progress" id="scrollProgress"></div>
<button
        type="button"
        class="btn btn-danger btn-floating btn-lg"
        id="btn-back-to-top"
        >
  <i class="fas fa-arrow-up"></i>
</button>
<!-- Navbar -->
<div class="navbar">
    <div class="logo">
        <a href="https://www.itssmart.it/">
            <img src="../assets/img/logo.png" alt="Logo" id="rotateImage">
        </a>
    </div>
    <div class="navbar-title">

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
<div class="dashboard"  id="calendarAdmin" data-section="calendarAdmin">
<div class="dashboard-header">
    <h3 class="">Calendario Eventi (Lezioni)</h3>
    <span class="toggle-icon">&#9660;</span>
    </div>
    <div class="dashboard-content">
    <div class="courses">
        <div class="course-card">
            <?php
                require_once '../utils/calendar_admin.php';
            ?>
        </div>
        </div>
    </div>
</div>

<!-- Sezione Presenze -->
<div class="dashboard" id="attendanceAdmin">
    <h3>Lezione di oggi</h3>
    <div class="courses">
        <div class="course-card">
        <?php
            require_once '../utils/manage_attendance.php';
         ?>
        </div>
    </div>
</div>

<!-- Sezione Presenze -->
<div class="dashboard" id="Panoramica">
    <h3>Panoramica Presenze/Assenze</h3>
    <div class="courses">
        <div class="course-card">
        <?php
            require_once '../utils/modify_attendance.php';
         ?>
        </div>
    </div>
</div>

<!-- Sezione Statistiche -->
<div class="dashboard" data-section="statsStudents">
<div class="dashboard-header">
    <h3>Statistiche Studenti</h3>
    <span class="toggle-icon">&#9660;</span>
    </div>
    <div class="dashboard-content">
    <div class="courses">
        <div class="course-card">
            <?php 
                require_once '../utils/stats_total.php'; 
            ?>
        </div>
        </div>
    </div>
</div>

<!-- Sezione Bacheca -->
<div class="dashboard" data-section="bacheca">
<div class="dashboard-header">
    <h3 class="">Bacheca</h3>
    <span class="toggle-icon">&#9660;</span>
    </div>
    <div class="dashboard-content">
    <div class="courses">
        <div class="course-card">Contenuto della bacheca</div>
    </div>
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
