<?php
// ==========================================================
// CONFIGURACIÓN DE CONEXIÓN
// Las credenciales se leen de las variables de entorno de Azure App Service.
// ==========================================================

// Asegurarse de que el reporte de errores esté activado para la depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getConnectionDetails($db_type) {
    if ($db_type === 'mysql') {
        return [
            'host' => getenv('MYSQL_HOST'),
            'user' => getenv('MYSQL_USER'),
            'pass' => getenv('MYSQL_PASS'),
            'db'   => 'mysql' 
        ];
    } elseif ($db_type === 'pgsql') {
        return [
            'host' => getenv('PG_HOST'),
            'user' => getenv('PG_USER'),
            'pass' => getenv('PG_PASS'),
            'db'   => 'postgres' 
        ];
    }
    return null;
}

// ==========================================================
// FUNCIÓN DE PRUEBA DE CONEXIÓN
// ==========================================================
function testConnection($db_type) {
    $details = getConnectionDetails($db_type);
    if (!$details) {
        $msg = "<h3>Prueba: $db_type</h3><p>Resultado: <span style='color: orange; font-weight: bold;'>⚠️ VARIABLES NO CONFIGURADAS</span></p><hr>";
        echo $msg;
        return;
    }

    $host = $details['host'];
    $user = $details['user'];
    $db   = $details['db'];
    
    echo "<h3>Prueba de Conexión: $db_type</h3>";
    echo "<p>Host (DMZ->PRD vía VNet): <b>$host</b></p>";
    
    try {
        if ($db_type === 'mysql') {
            // MySQL usa el driver pdo_mysql
            $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $details['pass']);
        } elseif ($db_type === 'pgsql') {
            // PostgreSQL usa el driver pdo_pgsql
            $conn = new PDO("pgsql:host=$host;dbname=$db;user=$user;password={$details['pass']}");
        }
        
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $status = "<span style='color: green; font-weight: bold;'>✅ CONEXIÓN EXITOSA</span>";
        $conn = null;
    } catch (PDOException $e) {
        $status = "<span style='color: red; font-weight: bold;'>❌ CONEXIÓN FALLIDA</span>";
        $status .= "<br>Error: " . htmlspecialchars($e->getMessage());
    }
    echo "<p>Resultado: $status</p>";
    echo "<hr>";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validación de Conectividad VNet DMZ -> PRD</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; color: #333; margin: 20px; }
        .container { max-width: 800px; margin: auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        h1 { color: #0078d4; border-bottom: 2px solid #0078d4; padding-bottom: 10px; }
        h3 { color: #505050; }
        hr { border: none; border-top: 1px solid #eee; margin: 20px 0; }
        p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ejercicio 3: Conexión Segura Web App (DMZ)</h1>
        <h2>Validación de conectividad a Bases de Datos (PRD)</h2>
        
        <?php
            testConnection('mysql');
            testConnection('pgsql');
        ?>

        <p style="margin-top: 30px; font-size: small; color: #666;">
            Si ambas pruebas son exitosas, la **Integración de VNet** de la Web App funciona correctamente, y las reglas de NSG (si fueran necesarias) permiten el tráfico de salida de DMZ (10.1.x.x) a PRD (10.2.x.x) en los puertos 3306 y 5432.
        </p>
    </div>
</body>
</html>