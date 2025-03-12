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
        GROUP_CONCAT(DISTINCT CONCAT(c.name, ' (', c.period, ')') ORDER BY c.name SEPARATOR ', ') AS courses,
        GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') AS roles
    FROM users u
    JOIN user_role_courses urc ON u.id_user = urc.id_user
    JOIN courses c ON urc.id_course = c.id_course
    JOIN roles r ON urc.id_role = r.id_role
    WHERE urc.id_role = 3
    GROUP BY u.id_user, u.firstname, u.lastname, u.email, u.phone, u.active
";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

$admins = [];
while ($row = $result->fetch_assoc()) {
    $admins[] = $row;
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
    $stmt->close();
}
?>

<div class="users-container">
    <?php foreach ($admins as $admin): ?>
        <div class="user-card">
            <div class="user-card-header <?php echo $admin['active'] ? '' : 'inactive'; ?>">
                <?php echo htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']); ?>
                <span style="float:right; font-size:0.8rem;">
                    <?php echo $admin['active'] ? 'Attivo' : 'Inattivo'; ?>
                </span>
            </div>
            <div class="user-card-body">
                <p><strong>Email:</strong> <?php echo htmlspecialchars($admin['email']); ?></p>
                <p><strong>Telefono:</strong> <?php echo htmlspecialchars($admin['phone']); ?></p>
                <p><strong>Corsi:</strong> <?php echo htmlspecialchars($admin['courses']); ?></p>
                <p><strong>Ruolo:</strong> <?php echo htmlspecialchars($admin['roles']); ?></p>
            </div>
            <div class="user-card-actions">
                <?php if ($admin['active']): ?>
                    <a href="?action=deactivate&id_user=<?php echo $admin['id_user']; ?>" 
                       class="inactive" 
                       onclick="return confirmDeactivate(this);">
                       Disattiva
                    </a>
                <?php else: ?>
                    <a href="?action=activate&id_user=<?php echo $admin['id_user']; ?>" 
                       class="inactive" 
                       onclick="return confirmActivate(this);">
                       Attiva
                    </a>
                <?php endif; ?>
                <a href="?action=delete&id_user=<?php echo $admin['id_user']; ?>" 
                   class="delete"
                   onclick="return confirmDelete(this);">
                   Elimina
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
