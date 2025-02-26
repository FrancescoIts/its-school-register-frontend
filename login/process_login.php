<?php
session_start();
require_once '../utils/config.php';

$_SESSION['errors'] = [];
$_SESSION['success'] = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Controllo che l'email contenga '@itssmartacademy.it'
    if (strpos($email, '@itssmartacademy.it') === false) {
        $_SESSION['errors'][] = "L'email non è valida (deve contenere @itssmartacademy.it).";
        header("Location: ../index.php");
        exit;
    }

    // 1) Recupero l'utente dal DB
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['psw'])) {
        if ($user['active'] == 1) {
            
            // 2) Controllo se esiste già una sessione attiva per l'utente
            $sqlSession = "
                SELECT session_id FROM sessions
                WHERE id_user = ? AND data_scadenza > NOW()
                LIMIT 1";
            $stmt = $conn->prepare($sqlSession);
            $stmt->bind_param('i', $user['id_user']);
            $stmt->execute();
            $result = $stmt->get_result();
            $existingSession = $result->fetch_assoc();

            if ($existingSession) {
                // Se esiste una sessione attiva, usiamo il suo session_id
                session_id($existingSession['session_id']);
                session_start();
            } else {
                // Se non c'è una sessione attiva, rigeneriamo l'ID di sessione
                session_regenerate_id(true);
            }

            // 3) Prelevo dal DB ruoli e corsi associati a quest’utente
            $sqlRolesCourses = "
                SELECT 
                    urc.id_role,
                    r.name AS role_name,
                    urc.id_course,
                    c.name AS course_name,
                    c.year,
                    c.period
                FROM user_role_courses urc
                LEFT JOIN roles r ON r.id_role = urc.id_role
                LEFT JOIN courses c ON c.id_course = urc.id_course
                WHERE urc.id_user = ?";
            $stmt2 = $conn->prepare($sqlRolesCourses);
            $stmt2->bind_param('i', $user['id_user']);
            $stmt2->execute();
            $rolesCoursesResult = $stmt2->get_result();

            $userRoles = [];
            $userCourses = [];

            while ($row = $rolesCoursesResult->fetch_assoc()) {
                if (!empty($row['role_name'])) {
                    $userRoles[] = strtolower($row['role_name']);
                }
                if (!empty($row['course_name'])) {
                    $userCourses[] = [
                        'id_course' => $row['id_course'],
                        'name'      => $row['course_name'],
                        'year'      => $row['year'],
                        'period'    => $row['period']
                    ];
                }
            }

            // 4) Salvo le informazioni dell'utente in sessione
            $_SESSION['user'] = [
                'id_user'   => $user['id_user'],
                'lastname'  => $user['lastname'],
                'firstname' => $user['firstname'],
                'phone'     => $user['phone'],
                'email'     => $user['email'],
                'active'    => $user['active'],
                'roles'     => $userRoles,
                'courses'   => $userCourses
            ];

            // 5) Aggiorno o creo la sessione nel database
            $session_id = session_id();
            $sessionDataJson = json_encode($_SESSION['user']);
            $sqlSessionSave = "
                INSERT INTO sessions (id_user, session_id, data_creazione, data_scadenza, session_data)
                VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE), ?)
                ON DUPLICATE KEY UPDATE 
                    session_id = VALUES(session_id),
                    data_scadenza = VALUES(data_scadenza),
                    session_data  = VALUES(session_data)";
            $stmtSession = $conn->prepare($sqlSessionSave);
            $stmtSession->bind_param('iss', $user['id_user'], $session_id, $sessionDataJson);
            $stmtSession->execute();

            // 6) Determino la pagina di destinazione in base al ruolo
            $_SESSION['success'][] = "Login effettuato con successo!";

            if (in_array('admin', $userRoles)) {
                $_SESSION['redirect'] = "../registro/admin/admin_panel.php";
            } elseif (in_array('docente', $userRoles)) {
                $_SESSION['redirect'] = "../registro/doc/doc_panel.php";
            } elseif (in_array('studente', $userRoles)) {
                $_SESSION['redirect'] = "../registro/student/student_panel.php";
            } elseif (in_array('sadmin', $userRoles)) {
                $_SESSION['redirect'] = "../registro/sadmin/sadmin_panel.php";
            } else {
                $_SESSION['errors'][] = "Ruolo non valido. Contatta l'amministratore.";
               // header("Location: ../index.php");
                exit;
            }

            header("Location: ../index.php");
            exit;
        } else {
            $_SESSION['errors'][] = "L'account non è attivo.";
        }
    } else {
        $_SESSION['errors'][] = "Email o password errati.";
    }

    header("Location: ../index.php");
    exit;
} else {
    header("Location: ../index.php");
    exit;
}
