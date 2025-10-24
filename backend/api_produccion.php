<?php
header('Content-Type: application/json');
require_once '../backend/GestorDeProduccion.php';

$produccion = GestorDeProduccion::obtenerInstancia();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'lotes';
    switch ($action) {
        case 'materiales_textiles':
            echo json_encode($produccion->mostrarMaterialesTextiles());
            break;
        case 'lotes':
            echo json_encode($produccion->listarLotes());
            break;
        case 'reporte_lotes':
            echo json_encode($produccion->generarReporteLotes());
            break;
        default:
            http_response_code(400);
            echo json_encode(["error" => "Acción inválida"]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? null;

    try {
        switch ($action) {
            case 'actualizar_stock_textil':
                $tipo = $data['tipo'] ?? null;
                $cantidad = $data['cantidad'] ?? null;
                if ($tipo && $cantidad !== null) {
                    $produccion->actualizarStockTextil($tipo, $cantidad);
                    echo json_encode(["mensaje" => "Stock textil actualizado", "materiales" => $produccion->mostrarMaterialesTextiles()]);
                } else {
                    throw new Exception("Datos inválidos");
                }
                break;
            case 'crear_lote':
                $tipo_empaque = $data['tipo_empaque'] ?? null;
                $cantidad_planeada = $data['cantidad_planeada'] ?? null;
                $procesos = $data['procesos'] ?? [];  // Array e.g., ['tejido', 'teñido', 'corte', 'confeccion']
                if ($tipo_empaque && $cantidad_planeada && !empty($procesos)) {
                    $resultado = $produccion->crearLote($tipo_empaque, $cantidad_planeada, $procesos);
                    echo json_encode(["mensaje" => "Lote creado", "lote" => $resultado]);
                } else {
                    throw new Exception("Datos inválidos para crear lote");
                }
                break;
            case 'completar_proceso':
                $id_lote = $data['id_lote'] ?? null;
                $proceso = $data['proceso'] ?? null;
                $cantidad_procesada = $data['cantidad_procesada'] ?? null;
                if ($id_lote && $proceso && $cantidad_procesada !== null) {
                    $produccion->completarProceso($id_lote, $proceso, $cantidad_procesada);
                    echo json_encode(["mensaje" => "Proceso completado"]);
                } else {
                    throw new Exception("Datos inválidos");
                }
                break;
            default:
                throw new Exception("Acción no especificada");
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(["error" => $e->getMessage()]);
    }
}
?>