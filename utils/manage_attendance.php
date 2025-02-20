<?php
// manage_attendance.php

// 1) Richiamiamo config.php e check_session.php
require_once '../utils/config.php';       // Adatta il percorso se necessario
require_once '../utils/check_session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// 2) Verifichiamo la sessione e recuperiamo i dati utente.
//    Per esempio, consentiamo l’accesso a ruoli: docente, admin, sadmin.
//    (checkSession accetta un array di ruoli in minuscolo)
$user = checkSession(true, ['docente', 'admin', 'sadmin']);

// 3) Ottenimento data odierna
$oggi = date('Y-m-d');
$oggiIta = date('d-m-Y');

// ----------------------------------------------------
// Funzione di utilità per verificare se l'utente
// possiede almeno uno dei ruoli passati.
// checkSession te li passa già in $user['roles']
// ----------------------------------------------------
function userHasAnyRole($userRoles, $allowedRoles) {
    // Entrambe sono array di stringhe in minuscolo
    foreach ($userRoles as $r) {
        if (in_array(strtolower($r), $allowedRoles)) {
            return true;
        }
    }
    return false;
}

// Verifichiamo che l’utente sia almeno uno di questi ruoli
// (in teoria lo facciamo già dentro checkSession, ma se vuoi un “doppio controllo”…)
if (!userHasAnyRole($user['roles'], ['docente','admin','sadmin'])) {
    echo "<p>Non hai i permessi per accedere a questa pagina.</p>";
    exit;
}

// ----------------------------------------------------
// Determiniamo i corsi a cui può accedere l’utente
// ----------------------------------------------------
$corsiDisponibili = [];

