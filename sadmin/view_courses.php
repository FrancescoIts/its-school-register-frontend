<?php
require_once '../utils/config.php';
require_once '../utils/check_session.php';

$user = checkSession(true, ['sadmin']);

// Query per recuperare i corsi
$query = "
    SELECT 
        c.id_course, 
        c.name, 
        c.period,
        c.year,
        c.total_hour,
        COUNT(CASE WHEN urc.id_role = 3 THEN 1 END) AS admin_count,
        GROUP_CONCAT(DISTINCT CASE WHEN urc.id_role = 3 THEN CONCAT(u.firstname, ' ', u.lastname) END 
                      ORDER BY u.firstname SEPARATOR ', ') AS admin_names,
        COUNT(CASE WHEN urc.id_role = 2 THEN 1 END) AS teacher_count,
        GROUP_CONCAT(DISTINCT CASE WHEN urc.id_role = 2 THEN CONCAT(u.firstname, ' ', u.lastname) END 
                      ORDER BY u.firstname SEPARATOR ', ') AS teacher_names,
        COUNT(CASE WHEN urc.id_role = 1 THEN 1 END) AS student_count,
        GROUP_CONCAT(DISTINCT CASE WHEN urc.id_role = 1 THEN CONCAT(u.firstname, ' ', u.lastname) END 
                      ORDER BY u.firstname SEPARATOR ', ') AS student_names
    FROM courses c
    LEFT JOIN user_role_courses urc ON c.id_course = urc.id_course
    LEFT JOIN users u ON urc.id_user = u.id_user
    GROUP BY c.id_course, c.name, c.period, c.year, c.total_hour
    ORDER BY c.name, c.period
";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();
?>
<div class="container">
    <?php if (empty($courses)): ?>
        <p style="text-align:center;">Nessun corso trovato.</p>
    <?php else: ?>
        <div class="responsive-table">
            <div class="responsive-table__row responsive-table__head">
                <div class="responsive-table__head__title">Corso</div>
                <div class="responsive-table__head__title">Anno</div>
                <div class="responsive-table__head__title">Dettagli</div>
                <div class="responsive-table__head__title">Azioni</div>
            </div>
            <div class="responsive-table__body">
                <?php foreach ($courses as $course): 
                    // Prepara la stringa per i dettagli, includendo la durata del corso
                    $details = "";
                    if (!empty($course['admin_names'])) {
                        $details .= "<strong>Admin:</strong> " . htmlspecialchars($course['admin_names']) . "<br>";
                    }
                    if (!empty($course['teacher_names'])) {
                        $details .= "<strong>Docenti:</strong> " . htmlspecialchars($course['teacher_names']) . "<br>";
                    }
                    if (!empty($course['student_names'])) {
                        $details .= "<strong>Studenti:</strong> " . htmlspecialchars($course['student_names']) . "<br>";
                    }
                    if (!empty($course['total_hour'])) {
                        $details .= "<strong>Durata:</strong> " . htmlspecialchars($course['total_hour']) . " ore";
                    }
                    ?>
                    <div class="responsive-table__row">
                        <div class="responsive-table__body__text" data-title="Corso">
                            <?php echo htmlspecialchars($course['name'] . ' (' . $course['period'] . ')'); ?>
                        </div>
                        <div class="responsive-table__body__text" data-title="Anno">
                            <?php echo htmlspecialchars($course['year']); ?>
                        </div>
                        <div class="responsive-table__body__text" data-title="Dettagli">
                            <button class="detail-button" onclick="showDetails(<?php echo htmlspecialchars(json_encode($course)); ?>)"><?php echo $details ? 'Visualizza' : 'Nessun dettaglio'; ?></button>
                        </div>
                        <div class="responsive-table__body__text" data-title="Azioni">
                            <button class="delete-button" onclick="deleteCourse(<?php echo htmlspecialchars(json_encode($course)); ?>)">Elimina</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
