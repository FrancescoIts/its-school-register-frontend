<?php
require_once '../utils/check_session.php'; 
?>


<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Super Admin</title>
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/admin_panel.css"> 
    <link rel="stylesheet" href="../assets/css/loader.css">
    <link rel="stylesheet" href="../assets/css/dark_mode.css"> 
    <link rel="stylesheet" href="../assets/css/view_courses.css">
    <link rel="stylesheet" href="../assets/css/dashboard_style.css"> 
    <link rel="stylesheet" href="../assets/css/overflow.css">
    <link rel="stylesheet" href="../assets/css/view_users.css">
    <link rel="stylesheet" href="../assets/css/create_user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="shortcut icon" href="../assets/img/favicon.ico">
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js"></script>    
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.4.8/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/main.js" defer></script>
<script src="../assets/js/admin.js" defer></script>
<script src="../assets/js/loader.js" defer></script>
<script src="../assets/js/create_course.js" defer></script>
<script src="../assets/js/view_courses.js" defer></script>
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
            <a href="#viewUsers" class="nav-item" data-label="Mostra Utenti">
                <i class="fas fa-users"></i>
            </a>
            <a href="#createUser" class="nav-item" data-label="Crea Utente">
                <i class="fas fa-user-plus"></i>
            </a>
            <a href="#viewCourses" class="nav-item" data-label="Visualizza Corsi">
                <i class="fa fa-list"></i>
            </a>
            <a href="#createCourse" class="nav-item" data-label="Crea Corso">
                <i class="fas fa-cogs"></i>
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
    <h3>Crezione Admin di Corso</h3>
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

<!-- Sezione Crezione Utenti -->
<div class="dashboard" data-section="viewCourses" id="viewCourses">
<div class="dashboard-header">
    <h3>Visualizza Corsi</h3>
    <span class="toggle-icon">&#9660;</span>
    </div>
    <div class="dashboard-content">
    <div class="courses">
        <div class="course-card">
            <?php
                require_once './view_courses.php';
            ?>
        </div>
    </div>
</div>
</div>

<!-- Sezione Crezione Utenti -->
<div class="dashboard" data-section="createCourse" id="createCourse">
<div class="dashboard-header">
    <h3>Crezione Corso</h3>
    <span class="toggle-icon">&#9660;</span>
    </div>
    <div class="dashboard-content">
    <div class="courses">
        <div class="course-card">
            <?php
                require_once './create_course.php';
            ?>
        </div>
    </div>
</div>
</div>

<?php require('../utils/loader.php'); ?>
</body>
</html>
