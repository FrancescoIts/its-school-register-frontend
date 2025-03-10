<?php
ob_start();
require_once '../utils/config.php';
require_once '../utils/check_session.php';

$user = checkSession(true, ['admin', 'sadmin']);
$id_admin = $user['id_user'];

// Recupero utenti dei corsi assegnati all'admin/sadmin evitando duplicati e rimuovendo l'utente loggato
$query = "
    SELECT u.id_user, u.firstname, u.lastname, u.email, u.phone, u.active, 
           COALESCE(GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', '), 'Nessun corso') AS courses
    FROM users u
    JOIN user_role_courses urc ON u.id_user = urc.id_user
    JOIN courses c ON urc.id_course = c.id_course
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

// Imposto l'header per il ritorno HTML
header('Content-Type: text/html; charset=utf-8');

// Genero il markup per le card
foreach ($users as $user) {
    ?>
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
    <?php
}

ob_end_flush();
