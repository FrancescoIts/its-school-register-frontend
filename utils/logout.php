<?php
ob_start(); // Inizio output buffering
session_start();
require_once 'config.php'; 

if (isset($_SESSION['user'])) {
    $session_id = session_id();
    $id_user = $_SESSION['user']['id_user'];

    $sql = "DELETE FROM sessions WHERE session_id = ? AND id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $session_id, $id_user);
    $stmt->execute();
}

// Distruggere la sessione
session_unset();
session_destroy();

// Eseguire il redirect con JavaScript per evitare problemi di output
echo "<script>window.location.href = '../index.php';</script>";
ob_end_flush(); // Fine output buffering

exit;

