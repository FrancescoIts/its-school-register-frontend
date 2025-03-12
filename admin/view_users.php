<?php
require_once '../utils/config.php';
require_once '../utils/check_session.php';

$user = checkSession(true, ['admin', 'sadmin']);
$id_admin = $user['id_user'];

/* --- Recupero dinamico dei corsi disponibili --- */
$queryCourses = "
    SELECT c.id_course, c.name 
    FROM courses c
    JOIN user_role_courses urc ON c.id_course = urc.id_course
    WHERE urc.id_user = ? AND (urc.id_role = 3 OR urc.id_role = 4)
";
$stmt = $conn->prepare($queryCourses);
$stmt->bind_param("i", $id_admin);
$stmt->execute();
$result = $stmt->get_result();
$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[$row['id_course']] = $row['name'];
}
$stmt->close();

/* --- Recupero dinamico dei ruoli disponibili (solo ruoli 3 e 4) --- */
$queryRoles = "SELECT id_role, name FROM roles WHERE id_role IN (3,4)";
$stmt = $conn->prepare($queryRoles);
$stmt->execute();
$result = $stmt->get_result();
$roles = [];
while ($row = $result->fetch_assoc()) {
    $roles[$row['id_role']] = $row['name'];
}
$stmt->close();

/* --- Gestione delle modifiche tramite GET --- */
if (isset($_GET['action']) && isset($_GET['id_user'])) {
    $id_user = (int)$_GET['id_user'];
    if ($_GET['action'] == 'deactivate') {
        $conn->query("UPDATE users SET active = 0 WHERE id_user = $id_user");
    } elseif ($_GET['action'] == 'activate') {
        $conn->query("UPDATE users SET active = 1 WHERE id_user = $id_user");
    } elseif ($_GET['action'] == 'delete') {
        $conn->query("DELETE FROM users WHERE id_user = $id_user");
    }  
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'action' => $_GET['action'], 'id_user' => $id_user]);
        exit;
    } else {
        header("Location: admin_panel.php#viewUsers");
        exit;
    }
}

/* --- Recupero utenti dei corsi assegnati all'admin/sadmin --- */
$query = "
    SELECT u.id_user, u.firstname, u.lastname, u.email, u.phone, u.active, 
           COALESCE(GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', '), 'Nessun corso') AS courses,
           COALESCE(GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', '), 'Nessun ruolo') AS roles
    FROM users u
    JOIN user_role_courses urc ON u.id_user = urc.id_user
    JOIN courses c ON urc.id_course = c.id_course
    JOIN roles r ON urc.id_role = r.id_role
    WHERE urc.id_course IN (
        SELECT id_course FROM user_role_courses WHERE id_user = ? AND (id_role = 3 OR id_role = 4)
    )
    AND u.id_user != ?
    GROUP BY u.id_user
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $id_admin, $id_admin);
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
?>
<script>
    var courses = <?php echo json_encode($courses); ?>;
    var roles = <?php echo json_encode($roles); ?>;
</script>
<!-- Pulsante per aggiornare le schede -->
<div style="text-align: center; margin-bottom: 20px;">
    <button onclick="refreshUsers()" class="refresh-button">Aggiorna Schede</button>
</div>

<div class="users-container">
    <?php foreach ($users as $user): ?>
        <div class="user-card">
            <div class="user-card-header <?php echo $user['active'] ? '' : 'inactive'; ?>">
                <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                <span style="float:right; font-size:0.8rem;">
                    <?php echo $user['active'] ? 'Attivo' : 'Inattivo'; ?>
                </span>
            </div>
            <div class="user-card-body">
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Telefono:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                <p><strong>Corsi:</strong> <?php echo htmlspecialchars($user['courses'] ?? 'Nessun corso'); ?></p>
                <p><strong>Ruolo:</strong> <?php echo htmlspecialchars($user['roles'] ?? 'Nessun ruolo'); ?></p>
            </div>
            <div class="user-card-actions">
                <?php if ($user['active']): ?>
                    <a href="endpoint_users.php?action=deactivate&id_user=<?php echo $user['id_user']; ?>" 
                       class="inactive" 
                       onclick="return confirmDeactivate(this);">
                       Disattiva
                    </a>
                <?php else: ?>
                    <a href="endpoint_users.php?action=activate&id_user=<?php echo $user['id_user']; ?>" 
                       class="inactive" 
                       onclick="return confirmActivate(this);">
                       Attiva
                    </a>
                <?php endif; ?>
                <a href="endpoint_users.php?action=delete&id_user=<?php echo $user['id_user']; ?>" 
                   class="delete"
                   onclick="return confirmDelete(this);">
                   Elimina
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
