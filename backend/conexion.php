<?php
class Conexion {
    private static $instancia = null;

    public static function obtenerConexion() {
        if (self::$instancia === null) {
            $host = "localhost:3306";
            $usuario = "root";
            $password = ""; // cambia si tu usuario tiene clave
            $baseDeDatos = "cafegourmet_db";

            self::$instancia = new mysqli($host, $usuario, $password, $baseDeDatos);

            if (self::$instancia->connect_error) {
                die("Error de conexión: " . self::$instancia->connect_error);
            }
        }
        return self::$instancia;
    }
}
?>
