<?php
session_start();
require_once './utils/config.php';

if (isset($_SESSION['user'])) {
    $userRoles = $_SESSION['user']['roles'] ?? [];

    if (in_array('admin', $userRoles)) {
        header("Location: ./admin/admin_panel.php");
        exit;
    } elseif (in_array('docente', $userRoles)) {
        header("Location: ./doc/doc_panel.php");
        exit;
    } elseif (in_array('studente', $userRoles)) {
        header("Location: ./student/student_panel.php");
        exit;
    } elseif (in_array('sadmin', $userRoles)) {
        header("Location: ./sadmin/sadmin_panel.php");
        exit;
    }
}
?>

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
        <link rel="stylesheet" href="./assets/css/dark_mode.css">
        <!-- FontAwesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        <link rel="shortcut icon" href="./assets/img/favicon.ico">  
    </head>
    <body>
        <style>
            .toggle {
                left: 1600px;
                top: -90px;
            }
        </style>
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <div class="header-image">
            <img src="./assets/img/logo.png" alt="Logo" id="rotateImage">
        </div>

        <input type="checkbox" class="sr-only" id="darkmode-toggle">
        <label for="darkmode-toggle" class="toggle">
        <span></span>
        </label>

        <div class="container">
            <div class="screen">
                <div class="screen__content">
                    <h1 class="login-title">Registro Elettronico</h1>

                    <form class="login" action="./login/process_login.php" method="POST">
                        <div class="login__field">
                            <i class="login__icon fas fa-user"></i>
                            <input type="text" name="email" class="login__input" placeholder="Email Scolastica" required>
                            <p class="error-message">
                                <?php echo $_SESSION['error_email'] ?? ''; ?>
                            </p>
                        </div>
                        <div class="login__field">
                            <i class="login__icon fas fa-lock"></i>
                            <input type="password" id="password" name="password" class="login__input" placeholder="Password" required>
                            <i class="pss fas fa-eye" id="togglePassword"></i>
                            <p class="error-message">
                                <?php echo $_SESSION['error_password'] ?? ''; ?>
                            </p>
                        </div>
                        <button type="submit" class="button login__submit">
                            <span>ACCEDI</span>
                            <i class="button__icon fas fa-chevron-right"></i>
                        </button>
                    </form>
                </div>
                <div class="screen__background">
                    <span class="screen__background__shape screen__background__shape4"></span>
                    <span class="screen__background__shape screen__background__shape3"></span>     
                    <span class="screen__background__shape screen__background__shape2"></span>
                    <span class="screen__background__shape screen__background__shape1"></span>
                </div>
            </div>
        </div>

        <script>
            // Hover icona 
            function toggleIcon(element, isHover) {
                let icon = element.querySelector("i");
                if (isHover) {
                    icon.classList.remove("fa-google");
                    icon.classList.add("fa-google");
                    icon.style.color = "#ffffff";
                } else {
                    icon.classList.remove("fa-google");
                    icon.classList.add("fa-google");
                    icon.style.color = "#7875b5";
                }
            }

            // Mostra/Nasconde la password
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
        </script>

        <?php
            // Se abbiamo errori da mostrare
            if (!empty($_SESSION['errors'])) {
                $errorMsg = json_encode(implode("<br>", $_SESSION['errors']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                echo "
                <script>
                    Swal.fire({
                        title: 'Errore',
                        html: $errorMsg,
                        icon: 'error',
                        timer: 1500,
                        showConfirmButton: false
                    });
                </script>";
                unset($_SESSION['errors']);
            }
        ?>

        <script src="./assets/js/main.js"></script>
        <?php require('./utils/footer.php'); ?>
    </body>
</html>
