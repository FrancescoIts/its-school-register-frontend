<?php
require_once 'config.php';
require_once 'check_session.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Se la richiesta è POST, significa che stiamo chiedendo i dati via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Impostiamo l'header a JSON così da evitare l'errore di parsing in JavaScript
    header('Content-Type: application/json; charset=utf-8');

    // Assicuriamoci che l'utente sia loggato
    if (!isset($_SESSION['user']['id_user'])) {
        echo json_encode(["error" => "Utente non autenticato."]);
        exit;
    }

    $id_user = $_SESSION['user']['id_user'];
    $user_role = $_SESSION['user']['role'];

    // Consentiamo l'accesso solo a docente (2), admin (3) e superadmin (4)
    $allowed_roles = [2, 3, 4];
    if (!in_array($user_role, $allowed_roles)) {
        echo json_encode(["error" => "Accesso negato."]);
        exit;
    }

    // Controlliamo che ci sia l'id_user dello studente
    if (!isset($_POST['id_user']) || empty($_POST['id_user'])) {
        echo json_encode(["error" => "ID studente mancante."]);
        exit;
    }
    $student_id = (int) $_POST['id_user'];

    // 1. Query totale assenze / ore massime
    $queryTotal = "
        SELECT 
            SUM(absence_hours) AS total_absences, 
            (COUNT(DISTINCT date) * 8) AS total_max_hours
        FROM attendance
        WHERE id_user = ?
    ";
    $stmt = $conn->prepare($queryTotal);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_absences = $row['total_absences'] ?? 0;
    $total_max_hours = $row['total_max_hours'] ?? 1; // Evitiamo /0
    $stmt->close();

    // 2. Query assenze per giorno della settimana
    $queryWeekDays = "
        SELECT 
            DAYOFWEEK(date) AS weekday, 
            SUM(absence_hours) AS total_absences
        FROM attendance
        WHERE id_user = ?
        GROUP BY DAYOFWEEK(date)
        ORDER BY weekday
    ";
    $stmt = $conn->prepare($queryWeekDays);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Mappa DAYS: Domenica=1 ... Sabato=7
    $weekdaysMap = [
        1 => "Domenica",
        2 => "Lunedì",
        3 => "Martedì",
        4 => "Mercoledì",
        5 => "Giovedì",
        6 => "Venerdì",
        7 => "Sabato"
    ];
    $week_absences = array_fill_keys(array_values($weekdaysMap), 0);

    while ($row = $result->fetch_assoc()) {
        $dayName = $weekdaysMap[$row['weekday']];
        $week_absences[$dayName] = (float) $row['total_absences'];
    }
    $stmt->close();

    // Ritorniamo il JSON
    echo json_encode([
        "total_absences" => (float) $total_absences,
        "total_max_hours" => (int) $total_max_hours,
        "week_absences" => $week_absences
    ]);
    exit;
}

// Se invece la richiesta è GET (normalmente quando navighiamo la pagina),
// mostriamo l'HTML con l'elenco studenti + JS per la chiamata AJAX.

// Verifichiamo ancora il ruolo (solo docenti, admin, superadmin)
if (!isset($_SESSION['user']['role']) || !in_array($_SESSION['user']['role'], [2, 3, 4])) {
    die("Accesso negato");
}

// A questo punto recuperiamo la lista studenti
// Nei tuoi dump non esiste un campo "full_name". Possiamo farlo concatenando firstname e lastname.
$queryStudents = "
    SELECT 
      u.id_user, 
      CONCAT(u.firstname, ' ', u.lastname) AS full_name
    FROM users u
    INNER JOIN user_role_courses urc ON u.id_user = urc.id_user
    WHERE urc.id_role = 1 -- Ruolo studente
    GROUP BY u.id_user
    ORDER BY full_name
";
$stmt = $conn->prepare($queryStudents);
$stmt->execute();
$result = $stmt->get_result();
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiche Assenze Studenti</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .student-list {
            margin-bottom: 20px;
        }
        .student-item {
            cursor: pointer;
            padding: 10px;
            border: 1px solid #ddd;
            margin: 5px;
            display: inline-block;
            background-color: #f9f9f9;
        }
        .student-item:hover {
            background-color: #e0e0e0;
        }
        #stats-container {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ccc;
            background: #f4f4f4;
        }
    </style>
</head>
<body>

<h2>Elenco Studenti</h2>
<div class="student-list">
    <?php foreach ($students as $student): ?>
        <div class="student-item" data-id="<?= htmlspecialchars($student['id_user']) ?>">
            <?= htmlspecialchars($student['full_name']) ?>
        </div>
    <?php endforeach; ?>
</div>

<h2>Statistiche Assenze</h2>
<div id="stats-container">
    <p>Seleziona uno studente per vedere le statistiche.</p>
</div>

<script>
$(document).ready(function() {
    $(".student-item").click(function() {
        const studentId = $(this).data("id");

        $.ajax({
            url: "stats_total.php", // Stesso file
            method: "POST",
            dataType: "json",
            data: {
                id_user: studentId
            },
            success: function(response) {
                if (response.error) {
                    $("#stats-container").html(
                        "<p style='color: red;'>" + response.error + "</p>"
                    );
                } else {
                    let statsHtml = "<p><strong>Ore di assenza totali:</strong> " 
                                    + response.total_absences + "</p>";
                    statsHtml += "<p><strong>Ore massime disponibili:</strong> " 
                                 + response.total_max_hours + "</p>";
                    statsHtml += "<h3>Assenze per giorno della settimana:</h3><ul>";
                    
                    $.each(response.week_absences, function(day, hours) {
                        statsHtml += "<li>" + day + ": " + hours + " ore</li>";
                    });
                    
                    statsHtml += "</ul>";
                    $("#stats-container").html(statsHtml);
                }
            },
            error: function(xhr, status, error) {
                $("#stats-container").html(
                    "<p style='color: red;'>Errore nel recupero delle statistiche: " + error + "</p>"
                );
            }
        });
    });
});
</script>

</body>
</html>
