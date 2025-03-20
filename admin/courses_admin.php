<?php
require_once '../utils/config.php';
require_once '../utils/check_session.php';

$userData = checkSession(true, ['admin', 'sadmin']);
$userId = $userData['id_user'];

$alertMessage = '';
$alertType = '';

// Salvataggio degli orari
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];

    // Array per i giorni della settimana
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    $updateFields = [];

    foreach ($days as $day) {
        $start_time = $_POST["start_time_$day"] ?? null;
        $end_time = $_POST["end_time_$day"] ?? null;

        if ($start_time && $end_time) {
            $updateFields[] = "`start_time_$day` = '$start_time', `end_time_$day` = '$end_time'";
        }
    }

    if (!empty($updateFields)) {
        $sql = "UPDATE courses SET " . implode(', ', $updateFields) . " WHERE id_course = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $course_id);

        if ($stmt->execute()) {
            $alertMessage = "Orari aggiornati con successo!";
            $alertType = "success";
        } else {
            $alertMessage = "Errore durante l'aggiornamento: " . $conn->error;
            $alertType = "error";
        }

        $stmt->close();
    }
}

// Recupera i corsi associati all'utente con ruolo admin o sadmin
$sql = "SELECT c.id_course, c.name 
        FROM courses c
        JOIN user_role_courses urc ON c.id_course = urc.id_course
        WHERE urc.id_user = ? AND (urc.id_role = 3 OR urc.id_role = 4)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

$stmt->close();
?>

<?php if (empty($courses)): ?>
    <p>Non hai corsi associati.</p>
<?php else: ?>
        <form method="POST" action="#courseSettings">
        <h4>Modifica Orari Corso</h4>
            <label for="course_id"></label>
            <select name="course_id" id="course_id" onchange="this.form.submit()" required>
                <option value="">Seleziona un corso</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id_course']; ?>" <?php echo (isset($_POST['course_id']) && $_POST['course_id'] == $course['id_course']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
            // Precompila gli orari se è stato selezionato un corso
            $selectedCourse = null;
            if (isset($_POST['course_id'])) {
                $course_id = $_POST['course_id'];
                $sql = "SELECT * FROM courses WHERE id_course = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $course_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $selectedCourse = $result->fetch_assoc();
                $stmt->close();
            }
            ?>
            <?php if ($selectedCourse): ?>
                    <div class="justifycontent">
                        <table class="attendance-table">
                            <tr>
                                <th>Giorno</th>
                                <th>Ora di Inizio</th>
                                <th>Ora di Fine</th>
                            </tr>
                            <?php
                            $days = [
                                'monday'    => 'Lunedì',
                                'tuesday'   => 'Martedì',
                                'wednesday' => 'Mercoledì',
                                'thursday'  => 'Giovedì',
                                'friday'    => 'Venerdì'
                            ];
                            foreach ($days as $key => $dayName): ?>
                                <tr>
                                    <td><?php echo $dayName; ?></td>
                                    <td>
                                        <input type="time" name="start_time_<?php echo $key; ?>" value="<?php echo $selectedCourse['start_time_' . $key] ?? ''; ?>">
                                    </td>
                                    <td>
                                        <input type="time" name="end_time_<?php echo $key; ?>" value="<?php echo $selectedCourse['end_time_' . $key] ?? ''; ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        <br>
                        <button type="submit">Salva Orari</button>
                    </div>
            <?php endif; ?>
        </form>
<?php endif; ?>

<?php if (!empty($alertMessage)): ?>
    <script>
        Swal.fire({
            title: "<?php echo $alertMessage; ?>",
            icon: "<?php echo $alertType; ?>",
            confirmButtonText: "OK"
        });
    </script>
<?php endif; ?>