// Se l'utente è admin/sadmin, può vedere tutti i corsi
// Altrimenti (docente) recuperiamo i corsi associati
$ruoliMinuscoli = array_map('strtolower', $user['roles']);
if (in_array('admin', $ruoliMinuscoli) || in_array('sadmin', $ruoliMinuscoli)) {
$stmt = $conn->prepare("
        SELECT c.*
        FROM courses c
        JOIN user_role_courses urc ON c.id_course = urc.id_course
        JOIN users u ON urc.id_user = u.id_user
        WHERE u.id_user = ?
          AND (urc.id_role = 3 OR urc.id_role = 4 )  -- ruolo admin/sadmin
        ORDER BY c.name
    ");
    $stmt->bind_param('i', $user['id_user']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        while ($rowC = $res->fetch_assoc()) {
            $corsiDisponibili[] = $rowC;
        }
    }
    $stmt->close();
} else { 
    $stmt = $conn->prepare("
        SELECT c.*
        FROM courses c
        JOIN user_role_courses urc ON c.id_course = urc.id_course
        JOIN users u ON urc.id_user = u.id_user
        WHERE u.id_user = ?
          AND urc.id_role = 2   -- ruolo docente
        ORDER BY c.name
    ");
    $stmt->bind_param('i', $user['id_user']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        while ($rowC = $res->fetch_assoc()) {
            $corsiDisponibili[] = $rowC;
        }
    }
    $stmt->close();
}

// --------------------------
// Selezione corso
// --------------------------
$idCorsoSelezionato = 0;
if (isset($_POST['id_course'])) {
    $idCorsoSelezionato = (int)$_POST['id_course'];
} 
// Se c’è un solo corso disponibile, usiamo quello automaticamente
if (count($corsiDisponibili) == 1) {
    $idCorsoSelezionato = $corsiDisponibili[0]['id_course'];
}

// --------------------------
// Salvataggio presenze
// --------------------------
if (isset($_POST['salva_presenze']) && $idCorsoSelezionato > 0) {
    // L’utente deve avere ruolo almeno docente/admin/sadmin per modificare
    if (!userHasAnyRole($user['roles'], ['docente','admin','sadmin'])) {
        echo "<p>Non hai i permessi per modificare le presenze.</p>";
        exit;
    }

    if (!empty($_POST['students']) && is_array($_POST['students'])) {
        foreach ($_POST['students'] as $idStudente => $valori) {
            $idStudente = (int)$idStudente;

            // Se la checkbox è presente => lo studente è “presente”
            $isPresente = isset($valori['presente']) ? 1 : 0;

            // Se presente, orari di default 14:00 e 16:00, salvo diversamente se il docente li ha modificati
            $entryHour = $isPresente ? (!empty($valori['entry_hour']) ? $valori['entry_hour'] : '14:00:00') : null;
            $exitHour  = $isPresente ? (!empty($valori['exit_hour'])  ? $valori['exit_hour']  : '16:00:00') : null;

            // Controlla se esiste già un record di attendance per (id_user, id_course, date=oggi)
            $sqlCheck = "SELECT id FROM attendance
                         WHERE id_user = ? AND id_course = ? AND date = ?
                         LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param('iis', $idStudente, $idCorsoSelezionato, $oggi);
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result();
            $rowAtt = $resCheck->fetch_assoc();
            $stmtCheck->close();

            if ($rowAtt) {
                // C’è già un record => facciamo UPDATE
                $sqlUpdate = "
                    UPDATE attendance
                    SET entry_hour = ?, exit_hour = ?
                    WHERE id = ?
                ";
                $stmtU = $conn->prepare($sqlUpdate);
                $stmtU->bind_param('ssi', $entryHour, $exitHour, $rowAtt['id']);
                $stmtU->execute();
                $stmtU->close();
            } else {
                // Non c’è => facciamo INSERT
                // Se lo studente non è presente, potremmo decidere di NON creare alcun record (dipende dalla logica).
                // Qui, per completezza, inseriamo comunque (con orari NULL se assente).
                $sqlInsert = "
                    INSERT INTO attendance (id_user, id_course, date, entry_hour, exit_hour)
                    VALUES (?, ?, ?, ?, ?)
                ";
                $stmtI = $conn->prepare($sqlInsert);
                $stmtI->bind_param('iisss', $idStudente, $idCorsoSelezionato, $oggi, $entryHour, $exitHour);
                $stmtI->execute();
                $stmtI->close();
            }
        }
        
    }
}


if ($idCorsoSelezionato > 0) {
    // Recuperiamo gli studenti di quel corso (role=studente -> id_role=1)
    $sqlStud = "
        SELECT u.id_user, u.firstname, u.lastname
        FROM users u
        JOIN user_role_courses urc ON u.id_user = urc.id_user
        WHERE urc.id_course = ?
          AND urc.id_role = 1
        ORDER BY u.lastname, u.firstname
    ";
    $stmtS = $conn->prepare($sqlStud);
    $stmtS->bind_param('i', $idCorsoSelezionato);
    $stmtS->execute();
    $resS = $stmtS->get_result();
    $studenti = [];
    while ($r = $resS->fetch_assoc()) {
        $studenti[] = $r;
    }
    $stmtS->close();

    if (!$studenti) {
        echo "<p>Nessuno studente trovato per questo corso.</p>";
    } else {
        // Prendiamo eventuali presenze già salvate per la data di oggi
        $sqlAtt = "
            SELECT id_user, entry_hour, exit_hour
            FROM attendance
            WHERE id_course = ?
              AND date = ?
        ";
        $stmtA = $conn->prepare($sqlAtt);
        $stmtA->bind_param('is', $idCorsoSelezionato, $oggi);
        $stmtA->execute();
        $resA = $stmtA->get_result();
        $mappaPresenze = [];
        while ($rowA = $resA->fetch_assoc()) {
            $mappaPresenze[$rowA['id_user']] = [
                'entry_hour' => $rowA['entry_hour'],
                'exit_hour'  => $rowA['exit_hour']
            ];
        }
        $stmtA->close();

        ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/manage_attendance.css" />
    <link rel="stylesheet" href="../assets/css/checkbox.css">
</head>
<body>
<div class="container">
    <h3>Presenze di oggi (<?php echo $oggi; ?>)</h3>
    <?php
    // Recupera il nome del corso selezionato
    $nomeCorsoSelezionato = 'Nessun corso selezionato';
    if ($idCorsoSelezionato > 0) {
        $stmtCorso = $conn->prepare("SELECT name FROM courses WHERE id_course = ?");
        $stmtCorso->bind_param('i', $idCorsoSelezionato);
        $stmtCorso->execute();
        $stmtCorso->bind_result($nomeCorso);
        if ($stmtCorso->fetch()) {
            $nomeCorsoSelezionato = $nomeCorso;
        }
        $stmtCorso->close();
    }
    ?>
    <p><strong><?php echo htmlspecialchars($nomeCorsoSelezionato); ?></strong></p>
        <br>
    <form method="post" class="styled-form" id="attendanceForm">
        <input type="hidden" name="id_course" value="<?php echo $idCorsoSelezionato; ?>" />
    <div class="table-container">
        <table class="attendance-table">
        <tr>
            <th>Studente</th>
            <th>Presente</th>
            <th>Ora Ingresso</th>
            <th>Ora Uscita</th>
        </tr>
        <?php foreach ($studenti as $stud): 
            $stId = $stud['id_user'];
            $entryH = isset($mappaPresenze[$stId]) ? $mappaPresenze[$stId]['entry_hour'] : null;
            $exitH  = isset($mappaPresenze[$stId]) ? $mappaPresenze[$stId]['exit_hour']  : null;
            $isPresente = (!empty($entryH) && !empty($exitH));
        ?>
        <tr>
            <td><?php echo htmlspecialchars($stud['lastname']." ".$stud['firstname']); ?></td>
            <td>
                <label class="checkbox">
                    <input type="checkbox" name="students[<?php echo $stId; ?>][presente]" value="1" 
                        class="checkbox__input" <?php echo $isPresente ? 'checked' : ''; ?> />
                    <svg class="checkbox__icon" viewBox="0 0 24 24" aria-hidden="true">
                        <rect width="24" height="24" fill="#e0e0e0" rx="4"></rect>
                        <path class="tick" fill="none" stroke="#007bff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M6 12l4 4 8-8"></path>
                    </svg>
                    <span class="checkbox__label"></span>
                </label>
            </td>
            <td>
                <input type="time" name="students[<?php echo $stId; ?>][entry_hour]" 
                    value="<?php echo $entryH ? $entryH : ''; ?>" />
            </td>
            <td>
                <input type="time" name="students[<?php echo $stId; ?>][exit_hour]" 
                    value="<?php echo $exitH ? $exitH : ''; ?>" />
            </td>
        </tr>
        <?php endforeach; ?>
        </table>
    </div>
    <br>
    <div class="button-container">
        <button type="submit" name="salva_presenze">Salva Presenze</button>
        <?php
            // Controlla il ruolo dell'utente
            if (in_array('docente', $user['roles'])) {
                echo '<button class="back" type="button" onclick="window.location.href=\'doc_panel.php\'">Indietro</button>';
            } else { // Se è admin o sadmin
                echo '<button class="back" type="button" onclick="window.location.href=\'admin_panel.php\'">Indietro</button>';
            }
        ?>

    </div>
    </form>
</div>
</body>
</html>
<?php
    }
} else {
    // Se l’utente ha più corsi, mostriamo un menu per selezionarne uno
    if (count($corsiDisponibili) > 1) {
        ?>
        <form method="post">
            <p>Seleziona il corso per cui inserire le presenze di oggi: <?php echo $oggiIta; ?></p><br>
            <select name="id_course" required>
                <option value="">-- scegli un corso --</option>
                <?php foreach ($corsiDisponibili as $c): ?>
                    <option value="<?php echo $c['id_course']; ?>">
                        <?php echo htmlspecialchars($c['name']." (".$c['period'].")"); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Vai</button>
        </form>
        <?php
    } else {
        echo "<p>Nessun corso disponibile o non selezionato.</p>";
    }
}
?>
