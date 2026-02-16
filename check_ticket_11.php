<?php
require 'conexion.php';
$id = 11;
$stmt = $pdo->prepare("SELECT id, estado FROM tickets WHERE id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($ticket);
