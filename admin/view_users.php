<?php
require_once '../utils/config.php';
require_once '../utils/check_session.php';

$user = checkSession(true, ['admin', 'sadmin']);
$id_admin = $user['id_user'];

// Recupero utenti dei corsi assegnati all'admin/sadmin
$query = "
    SELECT u.id_user, u.firstname, u.lastname, u.email, u.phone, u.active, c.name AS course_name
    FROM users u
    JOIN user_role_courses urc ON u.id_user = urc.id_user
    JOIN courses c ON urc.id_course = c.id_course
    WHERE urc.id_course IN (
        SELECT id_course FROM user_role_courses WHERE id_user = ? AND (id_role = 3 OR id_role = 4)
    )
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_admin);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

// Gestione attivazione/disattivazione ed eliminazione
if (isset($_GET['action']) && isset($_GET['id_user'])) {
    $id_user = (int)$_GET['id_user'];
    if ($_GET['action'] == 'deactivate') {
        $conn->query("UPDATE users SET active = 0 WHERE id_user = $id_user");
    } elseif ($_GET['action'] == 'activate') {
        $conn->query("UPDATE users SET active = 1 WHERE id_user = $id_user");
    } elseif ($_GET['action'] == 'delete') {
        $conn->query("DELETE FROM users WHERE id_user = $id_user");
    }
    //header("Location: ./view_users.php/");
    echo "<script>window.location.href = 'admin_panel.php#viewUsers';</script>";    

    exit;
}

?>
<div class="view-users-table-container">
    <table class="view-users-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Cognome</th>
                <th>Email</th>
                <th>Telefono</th>
                <th>Corso</th>
                <th>Stato</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id_user']; ?></td>
                    <td><?php echo htmlspecialchars($user['firstname']); ?></td>
                    <td><?php echo htmlspecialchars($user['lastname']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                    <td><?php echo htmlspecialchars($user['course_name']); ?></td>
                    <td><?php echo $user['active'] ? 'Attivo' : 'Inattivo'; ?></td>
                    <td>
                    <div class="view-users-actions">
                        <?php if ($user['active']): ?>
                            <a href="?action=deactivate&id_user=<?php echo $user['id_user']; ?>" class="view-users-button inactive">Disattiva</a>
                        <?php else: ?>
                            <a href="?action=activate&id_user=<?php echo $user['id_user']; ?>" class="view-users-button inactive">Attiva</a>
                        <?php endif; ?>
                        <a href="?action=delete&id_user=<?php echo $user['id_user']; ?>" 
                        class="view-users-button delete"
                        onclick="return confirmDelete(this);">Elimina</a>
                    </div>
                </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
