<?php
header('Content-Type: application/json'); // Establece el tipo de contenido de la respuesta como JSON, indicando que el output será en formato JSON.
require_once '../backend/GestorDeInventario.php'; // Incluye el archivo de la clase GestorDeInventario, necesario para utilizar sus métodos.

$inventario = GestorDeInventario::obtenerInstancia(); // Obtiene la instancia única de la clase GestorDeInventario usando el patrón Singleton.

if ($_SERVER['REQUEST_METHOD'] === 'GET') { // Verifica si la solicitud es de tipo GET.
    $action = $_GET['action'] ?? 'inventario'; // Obtiene el parámetro 'action' de la URL, con valor por defecto 'inventario'.
    switch ($action) { // Evalúa la acción solicitada.
        case 'inventario':
            echo json_encode($inventario->mostrarInventario()); // Devuelve el inventario completo en formato JSON.
            break;
        case 'pedidos':
            echo json_encode($inventario->listarPedidos()); // Devuelve la lista de pedidos pendientes en formato JSON.
            break;
        default:
            http_response_code(400); // Establece el código de estado HTTP a 400 (Bad Request) para acciones no válidas.
            echo json_encode(["error" => "Acción inválida"]); // Devuelve un mensaje de error en JSON.
    }
    exit; // Termina la ejecución después de procesar la solicitud GET.
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Verifica si la solicitud es de tipo POST.
    $data = json_decode(file_get_contents('php://input'), true); // Decodifica el cuerpo de la solicitud JSON en un array asociativo.
    $action = $data['action'] ?? null; // Obtiene la acción del cuerpo de la solicitud, con valor por defecto null.

    try { // Inicia un bloque try-catch para manejar excepciones.
        switch ($action) { // Evalúa la acción solicitada en el POST.
            case 'actualizar_stock':
                $tipo = $data['tipo'] ?? null; // Obtiene el tipo de grano del cuerpo JSON.
                $cantidad = $data['cantidad'] ?? null; // Obtiene la cantidad del cuerpo JSON.
                if ($tipo && $cantidad !== null) { // Valida que tanto tipo como cantidad estén presentes.
                    $inventario->actualizarStock($tipo, $cantidad); // Actualiza el stock en la base de datos.
                    echo json_encode(["mensaje" => "Stock actualizado", "inventario" => $inventario->mostrarInventario()]); // Devuelve un mensaje de éxito y el inventario actualizado.
                } else {
                    throw new Exception("Datos inválidos para actualizar stock"); // Lanza excepción si los datos son inválidos.
                }
                break;
            case 'crear_pedido':
                $detalles = $data['detalles'] ?? []; // Obtiene los detalles del pedido (array de tipo -> cantidad).
                if (empty($detalles)) { // Verifica que se haya especificado al menos un grano.
                    throw new Exception("Debe especificar al menos un grano"); // Lanza excepción si no hay detalles.
                }
                $resultado = $inventario->crearPedido($detalles); // Crea un nuevo pedido con los detalles.
                echo json_encode(["mensaje" => "Pedido creado", "pedido" => $resultado]); // Devuelve un mensaje de éxito y los datos del pedido.
                break;
            case 'marcar_entregado':
                $id_pedido = $data['id_pedido'] ?? null; // Obtiene el ID del pedido del cuerpo JSON.
                if ($id_pedido) { // Valida que el ID esté presente.
                    $inventario->marcarEntregado($id_pedido); // Marca el pedido como entregado, actualizando el inventario.
                    echo json_encode(["mensaje" => "Pedido entregado", "inventario" => $inventario->mostrarInventario()]); // Devuelve un mensaje de éxito y el inventario actualizado.
                } else {
                    throw new Exception("ID de pedido requerido"); // Lanza excepción si no se proporciona el ID.
                }
                break;
            default:
                throw new Exception("Acción no especificada"); // Lanza excepción para acciones no reconocidas.
        }
    } catch (Exception $e) { // Captura cualquier excepción lanzada.
        http_response_code(400); // Establece el código de estado HTTP a 400.
        echo json_encode(["error" => $e->getMessage()]); // Devuelve el mensaje de error en formato JSON.
    }
}
?>