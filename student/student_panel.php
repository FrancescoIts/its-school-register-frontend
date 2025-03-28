<?php
require_once '../utils/check_session.php'; 
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Studente</title>
    <link rel="stylesheet" href="../assets/css/navbar.css"> 
    <link rel="stylesheet" href="../assets/css/dark_mode.css"> 
    <link rel="stylesheet" href="../assets/css/student_panel.css"> 
    <link rel="stylesheet" href="../assets/css/dashboard_style.css"> 
    <link rel="stylesheet" href="../assets/css/calendar.css">
    <link rel="stylesheet" href="../assets/css/overflow.css">  
    <link rel="stylesheet" href="../assets/css/mini_loader.css">  
    <link rel="stylesheet" href="../assets/css/absences_admin.css">  
    <link rel="shortcut icon" href="../assets/img/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/manage_attendance.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assets/js/main.js" defer></script>
<script src="../assets/js/calendar.js" defer></script>
<script src="../assets/js/calendar_absences.js" defer></script>
<script src="../assets/js/student_panel.js" defer></script>
<script src="../assets/js/stats.js" defer></script>
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
            <a href="#lezioni" class="nav-item" data-label="Calendario">
                <i class="fas fa-calendar-alt"></i>
            </a>
            <a href="#stats" class="nav-item" data-label="Statistiche">
                <i class="fas fa-chart-bar"></i>
            </a>
            <a href="#abences" class="nav-item" data-label="Assenze">
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


<!-- Sezione Corso -->
<div class="dashboard">
    <h3>Corso</h3>
    <div class="courses">
    <?php
        require_once '../utils/getCourse.php';
    ?>
    </div>
</div>

<!-- Sezione Calendario con Eventi -->
<div class="dashboard" data-section="calendario_eventi" id="lezioni">
    <div class="dashboard-header">
        <h3>Calendario Eventi</h3>
        <span class="toggle-icon">&#9660;</span>
    </div>
    <div class="dashboard-content">
        <div class="courses">
            <div class="course-card">
                <?php require_once '../utils/calendar.php'; ?>
            </div>
        </div>
    </div>
</div>



<!-- Sezione Statistiche -->
<div class="dashboard"  data-section="stats" id="stats">    
    <div class="dashboard-header">
    <h3>Le tue statistiche</h3>
    <span class="toggle-icon">&#9660;</span>
    </div>
    <div class="dashboard-content">
    <div class="courses">
        <div class="course-card">
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

            <!-- Grafico Percentuale Assenze -->
            <div class="chart-container">
                <h3>Percentuale di Assenza</h3>
                <div class="chart-wrapper">
                    <canvas id="absenceChart"></canvas>
                </div>
                <div class="absence-percentage" id="percent"></div>
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
        </div>
    </div>
    </div>  
</div>
<!-- Sezione Calendario con Assenze -->
<div class="dashboard" data-section="absences" id="abences">
  <div class="dashboard-header">
      <h3>Calendario Assenze</h3>
      <span class="toggle-icon">&#9660;</span>
  </div>
  <div class="dashboard-content">
      <div class="courses">
          <div class="course-card" id="absences-container"> 
            <div class="loader loader-1">
            <div class="loader-outter"></div>
            <div class="loader-inner"></div>
            </div>
          </div>
      </div>
  </div>
</div>

<!-- Sezione Informazioni personali -->
<div class="dashboard" id="personal">
  <h3>Le tue informazioni</h3>
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
