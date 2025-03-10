<?php

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
             || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
// Costruisce l'URL di base che punta alla cartella "registro" nella radice del dominio
$baseUrl = $protocol . $_SERVER['HTTP_HOST'] . '/registro';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403</title>
    
    <!-- Percorsi assoluti per i file CSS -->
    <link rel="stylesheet" href="http://localhost/registro/assets/css/student_panel.css"> 
    <link rel="stylesheet" href="http://localhost/registro/assets/css/dashboard_style.css"> 
    <link rel="stylesheet" href="http://localhost/registro/assets/css/overflow.css">  
    <link rel="stylesheet" href="http://localhost/registro/assets/css/errors.css"> 

    <!-- Favicon -->
    <link rel="shortcut icon" href="http://localhost/registro/assets/img/favicon.ico">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>

<!-- Script con percorsi assoluti -->
<script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="http://localhost/registro/assets/js/main.js" defer></script>
<br><br>
<br>
<br><br>
<br>
<br><br>
<br>
<br>
<!-- Sezione Errore 404 -->
<div class="dashboard">
    <h3>403 Non dovresti essere qui!</h3>
    <div class="courses">
        <div class="course-card">
            Non hai accesso a questo luogo...<br><br>
            <a href="<?php echo $baseUrl; ?>/index.php" class="back-button">⬅ Torna Indietro</a>
        </div>
    </div>
</div>

</body>
</html>
