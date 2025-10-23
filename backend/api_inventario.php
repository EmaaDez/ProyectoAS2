<?php
header('Content-Type: application/json');
require_once '../backend/GestorDeInventario.php';

// Obtener instancia del Singleton
$inventario = GestorDeInventario::obtenerInstancia();

// Si se pide el inventario completo
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode($inventario->mostrarInventario());
    exit;
}

// Si se desea actualizar stock
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $tipo = $data['tipo'] ?? null;
    $cantidad = $data['cantidad'] ?? null;

    try {
        if ($tipo && $cantidad !== null) {
            $inventario->actualizarStock($tipo, $cantidad);
            echo json_encode(["mensaje" => "Stock actualizado", "inventario" => $inventario->mostrarInventario()]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Datos inválidos"]);
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(["error" => $e->getMessage()]);
    }
}
?>