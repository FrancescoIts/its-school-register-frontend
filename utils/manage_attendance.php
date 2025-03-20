<?php
require_once '../utils/config.php';
require_once '../utils/check_session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

$user = checkSession(true, ['docente','admin','sadmin']);

$oggi    = date('Y-m-d');
$oggiIta = date('d/m/Y');

function userHasAnyRole($userRoles, $allowedRoles) {
    foreach ($userRoles as $r) {
        if (in_array(strtolower($r), $allowedRoles)) {
            return true;
        }
    }
    return false;
}

if (!userHasAnyRole($user['roles'], ['docente','admin'])) {
    echo "<p>Non hai i permessi per accedere a questa pagina.</p>";
    return;
}

$isAdmin = (in_array('admin', array_map('strtolower', $user['roles'])) || in_array('sadmin', array_map('strtolower', $user['roles'])));

$corsiDisponibili = [];
$ruoliMinuscoli   = array_map('strtolower', $user['roles']);

if (in_array('admin', $ruoliMinuscoli) || in_array('sadmin', $ruoliMinuscoli)) {
    $stmt = $conn->prepare("
        SELECT c.*
        FROM courses c
        JOIN user_role_courses urc ON c.id_course = urc.id_course
        JOIN users u ON urc.id_user = u.id_user
        WHERE u.id_user = ?
          AND (urc.id_role = 3 OR urc.id_role = 4)
        ORDER BY c.name
    ");
    $stmt->bind_param('i', $user['id_user']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($rowC = $res->fetch_assoc()) {
        $corsiDisponibili[] = $rowC;
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare("
        SELECT c.*
        FROM courses c
        JOIN user_role_courses urc ON c.id_course = urc.id_course
        JOIN users u ON urc.id_user = u.id_user
        WHERE u.id_user = ?
          AND urc.id_role = 2
        ORDER BY c.name
    ");
    $stmt->bind_param('i', $user['id_user']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($rowC = $res->fetch_assoc()) {
        $corsiDisponibili[] = $rowC;
    }
    $stmt->close();
}

// Imposta il corso selezionato in base al parametro GET
$idCorsoSelezionato = 0;
if (isset($_GET['id_course']) && !empty($_GET['id_course'])) {
    $idCorsoSelezionato = (int)$_GET['id_course'];
}
// Se l'utente ha un solo corso, lo seleziona automaticamente
if (count($corsiDisponibili) == 1) {
    $idCorsoSelezionato = $corsiDisponibili[0]['id_course'];
}

// Se l'utente ha più corsi e non ha selezionato nessuno, mostriamo il form di scelta
if ($idCorsoSelezionato == 0 && count($corsiDisponibili) > 1) {
    ?>
    <h3>Seleziona il corso per cui vuoi gestire le presenze</h3>
    <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
      <select name="id_course" required>
         <option value="">-- Seleziona corso --</option>
         <?php
         foreach ($corsiDisponibili as $c) {
             echo '<option value="' . $c['id_course'] . '">' . htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') . '</option>';
         }
         ?>
      </select>
      <button type="submit">Seleziona</button>
    </form>
    <?php
} else {

    function getDailyCourseTimes($conn, $idCourse, $oggi) {
        $dayOfWeek = strtolower(date('l', strtotime($oggi)));
        if ($dayOfWeek === 'saturday' || $dayOfWeek === 'sunday') {
            return [null, null];
        }
        $startColumn = 'start_time_' . $dayOfWeek;
        $endColumn   = 'end_time_' . $dayOfWeek;

        $sql = "SELECT $startColumn AS start_day, $endColumn AS end_day FROM courses WHERE id_course = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $idCourse);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return [$res['start_day'], $res['end_day']];
    }

    list($giornoStart, $giornoEnd) = getDailyCourseTimes($conn, $idCorsoSelezionato, $oggi);

    $studenti = [];
    if ($idCorsoSelezionato > 0) {
        $sqlStud = "
            SELECT u.id_user, u.firstname, u.lastname
            FROM users u
            JOIN user_role_courses urc ON u.id_user = urc.id_user
            WHERE urc.id_course = ? AND urc.id_role = 1
            ORDER BY u.lastname, u.firstname
        ";
        $stmtS = $conn->prepare($sqlStud);
        $stmtS->bind_param('i', $idCorsoSelezionato);
        $stmtS->execute();
        $resS = $stmtS->get_result();
        while ($r = $resS->fetch_assoc()) {
            $studenti[] = $r;
        }
        $stmtS->close();
    }

    $mappaPresenze = [];
    if ($idCorsoSelezionato > 0) {
        $sqlAtt = "SELECT id_user, entry_hour, exit_hour FROM attendance WHERE id_course = ? AND date = ?";
        $stmtA = $conn->prepare($sqlAtt);
        $stmtA->bind_param('is', $idCorsoSelezionato, $oggi);
        $stmtA->execute();
        $resA = $stmtA->get_result();
        while ($rowA = $resA->fetch_assoc()) {
            $mappaPresenze[$rowA['id_user']] = [
                'entry_hour' => $rowA['entry_hour'],
                'exit_hour'  => $rowA['exit_hour']
            ];
        }
        $stmtA->close();
    }

    // Nome corso selezionato (inizializzato per evitare warning)
    $nomeCorsoSelezionato = '';
    if ($idCorsoSelezionato > 0) {
        foreach ($corsiDisponibili as $c) {
            if ($c['id_course'] == $idCorsoSelezionato) {
                $nomeCorsoSelezionato = $c['name'];
                break;
            }
        }
    }

    // Flag per segnalare modifiche non permesse
    $notAllowedModification = false;

    // Salvataggio in POST
    if (isset($_POST['salva_presenze'])) {
        $moduleSelected = isset($_POST['id_module']) ? (int)$_POST['id_module'] : 0;
        $lessonHours = (strtotime($giornoEnd) - strtotime($giornoStart)) / 3600;
        $operationPerformed = false;
        if (!empty($_POST['students']) && is_array($_POST['students'])) {
            foreach ($_POST['students'] as $idStudente => $valori) {
                $idStudente = (int)$idStudente;
                $isPresente = isset($valori['presente']) ? 1 : 0;
                if ($isPresente) {
                    $entryHour = !empty($valori['entry_hour']) ? $valori['entry_hour'] : $giornoStart;
                    $exitHour  = !empty($valori['exit_hour'])  ? $valori['exit_hour']  : $giornoEnd;
                } else {
                    $entryHour = null;
                    $exitHour  = null;
                }
                // Per i docenti (non admin) controlla eventuali presenze già inserite da chiunque
                if ($isAdmin) {
                    $sqlCheck = "SELECT id, created_by FROM attendance WHERE id_user = ? AND id_course = ? AND date = ? LIMIT 1";
                    $stmtCheck = $conn->prepare($sqlCheck);
                    $stmtCheck->bind_param('iis', $idStudente, $idCorsoSelezionato, $oggi);
                } else {
                    // Controlla se esiste già una riga per lo studente, a prescindere dal created_by
                    $sqlCheck = "SELECT id, created_by FROM attendance WHERE id_user = ? AND id_course = ? AND date = ? LIMIT 1";
                    $stmtCheck = $conn->prepare($sqlCheck);
                    $stmtCheck->bind_param('iis', $idStudente, $idCorsoSelezionato, $oggi);
                }
                $stmtCheck->execute();
                $resCheck = $stmtCheck->get_result();
                $rowAtt = $resCheck->fetch_assoc();
                $stmtCheck->close();
                
                if ($rowAtt) {
                    // Se l'utente è docente e la riga esiste già ma non è stata inserita da lui, segnala errore e non aggiorna/inserisce
                    if (!$isAdmin && $rowAtt['created_by'] != $user['id_user']) {
                        $notAllowedModification = true;
                        continue;
                    }
                    // Altrimenti, esegue l'update
                    if ($isAdmin) {
                        $sqlUpdate = "UPDATE attendance SET entry_hour = ?, exit_hour = ? WHERE id = ?";
                        $stmtU = $conn->prepare($sqlUpdate);
                        $stmtU->bind_param('ssi', $entryHour, $exitHour, $rowAtt['id']);
                    } else {
                        $sqlUpdate = "UPDATE attendance SET entry_hour = ?, exit_hour = ? WHERE id = ? AND created_by = ?";
                        $stmtU = $conn->prepare($sqlUpdate);
                        $stmtU->bind_param('ssii', $entryHour, $exitHour, $rowAtt['id'], $user['id_user']);
                    }
                    $stmtU->execute();
                    $stmtU->close();
                } else {
                    // Se non esiste alcuna riga, verifica se esiste già una riga inserita da un altro docente
                    if (!$isAdmin) {
                        // Eseguiamo una SELECT per vedere se esiste già una riga (da chiunque)
                        $sqlDupCheck = "SELECT id, created_by FROM attendance WHERE id_user = ? AND id_course = ? AND date = ? LIMIT 1";
                        $stmtDup = $conn->prepare($sqlDupCheck);
                        $stmtDup->bind_param('iis', $idStudente, $idCorsoSelezionato, $oggi);
                        $stmtDup->execute();
                        $resDup = $stmtDup->get_result();
                        if ($resDup->num_rows > 0) {
                            // Se esiste già una riga inserita da un altro docente, non fare nulla e segnala errore
                            $notAllowedModification = true;
                            $stmtDup->close();
                            continue;
                        }
                        $stmtDup->close();
                    }
                    $sqlInsert = "INSERT INTO attendance (id_user, id_course, date, entry_hour, exit_hour, created_by) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmtI = $conn->prepare($sqlInsert);
                    $stmtI->bind_param('iisssi', $idStudente, $idCorsoSelezionato, $oggi, $entryHour, $exitHour, $user['id_user']);
                    $stmtI->execute();
                    $stmtI->close();
                }
                $operationPerformed = true;
            }
        }
        if ($operationPerformed && $moduleSelected > 0) {
            $sqlCheckModule = "SELECT id, hours_accumulated FROM module_attendance WHERE id_course = ? AND id_module = ? AND date = ? LIMIT 1";
            $stmtM = $conn->prepare($sqlCheckModule);
            $stmtM->bind_param('iis', $idCorsoSelezionato, $moduleSelected, $oggi);
            $stmtM->execute();
            $resM = $stmtM->get_result();
            if ($rowM = $resM->fetch_assoc()) {
                $sqlUpdateModule = "UPDATE module_attendance SET hours_accumulated = ? WHERE id = ?";
                $stmtUM = $conn->prepare($sqlUpdateModule);
                $stmtUM->bind_param('di', $lessonHours, $rowM['id']);
                $stmtUM->execute();
                $stmtUM->close();
            } else {
                $sqlInsertModule = "INSERT INTO module_attendance (id_course, id_module, date, hours_accumulated) VALUES (?, ?, ?, ?)";
                $stmtIM = $conn->prepare($sqlInsertModule);
                $stmtIM->bind_param('iisd', $idCorsoSelezionato, $moduleSelected, $oggi, $lessonHours);
                $stmtIM->execute();
                $stmtIM->close();
            }
            $stmtM->close();
        }
        ?>
        <script>
          <?php if ($notAllowedModification): ?>
            Swal.fire({
                title: 'Attenzione',
                text: 'Non hai il permesso di modificare le presenze inserite da altri docenti.',
                icon: 'warning',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = '<?php echo $_SERVER['PHP_SELF']."?id_course=$idCorsoSelezionato"; ?>';
            });
          <?php else: ?>
            Swal.fire({
                title: 'Successo!',
                text: 'Registro salvato con successo.',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = '<?php echo $_SERVER['PHP_SELF']."?id_course=$idCorsoSelezionato"; ?>';
            });
          <?php endif; ?>
        </script>
        <?php
    }
    ?>


      <h3>Presenze di oggi (<?php echo htmlspecialchars($oggiIta, ENT_QUOTES, 'UTF-8'); ?>)</h3>
      <p><strong><?php echo htmlspecialchars($nomeCorsoSelezionato, ENT_QUOTES, 'UTF-8'); ?></strong></p>
      
      <input type="hidden" id="defaultStartTime" value="<?php echo $giornoStart; ?>">
      <input type="hidden" id="defaultEndTime" value="<?php echo $giornoEnd; ?>">
      
      <!-- Aggiungo l'attributo data-no-loader per escludere questo form dal listener globale del loader -->
      <form id="attendanceForm" data-no-loader="true" method="post" action="<?php echo $_SERVER['PHP_SELF']."?id_course=$idCorsoSelezionato"; ?>">
        <input type="hidden" name="id_course" value="<?php echo $idCorsoSelezionato; ?>">
        <div>
          <label for="id_module">Seleziona Modulo (per questa lezione):</label>
          <select name="id_module" id="id_module">
            <option value="0">Nessun modulo</option>
            <?php
              if ($idCorsoSelezionato > 0) {
                  $sqlMod = "SELECT id_module, module_name, module_duration FROM modules WHERE id_course = ? ORDER BY module_name";
                  $stmtM = $conn->prepare($sqlMod);
                  $stmtM->bind_param('i', $idCorsoSelezionato);
                  $stmtM->execute();
                  $resM = $stmtM->get_result();
                  while ($mod = $resM->fetch_assoc()) {
                      echo '<option value="' . $mod['id_module'] . '">' . htmlspecialchars($mod['module_name'], ENT_QUOTES, 'UTF-8') . ' (' . $mod['module_duration'] . ' ore)</option>';
                  }
                  $stmtM->close();
              }
            ?>
          </select>
        </div>
        <br>
        <div class="button-utilities">
          <button type="button" onclick="checkAllStudents()">Seleziona tutti</button>
          <button type="button" onclick="fillTimes()">Riempie orari</button>
                  <!-- Aggiungo un button info con tooltip "Apri tutorial" -->
        <button type="button" id="tutorialBtn" title="Apri tutorial">
        <i class="fas fa-info-circle" style="font-size: 1.5em; color:rgb(255, 255, 255);"></i>
      </button>
        </div>
        <br>
        <div style="text-align: center; margin-bottom: 15px;">
          <input type="text" id="Filter" placeholder="Filtra per nome o cognome..." style="padding: 5px; width: 50%;">
        </div>
    
        <div class="table-container">
          <table class="attendance-table">
            <tr>
              <th>Studente</th>
              <th>Presente</th>
              <th>Ora Ingresso</th>
              <th>Ora Uscita</th>
            </tr>
            <?php
            foreach ($studenti as $stud):
              $stId   = $stud['id_user'];
              $entryH = isset($mappaPresenze[$stId]['entry_hour']) ? $mappaPresenze[$stId]['entry_hour'] : '';
              $exitH  = isset($mappaPresenze[$stId]['exit_hour'])  ? $mappaPresenze[$stId]['exit_hour'] : '';
              $isPresente = (!empty($entryH) && !empty($exitH));
            ?>
            <tr class="student-row">
              <td><?php echo htmlspecialchars($stud['lastname'] . " " . $stud['firstname'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td>
                <label class="checkbox">
                  <input type="checkbox" name="students[<?php echo $stId; ?>][presente]" value="1" class="checkbox__input" <?php echo $isPresente ? 'checked' : ''; ?>>
                  <svg class="checkbox__icon" viewBox="0 0 24 24" aria-hidden="true">
                    <rect width="24" height="24" fill="#e0e0e0" rx="4"></rect>
                    <path class="tick" fill="none" stroke="#007bff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M6 12l4 4 8-8"></path>
                  </svg>
                </label>
              </td>
              <td>
                <input type="time" name="students[<?php echo $stId; ?>][entry_hour]" step="60" min="<?php echo $giornoStart; ?>" max="<?php echo $giornoEnd; ?>" value="<?php echo $entryH; ?>">
              </td>
              <td>
                <input type="time" name="students[<?php echo $stId; ?>][exit_hour]" step="60" min="<?php echo $giornoStart; ?>" max="<?php echo $giornoEnd; ?>" value="<?php echo $exitH; ?>">
              </td>
            </tr>
            <?php endforeach; ?>
          </table>
        </div>
    
        <br>
        <div class="button-container">
          <button type="submit" class="save" name="salva_presenze">Salva Presenze</button>
          <?php
          if (in_array('docente', $user['roles'])) {
              echo '<button type="button" class="back" onclick="window.location.href=\'doc_panel.php\'">Indietro</button>';
          } else {
              echo '<button type="button" class="back" onclick="window.location.href=\'admin_panel.php\'">Indietro</button>';
          }
          ?>
        </div>
      </form>
      <script>
        document.getElementById('id_module').addEventListener('change', function() {
          var selectElement = this;
          var selected = selectElement.value;
          if(selected === "0") {
            // Nessuna azione 
          } else {
            // Popup di conferma per la scelta del modulo
            Swal.fire({
              title: 'Conferma scelta modulo',
              text: 'Sei sicuro di voler selezionare questo modulo?',
              icon: 'question',
              showCancelButton: true,
              confirmButtonText: 'Si, conferma',
              cancelButtonText: 'Annulla'
            }).then((result) => {
              if (!result.isConfirmed) {
                selectElement.value = "0";
              }
            });
          }
        });
      </script>
    <?php
}
?>
