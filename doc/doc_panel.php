<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Docente</title>
    <link rel="stylesheet" href="../assets/css/doc_panel.css"> 
    <link rel="stylesheet" href="../assets/css/card_style.css">
    <link rel="stylesheet" href="../assets/css/overflow.css">  
    <link rel="shortcut icon" href="../assets/img/favicon.ico">

</head>
<body>
<script>
         document.addEventListener("scroll", function() {
      // Calcola lo scroll corrente
      const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
      const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
      const scrollPercent = (scrollTop / scrollHeight) * 100;

      // Aggiorna la larghezza della barra di progresso
      document.getElementById("scrollProgress").style.width = scrollPercent + "%";
    });
        document.addEventListener("DOMContentLoaded", function() {
            const elements = document.querySelectorAll(".animated-box");
            elements += document.querySelectorAll(".animated-box");
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("fade-in");
                    }
                });
            }, { threshold: 0.1 });

            elements.forEach(el => observer.observe(el));
        });

    document.addEventListener("DOMContentLoaded", function() {
        const elements = document.querySelectorAll(".dashboard");

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("fade-in");
                } else {
                    entry.target.classList.remove("fade-in");
                }
            });
        }, { threshold: 0.2 });

        elements.forEach(el => observer.observe(el));
    }); 
    document.addEventListener("DOMContentLoaded", function() {
        const elements = document.querySelectorAll(".animated-box");

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("fade-in");
                } else {
                    entry.target.classList.remove("fade-in");
                }
            });
        }, { threshold: 0.2 });

        elements.forEach(el => observer.observe(el));
    }); 
</script>
<div class="scroll-progress" id="scrollProgress"></div>
<div class="navbar">
    <h3 class="titleh3">Benvenuto, John Doe</h3>
    <div class="header-image">
        <img src="../assets/img/logo.png" alt="Logo" id="rotateImage">
    </div>
    <div>
        <a class="logout" href="../utils/logout.php">Logout</a>
        <button class="theme-toggle" id="theme-toggle">🌙</button>
    </div>
</div>

<div class="dashboard">
    <h3 class="animated-box">Corsi</h3>
    <div class="courses">
        <div class="course-card">I.C.T. System Developer</div>
        <img src="../assets/img/courses/" alt="Logo"> 
        <!-- IMMAGINE CHE VIENE PRESA IN BASE AI DATI DELLA SESSIONE -->
    </div>
</div>

<div class="dashboard">
    <h3 class="animated-box">Calendario</h3>
    <div class="courses">
        <div class="course-card animated-box">
            <?php require('../utils/calendar.php'); ?>
        </div>
    </div>
</div>

<div class="dashboard">
    <h3 class="animated-box">Dati studenti</h3>
    <div class="courses">
        <div class="course-card animated-box">Contenuto delle statistiche</div>
    </div>
</div>

<div class="dashboard">
    <h3 class="animated-box">Bacheca</h3>
    <div class="courses">
        <div class="course-card animated-box">Contenuto della bacheca</div>
    </div>
</div>

<div class="dashboard">
    <h3 class="animated-box">Le tue infos</h3>
    <div class="courses">
        <div class="course-card animated-box">informazioni personali</div>
    </div>
</div>  

<script src="../assets/js/main.js"></script>
</body>
</html>