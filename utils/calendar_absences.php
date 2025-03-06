<?php
require_once 'config.php';
require_once 'check_session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$user = checkSession(true, ['studente']);
$id_user = $user['id_user'] ?? 0;

// Recupero del corso associato all'utente
$stmtCourse = $conn->prepare("
  SELECT id_course FROM user_role_courses
  WHERE id_user = ? LIMIT 1
");
$stmtCourse->bind_param("i", $id_user);
$stmtCourse->execute();
$resultCourse = $stmtCourse->get_result()->fetch_assoc();
$stmtCourse->close();

$id_course = $resultCourse['id_course'] ?? null;

if (!$id_course) {
    die("Nessun corso associato all'utente.");
}

// Funzione per recuperare gli orari dal database
function getCourseTimes($conn, $id_course, $day_of_week) {
    $query = "
        SELECT start_time_{$day_of_week} AS start_time, end_time_{$day_of_week} AS end_time
        FROM courses
        WHERE id_course = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_course);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return [$result['start_time'], $result['end_time']];
}

// Recupero delle assenze
$currentYear = date('Y');
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
if(isset($_GET['year'])){
  $currentYear = (int)$_GET['year'];
}
$stmtA = $conn->prepare("
  SELECT date, entry_hour, exit_hour
  FROM attendance
  WHERE id_user = ?
    AND id_course = ?
    AND YEAR(date) = ?
");
$stmtA->bind_param("iii", $id_user, $id_course, $currentYear);
$stmtA->execute();
$resA = $stmtA->get_result();

$absences = [];
while ($row = $resA->fetch_assoc()) {
    $date = $row['date'];
    $entry = $row['entry_hour'] ?? null;
    $exit  = $row['exit_hour'] ?? null;

    // Ottieni il giorno della settimana in inglese (monday, tuesday, etc.)
    $dayOfWeek = strtolower(date('l', strtotime($date)));

    // Ottieni gli orari di inizio e fine dal corso
    list($course_start, $course_end) = getCourseTimes($conn, $id_course, $dayOfWeek);

    if (!$course_start || !$course_end) {
        continue;
    }

    $standard_entry_seconds = strtotime($course_start);
    $standard_exit_seconds  = strtotime($course_end);

    if (!$entry || !$exit) {
        $absence_hours = ($standard_exit_seconds - $standard_entry_seconds) / 3600;
        $absences[$date] = round($absence_hours, 2);
        continue;
    }

    $entry_seconds = strtotime($entry);
    $exit_seconds  = strtotime($exit);

    $absence_hours = 0;
    if ($entry_seconds > $standard_entry_seconds) {
        $absence_hours += ($entry_seconds - $standard_entry_seconds) / 3600;
    }
    if ($exit_seconds < $standard_exit_seconds) {
        $absence_hours += ($standard_exit_seconds - $exit_seconds) / 3600;
    }

    if ($absence_hours > 0) {
        $absences[$date] = round($absence_hours, 2);
    }
}
$stmtA->close();

// Dati per la generazione del calendario
$monthsIta = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
$monthName = $monthsIta[$currentMonth - 1];
// Utilizzo date('N') per ottenere il numero del giorno ISO (1 = Lun, 7 = Dom)
$firstDay = date('N', strtotime("$currentYear-$currentMonth-01"));
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
?>
  <div class="wrapper">
    <!-- Intestazione e navigazione -->
    <div class="calendar-header" style="text-align:center; margin-bottom:10px;">
      <a href="?month=<?php echo ($currentMonth == 1 ? 12 : $currentMonth - 1); ?>&year=<?php echo ($currentMonth == 1 ? $currentYear - 1 : $currentYear); ?>" class="prev-month o-btn">
        <strong>&#8810;</strong>
      </a>
      <span class="current-month" style="font-size:1.2em; font-weight:bold; margin: 0 10px;"><?php echo "$monthName $currentYear"; ?></span>
      <a href="?month=<?php echo ($currentMonth == 12 ? 1 : $currentMonth + 1); ?>&year=<?php echo ($currentMonth == 12 ? $currentYear + 1 : $currentYear); ?>" class="next-month o-btn">
        <strong>&#8811;</strong>
      </a>
    </div>

    <!-- Calendario -->
    <div class="c-calendar__style">
      <div class="c-cal__container">
        <!-- Intestazione giorni -->
        <div class="c-cal__row">
          <?php
            $daysOfWeek = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];
            foreach($daysOfWeek as $day){
              echo "<div class='c-cal__col'>$day</div>";
            }
          ?>
        </div>
        <!-- Generazione righe e celle -->
        <?php
          $totalCells = ($firstDay - 1) + $daysInMonth;
          $totalRows = ceil($totalCells / 7);
          $dayCounter = 1;
          for ($r = 0; $r < $totalRows; $r++) {
            echo '<div class="c-cal__row">';
            for ($c = 0; $c < 7; $c++) {
              $cellIndex = $r * 7 + $c + 1;
              if ($cellIndex < $firstDay || $dayCounter > $daysInMonth) {
                echo '<div class="c-cal__cel"></div>';
              } else {
                $dayStr = str_pad($dayCounter, 2, '0', STR_PAD_LEFT);
                $monthStr = str_pad($currentMonth, 2, '0', STR_PAD_LEFT);
                $dateString = "$currentYear-$monthStr-$dayStr";
                $hasAbsence = isset($absences[$dateString]);
                $cellClass = "c-cal__cel" . ($hasAbsence ? " event" : "");
                echo '<div class="' . $cellClass . '" data-date="' . $dateString . '" data-absence="' . ($hasAbsence ? $absences[$dateString] : '') . '">';
                echo "<p>$dayCounter</p>";
                echo '</div>';
                $dayCounter++;
              }
            }
            echo '</div>';
          }
        ?>
      </div>
    </div>
  </div>

  <script>
    // Assegna l'evento click ad ogni cella del calendario
    document.querySelectorAll('.c-cal__cel').forEach(cell => {
      cell.addEventListener('click', function() {
        let dateStr  = this.getAttribute('data-date');
        let absenceH = this.getAttribute('data-absence');
        let dateObj  = new Date(dateStr);
        let options  = { day: 'numeric', month: 'long', year: 'numeric' };
        let dateStrIta = dateObj.toLocaleDateString('it-IT', options);
        let msg = absenceH 
                    ? `Ore di assenza: ${absenceH}` 
                    : `<img src="https://media.giphy.com/media/d8lUKXD00IXSw/giphy.gif?cid=790b7611xn5dg1mlcc0g7hk6hdo94xtx3dqtpotmlk4uez7b&ep=v1_gifs_search&rid=giphy.gif&ct=g" width="250" alt="GIF">`;
        Swal.fire({
          title: `Dettagli: ${dateStrIta}`,
          html: msg,
          icon: 'info',
          confirmButtonText: 'OK',
          backdrop: 'rgba(0, 0, 0, 0.5)',
        });
      });
    });
  </script>
