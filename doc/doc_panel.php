<?php
require_once '../utils/check_session.php'; 
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Docente</title>
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/doc_panel.css"> 
    <link rel="stylesheet" href="../assets/css/dashboard_style.css"> 
    <link rel="stylesheet" href="../assets/css/calendar.css">
    <link rel="stylesheet" href="../assets/css/dark_mode.css">
    <link rel="stylesheet" href="../assets/css/overflow.css">
    <link rel="stylesheet" href="../assets/css/stats_total.css">
    <link rel="stylesheet" href="../assets/css/manage_attendance.css">
    <link rel="stylesheet" href="../assets/css/checkbox.css">
    <link rel="stylesheet" href="../assets/css/loader.css">
    <link rel="shortcut icon" href="../assets/img/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assets/js/main.js" defer></script>
<script src="../assets/js/calendar_admin.js" defer></script>
<script src="../assets/js/loader.js" defer></script>
<div class="scroll-progress" id="scrollProgress"></div>
<button
        type="button"
        class="btn btn-danger btn-floating btn-lg"
        id="btn-back-to-top"
        >
  <i class="fas fa-arrow-up"></i>
</button>
<!-- Navbar -->
<div class="navbar" id="sticky">
    <div class="logo">
        <a href="https://www.itssmart.it/">
            <img src="../assets/img/logo.png" alt="Logo" id="rotateImage">
        </a>
    </div>
        <div class="navbar-links">
            <a href="#calendarAdmin" class="nav-item" data-label="Calendario">
                <i class="fas fa-calendar-alt"></i>
            </a>
            <a href="#attendanceAdmin" class="nav-item" data-label="Lezione di Oggi">
                <i class="fas fa-chalkboard-teacher"></i>
            </a>
            <a href="#Panoramica" class="nav-item" data-label="Panoramica Assenze">
                <i class="fas fa-user-times"></i>
            </a>
            <a href="#statistiche" class="nav-item" data-label="Statistiche">
                <i class="fas fa-chart-bar"></i>
            </a>
            <a href="#personal" class="nav-item" data-label="Informazioni Personali">
            <i class="fas fa-info"></i>
            </a>
        </div>

    <div class="navbar-actions">
    <input type="checkbox" class="sr-only" id="darkmode-toggle">
    <label for="darkmode-toggle" class="toggle">
    <span></span>
    </label>
    <a class="logout" href="../utils/logout.php"><span>Logout</span></a>
    </div>
</div>
<div class="navbar-placeholder"></div>
<!-- Sezione Corsi -->
<div class="dashboard">
    <h3 class="">Corsi</h3>
    <div class="courses">
    <?php
        require_once '../utils/getCourse.php';
    ?>
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
<div class="dashboard" data-section="statsStudents" id="statistiche">
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

<!-- Sezione Informazioni personali -->
<div class="dashboard" id="personal">
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

<?php require('../utils/loader.php'); ?>
</body>
</html>
