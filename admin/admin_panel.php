<?php
require_once '../utils/check_session.php'; 
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Coordinatore</title>
    <link rel="stylesheet" href="../assets/css/absences_admin.css"> 
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/admin_panel.css"> 
    <link rel="stylesheet" href="../assets/css/loader.css">
    <link rel="stylesheet" href="../assets/css/dark_mode.css"> 
    <link rel="stylesheet" href="../assets/css/dashboard_style.css"> 
    <link rel="stylesheet" href="../assets/css/calendar.css">
    <link rel="stylesheet" href="../assets/css/overflow.css">
    <link rel="stylesheet" href="../assets/css/stats_total.css">
    <link rel="stylesheet" href="../assets/css/courses_admin.css">
    <link rel="stylesheet" href="../assets/css/view_users.css">
    <link rel="stylesheet" href="../assets/css/create_user.css">
    <link rel="stylesheet" href="../assets/css/manage_attendance.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/checkbox.css">
    <link rel="shortcut icon" href="../assets/img/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js"></script>    
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
<script src="../assets/js/main.js" defer></script>
<script src="../assets/js/admin.js" defer></script>
<script src="../assets/js/calendar_admin.js" defer></script>
<script src="../assets/js/absences_admin.js" defer></script>
<script src="../assets/js/view_users.js" defer></script>
<script src="../assets/js/loader.js" defer></script>
<script src="../assets/js/attendance.js" defer></script>
<div class="scroll-progress" id="scrollProgress"></div>
<button
        type="button"
        class="btn btn-danger btn-floating btn-lg .go-up"
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
            <a href="#statistiche" class="nav-item" data-label="Statistiche">
                <i class="fas fa-chart-bar"></i>
            </a>
            <a href="#Panoramica" class="nav-item" data-label="Panoramica Assenze">
                <i class="fas fa-user-times"></i>
            </a>
            <a href="#courseSettings" class="nav-item" data-label="Impostazioni Corso">
                <i class="fas fa-cogs"></i>
            </a>
            <a href="#viewUsers" class="nav-item" data-label="Mostra Utenti">
                <i class="fas fa-users"></i>
            </a>
            <a href="#createUser" class="nav-item" data-label="Crea Utente">
                <i class="fas fa-user-plus"></i>
            </a>
            <a href="#abencesAdmin" class="nav-item" data-label="Calendario Assenze Studenti">
                <i class="fas fa-calendar-times"></i>
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
<div class="dashboard" id="calendarAdmin" data-section="events">
<div class="dashboard-header">
    <h3 class="">Calendario Eventi</h3>
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
<div class="dashboard" data-section="events" id="statistiche">
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

<!-- Sezione Impostazioni Corsi -->
<div class="dashboard" id="courseSettings" data-section="coursesSettings">
<div class="dashboard-header">
    <h3>Impostazioni Corsi</h3>
    <span class="toggle-icon">&#9660;</span>
    </div>
    <div class="dashboard-content">
    <div class="courses">
        <div class="course-card">
            <?php
                require_once './courses_admin.php';
            ?>
        </div>
        <div class="course-card">
                <?php require_once './create_module.php'; ?>
            </div>
        </div>
    </div>
</div>

<!-- Sezione Mostra Utenti -->
<div class="dashboard" id="viewUsers" data-section="showUsers">
<div class="dashboard-header">
    <h3>Mostra Utenti</h3>
    <span class="toggle-icon">&#9660;</span>
    </div>
    <div class="dashboard-content">
    <div class="courses">
        <div class="course-card">
            <?php
                require_once './view_users.php';
            ?>
        </div>
        </div>
    </div>
</div>

<!-- Sezione Crezione Utenti -->
<div class="dashboard" data-section="createUser" id="createUser">
<div class="dashboard-header">
    <h3>Crezione Utenti</h3>
    <span class="toggle-icon">&#9660;</span>
    </div>
    <div class="dashboard-content">
    <div class="courses">
        <div class="course-card">
            <?php
                require_once './create_user.php';
            ?>
        </div>
    </div>
</div>
</div>

<!-- Sezione Calendario Assenze Studenti -->
<div class="dashboard" id="abencesAdmin" data-section="studentsAbesences">
<div class="dashboard-header">
    <h3>Calendario Assenze Studenti</h3>
    <span class="toggle-icon">&#9660;</span>
    </div>
    <div class="dashboard-content">
    <div class="courses">
        <div class="course-card" id="calendar-absences">
        
            <?php
                require_once '../utils/absences_admin.php';
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
