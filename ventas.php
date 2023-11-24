<?php

require_once 'headers.php';

$conn = new mysqli('localhost', 'root', '', 'fergies');

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

function sendJsonResponse($status, $data, $message = '') {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'data' => $data, 'message' => $message]);
    exit;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $queryVentas = "SELECT ventas.*, empleados.nombre AS nombreEmpleado, clientes.nombre AS nombreCliente 
                    FROM ventas 
                    JOIN empleados ON ventas.idEmpleado = empleados.idEmpleado 
                    JOIN clientes ON ventas.idCliente = clientes.idCliente";
        $resultVentas = $conn->query($queryVentas);
        $ventas = [];
        while ($venta = $resultVentas->fetch_assoc()) {
            $idVenta = $venta['idVenta'];
            $queryDetalles = "SELECT detalleventa.*, productos.nombre AS nombreProducto 
                          FROM detalleventa 
                          JOIN productos ON detalleventa.idProducto = productos.idProducto 
                          WHERE detalleventa.idVenta = '$idVenta'";
            $resultDetalles = $conn->query($queryDetalles);
            $detalles = [];
            while ($detalle = $resultDetalles->fetch_assoc()) {
                $detalles[] = $detalle;
            }
            $venta['detalles'] = $detalles;
            $ventas[] = $venta;
        }
        sendJsonResponse('success', $ventas);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'));
        if (empty($data->idEmpleado) || empty($data->idCliente) || empty($data->fecha) || empty($data->total)) {
            sendJsonResponse('error', null, 'Todos los campos son obligatorios');
        } else {
            $stmt = $conn->prepare("INSERT INTO Ventas (idEmpleado, idCliente, fecha, total) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisd", $data->idEmpleado, $data->idCliente, $data->fecha, $data->total);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $data->idVenta = $stmt->insert_id;
                sendJsonResponse('success', $data);
            } else {
                sendJsonResponse('error', null, 'Error al insertar la venta');
            }
        }
        break;

    case 'PUT':
        if (isset($_GET['idVenta'])) {
            $idVenta = $conn->real_escape_string($_GET['idVenta']);
            $data = json_decode(file_get_contents('php://input'));
            if (empty($data->idEmpleado) || empty($data->idCliente) || empty($data->fecha) || empty($data->total)) {
                sendJsonResponse('error', null, 'Todos los campos son obligatorios');
            } else {
                $stmt = $conn->prepare("UPDATE Ventas SET idEmpleado = ?, idCliente = ?, fecha = ?, total = ? WHERE idVenta = ?");
                $stmt->bind_param("iisdi", $data->idEmpleado, $data->idCliente, $data->fecha, $data->total, $idVenta);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    sendJsonResponse('success', null);
                } else {
                    sendJsonResponse('error', null, 'Error al actualizar la venta');
                }
            }
        }
        break;

    case 'DELETE':
        if (isset($_GET['idVenta'])) {
            $idVenta = $conn->real_escape_string($_GET['idVenta']);
            $stmt = $conn->prepare("DELETE FROM Ventas WHERE idVenta = ?");
            $stmt->bind_param("i", $idVenta);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                sendJsonResponse('success', null);
            } else {
                sendJsonResponse('error', null, 'Error al eliminar la venta');
            }
        }
        break;
}

$conn->close();
?>

