<?php
require_once 'conexion.php';

class GestorDeProduccion {
    private static $instancia = null;
    private $conexion;

    private function __construct() {
        $this->conexion = Conexion::obtenerConexion();
    }

    public static function obtenerInstancia() {
        if (self::$instancia === null) {
            self::$instancia = new GestorDeProduccion();
        }
        return self::$instancia;
    }

    // Mostrar inventario de materiales textiles
    public function mostrarMaterialesTextiles() {
        $sql = "SELECT tipo_material, cantidad_kg FROM materiales_textiles";
        $resultado = $this->conexion->query($sql);
        $materiales = [];
        while ($fila = $resultado->fetch_assoc()) {
            $materiales[$fila['tipo_material']] = floatval($fila['cantidad_kg']);
        }
        return $materiales;
    }

    // Actualizar stock de materiales textiles
    public function actualizarStockTextil($tipo, $cantidad) {
        $tipo = $this->conexion->real_escape_string($tipo);
        $cantidad = floatval($cantidad);
        // Lógica similar a actualizarStock en GestorDeInventario
        $sql = "SELECT cantidad_kg FROM materiales_textiles WHERE tipo_material = '$tipo'";
        $resultado = $this->conexion->query($sql);
        if ($resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            $cantidadActual = floatval($fila['cantidad_kg']);
            if ($cantidadActual + $cantidad < 0) {
                throw new Exception("La cantidad resultante no puede ser negativa.");
            }
            $sql = "UPDATE materiales_textiles SET cantidad_kg = cantidad_kg + $cantidad WHERE tipo_material = '$tipo'";
        } else {
            if ($cantidad < 0) {
                throw new Exception("No se puede insertar una cantidad negativa.");
            }
            $sql = "INSERT INTO materiales_textiles (tipo_material, cantidad_kg) VALUES ('$tipo', $cantidad)";
        }
        return $this->conexion->query($sql);
    }

    // Crear un lote de producción
    private function generarCodigoLote() {
        $fecha = date('Ymd');
        $sql = "SELECT COUNT(*) as count FROM lotes_produccion WHERE codigo_lote LIKE 'LOTE-$fecha-%'";
        $resultado = $this->conexion->query($sql);
        $count = $resultado->fetch_assoc()['count'] + 1;
        return "LOTE-$fecha-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    public function crearLote($tipo_empaque, $cantidad_planeada, $procesos) {
        $codigo = $this->generarCodigoLote();
        $sql = "INSERT INTO lotes_produccion (codigo_lote, tipo_empaque, cantidad_planeada) VALUES ('$codigo', '$tipo_empaque', $cantidad_planeada)";
        if ($this->conexion->query($sql)) {
            $id_lote = $this->conexion->insert_id;
            foreach ($procesos as $proceso) {
                $sql_proceso = "INSERT INTO procesos_lote (id_lote, proceso) VALUES ($id_lote, '$proceso')";
                $this->conexion->query($sql_proceso);
            }
            return ["id" => $id_lote, "codigo_lote" => $codigo];
        } else {
            throw new Exception("Error al crear el lote.");
        }
    }

    // Listar lotes de producción
    public function listarLotes() {
        $sql = "SELECT * FROM lotes_produccion";
        $resultado = $this->conexion->query($sql);
        $lotes = [];
        while ($fila = $resultado->fetch_assoc()) {
            $lotes[$fila['id']] = $fila;
            $lotes[$fila['id']]['procesos'] = $this->getProcesosLote($fila['id']);
        }
        return $lotes;
    }

    private function getProcesosLote($id_lote) {
        $sql = "SELECT proceso, cantidad_procesada, fecha_completado FROM procesos_lote WHERE id_lote = $id_lote";
        $resultado = $this->conexion->query($sql);
        $procesos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $procesos[] = $fila;
        }
        return $procesos;
    }

    // Marcar proceso como completado y actualizar stock
    public function completarProceso($id_lote, $proceso, $cantidad_procesada) {
        $sql = "UPDATE procesos_lote SET cantidad_procesada = $cantidad_procesada, fecha_completado = NOW() WHERE id_lote = $id_lote AND proceso = '$proceso'";
        if ($this->conexion->query($sql)) {
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
                // Actualizar lote a 'completado' y agregar a empaques terminados
                $sql_update = "UPDATE lotes_produccion SET estado = 'completado' WHERE id = $id_lote";
                $this->conexion->query($sql_update);
                $lote = $this->listarLotes()[$id_lote];
                $tipo_empaque = $lote['tipo_empaque'];
                $cantidad = $lote['cantidad_planeada'];
                $this->actualizarEmpaquesTerminados($tipo_empaque, $cantidad);
            }
            return true;
        }
        return false;
    }

    // Actualizar inventario de empaques terminados
    public function actualizarEmpaquesTerminados($tipo, $cantidad) {
        $tipo = $this->conexion->real_escape_string($tipo);
        $cantidad = intval($cantidad);
        $sql = "SELECT cantidad_unidades FROM empaques_terminados WHERE tipo_empaque = '$tipo'";
        $resultado = $this->conexion->query($sql);
        if ($resultado->num_rows > 0) {
            $sql = "UPDATE empaques_terminados SET cantidad_unidades = cantidad_unidades + $cantidad WHERE tipo_empaque = '$tipo'";
        } else {
            $sql = "INSERT INTO empaques_terminados (tipo_empaque, cantidad_unidades) VALUES ('$tipo', $cantidad)";
        }
        return $this->conexion->query($sql);
    }

    // Generar reporte (ejemplo: lotes completados)
    public function generarReporteLotes() {
        $sql = "SELECT codigo_lote, tipo_empaque, cantidad_planeada, estado, fecha_inicio, fecha_fin FROM lotes_produccion";
        $resultado = $this->conexion->query($sql);
        $reporte = [];
        while ($fila = $resultado->fetch_assoc()) {
            $reporte[] = $fila;
        }
        return $reporte;
    }

    // Más métodos para otros reportes (e.g., stock bajo, producción por mes)
}
?>