<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'localhost:3306';
$usuario = 'root';
$contrasena = '';
$base_datos = 'cafegourmet_db';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $mensaje = '<div class="mensaje-registro error">Error: El formulario no fue enviado correctamente.</div>';
    } else if (!isset($_POST['login-email']) || !isset($_POST['login-password'])) {
        $mensaje = '<div class="mensaje-registro error">Error: Faltan datos del formulario.</div>';
    } else {
        $conexion = new PDO("mysql:host=$host;dbname=$base_datos", $usuario, $contrasena);
        $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conexion->exec("SET CHARACTER SET utf8");

        $email = $_POST['login-email'];
        $password = $_POST['login-password'];

        $sql = "SELECT contrasena FROM usuarios WHERE correo_electronico = :correo_electronico";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':correo_electronico' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['contrasena'])) {
            $mensaje = '<div class="mensaje-registro success">Inicio de sesión exitoso</div>';
        } else {
            $mensaje = '<div class="mensaje-registro error">Correo o contraseña incorrectos.</div>';
        }
    } // Cierre del bloque else
} // Cierre del bloque try

catch (PDOException $e) {
    header('Content-Type: text/html');
    echo '<div class="mensaje-registro error">Error en el inicio de sesión: ' . addslashes($e->getMessage()) . '</div>';
} catch (Exception $e) {
    header('Content-Type: text/html');
    echo '<div class="mensaje-registro error">Error general: ' . addslashes($e->getMessage()) . '</div>';
}

header('Content-Type: text/html');
echo $mensaje;
?>s