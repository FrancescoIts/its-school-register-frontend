<?php
require 'config.php';

$sql = "DELETE FROM sessions WHERE data_scadenza < NOW()";
if ($conn->query($sql) === TRUE) {
    echo "Sessioni scadute eliminate con successo.";
} else {
    echo "Errore nell'eliminazione delle sessioni: " . $conn->error;
}
$conn->close();


/*
* * * * * php /percorso/del/file/cleanup_sessions.php TODO Cron Job
*/
