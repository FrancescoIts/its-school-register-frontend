<?php
// create_module.php
require_once '../utils/config.php';
require_once '../utils/check_session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

$user = checkSession(true, ['docente', 'admin', 'sadmin']);
if (!in_array(strtolower($user['roles'][0]), ['docente', 'admin', 'sadmin'])) {
    echo "<p>Non hai i permessi per accedere a questa pagina.</p>";
    exit;
}

// Recupera i corsi disponibili per l'utente
$corsiDisponibili = [];
$stmt = $conn->prepare("
    SELECT c.id_course, c.name 
    FROM courses c 
    JOIN user_role_courses urc ON c.id_course = urc.id_course 
    WHERE urc.id_user = ? 
    ORDER BY c.name
");
$stmt->bind_param("i", $user['id_user']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $corsiDisponibili[] = $row;
}
$stmt->close();

// Se non ci sono corsi disponibili, esce
if (count($corsiDisponibili) == 0) {
    echo "<p>Nessun corso disponibile per la gestione dei moduli.</p>";
    exit;
}

// Gestione eliminazione modulo (se inviato via POST)
if (isset($_POST['delete_module'])) {
    $id_module = (int)$_POST['delete_module'];
    $current_course = isset($_POST['current_course']) ? (int)$_POST['current_course'] : 0;
    $sqlDelete = "DELETE FROM modules WHERE id_module = ? AND id_course = ?";
    $stmtDel = $conn->prepare($sqlDelete);
    $stmtDel->bind_param("ii", $id_module, $current_course);
    $stmtDel->execute();
    $stmtDel->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?course=" . $current_course . "#courseSettings");
    exit;
}

// Gestione tasto "Indietro" per cambiare corso
if (isset($_POST['back_course'])) {
    // Forziamo il reset del corso selezionato
    $id_course = 0;
}

// Se il form per selezionare il corso è stato inviato o se è passato via GET, usiamo quel corso; altrimenti se c'è un solo corso lo usiamo automaticamente.
$id_course = 0;
if (isset($_POST['selected_course'])) {
    $id_course = (int)$_POST['selected_course'];
} elseif (isset($_GET['course'])) {
    $id_course = (int)$_GET['course'];
} elseif (count($corsiDisponibili) == 1) {
    $id_course = $corsiDisponibili[0]['id_course'];
}
?>

<div class="form-container">
    <?php if ($id_course <= 0): ?>
        <h4>Creazione/Visualizzazione moduli</h4>
        <form method="post" action="">
            <label for="selected_course">Corso:</label>
            <select name="selected_course" id="selected_course" required>
                <option value="">Seleziona un corso</option>
                <?php foreach ($corsiDisponibili as $corso): ?>
                    <option value="<?php echo $corso['id_course']; ?>">
                        <?php echo htmlspecialchars($corso['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Seleziona</button>
        </form>
    <?php endif; ?>
</div>

<?php
// Se è stato selezionato un corso, proseguiamo
if ($id_course > 0):
    // Form per la creazione del modulo e visualizzazione dei moduli già creati
    $message = "";
    if (isset($_POST['create_module'])) {
        $module_name = trim($_POST['module_name'] ?? '');
        $module_duration = floatval($_POST['module_duration'] ?? 0);
        if ($module_name === "" || $module_duration <= 0) {
            $message = "Inserisci un nome valido e una durata maggiore di 0 ore.";
        } else {
            $sqlInsert = "INSERT INTO modules (id_course, module_name, module_duration) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sqlInsert);
            $stmt->bind_param("isd", $id_course, $module_name, $module_duration);
            if ($stmt->execute()) {
                $message = "Modulo creato con successo.";
            } else {
                $message = "Errore durante la creazione del modulo.";
            }
            $stmt->close();
        }
    }

    // Recupera i moduli esistenti per il corso selezionato
    $modules = [];
    $sqlSelect = "SELECT id_module, module_name, module_duration FROM modules WHERE id_course = ? ORDER BY module_name";
    $stmt = $conn->prepare($sqlSelect);
    $stmt->bind_param("i", $id_course);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $modules[] = $row;
    }
    $stmt->close();

    // Per ogni modulo, calcoliamo lo stato di completamento.
    // Presupponiamo che nella tabella module_attendance siano registrate le ore accumulate per modulo.
    foreach ($modules as &$mod) {
        $sqlProgress = "SELECT SUM(hours_accumulated) AS total_hours FROM module_attendance WHERE id_course = ? AND id_module = ?";
        $stmtP = $conn->prepare($sqlProgress);
        $stmtP->bind_param("ii", $id_course, $mod['id_module']);
        $stmtP->execute();
        $resP = $stmtP->get_result();
        $rowP = $resP->fetch_assoc();
        $total_hours = $rowP['total_hours'] ? floatval($rowP['total_hours']) : 0;
        $stmtP->close();
        $mod['progress'] = ($mod['module_duration'] > 0) ? min(100, ($total_hours / $mod['module_duration']) * 100) : 0;
    }
    unset($mod);
    ?>



    <div class="form-container" id="courseSettings">
        <h2>Crea Nuovo Modulo</h2>
        <?php if ($message !== ""): ?>
            <p><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <!-- Il form usa l'ancora #courseSettings per rimanere in quella sezione -->
        <form method="post" action="#courseSettings">
            <input class="create-user-input" type="hidden" name="selected_course" value="<?php echo $id_course; ?>" />
            <label class="create-user-label" for="module_name">Nome Modulo:</label>
            <input class="create-user-input" type="text" name="module_name" id="module_name" required />
            <label class="create-user-label" for="module_duration">Durata prevista (ore):</label>
            <input class="create-user-input" type="number" name="module_duration" id="module_duration" step="0.1" min="0.1" required />
            <button type="submit" name="create_module">Crea Modulo</button>
        </form>
    </div>
    <br>    <!-- Tasto per tornare alla selezione del corso -->
    <div class="form-container">
        <form method="post" action="">
            <button class="delete-module" type="submit" name="back_course">Indietro</button>
        </form>
    </div><br>
    <div class="form-container">
        <h2>Moduli Esistenti</h2>
        <?php if (empty($modules)): ?>
            <p>Nessun modulo creato per questo corso.</p>
        <?php else: ?>
            <table class="responsive-table modules-table">
                <thead class="responsive-table__head">
                    <tr class="responsive-table__row">
                        <th class="responsive-table__head__title">ID</th>
                        <th class="responsive-table__head__title">Nome Modulo</th>
                        <th class="responsive-table__head__title">Durata prevista (ore)</th>
                        <th class="responsive-table__head__title">Completamento</th>
                        <th class="responsive-table__head__title">Azioni</th>
                    </tr>
                </thead>
                <tbody class="responsive-table__body">
                    <?php foreach ($modules as $mod): ?>
                        <tr class="responsive-table__row">
                            <td class="responsive-table__body__text"><?php echo $mod['id_module']; ?></td>
                            <td class="responsive-table__body__text"><?php echo htmlspecialchars($mod['module_name']); ?></td>
                            <td class="responsive-table__body__text"><?php echo number_format($mod['module_duration'], 2, ',', ''); ?></td>
                            <td class="responsive-table__body__text">
                            <?php if ($mod['progress'] < 100): 
                                if ($mod['progress'] < 50) {
                                    $color = "#ff0000"; // rosso
                                } else {
                                    $color = "#ffff00"; // giallo
                                }
                            ?>
                                    <div style="position: relative; background: #eee; border-radius: 5px; overflow: hidden; height: 20px; width: 100%; color: #333;">
                                        <div style="width: <?php echo $mod['progress']; ?>%; height: 100%; background: <?php echo $color; ?>;"></div>
                                        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; text-align: center; line-height: 20px; font-size: 0.9em;">
                                            <?php echo number_format($mod['progress'], 2, ',', ''); ?>%
                                        </div>
                                    </div>
                                    <?php else: ?>
                                        <i class="fas fa-check-circle" style="color: green; font-size: 1.5em;"></i>
                                    <?php endif; ?>
                                </td>

                            <td class="responsive-table__body__text">
                                <form method="post" style="display:inline;" onsubmit="return confirm('Sei sicuro di voler eliminare questo modulo?');">
                                    <input type="hidden" name="current_course" value="<?php echo $id_course; ?>" />
                                    <button class="delete-module" type="submit" name="delete_module" value="<?php echo $mod['id_module']; ?>">Elimina</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php endif; ?>