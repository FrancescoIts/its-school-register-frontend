<?php
require_once '../utils/config.php';
require_once '../utils/check_session.php';

// Verifica se l'utente ha i permessi di accesso
$user = checkSession(true, ['admin', 'sadmin']);

// Recupera i corsi disponibili per questo admin/sadmin
$id_admin = $user['id_user'];
$query = "
    SELECT c.id_course, c.name 
    FROM courses c
    JOIN user_role_courses urc ON c.id_course = urc.id_course
    WHERE urc.id_user = ? AND (urc.id_role = 3 OR urc.id_role = 4)
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_admin);
$stmt->execute();
$result = $stmt->get_result();

$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[$row['id_course']] = $row['name'];
}
$stmt->close();

// Gestione inserimento
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $phone = $_POST['phone'];
    $role = (int)$_POST['role'];
    $course_id = (int)$_POST['course_id'];

    // Validazione email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@itssmartacademy.it')) {
        $message = '<div class="create-user-message error">Email non valida o dominio errato.</div>';
    } elseif (strlen($password) < 8) {
        $message = '<div class="create-user-message error">La password deve contenere almeno 8 caratteri.</div>';
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Inserimento dell'utente
        $insertUser = "
            INSERT INTO users (psw, lastname, firstname, phone, email, active, time_stamp)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ";
        $stmt = $conn->prepare($insertUser);
        $stmt->bind_param("sssss", $hashed_password, $lastname, $firstname, $phone, $email);

        if ($stmt->execute()) {
            $new_user_id = $stmt->insert_id;

            // Assegna ruolo e corso all'utente
            $insertRole = "
                INSERT INTO user_role_courses (id_user, id_role, id_course)
                VALUES (?, ?, ?)
            ";
            $stmtRole = $conn->prepare($insertRole);
            $stmtRole->bind_param("iii", $new_user_id, $role, $course_id);
            $stmtRole->execute();

            $message = '<div class="create-user-message success">Utente creato con successo!</div>';
        } else {
            $message = '<div class="create-user-message error">Errore durante la creazione dell\'utente.</div>';
        }

        $stmt->close();
    }
}
?>
<div class="create-user-container">
    <h2 class="create-user-title">Crea un Nuovo Utente</h2>
    <?php echo $message; ?>
    <form method="POST" action="" class="create-user-form">
    <div class="form-group">
        <label for="email">Email istituzionale:</label>
        <input type="email" name="email" required placeholder="esempio@itssmartacademy.it">
    </div>

    <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" name="password" required>
    </div>

    <div class="form-group">
        <label for="firstname">Nome:</label>
        <input type="text" name="firstname" required>
    </div>

    <div class="form-group">
        <label for="lastname">Cognome:</label>
        <input type="text" name="lastname" required>
    </div>

    <div class="form-group">
        <label for="phone">Numero di telefono:</label>
        <input type="text" name="phone" required>
    </div>

    <div class="form-group">
        <label for="role">Ruolo:</label>
        <select name="role" required>
            <option value="1">Studente</option>
            <option value="2">Docente</option>
        </select>
    </div>

    <div class="form-group">
        <label for="course_id">Corso:</label>
        <select name="course_id" required>
            <?php foreach ($availableCourses as $course): ?>
                <option value="<?php echo $course['id_course']; ?>">
                    <?php echo htmlspecialchars($course['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit" name="create_user" class="create-user-button">Crea Utente</button>
</form>
</div>

