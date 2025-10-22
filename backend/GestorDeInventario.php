<?php
require_once 'conexion.php';

class GestorDeInventario {
    private static $instancia = null;
    private $conexion;

    private function __construct() {
        $this->conexion = Conexion::obtenerConexion();
    }

    public static function obtenerInstancia() {
        if (self::$instancia === null) {
            self::$instancia = new GestorDeInventario();
        }
        return self::$instancia;
    }

    public function mostrarInventario() {
        $sql = "SELECT tipo_grano, cantidad_kg FROM materia_prima";
        $resultado = $this->conexion->query($sql);

        $inventario = [];
        while ($fila = $resultado->fetch_assoc()) {
            $inventario[$fila['tipo_grano']] = $fila['cantidad_kg'];
        }

        return $inventario;
    }
}
?>
