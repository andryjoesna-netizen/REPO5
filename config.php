<?php
// Configuración de conexión a MySQL.
// Por seguridad, cambia estas credenciales si fueron compartidas públicamente.
$host = 'mysql-andry.alwaysdata.net';
$user = 'andry';
$password = 'clase1234';
$database = 'andry_hamburgueseria';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $password, $database);
    $conn->set_charset('utf8mb4');

    // Crear la tabla automáticamente si todavía no existe.
    $conn->query("
        CREATE TABLE IF NOT EXISTS pedidos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            direccion VARCHAR(255) NOT NULL,
            cantidad_sencilla INT UNSIGNED NOT NULL DEFAULT 0,
            cantidad_doble INT UNSIGNED NOT NULL DEFAULT 0,
            cantidad_mixta INT UNSIGNED NOT NULL DEFAULT 0,
            estado ENUM('preparando', 'despachado') NOT NULL DEFAULT 'preparando',
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('No se pudo conectar a la base de datos. Verifica las credenciales y que la base de datos exista en el servidor.');
}
