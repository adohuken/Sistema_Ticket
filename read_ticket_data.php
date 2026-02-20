<?php
require 'c:/xampp/htdocs/Sistema_Ticket/conexion.php';
try {
    $stmt = $pdo->prepare('SELECT * FROM tickets WHERE id = ?');
    $stmt->execute([22]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($ticket) {
        print_r($ticket);
    } else {
        echo "Ticket #22 not found.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
