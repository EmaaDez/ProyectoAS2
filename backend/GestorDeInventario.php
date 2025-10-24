<?php
require_once 'conexion.php'; 

class GestorDeInventario {
    private static $instancia = null; // Variable estática para implementar el patrón Singleton, asegurando una sola instancia de la clase.
    private $conexion; 

    private function __construct() {
        $this->conexion = Conexion::obtenerConexion(); 
    }

    public static function obtenerInstancia() {
        if (self::$instancia === null) { // Verifica si no existe una instancia, creando una nueva solo si es necesario.
            self::$instancia = new GestorDeInventario();
        }
        return self::$instancia; // Retorna la instancia única de la clase.
    }

    public function mostrarInventario() {
        $sql = "SELECT tipo_grano, cantidad_kg FROM materia_prima"; 
        $resultado = $this->conexion->query($sql);

        $inventario = []; // Array para almacenar el inventario como un mapa de tipo_grano -> cantidad_kg.
        while ($fila = $resultado->fetch_assoc()) { 
            $inventario[$fila['tipo_grano']] = floatval($fila['cantidad_kg']); 
        }

        return $inventario; // Retorna el array con el inventario completo.
    }

    public function actualizarStock($tipo, $cantidad) {
        $tipo = $this->conexion->real_escape_string(trim($tipo)); 
        $cantidad = floatval($cantidad); 
        if (empty($tipo)) { 
            throw new Exception("El tipo de grano no puede estar vacío."); 
        }

        $sql = "SELECT cantidad_kg FROM materia_prima WHERE tipo_grano = '$tipo'"; 
        $resultado = $this->conexion->query($sql); 

        if ($resultado->num_rows > 0) { // Si el tipo ya existe en la tabla.
            $fila = $resultado->fetch_assoc(); // Obtiene la fila correspondiente.
            $cantidadActual = floatval($fila['cantidad_kg']); // Convierte la cantidad actual a float.
            if ($cantidadActual + $cantidad < 0) { // Verifica que la nueva cantidad no sea negativa.
                throw new Exception("La cantidad resultante no puede ser negativa."); // Lanza excepción si es negativa.
            }
            $sql = "UPDATE materia_prima SET cantidad_kg = cantidad_kg + $cantidad WHERE tipo_grano = '$tipo'"; // Actualiza la cantidad existente.
        } else { // Si el tipo no existe.
            if ($cantidad < 0) { // Verifica que no se intente insertar una cantidad negativa sin registro previo.
                throw new Exception("No se puede insertar una cantidad negativa sin registro previo.");
            }
            $sql = "INSERT INTO materia_prima (tipo_grano, cantidad_kg) VALUES ('$tipo', $cantidad)"; // Inserta un nuevo registro.
        }

        return $this->conexion->query($sql); // Ejecuta la consulta SQL y retorna el resultado.
    }

    private function generarCodigoPedido() {
        $fecha = date('Ymd'); // Obtiene la fecha actual en formato AAAAMMDD.
        $sql = "SELECT COUNT(*) as count FROM pedidos WHERE codigo_pedido LIKE 'PED-$fecha-%'"; // Cuenta pedidos del día actual.
        $resultado = $this->conexion->query($sql); // Ejecuta la consulta.
        $count = $resultado->fetch_assoc()['count'] + 1; // Incrementa el contador de pedidos.
        return "PED-$fecha-" . str_pad($count, 3, '0', STR_PAD_LEFT); // Genera un código único (e.g., PED-20251024-001).
    }

    public function crearPedido($detalles) {
        $codigo = $this->generarCodigoPedido(); // Genera un código único para el pedido.
        $fecha = date('Y-m-d H:i:s'); // Obtiene la fecha y hora actual.

        $sql = "INSERT INTO pedidos (codigo_pedido, fecha, estado) VALUES ('$codigo', '$fecha', 'pendiente')"; // Inserta el pedido inicial.
        if ($this->conexion->query($sql)) { // Si la inserción del pedido es exitosa.
            $id_pedido = $this->conexion->insert_id; // Obtiene el ID del pedido recién creado.
            foreach ($detalles as $tipo => $cantidad) { // Itera sobre los detalles del pedido.
                $tipo = $this->conexion->real_escape_string($tipo); // Escapa el tipo.
                $cantidad = floatval($cantidad); // Convierte la cantidad a float.
                if ($cantidad <= 0) { // Valida que la cantidad sea positiva.
                    throw new Exception("La cantidad debe ser mayor a 0.");
                }
                $sql_detalle = "INSERT INTO pedidos_detalles (id_pedido, tipo_grano, cantidad_kg) VALUES ($id_pedido, '$tipo', $cantidad)"; // Inserta cada detalle.
                $this->conexion->query($sql_detalle); // Ejecuta la inserción del detalle.
            }
            return ["id" => $id_pedido, "codigo_pedido" => $codigo]; // Retorna el ID y código del pedido.
        } else {
            throw new Exception("Error al crear el pedido."); // Lanza excepción si falla la inserción.
        }
    }

    public function listarPedidos() {
        $sql = "SELECT p.id, p.codigo_pedido, p.fecha, p.estado FROM pedidos p WHERE p.estado = 'pendiente'"; // Consulta pedidos pendientes.
        $resultado = $this->conexion->query($sql); // Ejecuta la consulta.

        $pedidos = []; // Array para almacenar los pedidos.
        while ($fila = $resultado->fetch_assoc()) { // Itera sobre los resultados.
            $pedidos[$fila['id']] = $fila; // Almacena la fila con el ID como clave.
            $pedidos[$fila['id']]['detalles'] = $this->getDetallesPedido($fila['id']); // Agrega los detalles del pedido.
        }

        return $pedidos; // Retorna el array de pedidos.
    }

    private function getDetallesPedido($id_pedido) {
        $sql = "SELECT tipo_grano, cantidad_kg FROM pedidos_detalles WHERE id_pedido = $id_pedido"; // Consulta detalles del pedido.
        $resultado = $this->conexion->query($sql); // Ejecuta la consulta.

        $detalles = []; // Array para almacenar los detalles.
        while ($fila = $resultado->fetch_assoc()) { // Itera sobre los resultados.
            $detalles[$fila['tipo_grano']] = floatval($fila['cantidad_kg']); // Convierte y almacena la cantidad.
        }

        return $detalles; // Retorna los detalles del pedido.
    }

    public function marcarEntregado($id_pedido) {
        $detalles = $this->getDetallesPedido($id_pedido); // Obtiene los detalles del pedido.
        foreach ($detalles as $tipo => $cantidad) { // Itera sobre los detalles.
            $this->actualizarStock($tipo, -$cantidad); // Resta la cantidad del inventario.
        }

        $sql = "UPDATE pedidos SET estado = 'entregado' WHERE id = $id_pedido"; // Actualiza el estado del pedido.
        return $this->conexion->query($sql); // Ejecuta la actualización y retorna el resultado.
    }
}
?>