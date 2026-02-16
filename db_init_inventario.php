<?php
// db_init_inventario.php
$host = 'localhost';
$dbname = 'sistema_ticket';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Crear tabla inventario
    $sql = "CREATE TABLE IF NOT EXISTS inventario (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo ENUM('Laptop', 'PC', 'Monitor', 'Teclado', 'Mouse', 'Headset', 'Silla', 'Escritorio', 'Movil', 'Impresora', 'Otro') NOT NULL,
        marca VARCHAR(100),
        modelo VARCHAR(100),
        serial VARCHAR(100) UNIQUE,
        estado ENUM('Nuevo', 'Buen Estado', 'Regular', 'Malo', 'En Reparacion') DEFAULT 'Nuevo',
        condicion ENUM('Disponible', 'Asignado', 'Dado de Baja') DEFAULT 'Disponible',
        asignado_a VARCHAR(200) DEFAULT NULL COMMENT 'Nombre del colaborador o ID usuario',
        fecha_compra DATE NULL,
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "Tabla 'inventario' creada o verificada correctamente.\n";

    // Insertar algunos datos de prueba si está vacía
    $stmt = $pdo->query("SELECT COUNT(*) FROM inventario");
    if ($stmt->fetchColumn() == 0) {
        $sql_insert = "INSERT INTO inventario (tipo, marca, modelo, serial, estado, condicion) VALUES 
        ('Laptop', 'Dell', 'Latitude 5420', 'SN-DELL-001', 'Nuevo', 'Disponible'),
        ('Monitor', 'Samsung', '24 Inch Curved', 'SN-SAM-001', 'Nuevo', 'Disponible'),
        ('Silla', 'Herman Miller', 'Aeron', 'SN-HM-001', 'Buen Estado', 'Disponible'),
        ('Laptop', 'HP', 'ProBook 450', 'SN-HP-002', 'Regular', 'Disponible'),
        ('Movil', 'Samsung', 'Galaxy A54', 'SN-MOB-001', 'Nuevo', 'Disponible')";

        $pdo->exec($sql_insert);
        echo "Datos de prueba insertados en 'inventario'.\n";
    }

} catch (PDOException $e) {
    die("Error DB: " . $e->getMessage());
}
?>