<?php session_start(); ?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Registro Elettronico</title>
        <meta name="description" content="">
        <meta name="keywords" content="registro, its, smart academy, its smart academy">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="./assets/css/login.css">
        <link rel="shortcut icon" href="./assets/img/favicon.ico">
    </head>
    <body>

        <!-- ============= POPUP ERRORE ============= -->
        <div class="popup-overlay" id="popupOverlayError"></div>
        <div class="popup" id="errorPopup">
            <div class="popup-header" style="background-color: #c0392b; color: #fff;">Errore</div>
            <div class="popup-content" id="popupContentError" style="color: #c0392b;"></div>
            <button class="popup-close" onclick="closeErrorPopup()">Chiudi</button>
        </div>

        <!-- ============= POPUP SUCCESSO ============= -->
        <div class="popup-overlay" id="popupOverlaySuccess"></div>
        <div class="popup" id="successPopup">
            <div class="popup-header" style="background-color: #27ae60; color: #fff;">Successo</div>
            <div class="popup-content" id="popupContentSuccess" style="color: #27ae60;"></div>
            <button class="popup-close" onclick="closeSuccessPopup()">Chiudi</button>
        </div>

        <div class="header-image">
            <img src="./img/logo.png" alt="Logo" id="rotateImage">
        </div>

        <button id="theme-toggle" class="theme-toggle">🌙</button>

        <div class="container">
            <div class="screen">
                <div class="screen__content">
                    <h1 class="login-title">Registro Elettronico</h1>

                    <form class="login" action="./login/process_login.php" method="POST">
                        <div class="login__field">
                            <i class="login__icon fas fa-user"></i>
                            <input type="text" name="email" class="login__input" placeholder="Email Scolastica" required>
                            <p class="error-message"><?php echo $_SESSION['error_email'] ?? ''; ?></p>
                        </div>
                        <div class="login__field">
                            <i class="login__icon fas fa-lock"></i>
                            <input type="password" name="password" class="login__input" placeholder="Password" required>
                            <p class="error-message"><?php echo $_SESSION['error_password'] ?? ''; ?></p>
                        </div>
                        <button type="submit" class="button login__submit">
                            <span>ACCEDI</span>
                            <i class="button__icon fas fa-chevron-right"></i>
                        </button>
                    </form>

                    <div class="social-login">
                        <div class="col-md-3">
                            <a href="" class="login-with-google-btn">Google</a>
                        </div>
                    </div>
                </div>
                <div class="screen__background">
                    <span class="screen__background__shape screen__background__shape4"></span>
                    <span class="screen__background__shape screen__background__shape3"></span>     
                    <span class="screen__background__shape screen__background__shape2"></span>
                    <span class="screen__background__shape screen__background__shape1"></span>
                </div>
            </div>
        </div>

        <!-- Script per i pop-up -->
        <script>
            // Mostra pop-up di ERRORE
            function showErrorPopup(errorMessage) {
                document.getElementById("popupContentError").innerHTML = errorMessage;
                document.getElementById("errorPopup").style.display = "block";
                document.getElementById("popupOverlayError").style.display = "block";
            }

            // Chiude pop-up di ERRORE
            function closeErrorPopup() {
                document.getElementById("errorPopup").style.display = "none";
                document.getElementById("popupOverlayError").style.display = "none";
            }

            // Mostra pop-up di SUCCESSO
            function showSuccessPopup(successMessage) {
                document.getElementById("popupContentSuccess").innerHTML = successMessage;
                document.getElementById("successPopup").style.display = "block";
                document.getElementById("popupOverlaySuccess").style.display = "block";
            }

            // Chiude pop-up di SUCCESSO
            function closeSuccessPopup() {
                document.getElementById("successPopup").style.display = "none";
                document.getElementById("popupOverlaySuccess").style.display = "none";
            }

            // Se esistono errori in sessione, mostra il pop-up di errore
            <?php
            if (!empty($_SESSION['errors'])) {
                $errors = implode("<br>", $_SESSION['errors']);
                echo "showErrorPopup(`$errors`);";
                unset($_SESSION['errors']);
            }
            ?>

            // Se esistono messaggi di successo in sessione, mostra il pop-up di successo
            <?php
            if (!empty($_SESSION['success'])) {
                $successMsg = implode("<br>", $_SESSION['success']);
                echo "showSuccessPopup(`$successMsg`);";
                unset($_SESSION['success']);
            }
            ?>
        </script>

        <script src="./assets/js/main.js"></script>
        <?php require('./utils/footer.php'); ?>
    </body>
</html>
