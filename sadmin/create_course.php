<?php
require_once '../utils/config.php';
require_once '../utils/check_session.php';

$user = checkSession(true, ['admin', 'sadmin']);
$swalScript = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Raccolta dei dati dal form
    $name       = trim($_POST['name'] ?? '');
    $year       = trim($_POST['year'] ?? '');
    $period     = trim($_POST['period'] ?? '');
    $total_hour = trim($_POST['total_hour'] ?? '');
    
    // Orari per ogni giorno feriale (sono required)
    $start_time_monday    = $_POST['start_time_monday'] ?? null;
    $end_time_monday      = $_POST['end_time_monday'] ?? null;
    $start_time_tuesday   = $_POST['start_time_tuesday'] ?? null;
    $end_time_tuesday     = $_POST['end_time_tuesday'] ?? null;
    $start_time_wednesday = $_POST['start_time_wednesday'] ?? null;
    $end_time_wednesday   = $_POST['end_time_wednesday'] ?? null;
    $start_time_thursday  = $_POST['start_time_thursday'] ?? null;
    $end_time_thursday    = $_POST['end_time_thursday'] ?? null;
    $start_time_friday    = $_POST['start_time_friday'] ?? null;
    $end_time_friday      = $_POST['end_time_friday'] ?? null;
    
    // Validazione dei campi obbligatori
    if (empty($name) || empty($year) || empty($period) || empty($total_hour)) {
        $swalScript = "<script>Swal.fire({title:'Errore!', text:'Compilare tutti i campi obbligatori (Nome, Anno, Periodo e Ore Totali).', icon:'error'});</script>";
    } 
    // Controllo sul nome del corso (max 40 caratteri)
    elseif (strlen($name) > 40) {
        $swalScript = "<script>Swal.fire({title:'Errore!', text:'Il nome del corso non può superare 40 caratteri.', icon:'error'});</script>";
    } 
    // Controllo sul campo anno: deve essere numerico e avere al massimo 4 caratteri (es. 2024)
    elseif (!ctype_digit($year) || strlen($year) > 4) {
        $swalScript = "<script>Swal.fire({title:'Errore!', text:'L\'anno deve essere un numero e avere al massimo 4 caratteri (Es. 2024).', icon:'error'});</script>";
    }
    // Controllo sul campo periodo: massimo 9 caratteri
    elseif (strlen($period) > 9) {
        $swalScript = "<script>Swal.fire({title:'Errore!', text:'Il periodo deve avere al massimo 9 caratteri (Es. 2024-2026).', icon:'error'});</script>";
    }
    // Validazione del campo total_hour: deve essere numerico e composto da 3 o 4 cifre (min 100, max 9999)
    elseif (!ctype_digit($total_hour) || strlen($total_hour) < 3 || strlen($total_hour) > 4) {
        $swalScript = "<script>Swal.fire({title:'Errore!', text:'Le ore totali devono essere un numero di 3 o 4 cifre (Es. 100 - 9999).', icon:'error'});</script>";
    }
    // Controllo degli orari per ogni giorno: uscita > ingresso
    elseif (
        (strtotime($end_time_monday) <= strtotime($start_time_monday)) ||
        (strtotime($end_time_tuesday) <= strtotime($start_time_tuesday)) ||
        (strtotime($end_time_wednesday) <= strtotime($start_time_wednesday)) ||
        (strtotime($end_time_thursday) <= strtotime($start_time_thursday)) ||
        (strtotime($end_time_friday) <= strtotime($start_time_friday))
    ) {
        $swalScript = "<script>Swal.fire({title:'Errore!', text:'Gli orari di uscita devono essere maggiori degli orari di ingresso per ogni giorno.', icon:'error'});</script>";
    }
    else {
        // Verifica se esiste già un corso con lo stesso nome, anno e periodo
        $dupQuery = "SELECT COUNT(*) AS count FROM courses WHERE name = ? AND year = ? AND period = ?";
        $stmtDup = $conn->prepare($dupQuery);
        $stmtDup->bind_param("sis", $name, $year, $period);
        $stmtDup->execute();
        $resultDup = $stmtDup->get_result();
        $rowDup = $resultDup->fetch_assoc();
        $stmtDup->close();
        
        if ($rowDup['count'] > 0) {
            $swalScript = "<script>Swal.fire({title:'Errore!', text:'Esiste già un corso con lo stesso nome, anno e periodo.', icon:'error'});</script>";
        } else {
            // Preparazione query di inserimento, con il nuovo campo total_hour
            $insertQuery = "INSERT INTO courses 
                (name, year, period, total_hour, start_time_monday, end_time_monday, start_time_tuesday, end_time_tuesday, start_time_wednesday, end_time_wednesday, start_time_thursday, end_time_thursday, start_time_friday, end_time_friday)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
            $stmt = $conn->prepare($insertQuery);
            if (!$stmt) {
                $swalScript = "<script>Swal.fire({title:'Errore!', text:'Errore nella preparazione della query: " . addslashes($conn->error) . "', icon:'error'});</script>";
            } else {
                $stmt->bind_param(
                    "sisi" . "ssssssssss", 
                    $name, 
                    $year, 
                    $period, 
                    $total_hour, 
                    $start_time_monday, 
                    $end_time_monday, 
                    $start_time_tuesday, 
                    $end_time_tuesday, 
                    $start_time_wednesday, 
                    $end_time_wednesday, 
                    $start_time_thursday, 
                    $end_time_thursday, 
                    $start_time_friday, 
                    $end_time_friday
                );
                if ($stmt->execute()) {
                    $swalScript = "<script>Swal.fire({title:'Successo!', text:'Corso creato con successo!', icon:'success'});</script>";
                } else {
                    $swalScript = "<script>Swal.fire({title:'Errore!', text:'Errore durante l\'inserimento: " . addslashes($stmt->error) . "', icon:'error'});</script>";
                }
                $stmt->close();
            }
        }
    }
}
?>
<?php
if (!empty($swalScript)) {
    echo $swalScript;
}
?>
<div class="create-user-container">
    <h2 class="create-user-title">Crea Nuovo Corso</h2>
    <form method="POST" action="" class="create-user-form">
        <div class="form-group">
            <label class="create-user-label" for="name">Nome del Corso:</label>
            <input type="text" class="create-user-input" id="name" name="name" placeholder="Inserisci nome corso" required maxlength="40">
        </div>
        <div class="form-group">
            <label class="create-user-label" for="year">Anno:</label>
            <!-- Precompilato con l'anno corrente -->
            <input type="number" class="create-user-input" id="year" name="year" placeholder="Inserisci anno" required maxlength="4" value="<?php echo date('Y'); ?>">
        </div>
        <div class="form-group">
            <label class="create-user-label" for="period">Periodo:</label>
            <input type="text" class="create-user-input" id="period" name="period" placeholder="Es. 2024-2026" required maxlength="9">
        </div>
        <div class="form-group">
            <label class="create-user-label" for="total_hour">Ore Totali del Corso:</label>
            <input type="number" class="create-user-input" id="total_hour" name="total_hour" placeholder="Inserisci ore totali (100-9999)" required min="100" max="9999">
        </div>
        <h4 style="text-align:center; color:#FFF; margin-bottom:20px;">Orari delle Lezioni (24h)</h4>
        <!-- Sezione per impostare orari predefiniti -->
        <div class="preset-container">
            <input type="time" class="preset-input" id="presetEntry" placeholder="Ora Ingresso">
            <input type="time" class="preset-input" id="presetExit" placeholder="Ora Uscita">
            <button type="button" class="preset-button" onclick="applyPresetTimes()">Applica Orari a tutti</button>
        </div>
        <br>
        <!-- Lunedì -->
        <div class="form-group">
            <label class="create-user-label" for="start_time_monday">Lunedì - Inizio:</label>
            <input type="time" class="create-user-input" id="start_time_monday" name="start_time_monday" required>
        </div>
        <div class="form-group">
            <label class="create-user-label" for="end_time_monday">Lunedì - Fine:</label>
            <input type="time" class="create-user-input" id="end_time_monday" name="end_time_monday" required>
        </div>
        <!-- Martedì -->
        <div class="form-group">
            <label class="create-user-label" for="start_time_tuesday">Martedì - Inizio:</label>
            <input type="time" class="create-user-input" id="start_time_tuesday" name="start_time_tuesday" required>
        </div>
        <div class="form-group">
            <label class="create-user-label" for="end_time_tuesday">Martedì - Fine:</label>
            <input type="time" class="create-user-input" id="end_time_tuesday" name="end_time_tuesday" required>
        </div>
        <!-- Mercoledì -->
        <div class="form-group">
            <label class="create-user-label" for="start_time_wednesday">Mercoledì - Inizio:</label>
            <input type="time" class="create-user-input" id="start_time_wednesday" name="start_time_wednesday" required>
        </div>
        <div class="form-group">
            <label class="create-user-label" for="end_time_wednesday">Mercoledì - Fine:</label>
            <input type="time" class="create-user-input" id="end_time_wednesday" name="end_time_wednesday" required>
        </div>
        <!-- Giovedì -->
        <div class="form-group">
            <label class="create-user-label" for="start_time_thursday">Giovedì - Inizio:</label>
            <input type="time" class="create-user-input" id="start_time_thursday" name="start_time_thursday" required>
        </div>
        <div class="form-group">
            <label class="create-user-label" for="end_time_thursday">Giovedì - Fine:</label>
            <input type="time" class="create-user-input" id="end_time_thursday" name="end_time_thursday" required>
        </div>
        <!-- Venerdì -->
        <div class="form-group">
            <label class="create-user-label" for="start_time_friday">Venerdì - Inizio:</label>
            <input type="time" class="create-user-input" id="start_time_friday" name="start_time_friday" required>
        </div>
        <div class="form-group">
            <label class="create-user-label" for="end_time_friday">Venerdì - Fine:</label>
            <input type="time" class="create-user-input" id="end_time_friday" name="end_time_friday" required>
        </div>
        <button type="submit" class="create-user-button" id="createButton">Crea Corso</button>
    </form>
</div>
