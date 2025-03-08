<?php
require_once '../utils/config.php';
require_once '../utils/check_session.php';

// Verifica se l'utente ha i permessi di accesso (solo sadmin può accedere)
$user = checkSession(true, ['sadmin']);

// Recupera tutti i corsi esistenti (il sadmin può assegnare admin a qualsiasi corso)
$query = "SELECT id_course, name FROM courses";
$stmt = $conn->prepare($query);
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
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;
    $firstname = $_POST['firstname'] ?? null;
    $lastname = $_POST['lastname'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : null;
    
    // Controllo validità email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@itssmartacademy.it')) {
        $message = '<div class="create-user-message error">Email non valida o dominio errato.</div>';
    } 
    // Controllo validità password
    elseif (!preg_match('/^(?=.*[0-9].*[0-9])(?=.*[!@#$%^&*()_+\-=\[\]{};\':\"\\|,.<>\/?]).{8,}$/', $password)) {
        $message = '<div class="create-user-message error">La password deve contenere almeno 8 caratteri, 2 numeri e 1 carattere speciale.</div>';
    } 
    else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Inserimento utente
        $insertUser = "
            INSERT INTO users (psw, lastname, firstname, phone, email, active, time_stamp)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ";
        $stmt = $conn->prepare($insertUser);
        $stmt->bind_param("sssss", $hashed_password, $lastname, $firstname, $phone, $email);

        if ($stmt->execute()) {
            $new_user_id = $stmt->insert_id;

            // Inserimento ruolo (admin) e corso
            $role = 3; // L'utente può solo essere admin (id_role = 3)
            $insertRole = "
                INSERT INTO user_role_courses (id_user, id_role, id_course)
                VALUES (?, ?, ?)
            ";
            $stmtRole = $conn->prepare($insertRole);
            $stmtRole->bind_param("iii", $new_user_id, $role, $course_id);
            $stmtRole->execute();

            $message = '<div class="create-user-message success">Utente admin creato con successo!</div>';
        } else {
            $message = '<div class="create-user-message error">Errore durante la creazione dell\'utente.</div>';
        }
        $stmt->close();
    }
}
?>

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

<div class="create-user-container">
    <?php echo $message; ?>
    <form method="POST" action="" class="create-user-form">
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
        <div class="form-group">
            <label for="course_id">Assegna a un Corso:</label>
            <select name="course_id" class="form-control" required>
                <?php foreach ($courses as $id => $name): ?>
                    <option value="<?php echo $id; ?>">
                        <?php echo htmlspecialchars($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" name="create_user" class="btn btn-primary btn-block" id="createButton">Crea Admin</button>
    </form>
</div>