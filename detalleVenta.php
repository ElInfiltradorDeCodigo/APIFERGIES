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
        if (isset($_GET['idDetalle'])) {
            $idDetalle = $conn->real_escape_string($_GET['idDetalle']);
            $result = $conn->query("SELECT * FROM detalleventa WHERE idDetalle = '$idDetalle'");
            $data = $result->fetch_assoc();
            if ($data) {
                sendJsonResponse('success', $data);
            } else {
                sendJsonResponse('error', null, 'No se encontró el detalle de venta');
            }
        } else {
            $result = $conn->query("SELECT * FROM detalleventa");
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            sendJsonResponse('success', $data);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'));
        if (empty($data->idVenta) || empty($data->idProducto) || empty($data->cantidad) || empty($data->precio)) {
            sendJsonResponse('error', null, 'Todos los campos son obligatorios');
        } else {
            $stmt = $conn->prepare("INSERT INTO detalleventa (idVenta, idProducto, cantidad, precio) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiii", $data->idVenta, $data->idProducto, $data->cantidad, $data->precio);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $data->idDetalle = $stmt->insert_id;
                sendJsonResponse('success', $data);
            } else {
                sendJsonResponse('error', null, 'Error al insertar el detalle de venta');
            }
        }
        break;

    case 'PUT':
        if (isset($_GET['idDetalle'])) {
            $idDetalle = $conn->real_escape_string($_GET['idDetalle']);
            $data = json_decode(file_get_contents('php://input'));
            if (empty($data->idVenta) || empty($data->idProducto) || empty($data->cantidad) || empty($data->precio)) {
                sendJsonResponse('error', null, 'Todos los campos son obligatorios');
            } else {
                $stmt = $conn->prepare("UPDATE detalleventa SET idVenta = ?, idProducto = ?, cantidad = ?, precio = ? WHERE idDetalle = ?");
                $stmt->bind_param("iiiid", $data->idVenta, $data->idProducto, $data->cantidad, $data->precio, $idDetalle);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    sendJsonResponse('success', null);
                } else {
                    sendJsonResponse('error', null, 'Error al actualizar el detalle de venta');
                }
            }
        }
        break;

    case 'DELETE':
        if (isset($_GET['idDetalle'])) {
            $idDetalle = $conn->real_escape_string($_GET['idDetalle']);
            $stmt = $conn->prepare("DELETE FROM detalleventa WHERE idDetalle = ?");
            $stmt->bind_param("i", $idDetalle);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                sendJsonResponse('success', null);
            } else {
                sendJsonResponse('error', null, 'Error al eliminar el detalle de venta');
            }
        }
        break;
}

$conn->close();
?>


