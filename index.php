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
        <link rel="stylesheet" href="./assets/css/popup.css">
        <!-- FontAwesome per le icone -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        <link rel="shortcut icon" href="./assets/img/favicon.ico">  
    </head>
    <body>

        <!-- Overlay -->
        <div class="popup-overlay" id="popupOverlayError"></div>

        <!-- POPUP ERRORE -->
        <div class="popup" id="errorPopup">
            <div class="popup-header error">Errore</div>
            <div class="popup-content" id="popupContentError"></div>
            <button class="popup-close" onclick="closeErrorPopup()">Chiudi</button>
        </div>

        <!-- Overlay -->
        <div class="popup-overlay" id="popupOverlaySuccess"></div>

        <!-- POPUP SUCCESSO -->
        <div class="popup" id="successPopup">
            <div class="popup-header success">Successo</div>
            <div class="popup-content" id="popupContentSuccess"></div>
            <button class="popup-close success" onclick="closeSuccessPopup()">Chiudi</button>
        </div>


        <div class="header-image">
            <img src="./assets/img/logo.png" alt="Logo" id="rotateImage">
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
                            <input type="password" id="password" name="password" class="login__input" placeholder="Password" required>
                            <i class="pss fas fa-eye" id="togglePassword"></i>
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
    function showErrorPopup(errorMessage) {
        document.getElementById("popupContentError").innerHTML = errorMessage;
        document.getElementById("errorPopup").style.display = "block";
        document.getElementById("popupOverlayError").style.display = "block";
    }

    function closeErrorPopup() {
        document.getElementById("errorPopup").style.display = "none";
        document.getElementById("popupOverlayError").style.display = "none";
    }

    function showSuccessPopup(successMessage) {
        document.getElementById("popupContentSuccess").innerHTML = successMessage;
        document.getElementById("successPopup").style.display = "block";
        document.getElementById("popupOverlaySuccess").style.display = "block";

        setTimeout(function() {
            window.location.href = "<?php echo $_SESSION['redirect'] ?? 'index.php'; ?>";
        }, 500);
    }
    document.getElementById("togglePassword").addEventListener("click", function () {
        var passwordField = document.getElementById("password");
        if (passwordField.type === "password") {
            passwordField.type = "text";
            this.classList.remove("fa-eye");
            this.classList.add("fa-eye-slash");
        } else {
            passwordField.type = "password";
            this.classList.remove("fa-eye-slash");
            this.classList.add("fa-eye");
        }
        });
        <?php
    if (!empty($_SESSION['errors'])) {
        echo "showErrorPopup(`" . implode("<br>", $_SESSION['errors']) . "`);";
        unset($_SESSION['errors']);
    }

    if (!empty($_SESSION['success'])) {
        echo "showSuccessPopup(`" . implode("<br>", $_SESSION['success']) . "`);";
        unset($_SESSION['success']);
    }
    ?>
    </script>

        <script src="./assets/js/main.js"></script>
        <?php require('./utils/footer.php'); ?>
    </body>
</html>
