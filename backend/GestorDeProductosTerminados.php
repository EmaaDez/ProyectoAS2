<?php
require_once 'conexion.php';
require_once 'GestorDeInventario.php';  // Para restar materia prima (granos)
require_once 'GestorDeProduccion.php';  // Para restar empaques en envasado (opcional)

class GestorDeProductosTerminados {
    private static $instancia = null;
    private $conexion;

    private function __construct() {
        $this->conexion = Conexion::obtenerConexion();
    }

    public static function obtenerInstancia() {
        if (self::$instancia === null) {
            self::$instancia = new GestorDeProductosTerminados();
        }
        return self::$instancia;
    }

    // Mostrar inventario de productos terminados
    public function mostrarProductosTerminados() {
        $sql = "SELECT tipo_cafe, cantidad_kg FROM productos_terminados";
        $resultado = $this->conexion->query($sql);
        $productos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $productos[$fila['tipo_cafe']] = floatval($fila['cantidad_kg']);
        }
        return $productos;
    }

    // Actualizar stock de productos terminados
    public function actualizarStockProducto($tipo, $cantidad) {
        $tipo = $this->conexion->real_escape_string($tipo);
        $cantidad = floatval($cantidad);
        $sql = "SELECT cantidad_kg FROM productos_terminados WHERE tipo_cafe = '$tipo'";
        $resultado = $this->conexion->query($sql);
        if ($resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            $cantidadActual = floatval($fila['cantidad_kg']);
            if ($cantidadActual + $cantidad < 0) {
                throw new Exception("La cantidad resultante no puede ser negativa.");
            }
            $sql = "UPDATE productos_terminados SET cantidad_kg = cantidad_kg + $cantidad WHERE tipo_cafe = '$tipo'";
        } else {
            if ($cantidad < 0) {
                throw new Exception("No se puede insertar una cantidad negativa.");
            }
            $sql = "INSERT INTO productos_terminados (tipo_cafe, cantidad_kg) VALUES ('$tipo', $cantidad)";
        }
        return $this->conexion->query($sql);
    }

    // Generar código de lote
    private function generarCodigoLote() {
        $fecha = date('Ymd');
        $sql = "SELECT COUNT(*) as count FROM lotes_productos WHERE codigo_lote LIKE 'PROD-$fecha-%'";
        $resultado = $this->conexion->query($sql);
        $count = $resultado->fetch_assoc()['count'] + 1;
        return "PROD-$fecha-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    // Crear un lote de productos
    public function crearLote($tipo_cafe, $cantidad_planeada, $procesos) {
        $codigo = $this->generarCodigoLote();
        $fecha_produccion = date('Y-m-d');
        $fecha_vencimiento = date('Y-m-d', strtotime($fecha_produccion . ' + 6 months'));
        $sql = "INSERT INTO lotes_productos (codigo_lote, tipo_cafe, cantidad_kg, fecha_produccion, fecha_vencimiento) VALUES ('$codigo', '$tipo_cafe', $cantidad_planeada, '$fecha_produccion', '$fecha_vencimiento')";
        if ($this->conexion->query($sql)) {
            $id_lote = $this->conexion->insert_id;
            foreach ($procesos as $proceso) {
                $sql_proceso = "INSERT INTO procesos_productos (id_lote, proceso) VALUES ($id_lote, '$proceso')";
                $this->conexion->query($sql_proceso);
            }
            return ["id" => $id_lote, "codigo_lote" => $codigo];
        } else {
            throw new Exception("Error al crear el lote.");
        }
    }

    // Listar lotes de productos (para monitoreo)
    public function listarLotes() {
        $sql = "SELECT * FROM lotes_productos";
        $resultado = $this->conexion->query($sql);
        $lotes = [];
        while ($fila = $resultado->fetch_assoc()) {
            $lotes[$fila['id']] = $fila;
            $lotes[$fila['id']]['procesos'] = $this->getProcesosLote($fila['id']);
        }
        return $lotes;
    }

    private function getProcesosLote($id_lote) {
        $sql = "SELECT proceso, cantidad_procesada, fecha_completado FROM procesos_productos WHERE id_lote = $id_lote";
        $resultado = $this->conexion->query($sql);
        $procesos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $procesos[] = $fila;
        }
        return $procesos;
    }

    // Completar proceso y actualizar stocks (integra con inventario de granos y empaques)
    public function completarProceso($id_lote, $proceso, $cantidad_procesada) {
        $inventario = GestorDeInventario::obtenerInstancia();
        $produccion = GestorDeProduccion::obtenerInstancia();  // Para empaques si es envasado

        $sql = "SELECT tipo_cafe, cantidad_kg FROM lotes_productos WHERE id = $id_lote";
        $resultado = $this->conexion->query($sql);
        $lote = $resultado->fetch_assoc();
        $tipo_cafe = $lote['tipo_cafe'];
        $cantidad_planeada = floatval($lote['cantidad_kg']);

        // Asumir consumo: e.g., 1 kg de granos crudos produce 0.8 kg tostado/molido (ajustable)
        $consumo_granos = $cantidad_procesada;  // Simplificado: 1:1 para tostado/molido
        if ($proceso === 'tostado' || $proceso === 'molido') {
            // Restar granos de materia prima (asumir tipo de grano basado en tipo_cafe)
            $tipo_grano = explode(' ', $tipo_cafe)[0];  // e.g., 'Arábica' de 'Arábica Tostado'
            $inventario->actualizarStock($tipo_grano, -$consumo_granos);
        } elseif ($proceso === 'envasado') {
            // Restar empaques (asumir 1 unidad de empaque por kg de café)
            $tipo_empaque = 'Bolsa de 1kg';  // Ajustable
            $produccion->actualizarEmpaquesTerminados($tipo_empaque, -intval($cantidad_procesada));
        }

        $sql_update = "UPDATE procesos_productos SET cantidad_procesada = $cantidad_procesada, fecha_completado = NOW() WHERE id_lote = $id_lote AND proceso = '$proceso'";
        if ($this->conexion->query($sql_update)) {
            // Verificar si todos los procesos están completados
            $procesos = $this->getProcesosLote($id_lote);
            $completados = true;
            foreach ($procesos as $p) {
                if ($p['fecha_completado'] === null) {
                    $completados = false;
                    break;
                }
            }
            if ($completados) {
                $sql_complete = "UPDATE lotes_productos SET estado = 'completado' WHERE id = $id_lote";
                $this->conexion->query($sql_complete);
                $this->actualizarStockProducto($tipo_cafe, $cantidad_planeada);
            }
            return true;
        }
        return false;
    }

    // Generar reporte de lotes
    public function generarReporteLotes() {
        $sql = "SELECT codigo_lote, tipo_cafe, cantidad_kg, estado, fecha_produccion, fecha_vencimiento FROM lotes_productos";
        $resultado = $this->conexion->query($sql);
        $reporte = [];
        while ($fila = $resultado->fetch_assoc()) {
            $reporte[] = $fila;
        }
        return $reporte;
    }
}
?>