<?php
session_start();
require_once 'config.php'; 


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Verifica che l'email contenga il dominio @itssmartacademy.it
    if (strpos($email, '@itssmartacademy.it') === false) {
        echo "Errore: l'indirizzo email deve appartenere al dominio @itssmartacademy.it.";
        exit;
    }
    $sql = "SELECT * FROM `user` WHERE `email` = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        //bcrypt
        if (password_verify($password, $user['psw'])) {
            if ($user['active'] == 1) {
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
                    LEFT JOIN course c ON c.id_course = urc.id_course
                    WHERE urc.id_user = ?
                ";
                $stmt2 = $conn->prepare($sqlRolesCourses);
                $stmt2->bind_param('i', $user['id_user']);
                $stmt2->execute();
                $rolesCoursesResult = $stmt2->get_result();

                $userRoles = [];
                $userCourses = [];

                while ($row = $rolesCoursesResult->fetch_assoc()) {
                    // Ruolo
                    if (!empty($row['role_name'])) {
                        $userRoles[] = [
                            'id_role' => $row['id_role'],
                            'name'    => $row['role_name']
                        ];
                    }
                    // Corso
                    if (!empty($row['course_name'])) {
                        $userCourses[] = [
                            'id_course' => $row['id_course'],
                            'name'      => $row['course_name'],
                            'year'      => $row['year'],
                            'period'    => $row['period']
                        ];
                    }
                }

                // Salvo i dati dell’utente in $_SESSION
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

                // Salvataggio sessione in DB (con scadenza 30 minuti)
                $session_id = session_id();
                $sqlSession = "
                    INSERT INTO sessions (id_user, session_id, data_creazione, data_scadenza)
                    VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE))
                ";
                $stmtSession = $conn->prepare($sqlSession);
                $stmtSession->bind_param('is', $user['id_user'], $session_id);
                $stmtSession->execute();

                $redirectPage = 'index.php'; 
               
                $roleNames = array_map(function($item) {
                    return strtolower($item['name']); 
                }, $userRoles);

                if (in_array('admin', $roleNames)) {
                    $redirectPage = '../admin/admin_panel.php';
                } elseif (in_array('docente', $roleNames)) {
                    $redirectPage = '../doc/doc_panel.php';
                } elseif (in_array('studente', $roleNames)) {
                    $redirectPage = '../student/student_panel.php';
                }

                // Reindirizza alla pagina corrispondente
                header("Location: $redirectPage");
                exit;

            } else {
                // Utente non attivo
                echo "Utente non attivo. Contatta l’amministratore.";
            }
        } else {
            echo "Email o password non validi.";
        }
    } else {
        echo "Email o password non validi.";
    }

} else {
    header("Location: index.php");
    exit;
}
