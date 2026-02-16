<?php
$host = 'localhost';
$dbname = 'sistema_ticket';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Actualizando tabla usuarios ===\n";

    // Columnas a agregar
    $columnas = [
        'notifs_email' => "TINYINT(1) DEFAULT 0",
        'notifs_sonido' => "TINYINT(1) DEFAULT 1"
    ];

    foreach ($columnas as $col => $def) {
        $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE '$col'");
        if (!$stmt->fetch()) {
            echo "Agregando columna '$col'...\n";
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN $col $def");
            echo "✅ Columna agregada.\n";
        } else {
            echo "ℹ️ La columna '$col' ya existe.\n";
        }
    }

    echo "\n=== Proceso finalizado ===\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>