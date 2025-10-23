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
            $inventario[$fila['tipo_grano']] = floatval($fila['cantidad_kg']);
        }

        return $inventario;
    }

    public function actualizarStock($tipo, $cantidad) {
        $tipo = $this->conexion->real_escape_string($tipo);
        $cantidad = floatval($cantidad);

        // Verificar si el grano ya existe
        $sql = "SELECT cantidad_kg FROM materia_prima WHERE tipo_grano = '$tipo'";
        $resultado = $this->conexion->query($sql);

        if ($resultado->num_rows > 0) {
            // Actualizar cantidad existente
            $sql = "UPDATE materia_prima SET cantidad_kg = cantidad_kg + $cantidad WHERE tipo_grano = '$tipo'";
        } else {
            // Insertar nuevo tipo de grano
            if ($cantidad < 0) {
                throw new Exception("No se puede insertar una cantidad negativa para un nuevo tipo de grano.");
            }
            $sql = "INSERT INTO materia_prima (tipo_grano, cantidad_kg) VALUES ('$tipo', $cantidad)";
        }

        // Verificar que la cantidad final no sea negativa
        if ($resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            $cantidadActual = floatval($fila['cantidad_kg']);
            if ($cantidadActual + $cantidad < 0) {
                throw new Exception("La cantidad resultante no puede ser negativa.");
            }
        }

        return $this->conexion->query($sql);
    }
}
?>
