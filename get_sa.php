<?php
require 'conexion.php';
$stmt = $pdo->query("SELECT correo, password, rol FROM usuarios WHERE rol='SuperAdmin' LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
echo "User: " . $user['correo'] . "\n";
echo "Pass: " . $user['password'] . "\n";
