<?php
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

session_destroy();
header("Location: ../index.php");
exit;
?>