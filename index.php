<?php
// ==========================================================
// CONFIGURACIÓN DE CONEXIÓN Y UTILIDADES
// ==========================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getConnectionDetails($db_type) {
    // Lee las variables de entorno de Azure App Service (Configuración de la Aplicación)
    if ($db_type === 'mysql') {
        return [
            'host' => getenv('MYSQL_HOST'),
            'user' => getenv('MYSQL_USER'),
            'pass' => getenv('MYSQL_PASS'),
            'db'   => 'animes' // Usar la BD 'animes' creada
        ];
    } elseif ($db_type === 'pgsql') {
        return [
            'host' => getenv('PG_HOST'),
            'user' => getenv('PG_USER'),
            'pass' => getenv('PG_PASS'),
            // Usar la BD 'animes' creada. Si no existe, usar 'postgres' por defecto.
            'db'   => 'animes' 
        ];
    }
    return null;
}

// ==========================================================
// FUNCIÓN PRINCIPAL DE PRUEBA Y EXTRACCIÓN DE DATOS
// ==========================================================
function testConnectionAndData($db_type, $table_name, $db_name_override = null) {
    $details = getConnectionDetails($db_type);
    if (!$details) return "<h3>Prueba: $db_type</h3><p style='color: orange;'>⚠️ VARIABLES DE ENTORNO NO CONFIGURADAS.</p><hr>";

    $host = $details['host'];
    $user = $details['user'];
    $db   = $db_name_override ?: $details['db'];

    echo "<h3>Prueba de Conexión: $db_type</h3>";
    echo "<p>Host (DMZ->PRD vía VNet): <b>$host</b></p>";
    echo "<p>Base de Datos: <b>$db</b></p>";

    try {
        if ($db_type === 'mysql') {
            $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $details['pass']);
        } elseif ($db_type === 'pgsql') {
            $conn = new PDO("pgsql:host=$host;dbname=$db;user=$user;password={$details['pass']}");
        }

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $status = "<p style='color: green; font-weight: bold;'>✅ CONEXIÓN EXITOSA</p>";
        echo $status;

        // --- Extracción y Muestra de Datos ---
        $stmt = $conn->query("SELECT * FROM $table_name");
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
        $status = "<p style='color: red; font-weight: bold;'>❌ CONEXIÓN FALLIDA / ERROR SQL</p>";
        $error_message = $e->getMessage();

        if (strpos($error_message, '2002') !== false || strpos($error_message, 'could not translate host name') !== false) {
             $status .= "<p style='color: red;'>ERROR DE DNS: La App no puede resolver el nombre del host. Verifica los Vínculos DNS Privados.</p>";
        } elseif (strpos($error_message, 'Access denied') !== false) {
             $status .= "<p style='color: red;'>ERROR DE CREDENCIALES: Verifica usuario/contraseña en la Configuración de la App.</p>";
        } elseif (strpos($error_message, "Unknown database '$db'") !== false) {
             $status .= "<p style='color: red;'>ERROR DE BASE DE DATOS: La BD '$db' no existe. Créala desde la VM de prueba.</p>";
        } else {
             $status .= "<p>Error: " . htmlspecialchars($error_message) . "</p>";
        }
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
        p { margin: 8px 0; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ejercicio 3: Conexión Operativa y Accesibilidad Segura (Validación Final)</h1>
        <h2>Flujo de Tráfico: Web App (DMZ) &rarr; Bases de Datos (PRD)</h2>

        <?php
            // Prueba 1: Conexión a MySQL y Tabla ANIME
            testConnectionAndData('mysql', 'ANIME');

            // Prueba 2: Conexión a PostgreSQL y Tabla ANIME
            // (Asumimos la BD es 'animes' para esta tabla)
            testConnectionAndData('pgsql', 'ANIME', 'animes');

            // Prueba 3: Conexión a PostgreSQL y Tabla estudiantes
            // (Asumimos la BD es 'estudiantes' para esta tabla)
            testConnectionAndData('pgsql', 'estudiantes', 'estudiantes');
        ?>

        <hr>
        <p style="font-size: small; color: #0078d4;">
            **VALIDACIÓN DE ÉXITO:** Si las tres pruebas muestran **CONEXIÓN EXITOSA** y **Datos encontrados**, se cumplen los objetivos de Conexión Operativa, Accesibilidad Segura y Persistencia de Datos.
        </p>
    </div>
</body>
</html>