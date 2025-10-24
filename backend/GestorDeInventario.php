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
        $tipo = $this->conexion->real_escape_string(trim($tipo)); // Elimina espacios y escapa
        $cantidad = floatval($cantidad);

        // Validación estricta: no permitir tipo vacío o nulo
        if (empty($tipo)) {
            throw new Exception("El tipo de grano no puede estar vacío.");
        }

        $sql = "SELECT cantidad_kg FROM materia_prima WHERE tipo_grano = '$tipo'";
        $resultado = $this->conexion->query($sql);

        if ($resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            $cantidadActual = floatval($fila['cantidad_kg']);
            if ($cantidadActual + $cantidad < 0) {
                throw new Exception("La cantidad resultante no puede ser negativa.");
            }
            $sql = "UPDATE materia_prima SET cantidad_kg = cantidad_kg + $cantidad WHERE tipo_grano = '$tipo'";
        } else {
            if ($cantidad < 0) {
                throw new Exception("No se puede insertar una cantidad negativa sin registro previo.");
            }
            $sql = "INSERT INTO materia_prima (tipo_grano, cantidad_kg) VALUES ('$tipo', $cantidad)";
        }

        return $this->conexion->query($sql);
    }

    private function generarCodigoPedido() {
        $fecha = date('Ymd');
        $sql = "SELECT COUNT(*) as count FROM pedidos WHERE codigo_pedido LIKE 'PED-$fecha-%'";
        $resultado = $this->conexion->query($sql);
        $count = $resultado->fetch_assoc()['count'] + 1;
        return "PED-$fecha-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    public function crearPedido($detalles) {
        $codigo = $this->generarCodigoPedido();
        $fecha = date('Y-m-d H:i:s');

        $sql = "INSERT INTO pedidos (codigo_pedido, fecha, estado) VALUES ('$codigo', '$fecha', 'pendiente')";
        if ($this->conexion->query($sql)) {
            $id_pedido = $this->conexion->insert_id;
            foreach ($detalles as $tipo => $cantidad) {
                $tipo = $this->conexion->real_escape_string($tipo);
                $cantidad = floatval($cantidad);
                if ($cantidad <= 0) {
                    throw new Exception("La cantidad debe ser mayor a 0.");
                }
                $sql_detalle = "INSERT INTO pedidos_detalles (id_pedido, tipo_grano, cantidad_kg) VALUES ($id_pedido, '$tipo', $cantidad)";
                $this->conexion->query($sql_detalle);
            }
            return ["id" => $id_pedido, "codigo_pedido" => $codigo];
        } else {
            throw new Exception("Error al crear el pedido.");
        }
    }

    public function listarPedidos() {
        $sql = "SELECT p.id, p.codigo_pedido, p.fecha, p.estado FROM pedidos p WHERE p.estado = 'pendiente'";
        $resultado = $this->conexion->query($sql);

        $pedidos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $pedidos[$fila['id']] = $fila;
            $pedidos[$fila['id']]['detalles'] = $this->getDetallesPedido($fila['id']);
        }

        return $pedidos;
    }

    private function getDetallesPedido($id_pedido) {
        $sql = "SELECT tipo_grano, cantidad_kg FROM pedidos_detalles WHERE id_pedido = $id_pedido";
        $resultado = $this->conexion->query($sql);

        $detalles = [];
        while ($fila = $resultado->fetch_assoc()) {
            $detalles[$fila['tipo_grano']] = floatval($fila['cantidad_kg']);
        }

        return $detalles;
    }

    public function marcarEntregado($id_pedido) {
        $detalles = $this->getDetallesPedido($id_pedido);
        foreach ($detalles as $tipo => $cantidad) {
            $this->actualizarStock($tipo, -$cantidad);
        }

        $sql = "UPDATE pedidos SET estado = 'entregado' WHERE id = $id_pedido";
        return $this->conexion->query($sql);
    }
}
?>