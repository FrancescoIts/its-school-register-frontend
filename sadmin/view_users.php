<?php
require_once '../utils/config.php';
require_once '../utils/check_session.php';

$user = checkSession(true, ['sadmin']);

// Recupero tutti gli utenti con ruolo 'admin'
$query = "
    SELECT 
        u.id_user, 
        u.firstname, 
        u.lastname, 
        u.email, 
        u.phone, 
        u.active, 
        GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') AS courses
    FROM users u
    JOIN user_role_courses urc ON u.id_user = urc.id_user
    JOIN courses c ON urc.id_course = c.id_course
    WHERE urc.id_role = 3
    GROUP BY u.id_user, u.firstname, u.lastname, u.email, u.phone, u.active
";
$stmt = $conn->prepare($query);
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

    // Verifica che l'utente da modificare sia un admin
    $checkQuery = "SELECT id_user FROM user_role_courses WHERE id_user = ? AND id_role = 3";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        if ($_GET['action'] == 'deactivate') {
            $conn->query("UPDATE users SET active = 0 WHERE id_user = $id_user");
        } elseif ($_GET['action'] == 'activate') {
            $conn->query("UPDATE users SET active = 1 WHERE id_user = $id_user");
        } elseif ($_GET['action'] == 'delete') {
            $conn->query("DELETE FROM users WHERE id_user = $id_user");
        }
        echo "<script>window.location.href = 'sadmin_panel.php#viewUsers';</script>";
        exit;
    }
}
?>

<div class="view-users-table-container">
    <h3>Lista Utenti Admin</h3>
    <table class="view-users-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Cognome</th>
                <th>Email</th>
                <th>Telefono</th>
                <th>Corsi</th>
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
                    <td><?php echo htmlspecialchars($user['courses']); ?></td>
                    <td><?php echo $user['active'] ? 'Attivo' : 'Inattivo'; ?></td>
                    <td>
                        <div class="view-users-actions">
                            <?php if ($user['active']): ?>
                                <a href="?action=deactivate&id_user=<?php echo $user['id_user']; ?>" class="view-users-button inactive" onclick="return confirmDeactivate(this);">
                                    Disattiva
                                </a>
                            <?php else: ?>
                                <a href="?action=activate&id_user=<?php echo $user['id_user']; ?>" class="view-users-button active" onclick="return confirmActivate(this);">
                                    Attiva
                                </a>
                            <?php endif; ?>
                            <a href="?action=delete&id_user=<?php echo $user['id_user']; ?>" class="view-users-button delete" onclick="return confirmDelete(this);">
                                Elimina
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
