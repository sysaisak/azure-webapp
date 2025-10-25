<?php
// ==========================================================
// CONFIGURACIÓN DE CONEXIÓN Y UTILIDADES
// ==========================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getConnectionDetails($db_type) {
    // Lee las variables de entorno de Azure App Service (Configuración de la Aplicación)
    // Las credenciales deben estar configuradas en el portal.
    if ($db_type === 'mysql') {
        return [
            'host' => getenv('MYSQL_HOST'),
            'user' => getenv('MYSQL_USER'),
            'pass' => getenv('MYSQL_PASS'),
            'db'   => 'animes' // La BD donde debe estar la tabla ANIME en MySQL
        ];
    } elseif ($db_type === 'pgsql') {
        return [
            'host' => getenv('PG_HOST'),
            'user' => getenv('PG_USER'),
            'pass' => getenv('PG_PASS'),
            'db'   => 'animes' // La BD donde debe estar la tabla ANIME en PostgreSQL
        ];
    }
    return null;
}

// ==========================================================
// FUNCIÓN PRINCIPAL DE PRUEBA Y EXTRACCIÓN DE DATOS
// ==========================================================
function testConnectionAndData($db_type, $table_name, $db_name) {
    $details = getConnectionDetails($db_type);
    if (!$details) return "<h3>Prueba: $db_type</h3><p style='color: orange;'>⚠️ VARIABLES DE ENTORNO NO CONFIGURADAS.</p><hr>";

    $host = $details['host'];
    $user = $details['user'];
    
    echo "<h3>Prueba de Conexión: $db_type</h3>";
    echo "<p>Host (DMZ->PRD vía VNet): <b>$host</b></p>";
    echo "<p>Base de Datos: <b>$db_name</b></p>";

    try {
        if ($db_type === 'mysql') {
            // Conectar especificando la Base de Datos
            $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $user, $details['pass']);
        } elseif ($db_type === 'pgsql') {
            // Conectar especificando la Base de Datos
            $conn = new PDO("pgsql:host=$host;dbname=$db_name;user=$user;password={$details['pass']}");
        }

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $status = "<p style='color: green; font-weight: bold;'>✅ CONEXIÓN EXITOSA</p>";
        echo $status;

        // --- Extracción y Muestra de Datos ---
        // PostgreSQL es sensible a las mayúsculas/minúsculas de la tabla si no se usan comillas. 
        // Usamos comillas dobles para forzar el nombre en mayúsculas 'ANIME' si es necesario.
        $query = ($db_type === 'pgsql') ? "SELECT * FROM \"$table_name\"" : "SELECT * FROM $table_name";
        
        $stmt = $conn->query($query);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            echo "<p style='color: red;'>❌ Tabla '$table_name' existe, pero no contiene registros.</p>";
        } else {
            echo "<p style='color: green;'>✅ Datos encontrados en la tabla '$table_name' (" . count($results) . " registros):</p>";
            echo "<table border='1' style='border-collapse: collapse; font-size: small;'>";
            echo "<thead><tr>";
            foreach (array_keys($results[0]) as $col) {
                echo "<th style='padding: 5px;'>$col</th>";
            }
            echo "</tr></thead><tbody>";
            foreach ($results as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td style='padding: 5px;'>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</tbody></table>";
        }

        $conn = null;

    } catch (PDOException $e) {
        // Manejo de errores de conexión/DNS/Credenciales/SQL
        $status = "<p style='color: red; font-weight: bold;'>❌ CONEXIÓN FALLIDA / ERROR SQL</p>";
        $error_message = $e->getMessage();
        $status .= "<p>Error: " . htmlspecialchars($error_message) . "</p>";
    }
    echo "<hr>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VALIDACIÓN FINAL - DMZ -> PRD</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #e9ecef; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: auto; padding: 30px; background: #ffffff; border-radius: 10px; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1); }
        h1 { color: #0078d4; border-bottom: 3px solid #0078d4; padding-bottom: 10px; margin-top: 0; }
        h2 { color: #505050; }
        hr { border: none; border-top: 1px dashed #ccc; margin: 25px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ejercicio 3: Conexión Operativa y Accesibilidad Segura (Validación Final)</h1>
        <h2>Flujo de Tráfico: Web App (DMZ) &rarr; Bases de Datos (PRD)</h2>

        <?php
            // Prueba 1: Conexión a MySQL y Tabla ANIME
            testConnectionAndData('mysql', 'ANIME', 'animes'); 

            // Prueba 2: Conexión a PostgreSQL y Tabla ANIME
            testConnectionAndData('pgsql', 'ANIME', 'animes');
        ?>

        <hr>
        <p style="font-size: small; color: #0078d4;">
            **VALIDACIÓN DE ÉXITO:** Si ambas pruebas muestran **CONEXIÓN EXITOSA** y **Datos encontrados**, se cumplen los objetivos de Conexión Operativa y Accesibilidad Segura.
        </p>
    </div>
</body>
</html>
