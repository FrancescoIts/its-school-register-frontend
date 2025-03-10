<?php
require_once '../utils/config.php';
require_once '../utils/check_session.php';

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

// Il codice si attiverà solo se la richiesta POST proviene dal form specifico
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user'])) {
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;
    $firstname = $_POST['firstname'] ?? null;
    $lastname = $_POST['lastname'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $role = isset($_POST['role']) ? (int)$_POST['role'] : null;
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : null;
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@itssmartacademy.it')) {
        $message = '<div class="create-user-message error">Email non valida o dominio errato.</div>';
    } elseif (!preg_match('/^(?=.*[0-9].*[0-9])(?=.*[!@#$%^&*()_+\-=\[\]{};\':\"\\|,.<>\/?]).{8,}$/', $password)) {
        $message = '<div class="create-user-message error">La password deve contenere almeno 8 caratteri, 2 numeri e 1 carattere speciale.</div>';
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $insertUser = "
            INSERT INTO users (psw, lastname, firstname, phone, email, active, time_stamp)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ";
        $stmt = $conn->prepare($insertUser);
        $stmt->bind_param("sssss", $hashed_password, $lastname, $firstname, $phone, $email);

        if ($stmt->execute()) {
            $new_user_id = $stmt->insert_id;
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

<div class="scrollable-table">
<div class="create-user-container">
    <?php echo $message; ?>
    <form method="POST" action="#createUser" class="create-user-form">
        <div class="form-group">
            <label for="email">Email istituzionale:</label>
            <input type="email" name="email" class="form-control" required placeholder="esempio@itssmartacademy.it">
        </div>
        <div class="form-group position-relative">
            <label for="password">Password:</label>
            <div class="input-group">
                <input type="password" id="password" name="password" class="form-control" required>
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="form-group">
            <button type="button" class="btn btn-info" id="generatePassword">Suggerisci Password Sicura</button>
        </div>
        <div class="form-group">
            <label for="firstname">Nome:</label>
            <input type="text" name="firstname" class="form-control" placeholder="Mario" required>
        </div>
        <div class="form-group">
            <label for="lastname">Cognome:</label>
            <input type="text" name="lastname" class="form-control" placeholder="Rossi" required>
        </div>
        <div class="form-group">
            <label for="phone">Numero di telefono:</label>
            <input type="text" name="phone" class="form-control" placeholder="+391234567890" required>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="role">Ruolo:</label>
                <select name="role" class="form-control" required>
                    <option value="1">Studente</option>
                    <option value="2">Docente</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="course_id">Corso:</label>
                <select name="course_id" class="form-control" required>
                    <?php foreach ($courses as $id => $name): ?>
                        <option value="<?php echo $id; ?>">
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" name="create_user" class="btn btn-info create-user-button">Crea Utente</button>
    </form>
</div>
</div>

