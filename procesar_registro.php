<?php
// Habilitar la visualización de errores para depuración (quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuración de la conexión a la base de datos
$host = 'localhost:3306'; 
$usuario = 'root';
$contrasena = ''; // Sin contraseña, coincide con phpMyAdmin
$base_datos = 'cafegourmet_db';

try {
    // Verificar si el formulario fue enviado
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $mensaje = '<div class="mensaje-registro error">Error: El formulario no fue enviado correctamente.</div>';
    } else if (!isset($_POST['register-name']) || !isset($_POST['register-email']) || !isset($_POST['register-password']) || !isset($_POST['confirm-password'])) {
        $mensaje = '<div class="mensaje-registro error">Error: Faltan datos del formulario.</div>';
    } else {
        // Conexión usando PDO
        $conexion = new PDO("mysql:host=$host;dbname=$base_datos", $usuario, $contrasena);
        $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conexion->exec("SET CHARACTER SET utf8");

        // Obtener datos del formulario
        $nombre = $_POST['register-name'];
        $email = $_POST['register-email'];
        $contrasena = $_POST['register-password'];
        $confirmar_contrasena = $_POST['confirm-password'];

        // Validar que las contraseñas coincidan
        if ($contrasena !== $confirmar_contrasena) {
            $mensaje = '<div class="mensaje-registro error">Las contraseñas no coinciden. Por favor, inténtalo de nuevo.</div>';
        } else {
            // Encriptar la contraseña
            $contrasena_encriptada = password_hash($contrasena, PASSWORD_BCRYPT);

            // Verificar si el correo ya existe
            $sql_check = "SELECT correo_electronico FROM usuarios WHERE correo_electronico = :correo_electronico";
            $stmt_check = $conexion->prepare($sql_check);
            $stmt_check->execute([':correo_electronico' => $email]);
            if ($stmt_check->rowCount() > 0) {
                $mensaje = '<div class="mensaje-registro error">El correo electrónico ya está registrado. Por favor, utiliza otro.</div>';
            } else {
                // Insertar datos en la tabla
                $sql = "INSERT INTO usuarios (nombre_completo, correo_electronico, contrasena) 
                        VALUES (:nombre_completo, :correo_electronico, :contrasena)";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    ':nombre_completo' => $nombre,
                    ':correo_electronico' => $email,
                    ':contrasena' => $contrasena_encriptada
                ]);
                $mensaje = '<div class="mensaje-registro success">Registro exitoso. ¡Bienvenido a Café Gourmet!</div>';
            }
        }
    }

    // Devolver el mensaje como HTML
    header('Content-Type: text/html');
    echo $mensaje;

} catch (PDOException $e) {
    header('Content-Type: text/html');
    echo '<div class="mensaje-registro error">Error en el registro: ' . addslashes($e->getMessage()) . '</div>';
} catch (Exception $e) {
    header('Content-Type: text/html');
    echo '<div class="mensaje-registro error">Error general: ' . addslashes($e->getMessage()) . '</div>';
}
?>