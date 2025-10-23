<?php
header('Content-Type: application/json');
require_once '../backend/GestorDeInventario.php';

$inventario = GestorDeInventario::obtenerInstancia();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'inventario';
    switch ($action) {
        case 'inventario':
            echo json_encode($inventario->mostrarInventario());
            break;
        case 'pedidos':
            echo json_encode($inventario->listarPedidos());
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
            case 'actualizar_stock':
                $tipo = $data['tipo'] ?? null;
                $cantidad = $data['cantidad'] ?? null;
                if ($tipo && $cantidad !== null) {
                    $inventario->actualizarStock($tipo, $cantidad);
                    echo json_encode(["mensaje" => "Stock actualizado", "inventario" => $inventario->mostrarInventario()]);
                } else {
                    throw new Exception("Datos inválidos para actualizar stock");
                }
                break;
            case 'crear_pedido':
                $detalles = $data['detalles'] ?? [];
                if (empty($detalles)) {
                    throw new Exception("Debe especificar al menos un grano");
                }
                $resultado = $inventario->crearPedido($detalles);
                echo json_encode(["mensaje" => "Pedido creado", "pedido" => $resultado]);
                break;
            case 'marcar_entregado':
                $id_pedido = $data['id_pedido'] ?? null;
                if ($id_pedido) {
                    $inventario->marcarEntregado($id_pedido);
                    echo json_encode(["mensaje" => "Pedido entregado", "inventario" => $inventario->mostrarInventario()]);
                } else {
                    throw new Exception("ID de pedido requerido");
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